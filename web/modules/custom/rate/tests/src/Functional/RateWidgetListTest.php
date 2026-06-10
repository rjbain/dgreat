<?php

namespace Drupal\Tests\rate\Functional;

/**
 * Testing the listing functionality for the Rate widget entity.
 *
 * @group rate
 */
class RateWidgetListTest extends RateWidgetTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * The user object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Listing of rate widgets.
   */
  public function testEntityTypeList() {
    $this->user = $this->drupalCreateUser(['administer rate']);
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/structure/rate_widgets');
    $this->assertResponse(200);

    $this->drupalGet('<front>');
    $this->assertResponse(200);
  }

}
