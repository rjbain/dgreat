<?php

namespace Drupal\config_import_de;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class ConfigImportDeServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides config import validator class to delete any entities if needed.
    $definition = $container->getDefinition('entity.bundle_config_import_validator');
    $definition->setClass('Drupal\config_import_de\Event\ConfigImportDeBundleConfigImportValidate');
  }

}
