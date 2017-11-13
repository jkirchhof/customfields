<?php

namespace CustomFields\Tests\Notifier;

use CustomFields\Notifier\WPNotifier;

/**
 * Test calls to Wordpress API.
 */
class WPNotifierTest extends \WP_UnitTestCase {

  /**
   * Test printAdminNotice.
   */
  public function testQueueAdminNotice() {
    $adminNotifier = new WPNotifier();
    $this->expectOutputString('<div class="notice notice-error"><p>foo bar</p></div>');
    $adminNotifier->queueAdminNotice('foo bar');
    do_action('admin_notices');
  }

}
