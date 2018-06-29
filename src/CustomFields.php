<?php

namespace CustomFields;

use CustomFields\Cache\CacheInterface;
use CustomFields\Notifier\NotifierInterface;
use CustomFields\Storage\StorageInterface;
use CustomFields\Exception\CacheNullException;
use CustomFields\Exception\ExceptionInterface;
use CustomFields\Exception\NoDefinitionsException;
use CustomFields\Exception\NotInitializedException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Initialize Custom Fields and find configurations.
 */
class CustomFields {

  /**
   * Cache object.
   *
   * @var \CustomFields\Cache\CacheInterface
   */
  protected $cache;

  /**
   * Notifier object.
   *
   * @var \CustomFields\Notifier\NotifierInterface
   */
  protected $notifier;

  /**
   * Storage object.
   *
   * @var \CustomFields\Storage\StorageInterface
   */
  protected $storage;

  /**
   * Definitions, keyed by WP post type.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Filesystem path to definitions.
   *
   * @var string
   */
  protected $definitionsPath;

  /**
   * Initialization state of object (usually unset or TRUE)
   *
   * @var bool
   */
  protected $initialized;

  /**
   * Global WP query object.
   *
   * Referenced for the entirely cosmetic reason that "global" only needs to be
   * used once, in the constructor below, instead of all over this project.
   *
   * @var \WP_Query
   */
  public $wpQuery;

  /**
   * Inject cache and nofifier.
   *
   * Do not directly use constructor.  Initalize objects through static call to
   * initialize(). Future versions may re-write the constructor such as by using
   * a configuration file to set cache and notifier services.
   */
  public function __construct(CacheInterface $cache, NotifierInterface $notifier, StorageInterface $storage) {
    global $wp_query;
    $this->cache = $cache;
    $this->notifier = $notifier;
    $this->storage = $storage;
    $this->wpQuery = &$wp_query;
  }

  /**
   * Get cache object.
   *
   * @return \CustomFields\Cache\CacheInterface
   *   Cache object.
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * Get notification object.
   *
   * @return \CustomFields\Notifier\NotifierInterface
   *   Admin notification object.
   */
  public function getNotifier() {
    return $this->notifier;
  }

  /**
   * Get storage object.
   *
   * @return \CustomFields\Storage\StorageInterface
   *   Storage object.
   */
  public function getStorage() {
    return $this->storage;
  }

  /**
   * Get definitions.
   *
   * @return array
   *   PHP definitions of custom types, fields, etc., keyed by type.
   *
   * @throws \CustomFields\Exception\NotInitializedException
   *   Thrown if no instance is not initialized.
   */
  public function getDefinitions() {
    if (!$this->isInitialized()) {
      throw new NotInitializedException();
    }
    return $this->definitions;
  }

  /**
   * Check if object is initialized (with type definitions).
   *
   * @return bool
   *   TRUE when initialized.  FALSE when not.
   */
  public function isInitialized() {
    if (!empty($this->initialized)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Initialize plugin.  Can only succeed once per instance.
   *
   * Locate defitions for custom types, boxes, and fields. Find built
   * definitions. Build others from YAML.  Can be re-run if initial attempt
   * fails, but once initialized will not allow changes.
   *
   * @param string $definitionsPath
   *   Path to directory of definitions.
   *
   * @return static
   */
  public function initialize(string $definitionsPath) {
    // @TODO Add option to cache all types.  If found, return it here.
    // @TODO Create admin page to manage cached definitions.
    if ($this->isInitialized()) {
      return $this;
    }
    $this->definitionsPath = $definitionsPath;
    try {
      $definitionDirs = $this->findDefinitions();
      $definitionHashes = array_map(['CustomFields\\CustomFieldsUtilities', 'hashDirectory'], $definitionDirs);
    }
    catch (ExceptionInterface $e) {
      $this->getNotifier()->queueAdminNotice('' . $e);
      return $this;
    }
    $this->definitions = $this->collectDefinitions($definitionHashes, $definitionDirs);
    $this->initialized = TRUE;
    return $this;
  }

  /**
   * Find YAML definitions.
   *
   * @return array
   *   Array of definition_name => definition_directory_path.
   *
   * @throws \CustomFields\Exception\NoDefinitionsException
   *   Thrown if no definitions are defined.
   */
  protected function findDefinitions() {
    $definitionsPath = $this->definitionsPath;
    if (is_dir($definitionsPath)) {
      $dir = dir($definitionsPath);
    }
    if (empty($dir)) {
      throw new NoDefinitionsException();
    }
    $definitions = [];
    while (($defName = $dir->read()) !== FALSE) {
      if ($defName[0] != '.' && is_dir($definitionsPath . '/' . $defName)) {
        $defDir = $definitionsPath . '/' . $defName;
        $ymlPath = $defDir . '/' . $defName . '.yml';
        if (file_exists($ymlPath)) {
          $definitions[$defName] = $defDir;
        }
      }
    };
    if (empty($definitions)) {
      throw new NoDefinitionsException();
    }
    $this->autoloadDefinitionClasses();
    return $definitions;
  }

  /**
   * Define autoloader for definition classes.
   *
   * Code partly borrowed from https://www.php-fig.org/psr/psr-4/examples/.
   */
  protected function autoloadDefinitionClasses() {
    spl_autoload_register(function ($class) {
      $prefix = 'CustomFieldsDefinition\\';
      $baseDir = $this->definitionsPath . '/';
      $len = strlen($prefix);
      if (strncmp($prefix, $class, $len) !== 0) {
        return;
      }
      $relativeClass = substr($class, $len);
      $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
      if (file_exists($file)) {
        require $file;
      }
    });
  }

  /**
   * Retrieve each cached definition. Build and cache others.
   *
   * @param array $definitionHashes
   *   Hashes of definitions, keyed by custom post types.
   * @param array $definitionDirs
   *   Paths to YAML definitions, keyed by custom post types.
   *
   * @return array
   *   All definitions as PHP, keyed by name.
   */
  protected function collectDefinitions(array $definitionHashes, array $definitionDirs) {
    $definitions = [];
    foreach ($definitionHashes as $type => $hash) {
      try {
        $definition = $this->cache->get($hash);
      }
      catch (CacheNullException $e) {
        $definition = $this->parseDefinition($definitionDirs[$type], $type);
        if ($definition) {
          $this->cache->set($hash, $definition);
        }
      }
      if ($definition) {
        $definitions[$type] = $definition;
      }
      $className = "\\CustomFieldsDefinition\\$type\\" . ucfirst($type);
      if (class_exists($className)) {
        $definitions[$type]['object'] = new $className($this);
      }
    }
    return $definitions;
  }

  /**
   * Build PHP from YAML config file for custom type/boxes/fields.
   *
   * @param string $path
   *   Path to YAML directory.
   * @param string $type
   *   Post type described in directory.
   *
   * @return array
   *   PHP of data for definition(s).
   */
  protected function parseDefinition(string $path, string $type) {
    try {
      $definition = Yaml::parse(file_get_contents($path . '/' . $type . '.yml'),
        Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
    }
    catch (ParseException $e) {
      $definition = NULL;
      $notice = sprintf("The CustomFields definition for “%s” could not be " .
       "parsed. It will be ignored, which may cause other errors. The parser " .
       "returned:<br /><pre>%s</pre>", $type, $e);
      $this->getNotifier()->queueAdminNotice($notice);
    }
    return $definition;
  }

}
