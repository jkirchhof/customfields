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
   *
   * @throws \CustomFields\Exception\NoDefinitionsException
   *   Warning thrown if no definiitions are defined.
   */
  public static function loadDefinitions(string $definitionsPath) {
    try {
      throw new NoDefinitionsException();
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
