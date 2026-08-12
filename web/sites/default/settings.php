<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 */

$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config';

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

# When on Pantheon, connect to a D7 database.
$migrate_settings = __DIR__ . "/settings.migrate-on-pantheon.php";
if (file_exists($migrate_settings) && isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  include $migrate_settings;
}

// CAS Hostname settings
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
    // If it's the live environment, set the CAS hostname to point to prod
    if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
        $config['cas.settings']['server']['hostname'] = 'usfcas.usfca.edu';
    }
    else {
        // Use test server on every other Pantheon environment.
        $config['cas.settings']['server']['hostname'] = 'amidala.usfca.edu';
    }
}

if (isset($_SERVER['PANTHEON_ENVIRONMENT']) && php_sapi_name() != "cli") {
    if ($_SERVER["HTTP_HOST"] == "ets.usfca.edu") {
      header("HTTP/1.0 301 Moved Permanently");
      header("Location: https://myusf.usfca.edu/its/ets");
      exit();
    }   
}

if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
  // Redirect to https://$primary_domain in the Live environment
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    $primary_domain = 'myusf.usfca.edu';
  }
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
  }

  if ($_SERVER['HTTP_HOST'] != $primary_domain
      || !isset($_SERVER['HTTP_USER_AGENT_HTTPS'])
      || $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON' ) {

    # Name transaction "redirect" in New Relic for improved reporting (optional)
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://'. $primary_domain . $_SERVER['REQUEST_URI']);
    exit();
  }
  // Drupal 8 Trusted Host Settings
  if (is_array($settings)) {
    $settings['trusted_host_patterns'] = array('^'. preg_quote($primary_domain) .'$');
  }
}

// Configure Redis

if (defined('PANTHEON_ENVIRONMENT')) {
    // Include the Redis services.yml file. Adjust the path if you installed to a contrib or other subdirectory.
    $settings['container_yamls'][] = 'modules/redis/example.services.yml';

    //phpredis is built into the Pantheon application container.
    $settings['redis.connection']['interface'] = 'PhpRedis';
    // These are dynamic variables handled by Pantheon.
    $settings['redis.connection']['host']      = $_ENV['CACHE_HOST'];
    $settings['redis.connection']['port']      = $_ENV['CACHE_PORT'];
    $settings['redis.connection']['password']  = $_ENV['CACHE_PASSWORD'];

    $settings['cache']['default'] = 'cache.backend.redis'; // Use Redis as the default cache.
    $settings['cache_prefix']['default'] = 'pantheon-redis';

    // Set Redis to not get the cache_form (no performance difference).
    $settings['cache']['bins']['form']      = 'cache.backend.database';
}

// Redirect thridparty css from old path to new.
if (isset($_SERVER['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli' && $_SERVER['REQUEST_URI'] == '/sites/all/themes/usf_oa_radix/assets/stylesheets/thirdparty/myusf_template.css') {
        $newurl = '/themes/custom/myusf/thirdparty/myusf_template.css';
        header('HTTP/1.1 301 Moved Permanently');
        header("Location: $newurl");
        exit();
}

// Redirect thridparty css from old path to new.
if (isset($_SERVER['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli' && $_SERVER['REQUEST_URI'] == '/sites/all/themes/usf_oa_radix/assets/stylesheets/thirdparty/site.css') {
    $newurl = '/themes/custom/myusf/thirdparty/site.css';
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: $newurl");
    exit();
}

// Redirect ITS file, per Nick Reccia.
if (isset($_SERVER['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli' && $_SERVER['REQUEST_URI'] == '/system/files/its-files/information_security_policy.pdf') {
    $newurl = '/its/policies/information_security_policy';
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: $newurl");
    exit();
}

// Temp file setting for containerized env
// Make sure to FTP and add directory to file structure in test and live
if (($_ENV['PANTHEON_ENVIRONMENT'] === 'live') || ($_ENV['PANTHEON_ENVIRONMENT'] === 'test'))  {
  $config['system.file']['path']['temporary'] = __DIR__ . '/files/tmp';
  $settings['file_temp_path'] = __DIR__ . '/files/tmp';
}

// On Pantheon dev and multidev environments, redirect the compiled Drupal service
// container (php_storage) to /tmp instead of sites/default/files/php/storage.
//
// Why: when a multidev is created from dev, Pantheon clones the files mount —
// including any compiled container PHP files built against the old Drupal version.
// If a major upgrade (e.g. D10→D11) changed constructor signatures, the stale
// compiled container causes a fatal error on the very first `drush cr`.
//
// /tmp on Pantheon is per-appserver and ephemeral — it is never cloned between
// environments, so there can be no stale container from a previous Drupal version.
// The container is simply recompiled fresh on first bootstrap after each deploy.
if (isset($_ENV['PANTHEON_ENVIRONMENT'])
  && $_ENV['PANTHEON_ENVIRONMENT'] !== 'live'
  && $_ENV['PANTHEON_ENVIRONMENT'] !== 'test') {
  $settings['php_storage']['service_container']['directory'] = '/tmp/drupal_php_storage';
  $settings['php_storage']['twig']['directory'] = '/tmp/drupal_php_storage';
  // Force the 'container' cache bin to use MySQL instead of Redis.
  //
  // On Pantheon, Redis is the default cache backend AND is cloned from dev
  // when a multidev is created. This means the Redis 'container' bin can
  // contain a compiled container definition built against the old Drupal
  // version (e.g. D10). Even with php_storage redirected to /tmp (empty),
  // Drupal falls back to the 'container' cache bin to regenerate the PHP
  // file — and if that bin returns a D10 definition, the resulting container
  // calls MemoryBackend::__construct() with 0 args (D11 requires 1), causing
  // a fatal error before `drush cr` can complete.
  //
  // By forcing 'container' to use the database backend, our CI pre-step of
  // TRUNCATE cache_container (MySQL) reliably removes the stale definition,
  // so Drupal compiles a fresh D11 container on first bootstrap.
  $settings['cache']['bins']['container'] = 'cache.backend.database';
}

// Drupal recommends READ COMMITTED for MySQL/MariaDB.
if (!empty($databases) && is_array($databases)) {
  foreach ($databases as $database_key => $targets) {
    if (!is_array($targets)) {
      continue;
    }
    foreach ($targets as $target_key => $connection) {
      if (($connection['driver'] ?? NULL) !== 'mysql') {
        continue;
      }
      $databases[$database_key][$target_key]['init_commands']['isolation_level'] = 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED';
    }
  }
}
