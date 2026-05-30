/* global app, jQuery */
(function ($) {
	'use strict';

	function resolveMainPageApp() {
		var appName = 'MANAGEMENT';
		var $body = $('body');
		if ($body.attr('data-app') !== appName) {
			$body.attr('data-app', appName);
		}
		try {
			var url = new URL(window.location.href);
			if (url.searchParams.get('app') !== appName) {
				url.searchParams.set('app', appName);
				window.history.replaceState({}, '', url.toString());
			}
		} catch (e) { /* ignore */ }
	}

	$(function () {
		resolveMainPageApp();
		if (typeof app !== 'undefined' && app.event) {
			app.event.on('post.AjaxSave', resolveMainPageApp);
		}
	});
})(jQuery);
