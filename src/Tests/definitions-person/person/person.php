<?php

/**
 * @file
 * Functions for customfields test definition 'person'.
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
function cf__people__shortcode($attributes, $content, string $shortcode) {
  return "The [people] shortcode was processed.";
}
