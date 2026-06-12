<?php
/**
 * Woodrich Gallery child theme functionality.
 *
 * @package WoodrichGalleryChild
 */

defined('ABSPATH') || exit;

const WRUGC_TEXT_DOMAIN = 'woodrich-gallery-child';
const WRUGC_POST_TYPE   = 'gallery_item';

/**
 * Enqueue parent, child, and custom theme styles.
 */
function wrugc_enqueue_theme_styles()
{
	$parent_style_path = get_template_directory() . '/style.css';
	$child_style_path  = get_stylesheet_directory() . '/style.css';
	$custom_style_path = get_stylesheet_directory() . '/custom.css';

	wp_enqueue_style(
		'parent-style',
		get_template_directory_uri() . '/style.css',
		[],
		file_exists($parent_style_path) ? filemtime($parent_style_path) : null
	);

	wp_enqueue_style(
		'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		['parent-style'],
		file_exists($child_style_path) ? filemtime($child_style_path) : null
	);

	if (file_exists($custom_style_path)) {
		wp_enqueue_style(
			'child-custom-style',
			get_stylesheet_directory_uri() . '/custom.css',
			['child-style'],
			filemtime($custom_style_path)
		);
	}
}
add_action('wp_enqueue_scripts', 'wrugc_enqueue_theme_styles');

/**
 * Register the gallery submission custom post type.
 */
function wrugc_register_gallery_item_post_type()
{
	register_post_type(
		WRUGC_POST_TYPE,
		[
			'labels'              => [
				'name'               => __('Submissions', WRUGC_TEXT_DOMAIN),
				'menu_name'          => __('Submissions', WRUGC_TEXT_DOMAIN),
				'singular_name'      => __('Submission', WRUGC_TEXT_DOMAIN),
				'add_new'            => __('Add Submission', WRUGC_TEXT_DOMAIN),
				'add_new_item'       => __('Add New Submission', WRUGC_TEXT_DOMAIN),
				'edit_item'          => __('Edit Submission', WRUGC_TEXT_DOMAIN),
				'new_item'           => __('New Submission', WRUGC_TEXT_DOMAIN),
				'view_item'          => __('View Submission', WRUGC_TEXT_DOMAIN),
				'search_items'       => __('Search Submissions', WRUGC_TEXT_DOMAIN),
				'not_found'          => __('No submissions found', WRUGC_TEXT_DOMAIN),
				'not_found_in_trash' => __('No submissions found in Trash', WRUGC_TEXT_DOMAIN),
				'all_items'          => __('All Submissions', WRUGC_TEXT_DOMAIN),
				'archives'           => __('Submission Archives', WRUGC_TEXT_DOMAIN),
				'insert_into_item'   => __('Insert into submission', WRUGC_TEXT_DOMAIN),
			],
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_nav_menus'   => true,
			'has_archive'         => true,
			'rewrite'             => ['slug' => 'gallery_item'],
			'menu_icon'           => 'dashicons-format-gallery',
			'supports'            => ['title'],
			'show_in_rest'        => false,
			'capability_type'     => 'post',
		]
	);
}
add_action('init', 'wrugc_register_gallery_item_post_type');

/**
 * Add a friendlier single submission route.
 */
function wrugc_add_gallery_item_rewrite()
{
	add_rewrite_rule('gallery/([^/]+)/?$', 'index.php?post_type=' . WRUGC_POST_TYPE . '&name=$matches[1]', 'top');
}
add_action('init', 'wrugc_add_gallery_item_rewrite');

/**
 * Flush rewrite rules when the child theme is activated.
 */
function wrugc_flush_rewrite_rules()
{
	wrugc_register_gallery_item_post_type();
	wrugc_add_gallery_item_rewrite();
	flush_rewrite_rules();
}
add_action('after_switch_theme', 'wrugc_flush_rewrite_rules');

/**
 * Read a config value from a WordPress constant first, then an environment variable.
 *
 * @param string $name Constant/environment variable name.
 * @return string
 */
function wrugc_get_config_value($name)
{
	if (defined($name)) {
		return (string) constant($name);
	}

	$value = getenv($name);

	return false === $value ? '' : (string) $value;
}

/**
 * Load Composer's autoloader if it is available in the child theme.
 */
function wrugc_load_vendor_autoloader()
{
	static $loaded = false;

	if ($loaded) {
		return true;
	}

	$autoload_path = get_stylesheet_directory() . '/vendor/autoload.php';

	if (file_exists($autoload_path)) {
		require_once $autoload_path;
		$loaded = true;
	}

	return $loaded;
}

/**
 * Build an S3 client from environment/constant configuration.
 *
 * Supported constants/environment variables:
 * - WRUGC_AWS_ACCESS_KEY_ID
 * - WRUGC_AWS_SECRET_ACCESS_KEY
 * - WRUGC_AWS_REGION
 * - WRUGC_AWS_BUCKET
 *
 * @return \Aws\S3\S3Client|\WP_Error
 */
function wrugc_get_s3_client()
{
	if (!wrugc_load_vendor_autoloader() || !class_exists('\Aws\S3\S3Client')) {
		return new WP_Error(
			'wrugc_missing_aws_sdk',
			__('AWS SDK is not installed. Run composer install in the child theme directory.', WRUGC_TEXT_DOMAIN)
		);
	}

	$access_key = wrugc_get_config_value('WRUGC_AWS_ACCESS_KEY_ID');
	$secret_key = wrugc_get_config_value('WRUGC_AWS_SECRET_ACCESS_KEY');
	$region     = wrugc_get_config_value('WRUGC_AWS_REGION');
	$bucket     = wrugc_get_config_value('WRUGC_AWS_BUCKET');

	if (!$access_key || !$secret_key || !$region || !$bucket) {
		return new WP_Error(
			'wrugc_missing_s3_config',
			__('S3 uploads are not configured. Please set the WRUGC AWS constants or environment variables.', WRUGC_TEXT_DOMAIN)
		);
	}

	return new Aws\S3\S3Client(
		[
			'version'     => 'latest',
			'region'      => $region,
			'credentials' => [
				'key'    => $access_key,
				'secret' => $secret_key,
			],
		]
	);
}

/**
 * Get the configured S3 bucket.
 *
 * @return string
 */
function wrugc_get_s3_bucket()
{
	return wrugc_get_config_value('WRUGC_AWS_BUCKET');
}

/**
 * Allowed upload extensions.
 *
 * @return string[]
 */
