<?php

namespace CustomFields;

use CustomFields\Cache\WPOptionsCache;
use CustomFields\Exception\NoDefinitionsException;
use CustomFields\Exception\CacheNullException;
use CustomFields\Exception\ExceptionInterface;
use CustomFields\Notifier\WPNotifier;
use CustomFields\Tests\Notifier\TestNotifier;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Initialize Custom Fields and find configurations.
 */
class CustomFields {

  protected $cache;
  protected $notifier;
  protected $definitions = [];

  /**
   * Set some defaults.
   *
   * Do not directly use constructor.  Initalize objects through static call to
   * initialize(). Future versions may re-write the constructor such as by using
   * a configuration file to set cache and notifier services.
   */
  protected function __construct($environment) {
    switch ($environment) {
      case 'testing':
        $this->cache = new WPOptionsCache();
        $this->notifier = new TestNotifier();
        break;

      case 'prod':
      default:
        $this->cache = new WPOptionsCache();
        $this->notifier = new WPNotifier();
        break;
    }
  }

  /**
   * Get cache object.
   *
   * @return CustomFields\Cache\CacheInterface
   *   Cache object.
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * Get notification object.
   *
   * @return CustomFields\Notifier\NotifierInterface
   *   Admin notification object.
   */
  public function getNotifier() {
    return $this->notifier;
  }

  /**
   * Get definitions.
   *
   * @return array
   *   PHP definitions of custom types, fields, etc.
   */
  public function getDefinitions() {
    return $this->definitions;
  }

  /**
   * Factory to initialize plugin.
   *
   * Locate defitions for custom types, boxes, and fields. Find built
   * definitions. Build others from YAML.
   *
   * @param string $definitionsPath
   *   Path to directory of definitions.
   * @param string $environment
   *   Allows other components to use production, dev, and testing configs.
   *
   * @return null|static
   *   On failure, return NULL.  Otherwise, static.
   */
  public static function initialize(string $definitionsPath, $environment = 'prod') {
    // @TODO Add option to cache all types.  If found, return it here.
    // @TODO Create admin page to manage cached definitions.
    $cf = new static($environment);
    try {
      $definitionDirs = $cf->findDefinitions($definitionsPath);
      $definitionHashes = array_map(['CustomFields\\CustomFieldsUtilities', 'hashDirectory'], $definitionDirs);
    }
    catch (ExceptionInterface $e) {
      $cf->notifier->queueAdminNotice('' . $e);
      return NULL;
    }
    $cf->definitions = $cf->collectDefinitions($definitionHashes, $definitionDirs);
    return $cf;
  }

  /**
   * Find YAML definitions.
   *
   * @param string $definitionsPath
   *   Path to directory of definitions.
   *
   * @return array
   *   Array of definition_name => definition_directory_path.
   *
   * @throws \CustomFields\Exception\NoDefinitionsException
   *   Thrown if no definitions are defined.
   */
  protected function findDefinitions(string $definitionsPath) {
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
        $defPath = $defDir . '/' . $defName . '.yml';
        if (file_exists($defPath)) {
          $definitions[$defName] = $defDir;
        }
      }
    };
    if (empty($definitions)) {
      throw new NoDefinitionsException();
    }
    return $definitions;
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
      $definition = Yaml::parse(file_get_contents($path . '/' . $type . '.yml'), Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
      // @TODO parse other parts of definition.
    }
    catch (ParseException $e) {
      $notice = sprintf("The CustomFields definition for “%s” could not be " .
       "parsed. It will be ignored, which may cause other errors. The parser " .
       "returned:<br /><pre>%s</pre>", $type, $e);
      $this->getNotifier()->queueAdminNotice($notice);
      return NULL;
    }
    return $definition;
  }

}
