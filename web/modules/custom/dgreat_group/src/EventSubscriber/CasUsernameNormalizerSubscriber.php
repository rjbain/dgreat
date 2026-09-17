<?php

namespace Drupal\dgreat_group\EventSubscriber;

use Drupal\cas\Event\CasPreUserLoadEvent;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Matches CAS usernames to existing authmap casing before lookup.
 */
class CasUsernameNormalizerSubscriber implements EventSubscriberInterface {

  public function __construct(protected Connection $database) {}

  /**
   * Use an existing authmap value when CAS sends different casing.
   */
  public function matchStoredUsernameCasing(CasPreUserLoadEvent $event): void {
    $property_bag = $event->getCasPropertyBag();
    $username = trim($property_bag->getUsername());

    if ($username === '') {
      return;
    }

    $stored_authname = $this->database->select('authmap', 'a')
      ->fields('a', ['authname'])
      ->condition('module', 'cas')
      ->where('LOWER([authname]) = :username', [':username' => strtolower($username)])
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if (is_string($stored_authname) && $stored_authname !== $username) {
      $property_bag->setUsername($stored_authname);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CasPreUserLoadEvent::class => 'matchStoredUsernameCasing',
      'cas.pre_user_load' => 'matchStoredUsernameCasing',
    ];
  }

}
