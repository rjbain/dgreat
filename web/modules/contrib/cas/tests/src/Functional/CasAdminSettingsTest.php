<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests CAS admin settings form.
 *
 * @group cas
 */
class CasAdminSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas'];

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Disable strict schema cheking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that access to the password reset form is disabled for CAS users.
   */
  public function testPasswordResetBehavior() {
    // Create two users, one associated with CAS and one that's not.
    $cas_user = $this->drupalCreateUser([], 'user_with_cas');
    \Drupal::service('cas.user_manager')->setCasUsernameForAccount($cas_user, 'user_with_cas');
    $this->drupalCreateUser([], 'user_without_cas');

    // First, let's test that with this setting disabled, all users can
    // reset their passwords.
    $edit = [
      'user_accounts[restrict_password_management]' => FALSE,
      'user_accounts[email_hostname]' => 'sample.com',
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $this->drupalLogout();

    $this->drupalGet('user/password');

    $this->drupalPostForm('/user/password', ['name' => 'user_with_cas'], 'Submit');
    $this->assertSession()->addressEquals('user/login');
    $this->assertSession()->pageTextContains(t('Further instructions have been sent to your email address.'));

    $this->drupalPostForm('/user/password', ['name' => 'user_without_cas'], 'Submit');
    $this->assertSession()->addressEquals('user/login');
    $this->assertSession()->pageTextContains(t('Further instructions have been sent to your email address.'));

    // Now, enable password restriction and try again, testing that the CAS user
    // cannot reset their password.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'user_accounts[restrict_password_management]' => TRUE,
      'user_accounts[email_hostname]' => 'sample.com',
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $this->drupalLogout();

    $this->drupalGet('user/password');

    $this->drupalPostForm('/user/password', ['name' => 'user_with_cas'], 'Submit');
    $this->assertSession()->addressEquals('user/password');
    $this->assertSession()->pageTextContains(t('The requested account is associated with CAS and its password cannot be managed from this website.'));

    $this->drupalPostForm('/user/password', ['name' => 'user_without_cas'], 'Submit');
    $this->assertSession()->addressEquals('user/login');
    $this->assertSession()->pageTextContains(t('Further instructions have been sent to your email address.'));
  }

}