function wrugc_allowed_upload_extensions()
{
	return (array) apply_filters(
		'wrugc_allowed_upload_extensions',
		['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'mp4', 'mov']
	);
}

/**
 * Maximum accepted upload size per file.
 *
 * @return int
 */
function wrugc_max_upload_size()
{
	return (int) apply_filters('wrugc_max_upload_size', 100 * MB_IN_BYTES);
}

/**
 * Update an ACF field when available, otherwise fall back to post meta.
 *
 * @param string $name Field name.
 * @param mixed  $value Value.
 * @param int    $post_id Post ID.
 */
function wrugc_update_field_value($name, $value, $post_id)
{
	$updated = false;

	if (function_exists('update_field')) {
		$updated = update_field($name, $value, $post_id);
	}

	if (!$updated) {
		update_post_meta($post_id, $name, $value);
	}
}

/**
 * Read an ACF field when available, otherwise fall back to post meta.
 *
 * @param string   $name Field name.
 * @param int|null $post_id Post ID.
 * @return mixed
 */
function wrugc_get_field_value($name, $post_id = null)
{
	$value = null;

	if (function_exists('get_field')) {
		$value = get_field($name, $post_id);
	}

	if (null === $value || false === $value || '' === $value) {
		$value = get_post_meta($post_id ?: get_the_ID(), $name, true);
	}

	return $value;
}

/**
 * Normalize media repeater/post-meta values to an array of rows with file_url.
 *
 * @param string $field_name Field name.
 * @param int    $post_id Post ID.
 * @return array<int, array{file_url:string}>
 */
function wrugc_get_media_rows($field_name, $post_id)
{
	$rows       = wrugc_get_field_value($field_name, $post_id);
	$media_rows = wrugc_normalize_media_rows($rows);

	if (!empty($media_rows)) {
		return $media_rows;
	}

	$raw_rows = get_post_meta($post_id, $field_name, true);

	if ($raw_rows !== $rows) {
		$media_rows = wrugc_normalize_media_rows($raw_rows);

		if (!empty($media_rows)) {
			return $media_rows;
		}
	}

	if (is_numeric($raw_rows)) {
		for ($i = 0; $i < (int) $raw_rows; $i++) {
			$file_url = get_post_meta($post_id, "{$field_name}_{$i}_file_url", true);

			if (!$file_url) {
				$file_url = get_post_meta($post_id, "{$field_name}_{$i}_url", true);
			}

			if ($file_url && filter_var($file_url, FILTER_VALIDATE_URL)) {
				$media_rows[] = ['file_url' => esc_url_raw($file_url)];
			}
		}
	}

	return $media_rows;
}

/**
 * Normalize mixed media field data to rows with file_url.
 *
 * @param mixed $rows Raw field/post-meta data.
 * @return array<int, array{file_url:string}>
 */
function wrugc_normalize_media_rows($rows)
{
	if (is_string($rows)) {
		$maybe_unserialized = maybe_unserialize($rows);

		if (is_array($maybe_unserialized)) {
			$rows = $maybe_unserialized;
		}
	}

	$media_rows = [];

	if (is_string($rows) && filter_var($rows, FILTER_VALIDATE_URL)) {
		return [['file_url' => esc_url_raw($rows)]];
	}

	if (!is_array($rows)) {
		return [];
	}

	foreach ($rows as $row) {
		if (is_array($row) && !empty($row['file_url'])) {
			$media_rows[] = ['file_url' => esc_url_raw($row['file_url'])];
		} elseif (is_array($row) && !empty($row['url'])) {
			$media_rows[] = ['file_url' => esc_url_raw($row['url'])];
		} elseif (is_array($row) && !empty($row['ID'])) {
			$url = wp_get_attachment_url((int) $row['ID']);

			if ($url) {
				$media_rows[] = ['file_url' => esc_url_raw($url)];
			}
		} elseif (is_string($row) && filter_var($row, FILTER_VALIDATE_URL)) {
			$media_rows[] = ['file_url' => esc_url_raw($row)];
		}
	}

	return $media_rows;
}

/**
 * Compress a JPEG, PNG, or WEBP image with GD.
 *
 * @param string $source_path Source file path.
 * @param string $destination_path Destination file path.
 * @param int    $quality JPEG/WEBP quality, 1-100.
 * @return bool
 */
function wrugc_compress_image_gd($source_path, $destination_path, $quality = 75)
{
	if (!function_exists('getimagesize')) {
		return false;
	}

	$info = getimagesize($source_path);

	if (!$info || empty($info['mime'])) {
		return false;
	}

	switch ($info['mime']) {
		case 'image/jpeg':
			if (!function_exists('imagecreatefromjpeg') || !function_exists('imagejpeg')) {
				return false;
			}
			$image  = imagecreatefromjpeg($source_path);
			$result = $image ? imagejpeg($image, $destination_path, $quality) : false;
			break;

		case 'image/png':
			if (!function_exists('imagecreatefrompng') || !function_exists('imagepng')) {
				return false;
			}
			$image = imagecreatefrompng($source_path);

			if ($image) {
				imagealphablending($image, false);
				imagesavealpha($image, true);
			}

			$result = $image ? imagepng($image, $destination_path, max(0, min(9, 9 - (int) floor($quality / 10)))) : false;
			break;

		case 'image/webp':
			if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) {
				return false;
			}
			$image  = imagecreatefromwebp($source_path);
			$result = $image ? imagewebp($image, $destination_path, $quality) : false;
			break;

		default:
			return false;
	}

	if (!empty($image)) {
		imagedestroy($image);
	}

	return (bool) $result;
}

/**
 * Compress a video using ffmpeg when it is installed and exec is available.
 *
 * @param string $source_path Source file path.
 * @param string $destination_path Destination file path.
 * @return bool
 */
function wrugc_compress_video($source_path, $destination_path)
{
	if (!function_exists('exec')) {
		return false;
	}

	$command = sprintf(
		'ffmpeg -y -i %s -vf scale=1280:-2 -c:v libx264 -crf 32 -preset veryslow -c:a aac -b:a 96k -movflags +faststart %s 2>&1',
		escapeshellarg($source_path),
		escapeshellarg($destination_path)
	);

	$output     = [];
	$return_var = 1;
	exec($command, $output, $return_var);

	if (0 !== $return_var || !file_exists($destination_path) || 0 === filesize($destination_path)) {
		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log('WRUGC ffmpeg compression failed: ' . implode("\n", $output));
		}

		return false;
	}

	return true;
}

/**
 * Upload a set of files to S3.
 *
 * @param array  $files Files array from $_FILES.
 * @param string $upload_group_label Human label for errors.
 * @param array  $errors Error accumulator.
 * @return array<int, array{file_url:string}>
 */
function wrugc_upload_files_to_s3($files, $upload_group_label, &$errors)
{
	$uploaded_urls = [];

	if (empty($files['name'][0])) {
		return $uploaded_urls;
	}

	$s3 = wrugc_get_s3_client();

	if (is_wp_error($s3)) {
		$errors[] = $s3->get_error_message();
		return $uploaded_urls;
	}

	$bucket             = wrugc_get_s3_bucket();
	$allowed_extensions = wrugc_allowed_upload_extensions();
	$max_upload_size    = wrugc_max_upload_size();
	$count              = count($files['name']);

	for ($i = 0; $i < $count; $i++) {
		if (empty($files['tmp_name'][$i]) || UPLOAD_ERR_OK !== (int) $files['error'][$i]) {
			$errors[] = sprintf(
				/* translators: %s is BEFORE or AFTER. */
				__('%s file upload failed.', WRUGC_TEXT_DOMAIN),
				$upload_group_label
			);
			continue;
		}

		if ((int) $files['size'][$i] > $max_upload_size) {
			$errors[] = sprintf(
				/* translators: %s is a file name. */
				__('File is too large: %s', WRUGC_TEXT_DOMAIN),
				esc_html($files['name'][$i])
			);
			continue;
		}

		$original_name = basename((string) $files['name'][$i]);
		$temp_path     = (string) $files['tmp_name'][$i];
		$file_type     = wp_check_filetype($original_name);
		$extension     = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
		$content_type  = $file_type['type'] ?: (function_exists('mime_content_type') ? mime_content_type($temp_path) : 'application/octet-stream');

		if (!$content_type) {
			$content_type = 'application/octet-stream';
		}

		if (!in_array($extension, $allowed_extensions, true)) {
			$errors[] = sprintf(
				/* translators: %s is a file name. */
				__('Unsupported file type: %s', WRUGC_TEXT_DOMAIN),
				esc_html($original_name)
			);
			continue;
		}

		$filename       = sanitize_file_name(pathinfo($original_name, PATHINFO_FILENAME));
		$processed_path = $temp_path;
		$final_filename = $filename . '.' . $extension;

		if (in_array($content_type, ['image/jpeg', 'image/png', 'image/webp'], true)) {
			$compressed_path = tempnam(sys_get_temp_dir(), 'wrugc_img_') . '.' . $extension;

			if (wrugc_compress_image_gd($temp_path, $compressed_path, 70)) {
				$processed_path = $compressed_path;
			}
		} elseif (in_array($content_type, ['video/mp4', 'video/quicktime'], true)) {
			$compressed_video = tempnam(sys_get_temp_dir(), 'wrugc_vid_') . '.' . $extension;

			if (wrugc_compress_video($temp_path, $compressed_video)) {
				$processed_path = $compressed_video;
			}
		}

		$key = sprintf(
			'gallery-submissions/%s-%s-%s',
			time(),
			wp_generate_password(8, false, false),
			$final_filename
		);

		try {
			if (filesize($processed_path) > 5 * MB_IN_BYTES) {
				$uploader = new Aws\S3\MultipartUploader(
					$s3,
					$processed_path,
					[
						'bucket'          => $bucket,
						'key'             => $key,
						'before_initiate' => function ($command) use ($content_type) {
							$command['ContentType']        = $content_type;
							$command['ContentDisposition'] = 'inline';
						},
						'concurrency'     => 5,
					]
				);

				$result = $uploader->upload();
				$url    = isset($result['ObjectURL']) ? $result['ObjectURL'] : $s3->getObjectUrl($bucket, $key);
			} else {
				$result = $s3->putObject(
					[
						'Bucket'             => $bucket,
						'Key'                => $key,
						'SourceFile'         => $processed_path,
						'ContentType'        => $content_type,
						'ContentDisposition' => 'inline',
					]
				);

				$url = isset($result['ObjectURL']) ? $result['ObjectURL'] : $s3->getObjectUrl($bucket, $key);
			}

			$uploaded_urls[] = [
				'file_url' => esc_url_raw($url),
			];
		} catch (Aws\Exception\AwsException $e) {
			$errors[] = sprintf(
				/* translators: %s is an AWS error message. */
				__('S3 upload failed: %s', WRUGC_TEXT_DOMAIN),
				$e->getAwsErrorMessage()
			);
		} catch (Exception $e) {
			$errors[] = sprintf(
				/* translators: %s is an error message. */
				__('Upload failed: %s', WRUGC_TEXT_DOMAIN),
				$e->getMessage()
			);
		} finally {
			if ($processed_path !== $temp_path && file_exists($processed_path)) {
				unlink($processed_path);
			}
		}
	}

	return $uploaded_urls;
}

