<?php
/**
 * Quicksilver script: chosen-library-download.php
 *
 * Downloads the Chosen JS library required by the chosen module to
 * /web/libraries/chosen. The library is gitignored and must be fetched
 * after every deploy.
 */

echo "Downloading Chosen JS library...\n";
passthru('drush chosen:plugin', $exit_code);

if ($exit_code === 0) {
  echo "Chosen library installed successfully.\n";
}
else {
  echo "ERROR: Chosen library download failed (exit code: $exit_code).\n";
}
