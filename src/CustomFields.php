<?php

namespace CustomFields;

use Symfony\Component\Yaml\Yaml;

/**
 * Init plugin.
 */
class CustomFields {

  /**
   * Locate defitions for custom types, boxes, and fields.
   *
   * Find built definitions. Build others from YAML.
   */
  public static function buildDefinitions() {
    // Find custom post types
    // Hash each
    // Read matching hashes from DB
    // Build others and cache in DB, using hash as key.
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
