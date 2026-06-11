<?php
/**
 * Quicksilver script: clear-compiled-container.php
 *
 * Runs after sync_code (code deploy) to delete the compiled Drupal service
 * container PHP files from the filesystem.
 *
 * After a major Drupal version upgrade (e.g. D10 → D11), the compiled
 * container stored in sites/default/files/php/storage/ was built against
 * the old core. When the new code is deployed, the stale container causes
 * a fatal error on bootstrap (constructor signatures change, etc.) that
 * prevents `drush cr` from running at all.
 *
 * By deleting these files here — before any drush command runs — Drupal
 * is forced to recompile the container from scratch using the new code.
 *
 * On Pantheon, sites/default/files is mounted at /files.
 */

$storage_paths = [
  '/files/php/storage',
  // Fallback: resolve via symlink from webroot
  __DIR__ . '/../../../sites/default/files/php/storage',
];

$cleared = FALSE;
foreach ($storage_paths as $dir) {
  $real = realpath($dir);
  if ($real && is_dir($real)) {
    $files = glob($real . '/*.php') ?: [];
    foreach ($files as $file) {
      unlink($file);
    }
    echo "Cleared " . count($files) . " compiled container file(s) from: $real\n";
    $cleared = TRUE;
    break;
  }
}

if (!$cleared) {
  echo "No compiled container files found to clear (path does not exist yet).\n";
}
