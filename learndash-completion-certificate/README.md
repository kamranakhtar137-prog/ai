# LearnDash Completion Certificate

German **Teilnahmezertifikat** for LearnDash LMS: custom shortcodes, certificate HTML template, and a print-ready background image workflow.

## Contents

| Path | Purpose |
|------|---------|
| `learndash-completion-certificate.php` | Plugin bootstrap |
| `includes/class-shortcodes.php` | `[ldcc_lesson_count]` and `[ldcc_course_topics]` |
| `includes/class-course-context.php` | Resolves course/user context during PDF generation |
| `templates/certificate-content.html` | HTML to paste into the LearnDash certificate editor |
| `assets/certificate.jpg` | Background image for the Featured Image (A4 portrait) |
| `scripts/convert-certificate-image.py` | Converts `Certificate.png` → JPG for LearnDash |

## Installation

1. Copy the `learndash-completion-certificate` folder into `wp-content/plugins/`.
2. Activate **LearnDash Completion Certificate** in WordPress (LearnDash LMS must already be active).
3. Convert and upload the background image (see below).
4. Create or edit the LearnDash certificate and paste the HTML template.
5. Assign the certificate to each course under **Course Settings → Certificate**.

## Background Image

LearnDash certificate backgrounds must be **JPG** (PNG is not supported).

### Convert `Certificate.png`

1. Place the source file at `assets/source/Certificate.png`.
2. Run:

```bash
python3 scripts/convert-certificate-image.py
```

The script produces `assets/certificate.jpg` with:

- **Dimensions:** 2550 × 3300 px (A4 portrait @ 300 dpi)
- **Max size:** 1 MB (quality is reduced automatically if needed)
- **Format:** JPEG

If the PNG is not available yet, a placeholder split layout (B&amp;W left, black right) can be generated for testing:

```bash
python3 scripts/convert-certificate-image.py --placeholder
```

3. In WordPress, upload `assets/certificate.jpg` to the Media Library.
4. Set it as the **Featured Image** on the certificate post.

## LearnDash Certificate Settings

| Setting | Value |
|---------|-------|
| PDF Page Size | A4 |
| PDF Orientation | Portrait |
| Featured Image | `certificate.jpg` |

Certificate content: copy everything from `templates/certificate-content.html` into the certificate post editor (Text/HTML mode).

## Shortcodes

### Built-in LearnDash shortcodes

| Field | Shortcode |
|-------|-----------|
| Vorname | `[usermeta field="first_name"]` |
| Nachname | `[usermeta field="last_name"]` |
| Kursname | `[courseinfo show="course_title"]` |
| Abschlussdatum | `[courseinfo show="completed_on" format="d.m.Y"]` |

### Custom shortcodes (this plugin)

| Field | Shortcode | Notes |
|-------|-----------|-------|
| Anzahl Lektionen | `[ldcc_lesson_count]` | Counts `sfwd-lessons` only |
| Themen | `[ldcc_course_topics]` | Lists LearnDash topics (`sfwd-topic`) |

#### `[ldcc_lesson_count]`

```
[ldcc_lesson_count]
[ldcc_lesson_count course_id="123"]
```

#### `[ldcc_course_topics]`

```
[ldcc_course_topics]
[ldcc_course_topics source="topics" limit="10"]
[ldcc_course_topics source="lessons"]
[ldcc_course_topics source="custom"]
```

| Attribute | Default | Description |
|-----------|---------|-------------|
| `source` | `topics` | `topics` = LearnDash topics, `lessons` = lesson titles, `custom` = per-course manual list |
| `limit` | `0` | Max items (`0` = no limit) |
| `prefix` | `– ` | Bullet prefix for each line |
| `course_id` | auto | Override course ID (normally inferred from certificate context) |

### Hardcoded topics per course

When topics should be fixed per course (not pulled from LearnDash steps), add post meta on the **course**:

- **Meta key:** `ldcc_certificate_topics`
- **Value:** one topic per line, for example:

```
Einführung in das Thema
Praxisbeispiele
Abschluss und nächste Schritte
```

Then use in the certificate:

```
[ldcc_course_topics source="custom"]
```

This can be set via WP-CLI, ACF, or any custom-fields plugin.

## Typography

The template uses **Verdana Pro** with fallbacks:

```css
font-family: 'Verdana Pro', Verdana, Geneva, sans-serif;
```

For exact Verdana Pro rendering in PDFs, embed the licensed font in TCPDF/LearnDash if your license allows it. Otherwise Verdana is used as the fallback.

## Layout

The background image is a B&amp;W photo on the left with a black panel on the right. Certificate text is placed in the right column (~42% width) with white typography so it sits on the dark area.

Adjust column widths in `templates/certificate-content.html` if your final artwork uses a different split.

## Automatic issuance

1. Edit the course → **Settings** → enable **Certificate**.
2. Select the certificate post created above.
3. When a user completes the course, LearnDash issues the certificate automatically.

## Troubleshooting (`sfwd-certificates`)

Certificates only work when LearnDash can detect the **course** and **user** during PDF generation. If shortcodes show blank values:

1. **Assign the certificate to a course** under Course Settings → Certificate. `[courseinfo]` and the custom shortcodes only work for course-linked certificates.
2. **Use the HTML template in Text/Code mode**, not the block editor. LearnDash disables the visual editor for `sfwd-certificates`; blocks can break shortcodes.
3. **Paste the updated HTML** from `templates/certificate-content.html` (single quotes inside shortcodes, no HTML comments).
4. **Set a preview course** on the certificate post (sidebar meta box **Certificate Preview Course**) if testing from the admin screen.
5. **Download the certificate from the completed course page**, not by opening the bare certificate permalink. The download URL must include `user_id` and ideally `course_id` (the plugin adds `course_id` automatically).
6. **Featured image must be JPG** and set on the certificate post. PNG backgrounds are not supported by LearnDash.
7. **PDF settings** on the certificate post: A4, Portrait.

### Test URL format

A working download URL looks like:

```
https://yoursite.com/certificates/teilnahmezertifikat/?course_id=123&user_id=45&ld_certificate=67
```

Replace IDs with your course, user, and certificate post IDs.

## WP-CLI example (custom topics)

```bash
wp post meta update 123 ldcc_certificate_topics "Thema 1
Thema 2
Thema 3" --path=/path/to/wordpress
```

Replace `123` with the course post ID.
