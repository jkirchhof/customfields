<?php

namespace CustomFields;

use CustomFields\Exception\BadValidatorException;
use CustomFields\Exception\BadSanitizerException;
use CustomFields\Exception\MissingRenderMethodException;

/**
 * Add and manage field for custom field type.
 */
class CustomFieldsField {

  /**
   * CustomFieldsType object of type with field to be declared.
   *
   * @var \CustomFields\CustomFieldsType
   */
  protected $cfType;

  /**
   * Machine name of field.
   *
   * @var string
   */
  protected $field;

  /**
   * The camelCase version of field name.
   *
   * @var string
   */
  protected $fieldCamelCase;

  /**
   * Field definition.
   *
   * @var array
   */
  protected $fieldInfo;

  /**
   * Post ID (0 for new post) of post for which field is built.
   *
   * @var int
   */
  protected $postId;

  /**
   * Human-readable field name.
   *
   * @var string
   */
  protected $name;

  /**
   * Default, pre-populated, or used submitted value of field.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Sanitized value, not necessarily valid.
   *
   * @var mixed
   */
  protected $sanitizedValue;

  /**
   * Status of whether value has been checked as valid.
   *
   * @var bool
   */
  protected $validationComplete = FALSE;

  /**
   * Status of whether value is valid.
   *
   * @var bool
   */
  protected $validationPassed = FALSE;

  /**
   * Default message if validation fails.
   *
   * @var string
   */
  protected $validationMessage;

  /**
   * If TRUE, never persist (such as for validation-only fields).
   *
   * A custom persist method may still use the value, even by persisting it.
   *
   * @var bool
   */
  protected $neverPersist = FALSE;

  /**
   * Whether invalid values are persisted (though flagged) or not persisted.
   *
   * @var bool
   */
  protected $persistInvalidValues;

  /**
   * Methods to call for initial field validation.
   *
   * @var array
   */
  protected $fieldValidationMethods;

  /**
   * Method to check validation in context of other validators.
   *
   * If declared, should be called after field by field validation.
   *
   * @var callable
   */
  protected $contextualValidator;

  /**
   * Status of whether value has been checked as valid.
   *
   * @var bool
   */
  protected $sanitizationComplete = FALSE;

  /**
   * Methods to call for field sanitization.
   *
   * @var array
   */
  protected $fieldSanitizerMethods;

  /**
   * Method to render field in form.
   *
   * @var callable
   */
  protected $renderMethod;

  /**
   * Construct object for field.
   *
   * Protected method, forcing use of buildField factory, which checks user
   * permission a field before building it.
   *
   * @param CustomFieldsType $cfType
   *   CustomFieldsType object of type with field to be declared.
   * @param string $field
   *   Machine name of field.
   * @param array $fieldInfo
   *   Column definition.
   */
  protected function __construct(CustomFieldsType $cfType, string $field, array $fieldInfo) {
    $this->cfType = $cfType;
    $this->field = $field;
    $this->fieldCamelCase = CustomFieldsUtilities::makeCamelCase($field);
    $this->fieldInfo = $fieldInfo;
    $this->postId = $cfType->getPostId();
    $this->name = $fieldInfo['name'];
    $this->validationMessage = !empty($fieldInfo['error message']) ?
      $fieldInfo['error message'] :
      'The value of “' . $this->name . '” appears invalid.';
    if (!empty($fieldInfo['type']) && $fieldInfo['type'] == 'validation only') {
      $this->neverPersist = TRUE;
    }
    $this->persistInvalidValues = empty($fieldInfo['persist invalid values']) ?
      FALSE :
      TRUE;
    $this->setValue();
    $this->setValidators();
    $this->setContextualValidator();
    $this->setSanitizers();
    $this->setRenderMethod();
    // @TODO: queue css/js includes for metaboxes; not yet in definition.
  }

  /**
   * Factory to construct field object after checking permission.
   *
   * @param CustomFieldsType $cfType
   *   CustomFieldsType object of type with field to be declared.
   * @param string $field
   *   Machine name of field.
   * @param array $fieldInfo
   *   Column definition.
   *
   * @return static|null
   *   Field object.
   */
  public static function buildField(CustomFieldsType $cfType, string $field, array $fieldInfo) {
    if (!empty($fieldInfo['requires']) && is_array($fieldInfo['requires'])) {
      foreach ($fieldInfo['requires'] as $permission) {
        if (!current_user_can($permission, $cfType->getPostId())) {
          return;
        }
      }
    }
    return new static($cfType, $field, $fieldInfo);
  }

