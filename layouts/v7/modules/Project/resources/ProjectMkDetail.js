/**
 * Project Detail (MANAGEMENT): DOM hooks + single active tab (no observers / no reload loops).
 */
(function ($) {
	'use strict';

	var enhanced = false;

	function isManagementProjectDetail() {
		var b = document.body;
		return !!(
			b &&
			b.getAttribute('data-module') === 'Project' &&
			b.getAttribute('data-view') === 'Detail' &&
			b.getAttribute('data-app') === 'MANAGEMENT'
		);
	}

	function getRelatedTabsRoot() {
		var $root = $('.mk-project-detail-inner .related-tabs');
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
			if (!$block.hasClass('mk-project-comment-composer')) {
				$block.addClass('mk-project-comment-composer');
			}
			$block.find('.fileUploadBtn .fa-laptop')
				.removeClass('fa-laptop')
				.addClass('fa-paperclip');
		});
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-project-detail-related-tabs li[data-module] > a .numberCircle, ' +
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
		var $root = $('.mk-project-detail-inner');
		if (!$root.length) {
			return;
		}
		enhanced = true;

		$root.find('.related-tabs').addClass('mk-project-detail-related-tabs');
		$root.find('.summaryWidgetContainer').addClass('mk-project-detail-widget');

		var $star = $('#starToggle');
		if ($star.length && !$star.hasClass('mk-project-detail-btn')) {
			$star.addClass('mk-project-detail-btn mk-project-detail-btn--outline');
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
			'click.mkProjTab',
			'.detailview-content .related-tabs li.tab-item, .detailview-content .related-tabs li.more-tab, .detailview-content .related-tabs li.moreTabElement',
			function () {
				$(document).data('mkProjLastTab', $(this));
			}
		);

		app.event.on('post.relatedListLoad.click', function () {
			if (!isManagementProjectDetail()) {
				return;
			}
			var $last = $(document).data('mkProjLastTab');
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
			if (!isManagementProjectDetail()) {
				return;
			}
			var url = (settings && settings.url) || '';
			if (url.indexOf('showRecentComments') !== -1) {
				window.setTimeout(enhanceCommentsComposer, 0);
			}
		});
	}

	function boot() {
		if (!isManagementProjectDetail()) {
			return;
		}
		document.body.classList.add('mk-project-detail-modern');
		enhanceDomOnce();
		syncActiveTabFromUrl();
		bindTabActiveFix();
		bindCommentsEnhance();
		window.setTimeout(enhanceCommentsComposer, 400);
		window.setTimeout(enhanceCommentsComposer, 1200);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(jQuery);
