<?php

namespace Drupal\Tests\dgreat_solr_ai\Unit;

use Drupal\dgreat_solr_ai\Usage\SearchUsage;
use PHPUnit\Framework\TestCase;

final class SearchUsageTest extends TestCase {

  public function testSmallEmbeddingCostsRemainVisible(): void {
    self::assertEqualsWithDelta(0.00000018, SearchUsage::cost(9), 0.0000000001);
    self::assertStringContainsString('US$0.00000018', SearchUsage::markup(9));
    self::assertStringContainsString('Generated answer tokens: 0', SearchUsage::markup(9));
  }

  public function testBlankSearchCostsNothing(): void {
    self::assertSame(0.0, SearchUsage::cost(0));
    self::assertStringContainsString('No search text', SearchUsage::markup(0));
  }

}
