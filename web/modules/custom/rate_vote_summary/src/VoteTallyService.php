<?php

namespace Drupal\rate_vote_summary;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Computes vote tallies and the current viewer's vote.
 */
class VoteTallyService {

  /** @var \Drupal\Core\Database\Connection|null */
  protected ?Connection $db = null;

  /** @var \Drupal\Core\Session\AccountInterface|null */
  protected ?AccountInterface $currentUser = null;

  /** @var \Symfony\Component\HttpFoundation\RequestStack|null */
  protected ?RequestStack $requestStack = null;

  /** @var string Widget machine name, e.g. "post_votes". */
  protected string $voteType = 'post_votes';

  /** @var string Entity type id, e.g. "node". */
  protected string $entityType = 'node';

  /** @var string Column for user id in votingapi_vote ("user_id" or legacy "uid"). */
  protected string $userCol = 'user_id';

  /** @var string Column for widget tag in votingapi_vote ("rate_widget" or legacy "type"). */
  protected string $widgetCol = 'rate_widget';

  /** @var bool Table has vote_source column? */
  protected bool $hasVoteSource = false;

  /** @var bool Table has remote_addr column? */
  protected bool $hasRemoteAddr = false;

  /** @var string|null Optionally constrain to bundle like "updown" or "fivestar". */
  protected ?string $hardVoteBundle = null; // e.g. 'updown'

  public function __construct(
    ?Connection $db,
    ?AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    ?RequestStack $request_stack = null
  ) {
    // Be defensive: allow nulls and recover at runtime.
    $this->db = $db ?? Database::getConnection();
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack ?? \Drupal::service('request_stack');

    $config = $config_factory->get('rate_vote_summary.settings');
    if ($config) {
      $this->voteType = (string) ($config->get('vote_type') ?? $this->voteType);
      $this->entityType = (string) ($config->get('entity_type') ?? $this->entityType);
    }

    // Detect schema differences between VotingAPI versions.
    $schema = $this->db->schema();
    $this->userCol       = $schema->fieldExists('votingapi_vote', 'user_id') ? 'user_id' : 'uid';
    $this->widgetCol     = $schema->fieldExists('votingapi_vote', 'rate_widget') ? 'rate_widget' : 'type';
    $this->hasVoteSource = $schema->fieldExists('votingapi_vote', 'vote_source');
    $this->hasRemoteAddr = false; // not in your schema


    // If you want to lock to the thumbs widget bundle explicitly, uncomment:
    // $this->hardVoteBundle = 'updown';
  }

  /**
   * Returns [up_total, down_total, my_vote].
   *
   * - up_total: int (count value = 1)
   * - down_total: int (count value = -1)
   * - my_vote: 1 | -1 | NULL
   */
  public function getTallies(int $entity_id): array {
    // ---- Totals grouped by value (1 = up, -1 = down) -----------------------
    $totals_q = $this->db->select('votingapi_vote', 'v')
      ->fields('v', ['value'])
      ->condition('v.entity_type', $this->entityType)
      ->condition('v.entity_id', $entity_id)
      ->condition("v.$this->widgetCol", $this->voteType);

    if ($this->hardVoteBundle) {
      $totals_q->condition('v.type', $this->hardVoteBundle);
    }

    $totals_q->addExpression('COUNT(*)', 'count');
    $totals_q->groupBy('v.value');

    $totals = $totals_q->execute()->fetchAllKeyed(0, 1);
    $up_total = (int) ($totals[1] ?? 0);
    $down_total = (int) ($totals[-1] ?? 0);

    // ---- Current viewer's latest vote --------------------------------------
    $my_vote = $this->resolveMyVote($entity_id);

    return [$up_total, $down_total, $my_vote];
  }

