<?php

namespace CustomFields;

use Symfony\Component\Yaml\Yaml;
use CustomFields\Exception\NoDefinitionsException;
use CustomFields\Exception\HashException;
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
      $definitions = static::findDefinitions($definitionsPath);
      $definitionHashes = array_map(['static', 'hashDirectory'], $definitions);
    }
    catch (ExceptionInterface $e) {
      CustomFieldsWordpressAPI::printAdminNotice('' . $e);
      return;
    }
    // $definitions : type => $path to dir.
    // $definitionHashes : type => hash.
    // ---
    // Check database for each hash.
    // If found, replace hash with db contents.
    // If not found:
    // - 1) process with yaml parser,
    // - 2) cache in db,
    // - 3) replace hash with value.
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
   * Generate a single hash for a directory.
   *
   * Ignores hidden files.  Hashes file contents as well as file and directory
   * names.
   *
   * @param string $path
   *   Path to directory.
   *
   * @return string
   *   Single sha-1 hash.
   */
  public static function hashDirectory(string $path) {
    if (is_dir($path)) {
      $dir = dir($path);
    }
    if (empty($dir)) {
      throw new HashException();
    }
    $hashes = [sha1($path)];
    while (($file = $dir->read()) !== FALSE) {
      if ($file[0] != '.') {
        $fullPath = $path . '/' . $file;
        if (is_dir($fullPath)) {
          $hashes[] = self::hashDirectory($fullPath);
        }
        else {
          // Hash file name and contents so renaming files changes hash.
          $hashes[] = sha1($fullPath);
          $hashes[] = sha1_file($fullPath);
        }
      }
    }
    $hash = sha1(implode('', $hashes));
    return $hash;
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
