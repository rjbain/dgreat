<?php

namespace Drupal\dgreat_student_surveys\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\Block\WebformBlock;

/**
 * Provides a 'DgreatModalContactForm' block.
 *
 * @Block(
 *  id = "dgreat_modal_student_survey",
 *  admin_label = @Translation("Student Survey"),
 * )
 */
class ModalStudentSurveyForm extends WebformBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array_merge(parent::build(), [
      '#attached' => [
        'drupalSettings' => [
          'dgreatStudentSurveys' => [
            'hasSeen' => \Drupal::service('session')->get('student_surveys_has_seen_recently'),
          ],
        ],
      ],
    ]);
    // @todo: should be able to set this higher; set low for testing, see #199.
    $build['#cache']['max-age'] = 0;
    \Drupal::service('session')->set('student_surveys_has_seen_recently', TRUE);
    $account = \Drupal::currentUser();
    // @todo: Would prefer to run this in blockAccess, but for some reason it
    // does not run on the first page load after log in.
    if ($this->canAccess($account)) {
      return $build;
    } else {
      // If we don't build the block, make sure the null result isn't cached.
      return [
        '#cache' => [
          'max-age' => 0
        ]
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  protected function canAccess(AccountInterface $account) {
    return $this->isStudent($account)
      && $this->currentCohort($account)
      && !$this->hasAnswered($account);
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  private function currentCohort(AccountInterface $account) {
    $cid = 'dgreat_student_surveys:current_cohort';
    $data = NULL;
    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
    }
    else {
      $conn = Database::getConnection();
      $result = $conn->select('current_survey_students', 'css')
        ->fields('css', ['username'])
        ->execute()->fetchAllAssoc('username');
      $data = array_keys($result);
      \Drupal::cache()->set($cid, $data);
    }

    return in_array($account->getAccountName(), $data, FALSE);
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  private function hasAnswered(AccountInterface $account) {
    $config = $this->getConfiguration();
    $webform = Webform::load($config['webform_id']);

    if (!$webform) {
      return FALSE;
    }

    return \Drupal::entityQuery('webform_submission')
      ->condition('webform_id', $webform->id())
      ->condition('uid', $account->id())
      ->count()
      ->execute() >= 1;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  protected function isStudent(AccountInterface $account) {
    return in_array('student', $account->getRoles());
  }

}
