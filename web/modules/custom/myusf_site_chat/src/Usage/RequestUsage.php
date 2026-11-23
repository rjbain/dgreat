<?php

namespace Drupal\myusf_site_chat\Usage;

/**
 * Temporary per-request estimates; rates are USD per million tokens.
 */
final class RequestUsage {

  // Standard API prices checked 2026-11-23 (not Batch API).
  // https://developers.openai.com/api/docs/models/gpt-4o-mini
  // https://developers.openai.com/api/docs/models/text-embedding-3-small
  public const INPUT_RATE = 0.15;
  public const CACHED_RATE = 0.075;
  public const OUTPUT_RATE = 0.60;
  public const EMBEDDING_RATE = 0.02;

  public int $input = 0;
  public int $output = 0;
  public int $cached = 0;
  public int $embedding = 0;
  public bool $chatEstimated = FALSE;

  public function cost(): float {
    $cached = min($this->input, max(0, $this->cached));
    return (($this->input - $cached) * self::INPUT_RATE + $cached * self::CACHED_RATE
      + $this->output * self::OUTPUT_RATE + $this->embedding * self::EMBEDDING_RATE) / 1000000;
  }

  public function markup(): string {
    $total = $this->input + $this->output + $this->embedding;
    $counts = number_format($this->input) . ' input + ' . number_format($this->output)
      . ' output (' . number_format($this->cached) . ' cached input included)';
    $cost = number_format($this->cost(), 8, '.', '');
    $per_thousand = number_format($this->cost() * 1000, 4, '.', '');
    $basis = $this->chatEstimated ? 'Chat tokens estimated' : 'Chat tokens reported by OpenAI (zero if no chat call)';
    return '<div class="myusf-chat-usage"><hr><strong>Temporary request usage</strong>'
      . '<p>Tokens: ' . number_format($total) . ' total — ' . $counts . '; '
      . number_format($this->embedding) . ' search embedding (estimated).</p>'
      . '<p>Estimated cost: <strong>US$' . $cost . '</strong> for this request; about US$'
      . $per_thousand . ' per 1,000 similar requests.</p>'
      . '<details><summary>Estimate details</summary><p>' . $basis
      . '. Input includes instructions, retrieved page excerpts, question history and response schema. Output includes the full model response, even for a refusal.</p>'
      . '<p>Models: gpt-4o-mini and text-embedding-3-small. USD per million tokens: chat input $0.15, cached input $0.075, output $0.60, embeddings $0.02. Rates checked November 23, 2026. Excludes content indexing, hosting, taxes and unobserved retries. This is an estimate, not billing data. <a href="https://developers.openai.com/api/docs/pricing">OpenAI pricing</a>.</p></details></div>';
  }

}
