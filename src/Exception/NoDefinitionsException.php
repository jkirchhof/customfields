<?php

namespace CustomFields\Exception;

/**
 * Exception to throw when definitions are not found or cannot be parsed.
 */
class NoDefinitionsException extends RuntimeException {

  /**
   * Exception message.
   *
   * @var string
   */
  protected $message = 'No definitions could be read.  CustomFields will be surpressed.  This may cause unexpected behavior.';

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
