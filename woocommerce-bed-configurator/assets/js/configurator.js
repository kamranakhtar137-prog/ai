/**
 * WooCommerce Bed Configurator — options, pricing, cart.
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

	function initSelections() {
		wcbcData.config.groups.forEach(function (group) {
			var def = wcbcData.config.defaults[group.id] || '';
			var $checked = $('input.wcbc-radio[data-group="' + group.id + '"]:checked');
			state.selections[group.id] = $checked.length ? $checked.val() : def;
		});
		syncHiddenFields();
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

	function updateLayers(layers) {
		if (!layers) {
			return;
		}
		wcbcData.layers.forEach(function (layer) {
			if (layers[layer]) {
				var $img = $('#dynamic_' + layer);
				if ($img.attr('src') !== layers[layer]) {
					$img.attr('src', layers[layer]);
				}
			}
		});
	}

	function updatePrices(priceHtml) {
		if (priceHtml) {
			$('.wcbc-live-price').html(priceHtml);
		}
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

		calculate().done(function (response) {
			if (!response || !response.success) {
				return;
			}
			updatePrices(response.data.price_html);
			updateLayers(response.data.layers);
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
				.attr('type', 'submit');

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
