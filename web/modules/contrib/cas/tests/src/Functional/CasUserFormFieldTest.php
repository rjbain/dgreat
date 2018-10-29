<?php

namespace Drupal\Tests\cas\Functional;

/**
 * Tests modifications to the account and registration forms.
 *
 * @group cas
 */
class CasAccountAndRegistrationFormTest extends CasBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas', 'page_cache', 'dynamic_page_cache'];

  /**
   * Tests that the CAS username field works as expected.
   */
  public function testCasUsernameField() {
    // First test that a normal user has no access to edit their CAS username.
    $test_user_1 = $this->drupalCreateUser([], 'test_user_1');
    $this->drupalLogin($test_user_1);

    $this->drupalGet('/user/' . $test_user_1->id() . '/edit');

    $page = $this->getSession()->getPage();
    $this->assertNull($page->findField('cas_username'), 'CAS username field was found on page when user should not have access.');

    $this->drupalLogout();
    $admin_user = $this->drupalCreateUser(['administer users'], 'test_admin');
    $this->drupalLogin($admin_user);

    $this->drupalGet('/user/' . $test_user_1->id() . '/edit');

    $cas_username_field = $this->getSession()->getPage()->findField('cas_username');
    $this->assertNotNull($cas_username_field, 'CAS username field should exist on user form.');

    // Set the CAS username for this user.
    $edit = [
      'cas_username' => 'test_user_1_cas',
    ];
    $this->drupalPostForm('/user/' . $test_user_1->id() . '/edit', $edit, 'Save');

    // Check that field is still filled in with the CAS username.
    $cas_username_field = $this->getSession()->getPage()->findField('cas_username');
    $this->assertNotNull($cas_username_field, 'CAS username field should exist on user form.');
    $this->assertEquals('test_user_1_cas', $cas_username_field->getValue());

    // Verify data was stored in authmap properly as well.
    $authmap = $this->container->get('externalauth.authmap');
    $this->assertEquals('test_user_1_cas', $authmap->get($test_user_1->id(), 'cas'));

    // Register a new user, attempting to use the same CAS username.
    $new_user_data = [
      'mail' => 'test_user_2@sample.com',
      'name' => 'test_user_2',
      'pass[pass1]' => 'test_user_2',
      'pass[pass2]' => 'test_user_2',
      'cas_username' => 'test_user_1_cas',
    ];
    $this->drupalPostForm('/admin/people/create', $new_user_data, 'Create new account');
    $output = $this->getSession()->getPage()->getContent();

    $validation_error_message = 'The specified CAS username is already in use by another user.';
    $this->assertContains($validation_error_message, $output, 'Expected validation error not found on page.');

    // Submit with proper CAS username, and verify user was created and has the
    // proper CAS username associated.
    $new_user_data['cas_username'] = 'test_user_2_cas';
    $this->drupalPostForm('/admin/people/create', $new_user_data, 'Create new account');
    $output = $this->getSession()->getPage()->getContent();
    $this->assertNotContains($validation_error_message, $output, 'Validation error should not be found.');

    $test_user_2 = $this->container->get('entity_type.manager')->getStorage('user')->loadByProperties(['name' => 'test_user_2']);
    $test_user_2 = reset($test_user_2);
    $this->assertNotNull($test_user_2);
    $authmap = $this->container->get('externalauth.authmap');
    $this->assertEquals($test_user_2->id(), $authmap->getUid('test_user_2_cas', 'cas'));

    // Should be able to clear out the CAS username to remove the authmap entry.
    $edit = ['cas_username' => ''];
    $this->drupalPostForm('/user/' . $test_user_2->id() . '/edit', $edit, 'Save');
    $authmap = $this->container->get('externalauth.authmap');
    $this->assertFalse($authmap->get($test_user_2->id(), 'cas'));
    // Visit the edit page for this user to ensure CAS username field empty.
    $this->drupalGet('/user/' . $test_user_2->id() . '/edit');
    $this->assertEmpty($this->getSession()->getPage()->findField('cas_username')->getValue());
  }

  /**
   * Tests the "restrict password management" feature.
   */
  public function testRestrictedPasswordManagementWorks() {
    $admin = $this->drupalCreateUser(['administer account settings', 'administer users']);
    $non_cas_user = $this->drupalCreateUser();
    $cas_user = $this->drupalCreateUser();

    // Give the second user a CAS username association.
    $this->container->get('cas.user_manager')->setCasUsernameForAccount($cas_user, 'cas_user');

    // Enable the "restrict password management" feature.
    // And disable the "prevent normal login" feature so it doesn't interfere
    // with out logins.
    $this->drupalLogin($admin);
    $edit = [
      'user_accounts[restrict_password_management]' => TRUE,
      'user_accounts[prevent_normal_login]' => FALSE,
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $this->assertEquals(TRUE, $this->config('cas.settings')->get('user_accounts.restrict_password_management'));
    $this->drupalLogout();

    // The CAS module's modifications to the user account form and validation
    // should NOT take effect for non-CAS users, so test that such a user is
    // still able to manage their password and email as usual.
    $this->drupalLogin($non_cas_user);
    $this->drupalGet('/user/' . $non_cas_user->id() . '/edit');
    $page = $this->getSession()->getPage();
    $this->assertNotNull($page->findField('pass[pass1]'));
    $this->assertNotNull($page->findField('pass[pass2]'));
    $this->assertNotNull($page->findField('current_pass'));
    $form_data = [
      'pass[pass1]' => 'newpass',
      'pass[pass2]' => 'newpass',
      'current_pass' => 'incorrectpassword',
      'mail' => 'new-noncasuser-email@sample.com',
    ];
    // First try changing data with wrong password to ensure the protected
    // password constraint still works.
    $this->drupalPostForm('/user/' . $non_cas_user->id() . '/edit', $form_data, 'Save');
    $this->assertSession()->responseContains(t('Your current password is missing or incorrect'));
    // Now again with the correct current password.
    $form_data['current_pass'] = $non_cas_user->pass_raw;
    $this->drupalPostForm('/user/' . $non_cas_user->id() . '/edit', $form_data, 'Save');
    $this->assertSession()->responseContains(t('The changes have been saved.'));

    // For CAS users, we modify the user form to remove the password management
    // fields and remove the protected password constraint that normally
    // prevents changes to an email unless the current password is entered.
    // So here we test that for such a user, the password fields are gone
    // and the user can still update their email address.
    $this->drupalLogout();
    $this->drupalLogin($cas_user);
    $this->drupalGet('/user/' . $cas_user->id() . '/edit');
    $page = $this->getSession()->getPage();
    $this->assertNull($page->findField('pass[pass1]'));
    $this->assertNull($page->findField('pass[pass2]'));
    $this->assertNull($page->findField('current_pass'));
    $form_data = [
      'mail' => 'new-casuser-email@sample.com',
    ];
    $this->drupalPostForm('/user/' . $cas_user->id() . '/edit', $form_data, 'Save');
    $this->assertSession()->responseContains(t('The changes have been saved.'));

    // An admin should still be able to see the password fields the CAS user.
    $this->drupalLogout();
    $this->drupalLogin($admin);
    $this->drupalGet('/user/' . $cas_user->id() . '/edit');
    $page = $this->getSession()->getPage();
    $this->assertNotNull($page->findField('pass[pass1]'));
    $this->assertNotNull($page->findField('pass[pass2]'));

    // Now disable the "restrict password management" feature.
    $edit = [
      'user_accounts[restrict_password_management]' => FALSE,
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $this->assertEquals(FALSE, $this->config('cas.settings')->get('user_accounts.restrict_password_management'));
    $this->drupalLogout();

    // And ensure that the CAS user can now see the password management fields
    // and modify their password and email successfully.
    $this->drupalLogin($cas_user);
    $this->drupalGet('/user/' . $cas_user->id() . '/edit');
    $page = $this->getSession()->getPage();
    $this->assertNotNull($page->findField('pass[pass1]'));
    $this->assertNotNull($page->findField('pass[pass2]'));
    $this->assertNotNull($page->findField('current_pass'));
    $form_data = [
      'pass[pass1]' => 'newpass',
      'pass[pass2]' => 'newpass',
      'current_pass' => 'incorrectpassword',
      'mail' => 'another-new-casuser-email@sample.com',
    ];
    // First try changing data with wrong password.
    $this->drupalPostForm('/user/' . $cas_user->id() . '/edit', $form_data, 'Save');
    $this->assertSession()->responseContains(t('Your current password is missing or incorrect'));
    // Now again with the correct current password.
    $form_data['current_pass'] = $cas_user->pass_raw;
    $this->drupalPostForm('/user/' . $cas_user->id() . '/edit', $form_data, 'Save');
    $this->assertSession()->responseContains(t('The changes have been saved.'));
  }

  /**
   * Tests the restricted email management feature.
   */
  public function testRestrictedEmailManagementWorks() {
    $admin = $this->drupalCreateUser(['administer account settings', 'administer users']);
    $non_cas_user = $this->drupalCreateUser();
    $cas_user = $this->drupalCreateUser();

    // Give the second user a CAS username association.
    $this->container->get('cas.user_manager')->setCasUsernameForAccount($cas_user, 'cas_user');

    // Enable the "restrict email management" feature.
    // Disable the "prevent normal login" feature so it doesn't interfere with
    // out logins.
    $this->drupalLogin($admin);
    $edit = [
      'user_accounts[restrict_email_management]' => TRUE,
      'user_accounts[prevent_normal_login]' => FALSE,
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $this->assertEquals(TRUE, $this->config('cas.settings')->get('user_accounts.restrict_email_management'));
    $this->drupalLogout();

    // The CAS module's modifications to the user account form and validation
    // should NOT take effect for non-CAS users, so test that such a user is
    // still able to manage their email as usual.
    $this->drupalLogin($non_cas_user);
    $this->drupalGet('/user/' . $non_cas_user->id() . '/edit');
    $page = $this->getSession()->getPage();
    $this->assertNotNull($page->findField('mail'));
    $form_data = [
      'current_pass' => 'incorrectpassword',
      'mail' => 'new-noncasuser-email@sample.com',
    ];
    // First try changing data with wrong password to ensure the protected
    // password constraint still works.
    $this->drupalPostForm('/user/' . $non_cas_user->id() . '/edit', $form_data, 'Save');
    $this->assertSession()->responseContains(t('Your current password is missing or incorrect'));
    // Now again with the correct current password.
    $form_data['current_pass'] = $non_cas_user->pass_raw;
    $this->drupalPostForm('/user/' . $non_cas_user->id() . '/edit', $form_data, 'Save');
    $this->assertSession()->responseContains(t('The changes have been saved.'));

    // For CAS users, we modify the user form to disable the email field.
    $this->drupalLogout();
    $this->drupalLogin($cas_user);
    $this->drupalGet('/user/' . $cas_user->id() . '/edit');
    $page = $this->getSession()->getPage();
    $email_field = $page->findField('mail');
    $this->assertNotNull($email_field);
    $this->assertEquals('disabled', $email_field->getAttribute('disabled'));

    // An admin should not see a disabled email field for that same user.
    $this->drupalLogout();
    $this->drupalLogin($admin);
    $this->drupalGet('/user/' . $cas_user->id() . '/edit');
    $page = $this->getSession()->getPage();
    $email_field = $page->findField('mail');
    $this->assertNotNull($email_field);
    $this->assertObjectNotHasAttribute('disabled', $email_field);

    // Now disable the "restrict email management" feature.
    $edit = [
      'user_accounts[restrict_email_management]' => FALSE,
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $this->assertEquals(FALSE, $this->config('cas.settings')->get('user_accounts.restrict_email_management'));
    $this->drupalLogout();

    // And ensure that the email field on the CAS user's profile form is no
    // longer disabled.
    $this->drupalLogin($cas_user);
    $this->drupalGet('/user/' . $cas_user->id() . '/edit');
    $page = $this->getSession()->getPage();
    $email_field = $page->findField('mail');
    $this->assertNotNull($email_field);
    $this->assertEmpty($email_field->getAttribute('disabled'));
  }

}
