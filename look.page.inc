<?php

/**
 * @file
 * Contains look.page.inc.
 *
 * Page callback for Look entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Look templates.
 *
 * Default template: look.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_look(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
