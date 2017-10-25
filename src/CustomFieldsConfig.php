<?php

namespace CustomFields;

use Symfony\Component\Yaml\Yaml;
use CustomFields\Exception\NoDefinitionsException;
use CustomFields\Exception\ExceptionInterface;

/**
 * Method for managing configurations.
 */
class CustomFieldsConfig {

  /**
   * Locate defitions for custom types, boxes, and fields.
   *
   * Find built definitions. Build others from YAML.
   *
   * @param string $definitionsPath
   *   Path to directory of definitions.
   */
  public static function loadDefinitions(string $definitionsPath) {
    try {
      $definitionHashes = static::findDefinitions($definitionsPath);
    }
    catch (ExceptionInterface $e) {
      CustomFieldsWordpressAPI::printAdminNotice('' . $e);
      return;
    }
    // Find custom post types
    // Hash each
    // Read matching hashes from DB
    // Build others and cache in DB, using hash as key.
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
   *   Warning thrown if no definiitions are defined.
   */
  public static function findDefinitions(string $definitionsPath) {
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
   * Build PHP from YAML config file for custom type/boxes/fields.
   *
   * @param string $path
   *   Path to YAML file.
   *
   * @return array
   *   PHP of data for definition(s).
   */
  protected function parseDefinition(string $path = '') {
    $definition = Yaml::parse($path);
    return $definition;
  }

}
