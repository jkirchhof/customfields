<?php

namespace CustomFields\Exception;

/**
 * Exception to throw when field render method is not found or cannot be parsed.
 */
class MissingRenderMethodException extends RuntimeException {

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
   */
  public function __construct(string $customFieldType, string $field) {
    $this->message = sprintf('For post type “%1,” the field “%2” does not
      have a properly configured rendering method.  It will not be displayed on
      post forms.  This could cause additional problems, such as with
      validation.', $customFieldType, $field);
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
