<?php

namespace CustomFields;

use CustomFields\Definition\NullDefinition;
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
   * CustomFieldsDefinition object, potentially with custom methods.
   *
   * @var \CustomFields\Definition\DefinitionInterface
   */
  protected $object;

  /**
   * CustomFields object; serves as DI container for cache and notifier.
   *
   * @var \CustomFields\CustomFields
   */
  protected $cfs;

  /**
   * Wordpress post object relevant to current request.
   *
   * @var int
   */
  protected $postId;

  /**
   * Key for short term storage of warnings etc related to post.
   *
   * @var string
   */
  protected $transientId = '';

  /**
   * Whether user is saving data or data comes from already persisted values.
   *
   * @var bool
   */
  protected $userRequestedSave = FALSE;

  /**
   * Array of \CustomFields\CustomFieldsField objects, keyed by field id.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * Array of \CustomFields\CustomFieldsMetabox objects, keyed by metabox id.
   *
   * @var array
   */
  protected $metaboxes = [];

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
   *   CustomFields object used as container; includes cache, notifier, and
   *   storage objects.
   */
  protected function __construct(string $singularName, string $pluralName, array $definition, CustomFields $cfs) {
    $this->singularName = $singularName;
    $this->pluralName = $pluralName;
    $this->definition = $definition;
    $this->object = empty($definition['object']) ? new NullDefinition() : $definition['object'];
    $this->cfs = $cfs;
    // Existing post types aren't redeclared.
    if (!in_array($singularName, array_keys(get_post_types()))) {
      $this->declarePostType();
    }
    add_filter('post_updated_messages', [$this, 'addWarningsToMessages']);
    add_action("add_meta_boxes_{$this->singularName}", [$this, 'prepareFields']);
    add_action("save_post_{$this->singularName}", [$this, 'saveFieldsData'], 10, 3);
    if (!empty($definition['replace_archive_with_page'])) {
      $this->replaceArchiveWithPage();
    }
    if (method_exists($this->object, 'shortcodeCallback')) {
      add_shortcode($this->getPluralName(), [$this->object, 'shortcodeCallback']);
    }
    $this->addColumnsToAdmin();
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
   * Get object with custom methods for type.
   *
   * @return \CustomFields\Definition\DefinitionInterface
   *   Object with custom methods for type.
   */
  public function getObject() {
    return $this->object;
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
   * Get post object ID relevant to current request.
   *
   * @return int
   *   Wordpress post ID or 0.
   */
  public function getPostId() {
    return $this->postId ?: 0;
  }

  /**
   * Get transient key relevant to current request.
   *
   * @return string
   *   Key for short term storage of warnings etc related to post.
   */
  public function getTransientId() {
    return $this->transientId;
  }

  /**
   * Get status of flag $userRequestedSave.
   *
   * @return bool
   *   TRUE is user request save.  FALSE is values come from persisted data.
   */
  public function getUserRequestedSave() {
    return $this->userRequestedSave;
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
   * Get metabox object by key. Returns NULL if not defined.
   *
   * @param string $metabox
   *   Key of metabox object.
   *
   * @return \CustomFields\CustomFieldMetabox
   *   Metabox object associated with this type.
   */
  public function getMetabox(string $metabox) {
    if (array_key_exists($metabox, $this->metaboxes)) {
      return $this->metaboxes[$metabox];
    }
    else {
      return NULL;
    }
  }

  /**
   * Get array of all metabox objects for this type.
   *
   * @return array
   *   Array of \CustomFields\CustomFieldMetabox, keyed by metabox id.
   */
  public function getMetaboxes() {
    return $this->metaboxes;
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
   * Register this type with WP.
   */
  public function declarePostType() {
    $name = $this->getSingularName();
    $def = $this->getDefinition()['wp_definition'];
    register_post_type($name, $def);
  }

  /**
   * Build fields and metaboxes.
   *
   * Is a callback for WP action (such as add_meta_box).
   *
   * @param object|int $post
   *   Wordpress post object or post ID related to current request.
   */
  public function prepareFields($post = NULL) {
    if (empty($post)) {
      $this->postId = 0;
    }
    else {
      if (is_object($post)) {
        $this->postId = $post->ID;
      }
      else {
        $this->postId = (int) $post;
      }
    }
    $this->setTransientId();
    if (!empty($this->definition['fields'])) {
      $this->buildFields();
    }
    if (!empty($this->definition['metaboxes'])) {
      $this->buildMetaboxes();
    }
    foreach ($this->metaboxes as $metabox) {
      if ($metabox->getTitle()) {
        add_meta_box(
          $metabox->getMetabox(),
          $metabox->getTitle(),
          [$metabox, 'printMetaboxHtml'],
          $this->singularName,
          'normal',
          'default'
        );
      }
    }
  }

  /**
   * Determine fields to save; validate, sanitize, issue warnings, and/or save.
   *
   * Is a callback for WP action (such as save_post).
   *
   * @param int $postId
   *   Wordpress post ID related to current request.
   * @param \WP_Post $postObject
   *   WP post object (ignored).
   * @param bool $update
   *   Whether this is an existing post being updated or not.
   */
  public function saveFieldsData(int $postId, \WP_Post $postObject, $update = FALSE) {
    // Don't include in auto-save.
    if (defined('\DOING_AUTOSAVE') && \DOING_AUTOSAVE || !$update) {
      return;
    }

    // Method must handle its own permission check.
    if (!current_user_can('edit_page', $postId)) {
      return;
    }
    $nonce = isset($_POST['post_options_nonce']) ? $_POST['post_options_nonce'] : NULL;
    if (!wp_verify_nonce($nonce, 'post_options_nonce')) {
      // @TODO implement nonce per metabox; check all; return if any fail to match.
    }
    $this->userRequestedSave = TRUE;
    $this->prepareFields($postId);
    $metaboxes = $this->getMetaboxes();
    if (empty($metaboxes)) {
      return;
    }
    $fieldsToSave = array_reduce($metaboxes, function ($fieldsToSave, $metabox) {
      return array_merge($fieldsToSave, $metabox->getMetaboxFields());
    }, []);
    array_map(function ($field) {
      $field->callContextualValidator();
    }, $fieldsToSave);
    array_map(function ($field) use ($postId) {
      $field->persistValue($postId);
    }, $fieldsToSave);
  }

  /**
   * Build field objects from definition.
   */
  protected function buildFields() {
    $definition = $this->getDefinition();
    if (!empty($definition['fields']) && is_array($definition['fields'])) {
      foreach ($definition['fields'] as $field => $fieldInfo) {
        $newField = CustomFieldsField::buildField($this, $field, $fieldInfo);
        if (!empty($newField)) {
          $this->fields[$field] = $newField;
        }
      }
    }
  }

  /**
   * Build field objects from definition.
   */
  protected function buildMetaboxes() {
    $definition = $this->getDefinition();
    if (!empty($definition['metaboxes']) && is_array($definition['metaboxes'])) {
      foreach ($definition['metaboxes'] as $metabox => $metaboxInfo) {
        $newMetabox = CustomFieldsMetabox::buildMetabox($this, $metabox, $metaboxInfo);
        if (!empty($newMetabox)) {
          $this->metaboxes[$metabox] = $newMetabox;
        }
      }
    }
  }

  /**
   * Set transient ID from $this->postId or $_GET data.
   */
  protected function setTransientId() {
    if (!empty($this->postId)) {
      $postId = $this->postId;
    }
    elseif (!empty($_GET['post'])) {
      $postId = (int) $_GET['post'];
    }
    else {
      $postId = 0;
    }
    $this->transientId = 'custom_post_message_' . $postId . '_' .
      get_current_user_id();
  }

  /**
   * Callback to maniupulate Wordpress messages.
   *
   * @param array $messages
   *   Messages array supplied by Wordpress.
   *
   * @return array
   *   Messages array with warnings added to selected message.
   */
  public function addWarningsToMessages(array $messages) {
    if (empty($this->getTransientId())) {
      $this->setTransientId();
    }
    $warnings = $this
      ->cfs
      ->getNotifier()
      ->retrieveWarnings($this->getTransientId());
    if (is_array($warnings)) {
      // Modified from wp-admin/edit-form-advanced.php...
      // Get value of intended message.
      if (isset($_GET['message'])) {
        $messageId = (int) $_GET['message'];
        if (isset($messages[$this->singularName][$messageId])) {
          $message = $messages[$this->singularName][$messageId];
        }
        elseif (empty($messages[$this->singularName]) && isset($messages['post'][$messageId])) {
          $message = $messages['post'][$messageId];
        }
      }
      // Append custom warnings to $message.
      $errorMessages = $warnings['messages'];
      if (count($errorMessages) === 1) {
        $message .= "</p><p class='warning'>Warning: " . $errorMessages[0];
      }
      elseif (count($errorMessages)) {
        $message .= "</p><p><span class='warning'>Warnings:</span><ul>";
        foreach ($errorMessages as $errorMessage) {
          $message .= "<li class='warning'>$errorMessage</li>";
        }
        $message .= "</ul>";
      }
      // Set values so WP core to display combined message.
      $_GET['message'] = 1;
      $messages[$this->singularName][1] = $message;

      // Add JS to page, assigning warning class.
      // @TODO Attach script properly instead of echoing here.
      $errorElements = $warnings['elements'];
      if (count($errorElements)) {
        $errorElements = array_reduce($errorElements, function ($carry, $item) {
          $carry = empty($carry) ? '' : $carry . ', ';
          $carry .= "#$item, .field__$item";
          return $carry;
        }, '');
        echo "
<script type='text/javascript'>
  (function() {
    window.addEventListener('load', function () {
      console.log('$errorElements');
      var els = document.querySelectorAll('$errorElements');
      for (var i=0; i<els.length; i++) {
        els[i].classList.add('warning');
      }
    }, false);
  })();
</script>
        ";
      }

    }
    return $messages;
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
      if (method_exists($this->object, 'shortcodeCallback')) {
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
   * Declare hooks to add columns to admin view, set up sorting, etc.
   */
  protected function addColumnsToAdmin() {
    $definition = $this->getDefinition();
    if (!empty($definition['add columns']) && is_array($definition['add columns'])) {
      foreach ($definition['add columns'] as $column => $columnInfo) {
        new CustomFieldsColumn($this, $column, $columnInfo);
      }
    }
  }

}
