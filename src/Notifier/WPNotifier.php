<?php

namespace CustomFields\Notifier;

/**
 * Notices using Wordpress API.
 */
class WPNotifier implements NotifierInterface {

  /**
   * Array keyed by transient key. Children are arrays of user message strings.
   *
   * @var array
   */
  protected $userWarnings = [];

  /**
   * Array keyed by transient key. Children are arrays of fields to flag.
   *
   * @var array
   */
  protected $fieldWarnings = [];

  /**
   * Default transient key for warnings.
   *
   * @var string
   */
  protected $transientKey = '';

  /**
   * Store transient values on destruction.
   */
  public function __destruct() {
    if (count($this->userWarnings) || count($this->fieldWarnings)) {
      $transientKeys = array_unique(array_keys($this->userWarnings) + array_keys($this->fieldWarnings));
      foreach ($transientKeys as $transientKey) {
        $transientValue = [
          'messages' => $this->userWarnings[$transientKey] ?: [],
          'elements' => $this->fieldWarnings[$transientKey] ?: [],
        ];
        set_transient($transientKey, $transientValue, 30);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setTranientKey(string $key) {
    $this->transientKey = $key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function queueAdminNotice(string $message) {
    add_action('admin_notices', function () use ($message) {
      $message = __($message);
      echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    });
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function queueUserWarning(string $message, string $transientKey = '') {
    if ($transientKey == '') {
      $transientKey = $this->transientKey;
    }
    $this->userWarnings[$transientKey][] = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function queueFieldWarning(string $field, string $transientKey = '') {
    if ($transientKey == '') {
      $transientKey = $this->transientKey;
    }
    $this->fieldWarnings[$transientKey][] = $field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveWarnings(string $transientKey = '') {
    if ($transientKey == '') {
      $transientKey = $this->transientKey;
    }
    $warnings = get_transient($transientKey);
    // delete_transient($transientKey);
    return $warnings;
  }

}
