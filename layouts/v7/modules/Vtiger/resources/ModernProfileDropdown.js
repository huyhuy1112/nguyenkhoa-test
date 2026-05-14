/**
 * Profile dropdown: ESC close, dark-mode toggle (visual only), no backend persistence.
 */
(function ($) {
	'use strict';

	function closeProfileDropdown() {
		var $host = $('li.modern-profile-dropdown-host.open');
		if ($host.length) {
			$host.removeClass('open');
			$host.find('> div > a.dropdown-toggle').attr('aria-expanded', 'false');
		}
	}

	function bindAvatarFallback() {
		$('.modern-profile-dropdown .modern-profile-avatar-img').each(function () {
			var img = this;
			if (img.complete && img.naturalWidth === 0) {
				showAvatarFallback(img);
				return;
			}
			$(img).one('error', function () {
				showAvatarFallback(this);
			});
		});
	}

	function showAvatarFallback(imgEl) {
		var $img = $(imgEl);
		var $fb = $img.next('.modern-profile-avatar-fallback');
		$img.hide();
		$fb.removeClass('modern-profile-avatar-fallback--hidden').css('display', 'flex');
	}

	function init() {
		bindAvatarFallback();

		$(document).on('keydown.modernProfileDropdown', function (e) {
			if (e.keyCode !== 27) {
				return;
			}
			if (!$('li.modern-profile-dropdown-host.open').length) {
				return;
			}
			closeProfileDropdown();
		});

		$(document).on('click', '#modern-profile-dark-toggle', function (ev) {
			ev.preventDefault();
			ev.stopPropagation();
			var $btn = $(this);
			$btn.toggleClass('modern-profile-dark-toggle--on');
			var on = $btn.hasClass('modern-profile-dark-toggle--on');
			$btn.attr('aria-checked', on ? 'true' : 'false');
		});
	}

	$(init);
})(jQuery);
