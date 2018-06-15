<?php

namespace CustomFields\Exception;

/**
 * Exception to throw when field validator method is misconfigured.
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
    $this->message = sprintf('For post type “%s,” the field “%s” has a
      misconfigured validator.  Check the definition for “%s”. This may cause
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
