<?php

use Illuminate\Support\Collection;

if (!function_exists('collect')) {
  /**
   * Provide the legacy collection helper expected by custom project code.
   *
   * @param mixed $value
   *
   * @return \Illuminate\Support\Collection
   */
  function collect($value = NULL): Collection {
    return Collection::make($value);
  }
}
