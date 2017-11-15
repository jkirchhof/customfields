<?php

namespace CustomFields\Tests;

use CustomFields\CustomFieldsUtilities;

/**
 * Tests for CustomFieldInit.
 */
class CustomFieldsUtilitiesTest extends \WP_UnitTestCase {

  /**
   * Test for hash of directory and contents.
   */
  public function testHashDirectory() {
    $result = CustomFieldsUtilities::hashDirectory(__DIR__ . '/definitions/broken');
    $this->assertEquals($result, 'e1ac6baef1a9c87ce0f307c9549d936c4b987f9e');
  }

  /**
   * Exception when no passed invalid directory.
   *
   * @expectedException \CustomFields\Exception\HashException
   */
  public function testHashDirectoryInvalidDirectory() {
    $result = CustomFieldsUtilities::hashDirectory(__DIR__ . '/definitions/doesnotexist');
  }

}
