# Woodrich Gallery Child Theme

WordPress child theme functionality for customer before/after gallery submissions.

## Features

- Registers a `gallery_item` custom post type for submissions.
- Adds `[gallery_submission_form]` for front-end customer submissions.
- Uploads before/after images and videos to Amazon S3.
- Saves submission data to ACF fields when ACF is available, with post-meta fallback.
- Adds admin list previews, thumbnail selection helpers, CSV export, and email settings.
- Adds `[custom_gallery]` with search and AJAX "Load More" behavior.

## Installation

1. Upload this directory as a child theme under `wp-content/themes/`.
2. Confirm the `Template:` value in `style.css` matches the installed Thrive Theme Builder parent theme directory. It is currently set to `thrive-theme`.
3. Install PHP dependencies from this child theme directory:

   ```bash
   composer install --no-dev
   ```

4. Activate the child theme in WordPress.
5. Visit **Settings > Permalinks** once, or reactivate the child theme, to flush rewrite rules.

## AWS S3 configuration

Do not place AWS credentials in `functions.php`. Define these values in `wp-config.php`, server environment variables, or your hosting secret manager:

```php
define('WRUGC_AWS_ACCESS_KEY_ID', 'your-access-key-id');
define('WRUGC_AWS_SECRET_ACCESS_KEY', 'your-secret-access-key');
define('WRUGC_AWS_REGION', 'eu-north-1');
define('WRUGC_AWS_BUCKET', 'your-bucket-name');
```

The uploaded objects are stored under the `gallery-submissions/` prefix. The gallery expects uploaded objects to be publicly viewable or otherwise accessible from the browser.

## Optional email setting

Email notifications are enabled by default. To disable them globally:

```php
define('EMAIL_NOTIFICATIONS_ENABLED', false);
```

Configure recipient addresses and templates in **Settings > Email Settings**.

## Shortcodes

Submission form:

```text
[gallery_submission_form]
```

Gallery:

```text
[custom_gallery images_per_page="12" exclude_videos="false"]
```

## ACF field names

The theme reads/writes these field names:

- `ugc_name`
- `ugc_email`
- `ugc_phone`
- `ugc_project_type`
- `ugc_project_type_other`
- `ugc_project_description`
- `ugc_wood_type`
- `ugc_wood_type_other`
- `ugc_products_used`
- `ugc_colors_used`
- `ugc_terms`
- `ugc_media_urls`
- `ugc_before_media_urls`
- `ugc_after_media_urls`
- `ugc_thumbnail_url`

For media repeaters, each row should include a `file_url` subfield.
