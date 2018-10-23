<?php

namespace Drupal\dgreat_migration\Plugin\migrate\source;

use Drupal\externalauth\Plugin\migrate\source\Authmap;

/**
 * Drupal cas_user source from database.
 *
 * @MigrateSource(
 *   id = "cas_user",
 *   source_module = "user"
 * )
 */
class CasUser extends Authmap {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('cas_user', 'a')->fields('a');
    $query->addJoin('left', 'users', 'u', 'u.uid = a.uid AND u.login >= 1500221245');
    $query->condition('u.login', 1500221245, '>=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uid' => $this->t('User’s users.uid.'),
      'aid' => $this->t('Unique authentication ID.'),
      'cas_name' => $this->t('Unique username in CAS system.'),
    ];
  }

}
