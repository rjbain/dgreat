<?php

namespace Drupal\dgreat_nodes\Plugin\search_api\processor;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\MediaInterface;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Solarium\QueryType\Extract\Query as ExtractQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds extracted document text for media document items.
 */
#[SearchApiProcessor(
  id: 'dgreat_document_extracted_content',
  label: new TranslatableMarkup('Document extracted content'),
  description: new TranslatableMarkup('Extracts PDF/DOC text from document media items through Solr/Tika.'),
  stages: [
    'add_properties' => 0,
  ],
)]
class DocumentExtractedContent extends ProcessorPluginBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface|null
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->fileSystem = $container->get('file_system');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource && $datasource->getEntityTypeId() === 'media') {
      $definition = [
        'label' => $this->t('Document extracted content'),
        'description' => $this->t('Text extracted from document files through Solr/Tika.'),
        'type' => 'text',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['media_extracted_content'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $datasource = $item->getDatasource();
    if (!$datasource || $datasource->getEntityTypeId() !== 'media') {
      return;
    }

    $media = $item->getOriginalObject()?->getValue();
    if (!$media instanceof MediaInterface || $media->bundle() !== 'document') {
      return;
    }

    $text = $this->extractDocumentText($media);
    if ($text === '') {
      return;
    }

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), $item->getDatasourceId(), 'media_extracted_content');

    foreach ($fields as $field) {
      $field->addValue($text);
    }
  }

  /**
   * Extracts searchable text from a document media file via Solr/Tika.
   */
  protected function extractDocumentText(MediaInterface $media): string {
    if (!$media->hasField('field_media_document') || $media->get('field_media_document')->isEmpty()) {
      return '';
    }

    $file = $media->get('field_media_document')->entity;
    if (!$file) {
      return '';
    }

    $filepath = $this->getFileSystem()->realpath($file->getFileUri());
    if (!$filepath || !is_readable($filepath)) {
      return '';
    }

    $backend = $this->index?->getServerInstance()?->getBackend();
    if (!$backend instanceof SearchApiSolrBackend) {
      return '';
    }

    try {
      $text = $backend->extractContentFromFile($filepath, ExtractQuery::EXTRACT_FORMAT_TEXT);
    }
    catch (\Exception $exception) {
      \Drupal::logger('dgreat_nodes')->warning('Document extraction failed for media @id: @message', [
        '@id' => $media->id(),
        '@message' => $exception->getMessage(),
      ]);
      return '';
    }

    $text = trim((string) $text);
    return preg_replace('/\s+/', ' ', $text) ?? '';
  }

  /**
   * Gets the file system service.
   */
  protected function getFileSystem(): FileSystemInterface {
    return $this->fileSystem ?: \Drupal::service('file_system');
  }

}
