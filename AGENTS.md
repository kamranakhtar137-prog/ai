# AGENTS.md

## Cursor Cloud specific instructions

This repository is a single **WordPress plugin**: `youtube-playlist-widget/` (pure PHP with CSS/JS assets). It renders a configurable YouTube playlist card via a Beaver Builder module, a Gutenberg block, and a `[youtube_playlist_widget]` shortcode. There is no build step, no PHP dependency manager, and no automated test suite in the repo.

### Local WordPress dev environment

A WordPress dev site lives **outside the repo** at `~/wp` and uses the official **SQLite** integration (`wp-content/db.php` drop-in), so **no MySQL/MariaDB server needs to be started**. The plugin is symlinked into it at `~/wp/wp-content/plugins/youtube-playlist-widget` (the startup update script recreates this symlink), so edits under `/workspace/youtube-playlist-widget` are reflected live — no reinstall needed.

- WordPress core: `~/wp` — admin user `admin` / password `admin123`, site URL `http://localhost:8080`.
- `wp-cli` is installed globally; always pass `--allow-root` (it runs as root in the VM). Example: `wp plugin list --path=~/wp --allow-root`.
- If the plugin ever shows inactive: `wp plugin activate youtube-playlist-widget --path=~/wp --allow-root`.

### Run

Start the dev server (this is NOT in the update script — start it per session):

```
wp server --host=0.0.0.0 --port=8080 --path=$HOME/wp --allow-root
```

Then browse `http://localhost:8080/`. A demo page rendering the widget was created at `http://localhost:8080/?page_id=5` ("YouTube Playlist Widget Test"). Admin/editor is at `http://localhost:8080/wp-admin/`.

### Lint

No PHPCS ruleset is committed (code contains `phpcs:ignore` hints but ships no config). Use PHP syntax linting:

```
find youtube-playlist-widget -name '*.php' -print0 | xargs -0 -n1 php -l
```

The editor JS can be syntax-checked with `node --check youtube-playlist-widget/assets/js/block-editor.js`.

### Test / Build

No automated tests and no build pipeline exist in the repo. Validate changes manually by rendering the widget (shortcode/block/Beaver Builder) in the local WordPress site above.
