<?php
/**
 * Bed configurator template.
 *
 * @package WCBedConfigurator
 * @var WC_Product $product
 * @var array $config
 * @var array $selections
 * @var array $calc
 * @var string $plugin_url
 * @var string $part media|options
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$part  = isset( $part ) ? $part : 'full';
$layer_alts = array(
	'shadow'       => '',
	'legs'         => __( 'Bed Legs', 'wc-bed-configurator' ),
	'headboard'    => __( 'Bed Headboard', 'wc-bed-configurator' ),
	'storage_back' => __( 'Bed Storage Back', 'wc-bed-configurator' ),
	'base'         => __( 'Bed Base', 'wc-bed-configurator' ),
	'storage_1'    => __( 'Bed Storage 1', 'wc-bed-configurator' ),
	'storage_2'    => __( 'Bed Storage 2', 'wc-bed-configurator' ),
	'storage_3'    => __( 'Bed Storage 3', 'wc-bed-configurator' ),
	'storage_4'    => __( 'Bed Storage 4', 'wc-bed-configurator' ),
);
$icons = array(
	'size'      => $plugin_url . 'assets/icons/size.svg',
	'colour'    => $plugin_url . 'assets/icons/colour.svg',
	'headboard' => $plugin_url . 'assets/icons/headboard.svg',
	'depth'     => $plugin_url . 'assets/icons/depth.svg',
	'storage'   => $plugin_url . 'assets/icons/storage.svg',
);

if ( 'media' === $part ) : ?>
	<div id="ev-mediaproddetails" class="wcbc-media-wrap" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
		<div class="product media wcbc-media">
			<div class="byobed-in">
				<div class="leftpart">
					<div id="dynamic_product_images" class="dynamic_product_images wcbc-preview">
						<?php
						$image_mode  = function_exists( 'wcbc_get_image_mode' ) ? wcbc_get_image_mode() : 'demo';
						$transparent = WCBC_PLUGIN_URL . 'demo-images/layers/transparent.png';
						$demo_layers = ( 'demo' === $image_mode ) ? WCBC_Layer_Builder::build_demo_layers( $selections, $config['defaults'] ) : array();
						foreach ( WCBC_Config::get_layers() as $layer ) :
							$src      = isset( $calc['layers'][ $layer ] ) ? $calc['layers'][ $layer ] : '';
							$fallback = isset( $demo_layers[ $layer ] ) ? $demo_layers[ $layer ] : $transparent;
							$alt      = isset( $layer_alts[ $layer ] ) ? $layer_alts[ $layer ] : '';
							?>
							<img
								loading="lazy"
								class="lazyload dynamic_image_items dynamic_<?php echo esc_attr( $layer ); ?> wcbc-layer"
								id="dynamic_<?php echo esc_attr( $layer ); ?>"
								data-layer="<?php echo esc_attr( $layer ); ?>"
								data-fallback="<?php echo esc_url( $fallback ); ?>"
								alt="<?php echo esc_attr( $alt ); ?>"
								referrerpolicy="no-referrer"
								src="<?php echo esc_url( $src ); ?>"
							/>
						<?php endforeach; ?>
					</div>
					<div id="draw_toggler" class="wcbc-draw-toggler">
						<div class="toggle_label"><?php echo esc_html__( 'Drawers', 'wc-bed-configurator' ); ?></div>
						<div id="open_close_toggle">
							<label class="switch-light draw-light switch-candy">
								<input id="drawer_checkbox" type="checkbox" checked />
								<span>
									<span><?php echo esc_html__( 'Closed', 'wc-bed-configurator' ); ?></span>
									<span><?php echo esc_html__( 'Open', 'wc-bed-configurator' ); ?></span>
									<a></a>
								</span>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

if ( 'options' === $part ) : ?>
	<div class="product-info-main wcbc-options-column">
		<div class="wcbc-top-bar">
			<div class="wcbc-delivery">
				<div class="pp-order-info">
					<div class="leadtime line font-bold"><?php echo esc_html__( 'Free Next Day Delivery', 'wc-bed-configurator' ); ?></div>
					<div class="line"><?php echo esc_html__( 'UK Mainland', 'wc-bed-configurator' ); ?></div>
				</div>
			</div>
			<div class="wcbc-sticky-price">
				<div class="price-box price-final_price">
					<span class="normal-price">
						<span class="price-container">
							<span class="price-label"><?php echo esc_html__( 'Now', 'wc-bed-configurator' ); ?></span>
							<span class="price-wrapper">
								<span class="price wcbc-live-price"><?php echo wp_kses_post( wc_price( $calc['price'] ) ); ?></span>
							</span>
						</span>
					</span>
				</div>
			</div>
		</div>

		<div id="product-options-wrapper" class="product-options-wrapper">
			<div id="tabs-container">
				<div class="tab">
					<dl class="tab-content byob-accordian wcbc-accordian">
						<?php foreach ( WCBC_Config::visible_groups( $config ) as $index => $group ) : ?>
							<?php
							$gid       = $group['id'];
							$is_open   = ( 'size' === $gid );
							$selected  = WCBC_Config::resolve_group_selection( $group, isset( $selections[ $gid ] ) ? $selections[ $gid ] : '' );
							$sel_label = '';
							foreach ( $group['options'] as $opt ) {
								if ( $opt['id'] === $selected ) {
									$sel_label = $opt['label'];
									break;
								}
							}
							$icon_key = isset( $group['icon'] ) ? $group['icon'] : 'size';
							$icon_url = isset( $icons[ $icon_key ] ) ? $icons[ $icon_key ] : $icons['size'];
							$count    = count( $group['options'] );
							?>
							<dt class="wcbc-accordian-item">
								<button
									type="button"
									class="wcbc-accordian-head <?php echo $is_open ? 'isopen' : ''; ?>"
									data-tabid="<?php echo esc_attr( $gid ); ?>"
									aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
								>
								<div class="wcbc-accordian-head-inner">
									<div class="wcbc-accordian-icon iconwrap">
										<img width="50" height="50" alt="" src="<?php echo esc_url( $icon_url ); ?>" />
									</div>
									<div class="wcbc-accordian-summary">
										<div class="title_in_wrap">
											<?php echo esc_html( $group['label'] ); ?>
											<span class="ev_sel_size wcbc-selected-label" data-group="<?php echo esc_attr( $gid ); ?>"><?php echo esc_html( $sel_label ); ?></span>
										</div>
										<div class="wcbc-accordian-meta">
											<span class="options-available-pill rounded-3xl inline-block text-white">
												<?php
												printf(
													esc_html__( '%d options available', 'wc-bed-configurator' ),
													(int) $count
												);
												?>
											</span>
											<span class="ev_ln_filter_chevron <?php echo $is_open ? '' : 'ev_ln_filter_chevron_closed'; ?>">
												<svg class="ac-accordion__expand-chevron" width="20" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M23.23 6.173l.646.746a.5.5 0 0 1-.045.7l-11.5 10.254a.5.5 0 0 1-.665 0L.166 7.62a.5.5 0 0 1-.044-.701l.644-.743a.5.5 0 0 1 .71-.045l10.19 9.09a.5.5 0 0 0 .665 0L22.52 6.126a.5.5 0 0 1 .71-.046z"></path></svg>
											</span>
										</div>
									</div>
								</div>
								</button>
							</dt>
							<dd data-tabid="<?php echo esc_attr( $gid ); ?>" data-filter-type="<?php echo esc_attr( ! empty( $group['filter_type'] ) ? $group['filter_type'] : '' ); ?>" class="wcbc-accordian-body <?php echo $is_open ? 'isopen' : ''; ?>" <?php echo $is_open ? '' : 'style="display:none"'; ?>>
								<div class="input-box">
									<?php
									$is_fabric_sections = ( 'fabric' === ( $group['filter_type'] ?? '' ) && 'sections' === ( $group['display_mode'] ?? '' ) );
									$default_filter     = ! empty( $group['filters'][0]['filter'] ) ? $group['filters'][0]['filter'] : '';
									$active_filter      = $default_filter;
									if ( 'shape' === ( $group['filter_type'] ?? '' ) ) {
										foreach ( $group['options'] as $opt ) {
											if ( sanitize_title( $opt['id'] ) === $selected && ! empty( $opt['layers']['shape'] ) ) {
												$active_filter = $opt['layers']['shape'];
												break;
											}
										}
									}
									?>
									<?php if ( ! empty( $group['filter_type'] ) && ! empty( $group['filters'] ) && ! $is_fabric_sections ) : ?>
										<?php
										$filter_heading = 'fabric' === $group['filter_type']
											? __( 'Choose Fabric', 'wc-bed-configurator' )
											: __( 'Choose Shape', 'wc-bed-configurator' );
										?>
										<h2><?php echo esc_html( $filter_heading ); ?></h2>
										<div class="button-group filters-button-group wcbc-option-filters">
											<?php foreach ( $group['filters'] as $fi => $filter ) : ?>
												<button type="button" class="button wcbc-filter-btn <?php echo $active_filter === $filter['filter'] ? 'is-checked' : ''; ?>" data-filter="<?php echo esc_attr( $filter['filter'] ); ?>">
													<?php echo esc_html( $filter['label'] ); ?>
												</button>
											<?php endforeach; ?>
										</div>
										<h2 class="wcbc-style-heading"><?php echo esc_html__( 'Choose Style', 'wc-bed-configurator' ); ?></h2>
									<?php endif; ?>

									<ul class="options-list wcbc-options-grid <?php echo ! empty( $group['filter_type'] ) && ! $is_fabric_sections ? 'wcbc-filterable-grid grid grid-cols-3' : ''; ?> <?php echo $is_fabric_sections ? 'wcbc-colour-grid' : ''; ?>">
										<?php
										$last_fabric = '';
										foreach ( $group['options'] as $option ) :
											$shape   = ! empty( $option['layers']['shape'] ) ? $option['layers']['shape'] : '';
											$fabric  = ! empty( $option['layers']['fabric'] ) ? $option['layers']['fabric'] : '';
											$checked = ( sanitize_title( $option['id'] ) === $selected );
											$hidden  = false;
											if ( ! $is_fabric_sections && ! empty( $group['filter_type'] ) ) {
												if ( 'shape' === $group['filter_type'] && $shape && $active_filter !== $shape ) {
													$hidden = true;
												}
												if ( 'fabric' === $group['filter_type'] && $fabric && $default_filter !== $fabric && ! $checked ) {
													$hidden = true;
												}
											}
											if ( $is_fabric_sections && $fabric && $fabric !== $last_fabric ) :
												$last_fabric = $fabric;
												?>
												<li class="wcbc-fabric-heading" style="width:100%;list-style:none;">
													<h2><?php echo esc_html( ucfirst( $fabric ) ); ?></h2>
												</li>
											<?php endif; ?>
											<li id="<?php echo esc_attr( $option['id'] ); ?>" class="wcbc-option <?php echo $shape ? 'wcbc-shape-' . esc_attr( $shape ) : ''; ?> <?php echo $fabric ? 'wcbc-fabric-' . esc_attr( $fabric ) . ' color_way' : ''; ?> <?php echo $hidden ? 'wcbc-filter-hidden' : ''; ?> <?php echo $checked ? 'is-selected' : ''; ?>" data-shape="<?php echo esc_attr( $shape ); ?>" data-fabric="<?php echo esc_attr( $fabric ); ?>">
												<input type="radio" class="wcbc-radio wcbc-radio-input product-custom-option" name="wcbc_ui_<?php echo esc_attr( $gid ); ?>" id="wcbc_<?php echo esc_attr( $gid . '_' . $option['id'] ); ?>" value="<?php echo esc_attr( $option['id'] ); ?>" data-group="<?php echo esc_attr( $gid ); ?>" data-price="<?php echo esc_attr( $option['price'] ); ?>" <?php checked( $checked ); ?> />
												<label for="wcbc_<?php echo esc_attr( $gid . '_' . $option['id'] ); ?>" class="wcbc-option-label <?php echo $checked ? 'is-checked' : ''; ?>">
													<div class="swatchContainer">
														<div class="swatch45 product-option divswatch">
															<?php if ( ! empty( $option['image'] ) ) : ?>
																<img class="colourspan" loading="lazy" alt="<?php echo esc_attr( $option['label'] ); ?>" src="<?php echo esc_url( $option['image'] ); ?>" />
															<?php endif; ?>
															<div class="option-name option-name-custom small-color-font">
																<?php echo esc_html( $option['label'] ); ?>
																<?php if ( ! empty( $option['sublabel'] ) ) : ?>
																	<span class="bespoke-sub-size"><?php echo esc_html( $option['sublabel'] ); ?></span>
																<?php endif; ?>
															</div>
															<?php if ( ! empty( $option['badge'] ) ) : ?>
																<span class="options-available-pill wcbc-badge"><?php echo esc_html( $option['badge'] ); ?></span>
															<?php endif; ?>
														</div>
													</div>
												</label>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
								<div class="ev_acc_linebreak"></div>
							</dd>
						<?php endforeach; ?>
					</dl>
				</div>
			</div>
		</div>

		<div id="byob_price_wrap" class="wcbc-price-wrap">
			<div class="wcbc-price-row">
				<div class="wcbc-price-col">
					<div class="price-box price-final_price">
						<span class="normal-price">
							<span class="price-container">
								<span class="price-label"><?php echo esc_html__( 'Now', 'wc-bed-configurator' ); ?></span>
								<span class="price wcbc-live-price"><?php echo wp_kses_post( wc_price( $calc['price'] ) ); ?></span>
							</span>
						</span>
					</div>
				</div>
				<div class="wcbc-cart-button-wrap"></div>
			</div>
		</div>
	</div>
	<?php
endif;
