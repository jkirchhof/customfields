<?php

namespace CustomFields\Exception;

/**
 * Exception to throw when definitions are not found or cannot be parsed.
 */
class BadDefinitionException extends RuntimeException {

  /**
   * Exception message.
   *
   * @var string
   */
  protected $message = 'This definition could be parsed. This is likely to cause unexpected behavior, including additional errors.';

  /**
   * String output of exception.
   *
   * @return string
   *   Output $this->message without additional detail.
   */
  public function __toString() {
    return $this->message;
  }

}
