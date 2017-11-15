<?php

namespace CustomFields\Tests;

use CustomFields\CustomFields;
use CustomFields\Cache\CacheInterface;
use CustomFields\Notifier\NotifierInterface;

/**
 * Tests for CustomFieldInit.
 */
class CustomFieldsTest extends \WP_UnitTestCase {

  /**
   * Test initialize().
   */
  public function testinitialize() {
    $cf = CustomFields::initialize(__DIR__ . '/definitions');
    $this->assertInstanceOf(CustomFields::class, $cf);
  }

  /**
   * No return when definition parsing fails. Exceptions are properly handled.
   */
  public function testNoDefinitionsException() {
    $this->assertNull(CustomFields::initialize(''));
    $this->assertNull(CustomFields::initialize(__DIR__ . '/definitions/broken'));
    $this->assertNull(CustomFields::initialize(__DIR__ . '/definitions/doesnotexist'));
  }

  /**
   * Test getCache.
   */
  public function testGetCache() {
    $cf = CustomFields::initialize(__DIR__ . '/definitions');
    $this->assertInstanceOf(CacheInterface::class, $cf->getCache());
  }

  /**
   * Test getNotiofier.
   */
  public function testGetNotifier() {
    $cf = CustomFields::initialize(__DIR__ . '/definitions');
    $this->assertInstanceOf(NotifierInterface::class, $cf->getNotifier());
  }

  /**
   * Test getDefinitions.
   */
  public function testGetDefinitions() {
    $cf = CustomFields::initialize(__DIR__ . '/definitions');
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
