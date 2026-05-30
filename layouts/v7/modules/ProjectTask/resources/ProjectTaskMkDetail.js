/**
 * ProjectTask Detail (MANAGEMENT): DOM hooks + single active tab (no observers / no reload loops).
 */
(function ($) {
	'use strict';

	var enhanced = false;

	function isManagementProjectTaskDetail() {
		var b = document.body;
		return !!(
			b &&
			b.getAttribute('data-module') === 'ProjectTask' &&
			b.getAttribute('data-view') === 'Detail' &&
			b.getAttribute('data-app') === 'MANAGEMENT'
		);
	}

	function getRelatedTabsRoot() {
		var $root = $('.mk-projecttask-detail-inner .related-tabs');
		return $root.length ? $root : $('.detailview-content .related-tabs').first();
	}

	function getTabItems($root) {
		return $root.find('li.tab-item, li.more-tab, li.moreTabElement');
	}

	function setSingleActiveTab($li) {
		var $root = getRelatedTabsRoot();
		if (!$root.length) {
			return;
		}
		getTabItems($root).removeClass('active');
		if ($li && $li.length) {
			$li.addClass('active');
		}
	}

	function enhanceCommentsComposer() {
		var $scopes = $(
			'.widgetContainer_comments .addCommentBlock, .relatedContainer .addCommentBlock'
		);
		$scopes.each(function () {
			var $block = $(this);
			if (!$block.hasClass('mk-projecttask-comment-composer')) {
				$block.addClass('mk-projecttask-comment-composer');
			}
			$block.find('.fileUploadBtn .fa-laptop')
				.removeClass('fa-laptop')
				.addClass('fa-paperclip');
		});
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-projecttask-detail-related-tabs li[data-module] > a .numberCircle, ' +
				'.detailview-content .related-tabs li[data-module] > a .numberCircle'
		);
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			el.classList.remove('hide');
			var raw = (el.textContent || '').trim();
			var count = parseInt(raw, 10);
			if (isNaN(count)) {
				count = 0;
			}
			el.setAttribute('data-count', String(count));
			if (raw === '') {
				el.textContent = '0';
			}
		}
	}

	function enhanceDomOnce() {
		if (enhanced) {
			return;
		}
		var $root = $('.mk-projecttask-detail-inner');
		if (!$root.length) {
			return;
		}
		enhanced = true;

		$root.find('.related-tabs').addClass('mk-projecttask-detail-related-tabs');
		$root.find('.summaryWidgetContainer').addClass('mk-projecttask-detail-widget');

		var $star = $('#starToggle');
		if ($star.length && !$star.hasClass('mk-projecttask-detail-btn')) {
			$star.addClass('mk-projecttask-detail-btn mk-projecttask-detail-btn--outline');
		}

		refreshRelatedBadges();
	}

	function syncActiveTabFromUrl() {
		var params = new URLSearchParams(window.location.search);
		var mode = params.get('mode');
		var $root = getRelatedTabsRoot();
		if (!$root.length) {
			return;
		}

		if (mode) {
			var $match = getTabItems($root).filter(function () {
				var url = $(this).attr('data-url') || $(this).find('a').attr('href') || '';
				return url.indexOf(mode) !== -1;
			});
			if ($match.length) {
				setSingleActiveTab($match.first());
				return;
			}
		}

		var relationId = params.get('relationId');
		if (relationId) {
			var $rel = getTabItems($root).filter('[data-relation-id="' + relationId + '"]');
			if ($rel.length) {
				setSingleActiveTab($rel.first());
			}
		}
	}

	var tabFixBound = false;

	function bindTabActiveFix() {
		if (tabFixBound) {
			return;
		}
		tabFixBound = true;

		/* Remember clicked tab; apply active state after Vtiger finishes AJAX load */
		$(document).on(
			'click.mkPtTaskTab',
			'.detailview-content .related-tabs li.tab-item, .detailview-content .related-tabs li.more-tab, .detailview-content .related-tabs li.moreTabElement',
			function () {
				$(document).data('mkPtTaskLastTab', $(this));
			}
		);

		app.event.on('post.relatedListLoad.click', function () {
			if (!isManagementProjectTaskDetail()) {
				return;
			}
			var $last = $(document).data('mkPtTaskLastTab');
			if ($last && $last.length) {
				setSingleActiveTab($last);
			}
			refreshRelatedBadges();
			enhanceCommentsComposer();
		});
	}

	var commentsEnhanceBound = false;

	function bindCommentsEnhance() {
		if (commentsEnhanceBound) {
			return;
		}
		commentsEnhanceBound = true;
		$(document).ajaxSuccess(function (_e, _xhr, settings) {
			if (!isManagementProjectTaskDetail()) {
				return;
			}
			var url = (settings && settings.url) || '';
			if (url.indexOf('showRecentComments') !== -1) {
				window.setTimeout(enhanceCommentsComposer, 0);
			}
		});
	}

	function parseProgress(raw) {
		var text = $.trim(String(raw || ''));
		var pct = parseInt(text.replace(/[^\d]/g, ''), 10);
		if (isNaN(pct)) {
			pct = 0;
		}
		var tone = 'default';
		if (text.toUpperCase().indexOf('DONE') >= 0 || pct >= 100) {
			tone = 'done';
		}
		return { pct: Math.min(100, Math.max(0, pct)), tone: tone };
	}

	function enhanceProgressBars() {
		$(
			'td[data-name="projecttaskprogress"] .value, ' +
				'.taskDetailFormOuter .progressValue .value, ' +
				'.detailview-table td[data-fieldname="projecttaskprogress"] .value'
		).each(function () {
			var $cell = $(this);
			if (!$cell.length || $cell.find('.mk-projecttask-progress').length) {
				return;
			}
			var info = parseProgress($cell.text());
			var label = info.pct > 0 ? info.pct + '%' : '';
			$cell.html(
				'<div class="mk-projecttask-progress mk-projecttask-progress--' +
					info.tone +
					'">' +
					'<div class="mk-projecttask-progress__fill" style="width:' +
					info.pct +
					'%"></div>' +
					(label
						? '<span class="mk-projecttask-progress__label">' +
							$('<span/>').text(label).html() +
							'</span>'
						: '') +
					'</div>'
			);
		});
	}

	function boot() {
		if (!isManagementProjectTaskDetail()) {
			return;
		}
		document.body.classList.add('mk-projecttask-detail-modern');
		enhanceDomOnce();
		enhanceProgressBars();
		syncActiveTabFromUrl();
		bindTabActiveFix();
		bindCommentsEnhance();
		window.setTimeout(enhanceCommentsComposer, 400);
		window.setTimeout(enhanceCommentsComposer, 1200);
		window.setTimeout(enhanceProgressBars, 500);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(jQuery);
