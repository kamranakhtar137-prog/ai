<?php
/**
 * Admin product settings.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Admin {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_regenerate_import_token' ) );
	}

	/**
	 * Regenerate import token when requested from product admin.
	 */
	public static function maybe_regenerate_import_token() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['wcbc_regenerate_token'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'wcbc_regenerate_import_token' );

		if ( class_exists( 'WCBC_Cache_API' ) ) {
			WCBC_Cache_API::import_token( true );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$redirect = $post_id
			? add_query_arg(
				array(
					'post'             => $post_id,
					'action'           => 'edit',
					'wcbc_token_reset' => '1',
				),
				admin_url( 'post.php' )
			)
			: admin_url( 'edit.php?post_type=product' );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Enqueue media library scripts on product edit screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wcbc-admin', WCBC_PLUGIN_URL . 'assets/css/admin.css', array(), WCBC_VERSION );
		wp_enqueue_script( 'wcbc-admin-media', WCBC_PLUGIN_URL . 'assets/js/admin-media.js', array( 'jquery' ), WCBC_VERSION, true );
	}

	/**
	 * Add product data tab.
	 *
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public static function add_tab( $tabs ) {
		$tabs['wcbc'] = array(
			'label'    => __( 'Bed Configurator', 'wc-bed-configurator' ),
			'target'   => 'wcbc_product_data',
			'class'    => array( 'show_if_simple' ),
			'priority' => 65,
		);
		return $tabs;
	}

	/**
	 * Render admin panel.
	 */
	public static function render_panel() {
		global $post;
		$enabled = get_post_meta( $post->ID, WCBC_Config::ENABLED_KEY, true ) === 'yes';
		$config  = WCBC_Config::get_product_config( $post->ID );
		$json    = wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$import_script     = WCBC_Cache_API::import_script();
		$import_all_script = WCBC_Cache_API::import_all_script();
		$import_core_script = WCBC_Cache_API::import_core_script();
		$layer_media       = WCBC_Config::get_layer_media( $post->ID );
		$image_source      = WCBC_Config::get_image_source( $post->ID );
		$layer_labels      = WCBC_Config::get_layer_labels();
		$variation_driven  = array_flip( WCBC_Config::variation_driven_layers() );
		$static_layers     = array_flip( WCBC_Config::static_override_layers() );
		?>
		<div id="wcbc_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => 'wcbc_enabled',
						'label'       => __( 'Enable Bed Configurator', 'wc-bed-configurator' ),
						'description' => __( 'Replace the standard add-to-cart area with the BYOB configurator.', 'wc-bed-configurator' ),
						'value'       => $enabled ? 'yes' : 'no',
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'                => 'wcbc_base_price',
						'label'             => __( 'Configurator base price', 'wc-bed-configurator' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'step' => '0.01',
							'min'  => '0',
						),
						'value'             => $config['base_price'],
						'description'       => __( 'Base price before option modifiers. Leave empty to use product regular price.', 'wc-bed-configurator' ),
					)
				);
				woocommerce_wp_select(
					array(
						'id'          => 'wcbc_image_source',
						'label'       => __( 'Preview image source', 'wc-bed-configurator' ),
						'value'       => $image_source,
						'options'     => array(
							'demo'      => __( 'Bundled layers (recommended)', 'wc-bed-configurator' ),
							'auto'      => __( 'Auto — bundled demo layers', 'wc-bed-configurator' ),
							'happybeds' => __( 'Happy Beds CDN / cache', 'wc-bed-configurator' ),
							'media'     => __( 'WordPress Media Library only', 'wc-bed-configurator' ),
							'hybrid'    => __( 'Bundled layers + Media Library overrides', 'wc-bed-configurator' ),
						),
						'description' => __( 'Bundled layers update headboard, base, and storage automatically when colour, size, depth, or storage changes. Happy Beds mode requires importing images separately.', 'wc-bed-configurator' ),
					)
				);
				?>
				<div class="wcbc-layer-media-section options_group">
					<p class="form-field">
						<strong><?php esc_html_e( 'Preview layer images (Media Library)', 'wc-bed-configurator' ); ?></strong><br />
						<span class="description">
							<?php esc_html_e( 'Shadow and legs can use a single uploaded image in Hybrid mode. Headboard, base, and storage layers are variation-driven — one preview slot loads many different image files depending on size, colour, depth, headboard style, and storage (same as Happy Beds).', 'wc-bed-configurator' ); ?>
						</span>
					</p>
					<div class="wcbc-layer-media-grid">
						<?php foreach ( WCBC_Config::get_layers() as $layer ) : ?>
							<?php
							$attachment_id = isset( $layer_media[ $layer ] ) ? (int) $layer_media[ $layer ] : 0;
							$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
							$label         = isset( $layer_labels[ $layer ] ) ? $layer_labels[ $layer ] : $layer;
							$is_dynamic    = isset( $variation_driven[ $layer ] );
							$can_upload    = isset( $static_layers[ $layer ] ) || 'media' === $image_source;
							?>
							<div class="wcbc-layer-media-item <?php echo $is_dynamic ? 'is-variation-driven' : ''; ?>">
								<label>
									<?php echo esc_html( $label ); ?>
									<?php if ( $is_dynamic ) : ?>
										<br /><span class="description"><?php esc_html_e( 'Varies by selection', 'wc-bed-configurator' ); ?></span>
									<?php endif; ?>
								</label>
								<img
									class="wcbc-layer-media-preview <?php echo $preview_url ? '' : 'is-empty'; ?>"
									src="<?php echo esc_url( $preview_url ); ?>"
									alt=""
								/>
								<input type="hidden" name="wcbc_layer_media[<?php echo esc_attr( $layer ); ?>]" value="<?php echo esc_attr( $attachment_id ); ?>" />
								<?php if ( $can_upload ) : ?>
								<button type="button" class="button wcbc-upload-layer" data-layer="<?php echo esc_attr( $layer ); ?>" data-title="<?php echo esc_attr( $label ); ?>">
									<?php esc_html_e( 'Select image', 'wc-bed-configurator' ); ?>
								</button>
								<button type="button" class="button wcbc-remove-layer" <?php echo $attachment_id ? '' : 'style="display:none"'; ?>>
									<?php esc_html_e( 'Remove', 'wc-bed-configurator' ); ?>
								</button>
								<?php else : ?>
								<p class="description"><?php esc_html_e( 'Resolved automatically from bundled layer images.', 'wc-bed-configurator' ); ?></p>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="options_group">
					<p class="form-field">
						<strong><?php esc_html_e( 'Option swatch images (Media Library)', 'wc-bed-configurator' ); ?></strong><br />
						<span class="description"><?php esc_html_e( 'Set swatch thumbnails for each configurator option. Saves into the JSON config automatically.', 'wc-bed-configurator' ); ?></span>
					</p>
					<?php foreach ( $config['groups'] as $group ) : ?>
						<h4 style="margin:16px 0 8px;padding:0 12px;"><?php echo esc_html( $group['label'] ); ?></h4>
						<table class="wcbc-option-media-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Option', 'wc-bed-configurator' ); ?></th>
									<th><?php esc_html_e( 'Swatch', 'wc-bed-configurator' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'wc-bed-configurator' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $group['options'] as $option ) : ?>
									<?php
									$img_url = ! empty( $option['image'] ) ? $option['image'] : '';
									$img_id  = $img_url ? attachment_url_to_postid( $img_url ) : 0;
									?>
									<tr>
										<td><?php echo esc_html( trim( $option['label'] . ' ' . $option['sublabel'] ) ); ?></td>
										<td>
											<img class="wcbc-option-thumb <?php echo $img_url ? '' : 'is-empty'; ?>" src="<?php echo esc_url( $img_url ); ?>" alt="" />
										</td>
										<td>
											<input type="hidden" class="wcbc-option-image-id" name="wcbc_option_images[<?php echo esc_attr( $group['id'] ); ?>][<?php echo esc_attr( $option['id'] ); ?>]" value="<?php echo esc_attr( $img_id ); ?>" />
											<button type="button" class="button wcbc-upload-option-image" data-group="<?php echo esc_attr( $group['id'] ); ?>" data-option="<?php echo esc_attr( $option['id'] ); ?>">
												<?php esc_html_e( 'Select', 'wc-bed-configurator' ); ?>
											</button>
											<button type="button" class="button wcbc-remove-option-image" data-group="<?php echo esc_attr( $group['id'] ); ?>" data-option="<?php echo esc_attr( $option['id'] ); ?>" <?php echo $img_id ? '' : 'style="display:none"'; ?>>
												<?php esc_html_e( 'Remove', 'wc-bed-configurator' ); ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endforeach; ?>
				</div>
				<p class="form-field">
					<label for="wcbc_config_json"><?php esc_html_e( 'Configurator JSON (advanced)', 'wc-bed-configurator' ); ?></label>
					<textarea id="wcbc_config_json" name="wcbc_config_json" rows="18" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $json ); ?></textarea>
					<span class="description"><?php esc_html_e( 'Edit groups, options, images, and default selections. Invalid JSON is ignored on save.', 'wc-bed-configurator' ); ?></span>
				</p>
				<p class="form-field">
					<strong><?php esc_html_e( 'Happy Beds images — dynamic variations', 'wc-bed-configurator' ); ?></strong><br />
					<?php esc_html_e( 'Core preview layers (one slot each, many image files per slot):', 'wc-bed-configurator' ); ?>
					<strong><?php esc_html_e( 'Bed Legs', 'wc-bed-configurator' ); ?></strong>,
					<strong><?php esc_html_e( 'Bed Headboard', 'wc-bed-configurator' ); ?></strong>,
					<strong><?php esc_html_e( 'Bed Storage Back', 'wc-bed-configurator' ); ?></strong>,
					<strong><?php esc_html_e( 'Bed Base', 'wc-bed-configurator' ); ?></strong>.
					<?php esc_html_e( 'Image file changes when customer picks size, colour, or base depth.', 'wc-bed-configurator' ); ?>
					<br /><br />
					<strong><?php esc_html_e( 'Quick import (25 files)', 'wc-bed-configurator' ); ?></strong> —
					<?php esc_html_e( 'Beige velvet, Cornell lined, 14 inch, 2 drawers only. Good for testing one look per size.', 'wc-bed-configurator' ); ?>
					<br />
					<textarea id="wcbc_import_script" readonly rows="8" style="width:100%;font-family:monospace;font-size:11px;"><?php echo esc_textarea( $import_script ); ?></textarea>
					<button type="button" class="button" id="wcbc-copy-import-script"><?php esc_html_e( 'Copy quick import', 'wc-bed-configurator' ); ?></button>
					<br /><br />
					<strong style="color:#047857;"><?php esc_html_e( 'Core import (recommended first)', 'wc-bed-configurator' ); ?></strong> —
					<?php esc_html_e( 'All 4 core layers for every size × colour × base depth (Cornell lined, 2 drawers). ~3–5 min.', 'wc-bed-configurator' ); ?>
					<br />
					<textarea id="wcbc_import_core_script" readonly rows="8" style="width:100%;font-family:monospace;font-size:11px;"><?php echo esc_textarea( $import_core_script ); ?></textarea>
					<button type="button" class="button button-primary" id="wcbc-copy-import-core-script"><?php esc_html_e( 'Copy core import (4 layers)', 'wc-bed-configurator' ); ?></button>
					<br /><br />
					<strong style="color:#b45309;"><?php esc_html_e( 'Full import', 'wc-bed-configurator' ); ?></strong> —
					<?php esc_html_e( 'All colours, headboards, depths & storage options. Takes ~10–20 min on happybeds.co.uk Console.', 'wc-bed-configurator' ); ?>
					<br />
					<textarea id="wcbc_import_all_script" readonly rows="8" style="width:100%;font-family:monospace;font-size:11px;"><?php echo esc_textarea( $import_all_script ); ?></textarea>
					<button type="button" class="button" id="wcbc-copy-import-all-script"><?php esc_html_e( 'Copy full import (all variations)', 'wc-bed-configurator' ); ?></button>
					<br /><br />
					<span class="description" style="color:#b45309;">
						<?php esc_html_e( '401 Unauthorized? Copy a fresh script below — the token must match your site. Re-copy after every plugin update.', 'wc-bed-configurator' ); ?>
					</span>
					<br />
					<?php
					$regen_url = wp_nonce_url(
						add_query_arg(
							array(
								'post'                  => $post->ID,
								'action'                => 'edit',
								'wcbc_regenerate_token' => '1',
							),
							admin_url( 'post.php' )
						),
						'wcbc_regenerate_import_token'
					);
					?>
					<a href="<?php echo esc_url( $regen_url ); ?>" class="button"><?php esc_html_e( 'Regenerate import token', 'wc-bed-configurator' ); ?></a>
					<?php if ( isset( $_GET['wcbc_token_reset'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
						<span style="color:green;margin-left:8px;"><?php esc_html_e( 'Token regenerated — copy a new import script above.', 'wc-bed-configurator' ); ?></span>
					<?php endif; ?>
					<br /><br />
					<?php esc_html_e( 'Paste on https://www.happybeds.co.uk/build-your-own-bed → DevTools Console → Enter.', 'wc-bed-configurator' ); ?>
					<br />
					<?php
					printf(
						esc_html__( 'Cache status: %s', 'wc-bed-configurator' ),
						WCBC_Layer_Serve::cache_has_files()
							? '<span style="color:green;">' . esc_html__( 'imported', 'wc-bed-configurator' ) . '</span>'
							: '<span style="color:#b45309;">' . esc_html__( 'not imported yet', 'wc-bed-configurator' ) . '</span>'
					);
					?>
				</p>
				<p class="form-field">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcbc-docs' ) ); ?>" class="button" onclick="alert('See plugin README.md for JSON schema and demo images in demo-images/ folder.');return false;">
						<?php esc_html_e( 'View documentation', 'wc-bed-configurator' ); ?>
					</a>
					<button type="button" class="button" id="wcbc-reset-defaults"><?php esc_html_e( 'Reset to demo defaults', 'wc-bed-configurator' ); ?></button>
				</p>
			</div>
		</div>
		<script>
		(function(){
			var defaults = <?php echo wp_json_encode( WCBC_Config::get_default_config() ); ?>;
			document.getElementById('wcbc-reset-defaults')?.addEventListener('click', function(){
				if (confirm('Reset configurator JSON to plugin demo defaults?')) {
					document.getElementById('wcbc_config_json').value = JSON.stringify(defaults, null, 2);
				}
			});
			document.getElementById('wcbc-copy-import-script')?.addEventListener('click', function(){
				copyTextarea('wcbc_import_script', 'Quick import copied. Paste on happybeds.co.uk Console.');
			});
			document.getElementById('wcbc-copy-import-core-script')?.addEventListener('click', function(){
				copyTextarea('wcbc_import_core_script', 'Core import copied. Paste on happybeds.co.uk Console.');
			});
			document.getElementById('wcbc-copy-import-all-script')?.addEventListener('click', function(){
				copyTextarea('wcbc_import_all_script', 'Full import copied. Paste on happybeds.co.uk Console. Wait for FULL import complete.');
			});
			function copyTextarea(id, msg) {
				var ta = document.getElementById(id);
				if (!ta) return;
				ta.select();
				ta.setSelectionRange(0, 99999);
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(ta.value).then(function(){ alert(msg); });
				} else {
					document.execCommand('copy');
					alert(msg);
				}
			}
		})();
		</script>
		<?php
	}

	/**
	 * Save product meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save( $post_id ) {
		$enabled = isset( $_POST['wcbc_enabled'] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification
		update_post_meta( $post_id, WCBC_Config::ENABLED_KEY, $enabled );

		if ( isset( $_POST['wcbc_image_source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$source = sanitize_text_field( wp_unslash( $_POST['wcbc_image_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( in_array( $source, array( 'demo', 'auto', 'happybeds', 'media', 'hybrid' ), true ) ) {
				update_post_meta( $post_id, WCBC_Config::IMAGE_SOURCE_KEY, $source );
			}
		}

		if ( isset( $_POST['wcbc_layer_media'] ) && is_array( $_POST['wcbc_layer_media'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$layer_media = array();
			foreach ( WCBC_Config::get_layers() as $layer ) {
				if ( ! empty( $_POST['wcbc_layer_media'][ $layer ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$layer_media[ $layer ] = absint( wp_unslash( $_POST['wcbc_layer_media'][ $layer ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}
			update_post_meta( $post_id, WCBC_Config::LAYER_MEDIA_KEY, $layer_media );
		}

		if ( isset( $_POST['wcbc_base_price'] ) && '' !== $_POST['wcbc_base_price'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$base = (float) wc_clean( wp_unslash( $_POST['wcbc_base_price'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$config = null;
		if ( ! empty( $_POST['wcbc_config_json'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$raw    = wp_unslash( $_POST['wcbc_config_json'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$config = json_decode( $raw, true );
		}

		if ( ! is_array( $config ) || empty( $config['groups'] ) ) {
			$config = WCBC_Config::get_product_config( $post_id );
			unset( $config['product_id'], $config['layer_media'], $config['image_source'], $config['layers'] );
		}

		if ( isset( $_POST['wcbc_option_images'] ) && is_array( $_POST['wcbc_option_images'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$config = self::apply_option_images_from_post( $config, $_POST['wcbc_option_images'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( isset( $base ) ) {
			$config['base_price'] = $base;
		}

		if ( ! empty( $config['groups'] ) ) {
			update_post_meta( $post_id, WCBC_Config::META_KEY, $config );
		}
	}

	/**
	 * Apply media library swatch selections to config options.
	 *
	 * @param array<string,mixed> $config Config.
	 * @param array<string,mixed> $posted Posted option image ids.
	 * @return array<string,mixed>
	 */
	private static function apply_option_images_from_post( $config, $posted ) {
		foreach ( $config['groups'] as $gi => $group ) {
			$gid = $group['id'];
			if ( empty( $posted[ $gid ] ) || ! is_array( $posted[ $gid ] ) ) {
				continue;
			}
			foreach ( $group['options'] as $oi => $option ) {
				$oid = $option['id'];
				if ( empty( $posted[ $gid ][ $oid ] ) ) {
					continue;
				}
				$url = wp_get_attachment_image_url( absint( $posted[ $gid ][ $oid ] ), 'full' );
				if ( $url ) {
					$config['groups'][ $gi ]['options'][ $oi ]['image'] = $url;
				}
			}
		}
		return $config;
	}
}
