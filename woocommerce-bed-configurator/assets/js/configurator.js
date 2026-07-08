/**
 * WooCommerce Bed Configurator frontend logic.
 */
(function ($) {
	'use strict';

	if (typeof wcbcData === 'undefined') {
		return;
	}

	var state = {
		selections: {},
		drawersOpen: true,
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

	function formatMoney(amount) {
		var symbol = wcbcData.currency || '£';
		return symbol + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
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
				$('#dynamic_' + layer).attr('src', layers[layer]);
			}
		});
	}

	function updatePrices(priceHtml, formatted) {
		$('.wcbc-live-price').html(priceHtml || formatMoney(formatted));
	}

	function calculate() {
		return $.post(wcbcData.ajaxUrl, {
			action: 'wcbc_calculate_price',
			nonce: wcbcData.nonce,
			product_id: wcbcData.productId,
			selections: state.selections,
		});
	}

	function onSelectionChange(groupId, optionId, $label) {
		state.selections[groupId] = optionId;
		syncHiddenFields();

		$('input.wcbc-radio[data-group="' + groupId + '"]').each(function () {
			$(this).closest('li').find('label').removeClass('is-checked');
		});
		$label.addClass('is-checked');

		calculate().done(function (response) {
			if (!response.success) {
				return;
			}
			updatePrices(response.data.price_html, response.data.formatted);
			updateLayers(response.data.layers);
			updateLabels(response.data.labels);
		});
	}

	function bindAccordian() {
		$('.wcbc-accordian-head').on('click', function () {
			var tabId = $(this).data('tabid');
			var $body = $('dd[data-tabid="' + tabId + '"]');
			var isOpen = $(this).hasClass('isopen');

			$('.wcbc-accordian-head').removeClass('isopen');
			$('.wcbc-accordian-head .ev_ln_filter_chevron').addClass('ev_ln_filter_chevron_closed');
			$('.wcbc-accordian-body').removeClass('isopen').hide();

			if (!isOpen) {
				$(this).addClass('isopen');
				$(this).find('.ev_ln_filter_chevron').removeClass('ev_ln_filter_chevron_closed');
				$body.addClass('isopen').show();
			}
		});
	}

	function bindOptions() {
		$(document).on('change', 'input.wcbc-radio', function () {
			var groupId = $(this).data('group');
			onSelectionChange(groupId, $(this).val(), $(this).closest('li').find('label'));
		});
	}

	function bindHeadboardFilters() {
		$('.wcbc-filter-btn').on('click', function () {
			var filter = $(this).data('filter');
			$('.wcbc-filter-btn').removeClass('is-checked');
			$(this).addClass('is-checked');

			$('.wcbc-headboard-grid li').each(function () {
				var shape = $(this).data('shape');
				var show = filter === shape || $(this).find('input').is(':checked');
				$(this).toggleClass('wcbc-filter-hidden', !show && shape !== filter);
			});
		});
	}

	function bindDrawerToggle() {
		$('#drawer_checkbox').on('change', function () {
			state.drawersOpen = $(this).is(':checked');
			$('.wcbc-preview').toggleClass('wcbc-drawers-open', state.drawersOpen);
		});
	}

	function relocateAddToCart() {
		var $btn = $('form.cart .single_add_to_cart_button');
		var $qty = $('form.cart .quantity');
		if ($btn.length && $('.wcbc-cart-button-wrap').length) {
			$btn.appendTo('.wcbc-cart-button-wrap').addClass('wcbc-add-to-cart action checkout w-full text-base');
			$qty.hide();
		}
	}

	$(function () {
		initSelections();
		bindAccordian();
		bindOptions();
		bindHeadboardFilters();
		bindDrawerToggle();
		relocateAddToCart();
	});
})(jQuery);
