/**
 * WooCommerce Bed Configurator — options, pricing, cart, live preview layers.
 */
(function ($) {
	'use strict';

	if (typeof wcbcData === 'undefined') {
		return;
	}

	var FORM_ID = 'wcbc-product-form';

	var state = {
		selections: {},
		drawersOpen: false,
	};

	function pick(selections, key) {
		var defaults = wcbcData.config.defaults || {};
		return selections[key] || defaults[key] || '';
	}

	function headboardShape(headboardId) {
		if (!headboardId || headboardId.indexOf('no-headboard') !== -1) {
			return 'none';
		}
		if (headboardId.indexOf('dudley') !== -1) {
			return 'dudley';
		}
		if (headboardId.indexOf('victor') !== -1) {
			return 'victor';
		}
		return 'cornell';
	}

	function hasDrawers(storageId) {
		return storageId === '2-drawers' || storageId === '4-drawers' || storageId === 'end-drawer';
	}

	function isTransparentUrl(src) {
		return !src || /transparent\.png|FFFFFF-0/i.test(src);
	}

	function drawerRefForSize(sizeCode, drawerCode) {
		if (sizeCode === '3ft') {
			return null;
		}
		if (sizeCode === '5ft' || sizeCode === '6ft') {
			return '200';
		}
		return drawerCode;
	}

	function drawerBackPath(folder, sizeCode, drawerRef, suffix) {
		if (drawerRef === null) {
			return folder + '/reference_drawer_normal_back_' + sizeCode + '_drawer_normal_front_' + suffix + '.png';
		}
		return folder + '/reference_drawer_normal_back_' + drawerRef + '_' + sizeCode + '_drawer_normal_front_' + suffix + '.png';
	}

	function drawerFrontPath(folder, sizeCode, drawerRef, suffix) {
		if (drawerRef === null) {
			return folder + '/reference_drawer_normal_front_' + sizeCode + '_drawer_normal_front_' + suffix + '.png';
		}
		return folder + '/reference_drawer_normal_front_' + drawerRef + '_' + sizeCode + '_drawer_normal_front_' + suffix + '.png';
	}

	function happyBedsUrl(cdn, relative, mode) {
		relative = relative.replace(/^\//, '');
		if (mode === 'happybeds-proxy' && wcbcData.layerProxy) {
			return wcbcData.layerProxy + encodeURIComponent(relative);
		}
		return cdn + relative;
	}

	function happyBedsLayers(selections, mode) {
		mode = mode || wcbcData.imageMode || 'happybeds-cdn';
		var hb = wcbcData.happyBeds || {};
		var cdn = hb.cdn || 'https://www.happybeds.co.uk/media/new_configurator/';
		var defaults = wcbcData.config.defaults || {};
		var size = pick(selections, 'size') || defaults.size || 'double';
		var colour = pick(selections, 'colour') || defaults.colour || 'beige-velvet';
		var headboard = pick(selections, 'headboard') || defaults.headboard || 'cornell-lined';
		var baseDepth = pick(selections, 'base_depth') || defaults.base_depth || '14-inch';
		var storage = pick(selections, 'storage') || defaults.storage || 'no-drawers';

		var sizeCodes = hb.sizeCodes || {};
		var colourMeta = hb.colourMeta || {};
		var headboardMap = hb.headboards || {};
		var transparent = happyBedsUrl(cdn, hb.transparent || 'FFFFFF-0.png', mode);

		var sizeCode = sizeCodes[size] || '4ft6';
		var depthCode = baseDepth.replace('-inch', 'i');
		var meta = colourMeta[colour] || colourMeta['beige-velvet'] || { fabric: 'velvet', code: '30', drawer: '190' };
		var hbStyle = headboardMap[headboard];
		var suffix = depthCode + meta.code;
		var drawerFolder = meta.fabric === 'velvet' ? 'drawers_velvet' : 'drawers_cotton';
		var hbFolder = meta.fabric === 'velvet' ? 'headboards_velvet' : 'headboards_cotton';
		var drawerRef = drawerRefForSize(sizeCode, meta.drawer);

		var layers = {
			shadow: happyBedsUrl(cdn, 'new_shadow/shadow_wrk_' + sizeCode + '.jpg', mode),
			legs: happyBedsUrl(cdn, 'legs/bedding_legs_' + sizeCode + '.png', mode),
			storage_back: transparent,
			base: happyBedsUrl(cdn, 'bases/' + meta.fabric + '/bedbase_' + sizeCode + '_' + depthCode + '_' + meta.code + '.png', mode),
			headboard: transparent,
			storage_1: transparent,
			storage_2: transparent,
			storage_3: transparent,
		};

		if (hbStyle) {
			layers.headboard = happyBedsUrl(cdn, hbFolder + '/' + hbStyle + '_' + sizeCode + '_' + suffix + '.png', mode);
		}

		if (storage === '2-drawers' || storage === 'end-drawer') {
			layers.storage_2 = happyBedsUrl(cdn, drawerBackPath(drawerFolder, sizeCode, drawerRef, suffix), mode);
			layers.storage_3 = happyBedsUrl(cdn, drawerFrontPath(drawerFolder, sizeCode, drawerRef, suffix), mode);
		} else if (storage === '4-drawers') {
			var ref = drawerRef === null ? sizeCode : drawerRef;
			layers.storage_2 = happyBedsUrl(cdn, drawerBackPath(drawerFolder, sizeCode, drawerRef, suffix), mode);
			layers.storage_1 = happyBedsUrl(cdn, drawerFrontPath(drawerFolder, sizeCode, drawerRef, suffix), mode);
			if (drawerRef === null) {
				layers.storage_2 = happyBedsUrl(cdn, drawerFolder + '/reference_drawer_jumbo_front_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png', mode);
				layers.storage_3 = happyBedsUrl(cdn, drawerFolder + '/reference_drawer_jumbo_back_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png', mode);
			} else {
				layers.storage_2 = happyBedsUrl(cdn, drawerFolder + '/reference_drawer_jumbo_front_' + ref + '_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png', mode);
				layers.storage_3 = happyBedsUrl(cdn, drawerFolder + '/reference_drawer_jumbo_back_' + ref + '_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png', mode);
			}
		}

		return layers;
	}

	function buildLayers(selections) {
		var mode = wcbcData.imageMode || (wcbcData.useHappyBeds ? 'happybeds-cdn' : 'demo');
		if (mode === 'demo') {
			return demoLayers(selections);
		}
		return happyBedsLayers(selections, mode);
	}

	function demoLayers(selections) {
		var base = wcbcData.layerBase || '';
		var size = pick(selections, 'size') || 'small-double';
		var colour = pick(selections, 'colour') || 'beige-velvet';
		var headboard = pick(selections, 'headboard');
		var baseDepth = pick(selections, 'base_depth') || '14-inch';
		var storage = pick(selections, 'storage') || 'no-drawers';
		var shape = headboardShape(headboard);
		var drawerSuffix = hasDrawers(storage) ? '-drawers' : '';

		var layers = {
			shadow: base + 'shadow-only.png',
			legs: base + 'legs/' + size + '.png',
			storage_back: base + 'transparent.png',
			base: base + 'base/' + size + '/' + colour + '/' + baseDepth + drawerSuffix + '.png',
			headboard: base + 'transparent.png',
			storage_1: base + 'transparent.png',
			storage_2: base + 'transparent.png',
			storage_3: base + 'transparent.png',
		};

		if (shape !== 'none') {
			layers.headboard = base + 'headboard/' + size + '/' + shape + '/' + colour + '.png';
		}

		return layers;
	}

	function initSelections() {
		wcbcData.config.groups.forEach(function (group) {
			var def = wcbcData.config.defaults[group.id] || '';
			var $checked = $('input.wcbc-radio[data-group="' + group.id + '"]:checked');
			state.selections[group.id] = $checked.length ? $checked.val() : def;
		});
		syncHiddenFields();
		refreshPreview();
	}

	function syncHiddenFields() {
		Object.keys(state.selections).forEach(function (groupId) {
			$('#wcbc_sel_' + groupId).val(state.selections[groupId]);
		});
	}

	function updateLabels(labels) {
		if (!labels) {
			return;
		}
		Object.keys(labels).forEach(function (groupId) {
			$('.wcbc-selected-label[data-group="' + groupId + '"]').text(labels[groupId]);
		});
	}

	function applyLayerImage($img, nextSrc) {
		var isTransparent = isTransparentUrl(nextSrc);
		if ($img.attr('src') !== nextSrc) {
			$img.attr('src', nextSrc);
		}
		$img.css({
			opacity: isTransparent ? 0 : 1,
			visibility: isTransparent ? 'hidden' : 'visible',
		});
	}

	function updateLayers(layers) {
		if (!layers) {
			return;
		}
		var demoFallback = (wcbcData.imageMode === 'demo') ? demoLayers(state.selections) : {};
		wcbcData.layers.forEach(function (layer) {
			if (!layers[layer]) {
				return;
			}
			var $img = $('#dynamic_' + layer);
			if (!$img.length) {
				return;
			}
			var nextSrc = layers[layer];
			var fallbackSrc = $img.data('fallback') || demoFallback[layer] || (wcbcData.layerBase || '') + 'transparent.png';
			applyLayerImage($img, nextSrc);
			$img.off('error.wcbc').on('error.wcbc', function () {
				if (fallbackSrc && $img.attr('src') !== fallbackSrc) {
					applyLayerImage($img, fallbackSrc);
				}
			});
		});
	}

	function updatePrices(priceHtml) {
		if (priceHtml) {
			$('.wcbc-live-price').html(priceHtml);
		}
	}

	function refreshPreview() {
		updateLayers(buildLayers(state.selections));
	}

	function calculate() {
		return $.post(wcbcData.ajaxUrl, {
			action: 'wcbc_calculate_price',
			nonce: wcbcData.nonce,
			product_id: wcbcData.productId,
			selections: state.selections,
		});
	}

	function onSelectionChange(groupId, optionId) {
		state.selections[groupId] = optionId;
		syncHiddenFields();

		$('input.wcbc-radio[data-group="' + groupId + '"]').each(function () {
			var isMatch = $(this).val() === optionId;
			$(this).prop('checked', isMatch);
			$(this).closest('li').find('label').toggleClass('is-checked', isMatch);
		});

		// Update bed preview immediately from local layer map.
		refreshPreview();

		calculate().done(function (response) {
			if (!response || !response.success) {
				return;
			}
			updatePrices(response.data.price_html);
			if (response.data.layers) {
				updateLayers(response.data.layers);
			}
			updateLabels(response.data.labels);
		});
	}

	function bindOptions() {
		$(document).on('change', 'input.wcbc-radio', function () {
			onSelectionChange($(this).data('group'), $(this).val());
		});

		$(document).on('click', '.wcbc-option label', function (e) {
			e.preventDefault();
			var $radio = $(this).closest('li').find('input.wcbc-radio');
			if ($radio.length) {
				$radio.prop('checked', true).trigger('change');
			}
		});
	}

	function bindHeadboardFilters() {
		$(document).on('click', '.wcbc-filter-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var filter = $(this).data('filter');
			var $section = $(this).closest('dd');

			$section.find('.wcbc-filter-btn').removeClass('is-checked');
			$(this).addClass('is-checked');

			$section.find('.wcbc-headboard-grid li').each(function () {
				var shape = $(this).data('shape') || '';
				var isSelected = $(this).find('input.wcbc-radio').is(':checked');
				$(this).toggleClass('wcbc-filter-hidden', shape !== filter && !isSelected);
			});
		});
	}

	function bindDrawerToggle() {
		var $checkbox = $('#drawer_checkbox');
		if (!$checkbox.length) {
			return;
		}

		state.drawersOpen = $checkbox.is(':checked');
		$('.wcbc-preview').toggleClass('wcbc-drawers-open', state.drawersOpen);

		$checkbox.on('change', function () {
			state.drawersOpen = $(this).is(':checked');
			$('.wcbc-preview').toggleClass('wcbc-drawers-open', state.drawersOpen);
			refreshPreview();
			calculate().done(function (response) {
				if (response && response.success && response.data.layers) {
					updateLayers(response.data.layers);
				}
			});
		});
	}

	function setupCartForm() {
		var $form = $('form.cart').first();
		if (!$form.length) {
			return false;
		}

		$form.attr('id', FORM_ID);
		$form.addClass('wcbc-cart-form');

		var $wrap = $('.wcbc-cart-button-wrap');
		var $btn = $form.find('.single_add_to_cart_button').first();
		var $qty = $form.find('.quantity');

		if ($wrap.length && $btn.length && !$wrap.find('.single_add_to_cart_button').length) {
			$btn.detach()
				.appendTo($wrap)
				.addClass('wcbc-add-to-cart action checkout w-full text-base')
				.attr('type', 'submit')
				.attr('form', FORM_ID);

			$qty.addClass('wcbc-qty-hidden').hide();
			$form.addClass('wcbc-form-relocated');
			return true;
		}

		return $wrap.find('.single_add_to_cart_button').length > 0;
	}

	function relocateAddToCart() {
		if (setupCartForm()) {
			return;
		}

		var attempts = 0;
		var timer = window.setInterval(function () {
			attempts += 1;
			if (setupCartForm() || attempts >= 20) {
				window.clearInterval(timer);
			}
		}, 150);
	}

	$(function () {
		initSelections();
		bindOptions();
		bindHeadboardFilters();
		bindDrawerToggle();
		relocateAddToCart();
	});

	$(window).on('load', relocateAddToCart);
})(jQuery);
