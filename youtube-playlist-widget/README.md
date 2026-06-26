# YouTube Playlist Widget

Reusable WordPress CMS module for adding a configurable YouTube playlist card to any page. The primary editor integration is a Beaver Builder widget/module named **YouTube Playlist Widget**, with Gutenberg block and shortcode fallbacks.

## What editors can configure

- Title
- Description/text
- YouTube playlist URL or playlist ID
- Thumbnail/image from the Media Library or an external image URL
- Thumbnail loading mode (`auto`, `lazy`, or `eager`)
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

## Performance notes

- Prefer a Media Library thumbnail over an external image URL. WordPress can then output responsive `srcset`/`sizes` image markup and apply its image loading heuristics automatically.
- Leave thumbnail loading set to `auto` for most placements.
- Set thumbnail loading to `eager` only when the widget image is visible in the first viewport and is likely to be the Largest Contentful Paint (LCP) image on mobile. This adds high fetch priority for that thumbnail.
- Keep below-the-fold widgets on `auto` or `lazy` so they do not compete with the hero image.

## Design notes

- The thumbnail remains the primary visual element of the widget.
- The default layout uses a desktop split card and collapses to a mobile stacked card.
- The play button is larger and closer to the YouTube logo style:
  - white filled circle
  - transparent play triangle cut out of the circle
  - centered over the thumbnail with a prominent shadow

## Installation

1. Copy the `youtube-playlist-widget` directory into `wp-content/plugins/`.
2. Make sure Beaver Builder is installed and active.
3. Activate **YouTube Playlist Widget** in WordPress admin.
4. Edit any page with Beaver Builder.
5. Open the Beaver Builder module panel, search for **YouTube Playlist Widget**, and add it from the Media category.
6. Configure the content, playlist, image, colors, and fonts in the module settings.

## Beaver Builder editor workflow

1. Editor opens a page, for example the Student Exchange page.
2. Editor launches Beaver Builder.
3. Editor searches for or drags **YouTube Playlist Widget** into the layout.
4. Editor configures:
   - title
   - description/text
   - YouTube playlist URL or playlist ID
   - Media Library thumbnail or external thumbnail URL
   - Version 6 split layout or stacked layout
   - colors
   - title/body font presets and custom font-family values
5. Editor publishes the page.

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
thumbnail_loading="auto"
background_color="#f8f3ec"
content_color="#ffffff"
title_color="#1b1b1b"
text_color="#3d3d3d"
accent_color="#ff0000"
play_button_color="#ffffff"
title_font_family='"Baloo 2", Arial, sans-serif'
body_font_family='Arial, Helvetica, sans-serif'
title_font_size="clamp(2rem, 5vw, 4.5rem)"
description_font_size="clamp(1rem, 2vw, 1.25rem)"
button_text="Playlist ansehen"
layout="split"
open_in_new_tab="true"
```

## Test page

Use `templates/test-page.html` as starter content for a WordPress test page.
