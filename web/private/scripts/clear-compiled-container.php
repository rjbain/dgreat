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
 * Quicksilver webphp scripts run from the webroot (/code/web/).
 */

echo "Clearing compiled Drupal service container...\n";

$storage_paths = [
  '/files/php/storage',
  __DIR__ . '/../../../sites/default/files/php/storage',
];

$deleted = 0;
$cleared = FALSE;

foreach ($storage_paths as $dir) {
  $real = realpath($dir);
  if ($real && is_dir($real)) {
    // Use RecursiveIterator to handle hashed subdirectories.
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($real, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'php') {
        unlink($file->getPathname());
        $deleted++;
      }
    }
    echo "Cleared $deleted compiled container file(s) from: $real\n";
    $cleared = TRUE;
    break;
  }
}

if (!$cleared) {
  echo "No compiled container storage directory found — nothing to clear.\n";
  echo "Checked paths:\n";
  foreach ($storage_paths as $p) {
    echo "  $p (realpath: " . (realpath($p) ?: 'does not exist') . ")\n";
  }
}
