<?php
/**
 * Created by PhpStorm.
 * User: dustinleblanc
 * Date: 6/14/18
 * Time: 10:26 AM
 */

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
    return $this->select('cas_user', 'a')->fields('a');
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