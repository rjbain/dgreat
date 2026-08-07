#!/usr/bin/env bash

set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 <site.env>"
  echo "Example: $0 dgreat.dev"
  exit 1
fi

TARGET="$1"

read -r -d '' PHP_EVAL <<'PHP' || true
$modules_to_remove = [
  'ckeditor',
  'config_direct_save',
  'easy_install',
  'rate',
  'rate_vote_summary',
  'tour',
  'config_filter',
  'upgrade_rector',
  'votingapi',
  'webform_bootstrap',
  'webform_location_geocomplete',
  'webform_migrate',
  'webform_toggles',
  'jquery_ui_slider',
  'jquery_ui_touch_punch',
];

$themes_to_remove = [
  'adminimal_theme',
  'seven',
];

$block_configs_to_delete = [
  'block.block.adminimal_theme_breadcrumbs',
  'block.block.adminimal_theme_content',
  'block.block.adminimal_theme_help',
  'block.block.adminimal_theme_local_actions',
  'block.block.adminimal_theme_login',
  'block.block.adminimal_theme_messages',
  'block.block.adminimal_theme_page_title',
  'block.block.adminimal_theme_primary_local_tasks',
  'block.block.adminimal_theme_secondary_local_tasks',
  'block.block.seven_breadcrumbs',
  'block.block.seven_content',
  'block.block.seven_help',
  'block.block.seven_local_actions',
  'block.block.seven_login',
  'block.block.seven_messages',
  'block.block.seven_page_title',
  'block.block.seven_primary_local_tasks',
  'block.block.seven_secondary_local_tasks',
];

$ext = \Drupal::configFactory()->getEditable('core.extension');
$modules = $ext->get('module') ?: [];
$themes = $ext->get('theme') ?: [];
$removed_modules = [];
$removed_themes = [];

foreach ($modules_to_remove as $name) {
  if (array_key_exists($name, $modules)) {
    unset($modules[$name]);
    $removed_modules[] = $name;
  }
}

foreach ($themes_to_remove as $name) {
  if (array_key_exists($name, $themes)) {
    unset($themes[$name]);
    $removed_themes[] = $name;
  }
}

$ext->set('module', $modules)->set('theme', $themes)->save();
\Drupal::keyValue('system.schema')->deleteMultiple(array_merge($modules_to_remove, $themes_to_remove));

$deleted_block_configs = [];
foreach ($block_configs_to_delete as $name) {
  $config = \Drupal::configFactory()->getEditable($name);
  if (!$config->isNew()) {
    $config->delete();
    $deleted_block_configs[] = $name;
  }
}

print("Removed modules: " . implode(', ', $removed_modules ?: ['none']) . PHP_EOL);
print("Removed themes: " . implode(', ', $removed_themes ?: ['none']) . PHP_EOL);
print("Deleted block configs: " . implode(', ', $deleted_block_configs ?: ['none']) . PHP_EOL);
print("Cleanup complete" . PHP_EOL);
PHP

echo "Running stale extension cleanup on ${TARGET}..."
terminus drush "${TARGET}" -- php:eval "${PHP_EVAL}"

echo
echo "Suggested next commands:"
echo "  terminus drush ${TARGET} -- updatedb -y"
echo "  terminus drush ${TARGET} -- config-import -y"
echo "  terminus drush ${TARGET} -- cr"
