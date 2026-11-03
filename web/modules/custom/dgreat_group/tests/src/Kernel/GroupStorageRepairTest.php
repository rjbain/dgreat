<?php

namespace Drupal\Tests\dgreat_group\Kernel;

use Drupal\Core\Utility\UpdateException;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests recovery from the legacy Group metadata-only repair.
 */
#[Group('dgreat_group')]
#[RunTestsInSeparateProcesses]
class GroupStorageRepairTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'field', 'text', 'options', 'entity', 'flexible_permissions', 'group'];

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../dgreat_group.install';
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_relationship');
    $definition = $this->container->get('entity_type.manager')->getDefinition('group_config_wrapper');
    $this->container->get('entity.last_installed_schema.repository')->setLastInstalledDefinition($definition);
  }

  public function testMissingStorageIsInstalledAndRepairIsIdempotent(): void {
    dgreat_group_update_8105();
    $schema = $this->container->get('database')->schema();
    $this->assertTrue($schema->tableExists('group_config_wrapper'));
    foreach (['id', 'bundle', 'entity_id'] as $field) {
      $this->assertTrue($schema->fieldExists('group_config_wrapper', $field));
    }
    $changes = $this->container->get('entity.definition_update_manager')->getChangeList();
    $this->assertArrayNotHasKey('group_config_wrapper', $changes);
    $this->assertArrayNotHasKey('group_relationship', $changes);
    dgreat_group_update_8105();
    $changes = $this->container->get('entity.definition_update_manager')->getChangeList();
    $this->assertArrayNotHasKey('group_config_wrapper', $changes);
    $this->assertArrayNotHasKey('group_relationship', $changes);
  }

  public function testLegacyIndexTableMetadataIsNormalized(): void {
    $repository = $this->container->get('keyvalue')->get('entity.storage_schema.sql');
    $key = 'group_relationship.entity_schema_data';
    $metadata = $repository->get($key);
    foreach (['group_relationship' => 'group_content', 'group_relationship_field_data' => 'group_content_field_data'] as $current => $legacy) {
      $metadata[$legacy] = $metadata[$current];
      unset($metadata[$current]);
    }
    $repository->set($key, $metadata);
    foreach ($this->container->get('entity_field.manager')->getFieldStorageDefinitions('group_relationship') as $name => $definition) {
      $field_key = 'group_relationship.field_schema_data.' . $name;
      $field_schema = $repository->get($field_key, []);
      foreach (array_keys($field_schema) as $table) {
        $legacy_table = 'group_content' . substr($table, strlen('group_relationship'));
        $field_schema[$legacy_table] = $field_schema[$table];
        unset($field_schema[$table]);
      }
      $repository->set($field_key, $field_schema);
    }
    $repository->delete('group_relationship.field_schema_data.plugin_id');
    $repository->delete('group_relationship.field_schema_data.group_type');
    $database = $this->container->get('database');
    $database->insert('group_relationship')->fields(['id' => 1, 'uuid' => 'c9587f02-a1b4-4aeb-adac-582c85c55d46', 'type' => 'test', 'langcode' => 'en'])->execute();
    $row = ['id' => 1, 'type' => 'test', 'langcode' => 'en', 'uid' => 1, 'gid' => 1, 'entity_id' => 1, 'label' => 'Preserve relationship', 'created' => 1, 'changed' => 1, 'plugin_id' => 'group_membership', 'group_type' => 'groups', 'default_langcode' => 1];
    $database->insert('group_relationship_field_data')->fields($row)->execute();
    $database->schema()->changeField('group_relationship_field_data', 'plugin_id', 'plugin_id', ['type' => 'varchar', 'length' => 64, 'not null' => TRUE]);
    $database->schema()->changeField('group_relationship_field_data', 'group_type', 'group_type', ['type' => 'varchar_ascii', 'length' => 32, 'not null' => TRUE]);
    $before = $database->select('group_relationship_field_data', 'r')->fields('r')->execute()->fetchAssoc();
    dgreat_group_update_8105();
    $this->assertSame($before, $database->select('group_relationship_field_data', 'r')->fields('r')->execute()->fetchAssoc());
    $updated = $repository->get($key);
    $this->assertArrayNotHasKey('group_content', $updated);
    $this->assertArrayNotHasKey('group_content_field_data', $updated);
    $this->assertArrayHasKey('group_relationship', $updated);
    $this->assertArrayHasKey('group_relationship_field_data', $updated);
    $this->assertArrayNotHasKey('group_relationship', $this->container->get('entity.definition_update_manager')->getChangeList());
  }

  public function testPartialWrapperSchemaIsRejectedBeforeChanges(): void {
    $this->container->get('database')->schema()->createTable('group_config_wrapper', [
      'fields' => ['id' => ['type' => 'serial', 'not null' => TRUE]],
      'primary key' => ['id'],
    ]);
    $this->expectException(UpdateException::class);
    $this->expectExceptionMessage('partial field schema');
    dgreat_group_update_8105();
  }

}