/**
 * Determine whether email notifications are enabled.
 *
 * @return bool
 */
function wrugc_email_notifications_enabled()
{
	return !defined('EMAIL_NOTIFICATIONS_ENABLED') || (bool) EMAIL_NOTIFICATIONS_ENABLED;
}

/**
 * Get an unslashed posted scalar value.
 *
 * @param string $key POST key.
 * @return string
 */
function wrugc_posted_value($key)
{
	if (!isset($_POST[$key])) {
		return '';
	}

	return sanitize_text_field(wp_unslash($_POST[$key]));
}

/**
 * Get an unslashed posted textarea value.
 *
 * @param string $key POST key.
 * @return string
 */
function wrugc_posted_textarea($key)
{
	if (!isset($_POST[$key])) {
		return '';
	}

	return sanitize_textarea_field(wp_unslash($_POST[$key]));
}

/**
 * Get an unslashed posted array.
 *
 * @param string $key POST key.
 * @return string[]
 */
function wrugc_posted_array($key)
{
	if (empty($_POST[$key]) || !is_array($_POST[$key])) {
		return [];
	}

	return array_map('sanitize_text_field', wp_unslash($_POST[$key]));
}

/**
 * Gallery submission form shortcode.
 *
 * @return string
 */
function wrugc_gallery_submission_form_shortcode()
{
	ob_start();

	$project_type_options = [
		'Deck',
		'Fence',
		'Wood Siding',
		'T-111 Siding',
		'Log Home',
		'Pergola',
		'Gazebo',
		'Boat Dock',
		'Other',
	];

	$wood_type_options = [
		'Pine',
		'Cedar',
		'Redwood',
		'Fir',
		'Ipe',
		'Garapa',
		'Cumaru',
		'Hemlock',
		'Other',
	];

	$product_options = [
		'Timber Oil Deep Penetrating Stain',
		'Hardwood Wiping Stain',
		'HD-80 Heavy Duty Wood Stripper',
		'EFC-38 Wood Cleaner & Mild Stripper',
		'Citralic Wood Brightener & Neutralizer',
	];

	$color_options = [
		'Amaretto',
		'Clear Advantage',
		'Brown Sugar',
		'Signature Brown',
		'Warm Honey Gold',
		'Western Cedar',
	];

	$errors        = [];
	$is_submitted  = 'POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['wrugc_gallery_submission']);
	$success_param = isset($_GET['success']) ? sanitize_text_field(wp_unslash($_GET['success'])) : '';

	if ('1' === $success_param) {
		echo '<div class="wrugc-alert wrugc-alert-success">' . esc_html__('Thank you! Your submission has been received.', WRUGC_TEXT_DOMAIN) . '</div>';
	}

	if ($is_submitted) {
		$nonce = isset($_POST['wrugc_gallery_nonce']) ? sanitize_text_field(wp_unslash($_POST['wrugc_gallery_nonce'])) : '';

		if (!wp_verify_nonce($nonce, 'wrugc_gallery_submission')) {
			$errors[] = __('Security check failed. Please refresh the page and try again.', WRUGC_TEXT_DOMAIN);
		}

		$name                   = wrugc_posted_value('ugc_name');
		$email                  = sanitize_email(wrugc_posted_value('ugc_email'));
		$phone                  = wrugc_posted_value('ugc_phone');
		$ugc_project_type       = wrugc_posted_value('ugc_project_type');
		$ugc_project_type_other = wrugc_posted_value('ugc_project_type_other');
		$desc                   = wrugc_posted_textarea('ugc_project_description');
		$wood_type              = wrugc_posted_value('ugc_wood_type');
		$wood_type_other        = wrugc_posted_value('ugc_wood_type_other');
		$ugc_products_used      = wrugc_posted_array('ugc_products_used');
		$ugc_colors_used        = wrugc_posted_array('ugc_colors_used');
		$agreed                 = isset($_POST['ugc_terms']) && '1' === sanitize_text_field(wp_unslash($_POST['ugc_terms']));

		if (!$name) {
			$errors[] = __('Name is required.', WRUGC_TEXT_DOMAIN);
		}

		if (!$email || !is_email($email)) {
			$errors[] = __('Valid email is required.', WRUGC_TEXT_DOMAIN);
		}

		if (!$phone) {
			$errors[] = __('Phone number is required.', WRUGC_TEXT_DOMAIN);
		}

		if (!$ugc_project_type) {
			$errors[] = __('Project type is required.', WRUGC_TEXT_DOMAIN);
		}

		if ('Other' === $ugc_project_type && !$ugc_project_type_other) {
			$errors[] = __('Please specify project type when selecting "Other".', WRUGC_TEXT_DOMAIN);
		}

		if (!$desc) {
			$errors[] = __('Project description is required.', WRUGC_TEXT_DOMAIN);
		}

		if (!$wood_type) {
			$errors[] = __('Wood type is required.', WRUGC_TEXT_DOMAIN);
		}

		if ('Other' === $wood_type && !$wood_type_other) {
			$errors[] = __('Please specify wood type when selecting "Other".', WRUGC_TEXT_DOMAIN);
		}

		if (!$agreed) {
			$errors[] = __('You must agree to the terms and media release.', WRUGC_TEXT_DOMAIN);
		}

		if (empty($_FILES['ugc_before_files']['name'][0])) {
			$errors[] = __('Please upload at least one BEFORE image or video.', WRUGC_TEXT_DOMAIN);
		}

		if (empty($_FILES['ugc_after_files']['name'][0])) {
			$errors[] = __('Please upload at least one AFTER image or video.', WRUGC_TEXT_DOMAIN);
		}

		if (empty($errors)) {
			$before_media_urls = wrugc_upload_files_to_s3($_FILES['ugc_before_files'], 'BEFORE', $errors);
			$after_media_urls  = wrugc_upload_files_to_s3($_FILES['ugc_after_files'], 'AFTER', $errors);
			$media_urls        = array_merge($before_media_urls, $after_media_urls);

			if (empty($media_urls)) {
				$errors[] = __('Please upload at least one valid media file.', WRUGC_TEXT_DOMAIN);
			}

			if (empty($errors)) {
				$post_id = wp_insert_post(
					[
						'post_title'  => $name . ' - ' . current_time('YmdHis'),
						'post_type'   => WRUGC_POST_TYPE,
						'post_status' => 'pending',
					],
					true
				);

				if ($post_id && !is_wp_error($post_id)) {
					wrugc_update_field_value('ugc_name', $name, $post_id);
					wrugc_update_field_value('ugc_email', $email, $post_id);
					wrugc_update_field_value('ugc_phone', $phone, $post_id);
					wrugc_update_field_value('ugc_project_type', $ugc_project_type, $post_id);
					wrugc_update_field_value('ugc_project_type_other', $ugc_project_type_other, $post_id);
					wrugc_update_field_value('ugc_project_description', $desc, $post_id);
					wrugc_update_field_value('ugc_wood_type', $wood_type, $post_id);
					wrugc_update_field_value('ugc_wood_type_other', $wood_type_other, $post_id);
					wrugc_update_field_value('ugc_products_used', implode(', ', $ugc_products_used), $post_id);
					wrugc_update_field_value('ugc_colors_used', implode(', ', $ugc_colors_used), $post_id);
					wrugc_update_field_value('ugc_terms', 1, $post_id);
					wrugc_update_field_value('ugc_media_urls', $media_urls, $post_id);
					wrugc_update_field_value('ugc_before_media_urls', $before_media_urls, $post_id);
					wrugc_update_field_value('ugc_after_media_urls', $after_media_urls, $post_id);

					if (get_option('notification_emails')) {
						wrugc_send_gallery_submission_email($name, $email, $desc, $media_urls, $post_id);
					}

					wp_safe_redirect(add_query_arg('success', '1', get_permalink()));
					exit;
				}

				$errors[] = is_wp_error($post_id) ? $post_id->get_error_message() : __('Error saving your submission. Please try again.', WRUGC_TEXT_DOMAIN);
			}
		}

		if (!empty($errors)) {
			echo '<div class="wrugc-alert wrugc-alert-error"><ul>';

			foreach ($errors as $error) {
				echo '<li>' . esc_html($error) . '</li>';
			}

			echo '</ul></div>';
		}
	}
	?>

	<form method="post" enctype="multipart/form-data" class="ugc-submission-form" onsubmit="return wrugcHandleFormSubmit(this)">
		<?php wp_nonce_field('wrugc_gallery_submission', 'wrugc_gallery_nonce'); ?>
		<input type="hidden" name="wrugc_gallery_submission" value="1">

		<div class="form-section your-contact-info">
			<h3><?php esc_html_e('Your Contact Info', WRUGC_TEXT_DOMAIN); ?></h3>

			<div class="form-group">
				<input type="text" name="ugc_name" id="ugc_name" class="form-control" placeholder="<?php esc_attr_e('Full Name*', WRUGC_TEXT_DOMAIN); ?>" value="<?php echo esc_attr(wrugc_posted_value('ugc_name')); ?>" required>
			</div>

			<div class="form-group">
				<input type="email" name="ugc_email" id="ugc_email" class="form-control" placeholder="<?php esc_attr_e('Email*', WRUGC_TEXT_DOMAIN); ?>" value="<?php echo esc_attr(wrugc_posted_value('ugc_email')); ?>" required>
			</div>

			<div class="form-group">
				<input type="text" name="ugc_phone" id="ugc_phone" class="form-control" placeholder="<?php esc_attr_e('Phone*', WRUGC_TEXT_DOMAIN); ?>" value="<?php echo esc_attr(wrugc_posted_value('ugc_phone')); ?>" required>
			</div>
		</div>

		<div class="form-section your-project-details">
			<h3><?php esc_html_e('Your Project Details', WRUGC_TEXT_DOMAIN); ?></h3>

			<div class="form-group">
				<select name="ugc_project_type" id="ugc_project_type" class="form-control" required>
					<option value=""><?php esc_html_e('Project Type*', WRUGC_TEXT_DOMAIN); ?></option>
					<?php foreach ($project_type_options as $option) : ?>
						<option value="<?php echo esc_attr($option); ?>" <?php selected(wrugc_posted_value('ugc_project_type'), $option); ?>><?php echo esc_html($option); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="form-group conditional-field" id="project-type-other-container" style="display: none;">
				<input type="text" name="ugc_project_type_other" id="ugc_project_type_other" class="form-control" placeholder="<?php esc_attr_e('Project Type, if Other', WRUGC_TEXT_DOMAIN); ?>" value="<?php echo esc_attr(wrugc_posted_value('ugc_project_type_other')); ?>">
			</div>

			<div class="form-group">
				<textarea name="ugc_project_description" id="ugc_project_description" rows="4" class="form-control" placeholder="<?php esc_attr_e('Project Description (EXAMPLE: Deck had turned gray and existing stain was failing...)', WRUGC_TEXT_DOMAIN); ?>" required><?php echo esc_textarea(wrugc_posted_textarea('ugc_project_description')); ?></textarea>
			</div>

			<div class="form-group">
				<select name="ugc_wood_type" id="ugc_wood_type" class="form-control" required>
					<option value=""><?php esc_html_e('Wood Type*', WRUGC_TEXT_DOMAIN); ?></option>
					<?php foreach ($wood_type_options as $option) : ?>
						<option value="<?php echo esc_attr($option); ?>" <?php selected(wrugc_posted_value('ugc_wood_type'), $option); ?>><?php echo esc_html($option); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="form-group conditional-field" id="wood-type-other-container" style="display: none;">
				<input type="text" name="ugc_wood_type_other" id="ugc_wood_type_other" class="form-control" placeholder="<?php esc_attr_e('Wood Type, if Other', WRUGC_TEXT_DOMAIN); ?>" value="<?php echo esc_attr(wrugc_posted_value('ugc_wood_type_other')); ?>">
			</div>
		</div>

		<div class="form-section products-used-section">
			<h3><?php esc_html_e('Products Used', WRUGC_TEXT_DOMAIN); ?></h3>

			<div class="checkbox-group">
				<?php foreach ($product_options as $option) : ?>
					<label class="checkbox-option">
						<input type="checkbox" name="ugc_products_used[]" value="<?php echo esc_attr($option); ?>" <?php checked(in_array($option, wrugc_posted_array('ugc_products_used'), true)); ?>>
						<?php echo esc_html($option); ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="form-section stain-colors-section">
			<h3><?php esc_html_e('Stain Colors Used', WRUGC_TEXT_DOMAIN); ?></h3>

			<div class="checkbox-group">
				<?php foreach ($color_options as $option) : ?>
					<label class="checkbox-option">
						<input type="checkbox" name="ugc_colors_used[]" value="<?php echo esc_attr($option); ?>" <?php checked(in_array($option, wrugc_posted_array('ugc_colors_used'), true)); ?>>
						<?php echo esc_html($option); ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="form-section upload-photo-video-section">
			<h3><?php esc_html_e('Upload Photo/Videos', WRUGC_TEXT_DOMAIN); ?></h3>

			<div class="form-group">
				<label for="ugc_before_files"><?php esc_html_e('Step 1: Upload BEFORE Images / Videos', WRUGC_TEXT_DOMAIN); ?> <span class="wrugc-required">*</span></label>
				<input type="file" name="ugc_before_files[]" id="ugc_before_files" class="form-control-file ugc-file-input" multiple accept=".jpg,.jpeg,.png,.webp,.heic,.heif,.mp4,.mov" required>
				<small class="form-text text-muted"><?php esc_html_e('JPG, PNG, WEBP, HEIC, MP4, MOV.', WRUGC_TEXT_DOMAIN); ?></small>
				<div id="ugc_before_preview" class="ugc-upload-preview"></div>
			</div>

			<div class="form-group">
				<label for="ugc_after_files"><?php esc_html_e('Step 2: Upload AFTER Images / Videos', WRUGC_TEXT_DOMAIN); ?> <span class="wrugc-required">*</span></label>
				<input type="file" name="ugc_after_files[]" id="ugc_after_files" class="form-control-file ugc-file-input" multiple accept=".jpg,.jpeg,.png,.webp,.heic,.heif,.mp4,.mov" required>
				<small class="form-text text-muted"><?php esc_html_e('JPG, PNG, WEBP, HEIC, MP4, MOV.', WRUGC_TEXT_DOMAIN); ?></small>
				<div id="ugc_after_preview" class="ugc-upload-preview"></div>
			</div>
		</div>

		<div class="form-section terms-conditions-section">
			<h3><?php esc_html_e('Terms & Conditions', WRUGC_TEXT_DOMAIN); ?></h3>

			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="ugc_terms" value="1" id="ugc_terms" <?php checked(!$is_submitted || isset($_POST['ugc_terms'])); ?> required>
				<label class="form-check-label" for="ugc_terms">
					<?php esc_html_e('I agree to the Terms & Conditions / Media Release', WRUGC_TEXT_DOMAIN); ?> <span class="wrugc-required">*</span>
				</label>
			</div>
		</div>

		<button type="submit" id="ugcSubmitBtn" class="ugc-btn">
			<span id="ugcBtnSpinner" class="ugc-spinner" style="display: none;"></span>
			<span id="ugcBtnText"><?php esc_html_e('Submit', WRUGC_TEXT_DOMAIN); ?></span>
		</button>
	</form>

	<script>
	window.wrugcHandleFormSubmit = function (form) {
		const btn = form.querySelector('#ugcSubmitBtn');
		const spinner = form.querySelector('#ugcBtnSpinner');
		const text = form.querySelector('#ugcBtnText');

		if (btn) {
			btn.disabled = true;
		}

		if (spinner) {
			spinner.style.display = 'inline-block';
		}

		if (text) {
			text.textContent = 'Uploading...';
		}

		return true;
	};

	document.addEventListener('DOMContentLoaded', function () {
		const projectTypeSelect = document.getElementById('ugc_project_type');
		const projectTypeOtherContainer = document.getElementById('project-type-other-container');
		const projectTypeOtherInput = document.getElementById('ugc_project_type_other');
		const woodTypeSelect = document.getElementById('ugc_wood_type');
		const woodTypeOtherContainer = document.getElementById('wood-type-other-container');
		const woodTypeOtherInput = document.getElementById('ugc_wood_type_other');

		function toggleConditionalField(selectElement, otherContainer, otherInput) {
			const isOtherSelected = selectElement.value === 'Other';

			if (isOtherSelected) {
				otherContainer.style.display = 'block';
				otherInput.setAttribute('required', 'required');
			} else {
				otherContainer.style.display = 'none';
				otherInput.removeAttribute('required');
				otherInput.value = '';
			}
		}

		if (projectTypeSelect && projectTypeOtherContainer && projectTypeOtherInput) {
			projectTypeSelect.addEventListener('change', function () {
				toggleConditionalField(projectTypeSelect, projectTypeOtherContainer, projectTypeOtherInput);
			});

			toggleConditionalField(projectTypeSelect, projectTypeOtherContainer, projectTypeOtherInput);
		}

		if (woodTypeSelect && woodTypeOtherContainer && woodTypeOtherInput) {
			woodTypeSelect.addEventListener('change', function () {
				toggleConditionalField(woodTypeSelect, woodTypeOtherContainer, woodTypeOtherInput);
			});

			toggleConditionalField(woodTypeSelect, woodTypeOtherContainer, woodTypeOtherInput);
		}

		function setupFilePreview(inputId, previewId) {
			const input = document.getElementById(inputId);
			const preview = document.getElementById(previewId);

			if (!input || !preview) {
				return;
			}

			input.addEventListener('change', function () {
				renderFilePreview(input, preview);
			});
		}

		function renderFilePreview(input, preview) {
			preview.innerHTML = '';

			Array.from(input.files).forEach(function (file, index) {
				const item = document.createElement('div');
				item.className = 'ugc-preview-item';

				const fileUrl = URL.createObjectURL(file);
				const removeButton = '<button type="button" class="ugc-remove-file" data-index="' + index + '">Remove</button>';

				if (file.type.startsWith('image/')) {
					item.innerHTML = '<img src="' + fileUrl + '" alt="">' + removeButton;
				} else if (file.type.startsWith('video/')) {
					item.innerHTML = '<video src="' + fileUrl + '" muted controls></video>' + removeButton;
				} else {
					item.innerHTML = '<div class="ugc-file-name"></div>' + removeButton;
					item.querySelector('.ugc-file-name').textContent = file.name;
				}

				preview.appendChild(item);
			});

			preview.querySelectorAll('.ugc-remove-file').forEach(function (button) {
				button.addEventListener('click', function () {
					removeSelectedFile(input, preview, parseInt(button.getAttribute('data-index'), 10));
				});
			});
		}

		function removeSelectedFile(input, preview, removeIndex) {
			const dataTransfer = new DataTransfer();

			Array.from(input.files).forEach(function (file, index) {
				if (index !== removeIndex) {
					dataTransfer.items.add(file);
				}
			});

			input.files = dataTransfer.files;
			renderFilePreview(input, preview);
		}

		setupFilePreview('ugc_before_files', 'ugc_before_preview');
		setupFilePreview('ugc_after_files', 'ugc_after_preview');
	});
	</script>
	<?php

	return ob_get_clean();
}
add_shortcode('gallery_submission_form', 'wrugc_gallery_submission_form_shortcode');

