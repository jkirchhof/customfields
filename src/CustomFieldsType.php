<?php

namespace CustomFields;

use CustomFields\Exception\BadDefinitionException;

/**
 * Method for managing configurations.
 */
class CustomFieldsType {

  protected $definition;
  protected $singularName;
  protected $pluralName;
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
   * @param CustomFields $cfs
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
   * @param CustomFields $cfs
   *   Array defining this type.
   *
   * @return array
   *   Array of {static}, one per definition in $cfs, keyed by type name.
   */
  public static function buildTypes(CustomFields $cfs) {
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
      try {
        if (!in_array($singularName, array_keys(get_post_types()))) {
          $cfType = new static($singularName, $pluralName, $defArray, $cfs);
          add_action('init', [$cfType, 'declarePostType']);
          $defs[$name] = $cfType;
        }
        else {
          throw new BadDefinitionException();
        }
      }
      catch (BadDefinitionException $e) {
        $cfs->getNotifier()->queueAdminNotice(sprintf("<strong>Cannot redefine type “%s”</strong><br /> ", $name) . $e);
        continue;
      }
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
