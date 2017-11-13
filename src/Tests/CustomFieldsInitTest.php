<?php

namespace CustomFields\Tests;

use NonPublicAccess\NonPublicAccessTrait;
use CustomFields\CustomFieldsInit;
use CustomFields\Cache\CacheInterface;
use CustomFields\Notifier\NotifierInterface;

/**
 * Tests for CustomFieldInit.
 */
class CustomFieldsInitTest extends \WP_UnitTestCase {

  use NonPublicAccessTrait;

  /**
   * Test getCache.
   */
  public function testGetCache() {
    $cf = CustomFieldsInit::loadDefinitions(__DIR__ . '/definitions');
    $this->assertInstanceOf(CacheInterface::class, $cf->getCache());
  }

  /**
   * Test getNotiofier.
   */
  public function testGetNotifier() {
    $cf = CustomFieldsInit::loadDefinitions(__DIR__ . '/definitions');
    $this->assertInstanceOf(NotifierInterface::class, $cf->getNotifier());
  }

  /**
   * Test getDefinitions.
   */
  public function testGetDefinitions() {
    $cf = CustomFieldsInit::loadDefinitions(__DIR__ . '/definitions');
    $expected = [
      'sample' => [
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

  /**
   * No return when definition parsing fails. Exceptions are properly handled.
   */
  public function testNoDefinitionsException() {
    $this->assertNull(CustomFieldsInit::loadDefinitions(''));
    $this->assertNull(CustomFieldsInit::loadDefinitions(__DIR__ . '/definitions/broken'));
    $this->assertNull(CustomFieldsInit::loadDefinitions(__DIR__ . '/definitions/doesnotexist'));
  }

  /**
   * Test loadDefinitions().
   */
  public function testLoadDefinitions() {
    $cf = CustomFieldsInit::loadDefinitions(__DIR__ . '/definitions');
    $this->assertInstanceOf(CustomFieldsInit::class, $cf);
  }

}
