<?php

/**
 * @file
 * Callbacks for custom post type "withshortcode".
 */

/**
 * Sample shortcode callback.
 *
 * @param array|string $attributes
 *   Values passed to shortcode as array. Otherwise, empty string (because
 *   Wordpress is Wordpress).
 * @param string|null $content
 *   Content wrapped by shortcode. NULL if there is none.
 * @param string $shortcode
 *   Shortcode being processed.
 *
 * @return string
 *   HTML to output in place of shortcode.
 */
function cf__withshortcodes__shortcode($attributes, $content, string $shortcode) {
  return "Shortcode processed successfully.";
}
