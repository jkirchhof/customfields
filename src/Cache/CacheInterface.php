<?php

namespace CustomFields\Cache;

/**
 * Interface for caching used by CustomFields.
 */
interface CacheInterface {

  /**
   * Get value from cache.
   *
   * @param string $key
   *   Key of cache item.
   *
   * @return mixed
   *   Value from cache.
   *
   * @throws \CustomFields\Exception\CacheNullException
   *   Thrown when cache does not contain value.
   */
  public function get(string $key);

  /**
   * Set value in cache.
   *
   * @param string $key
   *   Key of cache item.
   * @param mixed $value
   *   Value to cache.
   *
   * @return bool
   *   TRUE if successfully cached.  FALSE on failure.
   */
  public function set(string $key, $value);

}