  /**
   * Get machine name of field.
   *
   * @return string
   *   Machine name of field.
   */
  public function getField() {
    return $this->field;
  }

  /**
   * Get post object ID relevant to current request.
   *
   * @return int
   *   Wordpress post ID or 0.
   */
  public function getPostId() {
    return $this->postId ?: 0;
  }

  /**
   * Get human readable name of field.
   *
   * @return string
   *   Human readable name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get value, making sure validation methods have run.
   *
   * @var bool $sanitized
   *   Whether return value should be sanitized.
   *
   * @return mixed
   *   Field value, possibly invalid.
   */
  public function getValue($sanitized = TRUE) {
    if (!$this->validationComplete) {
      $this->validateValue();
    }
    if (!$this->sanitizationComplete) {
      $this->sanitizeValue();
    }
    if (!$sanitized) {
      return $this->value;
    }
    return $this->sanitizedValue;
  }

  /**
   * Get validation status (or unknown) of value.
   *
   * @return bool|null
   *   NULL if unknown.  TRUE or FALSE is known.
   */
  public function getValidationStatus() {
    if (!$this->validationComplete) {
      return NULL;
    }
    return $this->validationPassed;
  }

  /**
   * Get default message for validation failure.
   *
   * @return string
   *   Default message for validation failure.
   */
  public function getValidationMessage() {
    return $this->validationMessage;
  }

  /**
   * Call render method and get resulting HTML.
   *
   * @return string
   *   HTML returned from render method.
   */
  public function getRenderedField() {
    return ($this->renderMethod)();
  }

  /**
   * Set initial value of field from post ID or default.
   */
  protected function setValue() {
    if (!$this->postId) {
      if (!empty($this->fieldInfo['default'])) {
        $this->value = $this->fieldInfo['default'];
      }
    }
    else {
      if ($this->cfType->getUserRequestedSave()) {
        if (empty($_POST[$this->field])) {
          $this->value = '';
        }
        else {
          $this->value = $_POST[$this->field];
        }
      }
      else {
        $this->value = $this
          ->cfType
          ->getCfs()
          ->getStorage()
          ->retrieve($this->postId, $this->field);
      }
    }
  }

  /**
   * Assign validators to field, from definition and custom callbacks.
   */
  protected function setValidators() {
    // Look for custom validator.
    if (method_exists($this->cfType->getObject(), $this->fieldCamelCase . 'Validation')) {
      $this->fieldValidationMethods[] = function ($input) {
        return $this->cfType->getObject()->{$this->fieldCamelCase . 'Validation'}($this);
      };
    }
    if (!empty($this->fieldInfo['validate']) && is_array($this->fieldInfo['validate'])) {
      array_map(function ($validator) {
        if (is_array($validator)) {
          $validationType = array_shift($validator);
          $validationArgs = $validator;
        }
        else {
          $validationType = $validator;
        }
        switch ($validationType) {
          case 'not empty':
            $this->fieldValidationMethods[] = function ($input) {
              return $this->validateNotEmpty($input);
            };
            break;

          case 'url':
            $this->fieldValidationMethods[] = function ($input) {
              return $this->validateIsUrl($input);
            };
            break;

          case 'email':
            $this->fieldValidationMethods[] = function ($input) {
              return $this->validateIsEmail($input);
            };
            break;

          case 'min-length':
            $this->fieldValidationMethods[] = function ($input) use ($validationArgs) {
              return $this->validateMinLength($input, ...$validationArgs);
            };
            break;

          case 'max-length':
            $this->fieldValidationMethods[] = function ($input) use ($validationArgs) {
              return $this->validateMaxLength($input, ...$validationArgs);
            };
            break;

          case 'pattern':
            $this->fieldValidationMethods[] = function ($input) use ($validationArgs) {
              return $this->validatePattern($input, ...$validationArgs);
            };
            break;

          default:
            throw new BadValidatorException($this->cfType->getSingularName(), $this->field, $validationType);
        }
      }, $this->fieldInfo['validate']);
    }
  }

  /**
   * Look for contextualValidator, and set it if declared.
   */
  protected function setContextualValidator() {
    if (method_exists($this->cfType->getObject(), $this->fieldCamelCase . 'ContextualValidator')) {
      $this->contextualValidator = function () {
        return $this->cfType->getObject()->{$this->fieldCamelCase . 'ContextualValidator'}($this);
      };
    }
  }

