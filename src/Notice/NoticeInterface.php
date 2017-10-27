<?php

namespace CustomFields\Notice;

/**
 * Exception interface for all exceptions thrown by CustomFields.
 */
interface NoticeInterface {

  /**
   * Show notice on admin pages.
   *
   * @param string $message
   *   Text to display in notice.
   */
  public function queueAdminNotice(string $message);

}
