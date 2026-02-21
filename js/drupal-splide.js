(function (Drupal, once, drupalSettings) {
  'use strict';

  /**
   * Initialize a Splide instance for a single element.
   *
   * @param   {HTMLElement} element
   *          The root carousel element.
   * 
   * @param   {Object} config
   *          The carousel config (expects a `options` object).
   */
  function initCarousel(element, config) {
    if (!window.Splide) {
      return;
    }

    var options = (config && config.options) ? config.options : {};
    var splide = new window.Splide(element, options);
    splide.mount();
  }

  Drupal.behaviors.drupalSplide = {
    attach: function (context) {
      // Read all registered carousels from drupalSettings.
      var settings = drupalSettings && drupalSettings.drupalSplide && drupalSettings.drupalSplide.carousels
        ? drupalSettings.drupalSplide.carousels
        : {};

      Object.keys(settings).forEach(function (id) {
        var selector = settings[id].selector || '';
        if (!selector) {
          return;
        }

        // Ensure we only initialize each carousel once per page.
        once('drupal-splide-' + id, selector, context).forEach(function (element) {
          initCarousel(element, settings[id]);
        });
      });
    }
  };
})(Drupal, once, drupalSettings);
