/**
 * Potentials Opportunities list (Sales): scroll/floatThead neutralization, pagination footer placement,
 * semantic row styling (category pills, stage dots). No toolbar/pagination DOM moves or layout classes.
 * Scope: body[data-module="Potentials"][data-view="List"][data-app="SALES"]
 */
(function ($) {
	'use strict';

	function isSalesOpportunityList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Potentials' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		return (b.getAttribute('data-app') || '').toUpperCase() === 'SALES';
	}

	function normalizeToken(value) {
		return (value || '').toString().toLowerCase().replace(/[^a-z0-9]+/g, '');
	}

	function renderCategoryPills(context) {
		$(context).find('td[data-name="order_category"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-category-pill').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var token = normalizeToken(text);
			var klass = 'mk-cat-default';
			if (token.indexOf('project') === 0) {
				klass = 'mk-cat-project';
			} else if (token.indexOf('service') === 0) {
				klass = 'mk-cat-service';
			} else if (token.indexOf('consult') === 0) {
				klass = 'mk-cat-consulting';
			}
			$value.empty().append($('<span>', { 'class': 'mk-category-pill ' + klass, text: text }));
		});
	}

	function renderSalesStages(context) {
		$(context).find('td[data-name="sales_stage"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-stage').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var token = normalizeToken(text);
			var stageClass = 'mk-stage-qualification';
			if (token.indexOf('proposal') > -1) {
				stageClass = 'mk-stage-proposal';
			} else if (token.indexOf('negotiation') > -1) {
				stageClass = 'mk-stage-negotiation';
			} else if (token.indexOf('closedwon') > -1) {
				stageClass = 'mk-stage-closedwon';
			} else if (token.indexOf('closedlost') > -1) {
				stageClass = 'mk-stage-closedlost';
			}
			$value.empty().append(
				$('<span>', { 'class': 'mk-stage ' + stageClass }).append(
					$('<span>', { 'class': 'dot' }),
					document.createTextNode(text)
				)
			);
		});
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
		if (!isSalesOpportunityList()) {
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

		var $scroller = $('#listViewContent #scroller_wrapper.bottom-fixed-scroll, #listViewContent .bottom-fixed-scroll');
		$scroller.css({
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
		if (!window.Vtiger_List_Js || !Vtiger_List_Js.prototype || Vtiger_List_Js.prototype.__mkOppScrollPatched) {
			return !!window.Vtiger_List_Js;
		}
		var proto = Vtiger_List_Js.prototype;
		var origRegister = proto.registerFloatingThead;
		var origReflow = proto.reflowList;

		proto.registerFloatingThead = function () {
			if (isSalesOpportunityList()) {
				fixListScrollContainer();
				return;
			}
			return origRegister.apply(this, arguments);
		};

		proto.reflowList = function () {
			if (isSalesOpportunityList()) {
				fixListScrollContainer();
				return;
			}
			return origReflow.apply(this, arguments);
		};

		proto.__mkOppScrollPatched = true;
		return true;
	}

	function enhanceRows(context) {
		if (!isSalesOpportunityList()) {
			return;
		}
		renderCategoryPills(context || document);
		renderSalesStages(context || document);
	}

	function afterListLayout() {
		if (!isSalesOpportunityList()) {
			return;
		}
		fixListScrollContainer();
		enhanceRows(document);
	}

	function init() {
		if (!isSalesOpportunityList()) {
			return;
		}
		var root = $('#listViewContent');
		if (!root.length) {
			return;
		}

		patchVtigerListScrollHooks();

		$(document).on('click.mkOpportunityList', '.mk-so-trigger-columns, .mk-opportunity-trigger-columns', function (e) {
			e.preventDefault();
			var col = root.find('.listColumnFilter').first();
			if (col.length) {
				col.trigger('click');
			}
		});

		$(document).on('click.mkOpportunityList', '.mk-so-filter-trigger-search, .mk-opportunity-filter-trigger-search', function (e) {
			e.preventDefault();
			root.toggleClass('mk-so-search-open mk-opportunity-search-open');
		});

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(afterListLayout, 200);
			});
			app.event.on('Vtiger.Post.MenuToggle', function () {
				setTimeout(fixListScrollContainer, 80);
			});
		}

		$(document).on('click.mkOpportunityList', '#listViewContent #NextPageButton, #listViewContent #PreviousPageButton, #listViewContent #pageToJumpSubmit', function () {
			setTimeout(function () { enhanceRows(document); }, 200);
		});

		var resizeTimer;
		$(window).on('resize.mkOpportunityList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isSalesOpportunityList()) {
					fixListScrollContainer();
				}
			}, 150);
		});

		setTimeout(afterListLayout, 0);
		setTimeout(function () {
			if (isSalesOpportunityList()) {
				patchVtigerListScrollHooks();
				fixListScrollContainer();
			}
		}, 60);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
