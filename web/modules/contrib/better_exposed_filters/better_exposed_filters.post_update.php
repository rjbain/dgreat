<?php

/**
 * @file
 * Post update functions for Better Exposed Filters.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\better_exposed_filters\BetterExposedFiltersConfigUpdater;
use Drupal\views\ViewEntityInterface;

/**
 * Implements hook_removed_post_updates().
 */
function better_exposed_filters_removed_post_updates(): array {
  return [
    'better_exposed_filters_post_update_combine_param' => '8.0.0',
    'better_exposed_filters_post_update_soft_limit' => '8.0.0',
    'better_exposed_filters_post_update_single_checkbox_param_key' => '8.0.0',
    'better_exposed_filters_post_update_open_by_default_param_key' => '8.0.0',
    'better_exposed_filters_post_update_field_classes_param_key' => '8.0.0',
    'better_exposed_filters_post_update_slider_tooltip_keys' => '8.0.0',
    'better_exposed_filters_post_update_autosubmit_breakpoint_key' => '8.0.0',
    'better_exposed_filters_post_update_autosubmit_sort_only_key' => '8.0.0',
    'better_exposed_filters_post_update_sort_options_defaults' => '8.0.0',
    'better_exposed_filters_post_update_slider_placement_key' => '8.0.0',
    'better_exposed_filters_post_update_remove_sort_options_unsupported_key' => '8.0.0',
    'better_exposed_filters_post_update_scrollable_params' => '8.0.0',
  ];
}

/**
 * Add soft_limit_include_children param key.
 */
function better_exposed_filters_post_update_soft_limit_include_children(?array &$sandbox = NULL): void {
  /** @var \Drupal\better_exposed_filters\BetterExposedFiltersConfigUpdater $config_updater */
  $config_updater = \Drupal::classResolver(BetterExposedFiltersConfigUpdater::class);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($config_updater): bool {
    return $config_updater->updateSoftLimitIncludeChildrenParam($view);
  });
}

/**
 * Backfill default values for the new "no_reset" filter and sort options.
 */
function better_exposed_filters_post_update_no_reset_keys(?array &$sandbox = NULL): void {
  /** @var \Drupal\better_exposed_filters\BetterExposedFiltersConfigUpdater $config_updater */
  $config_updater = \Drupal::classResolver(BetterExposedFiltersConfigUpdater::class);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($config_updater): bool {
    return $config_updater->updateAddNoResetKeys($view);
  });
}

/**
 * Backfill default values for the new "preserve URL query params" options.
 */
function better_exposed_filters_post_update_preserve_url_params_keys(?array &$sandbox = NULL): void {
  /** @var \Drupal\better_exposed_filters\BetterExposedFiltersConfigUpdater $config_updater */
  $config_updater = \Drupal::classResolver(BetterExposedFiltersConfigUpdater::class);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($config_updater): bool {
    return $config_updater->updateAddPreserveUrlParamsKeys($view);
  });
}
