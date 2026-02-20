# Drupal Splide

Provides configurable Splide.js carousels as config entities.

## Install

Enable the module:

```
composer require donbuche/drupal_splide

drush en drupal_splide -y
drush cr
```

## Admin UI

Go to:

`/admin/config/content/splide`

From there you can add, edit, and delete carousels.

## Splide library

This module expects Splide assets to be present at:

- `/web/libraries/splidejs/splide/dist/js/splide.min.js`
- `/web/libraries/splidejs/splide/dist/css/splide.min.css`

The module registers a Drupal library named:

`drupal_splide/splide`

Attach it in Twig:

```
{{ attach_library('drupal_splide/splide') }}
```

## Notes

- Carousels are stored as Config Entities, so they can be exported/imported with `drush cex/cim`.
