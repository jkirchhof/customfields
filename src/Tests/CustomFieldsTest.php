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
    $this->assertFalse($cf->isInitialized());
    $cf->initialize(__DIR__ . '/definitions');
    $this->assertInstanceOf(CustomFields::class, $cf);
    $this->assertTrue($cf->isInitialized());
    // Re-initialization returns correct object.
    $cf->initialize(__DIR__ . '/definitions');
    $this->assertInstanceOf(CustomFields::class, $cf);
    $this->assertTrue($cf->isInitialized());
    // Re-initialization returns original object.
    $cf->initialize('foobarfoobar');
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
    $cf->initialize(__DIR__ . '/definitions-broken');
    $this->assertFalse($cf->isInitialized());
  }

  /**
   * No return when definition parsing fails. Exceptions are properly handled.
   *
   * Definition YAML can't be read.
   */
  public function testNoDefinitionsExceptionBrokenDefBadYaml() {
    $this->expectExceptionMessage("could not be parsed. It will be ignored, " .
      "which may cause other errors. The parser returned");
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions-badyaml');
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
        'wp_definition' => [
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
      ],
      'testsample1' => [
        'singular_name' => 'testsample1',
        'plural_name' => 'testsample1s',
        'wp_definition' => [
          'labels' => [
            'name' => 'testsample1',
            'singular_name' => 'Sample1',
            'add_new_item' => 'Add New Sample1',
            'edit_item' => 'Edit Sample1',
            'new_item' => 'New Sample1',
            'view_item' => 'View Sample1',
          ],
          'public' => TRUE,
          'exclude_from_search' => FALSE,
          'hierarchical' => TRUE,
        ],
      ],
    ];
    $result = $cf->getDefinitions();
    $this->assertEquals($expected, $result);
  }

  /**
   * Test getDefinitions() with uninitialized CustomFields object.
   *
   * @expectedException \CustomFields\Exception\NotInitializedException
   */
  public function testGetDefinitionsNotInitialized() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->getDefinitions();
  }

}
