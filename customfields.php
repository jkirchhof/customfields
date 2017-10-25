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

require_once __DIR__ . '/vendor/autoload.php';

$cfs = CustomFields::loadDefinitions(__DIR__ . 'definitions');
