<?php

namespace CustomFields\Tests\Storage;

use CustomFields\Storage\WPMetaData;

/**
 * Store/get custom fields data using Wordpress' "metadata" functions.
 */
class WPMetaDataTest extends \WP_UnitTestCase {

  /**
   * ID of fixture post used in tests.
   *
   * @var int
   */
  protected static $testPostId;

  /**
   * Test key for scalar storage.
   *
   * @var string
   */
  protected $testStringKey = "stringkey";

  /**
   * Test key for scalar storage.
   *
   * @var string
   */
  protected $testArrayKey = "arraykey";

  /**
   * Test value for scalar storage.
   *
   * @var string
   */
  protected $testString = "string test value";

  /**
   * Test value for scalar storage.
   *
   * @var array
   */
  protected $testArray = [
    "value0",
    1,
    [
      'subvalue 0',
      1.1,
    ],
  ];

  /**
   * Be sure the post for which to store data as post meta exists.
   */
  public static function setUpBeforeClass() {
    $knownPostId = wp_insert_post([
      'post_title' => "WPMetaDataTest test post",
      'post_content' => "content of WPMetaDataTest test post",
      'post_status' => 'publish',
      'post_author' => 1,
      'post_type' => 'page',
    ]);
    if ($knownPostId) {
      static::$testPostId = $knownPostId;
    }
    else {
      static::markTestSkipped(
        'WPMetaDataTest test post creation failed.'
      );
    }
  }

  /**
   * Don't use default method.
   */
  public function setUp() {
    return NULL;
  }

  /**
   * Don't use default method.
   */
  public function tearDown() {
    return NULL;
  }

  /**
   * Remove fixture post and its meta data.
   */
  public static function tearDownAfterClass() {
    $storedMeta = get_post_meta(static::$testPostId);
    foreach ($storedMeta as $metaKey => $metaValue) {
      delete_post_meta(static::$testPostId, $metaKey);
    }
    wp_delete_post(static::$testPostId, TRUE);
  }

  /**
   * Test scalar and array persistance.
   */
  public function testPersist() {
    $storage = new WPMetaData();
    $persistedString = $storage->persist(static::$testPostId, $this->testStringKey, $this->testString);
    $persistedArray = $storage->persist(static::$testPostId, $this->testArrayKey, $this->testArray);
    $this->assertTrue($persistedString);
    $this->assertTrue($persistedArray);
  }

  /**
   * Test retrieve of scalar and array values.
   */
  public function testRetrieve() {
    $storage = new WPMetaData();
    $persistedString = $storage->retrieve(static::$testPostId, $this->testStringKey);
    $persistedArray = $storage->retrieve(static::$testPostId, $this->testArrayKey);
    $this->assertEquals($this->testString, $persistedString);
    $this->assertEquals($this->testArray, $persistedArray);
  }

  /**
   * Test retrieve of all values.
   */
  public function testRetrieveAll() {
    $storage = new WPMetaData();
    $persistedValues = $storage->retrieveAll(static::$testPostId);
    $this->assertArraySubset([$this->testStringKey => $this->testString], $persistedValues);
    $this->assertArraySubset([$this->testArrayKey => $this->testArray], $persistedValues);
  }

  /**
   * Test delete of value.
   */
  public function testDelete() {
    $storage = new WPMetaData();
    $deleted = $storage->delete(static::$testPostId, $this->testStringKey);
    $this->assertTrue($deleted);
    $persistedValues = $storage->retrieveAll(static::$testPostId);
    $this->assertArrayNotHasKey($this->testStringKey, $persistedValues);
  }

}
