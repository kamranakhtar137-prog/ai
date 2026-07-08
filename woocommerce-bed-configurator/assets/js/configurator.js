/**
 * WooCommerce Bed Configurator frontend logic.
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

	function onSelectionChange(groupId, optionId, $label) {
		state.selections[groupId] = optionId;
		syncHiddenFields();

		$('input.wcbc-radio[data-group="' + groupId + '"]').each(function () {
			$(this).prop('checked', $(this).val() === optionId);
			$(this).closest('li').find('label').toggleClass('is-checked', $(this).val() === optionId);
		});

		if ($label && $label.length) {
			$label.addClass('is-checked');
		}

		calculate().done(function (response) {
			if (!response || !response.success) {
				return;
			}
			updatePrices(response.data.price_html);
			updateLayers(response.data.layers);
			updateLabels(response.data.labels);
		});
	}

	function bindAccordian() {
		$(document).on('click', '.wcbc-accordian-head', function (e) {
			e.preventDefault();
			var tabId = $(this).data('tabid');
			var $body = $('dd.wcbc-accordian-body[data-tabid="' + tabId + '"]');
			var isOpen = $(this).hasClass('isopen');

			$('.wcbc-accordian-head').removeClass('isopen');
			$('.wcbc-accordian-head .ev_ln_filter_chevron').addClass('ev_ln_filter_chevron_closed');
			$('.wcbc-accordian-body').removeClass('isopen').slideUp(180);

			if (!isOpen) {
				$(this).addClass('isopen');
				$(this).find('.ev_ln_filter_chevron').removeClass('ev_ln_filter_chevron_closed');
				$body.addClass('isopen').slideDown(180);
			}
		});
	}

	function bindOptions() {
		$(document).on('change', 'input.wcbc-radio', function () {
			onSelectionChange($(this).data('group'), $(this).val(), $(this).closest('li').find('label'));
		});

		$(document).on('click', '.wcbc-option label', function (e) {
			e.preventDefault();
			var $radio = $(this).closest('li').find('input.wcbc-radio');
			if (!$radio.length) {
				return;
			}
			$radio.prop('checked', true).trigger('change');
		});
	}

	function bindHeadboardFilters() {
		$(document).on('click', '.wcbc-filter-btn', function (e) {
			e.preventDefault();
			var filter = $(this).data('filter');
			var $section = $(this).closest('dd');

			$section.find('.wcbc-filter-btn').removeClass('is-checked');
			$(this).addClass('is-checked');

			$section.find('.wcbc-headboard-grid li').each(function () {
				var shape = $(this).data('shape') || '';
				var isSelected = $(this).find('input.wcbc-radio').is(':checked');
				var show = shape === filter || isSelected;
				$(this).toggleClass('wcbc-filter-hidden', !show);
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

		if ($wrap.length && $btn.length) {
			$btn.detach()
				.appendTo($wrap)
				.addClass('wcbc-add-to-cart action checkout w-full text-base')
				.attr('type', 'submit');

			$qty.addClass('wcbc-qty-hidden').hide();
			$form.addClass('wcbc-form-relocated');
			return true;
		}

		return false;
	}

	function relocateAddToCart() {
		if (setupCartForm()) {
			return;
		}

		// WooCommerce may render the cart form after our script init.
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
		bindAccordian();
		bindOptions();
		bindHeadboardFilters();
		bindDrawerToggle();
		relocateAddToCart();
	});

	$(window).on('load', relocateAddToCart);
})(jQuery);
