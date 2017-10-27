<?php

namespace CustomFields\Notice;

/**
 * Calls to Wordpress API.
 */
class WPAdminNotice implements NoticeInterface {

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
