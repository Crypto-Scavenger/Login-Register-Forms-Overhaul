/**
 * Login Forms Overhaul - Login Page Behavior
 */

(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		if (typeof lfoSettings === 'undefined') {
			return;
		}

		if (lfoSettings.hideSessionExpire === '1') {
			window.addEventListener('heartbeat-tick', function(e, data) {
				if (data && data['wp-auth-check']) {
					e.stopImmediatePropagation();
					return false;
				}
			}, true);

			if (typeof wp !== 'undefined' && typeof wp.heartbeat !== 'undefined') {
				wp.heartbeat.interval('fast');
			}
		}

		var inputs = document.querySelectorAll('#loginform input[type="text"], #loginform input[type="password"]');
		inputs.forEach(function(input) {
			input.addEventListener('focus', function() {
				this.style.transform = 'scale(1.02)';
			});
			
			input.addEventListener('blur', function() {
				this.style.transform = 'scale(1)';
			});
		});

		var form = document.getElementById('loginform');
		if (form) {
			form.addEventListener('submit', function() {
				var submitBtn = form.querySelector('#wp-submit');
				if (submitBtn) {
					submitBtn.disabled = true;
					submitBtn.value = 'AUTHENTICATING...';
				}
			});
		}
	});
})();
