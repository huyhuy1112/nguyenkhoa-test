/* global jQuery */
(function ($) {
	'use strict';

	function isManagementTeamsList() {
		var body = document.body;
		return (
			body &&
			body.getAttribute('data-module') === 'Teams' &&
			body.getAttribute('data-view') === 'List' &&
			(body.getAttribute('data-app') || '').toUpperCase() === 'MANAGEMENT'
		);
	}

	$(function () {
		if (!isManagementTeamsList()) {
			return;
		}
		document.documentElement.classList.add('mk-teams-list-management');
	});
})();
