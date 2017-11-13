<?php

namespace CustomFields\Notifier;

/**
 * Exception interface for all exceptions thrown by CustomFields.
 */
interface NotifierInterface {

  /**
   * Show notice on admin pages.
   *
   * @param string $message
   *   Text to display in notice.
   */
  public function queueAdminNotice(string $message);

}