  /**
   * Assign sanitizers to field, from definition and custom callbacks.
   */
  protected function setSanitizers() {
    // Look for custom sanitizer.
    if (method_exists($this->cfType->getObject(), $this->fieldCamelCase . 'Sanitizer')) {
      $this->fieldSanitizerMethods[] = function ($input) {
        return $this->cfType->getObject()->{$this->fieldCamelCase . 'Sanitizer'}($input);
      };
    }
    if (!empty($this->fieldInfo['sanitize']) && is_array($this->fieldInfo['sanitize'])) {
      array_map(function ($sanitizer) {
        if (is_array($sanitizer)) {
          $sanitizerType = array_shift($sanitizer);
          $sanitizerArgs = $sanitizer;
        }
        else {
          $sanitizerType = $sanitizer;
        }
        switch ($sanitizerType) {
          case 'strip html':
            $this->fieldSanitizerMethods[] = function ($input) {
              return $this->sanitizeStripHtml($input);
            };
            break;

            case 'trim':
              $this->fieldSanitizerMethods[] = function ($input) {
                return $this->sanitizeTrim($input);
              };
              break;

          default:
            throw new BadSanitizerException($this->cfType->getSingularName(), $this->field, $sanitizerType);

        }
      }, $this->fieldInfo['sanitize']);
    }
  }

  /**
   * Look for render method, and set it.  Fallback to default when possible.
   */
  protected function setRenderMethod() {
    if (method_exists($this->cfType->getObject(), $this->fieldCamelCase . 'Render')) {
      $this->renderMethod = function () {
        return $this->cfType->getObject()->{$this->fieldCamelCase . 'Render'}($this);
      };
    }
    else {
      switch ($this->fieldInfo['type']) {
        case 'validation only':
          $this->renderMethod = function () {
            return $this->renderNothing();
          };
          break;

        case 'text':
          $this->renderMethod = function () {
            return $this->renderTextField();
          };
          break;

        case 'boolean':
          $this->renderMethod = function () {
            return $this->renderBooleanField();
          };
          break;

        default:
          throw new MissingRenderMethodException($this->cfType->getSingularName(), $this->field);

      }
    }
  }

  /**
   * Determine validation status of value.
   */
  protected function validateValue() {
    $validationTestFailures = count($this->fieldValidationMethods);
    if ($validationTestFailures) {
      foreach ($this->fieldValidationMethods as $validator) {
        if (($validator)($this->value)) {
          $validationTestFailures--;
        }
      }
    }
    if (empty($validationTestFailures)) {
      $this->validationPassed = TRUE;
    }
    else {
      $this->warnIsInvalid();
    }
    $this->validationComplete = TRUE;
  }

  /**
   * Queue error message for invalid field data.
   */
  public function warnIsInvalid() {
    $transintKey = $this->cfType->getTransientId();
    $notifier = $this
      ->cfType
      ->getCfs()
      ->getNotifier();
    $notifier
      ->setTranientKey($transintKey)
      ->queueUserWarning($this->validationMessage)
      ->queueFieldWarning($this->field);
  }

  /**
   * Sanitize value.
   */
  protected function sanitizeValue() {
    $value = $this->value;
    if (!empty($this->fieldSanitizerMethods)) {
      foreach ($this->fieldSanitizerMethods as $sanitizer) {
        $value = ($sanitizer)((string) $value);
      }
    }
    $this->sanitizationComplete = TRUE;
    $this->sanitizedValue = $value;
  }

  /**
   * Call method for contextual validation, if set, passing $this to it.
   */
  public function callContextualValidator() {
    if (!empty($this->contextualValidator)) {
      ($this->contextualValidator)();
    }
  }

  /**
   * Persist value to storage, if field is to be saved.
   *
   * @param int $postId
   *   Wordpress ID of post to associate with data.
   */
  public function persistValue(int $postId) {
    // If a custom persist method exists, it needs to handle all decisions
    // including cases where it doesn't actually persist data.
    if (method_exists($this->cfType->getObject(), $this->fieldCamelCase . 'Persist')) {
      $this->cfType->getObject()->{$this->fieldCamelCase . 'Persist'}($this);
      return;
    }
    // Calling getValue() ensures that validation and sanitization occur.
    $value = $this->getValue();
    // Some fields never persist.
    if (!$this->neverPersist &&
    // Others require valid data.
      ($this->validationPassed || $this->persistInvalidValues)) {
      $persisted = $this
        ->cfType
        ->getCfs()
        ->getStorage()
        ->persist($postId, $this->field, $value);
      if (!persisted) {
        // @TODO throw exception if persist fails.
      }
    }
  }

