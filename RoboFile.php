<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {

  /**
   * Run Behat Tests.
   */
  public function behat() {
    $this->_exec(__DIR__ . '/vendor/bin/drush --root=./web cr -y');
    $this->_exec(__DIR__ . '/vendor/bin/drush --root=./web cim -y');
    $this->_exec(__DIR__ . '/vendor/bin/drush --root=./web updb -y');
    $this->taskBehat(__DIR__ . '/vendor/bin/behat')
         ->format( 'pretty' )
         ->config( __DIR__ . '/tests/behat-pantheon.yml' )
         ->colors()
         ->noInteraction()
         ->run();
  }

  /**
   * Run Unit Tests for all *custom* modules.
   */
  public function unit() {
    $this->taskPHPUnit(__DIR__ . '/vendor/bin/phpunit')
         ->configFile(__DIR__ . '/web/core/phpunit.xml.dist')
         ->files( __DIR__ . '/web/modules/custom/**/tests/' )
         ->run();
  }

  /**
   * Lint custom modules and themes with Drupal coding standards.
   */
  public function lint() {
    $this->taskExec(__DIR__ . '/vendor/bin/phpcbf')
         ->rawArg('--standard=Drupal')
         ->rawArg('--ignore=web/themes/custom/**/node_modules')
         ->rawArg('-n web/modules/custom/* web/themes/custom/*')
         ->run();
  }

  /**
   * Run *full* test suite.
   */
  public function test() {
    $this->lint();
    $this->unit();
    $this->behat();
  }
}
