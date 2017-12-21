<?php

namespace CustomFields\Test\Cache;

use CustomFields\Cache\WPOptionsCache;

/**
 * Test CustomFields\Cache\WPOptionsCache.
 */
class WPOptionsCacheTest extends \WP_UnitTestCase {

  /**
   * Test get success.
   */
  public function testGet() {
    $wpoc = new WPOptionsCache();
    $key = 'existantTestKeyc0f2cae28e02';
    $value = 'existantTestValuec0f2cae28e02';
    delete_option(WPOptionsCache::CACHE_PREFIX . $key);
    update_option(WPOptionsCache::CACHE_PREFIX . $key, $value);
    $result = $wpoc->get($key);
    $this->assertEquals($value, $result);
    if (!delete_option(WPOptionsCache::CACHE_PREFIX . $key)) {
      throw new \RuntimeException("WPOptionsCacheTest::testGet() left value in database");
    };
  }

  /**
   * Test get for non-existant key.
   *
   * @expectedException CustomFields\Exception\CacheNullException
   */
  public function testGetForCacheNullException() {
    $wpoc = new WPOptionsCache();
    $wpoc->get('NonexistantTestKeyc0f2cae28e0245505f88');
  }

  /**
   * Test get for empty key.
   *
   * @expectedException CustomFields\Exception\CacheNullException
   */
  public function testGetForCacheNullException1() {
    $wpoc = new WPOptionsCache();
    $wpoc->get('');
  }

  /**
   * Test set for initial and redundant caching.
   */
  public function testSet() {
    $wpoc = new WPOptionsCache();
    $key = 'testkeye45674aa33d5b5843da5';
    $value = 'testvaluee45674aa33d5b5843da5';
    $valueA = 'TESTVALUE1e45674aa33d5b5843da5';
    delete_option(WPOptionsCache::CACHE_PREFIX . $key);
    $cacheSet0 = $wpoc->set($key, $value);
    $this->assertTrue($cacheSet0);
    $result = get_option(WPOptionsCache::CACHE_PREFIX . $key);
    $this->assertEquals($result, $value);
    $cacheSet1 = $wpoc->set($key, $value);
    $this->assertTrue($cacheSet1);
    $cacheSetA = $wpoc->set($key, $valueA);
    $this->assertTrue($cacheSetA);
    $resultA = get_option(WPOptionsCache::CACHE_PREFIX . $key);
    $this->assertEquals($valueA, $resultA);
    if (!delete_option(WPOptionsCache::CACHE_PREFIX . $key)) {
      throw new \RuntimeException("WPOptionsCacheTest::testSet() left value in database");
    };
  }

  /**
   * Test set for illegal value.
   *
   * @expectedException CustomFields\Exception\CacheSaveFailureException
   */
  public function testCacheSetForCacheSaveFailureException() {
    $wpoc = new WPOptionsCache();
    $wpoc->set('', NULL);
  }

}
