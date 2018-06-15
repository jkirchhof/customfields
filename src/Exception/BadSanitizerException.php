<?php

namespace CustomFields\Exception;

/**
 * Exception to throw when field sanitizer method is misconfigured.
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
    $this->message = sprintf('For post type “%s,” the field “%s” has a
      misconfigured sanitizer.  Check the definition for “%s”. This may cause
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
