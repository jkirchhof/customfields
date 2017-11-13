<?php

namespace CustomFields\Cache;

use CustomFields\Exception\CacheNullException;
use CustomFields\Exception\CacheSaveFailureException;

/**
 * CustomFields caching the "Wordpress way:" using the wp_options table.
 *
 * This is a lightweight cache implementation and fine for most uses.  Drawbacks
 * include several problematic choices in WP's implementation of get_option()
 * update_option().  Most signficantly, update_option() returns FALSE not only
 * for errors but also when the update does not change a value.  The
 * implementation of cacheSet() below queries the database first, which avoids
 * an ambiguous FALSE value but results in three queries to set one cache value.
 *
 * Also, this implementation uses the default value for update_option()'s third
 * parameter, "$autoload."  This is usually a bit more performant but is done
 * primarily to keep this class simple and easily replaced by another caching
 * system.
 */
class WPOptionsCache implements CacheInterface {

  const CACHE_PREFIX = 'CustomFieldsCacheItem';

  /**
   * {@inheritdoc}
   */
  public function get(string $key) {
    $value = get_option(self::CACHE_PREFIX . $key, NULL);
    if ($value === NULL || $key == '') {
      throw new CacheNullException();
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, $value) {
    // option_update() compares the current value with the value it's asked to
    // set.  If they're the same, it returns FALSE.  But it also returns FALSE
    // for errors.  So this repeats the duplicate value check.
    if ($key == '') {
      throw new CacheSaveFailureException();
    }
    try {
      $current_value = $this->get($key);
    }
    catch (CacheNullException $e) {
      // Continue. $current_value is unset.
    }
    if (!empty($current_value) && $value == $current_value) {
      // Value was already cached and remains set.
      return TRUE;
    }
    // Attempt to cache value.
    if (update_option(self::CACHE_PREFIX . $key, $value)) {
      return TRUE;
    }
    else {
      throw new CacheSaveFailureException();
    }
  }

}
