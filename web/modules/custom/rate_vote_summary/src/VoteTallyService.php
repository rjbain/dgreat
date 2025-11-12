<?php

declare(strict_types=1);

namespace Drupal\rate_vote_summary;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Computes total up/down votes and the current user's vote for an entity.
 *
 * Schema expectations (VotingAPI / Rate):
 *   - table: votingapi_vote
 *   - columns:
 *       entity_type   (e.g., 'node')
 *       entity_id     (int)
 *       value         (1 for up, -1 for down)
 *       user_id       (int, the Drupal user id)
 *       timestamp     (int, vote time)  <-- IMPORTANT: not "created"
 *       rate_widget   or "type" (the Rate widget machine name)
 *
 * Usage:
 *   [$ups, $downs, $myVote] = $tally->getTallies($entity_id);
 *   // $myVote is 1, -1, or NULL if user hasn't voted (or is anonymous).
 */
final class VoteTallyService {

  /**
   * Upvote and downvote numeric values used by Rate's "Thumbs up/down" widget.
   */
  private const UP_VALUE = 1;
  private const DOWN_VALUE = -1;

  /**
   * @param \Drupal\Core\Database\Connection $db
   *   Database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user service.
   * @param string $entityType
   *   Default entity type to query (e.g., 'node').
   * @param string $widgetCol
   *   Column name for the widget identifier ('rate_widget' or 'type').
   * @param string $voteType
   *   The Rate widget machine name to filter by (e.g., 'post_votes').
   */
  public function __construct(
    private Connection $db,
    private AccountProxyInterface $currentUser,
    private string $entityType = 'node',
    private string $widgetCol  = 'rate_widget',
    private string $voteType   = 'post_votes'
  ) {}

  /**
   * Returns [up_total, down_total, my_vote].
   *
   * - up_total/down_total include ALL votes on the entity for this widget.
   * - my_vote is ONLY the logged-in user's latest vote: 1, -1, or NULL.
   *
   * @param int $entity_id
   *   The entity id.
   *
   * @return array{0:int,1:int,2:int|null}
   *   [up_total, down_total, my_vote]
   */
  public function getTallies(int $entity_id): array {
    try {
      $up_total = $this->countByValue($entity_id, self::UP_VALUE);
      $down_total = $this->countByValue($entity_id, self::DOWN_VALUE);
      $my_vote = $this->getMyLatestVote($entity_id);
      return [$up_total, $down_total, $my_vote];
    }
    catch (\Throwable $e) {
      // Fail safe: never break the page; return zeros/NULL.
      return [0, 0, NULL];
    }
  }

  /**
   * Convenience: returns only the current user's latest vote.
   *
   * @param int $entity_id
   * @return int|null 1, -1, or NULL if none/anonymous.
   */
  public function getUserVote(int $entity_id): ?int {
    return $this->getMyLatestVote($entity_id);
  }

  /**
   * Counts votes with a specific value for this entity + widget across all users.
   *
   * @param int $entity_id
   * @param int $value
   *
   * @return int
   */
  private function countByValue(int $entity_id, int $value): int {
    $q = $this->db->select('votingapi_vote', 'v')
      ->condition('v.entity_type', $this->entityType)
      ->condition('v.entity_id', $entity_id)
      ->condition("v.$this->widgetCol", $this->voteType)
      ->condition('v.value', $value, '=');

    // countQuery() clones the base conditions and returns COUNT(*).
    return (int) $q->countQuery()->execute()->fetchField();
  }

  /**
   * Returns the current user's most recent vote value (1, -1) or NULL.
   *
   * NOTE: Anonymous users (uid=0) always return NULL by design.
   *
   * @param int $entity_id
   * @return int|null
   */
  private function getMyLatestVote(int $entity_id): ?int {
    $uid = (int) $this->currentUser->id();
    if ($uid <= 0) {
      return NULL; // Only consider authenticated users.
    }

    $value = $this->db->select('votingapi_vote', 'v')
      ->fields('v', ['value'])
      ->condition('v.entity_type', $this->entityType)
      ->condition('v.entity_id', $entity_id)
      ->condition("v.$this->widgetCol", $this->voteType)
      ->condition('v.user_id', $uid)
      ->orderBy('v.timestamp', 'DESC') // IMPORTANT: schema uses "timestamp"
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($value === FALSE || $value === NULL) {
      return NULL;
    }

    // Normalize to 1 / -1. If a site customized values, coerce sign.
    $v = (int) $value;
    if ($v === self::UP_VALUE) {
      return self::UP_VALUE;
    }
    if ($v === self::DOWN_VALUE) {
      return self::DOWN_VALUE;
    }
    return $v > 0 ? self::UP_VALUE : ($v < 0 ? self::DOWN_VALUE : NULL);
  }

}
