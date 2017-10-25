<?php

namespace CustomFields\Tests;

use CustomFields\CustomFieldsWordpressAPI;

/**
 * Test calls to Wordpress API.
 */
class CustomFieldsWordpressAPITest extends \WP_UnitTestCase {

  /**
   * Test printAdminNotice.
   */
  public function testPrintAdminNotice() {
    $this->expectOutputString('<div class="notice notice-error"><p>foo bar</p></div>');
    CustomFieldsWordpressAPI::printAdminNotice('foo bar');
    do_action('admin_notices');
  }

}
