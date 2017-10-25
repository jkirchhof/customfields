<?php

namespace CustomFields\Tests\Exception;

use CustomFields\Exception\NoDefinitionsException;

/**
 * Tests for CustomFields\Exception\NoDefinitionsException.
 */
class NoDefinitionsExceptionTest extends \WP_UnitTestCase {

  /**
   * Test NoDefinitionsException.
   */
  public function testNoDefinitionsException() {
    $exception = new NoDefinitionsException();
    $message = 'No definitions could be read.  CustomFields will be surpressed.  This may cause unexpected behavior.';
    $this->assertEquals($message, '' . $exception);
  }

}