/**
 * Render a preview next to an ACF file URL field.
 *
 * @param array $field ACF field.
 */
function wrugc_render_file_url_preview($field)
{
	if (empty($field['value'])) {
		return;
	}

	$url  = esc_url($field['value']);
	$type = wrugc_get_media_type_from_url($url);

	if (!$type) {
		return;
	}

	echo '<div style="margin-top:10px; display:flex; align-items:center; gap:10px;">';

	if ('image' === $type) {
		echo '<img src="' . esc_url($url) . '" style="max-width:100px; border:1px solid #ccc;" alt="">';
	} elseif ('video' === $type) {
		echo '<video src="' . esc_url($url) . '" style="max-width:100px;" controls muted preload="metadata"></video>';
	}

	echo '<button type="button" class="button set-thumbnail" data-url="' . esc_url($url) . '">' . esc_html__('Choose as Thumbnail', WRUGC_TEXT_DOMAIN) . '</button>';
	echo '</div>';
}
add_action('acf/render_field/name=file_url', 'wrugc_render_file_url_preview', 10, 1);

/**
 * Add ACF admin JavaScript for selecting a thumbnail URL from uploaded media.
 */
function wrugc_acf_admin_footer_script()
{
	?>
	<script>
	(function($) {
		$(document).ready(function() {
			$(document).on('click', '.set-thumbnail', function(e) {
				e.preventDefault();

				var thumbnailUrl = $(this).data('url');
				var $thumbnailField = findAcfField('ugc_thumbnail_url');

				if ($thumbnailField.length) {
					$thumbnailField.val(thumbnailUrl).trigger('change');
					updateThumbnailPreview($thumbnailField, thumbnailUrl);

					if (typeof acf !== 'undefined') {
						acf.doAction('change', $thumbnailField);
					}
				} else {
					window.alert('Could not find the thumbnail field.');
				}
			});

			function findAcfField(fieldName) {
				var selectors = [
					'[data-name="' + fieldName + '"] input[type="text"], [data-name="' + fieldName + '"] input[type="url"]',
					'input[name="acf[' + fieldName + ']"]'
				];

				for (var i = 0; i < selectors.length; i++) {
					var $field = $(selectors[i]);

					if ($field.length) {
						return $field;
					}
				}

				return $();
			}

			function updateThumbnailPreview($field, url) {
				var $wrapper = $field.closest('.acf-field');
				var $preview = $wrapper.find('.thumbnail-preview');

				if (!$preview.length) {
					$preview = $('<div class="thumbnail-preview" style="margin-top:10px;"></div>');
					$field.after($preview);
				}

				if (url.match(/\.(jpe?g|png|gif|webp|bmp)$/i)) {
					$preview.html('<img src="' + url + '" style="max-width:100px; max-height:100px; border:1px solid #ddd;" alt="">');
				} else if (url.match(/\.(mp4|mov|webm|ogg)$/i)) {
					$preview.html('<video src="' + url + '" style="max-width:100px; max-height:100px;" controls muted playsinline></video>');
				} else {
					$preview.html('<div class="notice notice-info inline" style="padding:5px 10px; margin:5px 0;">Preview not available for this file type</div>');
				}
			}
		});
	})(jQuery);
	</script>
	<?php
}
add_action('acf/input/admin_footer', 'wrugc_acf_admin_footer_script');

