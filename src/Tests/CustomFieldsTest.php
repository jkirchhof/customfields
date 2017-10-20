<?php

namespace CustomFields\Tests;

use PHPUnit\Framework\TestCase;
use NonPublicAccess\NonPublicAccessTrait;

/**
 * Tests for CustomField.
 */
class CustomFieldsTest extends TestCase {

  use NonPublicAccessTrait;

  /**
   * Test \CustomFields\CustomFields::parseDefinition.
   */
  public function testParseDefinition() {
    $result = self::invokeNonPublicMethod('\CustomFields\CustomFields', 'parseDefinition');
    $this->assertEquals($result, NULL);
  }

}
