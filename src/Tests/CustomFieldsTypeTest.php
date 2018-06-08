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
   * Test buildTypes() with complex definitions.
   */
  public function testBuildTypesComplex() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions-person');
    $result = CustomFieldsType::buildTypes($cf);
    foreach ($result as $r) {
      $this->assertInstanceOf(CustomFieldsType::class, $r);
    }
  }

  /**
   * Test buildTypes().
   */
  public function testBuildTypesBadDefinition() {
    $this->expectExceptionMessage('<strong>Error defining type ' .
      '“missingname”</strong><br />');
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions-missingname');
    $result = CustomFieldsType::buildTypes($cf);
  }

  /**
   * Test buildTypes() with uninitialized CustomFields object.
   *
   * The specific exception expected is thrown from CustomFields, but the test
   * for interface here ensures something is thown.  CustomFieldsType may do
   * additional handling but should still throw something.
   *
   * @expectedException \CustomFields\Exception\ExceptionInterface
   */
  public function testBuildTypesCustomFieldsNotInitialized() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $result = CustomFieldsType::buildTypes($cf);
  }

  /**
   * Test that post type exists.
   */
  public function testDeclarePostType() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    CustomFieldsType::buildTypes($cf);
    do_action('init');
    $this->assertTrue(array_key_exists('sample', get_post_types()));
  }

  /**
   * Test that shortcode callback is found.
   */
  public function testCreateShortcode() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    CustomFieldsType::buildTypes($cf);
    $result = do_shortcode("[samples]");
    $this->assertEquals("Shortcode processed successfully.", $result);
  }

  /**
   * Test notification for missing shortcode callback.
   *
   * @expectedException \CustomFields\Tests\Exception\TestException
   */
  public function testCreateShortcodeMissingCallback() {
    $this->expectExceptionMessage('<strong>Error defining shortcode for type ' .
      '“missingshortcode”</strong><br />Shortcodes will not be processed as ' .
      'expected and will likely be visible as raw text inside of posts.');
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions-missingshortcode');
    CustomFieldsType::buildTypes($cf);
  }

  /**
   * Test getSingularName().
   */
  public function testGetSingularName() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cf)['testsample']->getSingularName();
    $expected = 'testsample';
    $this->assertEquals($expected, $result);
  }

  /**
   * Test getPluralName().
   */
  public function testGetPluralName() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cf)['testsample']->getPluralName();
    $expected = 'testsamples';
    $this->assertEquals($expected, $result);
  }

  /**
   * Test getDefinition().
   */
  public function testGetDefinition() {
    $cf = new CustomFields(new WPOptionsCache(), new TestNotifier());
    $cf->initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cf)['testsample']->getDefinition()['wp_definition'];
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
    $this->assertEquals($expected, $result);
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
