<?php

namespace CustomFields\Notifier;

/**
 * Interface for user notices displayed by CustomFields.
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
