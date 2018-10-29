<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\cas\CasPropertyBag;

/**
 * Class CasEventsTest.
 *
 * @group cas
 */
class CasEventsTest extends CasBrowserTestBase {

  public static $modules = ['cas', 'cas_test'];

  /**
   * Tests we can use the CasPreRegisterEvent to alter user properties.
   */
  public function testSettingPropertiesOnRegistration() {
    /* The "cas_test" module includes a subscriber to CasPreRegisterEvent
     * which will prefix all auto-registered users with "testing_"
     */
    $this->drupalLogin($this->drupalCreateUser(['administer account settings']));
    $edit = [
      'user_accounts[auto_register]' => TRUE,
      'user_accounts[email_hostname]' => 'sample.com',
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');

    $cas_property_bag = new CasPropertyBag('foo');
    \Drupal::service('cas.user_manager')->login($cas_property_bag, 'fake_ticket_string');

    $this->assertFalse(user_load_by_name('foo'), 'User with name "foo" exists, but should not.');
    $this->assertNotFalse(user_load_by_name('testing_foo'), 'User with name "testing_foo" was not found.');
  }

}