  /**
   * Validate that input is not empty.
   *
   * 0 is valid.  Other falsey values are invalid.
   *
   * @param mixed $input
   *   Input submitted by user.  Not yet sanitized.
   *
   * @return bool
   *   TRUE if $input passes validator. Else FASLE.
   */
  public function validateNotEmpty($input) {
    if (!empty($input) || $input === 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate that input string is a URL.
   *
   * @param string $input
   *   Input submitted by user.  Not yet sanitized.
   *
   * @return bool
   *   TRUE if $input passes validator. Else FASLE.
   */
  public function validateIsUrl(string $input) {
    if (filter_var($input, FILTER_VALIDATE_URL)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate that input string is a valid email.
   *
   * @param string $input
   *   Input submitted by user.  Not yet sanitized.
   *
   * @return bool
   *   TRUE if $input passes validator. Else FASLE.
   */
  public function validateIsEmail(string $input) {
    if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate that input string is at least a minimum length.
   *
   * @param string $input
   *   Input submitted by user.  Not yet sanitized.
   * @param int $minLength
   *   Expected minimum length.
   *
   * @return bool
   *   TRUE if $input passes validator. Else FASLE.
   */
  public function validateMinLength(string $input, int $minLength) {
    if ($minLength <= mb_strlen($input)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate that input string is at least a maximum length.
   *
   * @param string $input
   *   Input submitted by user.  Not yet sanitized.
   * @param int $maxLength
   *   Expected maximum length.
   *
   * @return bool
   *   TRUE if $input passes validator. Else FASLE.
   */
  public function validateMaxLength(string $input, int $maxLength) {
    if ($maxLength >= mb_strlen($input)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate that input string matches a regex pattern.
   *
   * @param string $input
   *   Input submitted by user.  Not yet sanitized.
   * @param string $pattern
   *   Pattern to be matched.
   *
   * @return bool
   *   TRUE if $input passes validator. Else FASLE.
   */
  public function validatePattern(string $input, string $pattern) {
    return preg_match($pattern, $input);
  }

  /**
   * Strip HTML from a string.
   *
   * @param string $input
   *   Input submitted by user.
   *
   * @return string
   *   Sanitized string.
   */
  public function sanitizeStripHtml(string $input) {
    return wp_strip_all_tags($input);
  }

  /**
   * Trim a string.
   *
   * @param string $input
   *   Input submitted by user.
   *
   * @return string
   *   Sanitized string.
   */
  public function sanitizeTrim(string $input) {
    return trim($input);
  }

  /**
   * Render function with empty output.
   *
   * For field definitions affecting form components rendered by Wordpress or
   * otherwise not requiring output. JS, CSS, or more may still be added for
   * a field using this method.
   *
   * @return string
   *   Empty string.
   */
  public function renderNothing() {
    return '';
  }

  /**
   * Render a default text field.
   *
   * @return string
   *   HTML for text field.
   */
  public function renderTextField() {
    $name = $this->getName();
    $fieldId = $this->field;
    $value = esc_attr($this->value);
    return '<label><span class="field--label field--label__text field--label__' . $fieldId . '">' .
      $name .
      '</span><input class="field--input field--input__text field--input__' . $fieldId . '" ' .
      'type="text" name="' . $fieldId . '" value="' . $value . '" /></label>';
  }

  /**
   * Render a default boolean checkbox field.
   *
   * @return string
   *   HTML for checkbox field.
   */
  public function renderBooleanField() {
    $name = $this->getName();
    $fieldId = $this->field;
    $checked = empty($this->value) ? '' : ' checked="checked" ';
    return '<input class="field--input field--input__checkbox field--input__' . $fieldId . '" ' .
      'type="checkbox" id="' . $fieldId . '" name="' . $fieldId . '"' . $checked . ' />' .
      '<label for="' . $fieldId . '"><span class="field--label field--label__checkbox field--label__' . $fieldId . '">' . $name .
      '</span></label>';
  }

}
