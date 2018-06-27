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
         ->configFile(__DIR__ . '/phpunit.xml.dist')
         ->run();
  }

  /**
   * Lint custom modules and themes with Drupal coding standards.
   */
  public function lint() {
    $lintRun = $this->taskExec(__DIR__ . '/vendor/bin/phpcs')
         ->rawArg('--standard=Drupal')
         ->rawArg('-n web/modules/custom/* web/themes/custom/* --ignore=*/node_modules/*')
         ->run();
    if (!$lintRun->wasSuccessful() &&
      $this->confirm('Do you want to fix linting errors?')) {
      $this->fix();
    }
  }

  /**
   * Fix linting issues
   */
  public function fix() {
    $this->taskExec(__DIR__ . '/vendor/bin/phpcbf')
         ->rawArg('--standard=Drupal')
         ->rawArg('-n web/modules/custom/* web/themes/custom/* --ignore=*/node_modules/*')
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