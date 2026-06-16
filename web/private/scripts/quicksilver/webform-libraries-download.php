<?php
/**
 * Quicksilver script: webform-libraries-download.php
 *
 * Downloads Webform's optional JS libraries (Choices, CodeMirror, Select2,
 * etc.) to /web/libraries/. These are gitignored and must be fetched after
 * every deploy.
 */

echo "Downloading Webform external libraries...\n";
passthru('drush webform:libraries:download', $exit_code);

if ($exit_code === 0) {
  echo "Webform libraries downloaded successfully.\n";
}
else {
  echo "ERROR: Webform libraries download failed (exit code: $exit_code).\n";
}
