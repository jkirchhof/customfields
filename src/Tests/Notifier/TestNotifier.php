<?php

namespace CustomFields\Tests\Notifier;

use CustomFields\Notifier\NotifierInterface;
use CustomFields\Tests\Exception\TestException;

/**
 * Notices using Wordpress API.
 */
class TestNotifier implements NotifierInterface {

  /**
   * {@inheritdoc}
   */
  public function queueAdminNotice(string $message) {
    throw new TestException($message);
  }

}
