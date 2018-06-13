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
   * Array of \CustomFields\CustomFieldsField objects, keyed by field id.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * Constructor.  To be invoked by factory method static::buildTypes().
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
    // Existing post types aren't redeclared.
    if (!in_array($singularName, array_keys(get_post_types()))) {
      add_action('init', [$this, 'declarePostType']);
    }

    /* @TODO
     * Build metaboxes.
     * Determine used fields.
     * See details in "custom boxes plan.txt".
     */
    if (!empty($definition['fields'])) {
      $this->buildFields();
    }
    if (!empty($definition['replace_archive_with_page'])) {
      $this->replaceArchiveWithPage();
    }
    if (!empty($definition['create_shortcode'])) {
      $this->createShortcode();
    }
    if (!empty($definition['add_columns'])) {
      $this->addColumnsToAdmin();
    }
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

  /**
   * Get field object by key. Returns NULL if not defined.
   *
   * @param string $field
   *   Key of field object.
   *
   * @return \CustomFields\CustomFieldField
   *   Field object associated with this type.
   */
  public function getField(string $field) {
    if (array_key_exists($field, $this->fields)) {
      return $this->fields[$field];
    }
    else {
      return NULL;
    }
  }

  /**
   * Get array of all field objects for this type.
   *
   * @return array
   *   Array of \CustomFields\CustomFieldField, keyed by field id.
   */
  public function getFields() {
    return $this->fields;
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
    $defs = [];
    foreach ($cfs->getDefinitions() as $name => $defArray) {
      try {
        if (!empty($defArray) && !empty($defArray['singular_name'])
          && !empty($defArray['plural_name'])) {
          $singularName = $defArray['singular_name'];
          $pluralName = $defArray['plural_name'];
        }
        else {
          throw new BadDefinitionException();
        }
      }
      catch (BadDefinitionException $e) {
        unset($defArray);
        $message = sprintf("<strong>Error defining type “%s”</strong><br /> ",
          $name) . $e;
        $cfs
          ->getNotifier()
          ->queueAdminNotice($message);
      }
      if (!empty($defArray) && !empty($defArray['wp_definition'])) {
        $defs[$name] = new static($singularName, $pluralName, $defArray, $cfs);
      }
    }
    return $defs;
  }

  /**
   * Callback for 'init' action to register this type with WP.
   */
  public function declarePostType() {
    $name = $this->getSingularName();
    $def = $this->getDefinition()['wp_definition'];
    register_post_type($name, $def);
  }

  /**
   * Build field objects from definition.
   */
  protected function buildFields() {
    $definition = $this->getDefinition();
    if (!empty($definition['fields']) && is_array($definition['fields'])) {
      foreach ($definition['fields'] as $field => $fieldInfo) {
        $this->fields[$field] = new CustomFieldsField($this, $field, $fieldInfo);
      }
    }
  }

  /**
   * Replace default WP archive with an editable page.
   */
  protected function replaceArchiveWithPage() {
    // Make sure a page exists that will replace the post archive.
    add_action('init', [$this, 'createPageToReplaceArchive']);
    // Direct requests for the post type archive to that page.
    add_filter('template_include', [$this, 'redirectArchiveToPage'], 99);
    // Add item ancestor class to menu pages with titles matching the post type
    // I.e. when viewing a person post, the menu recognizes the page "people" as
    // the ancestor of the post, despite it not actually being that.
    add_filter('nav_menu_css_class', [$this, 'fixCustomPostMenuClasses'], 10, 2);
  }

  /**
   * If none exists, create page to replace default WP archive for type.
   *
   * Page initially contains shortcode for type, if defined.  Otherwise it's
   * empty.
   */
  public function createPageToReplaceArchive() {
    $pluralName = $this->getPluralName();
    if (!get_page_by_path($pluralName)) {
      if (!empty($this->getDefinition()['create_shortcode'])) {
        $postContent = '[' . $pluralName . ']';
      }
      else {
        $postContent = '';
      }
      $postConfig = [
        'post_name' => $pluralName,
        'post_status' => 'publish',
        'post_title' => ucfirst($pluralName),
        'post_content' => $postContent,
        'post_type' => 'page',
      ];
      wp_insert_post($postConfig);
    }
  }

  /**
   * Redirect requests for type archive to page that replaces it.
   *
   * Attempts to load templates from definition, using page.php as a default.
   * If none is found, falls back to original template type. Does not throw an
   * error or give a warning when this happens.
   *
   * @var string $template
   *   Path to theme template calculated by WP, such as the Archive template.
   *
   * @return string
   *   Path to theme template that will be used.
   */
  public function redirectArchiveToPage(string $template) {
    if (is_post_type_archive($this->getSingularName())) {
      $this->getCfs()->wpQuery = new \WP_Query([
        'pagename' => $this->getPluralName(),
        'post_parent' => 0,
      ]);
      if (is_array($this->definition['replace_archive_with_page'])) {
        $preferredTemplates = $this->definition['replace_archive_with_page'];
      }
      else {
        $preferredTemplates = ['page.php'];
      }
      $newTemplate = locate_template($preferredTemplates);
    }
    return $newTemplate ?: $template;
  }

  /**
   * Assign CSS as if page that replaced archive is ancestor of posts.
   *
   * Theme needs to support styling for class .current-menu-ancestor, which is
   * the WP recommended class for ancestor menu itmes, per:
   * https://developer.wordpress.org/reference/functions/wp_nav_menu/
   *
   * @param array $css_class
   *   Array of CSS classes of menu item.
   * @param \WP_Post $item
   *   Post object of item in menu.
   *
   * @return array
   *   Modified array of CSS classes of menu item.
   */
  public function fixCustomPostMenuClasses(array $css_class, \WP_Post $item) {
    static $query_post_type;
    $query_post_type = empty($query_post_type) ?
      strtolower(get_post_type()) : $query_post_type;
    if (!is_search() && $query_post_type == $this->getSingularName()
      && strtolower($item->title) == $this->getPluralName()) {
      array_push($css_class, 'current-menu-ancestor');
    }
    return $css_class;
  }

  /**
   * Declare callback for shortcode, or notify admins if callback is missing.
   */
  protected function createShortcode() {
    $expectedCallback = 'cf__' . $this->getPluralName() . '__shortcode';
    if (is_callable($expectedCallback)) {
      add_shortcode($this->getPluralName(), $expectedCallback);
    }
    else {
      $message = sprintf('<strong>Error defining shortcode for type ' .
        '“%s”</strong><br />Shortcodes will not be processed as expected ' .
        'and will likely be visible as raw text inside of posts.',
        $this->getSingularName());
      $this
        ->cfs
        ->getNotifier()
        ->queueAdminNotice($message);
    }
  }

  /**
   * Declare hooks to add columns to admin view, set up sorting, etc.
   */
  protected function addColumnsToAdmin() {
    $definition = $this->getDefinition();
    if (!empty($definition['add_columns']) && is_array($definition['add_columns'])) {
      foreach ($definition['add_columns'] as $column => $columnInfo) {
        new CustomFieldsColumn($this, $column, $columnInfo);
      }
    }
  }

}
