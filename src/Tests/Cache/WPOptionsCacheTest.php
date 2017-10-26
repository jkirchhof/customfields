<?php

namespace CustomFields\Test\Cache;

use CustomFields\Cache\WPOptionsCache;

/**
 * Test CustomFields\Cache\WPOptionsCache.
 */
class WPOptionsCacheTest extends \WP_UnitTestCase {

  /**
   * Test cacheGet success.
   */
  public function testCacheGet() {
    $key = 'existantTestKeyc0f2cae28e02';
    $value = 'existantTestValuec0f2cae28e02';
    delete_option(WPOptionsCache::CACHE_PREFIX . $key);
    update_option(WPOptionsCache::CACHE_PREFIX . $key, $value);
    $result = WPOptionsCache::cacheGet($key);
    $this->assertEquals($result, $value);
    if (!delete_option(WPOptionsCache::CACHE_PREFIX . $key)) {
      throw new \RuntimeException("WPOptionsCacheTest::testCacheGet() left value in database");
    };
  }

  /**
   * Test cacheGet for non-existant key.
   *
   * @expectedException CustomFields\Exception\CacheNullException
   */
  public function testCacheGetForCacheNullException() {
    WPOptionsCache::cacheGet('NonexistantTestKeyc0f2cae28e0245505f88');
  }

  /**
   * Test cacheGet for empty key.
   *
   * @expectedException CustomFields\Exception\CacheNullException
   */
  public function testCacheGetForCacheNullException1() {
    WPOptionsCache::cacheGet('');
  }

  /**
   * Test cacheSet for initial and redundant caching.
   */
  public function testCacheSet() {
    $key = 'testkeye45674aa33d5b5843da5';
    $value = 'testvaluee45674aa33d5b5843da5';
    $valueA = 'TESTVALUE1e45674aa33d5b5843da5';
    delete_option(WPOptionsCache::CACHE_PREFIX . $key);
    $cacheSet0 = WPOptionsCache::cacheSet($key, $value);
    $this->assertTrue($cacheSet0);
    $result = get_option(WPOptionsCache::CACHE_PREFIX . $key);
    $this->assertEquals($result, $value);
    $cacheSet1 = WPOptionsCache::cacheSet($key, $value);
    $this->assertTrue($cacheSet1);
    $cacheSetA = WPOptionsCache::cacheSet($key, $valueA);
    $this->assertTrue($cacheSetA);
    $resultA = get_option(WPOptionsCache::CACHE_PREFIX . $key);
    $this->assertEquals($resultA, $valueA);
    if (!delete_option(WPOptionsCache::CACHE_PREFIX . $key)) {
      throw new \RuntimeException("WPOptionsCacheTest::testCacheSet() left value in database");
    };
  }

  /**
   * Test cacheSet for illegal value.
   *
   * @expectedException CustomFields\Exception\CacheSaveFailureException
   */
  public function testCacheSetForCacheSaveFailureException() {
    WPOptionsCache::cacheSet('', NULL);
  }

}
