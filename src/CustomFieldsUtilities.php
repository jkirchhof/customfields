<?php

namespace CustomFields;

use CustomFields\Exception\HashException;

/**
 * Utility method for Custom Fields package.
 */
class CustomFieldsUtilities {

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
   *
   * @throws \CustomFields\Exception\HashException
   *   Thrown if hash cannot be calculated.
   */
  public static function hashDirectory(string $path) {
    if (is_dir($path)) {
      $dir = dir($path);
    }
    $pathParts = explode('/', $path);
    $dirName = end($pathParts);
    if (empty($dir) || empty($dirName)) {
      throw new HashException();
    }
    $hashes = [sha1($dirName)];
    while (($file = $dir->read()) !== FALSE) {
      if ($file[0] != '.') {
        $fullPath = $path . '/' . $file;
        if (is_dir($fullPath)) {
          $hashes[] = static::hashDirectory($fullPath);
        }
        else {
          // Hash file name and contents so renaming files changes hash.
          $hashes[] = sha1($file);
          $hashes[] = sha1_file($fullPath);
        }
      }
    }
    $hash = sha1(implode('', $hashes));
    return $hash;
  }

}