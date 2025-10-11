/**
 * Login Forms Overhaul - Admin Interface
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		$('.form-table textarea').on('focus', function() {
			$(this).css('box-shadow', '0 0 5px rgba(34, 113, 177, 0.5)');
		}).on('blur', function() {
			$(this).css('box-shadow', 'none');
		});
	});
})(jQuery);
