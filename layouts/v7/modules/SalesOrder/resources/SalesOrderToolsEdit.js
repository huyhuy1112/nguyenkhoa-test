/**
 * SalesOrder Create/Edit (TOOLS) — premium workspace enhancements.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260605_so_edit1';

	var BLOCK_ICONS = {
		order_info: 'fa-info-circle',
		approval: 'fa-check-circle',
		system: 'fa-cog'
	};

	function isScoped() {
		return (
			$('body').data('module') === 'SalesOrder' &&
			$('body').data('view') === 'Edit' &&
			$('body').data('app') === 'TOOLS' &&
			$('#mkSoToolsCreateWorkspace').length
		);
	}

	function $form() {
		return $('#mkSoToolsFormHost').find('form#EditView').first();
	}

	function styleFieldBlocks() {
		$form()
			.find('.mk-so-tools-block')
			.each(function () {
				var $block = $(this);
				var blockKey = $block.attr('data-block') || '';
				var $header = $block.find('.mk-so-tools-block__header').first();
				if (!$header.find('.mk-so-tools-block__icon').length && BLOCK_ICONS[blockKey]) {
					$header.prepend(
						$('<span>', { class: 'mk-so-tools-block__icon', 'aria-hidden': 'true' }).append(
							$('<i>', { class: 'fa ' + BLOCK_ICONS[blockKey] })
						)
					);
				}
				$block.find('> hr').hide();
			});
	}

	function bindActions() {
		$('#mkSoToolsSaveTop')
			.off('click.mkSoToolsSave')
			.on('click.mkSoToolsSave', function (e) {
				e.preventDefault();
				var $save = $form().find('.saveButton').first();
				if ($save.length) {
					$save.trigger('click');
				} else {
					$form().trigger('submit');
				}
			});

		$(document)
			.off('keydown.mkSoToolsEdit')
			.on('keydown.mkSoToolsEdit', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkSoToolsFormHost').length) {
						return;
					}
					e.preventDefault();
					$('#mkSoToolsSaveTop').trigger('click');
				}
			});
	}

	function runEnhancements() {
		if (!isScoped()) {
			return;
		}
		styleFieldBlocks();
		bindActions();
	}

	function init() {
		if (!isScoped()) {
			return;
		}
		runEnhancements();
		setTimeout(runEnhancements, 150);
		setTimeout(runEnhancements, 500);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	window.__mkSoToolsEditBuild = MK_BUILD;
})($);
