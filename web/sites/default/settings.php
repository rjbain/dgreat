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
 * Always install the 'standard' profile to stop the installer from
 * modifying settings.php.
 *
 * See: tests/installer-features/installer.feature
 */
$settings['install_profile'] = 'standard';

/**
 * Load and export secrets.
 */
$secrets_file = __DIR__ . "/files/private/secrets.json";
if (file_exists($secrets_file)) {
  $secrets = json_decode(file_get_contents($secrets_file), TRUE);
  foreach ($secrets as $key => $secret) {
    putenv("{$key}={$secret}");
  }
}

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

$config['sendgrid_integration.settings']['apikey'] = getenv('sendgrid-api-key');

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

