<?php

namespace CustomFields\Tests\Exception;

use CustomFields\Exception\BadDefinitionException;

/**
 * Tests for CustomFields\Exception\NoDefinitionsException.
 */
class BadDefinitionExceptionTest extends \WP_UnitTestCase {

  /**
   * Test NoDefinitionsException.
   */
  public function testNoDefinitionsException() {
    $exception = new BadDefinitionException();
    $message = 'This definition could be parsed. This is likely to cause unexpected behavior, including additional errors.';
    $this->assertEquals($message, '' . $exception);
  }

}