/**
 * Render thumbnail URL preview in ACF.
 *
 * @param array $field ACF field.
 */
function wrugc_render_thumbnail_url_preview($field)
{
	echo '<div class="thumbnail-preview" style="margin-top:10px;">';

	if (!empty($field['value'])) {
		$url  = esc_url($field['value']);
		$type = wrugc_get_media_type_from_url($url);

		if ('image' === $type) {
			echo '<img src="' . esc_url($url) . '" style="max-width:100px; border:1px solid #ccc;" alt="">';
		} elseif ('video' === $type) {
			echo '<video src="' . esc_url($url) . '" style="max-width:100px;" controls muted preload="metadata"></video>';
		} else {
			echo '<div style="padding:10px; background:#f5f5f5; border:1px solid #ddd;">' . esc_html__('Preview not available for this file type', WRUGC_TEXT_DOMAIN) . '</div>';
		}
	}

	echo '</div>';
}
add_action('acf/render_field/name=ugc_thumbnail_url', 'wrugc_render_thumbnail_url_preview');

/**
 * Return media type from URL extension.
 *
 * @param string $url Media URL.
 * @return string
 */
function wrugc_get_media_type_from_url($url)
{
	$path = (string) wp_parse_url($url, PHP_URL_PATH);
	$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

	if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
		return 'image';
	}

	if (in_array($ext, ['mp4', 'mov', 'webm', 'ogg'], true)) {
		return 'video';
	}

	return '';
}

/**
 * Add submission admin list columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function wrugc_gallery_item_columns($columns)
{
	$date_column = $columns['date'] ?? null;

	if (isset($columns['date'])) {
		unset($columns['date']);
	}

	$columns['email']               = __('Email', WRUGC_TEXT_DOMAIN);
	$columns['project_description'] = __('Project Description', WRUGC_TEXT_DOMAIN);
	$columns['ugc_before_preview']  = __('Before Preview', WRUGC_TEXT_DOMAIN);
	$columns['ugc_after_preview']   = __('After Preview', WRUGC_TEXT_DOMAIN);

	if ($date_column) {
		$columns['date'] = $date_column;
	}

	return $columns;
}
add_filter('manage_' . WRUGC_POST_TYPE . '_posts_columns', 'wrugc_gallery_item_columns');

/**
 * Render submission admin list columns.
 *
 * @param string $column Column key.
 * @param int    $post_id Post ID.
 */
function wrugc_gallery_item_custom_column($column, $post_id)
{
	if ('ugc_before_preview' === $column) {
		wrugc_render_admin_media_preview(
			wrugc_get_media_rows('ugc_before_media_urls', $post_id),
			__('No before media', WRUGC_TEXT_DOMAIN)
		);
	} elseif ('ugc_after_preview' === $column) {
		wrugc_render_admin_media_preview(
			wrugc_get_media_rows('ugc_after_media_urls', $post_id),
			__('No after media', WRUGC_TEXT_DOMAIN)
		);
	} elseif ('project_description' === $column) {
		$description = wrugc_get_field_value('ugc_project_description', $post_id);
		echo $description ? esc_html(wp_trim_words($description, 20)) : '<span style="color:#999;">' . esc_html__('No description', WRUGC_TEXT_DOMAIN) . '</span>';
	} elseif ('email' === $column) {
		$email = wrugc_get_field_value('ugc_email', $post_id);
		echo $email ? '<span>' . esc_html($email) . '</span>' : '<span style="color:#999;">' . esc_html__('No email provided', WRUGC_TEXT_DOMAIN) . '</span>';
	}
}
add_action('manage_' . WRUGC_POST_TYPE . '_posts_custom_column', 'wrugc_gallery_item_custom_column', 10, 2);

/**
 * Render small media thumbnails in the submissions list table.
 *
 * @param array  $media_rows Media rows.
 * @param string $empty_label Empty state label.
 */
