<?php

namespace Drupal\config_import_de\Event;

use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\Event\BundleConfigImportValidate;
use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Entity config importer validation event subscriber.
 */
class ConfigImportDeBundleConfigImportValidate extends BundleConfigImportValidate {

  /**
   * Ensures bundles that will be deleted are not in use.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    foreach ($event->getChangelist('delete') as $config_name) {
      // Get the config entity type ID. This also ensure we are dealing with a
      // configuration entity.
      if ($entity_type_id = $this->configManager->getEntityTypeIdByName($config_name)) {
        $entity_type = $this->entityManager->getDefinition($entity_type_id);
        // Does this entity type define a bundle of another entity type.
        if ($bundle_of = $entity_type->getBundleOf()) {
          // Work out if there are entities with this bundle.
          $bundle_of_entity_type = $this->entityManager->getDefinition($bundle_of);
          $bundle_id = ConfigEntityStorage::getIDFromConfigName($config_name, $entity_type->getConfigPrefix());
          $entity_query = $this->entityManager->getStorage($bundle_of)->getQuery();
          $entity_ids = $entity_query->condition($bundle_of_entity_type->getKey('bundle'), $bundle_id)
            ->accessCheck(FALSE)
            ->execute();

          $config_import_settings = \Drupal::service('config.factory')->getEditable('config_import_de.config');

          $delete_entities_enabled = $config_import_settings->get('delete_detected_entities');
          $debug_mode_enabled = $config_import_settings->get('debug_mode');

          if (!empty($entity_ids)) {
            foreach ($entity_ids as $entity_id) {
              // Delete entities is the option is enabled.
              if ($delete_entities_enabled) {
                $entity = \Drupal::entityTypeManager()->getStorage($bundle_of_entity_type->id())->load($entity_id);
                $entity->delete();
              }

              // Output a list of entities if debug mode is enabled.
              if ($debug_mode_enabled) {
                // TODO Make this create links.
                drupal_set_message($bundle_of_entity_type->id() . ':' . $entity_id . ' must/will be deleted.', 'warning');
              }
            }

            // Only show the error if entity deletion is not enabled.
            if (!$delete_entities_enabled) {
              $entity = $this->entityManager->getStorage($entity_type_id)->load($bundle_id);
              $event->getConfigImporter()->logError($this->t('Entities exist of type %entity_type and %bundle_label %bundle. These entities need to be deleted before importing.', array('%entity_type' => $bundle_of_entity_type->getLabel(), '%bundle_label' => $bundle_of_entity_type->getBundleLabel(), '%bundle' => $entity->label())));
            }
          }
        }
      }
    }
  }

}
