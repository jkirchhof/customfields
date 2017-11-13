<?php

namespace CustomFields\Tests\Notifier;

use CustomFields\Notifier\WPNotifier;

/**
 * Test calls to Wordpress API.
 */
class WPNotifierTest extends \WP_UnitTestCase {

  /**
   * Test printAdminNotice.
   *
   * N.b. This tests against output of all method attached to WP's action
   * "admin_notices". If a notice is default behavior of the test configuration
   * (such as if no definitions are parsed), extra output fill make the test
   * fail.
   */
  public function testQueueAdminNotice() {
    $adminNotifier = new WPNotifier();
    $this->expectOutputString('<div class="notice notice-error"><p>foo bar</p></div>');
    $adminNotifier->queueAdminNotice('foo bar');
    do_action('admin_notices');
  }

}
