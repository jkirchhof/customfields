<?php

namespace CustomFields\Tests\Exception;

use CustomFields\Exception\RuntimeException;

/**
 * Exception thrown within tests, such as in lieu of WP call for user feedback.
 */
class TestException extends RuntimeException {
}
