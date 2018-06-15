<?php

namespace CustomFields\Exception;

/**
 * Exception to throw when definitions are not found or cannot be parsed.
 */
class BadValidatorException extends RuntimeException {

  /**
   * Exception message.
   *
   * @var string
   */
  protected $message;

  /**
   * Set message using intended type, field, and validator.
   *
   * @param string $customFieldType
   *   Name of custom field type.
   * @param string $field
   *   Id of field.
   * @param string $validator
   *   Name of validator.
   */
  public function __construct(string $customFieldType, string $field, string $validator) {
    $this->message = sprintf('For post type “%1,” the field “%2” has a
      misconfigured validator.  Check the definition for “%3”. This may cause
      unexpected behavior, including allowing invalid data to be saved, which
      is a security risk.', $customFieldType, $field, $validator);
  }

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
