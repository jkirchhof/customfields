<?php

namespace CustomFields;

/**
 * Add and manage metabox for custom field type.
 */
class CustomFieldsMetabox {

  /**
   * CustomFieldsType object of type with metabox to be declared.
   *
   * @var \CustomFields\CustomFieldsType
   */
  protected $cfType;

  /**
   * Machine name of metabox.
   *
   * @var string
   */
  protected $metabox;

  /**
   * The camelCase version of metabox name.
   *
   * @var string
   */
  protected $metaboxCamelCase;

  /**
   * Metabox definition.
   *
   * @var array
   */
  protected $metaboxInfo;

  /**
   * Post ID (0 for new post) of post for which field is built.
   *
   * @var int
   */
  protected $postId;

  /**
   * Human-readable metabox title.
   *
   * @var string
   */
  protected $title;

  /**
   * Method to render metabox in form.
   *
   * @var callable
   */
  protected $renderMethod;

  /**
   * Array of fields in metabox, after checking permission, context, etc.
   *
   * Each is an object of \CustomFields\CustomFields\Fields, keyed by
   * its id.
   *
   * @var array
   */
  protected $metaboxFields = [];

  /**
   * Construct object for field.
   *
   * Protected method, forcing use of buildField factory, which checks user
   * permission a field before building it.
   *
   * @param CustomFieldsType $cfType
   *   CustomFieldsType object of type with field to be declared.
   * @param string $metabox
   *   Machine name of metabox.
   * @param array $metaboxInfo
   *   Metabox definition.
   */
  protected function __construct(CustomFieldsType $cfType, string $metabox, array $metaboxInfo) {
    $this->cfType = $cfType;
    $this->metabox = $metabox;
    $this->metaboxCamelCase = CustomFieldsUtilities::makeCamelCase($metabox);
    $this->metaboxInfo = $metaboxInfo;
    $this->postId = $cfType->getPostId();
    $this->title = empty($metaboxInfo['title']) ? NULL : $metaboxInfo['title'];
    $this->setRenderMethod();
    $this->setMetaboxFields();
  }

  /**
   * Factory to construct metabox object after checking permission.
   *
   * @param CustomFieldsType $cfType
   *   CustomFieldsType object of type with field to be declared.
   * @param string $metabox
   *   Machine name of metabox.
   * @param array $metaboxInfo
   *   Metabox definition.
   *
   * @return static|null
   *   Metabox object.
   */
  public static function buildMetabox(CustomFieldsType $cfType, string $metabox, array $metaboxInfo) {
    if (!empty($metaboxInfo['display']) && $metaboxInfo['display'] == 'remove') {
      $removeBox = TRUE;
    }
    if (!empty($metaboxInfo['requires']) && is_array($metaboxInfo['requires'])) {
      foreach ($metaboxInfo['requires'] as $permission) {
        if (!current_user_can($permission, $cfType->getPostId())) {
          $removeBox = TRUE;
        }
      }
    }
    if (!empty($removeBox)) {
      // Remove box from all contexts.
      add_action('do_meta_boxes', function () use ($metabox, $cfType) {
        foreach (['normal', 'side', 'advanced'] as $context) {
          remove_meta_box($metabox, $cfType->getSingularName(), $context);
        }
      }, 1);
      return;
    }
    return new static($cfType, $metabox, $metaboxInfo);
  }

  /**
   * Get machine name of metabox.
   *
   * @return string
   *   Machine name of metabox.
   */
  public function getMetabox() {
    return $this->metabox;
  }

  /**
   * Human readable title of metabox.
   *
   * @return string
   *   Title of metabox.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Get fields available to metabox.
   *
   * @return array
   *   Array of fields in metabox.
   */
  public function getMetaboxFields() {
    return $this->metaboxFields;
  }

  /**
   * Look for render method, and set it. Fallback to printing fields in order.
   */
  protected function setRenderMethod() {
    if (method_exists($this->cfType->getObject(), $this->metaboxCamelCase . 'MetaboxRender')) {
      $this->renderMethod = function () {
        return $this->cfType->getObject()->{$this->metaboxCamelCase . 'MetaboxRender'}($this);
      };
    }
    else {
      $this->renderMethod = function () {
        return $this->defaultRenderMetabox();
      };
    }
  }

  /**
   * Determine and set fields available to metabox.
   */
  protected function setMetaboxFields() {
    if (empty($this->metaboxInfo['fields'])) {
      return;
    }
    foreach ($this->metaboxInfo['fields'] as $field) {
      $builtField = $this->cfType->getField($field);
      if (!empty($builtField)) {
        $this->metaboxFields[$field] = $builtField;
      }
    }
  }

  /**
   * Render metabox fields in order.
   *
   * @return string
   *   HTML to be printed as metabox content.
   */
  protected function defaultRenderMetabox() {
    $renderedFields = array_map(function ($field) {
      $fieldId = $field->getField();
      return '<div class="field field__' . $fieldId . '">' .
        $field->getRenderedField() .
        '</div>';
    }, $this->metaboxFields);
    if (empty($renderedFields)) {
      return '';
    }
    return implode($renderedFields);
  }

  /**
   * Print metabox content, including additional JS/CSS.
   */
  public function printMetaboxHtml() {
    // @TODO add JS/CSS
    // @TODO add nonce using wp_nonce_field().
    print ($this->renderMethod)();
  }

}
