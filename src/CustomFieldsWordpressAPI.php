<?php

namespace CustomFields;

/**
 * Calls to Wordpress API.
 */
class CustomFieldsWordpressAPI {

  /**
   * Show notice on admin pages.
   *
   * @param string $message
   *   Text to display in notice.
   */
  public static function printAdminNotice(string $message) {
    add_action('admin_notices', function () use ($message) {
      $message = esc_html(__($message));
      echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    });
  }

}
