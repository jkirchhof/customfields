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
    $this->assertEquals($result, '4de9995d63e4450a251a433aa6b6d4011db28776');
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
