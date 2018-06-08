<?php

namespace CustomFields\Tests;

use CustomFields\CustomFieldsUtilities;

/**
 * Tests for CustomFieldInit.
 */
class CustomFieldsUtilitiesTest extends \WP_UnitTestCase {

  /**
   * Test for hash of directory containing only files.
   */
  public function testHashDirectory() {
    $result = CustomFieldsUtilities::hashDirectory(__DIR__ . '/definitions/broken');
    $this->assertEquals('e1ac6baef1a9c87ce0f307c9549d936c4b987f9e', $result);
  }

  /**
   * Test for hash of directory containing subdirectory.
   */
  public function testHashDirectoryWithSubdirectory() {
    $result = CustomFieldsUtilities::hashDirectory(__DIR__ . '/definitions-badyaml');
    $this->assertEquals('67ee601149dadb1231527baa651af9368ad259e6', $result);
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
