<?php
/**
 * Quicksilver script: ckeditor-media-embed-install.php
 *
 * Downloads the @ckeditor/ckeditor5-media-embed JS library required by the
 * ckeditor_media_embed module. The library is not bundled with the module and
 * is gitignored under /web/libraries, so it must be fetched after every deploy.
 */

echo "Installing CKEditor Media Embed library...\n";
passthru('drush ckeditor_media_embed:install', $exit_code);

if ($exit_code === 0) {
  echo "CKEditor Media Embed library installed successfully.\n";
}
else {
  echo "ERROR: CKEditor Media Embed library install failed (exit code: $exit_code).\n";
}
