/**
 * @file
 * Look block settings.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.lookBlockSettings = {
    attach: function () {

      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('[data-drupal-selector="edit-visibility-look"]').drupalSetSummary(function (context) {
        var $checkboxes = $(context).find('input[type="checkbox"]:checked');
        if (!$checkboxes.length) {
          return Drupal.t('Not restricted');
        }
        else {
          return Drupal.t('Restricted to certain looks');
        }
      });
    }
  };

})(jQuery, Drupal);
