<?php

namespace Drupal\Tests\dgreat_solr_ai\Unit;

use Drupal\ai\OperationType\Embeddings\EmbeddingsInput;
use Drupal\ai\OperationType\Embeddings\EmbeddingsOutput;
use Drupal\ai\Plugin\ProviderProxy;
use Drupal\dgreat_solr_ai\Plugin\search_api\processor\ReliableDenseVector;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\SearchApiException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests that embedding failures cannot reach Solr as plain text.
 *
 */
#[Group('dgreat_solr_ai')]
class ReliableDenseVectorTest extends TestCase {

  public function testFailedRequestLeavesInputQueued(): void {
    [$processor, $proxy, $field, $item] = $this->buildProcessor();
    $proxy->method('__call')->willThrowException(new \RuntimeException('Synthetic rate limit'));
    $field->expects($this->never())->method('setValues');
    $this->expectException(SearchApiException::class);
    $this->expectExceptionMessage('items remain queued for retry');
    $processor->preprocessIndexItems([$item]);
  }

  public function testSuccessfulRequestStoresOnlyNumericVectors(): void {
    [$processor, $proxy, $field, $item] = $this->buildProcessor();
    $vector = array_fill(0, 1536, 0.1);
    $proxy->expects($this->once())->method('__call')->with(
      'embeddings',
      $this->callback(static fn(array $args) => $args[0] instanceof EmbeddingsInput
        && $args[0]->getPrompt() === 'Published page text'
        && $args[1] === 'text-embedding-3-small'
        && $args[2] === ['skip_moderation']),
    )->willReturn(new EmbeddingsOutput($vector, [], []));
    $field->expects($this->once())->method('setValues')->with([$vector]);
    $processor->preprocessIndexItems([$item]);
  }

  public function testInvalidVectorStopsBeforeSubmission(): void {
    [$processor, $proxy, $field, $item] = $this->buildProcessor();
    $proxy->method('__call')->willReturn(new EmbeddingsOutput([0.1], [], []));
    $field->expects($this->never())->method('setValues');
    $this->expectException(SearchApiException::class);
    $this->expectExceptionMessage('dimensions do not match');
    $processor->preprocessIndexItems([$item]);
  }

  private function buildProcessor(): array {
    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn('solr_ai');
    $index->method('getThirdPartySetting')->willReturn(TRUE);
    $proxy = $this->createMock(ProviderProxy::class);
    $configuration = [
      'ai_provider' => 'openai', 'ai_model_id' => 'text-embedding-3-small', 'vector_dimension' => 1536,
    ];
    $processor = $this->getMockBuilder(ReliableDenseVector::class)
      ->setConstructorArgs([$configuration, 'solr_densevector', ['provider' => 'search_api_solr_dense_vector']])
      ->onlyMethods(['getEmbeddingProvider'])->getMock();
    $processor->method('getEmbeddingProvider')->willReturn($proxy);
    $processor->setIndex($index);
    $field = $this->createMock(FieldInterface::class);
    $field->method('getType')->willReturn('solr_densevector');
    $field->method('getValues')->willReturn(['Published page text']);
    $item = $this->createMock(ItemInterface::class);
    $item->method('getFields')->willReturn(['body_vector' => $field]);
    return [$processor, $proxy, $field, $item];
  }

}
