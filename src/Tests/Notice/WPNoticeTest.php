<?php

namespace CustomFields\Tests\Notice;

use CustomFields\Notice\WPNotice;

/**
 * Test calls to Wordpress API.
 */
class WPNoticeTest extends \WP_UnitTestCase {

  /**
   * Test printAdminNotice.
   */
  public function testQueueAdminNotice() {
    $adminNotifier = new WPNotice();
    $this->expectOutputString('<div class="notice notice-error"><p>foo bar</p></div>');
    $adminNotifier->queueAdminNotice('foo bar');
    do_action('admin_notices');
  }

}