function wrugc_render_admin_media_preview($media_rows, $empty_label)
{
	if (empty($media_rows)) {
		echo '<span style="color:#999;">' . esc_html($empty_label) . '</span>';
		return;
	}

	$count = 0;

	foreach ($media_rows as $item) {
		if (empty($item['file_url'])) {
			continue;
		}

		$url  = esc_url($item['file_url']);
		$type = wrugc_get_media_type_from_url($url);

		if (!$url) {
			continue;
		}

		echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" style="display:inline-block; margin-right:5px; vertical-align:middle;">';

		if ('image' === $type) {
			echo '<img src="' . esc_url($url) . '" style="height:40px; width:40px; object-fit:cover; border:1px solid #ccd0d4; border-radius:2px;" alt="">';
		} elseif ('video' === $type) {
			echo '<video src="' . esc_url($url) . '" style="height:40px; width:40px; object-fit:cover; border:1px solid #ccd0d4; border-radius:2px;" muted preload="metadata"></video>';
		} else {
			echo '<span class="dashicons dashicons-media-default" style="line-height:40px; width:40px; height:40px; border:1px solid #ccd0d4; color:#50575e;"></span>';
		}

		echo '</a>';

		$count++;

		if ($count >= 4) {
			break;
		}
	}

	if (0 === $count) {
		echo '<span style="color:#999;">' . esc_html($empty_label) . '</span>';
	}
}

/**
 * Add email settings submenu.
 */
function wrugc_email_settings_menu()
{
	add_options_page(
		__('Email Settings', WRUGC_TEXT_DOMAIN),
		__('Email Settings', WRUGC_TEXT_DOMAIN),
		'manage_options',
		'wrugc-email-settings',
		'wrugc_email_settings_page'
	);
}
add_action('admin_menu', 'wrugc_email_settings_menu');

/**
 * Render email settings page.
 */
