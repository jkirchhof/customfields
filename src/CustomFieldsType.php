<?php

namespace CustomFields;

/**
 * Method for managing configurations.
 */
class CustomFieldsType {

  protected $definition;
  protected $singularName;
  protected $pluralName;

  /**
   * Constructor.  To be invoked with static::factory().
   *
   * @param string $singularName
   *   Singular name of type used within WP.
   * @param string $pluralName
   *   Plural name of type used within WP.
   * @param array $definition
   *   Definition used to build type.
   */
  protected function __construct(string $singularName, string $pluralName, array $definition) {
    $this->singularName = $singularName;
    $this->pluralName = $pluralName;
    $this->definition = $definition;
    return $this;
  }

  /**
   * Factory to create type/fields object from definition arrays.
   *
   * @param array $defArray
   *   Array defining this type.
   *
   * @return static
   */
  public static function factory(array $defArray) {
    $singularName = $defArray['singular_name'];
    $pluralName = $defArray['plural_name'];
    unset($defArray['singular_name'], $defArray['plural_name']);
    // @TODO Check 3 vars above. If bad, return exception.
    $cfType = new static($singularName, $pluralName, $defArray);
    // @TODO - only call for non-existing post types - check those first.
    add_action('init', [$cfType, 'declarePostType']);
    return $cfType;
  }

  /**
   * Callback for 'init' action to register this type with WP.
   *
   * @return static
   */
  public function declarePostType() {
    $name = $this->getSingularName();
    $def = $this->getDefinition();
    register_post_type($name, $def);
    return $this;
  }

  /**
   * Get definition of type.
   *
   * @return array
   *   Definition used to build type.
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Get (machine) signular name.
   *
   * @return string
   *   Singular name of type used within WP.
   */
  public function getSingularName() {
    return $this->singularName;
  }

  /**
   * Get (machine) plural name.
   *
   * @return string
   *   Plural name of type used within WP.
   */
  public function getPluralName() {
    return $this->pluralName;
  }

}
