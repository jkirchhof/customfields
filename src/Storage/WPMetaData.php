<?php

namespace CustomFields\Storage;

/**
 * Store/get custom fields data using Wordpress' "metadata" functions.
 */
class WPMetaData implements StorageInterface {

  /**
   * {@inheritdoc}
   */
  public function persist(int $postId, string $key, $value) {
    // Return value of update_post_meta is ignored. FALSE is returned on both
    // failure and when previous value is same as new value, with no distinction
    // for the apparent error.
    update_post_meta($postId, $key, $value);
    $storedValue = $this->retrieve($postId, $key);
    if (is_scalar($value)) {
      return (string) $value == (string) $storedValue;
    }
    else {
      return $value == $storedValue;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieve(int $postId, string $key) {
    return get_post_meta($postId, $key, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveAll(int $postId) {
    $data = get_post_meta($postId);
    array_walk($data, function (&$val) {
      $val = maybe_unserialize($val[0]);
    });
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(int $postId, string $key) {
    return delete_post_meta($postId, $key);
  }

}
