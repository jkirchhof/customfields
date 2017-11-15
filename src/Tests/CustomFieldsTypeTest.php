<?php

namespace CustomFields\Tests;

use CustomFields\CustomFields;
use CustomFields\CustomFieldsType;

/**
 * Tests for CustomFieldsType.
 */
class CustomFieldsTypeTest extends \WP_UnitTestCase {

  /**
   * Test buildTypes().
   */
  public function testBuildTypes() {
    $cfs = CustomFields::initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cfs);
    foreach ($result as $r) {
      $this->assertInstanceOf(CustomFieldsType::class, $r);
    }
  }

  /**
   * Test buildTypes().
   */
  public function testBuildTypesCannotRedeclare() {
    $this->expectExceptionMessage('<strong>Cannot redefine type “post”</strong><br /> This definition could be parsed. This is like to cause unexpected behavior, including additional errors.');
    $cfs = CustomFields::initialize(__DIR__ . '/baddefinitions', 'testing');
    $result = CustomFieldsType::buildTypes($cfs);
  }

  /**
   * Test that post type exists.
   */
  public function testDeclarePostType() {
    $this->assertTrue(array_key_exists('sample', get_post_types()));
  }

  /**
   * Test getDefinition().
   */
  public function testGetDefinition() {
    $cfs = CustomFields::initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cfs)['testsample']->getDefinition();
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
   * Test getSingularName().
   */
  public function testGetSingularName() {
    $cfs = CustomFields::initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cfs)['testsample']->getSingularName();
    $expected = 'testsample';
    $this->assertEquals($result, $expected);
  }

  /**
   * Test getPluralName().
   */
  public function testGetPluralName() {
    $cfs = CustomFields::initialize(__DIR__ . '/definitions');
    $result = CustomFieldsType::buildTypes($cfs)['testsample']->getPluralName();
    $expected = 'testsamples';
    $this->assertEquals($result, $expected);
  }

}
