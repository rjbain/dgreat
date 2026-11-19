<?php

namespace Drupal\Tests\myusf_site_chat\Unit;

use Drupal\myusf_site_chat\Plugin\ChatProcessor\SiteChat;
use PHPUnit\Framework\TestCase;

final class SiteChatTest extends TestCase {

  public function testUnsupportedOrInventedCitationsAreRejected(): void {
    $answer = ['supported' => TRUE, 'answer' => 'A supported answer.', 'source_ids' => [1]];
    self::assertTrue(SiteChat::validAnswer($answer, [1, 2]));
    self::assertFalse(SiteChat::validAnswer($answer, [2]));
    $answer['source_ids'] = [];
    self::assertFalse(SiteChat::validAnswer($answer, [1, 2]));
    $answer['source_ids'] = [1];
    $answer['supported'] = FALSE;
    self::assertFalse(SiteChat::validAnswer($answer, [1, 2]));
    self::assertFalse(SiteChat::validAnswer(NULL, [1, 2]));
  }

}
