<?php

namespace CustomFields\Tests\Notice;

use CustomFields\Notice\WPAdminNotice;

/**
 * Test calls to Wordpress API.
 */
class WPAdminNoticeTest extends \WP_UnitTestCase {

  /**
   * Test printAdminNotice.
   */
  public function testQueueAdminNotice() {
    $adminNotifier = new WPAdminNotice();
    $this->expectOutputString('<div class="notice notice-error"><p>foo bar</p></div>');
    $adminNotifier->queueAdminNotice('foo bar');
    do_action('admin_notices');
  }

}
