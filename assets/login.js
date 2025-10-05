/**
 * Frontend JavaScript for Login & Register Forms Overhaul
 *
 * @package LoginRegisterFormsOverhaul
 * @since   1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		
		// Hide session expire modal if enabled
		if (typeof lrfoData !== 'undefined' && lrfoData.hideSessionExpire) {
			
			// Disable heartbeat session checks
			if (typeof wp !== 'undefined' && typeof wp.heartbeat !== 'undefined') {
				$(document).on('heartbeat-tick', function(event, data) {
					if (data['wp-auth-check']) {
						return false;
					}
				});
			}
			
			// Hide any auth-check modal that appears
			var checkForModal = setInterval(function() {
				var modal = $('#wp-auth-check-wrap');
				if (modal.length) {
					modal.remove();
					clearInterval(checkForModal);
				}
			}, 500);
			
			// Remove auth-check completely
			if (typeof wp !== 'undefined' && typeof wp.authcheck !== 'undefined') {
				wp.authcheck = {};
			}
		}
		
		// Add Font Awesome icon to invite code field
		var inviteCodeField = $('#invite_code').closest('p');
		if (inviteCodeField.length) {
			inviteCodeField.addClass('invite-code-field');
		}
		
		// Add smooth focus effects
		$('input[type="text"], input[type="password"], input[type="email"]').on('focus', function() {
			$(this).parent().addClass('focused');
		}).on('blur', function() {
			$(this).parent().removeClass('focused');
		});
		
		// Prevent form submission spam
		$('form').on('submit', function() {
			var submitBtn = $(this).find('input[type="submit"]');
			submitBtn.prop('disabled', true);
			
			setTimeout(function() {
				submitBtn.prop('disabled', false);
			}, 3000);
		});
		
		// Add loading effect to submit button
		$('.wp-core-ui .button-primary').on('click', function() {
			var btn = $(this);
			if (!btn.hasClass('loading')) {
				btn.addClass('loading');
				setTimeout(function() {
					btn.removeClass('loading');
				}, 2000);
			}
		});
		
	});
	
})(jQuery);
