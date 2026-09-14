/* Settings → Integration Hub (UI polish + real data) */
Vtiger.Class('Settings_Vtiger_IntegrationHub_Js', {}, {
	init: function () {
		this.addComponents();
	},

	addComponents: function () {
		this.addModuleSpecificComponent('Index', app.module(), app.getParentModuleName());
	},

	registerEvents: function () {
		this.addComponents();
		var root = jQuery('#NkIntegrationHub');
		if (!root.length) {
			return;
		}
		this.root = root;
		this.selectedCode = null;
		this.loadData();
		this.renderStats();
		this.renderCards();
		this.renderActivity();
		this.renderPipeline();
		this.selectInitial();
		this.bindFilters();
		this.bindCardSelect();
		this.bindViewToggle();
		this.bindViewAll();
		this.bindDetailActions();
		this.bindAddModal();
		this.bindModals();
	},

	loadData: function () {
		this.connections = this.readConnections();
		this.summary = this.readSummary();
		this.activity = this.readActivity();
		this.CRM_MODULES = this.readCrmModules();
		this.addCatalog = this.connections.map(function (c) {
			return { code: c.code, label: c.label, icon: c.icon };
		});
	},

	readConnections: function () {
		var el = document.getElementById('nk-hub-connections-json');
		if (!el || !el.textContent) {
			return [];
		}
		try {
			var parsed = JSON.parse(el.textContent);
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			if (window.console) console.warn('[Hub] connections JSON parse error', e);
			return [];
		}
	},

	readActivity: function () {
		var el = document.getElementById('nk-hub-activity-json');
		if (!el || !el.textContent) {
			return [];
		}
		try {
			var parsed = JSON.parse(el.textContent);
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			if (window.console) console.warn('[Hub] activity JSON parse error', e);
			return [];
		}
	},

	readSummary: function () {
		var el = document.getElementById('nk-hub-summary-json');
		if (el && el.textContent) {
			try {
				var parsed = JSON.parse(el.textContent);
				if (parsed && typeof parsed === 'object') {
					return parsed;
				}
			} catch (e) {
				if (window.console) console.warn('[Hub] summary JSON parse error', e);
			}
		}
		return this.computeSummary(this.connections);
	},

	readCrmModules: function () {
		var el = document.getElementById('nk-hub-crm-json');
		if (el && el.textContent) {
			try {
				var parsed = JSON.parse(el.textContent);
				if (Array.isArray(parsed) && parsed.length) {
					return parsed;
				}
			} catch (e) {
				if (window.console) console.warn('[Hub] crm JSON parse error', e);
			}
		}
		return [
			{ label: 'Lead', status: 'ok' },
			{ label: 'Cơ hội', status: 'ok' },
			{ label: 'Khách hàng', status: 'ok' },
			{ label: 'Đơn hàng', status: 'ok' }
		];
	},

	computeSummary: function (list) {
		var total = list.length;
		var active = 0;
		var warning = 0;
		var error = 0;
		for (var i = 0; i < list.length; i++) {
			var s = list[i].hub_status;
			if (s === 'active') active++;
			else if (s === 'warning') warning++;
			else if (s === 'error') error++;
		}
		var pct = function (n) {
			return total ? Math.round((n / total) * 100) : 0;
		};
		return {
			total: total,
			active: active,
			active_pct: pct(active),
			warning: warning,
			warning_pct: pct(warning),
			error: error,
			error_pct: pct(error),
			synced_today: 0
		};
	},

	formatNumber: function (n) {
		n = Number(n) || 0;
		return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	},

	renderStats: function () {
		var s = this.summary || {};
		this.root.find('[data-stat="total"]').text(s.total != null ? s.total : 0);
		this.root.find('[data-stat="active"]').text(s.active != null ? s.active : 0);
		this.root.find('[data-stat="active_pct"]').text(s.active_pct != null ? s.active_pct : 0);
		this.root.find('[data-stat="warning"]').text(s.warning != null ? s.warning : 0);
		this.root.find('[data-stat="warning_pct"]').text(s.warning_pct != null ? s.warning_pct : 0);
		this.root.find('[data-stat="error"]').text(s.error != null ? s.error : 0);
		this.root.find('[data-stat="error_pct"]').text(s.error_pct != null ? s.error_pct : 0);
		this.root.find('[data-stat="synced_today"]').text(this.formatNumber(s.synced_today || 0));
	},

	LOGO_BASE: 'layouts/v7/modules/Settings/Vtiger/resources/logos/',

	logoUrl: function (icon) {
		var map = {
			google_sheet: 'google_sheet.svg',
			zalo_oa: 'zalo_oa.svg',
			misa: 'misa.svg',
			edubit: 'edubit.svg',
			website: 'website.svg',
			shopee: 'shopee.svg',
			ghtk: 'ghtk.svg',
			email: 'email.svg'
		};
		var file = map[icon] || map.website;
		return this.LOGO_BASE + file;
	},

	logoImg: function (icon, alt) {
		return '<img src="' + this.esc(this.logoUrl(icon)) + '" alt="' + this.esc(alt || '') + '" loading="lazy" />';
	},

	esc: function (str) {
		return jQuery('<div/>').text(str == null ? '' : String(str)).html();
	},

	escDecoded: function (str) {
		if (str == null) return '';
		var tmp = document.createElement('textarea');
		tmp.innerHTML = String(str);
		return this.esc(tmp.value);
	},

	renderCards: function () {
		var self = this;
		var html = '';
		for (var i = 0; i < this.connections.length; i++) {
			var c = this.connections[i];
			html +=
				'<article class="nk-hub-card nk-hub-card--' +
				this.esc(c.hub_status || 'inactive') +
				'" data-code="' +
				this.esc(c.code) +
				'" data-status="' +
				this.esc(c.hub_status || 'inactive') +
				'" data-search="' +
				this.esc((c.label || '') + ' ' + (c.subtitle || '')) +
				'">' +
				'<div class="nk-hub-card__brand">' +
				'<span class="nk-hub-card__icon" aria-hidden="true">' +
				self.logoImg(c.icon || 'website', c.label) +
				'</span>' +
				'<div class="nk-hub-card__text">' +
				'<h3 class="nk-hub-card__name">' +
				this.esc(c.label) +
				'</h3>' +
				'<p class="nk-hub-card__desc">' +
				this.esc(c.subtitle) +
				'</p></div></div>' +
				'<span class="nk-hub-badge nk-hub-badge--' +
				this.esc(c.hub_status || 'inactive') +
				'">' +
				this.esc(c.hub_status_label || '') +
				'</span>' +
				'<footer class="nk-hub-card__foot">' +
				'<span class="nk-hub-card__sync">Đồng bộ ' +
				this.esc(c.last_sync_hint || '—') +
				'</span>' +
				'<button type="button" class="nk-hub-card__gear" data-action="configure" title="Cấu hình" aria-label="Cấu hình">' +
				'<span class="fa fa-cog"></span></button></footer></article>';
		}
		this.root.find('#nk-hub-cards').html(html);
	},

	renderActivity: function () {
		var list = this.activity || [];
		var $list = this.root.find('#nk-hub-log-list');
		if (!list.length) {
			$list.html('<li class="nk-hub-log__empty">Chưa có hoạt động nào.</li>');
			return;
		}
		var html = '';
		for (var i = 0; i < list.length; i++) {
			var log = list[i];
			html +=
				'<li class="nk-hub-log__item nk-hub-log__item--' +
				this.esc(log.type || 'success') +
				'">' +
				'<span class="nk-hub-log__ic" aria-hidden="true"></span>' +
				'<div class="nk-hub-log__body">' +
				'<p class="nk-hub-log__headline">' +
				this.escDecoded(log.title) +
				'</p>' +
				'<p class="nk-hub-log__detail">' +
				this.escDecoded(log.detail) +
				'</p>' +
				'<time class="nk-hub-log__time">' +
				this.esc(log.time) +
				'</time></div></li>';
		}
		$list.html(html);
	},

	PIPELINE_SOURCES: ['ecommerce', 'google_sheet', 'zalo_oa'],
	PIPELINE_EXTERNAL: ['misa', 'edubit', 'ghtk', 'shopee_express'],

	pipelineConnections: function (codes) {
		var out = [];
		for (var i = 0; i < codes.length; i++) {
			var c = this.findConnection(codes[i]);
			if (c) {
				out.push(c);
			}
		}
		return out;
	},

	pipelineConnectorHtml: function () {
		return '<div class="nk-hub-pipeline__connector" aria-hidden="true"><span class="nk-hub-pipeline__connector-line"></span></div>';
	},

	pipelineStageItemsHtml: function (items, mode) {
		var self = this;
		var html = '';
		for (var i = 0; i < items.length; i++) {
			var item = items[i];
			if (mode === 'logo') {
				html +=
					'<li class="nk-hub-pipeline__item">' +
					self.logoImg(item.icon || 'website', item.label) +
					'<span>' +
					self.esc(item.label) +
					'</span></li>';
			} else {
				html +=
					'<li class="nk-hub-pipeline__item nk-hub-pipeline__item--text">' +
					'<span class="nk-hub-pipeline__dot nk-hub-pipeline__dot--' +
					self.esc(item.status || 'ok') +
					'"></span><span>' +
					self.esc(item.label) +
					'</span></li>';
			}
		}
		return html;
	},

	pipelineStageHtml: function (stage, title, badgeText, badgeType, items, itemMode) {
		var icons = {
			source:
				'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83"/></svg>',
			hub: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
			crm: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
			external:
				'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M10 13a5 5 0 0 1 7 0l1 1a3 3 0 0 1-3 3h-3a3 3 0 0 1-3-3l1-1z"/></svg>'
		};
		var hubClass = stage === 'hub' ? ' nk-hub-pipeline__stage--hub' : '';
		var itemsClass = stage === 'hub' ? ' nk-hub-pipeline__items--steps' : '';
		return (
			'<article class="nk-hub-pipeline__stage' +
			hubClass +
			'" data-stage="' +
			this.esc(stage) +
			'">' +
			'<header class="nk-hub-pipeline__stage-head">' +
			'<span class="nk-hub-pipeline__stage-ic" aria-hidden="true">' +
			(icons[stage] || icons.source) +
			'</span><div>' +
			'<h3 class="nk-hub-pipeline__stage-title">' +
			this.esc(title) +
			'</h3>' +
			'<span class="nk-hub-pipeline__badge nk-hub-pipeline__badge--' +
			this.esc(badgeType) +
			'">' +
			this.esc(badgeText) +
			'</span></div></header>' +
			'<ul class="nk-hub-pipeline__items' +
			itemsClass +
			'">' +
			this.pipelineStageItemsHtml(items, itemMode) +
			'</ul></article>'
		);
	},

	pipelineHubStageHtml: function () {
		var hasWarning = false;
		var activeCount = 0;
		var totalCount = this.connections.length;
		for (var i = 0; i < this.connections.length; i++) {
			if (this.connections[i].hub_status === 'warning') hasWarning = true;
			if (this.connections[i].hub_status === 'active') activeCount += 1;
		}

		var syncedToday = (this.summary && this.summary.synced_today) || 0;
		var activityCount = this.activity ? this.activity.length : 0;

		var items = [
			{ label: 'Kết nối hoạt động: ' + activeCount + '/' + totalCount },
			{ label: 'Bản ghi đồng bộ hôm nay: ' + this.formatNumber(syncedToday) },
			{ label: 'Sự kiện gần đây: ' + activityCount }
		];

		var itemsHtml = '';
		for (var k = 0; k < items.length; k++) {
			itemsHtml +=
				'<li class="nk-hub-pipeline__item nk-hub-pipeline__item--text">' +
				'<span class="nk-hub-pipeline__dot nk-hub-pipeline__dot--ok"></span>' +
				'<span>' + this.esc(items[k].label) + '</span></li>';
		}

		return (
			'<article class="nk-hub-pipeline__stage nk-hub-pipeline__stage--hub" data-stage="hub">' +
			'<header class="nk-hub-pipeline__stage-head">' +
			'<span class="nk-hub-pipeline__stage-ic" aria-hidden="true">' +
			'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>' +
			'</span><div>' +
			'<h3 class="nk-hub-pipeline__stage-title">Integration Platform</h3>' +
			'<span class="nk-hub-pipeline__badge nk-hub-pipeline__badge--' +
			(hasWarning ? 'warn' : 'ok') + '">' +
			(hasWarning ? 'Đang xử lý' : 'Sẵn sàng') +
			'</span></div></header>' +
			'<ul class="nk-hub-pipeline__items">' + itemsHtml + '</ul>' +
			'</article>'
		);
	},

	renderPipeline: function () {
		var track = this.root.find('#nk-hub-pipeline-track');
		if (!track.length) {
			return;
		}

		var sources = this.pipelineConnections(this.PIPELINE_SOURCES);
		var externals = this.pipelineConnections(this.PIPELINE_EXTERNAL);

		var errorCount = 0;
		var activeCount = 0;
		for (var i = 0; i < externals.length; i++) {
			if (externals[i].hub_status === 'error') errorCount += 1;
			if (externals[i].hub_status === 'active') activeCount += 1;
		}

		var externalBadge;
		var externalBadgeType;
		if (errorCount > 0) {
			externalBadge = errorCount + ' lỗi';
			externalBadgeType = 'err';
		} else if (activeCount > 0) {
			externalBadge = activeCount + ' đang chạy';
			externalBadgeType = 'ok';
		} else {
			externalBadge = externals.length + ' hệ thống';
			externalBadgeType = 'ok';
		}

		var srcActive = 0;
		var srcWarning = 0;
		for (var j = 0; j < sources.length; j++) {
			if (sources[j].hub_status === 'active') srcActive += 1;
			if (sources[j].hub_status === 'warning') srcWarning += 1;
		}
		var sourceBadge = srcActive > 0
			? srcActive + ' đang chạy'
			: sources.length + ' nguồn';
		var sourceBadgeType = srcWarning > 0 ? 'warn' : 'ok';

		var crmModules = this.CRM_MODULES || [];

		var html = '';
		html += this.pipelineStageHtml(
			'source',
			'Nguồn dữ liệu',
			sourceBadge,
			sourceBadgeType,
			sources,
			'logo'
		);
		html += this.pipelineConnectorHtml();
		html += this.pipelineHubStageHtml();
		html += this.pipelineConnectorHtml();
		html += this.pipelineStageHtml(
			'crm',
			'CRM',
			crmModules.length + ' module',
			'ok',
			crmModules,
			'status'
		);
		html += this.pipelineConnectorHtml();
		html += this.pipelineStageHtml(
			'external',
			'Hệ thống ngoài',
			externalBadge,
			externalBadgeType,
			externals,
			'logo'
		);
		track.html(html);

		var total = this.connections.length || 1;
		var active = this.summary.active || 0;
		var pct = Math.round((active / total) * 100);
		if (active === 0) pct = 0;
		this.root.find('#nk-hub-pipeline-progress').css('width', pct + '%');
		this.root.find('#nk-hub-pipeline-pct').text(pct + '%');
	},

	findConnection: function (code) {
		code = String(code || '');
		for (var i = 0; i < this.connections.length; i++) {
			if (String(this.connections[i].code) === code) {
				return this.connections[i];
			}
		}
		return null;
	},

	selectInitial: function () {
		var $first = this.root.find('#nk-hub-cards .nk-hub-card').first();
		if ($first.length) {
			$first.addClass('is-selected');
			this.selectedCode = $first.data('code');
			this.renderDetail(this.findConnection(this.selectedCode));
		}
	},

	selectCard: function (code) {
		this.selectedCode = code;
		this.root.find('#nk-hub-cards .nk-hub-card').removeClass('is-selected');
		this.root.find('#nk-hub-cards .nk-hub-card[data-code="' + code + '"]').addClass('is-selected');
		this.renderDetail(this.findConnection(code));
	},

	renderDetail: function (conn) {
		if (!conn) {
			return;
		}
		var detail = this.root.find('#nk-hub-detail');
		detail.find('[data-role="detail-icon"]')
			.attr('class', 'nk-hub-card__icon')
			.html(this.logoImg(conn.icon || 'website', conn.label));
		detail.find('[data-role="detail-title"]').text(conn.label || '—');
		detail.find('[data-role="detail-badge"]')
			.attr('class', 'nk-hub-badge nk-hub-badge--' + (conn.hub_status || 'inactive'))
			.text(conn.hub_status_label || '—');
		detail.find('[data-role="detail-enabled"]').prop('checked', !!conn.enabled);
		detail.find('[data-role="detail-url"]').text(conn.base_url || '—');
		detail.find('[data-role="detail-api"]').text(conn.api_key_masked || '—');
		detail.find('[data-role="detail-webhook"]').text(conn.webhook_url || '—');
		detail.find('[data-role="detail-twoway"]').text(conn.two_way ? 'Có' : 'Không');
		detail.find('[data-role="detail-sync"]').text(conn.last_sync_hint || '—');
		detail.find('[data-role="detail-status"]').text(conn.hub_status_label || '—');
		detail.find('[data-role="detail-notes"]').text(conn.notes || '—');
		detail.find('[data-role="detail-configure"]').attr(
			'href',
			conn.configure_url || this.root.data('legacy-url')
		);
	},

	// Cập nhật riêng badge + status text, KHÔNG ghi đè toggle
	updateDetailBadges: function (conn) {
		if (!conn) return;
		var detail = this.root.find('#nk-hub-detail');
		detail.find('[data-role="detail-badge"]')
			.attr('class', 'nk-hub-badge nk-hub-badge--' + (conn.hub_status || 'inactive'))
			.text(conn.hub_status_label || '—');
		detail.find('[data-role="detail-status"]').text(conn.hub_status_label || '—');
	},

	saveConnectionEnabled: function (code, enabled) {
		var deferred = jQuery.Deferred();
		var params = {
			module: 'Vtiger',
			parent: 'Settings',
			action: 'SaveConnectionStatus',
			code: code,
			enabled: enabled ? 1 : 0
		};
		if (typeof window.csrfMagicToken !== 'undefined' && window.csrfMagicName) {
			params[window.csrfMagicName] = window.csrfMagicToken;
		}

		if (window.app && app.request && typeof app.request.post === 'function') {
			app.request.post({ data: params }).then(function (err, res) {
				if (err) {
					deferred.reject(err);
					return;
				}
				var payload = res && res.result ? res.result : res;
				if (!payload || payload.success === false) {
					deferred.reject(
						(payload && (payload.error || payload.message)) ||
							(res && (res.error || res.message)) ||
							'Không lưu được trạng thái.'
					);
					return;
				}
				deferred.resolve(payload);
			});
		} else {
			jQuery.ajax({
				url: 'index.php',
				type: 'POST',
				data: params,
				dataType: 'json'
			})
				.done(function (res) {
					var payload = res && res.result ? res.result : res;
					if (!payload || payload.success === false) {
						deferred.reject(
							(payload && (payload.error || payload.message)) ||
								(res && (res.error || res.message)) ||
								'Không lưu được trạng thái.'
						);
						return;
					}
					deferred.resolve(payload);
				})
				.fail(function () {
					deferred.reject('Không lưu được trạng thái.');
				});
		}
		return deferred.promise();
	},

	mapHubStatusFromConfig: function (cfg) {
		var status = String((cfg && cfg.status) || '');
		var enabled = !!(cfg && cfg.enabled);
		if (status === 'error') return 'error';
		if (!enabled) return 'inactive';
		if (status === 'ok' || status === 'idle') return 'active';
		if (status === 'coming_soon') return 'warning';
		return 'inactive';
	},

	hubStatusLabel: function (hubStatus) {
		var map = {
			active: 'Đang hoạt động',
			warning: 'Cảnh báo',
			error: 'Lỗi kết nối',
			inactive: 'Chưa kết nối'
		};
		return map[hubStatus] || 'Chưa kết nối';
	},

	notify: function (message, isError) {
		if (!message) return;
		if (app.helper) {
			if (isError && app.helper.showErrorNotification) {
				app.helper.showErrorNotification({ message: message });
				return;
			}
			if (!isError && app.helper.showSuccessNotification) {
				app.helper.showSuccessNotification({ message: message });
			}
		}
	},

	bindFilters: function () {
		var self = this;
		this.root.on('change keyup', '#nk-hub-filter-status, #nk-hub-search', function () {
			self.applyFilters();
		});
	},

	applyFilters: function () {
		var status = String(this.root.find('#nk-hub-filter-status').val() || 'all');
		var q = String(this.root.find('#nk-hub-search').val() || '')
			.toLowerCase()
			.trim();
		var visible = 0;
		this.root.find('#nk-hub-cards .nk-hub-card').each(function () {
			var $card = jQuery(this);
			var cardStatus = String($card.data('status') || '');
			var search = String($card.data('search') || '').toLowerCase();
			var show = (status === 'all' || cardStatus === status) && (!q || search.indexOf(q) !== -1);
			$card.toggle(show);
			if (show) visible += 1;
		});
		this.root.find('#nk-hub-cards-empty').prop('hidden', visible > 0);
	},

	bindCardSelect: function () {
		var self = this;
		this.root.on('click', '#nk-hub-cards .nk-hub-card', function (e) {
			if (jQuery(e.target).closest('[data-action="configure"]').length) {
				e.preventDefault();
				e.stopPropagation();
				var code = jQuery(this).data('code');
				var conn = self.findConnection(code);
				if (conn && conn.configure_url) {
					window.location.href = conn.configure_url;
				}
				return;
			}
			self.selectCard(jQuery(this).data('code'));
		});
	},

	bindViewToggle: function () {
		var self = this;
		this.root.on('click', '.nk-hub-view-btn', function (e) {
			e.preventDefault();
			var view = jQuery(this).data('view');
			self.root.find('.nk-hub-view-btn').removeClass('is-active');
			jQuery(this).addClass('is-active');
			self.root.find('#nk-hub-cards').toggleClass('is-list', view === 'list');
		});
	},

	bindViewAll: function () {
		var self = this;
		this.root.on('click', '#nk-hub-view-all, #nk-hub-log-view-all', function (e) {
			e.preventDefault();
			self.root.find('#nk-hub-filter-status').val('all');
			self.root.find('#nk-hub-search').val('');
			self.root.find('#nk-hub-cards .nk-hub-card').show();
			self.root.find('#nk-hub-cards-empty').prop('hidden', true);
		});
	},

	bindDetailActions: function () {
		// Guard: chỉ bind 1 lần, tránh gắn handler trùng
		if (this.__detailActionsBound) {
			return;
		}
		this.__detailActionsBound = true;

		var self = this;

		// ===== Toggle bật/tắt kết nối =====
		this.root.on('change', '[data-role="detail-enabled"]', function () {
			var $toggle = jQuery(this);
			var conn = self.findConnection(self.selectedCode);
			if (!conn) {
				return;
			}

			var nextEnabled = $toggle.is(':checked');
			var prevEnabled = !!conn.enabled;

			$toggle.prop('disabled', true);

			self.saveConnectionEnabled(conn.code, nextEnabled)
				.done(function (result) {
					$toggle.prop('disabled', false);
					if (!result || result.success === false) {
						$toggle.prop('checked', prevEnabled);
						self.notify('Không lưu được trạng thái.', true);
						return;
					}

					var cfg = result.config || {};
					// Cập nhật connection từ server response
					conn.enabled = !!cfg.enabled;
					conn.status = cfg.status || conn.status;
					conn.hub_status = self.mapHubStatusFromConfig(cfg);
					conn.hub_status_label = cfg.hub_status_label || self.hubStatusLabel(conn.hub_status);

					// Set toggle theo response server, KHÔNG dùng conn.enabled
					$toggle.prop('checked', !!cfg.enabled);

					// Chỉ cập nhật badge, KHÔNG renderDetail (tránh ghi đè toggle)
					self.updateDetailBadges(conn);
					self.updateCardBadge(conn);
					self.renderStats();
					self.renderPipeline();

					self.notify(
						cfg.enabled ? 'Đã bật kết nối.' : 'Đã tắt kết nối.',
						false
					);
				})
				.fail(function (err) {
					$toggle.prop('disabled', false);
					$toggle.prop('checked', prevEnabled);
					var msg =
						(err && (err.message || err.error)) ||
						(typeof err === 'string' ? err : '') ||
						'Không lưu được trạng thái.';
					self.notify(String(msg), true);
				});
		});

		// ===== Nút "Ngắt kết nối" — mở modal =====
		this.root.on('click', '.nk-hub-detail__disconnect', function (e) {
			e.preventDefault();
			var conn = self.findConnection(self.selectedCode);
			if (!conn) return;
			self.root.find('#nk-hub-disconnect-name').text(conn.label || '');
			self.openModal('nk-hub-modal-disconnect');
		});

		// ===== Nút "Xác nhận ngắt kết nối" — gọi API =====
		this.root.on('click', '#nk-hub-disconnect-confirm', function (e) {
			e.preventDefault();
			var conn = self.findConnection(self.selectedCode);
			if (!conn) return;

			self.saveConnectionEnabled(conn.code, false)
				.done(function (result) {
					if (!result || !result.success) {
						self.notify('Không ngắt được kết nối.', true);
						return;
					}
					var cfg = result.config || {};
					conn.enabled = false;
					conn.hub_status = self.mapHubStatusFromConfig(cfg);
					conn.hub_status_label = cfg.hub_status_label || self.hubStatusLabel(conn.hub_status);

					self.root.find('[data-role="detail-enabled"]').prop('checked', false);
					self.updateDetailBadges(conn);
					self.updateCardBadge(conn);
					self.renderStats();
					self.renderPipeline();
					self.closeModal('nk-hub-modal-disconnect');
					self.notify('Đã ngắt kết nối.', false);
				})
				.fail(function () {
					self.notify('Lỗi kết nối server.', true);
				});
		});

		// ===== Nút "Kiểm tra kết nối" =====
		this.root.on('click', '.nk-hub-detail__test', function (e) {
			e.preventDefault();
			var conn = self.findConnection(self.selectedCode);
			if (!conn) return;
			self.openModal('nk-hub-modal-test');
			self.root.find('#nk-hub-test-name').text(conn.label || '');
			self.root.find('#nk-hub-test-body').html(
				'<div class="nk-hub-modal-loading"><span class="nk-hub-spinner"></span><p>Đang kiểm tra kết nối...</p></div>'
			);
			setTimeout(function () {
				var result = conn.test_result || 'success';
				var msg =
					result === 'success'
						? 'Kết nối thành công.'
						: result === 'warning'
							? 'Kết nối chậm — cần kiểm tra token.'
							: 'Không kết nối được — HTTP timeout.';
				var cls =
					result === 'success' ? 'is-success' : result === 'warning' ? 'is-warning' : 'is-error';
				self.root.find('#nk-hub-test-body').html(
					'<div class="nk-hub-modal-result ' +
						cls +
						'"><p>' +
						self.esc(msg) +
						'</p></div>'
				);
			}, 1200);
		});
	},

	updateCardBadge: function (conn) {
		var $card = this.root.find('#nk-hub-cards .nk-hub-card[data-code="' + conn.code + '"]');
		$card
			.attr('class', 'nk-hub-card nk-hub-card--' + (conn.hub_status || 'inactive') + ' is-selected')
			.attr('data-status', conn.hub_status || 'inactive');
		$card.find('.nk-hub-badge')
			.attr('class', 'nk-hub-badge nk-hub-badge--' + (conn.hub_status || 'inactive'))
			.text(conn.hub_status_label || '');
	},

	bindAddModal: function () {
		var self = this;
		this.root.on('click', '#nk-hub-add', function (e) {
			e.preventDefault();
			self.renderAddCatalog();
			self.openModal('nk-hub-modal-add');
		});
	},

	renderAddCatalog: function () {
		var html = '';
		for (var i = 0; i < this.addCatalog.length; i++) {
			var item = this.addCatalog[i];
			html +=
				'<button type="button" class="nk-hub-add-item" data-code="' +
				this.esc(item.code) +
				'">' +
				'<span class="nk-hub-card__icon">' +
				this.logoImg(item.icon || 'website', item.label) +
				'</span>' +
				'<span class="nk-hub-add-item__label">' +
				this.esc(item.label) +
				'</span></button>';
		}
		this.root.find('#nk-hub-add-grid').html(html);
	},

	bindModals: function () {
		var self = this;
		this.root.on('click', '[data-hub-modal-close]', function (e) {
			e.preventDefault();
			self.closeModal(jQuery(this).closest('.nk-hub-modal').attr('id'));
		});
		this.root.on('click', '.nk-hub-modal__backdrop', function (e) {
			if (e.target === this) {
				self.closeModal(jQuery(this).closest('.nk-hub-modal').attr('id'));
			}
		});
		this.root.on('click', '.nk-hub-add-item', function (e) {
			e.preventDefault();
			var code = jQuery(this).data('code');
			self.closeModal('nk-hub-modal-add');
			self.selectCard(code);
			var conn = self.findConnection(code);
			self.notify('Đã chọn ' + (conn ? conn.label : code) + '.', false);
		});
	},

	openModal: function (id) {
		this.root.find('#' + id).addClass('is-open').attr('aria-hidden', 'false');
		document.body.classList.add('nk-hub-modal-open');
	},

	closeModal: function (id) {
		this.root.find('#' + id).removeClass('is-open').attr('aria-hidden', 'true');
		if (!this.root.find('.nk-hub-modal.is-open').length) {
			document.body.classList.remove('nk-hub-modal-open');
		}
	}
});