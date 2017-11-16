<?php

namespace CustomFields;

use CustomFields\Exception\BadDefinitionException;

/**
 * Method for managing configurations.
 */
class CustomFieldsType {

  /**
   * Machine name of type in singular use.
   *
   * @var string
   */
  protected $singularName;

  /**
   * Machine name of type in plural use.
   *
   * @var string
   */

  protected $pluralName;
  /**
   * Type definition declaring WP post type.
   *
   * @var array
   */
  protected $definition;

  /**
   * CustomFields object; serves as DI container for cache and notifier.
   *
   * @var \CustomFields\CustomFields
   */
  protected $cfs;

  /**
   * Constructor.  To be invoked with static::factory().
   *
   * @param string $singularName
   *   Singular name of type used within WP.
   * @param string $pluralName
   *   Plural name of type used within WP.
   * @param array $definition
   *   Definition used to build type.
   * @param \CustomFields\CustomFields $cfs
   *   CustomFields object used as container; includes cache and notifier.
   */
  protected function __construct(string $singularName, string $pluralName, array $definition, CustomFields $cfs) {
    $this->singularName = $singularName;
    $this->pluralName = $pluralName;
    $this->definition = $definition;
    $this->cfs = $cfs;
  }

  /**
   * Factory to create type/fields objects from definitions.
   *
   * @param \CustomFields\CustomFields $cfs
   *   Array defining this type.
   *
   * @return array
   *   Array of {static}, one per definition in $cfs, keyed by type name.
   */
  public static function buildTypes(CustomFields $cfs) {
    // @TODO test if ($cfs->isInitialized()) and throw error if needed.
    $defs = [];
    foreach ($cfs->getDefinitions() as $name => $defArray) {
      try {
        if (!empty($defArray) && !empty($defArray['singular_name']) && !empty($defArray['plural_name'])) {
          $singularName = $defArray['singular_name'];
          $pluralName = $defArray['plural_name'];
          unset($defArray['singular_name'], $defArray['plural_name']);
        }
        else {
          throw new BadDefinitionException();
        }
      }
      catch (BadDefinitionException $e) {
        $cfs->getNotifier()->queueAdminNotice(sprintf("<strong>Error defining type “%s”</strong><br /> ", $name) . $e);
        continue;
      }
      $cfType = new static($singularName, $pluralName, $defArray, $cfs);
      // Existing post types aren't redeclared but may have added fields etc.
      if (!in_array($singularName, array_keys(get_post_types()))) {
        add_action('init', [$cfType, 'declarePostType']);
      }
      $defs[$name] = $cfType;
    }
    return $defs;
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
   * Get \CustomFields\CustomFields container.
   *
   * @return \CustomFields\CustomFields
   *   CustomFields container.
   */
  public function getCfs() {
    return $this->cfs;
  }

}
