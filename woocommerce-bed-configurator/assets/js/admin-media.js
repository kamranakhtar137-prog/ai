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

	$(document).on('click', '.wcbc-upload-variation-layer', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $wrap = $btn.closest('.wcbc-layer-media-item');
		var $input = $wrap.find('input[type="hidden"]');
		var $preview = $wrap.find('.wcbc-layer-media-preview');

		openMediaFrame($btn, function (attachment) {
			$input.val(attachment.id);
			$preview.attr('src', attachment.url).removeClass('is-empty').show();
			$wrap.find('.wcbc-remove-variation-layer').show();
		});
	});

	$(document).on('click', '.wcbc-remove-variation-layer', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.wcbc-layer-media-item');
		$wrap.find('input[type="hidden"]').val('');
		$wrap.find('.wcbc-layer-media-preview').attr('src', '').addClass('is-empty').hide();
		$(this).hide();
	});

	$(document).on('click', '.wcbc-upload-option-image', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $row = $btn.closest('tr');
		var $input = $row.find('input.wcbc-option-image-id');
		var $preview = $row.find('.wcbc-option-thumb');

		openMediaFrame($btn, function (attachment) {
			$input.val(attachment.id);
			$preview.attr('src', attachment.url).removeClass('is-empty').show();
			$row.find('.wcbc-remove-option-image').show();
		});
	});

	$(document).on('click', '.wcbc-remove-option-image', function (e) {
		e.preventDefault();
		var $row = $(this).closest('tr');
		$row.find('input.wcbc-option-image-id').val('');
		$row.find('.wcbc-option-thumb').attr('src', '').addClass('is-empty').hide();
		$(this).hide();
	});

	$('#wcbc_image_source').on('change', function () {
		var val = $(this).val();
		$('.wcbc-variation-layers-section').toggle(val === 'media');
	}).trigger('change');
})(jQuery);
