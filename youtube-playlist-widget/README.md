# YouTube Playlist Widget

Reusable WordPress CMS module for adding a configurable YouTube playlist card to any page. It provides a Beaver Builder custom module and a classic WordPress widget named **YouTube Playlist Widget**, with Gutenberg block and shortcode fallbacks.

## What editors can configure

- Title
- Description/text
- Right-side heading and text
- Video row dates and titles
- YouTube playlist URL or playlist ID
- Thumbnail/image from the Media Library or an external image URL
- Layout (`Version 6 split layout` or stacked)
- Colors:
  - outer background
  - content background
  - title
  - description
  - CTA/accent
  - play circle
- Typography:
  - title font preset
  - title font-family CSS value
  - title font size
  - body font preset
  - body font-family CSS value
  - description font size

The title font can be switched from a Shadow-style preset to Baloo, or replaced with any project font-family stack.

## Design notes

- The design follows the provided reference structure:
  - orange playlist card on the left
  - handwritten white playlist title
  - description text next to the thumbnail
  - two video rows with dates, titles, dividers, and white circular play buttons
  - "Alle Videos" CTA button
  - handwritten coral heading and body text on the right
- The thumbnail remains a core part of the widget.

## Installation

1. Copy the `youtube-playlist-widget` directory into `wp-content/plugins/`.
2. Make sure Beaver Builder is installed and active.
3. Activate **YouTube Playlist Widget** in WordPress admin.
4. Edit any page with Beaver Builder.
5. Open the Beaver Builder module panel and search for **YouTube Playlist Widget**.
   - Preferred: add it from the **YouTube** category.
   - Fallback: add it from Beaver Builder's **WordPress Widgets** group.
6. Configure the content, playlist, image, colors, and fonts in the module settings.

## Beaver Builder editor workflow

1. Editor opens a page, for example the Student Exchange page.
2. Editor launches Beaver Builder.
3. Editor searches for or drags **YouTube Playlist Widget** into the layout from the YouTube category or WordPress Widgets group.
4. Editor configures:
   - playlist card title
   - description/text
   - right heading and right text
   - video row dates and titles
   - YouTube playlist URL or playlist ID
   - Media Library thumbnail or external thumbnail URL
   - Version 6 split layout or stacked layout
   - colors
   - title/body font presets and custom font-family values
5. Editor publishes the page.

## If the widget does not appear in Beaver Builder search

1. Confirm **Beaver Builder** is active.
2. Confirm **YouTube Playlist Widget** is active under WordPress plugins.
3. Check Beaver Builder module settings and confirm third-party/custom modules are not disabled.
4. Clear Beaver Builder cache from WordPress admin if available.
5. Refresh/reopen the Beaver Builder editor and search for `YouTube` or `Playlist`.

## Full Beaver Builder fallback via WordPress Widgets

If your Beaver Builder setup blocks third-party custom modules, use Beaver Builder's built-in **WordPress Widgets** group instead. The plugin registers a classic WordPress widget named **YouTube Playlist Widget** with the full configuration form:

- Title
- Description/Text
- Right Heading
- Right Text
- Video 1 Date / Title
- Video 2 Date / Title
- YouTube Playlist URL
- YouTube Playlist ID
- Thumbnail/Image URL
- Colors
- Fonts
- Layout

This is the best fallback because editors still get real form fields instead of editing shortcode text.

## Shortcode emergency fallback

If both custom modules and WordPress Widgets are unavailable in Beaver Builder, use Beaver Builder's built-in **Shortcode** module or **HTML** module and paste this shortcode:

```text
[youtube_playlist_widget title="YouTube Playlist" description="Hier findest Du unsere YouTube-Playlist abcdfeghijklmn" playlist_id="PLxxxxxxxxxxxx" thumbnail_url="https://example.com/playlist.jpg"]
```

This uses the same renderer and design, but it is less editor-friendly than the Beaver module or WordPress widget.

## Gutenberg fallback

If a site also uses the block editor, the plugin registers a **YouTube Playlist Widget** block in the Media category using the same renderer and styling as the Beaver Builder module.

## Shortcode fallback

The same renderer is available as a shortcode:

```text
[youtube_playlist_widget title="YouTube Playlist" description="Hier findest Du unsere YouTube-Playlist abcdfeghijklmn" playlist_id="PLxxxxxxxxxxxx" thumbnail_url="https://example.com/playlist.jpg"]
```

Additional shortcode attributes:

```text
playlist_url=""
content_title="Hier ist eine Überschrift"
content_text="Hast Du schon mal vom American Dream gehört? ..."
video_one_date="3. März 2025"
video_one_title="Schulalltag in Schweden | Experiment Vlog"
video_two_date="18. Feb. 2025"
video_two_title="Ein Wochenende in Stockholm | Experiment Vlog"
background_color="#ff7f66"
content_color="#ffffff"
title_color="#ffffff"
text_color="#ffffff"
accent_color="#ff6f61"
play_button_color="#ffffff"
title_font_family='"Shadows Into Light", cursive'
body_font_family='Arial, Helvetica, sans-serif'
title_font_size="clamp(1.85rem, 3vw, 2.45rem)"
description_font_size="clamp(1rem, 2vw, 1.25rem)"
button_text="Alle Videos"
layout="split"
open_in_new_tab="true"
```

## Test page

Use `templates/test-page.html` as starter content for a WordPress test page.
