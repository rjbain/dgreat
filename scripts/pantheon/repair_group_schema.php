<?php

declare(strict_types=1);

use Drupal\Core\Database\Schema;

/**
 * Repairs partially migrated Group v2->v3 tables on cloned environments.
 *
 * Some Pantheon clones have Group v3 config but are missing the
 * group_relationship tables that replaced group_content. This script recreates
 * the missing tables from legacy tables or backup tables when available.
 */

$database = \Drupal::database();
$schema = $database->schema();
$config_factory = \Drupal::configFactory();

/**
 * Quotes an identifier for a direct SQL statement.
 */
function _myusf_group_quote(string $identifier): string {
  return '`' . str_replace('`', '``', $identifier) . '`';
}

/**
 * Returns the first existing table from a list of candidates.
 */
function _myusf_group_pick_source(Schema $schema, array $candidates): ?string {
  foreach ($candidates as $candidate) {
    if ($schema->tableExists($candidate)) {
      return $candidate;
    }
  }
  return NULL;
}

/**
 * Returns the first table matching a SQL LIKE pattern.
 */
function _myusf_group_pick_pattern(Schema $schema, string $pattern): ?string {
  $matches = $schema->findTables($pattern);
  if ($matches) {
    ksort($matches);
    return array_key_first($matches);
  }
  return NULL;
}

/**
 * Creates a table by cloning an existing source table definition.
 */
function _myusf_group_create_like(\Drupal\Core\Database\Connection $database, string $target, string $source): void {
  $database->query(sprintf(
    'CREATE TABLE %s LIKE %s',
    _myusf_group_quote($target),
    _myusf_group_quote($source)
  ));
}

/**
 * Copies all rows from one table to another.
 */
function _myusf_group_copy_all(\Drupal\Core\Database\Connection $database, string $target, string $source): void {
  $database->query(sprintf(
    'INSERT INTO %s SELECT * FROM %s',
    _myusf_group_quote($target),
    _myusf_group_quote($source)
  ));
}

$messages = [];

if (!$schema->tableExists('group_relationship')) {
  $source = _myusf_group_pick_source($schema, [
    'group_content',
  ]) ?? _myusf_group_pick_pattern($schema, 'group_content_backup_%');

  if ($source) {
    _myusf_group_create_like($database, 'group_relationship', $source);
    _myusf_group_copy_all($database, 'group_relationship', $source);
    $messages[] = "Created group_relationship from {$source}.";
  }
  else {
    $messages[] = 'Missing group_relationship and no legacy source table was found.';
  }
}

if (!$schema->tableExists('group_relationship__group_roles')) {
  $source = _myusf_group_pick_source($schema, [
    'group_content__group_roles',
  ]);

  if ($source) {
    _myusf_group_create_like($database, 'group_relationship__group_roles', $source);
    _myusf_group_copy_all($database, 'group_relationship__group_roles', $source);
    $messages[] = "Created group_relationship__group_roles from {$source}.";
  }
}

if (!$schema->tableExists('group_relationship_field_data')) {
  $source = _myusf_group_pick_source($schema, [
    'group_content_field_data',
  ]) ?? _myusf_group_pick_pattern($schema, 'group_content_field_data_backup_%');

  if ($source) {
    _myusf_group_create_like($database, 'group_relationship_field_data', $source);

    $database->query("
      ALTER TABLE `group_relationship_field_data`
        ADD COLUMN `plugin_id` varchar(64) NOT NULL DEFAULT '' AFTER `changed`,
        ADD COLUMN `group_type` varchar(32) CHARACTER SET ascii DEFAULT NULL COMMENT 'The ID of the target entity.' AFTER `plugin_id`,
        ADD KEY `group_relationship__load_by_group` (`gid`,`plugin_id`,`entity_id`),
        ADD KEY `group_relationship__load_by_entity` (`entity_id`,`plugin_id`),
        ADD KEY `group_relationship__load_by_plugin` (`plugin_id`),
        ADD KEY `group_relationship__sync_scope_checks` (`group_type`,`plugin_id`),
        ADD KEY `group_relationship_field__group_type__target_id` (`group_type`)
    ");

    $relationship_types = [];
    foreach ($config_factory->listAll('group.relationship_type.') as $config_name) {
      $config = $config_factory->get($config_name);
      $relationship_type_id = $config->get('id') ?: substr($config_name, strlen('group.relationship_type.'));
      $relationship_types[$relationship_type_id] = [
        'plugin_id' => (string) ($config->get('content_plugin') ?? ''),
        'group_type' => $config->get('group_type'),
      ];
    }

    foreach ($relationship_types as $relationship_type_id => $definition) {
      $database->query(
        "INSERT INTO `group_relationship_field_data`
          (`id`, `type`, `langcode`, `gid`, `entity_id`, `label`, `uid`, `created`, `changed`, `plugin_id`, `group_type`, `default_langcode`)
         SELECT `id`, `type`, `langcode`, `gid`, `entity_id`, `label`, `uid`, `created`, `changed`, :plugin_id, :group_type, `default_langcode`
         FROM `" . $source . "`
         WHERE `type` = :relationship_type",
        [
          ':plugin_id' => $definition['plugin_id'],
          ':group_type' => $definition['group_type'],
          ':relationship_type' => $relationship_type_id,
        ]
      );
    }

    $known_types = array_keys($relationship_types);
    $unmapped_query = $database->select($source, 'gcfdb')
      ->fields('gcfdb', ['type'])
      ->distinct();
    if ($known_types) {
      $unmapped_query->condition('type', $known_types, 'NOT IN');
    }
    $unmapped_types = $unmapped_query->execute()->fetchCol();

    foreach ($unmapped_types as $relationship_type_id) {
      $database->query(
        "INSERT INTO `group_relationship_field_data`
          (`id`, `type`, `langcode`, `gid`, `entity_id`, `label`, `uid`, `created`, `changed`, `plugin_id`, `group_type`, `default_langcode`)
         SELECT `id`, `type`, `langcode`, `gid`, `entity_id`, `label`, `uid`, `created`, `changed`, '', NULL, `default_langcode`
         FROM `" . $source . "`
         WHERE `type` = :relationship_type",
        [
          ':relationship_type' => $relationship_type_id,
        ]
      );
    }

    $messages[] = "Created group_relationship_field_data from {$source}.";
  }
  else {
    $messages[] = 'Missing group_relationship_field_data and no legacy source table was found.';
  }
}

if (empty($messages)) {
  $messages[] = 'Group schema already intact.';
}

foreach ($messages as $message) {
  print $message . PHP_EOL;
}
