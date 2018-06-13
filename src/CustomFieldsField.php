<?php

namespace CustomFields;

/**
 * Add and manages column for custom field type.
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
   * Construct object for field.
   *
   * @param CustomFieldsType $cfType
   *   CustomFieldsType object of type with field to be declared.
   * @param string $field
   *   Machine name of field.
   * @param array $fieldInfo
   *   Column definition.
   */
  public function __construct(CustomFieldsType $cfType, string $field, array $fieldInfo) {
    $this->cfType = $cfType;
    $this->field = $field;
    $this->fieldInfo = $fieldInfo;
    // Populate columns.
    $this->columnContentMethod = 'cf__' . $this->cfType->getPluralName() . '__' . $this->column . '__columnContent';
    if (!is_callable($this->columnContentMethod)) {
      $this->columnContentMethod = [$this, 'defaultColumnContentMethod'];
    }

    /*
     * @TODO:
     * - field name (from definition[name])
     * - initial validator (validate without context; from definition[validate];
     *   also from callback pattern)
     * - basic validation/sanitization error message (from
     *   definition[error message])
     * - pre-save validator (validate after other values are processed; from
     *   callback pattern)
     * - pre-save sanitizer (from definition[allow]; from callback pattern)
     * - permission (from definition[requires])
     * - context in which to require, allow, exclude (used for save and for
     *   display)
     * - render function (default set by definition[type]; override set by
     *   callback pattern)
     * - css/js includes (queues for including with metaboxes; not used yet)
     */
  }

}
