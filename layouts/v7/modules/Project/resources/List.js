/**
 * Project List (MANAGEMENT): neutralize floatThead / bottom scroller (MkSalesListShared handles footer).
 */
(function ($) {
	'use strict';

	function isManagementProjectList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Project' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		return (b.getAttribute('data-app') || '').toUpperCase() === 'MANAGEMENT';
	}

	function destroyPerfectScrollbar($tc) {
		if (!$tc || !$tc.length) {
			return;
		}
		try {
			if ($.fn.perfectScrollbar) {
				$tc.perfectScrollbar('destroy');
			}
		} catch (e) {
			/* ignore */
		}
		$tc.removeClass('ps ps--active-x ps--active-y ps--scrolling-x ps--scrolling-y');
		$tc.find('.ps__rail-x, .ps__rail-y, .ps__thumb-x, .ps__thumb-y').remove();
	}

	function fixListScrollContainer() {
		if (!isManagementProjectList()) {
			return;
		}
		var $tc = $('#listViewContent #table-content');
		if (!$tc.length) {
			return;
		}

		destroyPerfectScrollbar($tc);

		$tc.css({
			position: 'relative',
			width: '100%',
			height: 'auto',
			maxHeight: '',
			overflowX: 'auto',
			overflowY: 'auto',
			WebkitOverflowScrolling: 'touch',
			pointerEvents: 'auto'
		});

		$('#listViewContent #scroller_wrapper.bottom-fixed-scroll, #listViewContent .bottom-fixed-scroll').css({
			display: 'none',
			height: 0,
			margin: 0,
			padding: 0,
			border: 'none',
			overflow: 'hidden',
			pointerEvents: 'none',
			position: 'absolute',
			left: '-9999px',
			width: 0
		});

		var $table = $('#listViewContent #listview-table');
		if ($table.length && $.fn.floatThead) {
			try {
				$table.floatThead('destroy');
			} catch (e2) {
				/* not initialized */
			}
		}
		$table.removeClass('floatThead-table');
		$('.floatThead-container').remove();
	}

	function patchVtigerListScrollHooks() {
		if (!window.Vtiger_List_Js || !Vtiger_List_Js.prototype || Vtiger_List_Js.prototype.__mkProjectScrollPatched) {
			return !!window.Vtiger_List_Js;
		}
		var proto = Vtiger_List_Js.prototype;
		var origRegister = proto.registerFloatingThead;
		var origReflow = proto.reflowList;

		proto.registerFloatingThead = function () {
			if (isManagementProjectList()) {
				fixListScrollContainer();
				return;
			}
			return origRegister.apply(this, arguments);
		};

		proto.reflowList = function () {
			if (isManagementProjectList()) {
				fixListScrollContainer();
				return;
			}
			return origReflow.apply(this, arguments);
		};

		proto.__mkProjectScrollPatched = true;
		return true;
	}

	function init() {
		if (!isManagementProjectList()) {
			return;
		}
		patchVtigerListScrollHooks();
		fixListScrollContainer();
		setTimeout(fixListScrollContainer, 0);
		setTimeout(fixListScrollContainer, 150);
		$(document).on('mkProjectListPostLoad', function () {
			setTimeout(fixListScrollContainer, 0);
		});
		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(fixListScrollContainer, 80);
			});
			app.event.on('Vtiger.Post.MenuToggle', function () {
				setTimeout(fixListScrollContainer, 80);
			});
		}
		$(document).on(
			'post.overLayDetailView.post.overLayEditView.post.overLaySummaryView',
			function () {
				setTimeout(fixListScrollContainer, 80);
			}
		);
	}

	$(init);
})();
