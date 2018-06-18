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
   * Methods to call for initial field validation.
   *
   * Each element is an array:
   *   0 => callable
   *   1 => args passed to callable
   * Args array can be empty but is unpacked if existant.
   *
   * @var array
   */
  protected $fieldValidtionMethods;

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
   * Each element is an array:
   *   0 => callable
   *   1 => args passed to callable
   * Args array can be empty but is unpacked if existant.
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
   * @param int $postId
   *   Post ID (0 for new post) for which field is being built.
   */
  protected function __construct(CustomFieldsType $cfType, string $field, array $fieldInfo, int $postId) {
    $this->cfType = $cfType;
    $this->field = $field;
    $this->fieldInfo = $fieldInfo;
    $this->postId;
    $this->name = $fieldInfo['name'];
    $this->validationMessage = !empty($fieldInfo['error message']) ?
      $fieldInfo['error message'] :
      'The value of “' . $this->name . '” appears invalid.';
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
    $post = $cfType->getPost;
    if (!empty($post) && !empty($post->ID)) {
      $postId = $post->ID;
    }
    else {
      $postId = 0;
    }
    if (!empty($fieldInfo['requires']) && is_array($fieldInfo['requires'])) {
      foreach ($fieldInfo['requires'] as $permission) {
        if (!current_user_can($permission, $post)) {
          return;
        }
      }
    }
    return new static($cfType, $field, $fieldInfo, $postId);
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
      $this->value = $this
        ->cfType
        ->getCf()
        ->getStorage()
        ->retreive($this->postId, $this->field);
    }
  }

  /**
   * Assign validators to field, from definition and custom callbacks.
   */
  protected function setValidators() {
    // Look for custom validator.
    $fieldValidtionMethod = 'cf__' . $this->cfType->getPluralName() .
      '__' . $this->field . '__validationMethod';
    if (is_callable($fieldValidtionMethod)) {
      $this->fieldValidtionMethods[] = [$fieldValidtionMethod];
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
            $this->fieldValidtionMethods[] = [
              [$this, 'validateNotEmpty'],
            ];
            break;

          case 'url':
            $this->fieldValidtionMethods[] = [
              [$this, 'validateIsUrl'],
            ];
            break;

          case 'min-length':
            $this->fieldValidtionMethods[] = [
              [$this, 'validateMinLength'],
              $validationArgs,
            ];
            break;

          case 'max-length':
            $this->fieldValidtionMethods[] = [
              [$this, 'validateMaxLength'],
              $validationArgs,
            ];
            break;

          case 'pattern':
            $this->fieldValidtionMethods[] = [
              [$this, 'validatePattern'],
              $validationArgs,
            ];
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
    $contextualValidator = 'cf__' . $this->cfType->getPluralName() .
      '__' . $this->field . '__contextualValidator';
    if (is_callable($contextualValidator)) {
      $this->contextualValidator = $contextualValidator;
    }
  }

  /**
   * Assign sanitizers to field, from definition and custom callbacks.
   */
  protected function setSanitizers() {
    // Look for custom sanitizer.
    $fieldSanitizerMethod = 'cf__' . $this->cfType->getPluralName() .
      '__' . $this->field . '__sanitizerMethod';
    if (is_callable($fieldSanitizerMethod)) {
      $this->fieldSanitizerMethods[] = [$fieldSanitizerMethod];
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
    $renderMethod = 'cf__' . $this->cfType->getPluralName() .
      '__' . $this->field . '__renderMethod';
    if (is_callable($renderMethod)) {
      $this->renderMethod = function () use ($renderMethod) {
        return $renderMethod();
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
    $validationTestFailures = length($this->fieldValidationMethods);
    foreach ($this->fieldValidationMethods as $validator) {
      if (empty($validator[1])) {
        $result = $validator[0]($this->value);
      }
      else {
        $result = $validator[0]($this->value, ...$validator[1]);
      }
      if ($result) {
        $validationTestFailures--;
      }
    }
    if (!$validationTestFailures) {
      $this->validationPassed = TRUE;
    }
    $this->validationComplete = TRUE;
  }

  /**
   * Sanitize value.
   */
  protected function sanitizeValue() {
    $value = $this->value;
    foreach ($this->fieldSanitizerMethods as $sanitizer) {
      if (empty($sanitizer[1])) {
        $value = $sanitizer[0]($value);
      }
      else {
        $value = $sanitizer[0]($value, ...$sanitizer[1]);
      }
    }
    $this->sanitizationComplete = TRUE;
    $this->sanitizedValue = $value;
  }

  /**
   * Call method for contextual validation, passing $this to it.
   */
  public function callContextualValidator() {
    $this->contextualValidator($this);
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
