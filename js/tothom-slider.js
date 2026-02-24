(function (Drupal, once, drupalSettings) {
  'use strict';

  /**
   * Initialize a Splide instance for a single element.
   *
   * @param   {HTMLElement} element
   *          The root slider element.
   * 
   * @param   {Object} config
   *          The slider config (expects a `options` object).
   */
  function initCarousel(element, config) {
    if (!window.Splide) {
      return;
    }
    if (!element.querySelector('.splide__track') || !element.querySelector('.splide__list')) {
      return;
    }

    var options = (config && config.options) ? config.options : {};
    var splide = new window.Splide(element, options);
    splide.mount();
  }

  Drupal.behaviors.tothomSlider = {
    attach: function (context) {
      // Read all registered sliders from drupalSettings.
      var settings = drupalSettings && drupalSettings.tothomSlider && drupalSettings.tothomSlider.sliders
        ? drupalSettings.tothomSlider.sliders
        : {};

      Object.keys(settings).forEach(function (id) {
        var selector = settings[id].selector || '';
        if (!selector) {
          return;
        }

        // Ensure we only initialize each slider once per page.
        once('tothom-slider-' + id, selector, context).forEach(function (element) {
          initCarousel(element, settings[id]);
        });
      });
    }
  };
})(Drupal, once, drupalSettings);