function wrugc_email_settings_page()
{
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Email Settings', WRUGC_TEXT_DOMAIN); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields('wrugc_email_settings_group');
			do_settings_sections('wrugc-email-settings');
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Register email settings.
 */
function wrugc_email_settings_init()
{
	register_setting('wrugc_email_settings_group', 'notification_emails', 'wrugc_sanitize_emails');
	register_setting('wrugc_email_settings_group', 'admin_email_message', 'wp_kses_post');
	register_setting('wrugc_email_settings_group', 'user_status_update_message', 'wp_kses_post');

	add_settings_section(
		'wrugc_email_settings_section',
		__('Notification Email Addresses', WRUGC_TEXT_DOMAIN),
		'__return_empty_string',
		'wrugc-email-settings'
	);

	add_settings_field(
		'notification_emails',
		__('Email Addresses', WRUGC_TEXT_DOMAIN),
		'wrugc_notification_emails_callback',
		'wrugc-email-settings',
		'wrugc_email_settings_section'
	);

	add_settings_field(
		'admin_email_message',
		__('Admin Notification Message', WRUGC_TEXT_DOMAIN),
		'wrugc_admin_email_message_callback',
		'wrugc-email-settings',
		'wrugc_email_settings_section'
	);

	add_settings_field(
		'user_status_update_message',
		__('User Status Update Message', WRUGC_TEXT_DOMAIN),
		'wrugc_user_status_update_message_callback',
		'wrugc-email-settings',
		'wrugc_email_settings_section'
	);
}
add_action('admin_init', 'wrugc_email_settings_init');

/**
 * Sanitize comma-separated emails.
 *
 * @param string $input Raw input.
 * @return string
 */
function wrugc_sanitize_emails($input)
{
	$emails           = explode(',', (string) $input);
	$sanitized_emails = [];

	foreach ($emails as $email) {
		$email = sanitize_email(trim($email));

		if ($email && is_email($email)) {
			$sanitized_emails[] = $email;
		}
	}

	return implode(',', $sanitized_emails);
}

/**
 * Render notification emails setting.
 */
function wrugc_notification_emails_callback()
{
	$emails = get_option('notification_emails');

	echo '<input type="text" name="notification_emails" value="' . esc_attr($emails) . '" class="regular-text" placeholder="admin@example.com, another@example.com">';
	echo '<br><span style="color:#777; font-size:13px; display:inline-block; margin-top:6px;">' . esc_html__('Enter email addresses to receive notifications (comma separated).', WRUGC_TEXT_DOMAIN) . '</span>';
}

/**
 * Render admin email message setting.
 */
function wrugc_admin_email_message_callback()
{
	$content = get_option('admin_email_message');

	echo '<textarea name="admin_email_message" rows="6" class="large-text" placeholder="' . esc_attr__('Enter message for admin when a new submission arrives', WRUGC_TEXT_DOMAIN) . '">' . esc_textarea($content) . '</textarea>';
	echo '<br><span style="color:#777; font-size:13px; display:inline-block; margin-top:6px;">' . wp_kses_post(__('Available placeholders: <code>{name}</code>, <code>{email}</code>, <code>{description}</code>, <code>{media_count}</code>, <code>{media_html}</code>, <code>{admin_url}</code>, <code>{site_name}</code>, <code>{edit_link}</code>.', WRUGC_TEXT_DOMAIN)) . '</span>';
}

/**
 * Render user status update email message setting.
 */
function wrugc_user_status_update_message_callback()
{
	$content = get_option('user_status_update_message');

	echo '<textarea name="user_status_update_message" rows="6" class="large-text" placeholder="' . esc_attr__('Enter message for user when status is updated', WRUGC_TEXT_DOMAIN) . '">' . esc_textarea($content) . '</textarea>';
	echo '<br><span style="color:#777; font-size:13px; display:inline-block; margin-top:6px;">' . wp_kses_post(__('Available placeholders: <code>{name}</code>, <code>{status}</code>.', WRUGC_TEXT_DOMAIN)) . '</span>';
}

/**
 * Build branded HTML email template.
 *
 * @param string $subject Subject.
 * @param string $body_content_html Body HTML.
 * @return string
 */
function wrugc_build_email_template($subject, $body_content_html)
{
	$company_logo_url = 'https://woodrichbrand.com/wp-content/uploads/2019/05/WoodrichLOGOpng2.png';

	return sprintf(
		'<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<title>%s</title>
			<style>
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
				.header { text-align: center; margin-bottom: 20px; }
				.logo { max-height: 100px; }
				.content { background: #f9f9f9; padding: 20px; border-radius: 5px; }
				.footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; }
			</style>
		</head>
		<body>
			<div class="header">
				<img src="%s" alt="Woodrich Brand" class="logo" style="max-height:100px;">
			</div>
			<div class="content">%s</div>
			<div class="footer"><p>This is an automated email from %s</p></div>
		</body>
		</html>',
		esc_html($subject),
		esc_url($company_logo_url),
		wp_kses_post($body_content_html),
		esc_html(get_bloginfo('name'))
	);
}

/**
 * Send an email when a submission is published.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post Post object.
 */
function wrugc_send_email_on_publish($new_status, $old_status, $post)
{
	if (!wrugc_email_notifications_enabled()) {
		return;
	}

	if ('publish' === $old_status || 'publish' !== $new_status || WRUGC_POST_TYPE !== $post->post_type) {
		return;
	}

	$user_email = wrugc_get_field_value('ugc_email', $post->ID);
	$user_name  = wrugc_get_field_value('ugc_name', $post->ID);

	if (!$user_email || !is_email($user_email)) {
		return;
	}

	$template = get_option('user_status_update_message');

	if (!$template) {
		return;
	}

	$plain_text = strtr(
		$template,
		[
			'{name}'   => $user_name ?: __('User', WRUGC_TEXT_DOMAIN),
			'{status}' => __('Published', WRUGC_TEXT_DOMAIN),
		]
	);

	$body_html = '<p>' . nl2br(esc_html($plain_text)) . '</p>';
	$message   = wrugc_build_email_template(__('Your Questionnaire Has Been Published', WRUGC_TEXT_DOMAIN), $body_html);
	$headers   = [
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . get_bloginfo('name') . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
	];

	wp_mail($user_email, __('Your Questionnaire Has Been Published', WRUGC_TEXT_DOMAIN), $message, $headers);
}
add_action('transition_post_status', 'wrugc_send_email_on_publish', 10, 3);

/**
 * Send the admin submission notification email.
 *
 * @param string $name Submitter name.
 * @param string $email Submitter email.
 * @param string $description Submission description.
 * @param array  $media_urls Media rows.
 * @param int    $post_id Post ID.
 */
function wrugc_send_gallery_submission_email($name, $email, $description, $media_urls, $post_id = null)
{
	if (!wrugc_email_notifications_enabled()) {
		return;
	}

	$template = get_option('admin_email_message');

	if (!$template) {
		return;
	}

	$media_html = '';

	foreach ($media_urls as $media) {
		if (empty($media['file_url'])) {
			continue;
		}

		$url      = esc_url($media['file_url']);
		$type     = wrugc_get_media_type_from_url($url);
		$link_url = $post_id ? admin_url("post.php?post={$post_id}&action=edit") : $url;

		if ('image' === $type) {
			$media_html .= sprintf(
				'<a href="%s" target="_blank" style="display:inline-block; margin:5px;"><img src="%s" alt="Submission" style="width:100px; height:100px; object-fit:cover; border-radius:4px; border:1px solid #ddd;"></a>',
				esc_url($link_url),
				esc_url($url)
			);
		} elseif ('video' === $type) {
			$media_html .= sprintf(
				'<a href="%s" target="_blank" style="display:inline-block; margin:5px;"><img src="https://img.icons8.com/color/96/video.png" alt="Video" style="width:100px; height:100px; object-fit:cover; border-radius:4px; border:1px solid #ddd;"></a>',
				esc_url($link_url)
			);
		}
	}

	$content = strtr(
		$template,
		[
			'{name}'        => $name ?: __('User', WRUGC_TEXT_DOMAIN),
			'{email}'       => $email,
			'{description}' => $description,
			'{media_count}' => count($media_urls),
			'{media_html}'  => $media_html,
			'{admin_url}'   => admin_url('edit.php?post_type=' . WRUGC_POST_TYPE),
			'{site_name}'   => get_bloginfo('name'),
			'{logo_url}'    => 'https://woodrichbrand.com/wp-content/uploads/2019/05/WoodrichLOGOpng2.png',
			'{edit_link}'   => $post_id ? admin_url("post.php?post={$post_id}&action=edit") : '',
		]
	);

	$body_html = wpautop(wp_kses_post($content));
	$message   = wrugc_build_email_template(__('New Questionnaire Submission', WRUGC_TEXT_DOMAIN), $body_html);
	$headers   = [
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . get_bloginfo('name') . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
		'Reply-To: ' . sanitize_email($email),
	];

	$notification_emails = get_option('notification_emails');
	$to                  = $notification_emails
		? array_filter(array_map('trim', explode(',', $notification_emails)), 'is_email')
		: [get_option('admin_email')];

	if (!empty($to)) {
		wp_mail($to, __('New Questionnaire Submission', WRUGC_TEXT_DOMAIN), $message, $headers);
	}
}

/**
 * Add CSV export submenu.
 */
function wrugc_add_export_submenu()
{
	add_submenu_page(
		'edit.php?post_type=' . WRUGC_POST_TYPE,
		__('Export Submissions CSV', WRUGC_TEXT_DOMAIN),
		__('Export Submissions CSV', WRUGC_TEXT_DOMAIN),
		'manage_options',
		'wrugc-export-questionnaire-csv',
		'wrugc_export_submenu_page'
	);
}
add_action('admin_menu', 'wrugc_add_export_submenu');

/**
 * Render CSV export page.
 */
function wrugc_export_submenu_page()
{
	$export_url = wp_nonce_url(
		admin_url('admin-post.php?action=wrugc_export_questionnaire_csv'),
		'wrugc_export_questionnaire_csv'
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Export Submissions Data', WRUGC_TEXT_DOMAIN); ?></h1>
		<p><?php esc_html_e('Click below to download all published submissions as CSV.', WRUGC_TEXT_DOMAIN); ?></p>
		<a class="button button-primary" href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('Download CSV', WRUGC_TEXT_DOMAIN); ?></a>
	</div>
	<?php
}

/**
 * Export submissions as CSV.
 */
function wrugc_export_questionnaire_csv_now()
{
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('Unauthorized', WRUGC_TEXT_DOMAIN));
	}

	check_admin_referer('wrugc_export_questionnaire_csv');

	if (ob_get_length()) {
		ob_end_clean();
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="questionnaire_data.csv"');
	header('Pragma: no-cache');
	header('Expires: 0');

	$output = fopen('php://output', 'w');

	fputcsv(
		$output,
		[
			'Name',
			'Email',
			'Street Address',
			'Apartment/Suite',
			'City',
			'State',
			'Zip Code',
			'Phone',
			'Project Type',
			'Project Description',
			'Wood Type',
			'Other Wood Type',
			'Products Used',
			'Colors Used',
			'Media URLs',
		]
	);

	$posts = get_posts(
		[
			'post_type'      => WRUGC_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		]
	);

	foreach ($posts as $post) {
		$urls       = wrugc_get_media_rows('ugc_media_urls', $post->ID);
		$media_urls = [];

		foreach ($urls as $item) {
			if (!empty($item['file_url'])) {
				$media_urls[] = $item['file_url'];
			}
		}

		fputcsv(
			$output,
			[
				wrugc_get_field_value('ugc_name', $post->ID),
				wrugc_get_field_value('ugc_email', $post->ID),
				wrugc_get_field_value('ugc_street_address', $post->ID),
				wrugc_get_field_value('ugc_address_optional', $post->ID),
				wrugc_get_field_value('ugc_city', $post->ID),
				wrugc_get_field_value('ugc_state', $post->ID),
				wrugc_get_field_value('ugc_zip_code', $post->ID),
				wrugc_get_field_value('ugc_phone', $post->ID),
				wrugc_get_field_value('ugc_project_type', $post->ID),
				wrugc_get_field_value('ugc_project_description', $post->ID),
				wrugc_get_field_value('ugc_wood_type', $post->ID),
				wrugc_get_field_value('ugc_wood_type_other', $post->ID),
				wrugc_get_field_value('ugc_products_used', $post->ID),
				wrugc_get_field_value('ugc_colors_used', $post->ID),
				implode(', ', $media_urls),
			]
		);
	}

	fclose($output);
	exit;
}
add_action('admin_post_wrugc_export_questionnaire_csv', 'wrugc_export_questionnaire_csv_now');

/**
 * Build gallery data for the shortcode and AJAX endpoint.
 *
 * @param array $args Gallery args.
 * @return array{items:array,total_pages:int}
 */
function wrugc_get_gallery_data($args)
{
	$search_term = isset($args['search']) ? sanitize_text_field($args['search']) : '';
	$meta_query  = [];

	if ($search_term) {
		$meta_query = [
			'relation' => 'OR',
			[
				'key'     => 'ugc_project_type',
				'value'   => $search_term,
				'compare' => 'LIKE',
			],
			[
				'key'     => 'ugc_wood_type',
				'value'   => $search_term,
				'compare' => 'LIKE',
			],
			[
				'key'     => 'ugc_products_used',
				'value'   => $search_term,
				'compare' => 'LIKE',
			],
			[
				'key'     => 'ugc_colors_used',
				'value'   => $search_term,
				'compare' => 'LIKE',
			],
		];
	}

	$query_args = [
		'post_type'      => $args['post_type'],
		'posts_per_page' => -1,
		'post_status'    => $args['post_status'],
		'orderby'        => $args['orderby'],
		'order'          => $args['order'],
	];

	if ($meta_query) {
		$query_args['meta_query'] = $meta_query;
	}

	$gallery_query = new WP_Query($query_args);
	$all_items     = [];

	while ($gallery_query->have_posts()) {
		$gallery_query->the_post();

		$post_id       = get_the_ID();
		$thumbnail_url = wrugc_get_field_value('ugc_thumbnail_url', $post_id);
		$description   = wrugc_get_field_value('ugc_project_description', $post_id);

		if (!$thumbnail_url) {
			$media_rows = wrugc_get_media_rows('ugc_media_urls', $post_id);
			$first_row  = reset($media_rows);

			if (!empty($first_row['file_url'])) {
				$thumbnail_url = $first_row['file_url'];
			}
		}

		if (!$thumbnail_url) {
			continue;
		}

		$file_url = esc_url_raw($thumbnail_url);
		$type     = wrugc_get_media_type_from_url($file_url);

		if (!$type || (!empty($args['exclude_videos']) && 'video' === $type)) {
			continue;
		}

		$all_items[] = [
			'url'         => $file_url,
			'type'        => $type,
			'post_id'     => $post_id,
			'title'       => get_the_title(),
			'description' => $description,
			'link'        => get_permalink(),
		];
	}

	wp_reset_postdata();

	$images_per_page   = max(1, (int) $args['images_per_page']);
	$paged             = max(1, (int) $args['paged']);
	$image_start_index = ($paged - 1) * $images_per_page;
	$paged_items       = array_slice($all_items, $image_start_index, $images_per_page);
	$total_pages       = (int) ceil(count($all_items) / $images_per_page);

	return [
		'items'       => $paged_items,
		'total_pages' => max(1, $total_pages),
	];
}

/**
 * Render gallery item cards.
 *
 * @param array $items Gallery items.
 * @return string
 */
function wrugc_render_gallery_items($items)
{
	ob_start();

	if (count($items) > 0) {
		foreach ($items as $item) {
			if ($item['description']) {
				$short_description = function_exists('mb_strimwidth')
					? mb_strimwidth($item['description'], 0, 120, '...')
					: wp_trim_words($item['description'], 20, '...');
			} else {
				$short_description = __('No description available', WRUGC_TEXT_DOMAIN);
			}
			?>
			<div class="gallery-item gallery-item-link">
				<a href="<?php echo esc_url($item['link']); ?>">
					<div class="gallery-item-thumbnail">
						<?php if ('image' === $item['type']) : ?>
							<img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="lazy">
						<?php elseif ('video' === $item['type']) : ?>
							<div class="video-thumb">
								<video src="<?php echo esc_url($item['url']); ?>" muted preload="metadata"></video>
								<span class="play-button" aria-hidden="true">&#9658;</span>
							</div>
						<?php else : ?>
							<div class="gallery-file-fallback">
								<span><?php esc_html_e('View File', WRUGC_TEXT_DOMAIN); ?></span>
							</div>
						<?php endif; ?>
					</div>
					<div class="gallery-item-content">
						<h3><?php echo esc_html($short_description); ?></h3>
					</div>
				</a>
			</div>
			<?php
		}
	} else {
		echo '<p class="wrugc-gallery-empty">' . esc_html__('No gallery items found.', WRUGC_TEXT_DOMAIN) . '</p>';
	}

	return ob_get_clean();
}

/**
 * Display custom gallery.
 *
 * @param array $args Gallery args.
 * @return string
 */
function wrugc_display_custom_gallery($args = [])
{
	$defaults = [
		'post_type'       => WRUGC_POST_TYPE,
		'posts_per_page'  => -1,
		'post_status'     => 'publish',
		'exclude_videos'  => false,
		'images_per_page' => 12,
		'paged'           => 1,
		'search'          => '',
		'orderby'         => 'date',
		'order'           => 'DESC',
		'ajax'            => false,
	];

	$args = wp_parse_args($args, $defaults);
	$data = wrugc_get_gallery_data($args);

	if (!empty($args['ajax'])) {
		return wrugc_render_gallery_items($data['items']);
	}

	ob_start();
	?>
	<div id="custom-gallery-wrapper">
		<div class="gallery-search-container">
			<form class="gallery-search-form" id="gallery-search-form">
				<input type="search" name="gallery_search" placeholder="<?php esc_attr_e('Search', WRUGC_TEXT_DOMAIN); ?>" value="<?php echo esc_attr($args['search']); ?>">
				<button type="submit"><?php esc_html_e('Search', WRUGC_TEXT_DOMAIN); ?></button>
				<button type="button" id="clear-search" aria-label="<?php esc_attr_e('Clear search', WRUGC_TEXT_DOMAIN); ?>">&times;</button>
			</form>
		</div>

		<div class="search-results-info" <?php echo empty($args['search']) ? 'style="display:none;"' : ''; ?>>
			<?php
			if (!empty($args['search'])) {
				printf(
					/* translators: %s is the search term. */
					esc_html__('Showing results for: "%s"', WRUGC_TEXT_DOMAIN),
					esc_html($args['search'])
				);
			}
			?>
		</div>

		<div class="custom-gallery-container">
			<?php echo wrugc_render_gallery_items($data['items']); ?>
		</div>

		<div class="load-more-wrap" <?php echo $data['total_pages'] > 1 ? '' : 'style="display:none;"'; ?>>
			<button id="load-more-btn" data-page="<?php echo esc_attr((int) $args['paged']); ?>" data-max="<?php echo esc_attr($data['total_pages']); ?>" data-ppp="<?php echo esc_attr((int) $args['images_per_page']); ?>">
				<?php esc_html_e('Load More', WRUGC_TEXT_DOMAIN); ?>
			</button>
			<div id="gallery-loader" style="display:none;">
				<div class="spinner"></div>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * AJAX endpoint for gallery pagination/search.
 */
function wrugc_ajax_load_more_gallery()
{
	check_ajax_referer('load_more_gallery_nonce', 'security');

	$paged       = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
	$ppp         = isset($_POST['posts_per_page']) ? max(1, intval($_POST['posts_per_page'])) : 12;
	$search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
	$data        = wrugc_get_gallery_data(
		[
			'paged'           => $paged,
			'images_per_page' => $ppp,
			'orderby'         => 'date',
			'order'           => 'DESC',
			'exclude_videos'  => false,
			'search'          => $search_term,
			'post_type'       => WRUGC_POST_TYPE,
			'post_status'     => 'publish',
		]
	);

	wp_send_json_success(
		[
			'html'        => wrugc_render_gallery_items($data['items']),
			'total_pages' => $data['total_pages'],
		]
	);
}
add_action('wp_ajax_load_more_gallery', 'wrugc_ajax_load_more_gallery');
add_action('wp_ajax_nopriv_load_more_gallery', 'wrugc_ajax_load_more_gallery');

/**
 * Custom gallery shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function wrugc_custom_gallery_shortcode($atts)
{
	$atts = shortcode_atts(
		[
			'images_per_page' => 12,
			'orderby'         => 'date',
			'order'           => 'DESC',
			'exclude_videos'  => false,
			'paged'           => 1,
			'search'          => '',
		],
		$atts
	);

	if (get_query_var('paged')) {
		$atts['paged'] = get_query_var('paged');
	} elseif (isset($_GET['paged'])) {
		$atts['paged'] = intval($_GET['paged']);
	}

	if (isset($_GET['gallery_search'])) {
		$atts['search'] = sanitize_text_field(wp_unslash($_GET['gallery_search']));
	}

	return wrugc_display_custom_gallery($atts);
}
add_shortcode('custom_gallery', 'wrugc_custom_gallery_shortcode');

/**
 * Enqueue gallery AJAX script.
 */
function wrugc_enqueue_gallery_scripts()
{
	wp_enqueue_script('jquery');

	wp_register_script('custom-gallery-ajax', '', ['jquery'], null, true);
	wp_enqueue_script('custom-gallery-ajax');

	wp_localize_script(
		'custom-gallery-ajax',
		'gallery_ajax_obj',
		[
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('load_more_gallery_nonce'),
		]
	);

	$script = <<<'JS'
	jQuery(document).ready(function($) {
		$('#gallery-search-form').on('submit', function(e) {
			e.preventDefault();
			var searchTerm = $(this).find('input[name="gallery_search"]').val();
			loadGalleryItems(1, searchTerm, $('#load-more-btn').data('ppp') || 12, false);
		});

		$('#clear-search').on('click', function(e) {
			e.preventDefault();
			$('#gallery-search-form input[name="gallery_search"]').val('');
			loadGalleryItems(1, '', $('#load-more-btn').data('ppp') || 12, false);
		});

		$('#gallery-search-form input').on('input', function() {
			$('#clear-search').toggle($(this).val().length > 0);
		}).trigger('input');

		$(document).on('click', '#load-more-btn', function(e) {
			e.preventDefault();
			var button = $(this);
			var page = parseInt(button.attr('data-page'), 10) + 1;
			var ppp = parseInt(button.attr('data-ppp'), 10);
			var searchTerm = $('#gallery-search-form input[name="gallery_search"]').val();

			loadGalleryItems(page, searchTerm, ppp, true, button);
		});

		function loadGalleryItems(page, searchTerm, ppp, append, button) {
			var container = $('.custom-gallery-container');
			var loader = $('#gallery-loader');
			var loadMoreWrap = $('.load-more-wrap');
			var loadMoreBtn = $('#load-more-btn');

			if (page === 1) {
				container.html('');
			}

			if (button) {
				button.prop('disabled', true).text('Loading...');
			}

			loader.show();

			$.ajax({
				url: gallery_ajax_obj.ajax_url,
				type: 'POST',
				data: {
					action: 'load_more_gallery',
					page: page,
					posts_per_page: ppp,
					search: searchTerm,
					security: gallery_ajax_obj.nonce
				},
				success: function(response) {
					if (response && response.success) {
						var html = response.data.html || '';
						var totalPages = parseInt(response.data.total_pages, 10) || 1;

						if (append) {
							container.append(html);
						} else {
							container.html(html);
						}

						if (searchTerm) {
							$('.search-results-info').text('Showing results for: "' + searchTerm + '"').show();
						} else {
							$('.search-results-info').hide();
						}

						loadMoreBtn.attr('data-page', page).attr('data-max', totalPages).attr('data-ppp', ppp);

						if (page < totalPages) {
							loadMoreWrap.show();
							loadMoreBtn.show().prop('disabled', false).text('Load More');
						} else {
							loadMoreWrap.hide();
						}
					}
				},
				error: function(xhr, status, error) {
					window.console && console.error('Gallery AJAX error:', status, error);
				},
				complete: function() {
					loader.hide();

					if (button) {
						button.prop('disabled', false).text('Load More');
					}
				}
			});
		}
	});
JS;

	wp_add_inline_script('custom-gallery-ajax', $script);
}
add_action('wp_enqueue_scripts', 'wrugc_enqueue_gallery_scripts');
