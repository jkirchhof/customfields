<?php

namespace CustomFields\Tests;

use CustomFields\CustomFields;
use CustomFields\CustomFieldsType;
use CustomFields\Cache\WPOptionsCache;
use CustomFields\Tests\Notifier\TestNotifier;

/**
 * Tests for CustomFieldsType.
 */
class CustomFieldsTypeTest extends \WP_UnitTestCase {

  /**
   * Test buildTypes().
   */
  public function testBuildTypes() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cf);
    foreach ($result as $r) {
      $this->assertInstanceOf(CustomFieldsType::class, $r);
    }
  }

  /**
   * Test that post type exists.
   */
  public function testDeclarePostType() {
    $this->assertTrue(array_key_exists('sample', get_post_types()));
  }

  /**
   * Test getSingularName().
   */
  public function testGetSingularName() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cf)['testsample']->getSingularName();
    $expected = 'testsample';
    $this->assertEquals($result, $expected);
  }

  /**
   * Test getPluralName().
   */
  public function testGetPluralName() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cf)['testsample']->getPluralName();
    $expected = 'testsamples';
    $this->assertEquals($result, $expected);
  }

  /**
   * Test getDefinition().
   */
  public function testGetDefinition() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cf)['testsample']->getDefinition();
    $expected = [
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
    ];
    $this->assertEquals($result, $expected);
  }

  /**
   * Test getCfs().
   */
  public function testGetCfs() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cf)['testsample']->getCfs();
    $this->assertInstanceOf(CustomFields::class, $result);
  }

}
