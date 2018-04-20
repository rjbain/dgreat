<?php


namespace Drupal\dgreat_migration\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\user\Entity\User;
use Drupal\dgreat_group\DgreatGroup;

/**
 * Class PostMigrationSubscriber.
 *
 * Run our user flagging after the last node migration is run.
 *
 * @package Drupal\dgreat_migration
 */
class PostMigrationSubscriber implements EventSubscriberInterface {

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    return $events;
  }

  /**
   * Check for our specified last fav links migration and run our flagging
   * mechanisms.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {
    if ($event->getMigration()->getBaseId() == 'upgrade_d7_node_oa_group_fav_links') {
      $this->flagAllUsers();
    }
  }

  /**
   * Updates all default flags on our users.
   */
  private function flagAllUsers() {
    $users = User::loadMultiple();
    foreach ($users as $user) {
      drush_print(dt('Adding flags to uid !uid', ['!uid' => $user->id()]));
      $add = (new DgreatGroup($user))->flagUserDefaultContent('field_user_group');
    }
  }
}