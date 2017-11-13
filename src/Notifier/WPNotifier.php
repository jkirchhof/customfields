<?php

namespace CustomFields\Notifier;

/**
 * Notices using Wordpress API.
 */
class WPNotifier implements NotifierInterface {

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
