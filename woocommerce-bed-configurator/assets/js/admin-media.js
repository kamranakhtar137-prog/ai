(function ($) {
	'use strict';

	function openMediaFrame(button, onSelect) {
		var frame = wp.media({
			title: button.data('title') || 'Select image',
			button: { text: button.data('button') || 'Use image' },
			multiple: false,
			library: { type: 'image' },
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			onSelect(attachment);
		});

		frame.open();
	}

	$(document).on('click', '.wcbc-upload-layer', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var layer = $btn.data('layer');
		var $wrap = $btn.closest('.wcbc-layer-media-item');
		var $input = $wrap.find('input[name="wcbc_layer_media[' + layer + ']"]');
		var $preview = $wrap.find('.wcbc-layer-media-preview');

		openMediaFrame($btn, function (attachment) {
			$input.val(attachment.id);
			$preview.attr('src', attachment.url).removeClass('is-empty').show();
			$wrap.find('.wcbc-remove-layer').show();
		});
	});

	$(document).on('click', '.wcbc-remove-layer', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.wcbc-layer-media-item');
		$wrap.find('input[type="hidden"]').val('');
		$wrap.find('.wcbc-layer-media-preview').attr('src', '').addClass('is-empty').hide();
		$(this).hide();
	});

	$(document).on('click', '.wcbc-upload-option-image', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var group = $btn.data('group');
		var option = $btn.data('option');
		var $row = $btn.closest('tr');
		var $input = $row.find('input.wcbc-option-image-id');
		var $preview = $row.find('.wcbc-option-thumb');

		openMediaFrame($btn, function (attachment) {
			$input.val(attachment.id);
			$preview.attr('src', attachment.url).removeClass('is-empty').show();
			$row.find('.wcbc-remove-option-image').show();
			syncOptionImageToJson(group, option, attachment.url);
		});
	});

	$(document).on('click', '.wcbc-remove-option-image', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var group = $btn.data('group');
		var option = $btn.data('option');
		var $row = $btn.closest('tr');
		$row.find('input.wcbc-option-image-id').val('');
		$row.find('.wcbc-option-thumb').attr('src', '').addClass('is-empty').hide();
		$btn.hide();
		syncOptionImageToJson(group, option, '');
	});

	function syncOptionImageToJson(groupId, optionId, url) {
		var $ta = $('#wcbc_config_json');
		if (!$ta.length) {
			return;
		}
		try {
			var config = JSON.parse($ta.val());
			if (!config.groups) {
				return;
			}
			config.groups.forEach(function (group) {
				if (group.id !== groupId || !group.options) {
					return;
				}
				group.options.forEach(function (opt) {
					if (opt.id === optionId) {
						opt.image = url || '';
					}
				});
			});
			$ta.val(JSON.stringify(config, null, 2));
		} catch (err) {
			// Keep JSON textarea unchanged if invalid.
		}
	}

	$('#wcbc_image_source').on('change', function () {
		var val = $(this).val();
		$('.wcbc-layer-media-section').toggle(val === 'media' || val === 'hybrid');
	}).trigger('change');
})(jQuery);
