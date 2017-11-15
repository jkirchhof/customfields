<?php

/**
 * @file
 * Declare plugin to WP, and initialize it.
 */

/*
Plugin Name: Custom Fields
Description: API for custom fields, types, and metaboxes
Author: Joe Kirchhof.
 */

namespace CustomFields;

use CustomFields\Cache\WPOptionsCache;
use CustomFields\Notifier\WPNotifier;

require_once __DIR__ . '/vendor/autoload.php';

$cfs = new CustomFields(new WPOptionsCache(), new WPNotifier());
$cfs->initialize(__DIR__ . '/definitions');
$cfTypes = CustomFieldsType::buildTypes($cfs);
