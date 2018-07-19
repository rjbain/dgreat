<?php

namespace Drupal\dgreat_migration\Plugin\migrate\destination;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use \Drupal\externalauth\Plugin\migrate\destination\Authmap as ExtAuthmap;
use Drupal\migrate\Annotation\MigrateDestination;
use Drupal\migrate\Row;
use Drupal\user\Entity\User;

/**
 * USF Drupal 8 authmap destination.
 *
 * @MigrateDestination(
 *   id = "dgreat_authmap"
 * )
 */
class Authmap extends ExtAuthmap implements ContainerFactoryPluginInterface  {

  public function import(Row $row, array $old_destination_id_values = []) {
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load($row->getDestinationProperty('uid'));
    $provider = $row->getDestinationProperty('provider');
    $authname = $row->getDestinationProperty('authname');

    if (!is_null($account)) {
      $this->authmap->save($account, $provider, $authname);
      return [$account->id()];
    }
    return FALSE;
  }
}