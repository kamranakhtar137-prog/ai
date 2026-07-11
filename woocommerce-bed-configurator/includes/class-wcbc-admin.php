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
				?>
				<p class="form-field">
					<label for="wcbc_config_json"><?php esc_html_e( 'Configurator JSON (advanced)', 'wc-bed-configurator' ); ?></label>
					<textarea id="wcbc_config_json" name="wcbc_config_json" rows="18" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $json ); ?></textarea>
					<span class="description"><?php esc_html_e( 'Edit groups, options, images, and default selections. Invalid JSON is ignored on save.', 'wc-bed-configurator' ); ?></span>
				</p>
				<p class="form-field">
					<strong><?php esc_html_e( 'Happy Beds real images', 'wc-bed-configurator' ); ?></strong><br />
					<?php esc_html_e( 'Happy Beds blocks hotlinked images on localhost. Import real layer files once using the browser script in scripts/import-happybeds-to-wp.js', 'wc-bed-configurator' ); ?>
					<br /><br />
					<code style="display:block;padding:8px;background:#f6f7f7;">
						wcbcWpSite = '<?php echo esc_js( home_url() ); ?>';<br />
						wcbcImportNonce = '<?php echo esc_js( WCBC_Cache_API::import_nonce() ); ?>';
					</code>
					<br />
					<?php
					printf(
						/* translators: %s: path to script */
						esc_html__( 'Run those two lines on happybeds.co.uk, then paste %s in DevTools Console.', 'wc-bed-configurator' ),
						'<code>scripts/import-happybeds-to-wp.js</code>'
					);
					?>
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

		if ( isset( $_POST['wcbc_base_price'] ) && '' !== $_POST['wcbc_base_price'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$base = (float) wc_clean( wp_unslash( $_POST['wcbc_base_price'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! empty( $_POST['wcbc_config_json'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$raw    = wp_unslash( $_POST['wcbc_config_json'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$config = json_decode( $raw, true );
			if ( is_array( $config ) && ! empty( $config['groups'] ) ) {
				if ( isset( $base ) ) {
					$config['base_price'] = $base;
				}
				update_post_meta( $post_id, WCBC_Config::META_KEY, $config );
			}
		} elseif ( isset( $base ) ) {
			$config = WCBC_Config::get_product_config( $post_id );
			$config['base_price'] = $base;
			update_post_meta( $post_id, WCBC_Config::META_KEY, $config );
		}
	}
}
