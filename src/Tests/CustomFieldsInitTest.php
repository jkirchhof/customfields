<?php

namespace CustomFields\Tests;

use NonPublicAccess\NonPublicAccessTrait;
use CustomFields\CustomFieldsInit;

/**
 * Tests for CustomFieldInit.
 */
class CustomFieldsInitTest extends \WP_UnitTestCase {

  use NonPublicAccessTrait;

  /**
   * No return when definition parsing fails. Exception was properly handled.
   */
  public function testNoDefinitionsException() {
    $this->assertNull(CustomFieldsInit::loadDefinitions(''));
  }

  /**
   * Test loadDefinitions().
   */
  public function testLoadDefinitions() {
    $cf = CustomFieldsInit::loadDefinitions(__DIR__ . '/definitions');
    $this->assertInstanceOf(CustomFieldsInit::class, $cf);
  }

  /**
   * Find definition's primary YAML file.
   */
  public function testFindDefinitions() {
    $result = self::invokeNonPublicMethod('\CustomFields\CustomFieldsInit', 'findDefinitions', __DIR__ . '/definitions');
    $this->assertEquals($result, ['sample' => __DIR__ . '/definitions/sample']);
  }

  /**
   * Exception when no definitions are found.
   *
   * @expectedException \CustomFields\Exception\NoDefinitionsException
   */
  public function testFindDefinitionsNoneFound() {
    $result = self::invokeNonPublicMethod('\CustomFields\CustomFieldsInit', 'findDefinitions', __DIR__ . '/definitions/broken');
  }

  /**
   * Exception when no passed invalid directory.
   *
   * @expectedException \CustomFields\Exception\NoDefinitionsException
   */
  public function testFindDefinitionsInvalidDirectory() {
    $result = self::invokeNonPublicMethod('\CustomFields\CustomFieldsInit', 'findDefinitions', __DIR__ . '/definitions/doesnotexist');
  }

  /**
   * Test for hash of directory and contents.
   */
  public function testHashDirectory() {
    $cf = new CustomFieldsInit();
    $result = $cf->hashDirectory(__DIR__ . '/definitions/broken');
    $this->assertEquals($result, 'c0f2cae28e0245505f88e45674aa33d5b5843da5');
  }

  /**
   * Exception when no passed invalid directory.
   *
   * @expectedException \CustomFields\Exception\HashException
   */
  public function testHashDirectoryInvalidDirectory() {
    $cf = new CustomFieldsInit();
    $result = $cf->hashDirectory(__DIR__ . '/definitions/doesnotexist');
  }

  /**
   * Test \CustomFields\CustomFieldsInit::parseDefinition.
   */
  public function testParseDefinition() {
    $expected = [
      'name' => 'sample',
      'details' => [
        'zero',
        'one',
        'two',
      ],
    ];
    $result = self::invokeNonPublicMethod('\CustomFields\CustomFieldsInit', 'parseDefinition', __DIR__ . '/definitions/sample', 'sample');
    $this->assertEquals($result, $expected);
  }

}
