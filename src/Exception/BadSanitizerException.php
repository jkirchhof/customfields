<?php

namespace CustomFields\Exception;

/**
 * Exception to throw when definitions are not found or cannot be parsed.
 */
class BadSanitizerException extends RuntimeException {

  /**
   * Exception message.
   *
   * @var string
   */
  protected $message;

  /**
   * Set message using intended type, field, and sanitizer.
   *
   * @param string $customFieldType
   *   Name of custom field type.
   * @param string $field
   *   Id of field.
   * @param string $sanitizer
   *   Name of sanitizer.
   */
  public function __construct(string $customFieldType, string $field, string $sanitizer) {
    $this->message = sprintf('For post type “%1,” the field “%2” has a
      misconfigured sanitizer.  Check the definition for “%3”. This may cause
      unexpected behavior, including allowing unsanitized data to be saved,
      which is a security risk.', $customFieldType, $field, $sanitizer);
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
