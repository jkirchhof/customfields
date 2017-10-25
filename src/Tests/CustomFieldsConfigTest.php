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
    $this->assertNull(CustomFieldsConfig::parseDefinitions(''));
  }

  /**
   * Test \CustomFields\CustomFieldsConfig::parseDefinition.
   */
  public function testParseDefinition() {
    $result = self::invokeNonPublicMethod('\CustomFields\CustomFieldsConfig', 'parseDefinition');
    $this->assertEquals($result, NULL);
  }

}
