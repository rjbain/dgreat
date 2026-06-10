<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\sitewide_alert\Form\SitewideAlertRevisionDeleteForm;
use Drupal\sitewide_alert\Form\SitewideAlertRevisionRevertTranslationForm;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Sitewide Alert entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class SitewideAlertHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($history_route = $this->getHistoryRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.version_history", $history_route);
    }

    if ($revision_route = $this->getRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision", $revision_route);
    }

    if ($revert_route = $this->getRevisionRevertRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_revert", $revert_route);
    }

    if ($delete_route = $this->getRevisionDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_delete", $delete_route);
    }

    if ($translation_route = $this->getRevisionTranslationRevertRoute($entity_type)) {
      $collection->add("{$entity_type_id}.revision_revert_translation_confirm", $translation_route);
    }

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    return $collection;
  }

  /**
   * Gets the version history route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getHistoryRoute(EntityTypeInterface $entity_type): ?Route {
    if ($entity_type->hasLinkTemplate('version-history')) {
      $route = new Route($entity_type->getLinkTemplate('version-history'));
      $route
        ->setDefaults([
          '_title' => "{$entity_type->getLabel()} revisions",
          '_controller' => '\Drupal\sitewide_alert\Controller\SitewideAlertController::revisionOverview',
        ])
        ->setRequirement('_permission', 'view all sitewide alert revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRoute(EntityTypeInterface $entity_type): ?Route {
    if ($entity_type->hasLinkTemplate('revision')) {
      $route = new Route($entity_type->getLinkTemplate('revision'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\sitewide_alert\Controller\SitewideAlertController::revisionShow',
          '_title_callback' => '\Drupal\sitewide_alert\Controller\SitewideAlertController::revisionPageTitle',
        ])
        ->setRequirement('_permission', 'view all sitewide alert revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the revision revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRevertRoute(EntityTypeInterface $entity_type): ?Route {
    if ($entity_type->hasLinkTemplate('revision_revert')) {
      $route = new Route($entity_type->getLinkTemplate('revision_revert'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\sitewide_alert\Form\SitewideAlertRevisionRevertForm',
          '_title' => 'Revert to earlier revision',
        ])
        ->setRequirement('_permission', 'revert all sitewide alert revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the revision delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionDeleteRoute(EntityTypeInterface $entity_type): ?Route {
    if ($entity_type->hasLinkTemplate('revision_delete')) {
      $route = new Route($entity_type->getLinkTemplate('revision_delete'));
      $route
        ->setDefaults([
          '_form' => SitewideAlertRevisionDeleteForm::class,
          '_title' => 'Delete earlier revision',
        ])
        ->setRequirement('_permission', 'delete all sitewide alert revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the revision translation revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionTranslationRevertRoute(EntityTypeInterface $entity_type): ?Route {
    if ($entity_type->hasLinkTemplate('translation_revert')) {
      $route = new Route($entity_type->getLinkTemplate('translation_revert'));
      $route
        ->setDefaults([
          '_form' => SitewideAlertRevisionRevertTranslationForm::class,
          '_title' => 'Revert to earlier revision of a translation',
        ])
        ->setRequirement('_permission', 'revert all sitewide alert revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type): ?Route {
    if (!$entity_type->getBundleEntityType()) {
      $route = new Route("/admin/content/{$entity_type->id()}/settings");
      $route
        ->setDefaults([
          '_form' => 'Drupal\sitewide_alert\Form\SitewideAlertSettingsForm',
          '_title' => "{$entity_type->getLabel()} settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }

}
