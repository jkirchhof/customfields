<?php

namespace CustomFields\Notice;

/**
 * Notices using Wordpress API.
 */
class WPNotice implements NoticeInterface {

  /**
   * {@inheritdoc}
   */
  public function queueAdminNotice(string $message) {
    add_action('admin_notices', function () use ($message) {
      $message = __($message);
      echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    });
  }

}
