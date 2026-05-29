/**
 * Campaigns Create (MARKETING) — top Save button triggers vtiger save.
 */
(function ($) {
	'use strict';

	function isScoped() {
		return (
			$('body').data('module') === 'Campaigns' &&
			$('body').data('view') === 'Edit' &&
			$('body').data('app') === 'MARKETING' &&
			$('#mkCampCreateWorkspace').length &&
			!$('input[name="record"]').val()
		);
	}

	function $form() {
		return $('#mkCampFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function triggerSave() {
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
	}

	function bindActions() {
		$('#mkCampSaveTop')
			.off('click.mkCampSave')
			.on('click.mkCampSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$(document)
			.off('keydown.mkCampCreate')
			.on('keydown.mkCampCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkCampFormHost').length) {
						return;
					}
					e.preventDefault();
					triggerSave();
				}
			});
	}

	function runEnhancements() {
		if (!isScoped()) {
			return;
		}
		bindActions();
	}

	function init() {
		if (!isScoped()) {
			return;
		}
		runEnhancements();
		setTimeout(runEnhancements, 150);
		setTimeout(runEnhancements, 600);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})($);

