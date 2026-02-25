# TOTHOM Slider

## Table of contents
- [TOTHOM Slider](#tothom-slider)
  - [Table of contents](#table-of-contents)
    - [1. Overview](#1-overview)
    - [2. Requirements](#2-requirements)
    - [3. Install](#3-install)
    - [4. Admin paths](#4-admin-paths)
    - [5. Create a slider](#5-create-a-slider)
    - [6. Content sources](#6-content-sources)
    - [7. Prefix and suffix content](#7-prefix-and-suffix-content)
    - [8. Splide options](#8-splide-options)
    - [9. Breakpoints](#9-breakpoints)
    - [10. Classes](#10-classes)
    - [11. i18n](#11-i18n)
    - [12. Autoplay toggle](#12-autoplay-toggle)
    - [13. Reduced motion](#13-reduced-motion)
    - [14. Views integration (Embed only)](#14-views-integration-embed-only)
    - [15. Blocks and Layout Builder](#15-blocks-and-layout-builder)
    - [16. Caching](#16-caching)
    - [17. Troubleshooting](#17-troubleshooting)
    - [18. Export and import](#18-export-and-import)

### 1. Overview
<a id="overview"></a>
TOTHOM Slider provides accessible, highly configurable sliders/carousels for Drupal, powered by Splide.js. The name "TOTHOM" comes from a Catalan word that means "everyone", a small nod to inclusivity that fits right in with an accessible-first slider. 

You create sliders in the admin UI and place them as blocks. The recommended block is "TOTHOM Slider", which lets you choose a slider from a dropdown and avoids block-definition cache issues.

### 2. Requirements
<a id="requirements"></a>
The Splide.js library files must exist in `web/libraries` and match the paths configured in `tothom_slider.libraries.yml`.

Splide.js is a third-party library and is not distributed with this module. Please refer to the Splide.js project for its license: https://splidejs.com.

### 3. Install
<a id="install"></a>
Step 1: `composer require donbuche/tothom_slider`  
Step 2: `drush en tothom_slider -y`  
Step 3: `drush cr`

### 4. Admin paths
<a id="admin-paths"></a>
Sliders administration: `/admin/content/sliders`

### 5. Create a slider
<a id="create-a-slider"></a>
Step 1: Go to `/admin/content/sliders`.  
Step 2: Click "Add slider".  
Step 3: Fill in content source and options.  
Step 4: Save and place it with the "TOTHOM Slider" block.

### 6. Content sources
<a id="content-sources"></a>
You can build slides from Nodes or from Views.

Nodes: select one or more content types, optionally pick a view mode per type, then add specific nodes.  
Views: select an **Embed** display using the "TOTHOM Slider" style.

### 7. Prefix and suffix content
<a id="prefix-and-suffix-content"></a>
You can add formatted text above (Prefix content) and below (Suffix content) the slider. This is useful for headings, captions, or CTAs.

### 8. Splide options
<a id="splide-options"></a>
Options are grouped into accordions (General, Layout, Navigation, Autoplay, Drag & wheel, Accessibility, Behavior, Reduced motion, Classes, i18n). Each field includes a "Read docs" link to the official Splide options documentation.

### 9. Breakpoints
<a id="breakpoints"></a>
Breakpoints can be edited in two modes:

Simple builder: a friendly table for common options (`perPage`, `perMove`, `gap`, `arrows`, `pagination`). The table is serialized into JSON on save.  
JSON: advanced mode where you can write any breakpoint options. Options added here that are not supported by the simple builder will not appear in the table, but they are still saved and applied.

The "Media query" setting controls how breakpoints are interpreted. Use `min` for mobile‑first, `max` for desktop‑first.

### 10. Classes
<a id="classes"></a>
The Classes table lets you append custom class names to Splide’s defaults. Default classes are always included so core styling continues to work.

### 11. i18n
<a id="i18n"></a>
The i18n table lets you override Splide interface strings. Defaults are in English. Leave fields empty to keep defaults.

### 12. Autoplay toggle
<a id="autoplay-toggle"></a>
If autoplay is enabled, the module renders a play/pause toggle button using Splide’s recommended markup. Labels use your i18n overrides if provided.

### 13. Reduced motion
<a id="reduced-motion"></a>
Reduced motion options apply only when the operating system has "Reduce motion" enabled. They do not affect normal playback.

### 14. Views integration (Embed only)
<a id="views-integration-embed-only"></a>
Step 1: Create a View with an **Embed** display.  
Step 2: Set **Format** to "TOTHOM Slider".  
Step 3: Set **Show** to "Content" or "Fields".  
Step 4: Set pager to "Display all items".  
Step 5: Select this display in the slider form.

Pagination is ignored for the TOTHOM Slider style. The style forces a full result set to be rendered.

### 15. Blocks and Layout Builder
<a id="blocks-and-layout-builder"></a>
Use the block "TOTHOM Slider".  
Step 1: Add the block in Layout Builder.  
Step 2: Open block configuration.  
Step 3: Select a slider from the dropdown.  
Step 4: Save.

### 16. Caching
<a id="caching"></a>
Slider output is cacheable and invalidated when the slider configuration changes. You should not need to clear caches when updating an existing slider.

### 17. Troubleshooting
<a id="troubleshooting"></a>
If the slider does not initialize:

Step 1: Verify Splide assets exist at:  
`/web/libraries/splidejs/splide/dist/js/splide.min.js`  
`/web/libraries/splidejs/splide/dist/css/splide.min.css`

Step 2: If using Views, confirm the display is **Embed** and the style is **TOTHOM Slider**.  
Step 3: Ensure the slider is enabled and the block is placed.  
Step 4: Clear caches if you have recently added the module or changed routes.

### 18. Export and import
<a id="export-and-import"></a>
Sliders are config entities, so they can be exported and imported with `drush cex` and `drush cim`.
