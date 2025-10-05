/**
 * Admin JavaScript for Login & Register Forms Overhaul
 *
 * @package LoginRegisterFormsOverhaul
 * @since   1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		
		// Media uploader for logo
		var mediaUploader;
		$('.lrfo-upload-image').on('click', function(e) {
			e.preventDefault();
			
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}
			
			mediaUploader = wp.media({
				title: 'Choose Logo Image',
				button: {
					text: 'Use this image'
				},
				multiple: false
			});
			
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				$('#logo_url').val(attachment.url);
			});
			
			mediaUploader.open();
		});
		
		// Generate random code
		$('.lrfo-generate-code').on('click', function(e) {
			e.preventDefault();
			var code = 'INV-' + generateRandomString(8);
			$('#code_string').val(code);
		});
		
		function generateRandomString(length) {
			var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			var result = '';
			for (var i = 0; i < length; i++) {
				result += chars.charAt(Math.floor(Math.random() * chars.length));
			}
			return result;
		}
		
		// Tab switching with URL hash support
		$('.nav-tab').on('click', function(e) {
			e.preventDefault();
			var tab = $(this).attr('href').split('tab=')[1];
			
			$('.nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			
			$('.lrfo-tab-content').hide();
			$('#tab-' + tab).show();
		});
		
		// Confirm delete
		$('button[type="submit"]').on('click', function(e) {
			if ($(this).text().indexOf('Delete') !== -1) {
				if (!confirm('Are you sure you want to delete this code?')) {
					e.preventDefault();
					return false;
				}
			}
		});
	});
	
})(jQuery);
