/**
 * DocumentTemplate list (TOOLS): mark page ready.
 */
(function () {
	'use strict';

	function isDocumentTemplateToolsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'DocumentTemplate' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		if ((b.getAttribute('data-app') || '').toUpperCase() === 'TOOLS') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'DocumentTemplate' && params.get('view') === 'List' && params.get('app') === 'TOOLS';
	}

	function boot() {
		if (!isDocumentTemplateToolsList()) {
			return;
		}
		document.documentElement.classList.add('mk-document-template-list-tools');
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
