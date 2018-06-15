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
   * @var array
   */
  protected $metaboxFields;

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
   * @param int $postId
   *   Post ID (0 for new post) for which metabox is being built.
   */
  protected function __construct(CustomFieldsType $cfType, string $metabox, array $metaboxInfo, int $postId) {
    $this->cfType = $cfType;
    $this->metabox = $metabox;
    $this->metaboxInfo = $metaboxInfo;
    $this->postId;
    $this->title = $metaboxInfo['title'];
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
    if (!empty($metaboxInfo['requires']) && is_array($metaboxInfo['requires'])) {
      foreach ($metaboxInfo['requires'] as $permission) {
        // @TODO Remove "0 &&" once roles with permissions are set up.
        // @TODO Action to remove boxes when not allowed or in wrong context:
        // callback provided cftype, metabox id, and admin page context.
        if (0 && !current_user_can($permission)) {
          return;
        }
      }
    }
    // @TODO also pass post object or ID
    return new static($cfType, $metabox, $metaboxInfo, 0);
  }

  /**
   * Get fields available to metabox.
   *
   * @return array
   *   Array of fields in metabox.
   */
  public function getMetaboxFields() {
    // @TODO use field names or objects?
    return $this->metaboxFields;
  }

  /**
   * Look for render method, and set it. Fallback to printing fields in order.
   */
  protected function setRenderMethod() {
    $renderMethod = 'cf__' . $this->cfType->getPluralName() .
      '__' . $this->field . '__renderMethod';
    if (is_callable($renderMethod)) {
      $this->renderMethod = $renderMethod;
    }
    else {
      $this->renderMethod = [$this, 'defaultRenderMetabox'];
    }
  }

  /**
   * Determine and set fields available to metabox.
   */
  protected function setMetaboxFields() {
    // @TODO filter fields from definition to only include those defined and
    // set those fields in $this->metaboxFields.
  }

  /**
   * Render metabox fields in order.
   *
   * @return string
   *   HTML to be printed as metabox content.
   */
  protected function defaultRenderMetabox() {
    // @TODO For each field, wrap it in div with class names and
    // return it.
    return $metaboxHtml;
  }

  /**
   * Print metabox content, including additional JS/CSS.
   */
  public function printMetaboxHtml() {
    // @TODO add JS/CSS
    print $this->renderMethod();
  }

}
