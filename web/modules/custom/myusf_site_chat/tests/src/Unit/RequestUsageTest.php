<?php

namespace Drupal\Tests\myusf_site_chat\Unit;

use Drupal\myusf_site_chat\Usage\RequestUsage;
use PHPUnit\Framework\TestCase;

final class RequestUsageTest extends TestCase {

  public function testCostIncludesEmbeddingsAndDiscountsCachedInput(): void {
    $usage = new RequestUsage();
    $usage->input = 10000;
    $usage->cached = 4000;
    $usage->output = 500;
    $usage->embedding = 20;
    self::assertEqualsWithDelta(0.0015004, $usage->cost(), 0.0000000001);
    self::assertStringContainsString('US$0.001500', $usage->markup());
    self::assertStringContainsString('10,520 total', $usage->markup());
  }

  public function testEmbeddingOnlyRefusalAndNoApiRequest(): void {
    $usage = new RequestUsage();
    self::assertSame(0.0, $usage->cost());
    $usage->embedding = 50;
    self::assertEqualsWithDelta(0.000001, $usage->cost(), 0.0000000001);
    self::assertStringContainsString('50 search embedding (estimated)', $usage->markup());
  }

}
