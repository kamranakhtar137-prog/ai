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

	function headboardVariant(headboardId) {
		if (!headboardId || headboardId.indexOf('no-headboard') !== -1) {
			return 'plain';
		}
		if (headboardId.indexOf('buttoned') !== -1) {
			return 'buttoned';
		}
		if (headboardId.indexOf('lined') !== -1) {
			return 'lined';
		}
		return 'plain';
	}

	function demoStorageUrl(layer, base, storage, size, colour, depth) {
		if (!storage || storage === 'no-drawers') {
			return base + 'transparent.png';
		}
		storage = normalizeStorage(storage);
		return base + 'storage/' + layer + '/' + storage + '/' + size + '/' + colour + '/' + depth + '.png';
	}

	function hasDrawers(storageId) {
		storageId = normalizeStorage(storageId);
		return storageId === '2-drawers' || storageId === '4-drawers' || storageId === 'end-drawer';
	}

	function normalizeStorage(storage) {
		var map = {
			'2-drawers-same-side': '2-drawers',
			'2-drawers-with-end-drawer': '2-drawers',
			'2-drawers-with-2-mini-drawers': '2-drawers',
			'end-drawer-with-2-mini-drawers': 'end-drawer',
		};
		return map[storage] || storage;
	}

	function resolveColourSlug(colour) {
		var aliases = {
			'black-linen': 'black-cotton',
			'charcoal-linen': 'charcoal-cotton',
			'chocolate-linen': 'chocolate-cotton',
			'cream-linen': 'cream-cotton',
			'duck-egg-blue-linen': 'duck-egg-blue-cotton',
			'lime-linen': 'lime-cotton',
			'midnight-blue-linen': 'midnight-blue-cotton',
			'orchid-linen': 'orchid-cotton',
			'plum-linen': 'plum-cotton',
			'red-linen': 'red-cotton',
			'slate-grey-linen': 'slate-grey-cotton',
			'white-linen': 'white-cotton',
			'silver-grey-linen': 'silver-grey-cotton',
		};
		return aliases[colour] || colour;
	}

	function storageBackFromFront(storageFrontRelative) {
		if (!storageFrontRelative) {
			return null;
		}
		return storageFrontRelative.replace(/4ft6/g, '4ft').replace(/_front_/g, '_front_left_');
	}

	function isVariationDrivenLayer(layer) {
		var driven = wcbcData.variationDrivenLayers || [];
		return driven.indexOf(layer) !== -1;
	}

	function shouldApplyOptionOverrides() {
		var source = wcbcData.imageSource || 'media';
		return source === 'hybrid';
	}

	function isDemoLayerUrl(url) {
		return /demo-images\/layers/i.test(url || '');
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

	function fabricPaths(hbFabric) {
		var hb = wcbcData.happyBeds || {};
		var map = hb.fabricPaths || {};
		return map[hbFabric] || map.velvet || {
			base: 'velvet',
			headboard: 'headboards_velvet',
			drawer: 'drawers_velvet',
		};
	}

	function happyBedsLayers(selections, mode) {
		mode = mode || wcbcData.imageMode || 'happybeds-cdn';
		var hb = wcbcData.happyBeds || {};
		var cdn = hb.cdn || 'https://www.happybeds.co.uk/media/new_configurator/';
		var defaults = wcbcData.config.defaults || {};
		var size = pick(selections, 'size') || defaults.size || 'double';
		var colour = resolveColourSlug(pick(selections, 'colour') || defaults.colour || 'beige-velvet');
		var headboard = pick(selections, 'headboard') || defaults.headboard || 'cornell-lined';
		var baseDepth = pick(selections, 'base_depth') || defaults.base_depth || '14-inch';
		var storage = normalizeStorage(pick(selections, 'storage') || defaults.storage || 'no-drawers');
		var drawersOpen = storage === 'ottoman' && (state.drawersOpen || selections.drawers_open === '1');

		var sizeCodes = hb.sizeCodes || {};
		var colourMeta = hb.colourMeta || {};
		var headboardMap = hb.headboards || {};
		var transparent = happyBedsUrl(cdn, hb.transparent || 'FFFFFF-0.png', mode);

		var sizeCode = sizeCodes[size] || '4ft6';
		var depthCode = baseDepth.replace('-inch', 'i');
		var meta = colourMeta[colour] || colourMeta['beige-velvet'] || { fabric: 'velvet', hb_fabric: 'velvet', code: '30', drawer: '190' };
		var hbStyle = headboardMap[headboard];
		var suffix = depthCode + meta.code;
		var paths = fabricPaths(meta.hb_fabric || meta.fabric);
		var drawerFolder = paths.drawer;
		var hbFolder = paths.headboard;
		var drawerRef = drawerRefForSize(sizeCode, meta.drawer);

		var baseRel = 'bases/' + paths.base + '/bedbase_' + sizeCode + '_' + depthCode + '_' + meta.code + '.png';
		if (drawersOpen) {
			baseRel = 'bases/' + paths.base + '/ottoman_open/bedbase_' + sizeCode + '_' + depthCode + '_' + meta.code + '.png';
		}

		var legsRel = 'legs/bedding_legs_' + sizeCode + '.png';
		if (storage === 'ottoman') {
			if (sizeCode === '4ft6') {
				legsRel = 'legs/hb_legs_4ft6_ottoman.png';
			} else if (sizeCode === '5ft') {
				legsRel = 'legs/hb_legs_5ft_ottoman.png';
			} else if (sizeCode === '6ft') {
				legsRel = 'legs/hb_legs_6ft_ottoman.png';
			}
		}

		var layers = {
			shadow: happyBedsUrl(cdn, 'new_shadow/shadow_wrk_' + sizeCode + '.jpg', mode),
			legs: happyBedsUrl(cdn, legsRel, mode),
			storage_back: transparent,
			base: happyBedsUrl(cdn, baseRel, mode),
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
			var backRel = storageBackFromFront(drawerFrontPath(drawerFolder, sizeCode, drawerRef, suffix));
			if (backRel) {
				layers.storage_back = happyBedsUrl(cdn, backRel, mode);
			}
		} else if (storage === '4-drawers') {
			var ref = drawerRef === null ? sizeCode : drawerRef;
			layers.storage_2 = happyBedsUrl(cdn, drawerBackPath(drawerFolder, sizeCode, drawerRef, suffix), mode);
			layers.storage_1 = happyBedsUrl(cdn, drawerFrontPath(drawerFolder, sizeCode, drawerRef, suffix), mode);
			var storage3Rel;
			if (drawerRef === null) {
				layers.storage_2 = happyBedsUrl(cdn, drawerFolder + '/reference_drawer_jumbo_front_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png', mode);
				storage3Rel = drawerFolder + '/reference_drawer_jumbo_back_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png';
				layers.storage_3 = happyBedsUrl(cdn, storage3Rel, mode);
			} else {
				layers.storage_2 = happyBedsUrl(cdn, drawerFolder + '/reference_drawer_jumbo_front_' + ref + '_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png', mode);
				storage3Rel = drawerFolder + '/reference_drawer_jumbo_back_' + ref + '_' + sizeCode + '_drawer_jumbo_front_' + suffix + '.png';
				layers.storage_3 = happyBedsUrl(cdn, storage3Rel, mode);
			}
			var backRel = storageBackFromFront(storage3Rel);
			if (backRel) {
				layers.storage_back = happyBedsUrl(cdn, backRel, mode);
			}
		}

		return layers;
	}

	function applyOptionLayerOverrides(layers, selections) {
		if (!shouldApplyOptionOverrides()) {
			return layers;
		}

		var config = wcbcData.config || {};
		var defaults = config.defaults || {};
		var validLayers = {};
		var source = wcbcData.imageSource || 'auto';
		var allowVariationUrl = source === 'media';
		if (wcbcData.layers) {
			wcbcData.layers.forEach(function (layer) {
				validLayers[layer] = true;
			});
		}
		if (!config.groups) {
			return layers;
		}
		config.groups.forEach(function (group) {
			var sel = selections[group.id] || defaults[group.id] || '';
			if (!group.options) {
				return;
			}
			group.options.forEach(function (opt) {
				if (opt.id !== sel || !opt.layers) {
					return;
				}
				Object.keys(opt.layers).forEach(function (layerKey) {
					if (!validLayers[layerKey] || !opt.layers[layerKey] || layers[layerKey] === undefined) {
						return;
					}
					if (!allowVariationUrl && isVariationDrivenLayer(layerKey)) {
						return;
					}
					if (wcbcData.useHappyBeds && isDemoLayerUrl(opt.layers[layerKey])) {
						return;
					}
					layers[layerKey] = opt.layers[layerKey];
				});
			});
		});
		return layers;
	}

	function buildVariationMediaLayers(selections) {
		var transparent = wcbcData.transparentLayer || (wcbcData.layerBase || '') + 'transparent.png';
		var maps = wcbcData.variationLayerMedia || { size: {}, sizeHeadboards: {}, colour: {} };
		var colourSlots = wcbcData.colourLayerSlots || ['storage_back', 'base', 'storage_2', 'storage_3', 'headboard'];
		var size = pick(selections, 'size') || 'small-single';
		var colour = resolveColourSlug(pick(selections, 'colour') || 'light-silver-velvet');
		var headboard = pick(selections, 'headboard');
		var sizeSet = maps.size[size] || {};
		var colourSet = (maps.colour[size] && maps.colour[size][colour]) ? maps.colour[size][colour] : {};
		var sizeHeadboards = maps.sizeHeadboards[size] || {};
		var layers = {};

		wcbcData.layers.forEach(function (layer) {
			layers[layer] = transparent;
		});

		['shadow', 'legs'].forEach(function (layer) {
			if (sizeSet[layer]) {
				layers[layer] = sizeSet[layer];
			}
		});

		if (headboard && headboardShape(headboard) !== 'none') {
			if (sizeHeadboards[headboard]) {
				layers.headboard = sizeHeadboards[headboard];
			} else if (sizeHeadboards._legacy) {
				layers.headboard = sizeHeadboards._legacy;
			}
		}

		colourSlots.forEach(function (layer) {
			if (colourSet[layer]) {
				layers[layer] = colourSet[layer];
			}
		});

		if (headboardShape(headboard) === 'none') {
			layers.headboard = transparent;
		}

		return layers;
	}

	function buildMediaLayers(selections) {
		return buildVariationMediaLayers(selections);
	}

	function mergeHybridMedia(layers, selections) {
		var media = wcbcData.layerMedia || {};
		var staticLayers = wcbcData.staticOverrideLayers || ['shadow', 'legs'];
		Object.keys(media).forEach(function (layer) {
			if (media[layer] && staticLayers.indexOf(layer) !== -1) {
				layers[layer] = media[layer];
			}
		});
		return applyOptionLayerOverrides(layers, selections);
	}

	function buildLayers(selections) {
		var source = wcbcData.imageSource || 'media';
		if (source === 'media') {
			return buildVariationMediaLayers(selections);
		}
		if (source === 'demo') {
			return demoLayers(selections);
		}

		var mode = wcbcData.imageMode || 'demo';
		var layers;
		if (mode === 'demo') {
			layers = demoLayers(selections);
		} else {
			layers = happyBedsLayers(selections, mode);
		}

		if (source === 'hybrid') {
			return mergeHybridMedia(layers, selections);
		}

		if (shouldApplyOptionOverrides()) {
			return applyOptionLayerOverrides(layers, selections);
		}

		return layers;
	}

	function demoLayers(selections) {
		var base = wcbcData.layerBase || '';
		var size = pick(selections, 'size') || 'small-double';
		var colour = resolveColourSlug(pick(selections, 'colour') || 'light-silver-velvet');
		var headboard = pick(selections, 'headboard');
		var baseDepth = pick(selections, 'base_depth') || '14-inch';
		var storage = normalizeStorage(pick(selections, 'storage') || 'no-drawers');
		var shape = headboardShape(headboard);
		var variant = headboardVariant(headboard);
		var drawerSuffix = hasDrawers(storage) ? '-drawers' : '';

		var layers = {
			shadow: base + 'shadow-only.png',
			legs: base + 'legs/' + size + '.png',
			storage_back: demoStorageUrl('storage_back', base, storage, size, colour, baseDepth),
			base: base + 'base/' + size + '/' + colour + '/' + baseDepth + drawerSuffix + '.png',
			headboard: base + 'transparent.png',
			storage_1: demoStorageUrl('storage_1', base, storage, size, colour, baseDepth),
			storage_2: demoStorageUrl('storage_2', base, storage, size, colour, baseDepth),
			storage_3: demoStorageUrl('storage_3', base, storage, size, colour, baseDepth),
		};

		if (shape !== 'none') {
			if (shape === 'cornell') {
				layers.headboard = base + 'headboard/' + size + '/' + shape + '/' + variant + '/' + colour + '.png';
			} else {
				layers.headboard = base + 'headboard/' + size + '/' + shape + '/plain/' + colour + '.png';
			}
		}

		return layers;
	}

	function resolveDefaultSelection(group) {
		if (!group.options || !group.options.length) {
			return '';
		}
		if (group.id === 'size' || group.id === 'colour') {
			return group.options[0].id;
		}
		var defaults = wcbcData.config.defaults || {};
		var selected = defaults[group.id] || '';
		var found = group.options.some(function (opt) {
			return opt.id === selected;
		});
		return found ? selected : group.options[0].id;
	}

	function syncGroupSelectionUI(groupId, optionId) {
		var $section = $('.wcbc-accordian-body[data-tabid="' + groupId + '"]');
		var $radios = $section.length
			? $section.find('input.wcbc-radio[data-group="' + groupId + '"]')
			: $('input.wcbc-radio[data-group="' + groupId + '"]');

		$radios.each(function () {
			var $input = $(this);
			var isMatch = $input.val() === optionId;
			var $option = $input.closest('li.wcbc-option');
			$input.prop('checked', isMatch);
			$option.toggleClass('is-selected', isMatch);
			$option.find('label').first().toggleClass('is-checked', isMatch);
		});
	}

	function initSelections() {
		wcbcData.config.groups.forEach(function (group) {
			var selected = resolveDefaultSelection(group);
			state.selections[group.id] = selected;
			syncGroupSelectionUI(group.id, selected);
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

	function syncLayerHiddenFields(layers) {
		if (!layers) {
			return;
		}
		var map = {
			shadow: 'bs_shadow',
			legs: 'bs_legs',
			headboard: 'bs_headboard',
			storage_back: 'bs_storage_back',
			base: 'bs_base',
			storage_1: 'bs_storage_1',
			storage_2: 'bs_storage_2',
			storage_3: 'bs_storage_3',
		};
		Object.keys(map).forEach(function (layer) {
			var $field = $('#' + map[layer]);
			if ($field.length) {
				$field.val(layers[layer] || '');
			}
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
		syncLayerHiddenFields(layers);
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
			drawers_open: state.drawersOpen ? '1' : '0',
		});
	}

	function onSelectionChange(groupId, optionId) {
		state.selections[groupId] = optionId;
		syncHiddenFields();
		syncGroupSelectionUI(groupId, optionId);

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
			if (response.data.selections) {
				Object.keys(response.data.selections).forEach(function (groupId) {
					state.selections[groupId] = response.data.selections[groupId];
					syncGroupSelectionUI(groupId, response.data.selections[groupId]);
				});
				syncHiddenFields();
			}
			updateLabels(response.data.labels);
		});
	}

	function selectOptionRadio(radio) {
		var $radio = $(radio);
		if (!$radio.length) {
			return;
		}
		var groupId = $radio.attr('data-group') || $radio.data('group');
		var optionId = $radio.val();
		if (!groupId || !optionId) {
			return;
		}
		onSelectionChange(groupId, optionId);
	}

	window.wcbcSelectOption = selectOptionRadio;

	function bindOptions() {
		// Capture phase — runs before theme/accordion handlers that may block bubbling.
		document.addEventListener(
			'click',
			function (e) {
				var option = e.target.closest('.wcbc-option');
				if (!option || !option.closest('.wcbc-accordian')) {
					return;
				}
				var radio = option.querySelector('input.wcbc-radio');
				if (!radio) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				radio.checked = true;
				selectOptionRadio(radio);
			},
			true
		);

		$(document).on('change', '.wcbc-accordian input.wcbc-radio', function () {
			if (this.checked) {
				selectOptionRadio(this);
			}
		});
	}

	function bindOptionFilters() {
		$(document).on('click', '.wcbc-filter-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var filter = $(this).data('filter');
			var $section = $(this).closest('dd');
			var filterType = $section.data('filter-type') || 'shape';

			$section.find('.wcbc-filter-btn').removeClass('is-checked');
			$(this).addClass('is-checked');

			$section.find('.wcbc-filterable-grid li').each(function () {
				var matchKey = filterType === 'fabric' ? ($(this).data('fabric') || '') : ($(this).data('shape') || '');
				var isSelected = $(this).find('input.wcbc-radio').is(':checked');
				$(this).toggleClass('wcbc-filter-hidden', matchKey !== filter && !isSelected);
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
		bindOptionFilters();
		bindDrawerToggle();
		relocateAddToCart();
	});

	$(window).on('load', relocateAddToCart);
})(jQuery);
