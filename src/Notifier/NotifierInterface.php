<?php

namespace CustomFields\Notifier;

/**
 * Interface for user notices displayed by CustomFields.
 */
interface NotifierInterface {

  /**
   * Set default transient key.
   *
   * @param string $key
   *   Key of transient.
   *
   * @return static
   */
  public function setTranientKey(string $key);

  /**
   * Show notice on admin pages.
   *
   * @param string $message
   *   Text to display in notice.
   *
   * @return static
   */
  public function queueAdminNotice(string $message);

  /**
   * Add message to display to user.
   *
   * @param string $message
   *   User message to queue.
   * @param string $transientKey
   *   Transient key for saving message. Implementation should default to
   *   $this->transientKey.
   *
   * @return static
   */
  public function queueUserWarning(string $message, string $transientKey);

  /**
   * Add field to list of those to flag.
   *
   * @param string $field
   *   Machine name of field.
   * @param string $transientKey
   *   Transient key for saving message. Implementation should default to
   *   $this->transientKey.
   *
   * @return static
   */
  public function queueFieldWarning(string $field, string $transientKey);

  /**
   * Retrieve warnings from storage; remove value from storage.
   *
   * @param string $transientKey
   *   Key of transient.
   *
   * @return mixed
   *   Warnings.
   */
  public function retrieveWarnings(string $transientKey);

}
