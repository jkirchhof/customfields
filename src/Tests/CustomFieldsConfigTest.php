<?php

namespace CustomFields\Tests;

use NonPublicAccess\NonPublicAccessTrait;
use CustomFields\CustomFieldsConfig;

/**
 * Tests for CustomFieldConfig.
 */
class CustomFieldsConfigTest extends \WP_UnitTestCase {

  use NonPublicAccessTrait;

  /**
   * No return when definition parsing fails. Exception was properly handled.
   */
  public function testNoDefinitionsException() {
    $this->assertNull(CustomFieldsConfig::loadDefinitions(''));
  }

  /**
   * Find definition's primary YAML file.
   */
  public function testFindDefinitions() {
    $result = CustomFieldsConfig::findDefinitions(__DIR__ . '/definitions');
    $this->assertEquals($result, ['sample' => __DIR__ . '/definitions/sample']);
  }

  /**
   * Exception when no definitions are found.
   *
   * @expectedException \CustomFields\Exception\NoDefinitionsException
   */
  public function testFindDefinitionsNoneFound() {
    $result = CustomFieldsConfig::findDefinitions(__DIR__ . '/definitions/broken');
  }

  /**
   * Exception when no passed invalid directory.
   *
   * @expectedException \CustomFields\Exception\NoDefinitionsException
   */
  public function testFindDefinitionsInvalidDirectory() {
    $result = CustomFieldsConfig::findDefinitions(__DIR__ . '/definitions/doesnotexist');
  }

  /**
   * Test \CustomFields\CustomFieldsConfig::parseDefinition.
   */
  public function testParseDefinition() {
    $result = self::invokeNonPublicMethod('\CustomFields\CustomFieldsConfig', 'parseDefinition');
    $this->assertEquals($result, NULL);
  }

}
