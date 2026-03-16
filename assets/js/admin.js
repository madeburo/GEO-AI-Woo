/**
 * GEO AI Search Optimization — Admin JavaScript
 *
 * @package GeoAiWoo
 */

(function ($) {
	'use strict';

	// Regenerate llms.txt button
	$('#geo-ai-for-woocommerce-regenerate').on('click', function () {
		var $btn = $(this);
		var $status = $('#geo-ai-for-woocommerce-regenerate-status');

		$btn.prop('disabled', true);
		$status
			.removeClass('success error')
			.text(geo_ai_woo_admin.regenerating);

		$.post(
			ajaxurl,
			{
				action: 'geo_ai_woo_regenerate',
				nonce: geo_ai_woo_admin.nonce,
			},
			function (response) {
				$btn.prop('disabled', false);
				if (response.success) {
					$status.addClass('success').text(geo_ai_woo_admin.done);
					// Refresh preview if visible
					loadPreview();
				} else {
					$status.addClass('error').text(geo_ai_woo_admin.error);
				}
				setTimeout(function () {
					$status.removeClass('success error').text('');
				}, 3000);
			}
		).fail(function () {
			$btn.prop('disabled', false);
			$status.addClass('error').text(geo_ai_woo_admin.error);
		});
	});

	// Live preview — load button
	$('#geo-ai-for-woocommerce-load-preview').on('click', function () {
		loadPreview();
	});

	/**
	 * Load llms.txt preview via AJAX
	 */
	function loadPreview() {
		var $preview = $('#geo-ai-for-woocommerce-preview-content');
		var $btn = $('#geo-ai-for-woocommerce-load-preview');

		if (!$preview.length) {
			return;
		}

		$btn.prop('disabled', true);
		$preview.text(geo_ai_woo_admin.loading || 'Loading...');

		$.post(
			ajaxurl,
			{
				action: 'geo_ai_woo_preview',
				nonce: geo_ai_woo_admin.preview_nonce,
			},
			function (response) {
				$btn.prop('disabled', false);
				if (response.success && response.data && response.data.content) {
					$preview.text(response.data.content);
				} else {
					$preview.text(geo_ai_woo_admin.error || 'Error loading preview.');
				}
			}
		).fail(function () {
			$btn.prop('disabled', false);
			$preview.text(geo_ai_woo_admin.error || 'Error loading preview.');
		});
	}

	// AI Description character counter (meta box)
	$('#geo_ai_woo_description').on('input', function () {
		var $textarea = $(this);
		var maxLength = 200;
		var currentLength = $textarea.val().length;

		var $counter = $textarea.next('.geo-ai-for-woocommerce-char-count');
		if (!$counter.length) {
			$counter = $(
				'<span class="description geo-ai-for-woocommerce-char-count"></span>'
			);
			$textarea.after($counter);
		}

		$counter.text(currentLength + '/' + maxLength);

		if (currentLength > maxLength) {
			$counter.css('color', '#dc3232');
		} else {
			$counter.css('color', '');
		}
	});

	// Dismiss admin notices
	$(document).on('click', '.geo-ai-for-woocommerce-notice .notice-dismiss', function () {
		var $notice = $(this).closest('.geo-ai-for-woocommerce-notice');
		var noticeId = $notice.data('notice-id');

		if (noticeId) {
			$.post(ajaxurl, {
				action: 'geo_ai_woo_dismiss_notice',
				nonce: geo_ai_woo_admin.nonce,
				notice_id: noticeId,
			});
		}
	});

	// AI Generate button (single post — meta box & WC panel)
	$(document).on('click', '.geo-ai-for-woocommerce-generate-btn', function () {
		var $btn = $(this);
		var $status = $btn.siblings('.geo-ai-for-woocommerce-generate-status');
		var postId = $btn.data('post-id');

		$btn.prop('disabled', true);
		$status.text(geo_ai_woo_admin.ai_generating || 'Generating...');

		$.post(
			ajaxurl,
			{
				action: 'geo_ai_woo_ai_generate',
				nonce: geo_ai_woo_admin.ai_nonce,
				post_id: postId,
			},
			function (response) {
				$btn.prop('disabled', false);
				if (response.success && response.data.description) {
					// Fill the description textarea
					var $textarea =
						$('#geo_ai_woo_description, #_geo_ai_woo_description');
					$textarea.val(response.data.description).trigger('input');
					$status
						.addClass('success')
						.text(geo_ai_woo_admin.ai_generated || 'Generated!');
				} else {
					var msg =
						response.data && response.data.message
							? response.data.message
							: geo_ai_woo_admin.error;
					$status.addClass('error').text(msg);
				}
				setTimeout(function () {
					$status.removeClass('success error').text('');
				}, 3000);
			}
		).fail(function () {
			$btn.prop('disabled', false);
			$status.addClass('error').text(geo_ai_woo_admin.error);
		});
	});

	// Bulk AI Generate button (settings page)
	$('#geo-ai-for-woocommerce-bulk-generate').on('click', function () {
		var $btn = $(this);
		var $progress = $('#geo-ai-for-woocommerce-bulk-progress');
		var $fill = $progress.find('.geo-ai-for-woocommerce-progress-fill');
		var $text = $progress.find('.geo-ai-for-woocommerce-progress-text');

		$btn.prop('disabled', true);
		$progress.show();
		$fill.css('width', '0%');
		$text.text(geo_ai_woo_admin.ai_bulk_running || 'Processing...');

		// Start bulk generation
		$.post(
			ajaxurl,
			{
				action: 'geo_ai_woo_ai_bulk_generate',
				nonce: geo_ai_woo_admin.ai_bulk_nonce,
			},
			function (response) {
				if (response.success) {
					updateBulkProgress(response.data);
				} else {
					$btn.prop('disabled', false);
					var msg =
						response.data && response.data.message
							? response.data.message
							: geo_ai_woo_admin.error;
					$text.text(msg);
				}
			}
		).fail(function () {
			$btn.prop('disabled', false);
			$text.text(geo_ai_woo_admin.error);
		});
	});

	/**
	 * Update bulk progress and continue if needed
	 */
	function updateBulkProgress(data) {
		var $btn = $('#geo-ai-for-woocommerce-bulk-generate');
		var $fill = $('#geo-ai-for-woocommerce-bulk-progress .geo-ai-for-woocommerce-progress-fill');
		var $text = $('#geo-ai-for-woocommerce-bulk-progress .geo-ai-for-woocommerce-progress-text');

		var percent = data.total > 0 ? (data.processed / data.total) * 100 : 100;
		$fill.css('width', percent + '%');
		$text.text(data.processed + '/' + data.total + ' (' + data.succeeded + ' succeeded, ' + data.failed + ' failed)');

		if (data.completed) {
			$btn.prop('disabled', false);
			$text.text(
				(geo_ai_woo_admin.ai_bulk_complete || 'Complete!') +
					' ' +
					data.succeeded +
					' generated, ' +
					data.failed +
					' failed.'
			);
			return;
		}

		// Continue processing next batch after a short delay
		setTimeout(function () {
			$.post(
				ajaxurl,
				{
					action: 'geo_ai_woo_ai_bulk_progress',
					nonce: geo_ai_woo_admin.ai_bulk_nonce,
				},
				function (response) {
					if (response.success) {
						updateBulkProgress(response.data);
					} else {
						$btn.prop('disabled', false);
						$text.text(geo_ai_woo_admin.error);
					}
				}
			).fail(function () {
				$btn.prop('disabled', false);
				$text.text(geo_ai_woo_admin.error);
			});
		}, 2000);
	}
})(jQuery);
