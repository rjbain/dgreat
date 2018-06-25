<?php

namespace Drupal\usf_services_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\block\Entity\Block;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the Example module.
 */
class USFServicesPageController extends ControllerBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a USFServicesPageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns the services page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function showServices() {
    // Renders the services block.
    $block = Block::load('views_block__services_block_1');
    $render['services'] = $this->entityTypeManager
      ->getViewBuilder('block')
      ->view($block);

    // Loads the audience facet.
    $block = Block::load('audience');
    $render['audience'] = $this->entityTypeManager
      ->getViewBuilder('block')
      ->view($block);

    return [
      '#theme' => 'usf_services_page',
      '#page' => $render,
    ];
  }

  /**
   * Returns the How Do I page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function pageHowDoI() {
    // Loads the how do i page.
    $block = Block::load('views_block__services_block_2');
    $render['howdoi'] = $this->entityTypeManager
      ->getViewBuilder('block')
      ->view($block);

    // Top Student Questions custom block.
    $block = Block::load('topstudentquestions');
    $render['topstudent'] = $this->entityTypeManager
      ->getViewBuilder('block')
      ->view($block);

    // Top Faculty Questions custom block.
    $block = Block::load('topfacultystaffquestions');
    $render['topfaculty'] = $this->entityTypeManager
      ->getViewBuilder('block')
      ->view($block);

    return [
      '#theme' => 'how_do_i_page',
      '#page' => $render,
    ];
  }

}
