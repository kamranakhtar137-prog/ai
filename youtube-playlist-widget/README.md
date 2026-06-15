# YouTube Playlist Widget

Reusable WordPress CMS module for adding a configurable YouTube playlist card to any page.

## What editors can configure

- Title
- Description/text
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

- The thumbnail remains the primary visual element of the widget.
- The default layout uses a desktop split card and collapses to a mobile stacked card.
- The play button is larger and closer to the YouTube logo style:
  - white filled circle
  - transparent play triangle cut out of the circle
  - centered over the thumbnail with a prominent shadow

## Installation

1. Copy the `youtube-playlist-widget` directory into `wp-content/plugins/`.
2. Activate **YouTube Playlist Widget** in WordPress admin.
3. Edit any page.
4. Add the **YouTube Playlist Widget** block from the Media block category.
5. Configure the content, playlist, image, colors, and fonts in the block sidebar.

## Shortcode fallback

The same renderer is available as a shortcode:

```text
[youtube_playlist_widget title="YouTube Playlist" description="Hier findest Du unsere YouTube-Playlist abcdfeghijklmn" playlist_id="PLxxxxxxxxxxxx" thumbnail_url="https://example.com/playlist.jpg"]
```

Additional shortcode attributes:

```text
playlist_url=""
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
