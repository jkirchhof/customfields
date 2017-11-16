<?php

namespace CustomFields\Tests;

use CustomFields\CustomFields;
use CustomFields\Cache\CacheInterface;
use CustomFields\Cache\WPOptionsCache;
use CustomFields\Notifier\NotifierInterface;
use CustomFields\Notifier\WPNotifier;
use CustomFields\Tests\Notifier\TestNotifier;

/**
 * Tests for CustomFieldInit.
 */
class CustomFieldsTest extends \WP_UnitTestCase {

  /**
   * Test initialize().
   *
   * Also tests isInitialized().
   */
  public function testInitialize() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $this->assertInstanceOf(CustomFields::class, $cf);
    $this->assertTrue($cf->isInitialized());
  }

  /**
   * No return when definition parsing fails. Exceptions are properly handled.
   *
   * Empty path to definiton.
   */
  public function testNoDefinitionsExceptionEmptyDef() {
    $cf = new CustomFields(new WPOptionsCache(), new WPNotifier());
    $cf->initialize('');
    $this->assertFalse($cf->isInitialized());
  }

  /**
   * No return when definition parsing fails. Exceptions are properly handled.
   *
   * Definition signature is bad.
   */
  public function testNoDefinitionsExceptionBrokenDef() {
    $cf = new CustomFields(new WPOptionsCache(), new WPNotifier());
    $cf->initialize(__DIR__ . '/definitions/broken');
    $this->assertFalse($cf->isInitialized());
  }

  /**
   * No return when definition parsing fails. Exceptions are properly handled.
   *
   * Path does not resolve.
   */
  public function testNoDefinitionsExceptionBadPath() {
    $cf = new CustomFields(new WPOptionsCache(), new WPNotifier());
    $cf->initialize(__DIR__ . '/definitions/doesnotexist');
    $this->assertFalse($cf->isInitialized());
  }

  /**
   * Test getCache.
   */
  public function testGetCache() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $this->assertInstanceOf(CacheInterface::class, $cf->getCache());
  }

  /**
   * Test getNotiofier.
   */
  public function testGetNotifier() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $this->assertInstanceOf(NotifierInterface::class, $cf->getNotifier());
  }

  /**
   * Test getDefinitions.
   */
  public function testGetDefinitions() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $expected = [
      'testsample' => [
        'singular_name' => 'testsample',
        'plural_name' => 'testsamples',
        'labels' => [
          'name' => 'Sample',
          'singular_name' => 'Sample',
          'add_new_item' => 'Add New Sample',
          'edit_item' => 'Edit Sample',
          'new_item' => 'New Sample',
          'view_item' => 'View Sample',
        ],
        'public' => TRUE,
        'exclude_from_search' => FALSE,
        'hierarchical' => TRUE,
      ],
    ];
    $result = $cf->getDefinitions();
    $this->assertEquals($result, $expected);
  }

}