  /**
 * Resolve the current viewer's latest vote for this entity/widget.
 */
protected function resolveMyVote(int $entity_id): ?int {
  // 0) Preferred: use VotingAPI's Vote entity storage (schema-agnostic).
  $vote = $this->loadLatestVoteEntity($entity_id);
  if ($vote !== null) {
    return $vote;
  }

  // 1) Authenticated user (DB fallback).
  if ($this->currentUser && $this->currentUser->isAuthenticated()) {
    $auth_vote = $this->lookupLatestVoteDb($entity_id, [
      [$this->userCol, (int) $this->currentUser->id()],
    ]);
    if ($auth_vote !== null) {
      return $auth_vote;
    }
  }

  // 2) Session-based (anonymous) fallback.
  if ($this->hasVoteSource && $this->requestStack) {
    $req = $this->requestStack->getCurrentRequest();
    if ($req && $req->hasSession()) {
      $sid = $req->getSession()->getId();
      if ($sid) {
        $session_vote = $this->lookupLatestVoteDb($entity_id, [
          ['vote_source', 'session:' . $sid],
        ]);
        if ($session_vote !== null) {
          return $session_vote;
        }
      }
    }
  }

  // 3) IP-based fallback.
//   if ($this->hasRemoteAddr && $this->requestStack) {
//     $req = $this->requestStack->getCurrentRequest();
//     if ($req) {
//       $ip = $req->getClientIp();
//       if ($ip) {
//         $ip_vote = $this->lookupLatestVoteDb($entity_id, [
//           ['remote_addr', $ip],
//         ]);
//         if ($ip_vote !== null) {
//           return $ip_vote;
//         }
//       }
//     }
//   }

  return null;
}



/**
 * Try to load the latest vote via the Vote entity (schema-agnostic).
 * Returns 1, -1, or NULL.
 */
protected function loadLatestVoteEntity(int $entity_id): ?int {
  try {
    $storage = \Drupal::entityTypeManager()->getStorage('vote');

    // Build an entity query that matches this entity + widget.
    $query = $storage->getQuery()
      ->condition('entity_type', $this->entityType)
      ->condition('entity_id', $entity_id)
      ->condition($this->widgetCol === 'rate_widget' ? 'rate_widget' : 'type', $this->voteType)
      ->sort('timestamp', 'DESC')
      ->range(0, 1);

    // Prefer to bind to the current user if authenticated.
    if ($this->currentUser && $this->currentUser->isAuthenticated()) {
      // On Vote entities, the field is 'user_id' (a user reference).
      $query->condition('user_id', (int) $this->currentUser->id());
    }

    $ids = $query->execute();
    if (!$ids) {
      // If we filtered by user and found nothing, try again without user_id
      // (e.g., session/anonymous votes that later became logged-in).
      if ($this->currentUser && $this->currentUser->isAuthenticated()) {
        $queryNoUser = $storage->getQuery()
          ->condition('entity_type', $this->entityType)
          ->condition('entity_id', $entity_id)
          ->condition($this->widgetCol === 'rate_widget' ? 'rate_widget' : 'type', $this->voteType)
          ->sort('timestamp', 'DESC')
          ->range(0, 1);
        $ids = $queryNoUser->execute();
      }
    }

    if ($ids) {
      $entities = $storage->loadMultiple($ids);
      $vote = reset($entities);
      if ($vote) {
        // Value is typically on the base field 'value'.
        $value = $vote->get('value')->value ?? null;
        return is_numeric($value) ? (int) $value : null;
      }
    }
  }
  catch (\Throwable $e) {
    // If anything goes sideways, fall back to DB method.
  }
  return null;
}



/**
 * DB fallback: fetch latest vote value with extra conditions.
 *
 * @param int $entity_id
 * @param array<array{0:string,1:mixed}> $extraConditions
 *   Column/value pairs, e.g. [['user_id', 123]] or [['vote_source', 'session:...']]
 */
protected function lookupLatestVoteDb(int $entity_id, array $extraConditions): ?int {
  try {
    $q = $this->db->select('votingapi_vote', 'v')
      ->fields('v', ['value'])
      ->condition('v.entity_type', $this->entityType)
      ->condition('v.entity_id', $entity_id)
      ->condition("v.$this->widgetCol", $this->voteType)
      ->orderBy('v.timestamp', 'DESC')
      ->range(0, 1);

    if ($this->hardVoteBundle) {
      $q->condition('v.type', $this->hardVoteBundle);
    }
    foreach ($extraConditions as [$col, $val]) {
      $q->condition("v.$col", $val);
    }

    $value = $q->execute()->fetchField();
    return is_numeric($value) ? (int) $value : null;
  }
  catch (\Exception $e) {
    return null;
  }
}

}
