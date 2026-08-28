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

	function rememberTabSelection($li) {
		if (!$li || !$li.length) {
			return;
		}
		$(document).data('mkProjLastTab', $li);
		$(document).data('mkProjLastRelationId', ($li.attr('data-relation-id') || '').toString());
		$(document).data('mkProjLastModule', ($li.attr('data-module') || '').toString());
	}

	function cleanupMoreTabClone($li) {
		if (!$li || !$li.length) {
			return;
		}
		if ($li.closest('#relatedmenuList').length) {
			return;
		}
		if ($li.hasClass('more-tab') && !$li.hasClass('moreTabElement')) {
			return;
		}
		if (!$li.hasClass('moreTabElement')) {
			$('.detailview-content .related-tabs .moreTabElement').remove();
		}
	}

	function applyActiveTabFromMemory() {
		var $root = getRelatedTabsRoot();
		if (!$root.length) {
			return;
		}
		var relId = ($(document).data('mkProjLastRelationId') || '').toString();
		var mod = ($(document).data('mkProjLastModule') || '').toString();
		var $match = $();

		if (relId) {
			$match = getTabItems($root).filter('[data-relation-id="' + relId + '"]');
		}
		if (!$match.length && mod) {
			$match = getTabItems($root).filter('[data-module="' + mod + '"]');
		}
		if ($match.length) {
			var $visible = $match.filter(function () {
				var $item = $(this);
				return $item.closest('#relatedmenuList').length === 0;
			});
			setSingleActiveTab(($visible.length ? $visible : $match).first());
			return;
		}
		var $last = $(document).data('mkProjLastTab');
		if ($last && $last.length) {
			setSingleActiveTab($last);
		}
	}

	function syncActiveTabFromLoadedContent() {
		var relatedModule = ($('.relatedContainer input.relatedModuleName').val() || '').toString();
		if (!relatedModule) {
			return;
		}
		var $root = getRelatedTabsRoot();
		var $tab = getTabItems($root).filter('[data-module="' + relatedModule + '"]').filter(function () {
			return $(this).closest('#relatedmenuList').length === 0 && !$(this).hasClass('moreTabElement');
		});
		if ($tab.length) {
			rememberTabSelection($tab.first());
			setSingleActiveTab($tab.first());
		}
	}

	function enhanceCommentsComposer() {
		var $scopes = $(
			'.widgetContainer_comments .addCommentBlock, .relatedContainer .addCommentBlock, .commentsRelatedContainer .addCommentBlock'
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
		$('.mk-proj-comments-panel, .commentsRelatedContainer').addClass('mk-proj-comments-panel--ready');
		upgradeLegacyCommentAvatars();
		enhanceCommentAvatars();
	}

	function upgradeLegacyCommentAvatars() {
		var $scope = $('.mk-proj-comments-panel, .commentsRelatedContainer, .widgetContainer_comments');
		$scope.find('.commentDiv').each(function () {
			var $comment = $(this);
			if ($comment.hasClass('mk-proj-comment-item') && $comment.find('.mk-proj-comment-avatar').length) {
				return;
			}
			$comment.addClass('mk-proj-comment-item');

			var $single = $comment.find('.singleComment').first();
			if (!$single.length) {
				return;
			}

			var $legacyImage = $single.find('.recordImage.commentInfoHeader').first();
			var name = ($single.find('.creatorName').first().text() || '').trim();
			var initials = name.substring(0, 2).toUpperCase() || '?';
			var imgSrc = $legacyImage.find('img').attr('src');

			if (!$single.find('.mk-proj-comment-avatar').length) {
				var $avatar = $('<div class="mk-proj-comment-avatar" aria-hidden="true"></div>');
				if (imgSrc) {
					$avatar.append(
						$('<img class="mk-proj-comment-avatar__photo" alt="">')
							.attr('src', imgSrc)
							.attr('alt', name)
					);
				} else {
					var legacyInitials = ($legacyImage.find('.name').text() || '').trim() || initials;
					$avatar.append($('<span class="mk-proj-comment-avatar__initials"></span>').text(legacyInitials));
				}

				var $header = $single.find('.commentInfoHeader[data-commentid]').first();
				var $body = $single.find('.media-body').first();
				if ($header.length && $body.length) {
					var $row = $('<div class="mk-proj-comment-item__row"></div>');
					['data-commentid', 'data-parentcommentid', 'data-relatedto'].forEach(function (attr) {
						var val = $header.attr(attr);
						if (val !== undefined) {
							$row.attr(attr, val);
						}
					});
					$row.append($avatar);
					var $content = $('<div class="mk-proj-comment-item__body"></div>').append($body.contents());
					$row.append($content);
					$header.replaceWith($row);
					$legacyImage.remove();
				} else if ($single.find('.mk-proj-comment-item__row').length) {
					$single.find('.mk-proj-comment-item__row').prepend($avatar);
				} else {
					$single.prepend($avatar);
				}
			}
		});
	}

	function enhanceCommentAvatars() {
		$('.mk-proj-comment-avatar__photo').each(function () {
			var img = this;
			var $img = $(img);
			var $avatar = $img.closest('.mk-proj-comment-avatar');
			var fallback = ($avatar.data('initials') || '').toString();
			if (!fallback) {
				var $row = $avatar.closest('.mk-proj-comment-item__row, .mk-proj-comment-item');
				var name = ($row.find('.creatorName').first().text() || '').trim();
				fallback = name.substring(0, 2).toUpperCase();
				$avatar.attr('data-initials', fallback);
			}
			var applyFallback = function () {
				$img.remove();
				if (!$avatar.find('.mk-proj-comment-avatar__initials').length) {
					$avatar.append(
						$('<span class="mk-proj-comment-avatar__initials"></span>').text(fallback || '?')
					);
				}
			};
			img.onerror = applyFallback;
			if (img.complete && img.naturalWidth === 0) {
				applyFallback();
			}
		});
	}

	function measureGanttGridWidth($workspace) {
		var total = 0;
		var $headers = $workspace.find('.gdfTable.fixHead .gdfColHeader');
		if ($headers.length) {
			$headers.each(function () {
				var w = $(this).outerWidth() || parseInt(this.style.width, 10) || 0;
				total += w;
			});
		}
		if (total > 0) {
			return total + 4;
		}
		var $splitBox1 = $workspace.find('.splitBox1').first();
		if ($splitBox1.length) {
			var $inner = $splitBox1.find('.gdfTable').first();
			if ($inner.length) {
				total = $inner.get(0).scrollWidth || $inner.outerWidth() || 0;
			}
			if (!total) {
				total = $splitBox1.get(0).scrollWidth || 0;
			}
		}
		return total > 0 ? total : 520;
	}

	function syncGanttSplitter($workspace) {
		var $splitBox1 = $workspace.find('.splitBox1').first();
		var $splitBar = $workspace.find('.vSplitBar').first();
		var $splitBox2 = $workspace.find('.splitBox2').first();
		var totalW = $workspace.innerWidth() || $workspace.width() || 0;
		if (!totalW || !$splitBox1.length || !$splitBar.length || !$splitBox2.length) {
			return;
		}

		var barW = $splitBar.outerWidth() || 5;
		var contentW = Math.ceil(measureGanttGridWidth($workspace));
		var minTimelineW = 300;
		var maxLeftW = Math.max(280, totalW - barW - minTimelineW);
		var leftW = Math.min(contentW, maxLeftW);

		$splitBox1.width(leftW).css({
			overflowX: contentW > leftW ? 'scroll' : 'auto',
			overflowY: 'hidden'
		});
		$splitBar.css('left', leftW);
		$splitBox2.css({
			left: leftW + barW,
			width: Math.max(0, totalW - leftW - barW),
			overflowX: 'scroll',
			overflowY: 'auto',
			WebkitOverflowScrolling: 'touch'
		});

		if (window.Project_Detail_Js && Project_Detail_Js.gantt && Project_Detail_Js.gantt.splitter) {
			Project_Detail_Js.gantt.splitter.perc = (leftW / totalW) * 100;
		}
	}

	function scheduleGanttSplitterSync($workspace) {
		syncGanttSplitter($workspace);
		window.setTimeout(function () {
			syncGanttSplitter($workspace);
		}, 30);
		window.setTimeout(function () {
			syncGanttSplitter($workspace);
		}, 200);
	}

	function enhanceGanttChart() {
		var $workspace = $('#workSpace');
		if (!$workspace.length) {
			return;
		}
		var $panel = $workspace.closest('.mk-proj-chart-panel');
		if ($panel.length) {
			$panel.addClass('mk-proj-chart-ready');
		}
		var $wrap = $workspace.closest('.mk-proj-chart-workspace-wrap');
		var chartHeight = Math.max(380, Math.min(540, Math.round($(window).height() * 0.44)));
		if ($wrap.length) {
			$wrap.css('min-height', chartHeight + 'px');
		}
		$workspace.css({
			height: chartHeight + 'px',
			width: '100%'
		});
		$workspace.find('.ganttButtonBar').hide();
		scheduleGanttSplitterSync($workspace);
		$workspace.trigger('resize.gantt');
		scheduleGanttSplitterSync($workspace);

		if (!$workspace.data('mkGanttSplitBound')) {
			$workspace.data('mkGanttSplitBound', 1);
			$(window).on('resize.mkProjGantt', function () {
				if ($('#workSpace').length) {
					scheduleGanttSplitterSync($('#workSpace'));
				}
			});
		}
	}

	function filterCommentsList(query) {
		var q = String(query || '').toLowerCase().trim();
		$('.mk-proj-comments-panel .commentDetails, .commentsRelatedContainer .commentDetails').each(function () {
			var text = ($(this).text() || '').toLowerCase();
			$(this).toggle(!q || text.indexOf(q) !== -1);
		});
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-project-detail-related-tabs li[data-module] > a .numberCircle, ' +
				'.detailview-content .related-tabs li[data-module] > a .numberCircle'
		);
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			var raw = (el.textContent || '').trim();
			var count = parseInt(raw, 10);
			if (isNaN(count)) {
				count = 0;
			}
			el.setAttribute('data-count', String(count));
			if (count <= 0) {
				el.classList.add('hide');
				el.textContent = '0';
			} else {
				el.classList.remove('hide');
				el.textContent = String(count);
			}
		}
	}

	function enhanceRelatedIconTabs() {
		var $root = getRelatedTabsRoot();
		if (!$root.length) {
			return;
		}
		$root.addClass('mk-proj-related-icon-tabs');
		$root.find('li.tab-item[data-module], li.more-tab[data-module], li.dropdown.related-tab-more-element')
			.addClass('mk-proj-related-icon-tab');
	}

	function taskStatusKey(text) {
		var t = String(text || '').toLowerCase().trim();
		if (!t) {
			return 'default';
		}
		if (t.indexOf('chưa thực hiện') >= 0 || t.indexOf('not started') >= 0 || t.indexOf('open') >= 0) {
			return 'not-started';
		}
		if (t.indexOf('đang tiến hành') >= 0 || t.indexOf('đang thực hiện') >= 0 || t.indexOf('in progress') >= 0) {
			return 'in-progress';
		}
		if (t.indexOf('hoàn thành') >= 0 || t.indexOf('completed') >= 0) {
			return 'completed';
		}
		if (t.indexOf('tạm dừng') >= 0 || t.indexOf('on hold') >= 0) {
			return 'on-hold';
		}
		return 'default';
	}

	function parseTaskProgress(text) {
		var m = String(text || '').match(/(\d+(?:\.\d+)?)/);
		if (!m) {
			return null;
		}
		var n = parseFloat(m[1]);
		return isNaN(n) ? null : Math.max(0, Math.min(100, n));
	}

	function enhanceTasksWidget() {
		var $widget = $('.widgetContainer_tasks').closest('.summaryWidgetContainer');
		if (!$widget.length) {
			return;
		}
		$widget.addClass('mk-proj-tasks-widget');

		var totalRaw = $widget.find('[name="totalRelatedEntries"]').val();
		var totalCount = totalRaw !== undefined && totalRaw !== '' ? parseInt(totalRaw, 10) : NaN;
		if (!isNaN(totalCount)) {
			$widget.find('.mk-proj-tasks-more-footer').toggle(totalCount > 5);
		}

		$widget.find('.recentActivitiesContainer').each(function () {
			var $card = $(this);
			$card.addClass('mk-proj-task-card');

			var $progressDrop = $card.find('[data-fieldname="projecttaskprogress"]');
			var $statusDrop = $card.find('[data-fieldname="projecttaskstatus"]');
			var progressText = ($progressDrop.find('.fieldValue').first().text() || $card.find('.mk-proj-task-meta--progress .mk-proj-task-pill').first().text() || '').trim();
			var statusText = ($statusDrop.find('.fieldValue').first().text() || $card.find('.mk-proj-task-meta--status .mk-proj-task-pill').first().text() || '').trim();
			var pct = parseTaskProgress(progressText);
			var statusKey = taskStatusKey(statusText);

			$statusDrop.find('.dropdown-toggle, .mk-proj-task-pill').first()
				.addClass('mk-proj-task-pill mk-proj-task-pill--status mk-proj-task-pill--' + statusKey);
			$progressDrop.find('.dropdown-toggle').addClass('mk-proj-task-pill mk-proj-task-pill--progress');
			$progressDrop.find('.fieldValue').first().addClass('mk-proj-task-pill__value');

			if (pct !== null) {
				var $bar = $card.find('.mk-proj-task-progress-bar');
				if (!$bar.length) {
					$bar = $('<div class="mk-proj-task-progress-bar" aria-hidden="true"><span></span></div>');
					$card.find('.mk-proj-task-card__name, .textOverflowEllipsis').first().after($bar);
				}
				$bar.find('span').css('width', pct + '%');
			}
		});
	}

	function enhanceUpdatesTimeline() {
		var $panel = $('#updates.mk-proj-updates-panel, .relatedContainer .mk-proj-updates-panel').first();
		if (!$panel.length) {
			$panel = $('.relatedContainer .recentActivitiesContainer').first();
		}
		if (!$panel.length) {
			return;
		}
		$panel.addClass('mk-proj-updates-panel');
		$panel.find('.updates_timeline').addClass('mk-proj-updates-timeline');
		$panel.find('.updates_timeline > li').not('#more_button').addClass('mk-proj-update-item');

		var totalRaw = $panel.find('[name="totalUpdatesCount"]').val();
		var totalCount = totalRaw !== undefined && totalRaw !== '' ? parseInt(totalRaw, 10) : NaN;
		if (!isNaN(totalCount) && totalCount <= 5) {
			$panel.find('#more_button, .mk-proj-updates-more-item').remove();
		}

		$panel.find('.moreRecentUpdates').addClass('mk-proj-updates-more-btn');
	}

	function enhanceLuxuryMotion() {
		var $cards = $(
			'.mk-project-detail-summary-layout > .mk-project-detail-col > .summaryView, ' +
				'.mk-project-detail-summary-layout > .mk-project-detail-col > .summaryWidgetContainer'
		);
		$cards.each(function (i) {
			var el = this;
			el.classList.add('mk-proj-lux-card');
			el.style.animationDelay = (0.06 + i * 0.05) + 's';
		});
		var $hero = $('.mk-project-detail-hero-strip');
		if ($hero.length) {
			$hero.addClass('mk-proj-lux-hero');
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
		enhanceRelatedIconTabs();
		enhanceLuxuryMotion();
		enhanceTasksWidget();
		enhanceUpdatesTimeline();
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
		var relatedModule = params.get('relatedModule');
		if (relatedModule) {
			var $mod = getTabItems($root).filter('[data-module="' + relatedModule + '"]').filter(function () {
				return $(this).closest('#relatedmenuList').length === 0 && !$(this).hasClass('moreTabElement');
			});
			if ($mod.length) {
				setSingleActiveTab($mod.first());
				return;
			}
		}
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

		$(document).on(
			'click.mkProjTab',
			'.detailview-content .related-tabs li.tab-item[data-module] > a, .detailview-content .related-tabs li.more-tab[data-module] > a, .detailview-content .related-tabs li.moreTabElement[data-module] > a',
			function () {
				var $li = $(this).closest('li');
				cleanupMoreTabClone($li);
				rememberTabSelection($li);
				setSingleActiveTab($li);
			}
		);

		$(document).on(
			'click.mkProjTabLi',
			'.detailview-content .related-tabs li.tab-item[data-module], .detailview-content .related-tabs li.more-tab[data-module], .detailview-content .related-tabs li.moreTabElement[data-module]',
			function () {
				cleanupMoreTabClone($(this));
				rememberTabSelection($(this));
			}
		);

		$(document).on('input.mkProjCommentFilter', '.mk-proj-comments-filter__input', function () {
			filterCommentsList($(this).val());
		});

		app.event.on('post.relatedListLoad.click', function () {
			if (!isManagementProjectDetail()) {
				return;
			}
			applyActiveTabFromMemory();
			syncActiveTabFromLoadedContent();
			refreshRelatedBadges();
			enhanceRelatedIconTabs();
			enhanceCommentsComposer();
			enhanceGanttChart();
			enhanceLuxuryMotion();
			enhanceTasksWidget();
			enhanceUpdatesTimeline();
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
			if (url.indexOf('showChart') !== -1 || url.indexOf('mode=showChart') !== -1) {
				window.setTimeout(enhanceGanttChart, 0);
				window.setTimeout(enhanceGanttChart, 400);
				window.setTimeout(enhanceGanttChart, 1000);
			}
			if (url.indexOf('ProjectTask') !== -1 || url.indexOf('showRelatedRecords') !== -1 || url.indexOf('widget') !== -1) {
				window.setTimeout(enhanceTasksWidget, 0);
			}
			if (url.indexOf('showRecentActivities') !== -1) {
				window.setTimeout(enhanceUpdatesTimeline, 0);
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
		enhanceRelatedIconTabs();
		window.setTimeout(enhanceCommentsComposer, 400);
		window.setTimeout(enhanceCommentsComposer, 1200);
		window.setTimeout(enhanceGanttChart, 600);
		window.setTimeout(enhanceGanttChart, 1500);
		window.setTimeout(enhanceTasksWidget, 400);
		window.setTimeout(enhanceTasksWidget, 1200);
		window.setTimeout(enhanceUpdatesTimeline, 400);
		window.setTimeout(enhanceUpdatesTimeline, 1200);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(jQuery);
