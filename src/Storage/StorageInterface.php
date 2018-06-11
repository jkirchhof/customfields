<?php

namespace CustomFields\Storage;

/**
 * Interface for field storage used by CustomFields.
 */
interface StorageInterface {

  /**
   * For a specific post ID, persist a key-value pair to storage.
   *
   * @param int $postId
   *   ID of post related to key-value pair.
   * @param string $key
   *   Key of stored value.
   * @param mixed $value
   *   Value to store. Scalar and boolean values may be converted to strings.
   *   Other values may be serialized. Values may also be escaped.
   *
   * @return bool
   *   TRUE on success; FALSE on failure.
   */
  public function persist(int $postId, string $key, $value);

  /**
   * For a specific post ID and key, get its value from storage.
   *
   * @param int $postId
   *   ID of post related to key-value pair.
   * @param string $key
   *   Key of stored value.
   *
   * @return mixed
   *   Stored value, possibly unserialized and/or unescaped.
   */
  public function retrieve(int $postId, string $key);

  /**
   * For a specific post ID, get all key-value pairs from storage.
   *
   * @param int $postId
   *   ID of post related to key-value pair.
   *
   * @return array
   *   Keyed array of stored values, possibly unserialized and/or unescaped.
   */
  public function retrieveAll(int $postId);

  /**
   * For a specific post ID and key, delete its value from storage.
   *
   * @param int $postId
   *   ID of post related to key-value pair.
   * @param string $key
   *   Key of stored value.
   *
   * @return bool
   *   TRUE on success; FALSE on failure.
   */
  public function delete(int $postId, string $key);

}
