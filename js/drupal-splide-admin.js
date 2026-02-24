(function (Drupal, once) {
  'use strict';

  /**
   * Synchronize the Role field with the selected carousel semantics.
   *
   * - Decorative carousel: force role to "group" and require it.
   * - Content carousel: clear role and make it optional.
   *
   * @param {HTMLInputElement|null} roleInput
   *   The role textfield input.
   * @param {HTMLInputElement|null} semanticsInput
   *   The selected semantics radio input.
   */
  function syncRoleField(roleInput, semanticsInput) {
    if (!roleInput || !semanticsInput) {
      return;
    }
    if (semanticsInput.value === 'decorative') {
      roleInput.value = 'group';
      roleInput.required = true;
    }
    else {
      roleInput.required = false;
      roleInput.value = '';
    }
  }

  Drupal.behaviors.drupalSplideAdmin = {
    attach: function (context) {
      // Attach once per form to avoid duplicate listeners.
      once('drupal-splide-admin', 'form', context).forEach(function (form) {
        var semanticsInput = form.querySelector('input[name="content[semantics]"]:checked') || form.querySelector('input[name="content[semantics]"]');
        var roleInput = form.querySelector('input[name="options[accessibility][role]"]');
        if (!semanticsInput || !roleInput) {
          return;
        }

        // Initialize the Role field based on current semantics.
        syncRoleField(roleInput, semanticsInput);
        // Keep Role in sync when the semantics radios change.
        form.querySelectorAll('input[name="content[semantics]"]').forEach(function (input) {
          input.addEventListener('change', function () {
            syncRoleField(roleInput, input);
          });
        });
      });
    }
  };
})(Drupal, once);
