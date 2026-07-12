(function ($) {
	'use strict';

	function getAdmin() {
		return window.wcbcAdmin || {};
	}

	function showSaved($container) {
		var $msg = $container.find('.wcbc-saved-msg');
		if (!$msg.length) {
			$msg = $('<span class="wcbc-saved-msg" style="color:#00a32a;font-size:11px;margin-left:6px;">Saved</span>');
			$container.append($msg);
		}
		$msg.stop(true, true).fadeIn(100).delay(2000).fadeOut(400);
	}

	function persistOptionSwatch($input) {
		var admin = getAdmin();
		if (!admin.productId) {
			if (admin.i18n && admin.i18n.noProduct) {
				alert(admin.i18n.noProduct);
			}
			return;
		}
		if (!admin.ajaxUrl || !admin.nonce) {
			return;
		}

		var groupId = $input.data('group-id');
		var optionId = $input.data('option-id');
		if (!groupId || !optionId) {
			return;
		}

		$.post(admin.ajaxUrl, {
			action: 'wcbc_save_option_swatch',
			nonce: admin.nonce,
			product_id: admin.productId,
			group_id: groupId,
			option_id: optionId,
			attachment_id: $input.val() || 0
		}).done(function (res) {
			if (res && res.success) {
				showSaved($input.closest('td'));
			}
		});
	}

	function persistVariationLayer($input) {
		var admin = getAdmin();
		if (!admin.productId) {
			if (admin.i18n && admin.i18n.noProduct) {
				alert(admin.i18n.noProduct);
			}
			return;
		}
		if (!admin.ajaxUrl || !admin.nonce) {
			return;
		}

		var layerType = $input.data('layer-type');
		var sizeId = $input.data('size-id');
		if (!layerType || !sizeId) {
			return;
		}

		var data = {
			action: 'wcbc_save_variation_layer',
			nonce: admin.nonce,
			product_id: admin.productId,
			layer_type: layerType,
			size_id: sizeId,
			attachment_id: $input.val() || 0
		};

		if (layerType === 'colour') {
			data.colour_id = $input.data('colour-id');
			data.layer = $input.data('layer');
		} else {
			data.style_id = $input.data('style-id');
			data.colour_id = $input.data('colour-id');
		}

		$.post(admin.ajaxUrl, data).done(function (res) {
			if (res && res.success) {
				showSaved($input.closest('.wcbc-layer-media-item'));
			}
		});
	}

	function persistInput($input) {
		if ($input.hasClass('wcbc-option-image-id')) {
			persistOptionSwatch($input);
		} else if ($input.hasClass('wcbc-variation-layer-input')) {
			persistVariationLayer($input);
		}
	}

	function openMediaPicker($input, title) {
		var frame = wp.media({
			title: title || 'Select image',
			button: { text: 'Use image' },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$input.val(attachment.id);

			var $preview = $input.siblings('.wcbc-option-thumb, .wcbc-layer-media-preview').first();
			if ($preview.length) {
				$preview.attr('src', attachment.url).removeClass('is-empty');
			}

			$input.siblings('.wcbc-remove-option-image, .wcbc-remove-variation-layer').show();
			persistInput($input);
		});

		frame.open();
	}

	$(document).on('click', '.wcbc-upload-option-image', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $input = $btn.closest('td').find('.wcbc-option-image-id');
		openMediaPicker($input, 'Select swatch image');
	});

	$(document).on('click', '.wcbc-remove-option-image', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $input = $btn.closest('td').find('.wcbc-option-image-id');
		$input.val('');
		$btn.closest('td').find('.wcbc-option-thumb').attr('src', '').addClass('is-empty');
		$btn.hide();
		persistInput($input);
	});

	$(document).on('click', '.wcbc-upload-variation-layer', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $input = $btn.siblings('.wcbc-variation-layer-input');
		openMediaPicker($input, $btn.data('title') || 'Select layer image');
	});

	$(document).on('click', '.wcbc-remove-variation-layer', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $input = $btn.siblings('.wcbc-variation-layer-input');
		$input.val('');
		$btn.siblings('.wcbc-layer-media-preview').attr('src', '').addClass('is-empty');
		$btn.hide();
		persistInput($input);
	});
})(jQuery);
