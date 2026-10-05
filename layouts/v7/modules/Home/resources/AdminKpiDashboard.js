(function ($) {
	'use strict';

	var ROOT_SEL = '#mkAdminKpiRoot';
	var state = {
		section: 'customers',
		revenueMode: 'total',
		period: 'month',
		saleId: 0,
		fullSales: false,
		chartGroup: 'month',
		chartDimension: 'none',
		chartYear: new Date().getFullYear(),
		openDrillSig: '',
		stagePeriod: 'month',
		stageOffset: 0,
		stagePanel: 'offline',
		widgetsCache: null,
		paintedPanels: {},
	};

	function money(n) {
		n = Number(n) || 0;
		try {
			return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' đ';
		} catch (e) {
			return String(Math.round(n)) + ' đ';
		}
	}

	function num(n) {
		n = Number(n) || 0;
		try {
			return new Intl.NumberFormat('vi-VN').format(n);
		} catch (e) {
			return String(n);
		}
	}

	function errText(err) {
		if (err == null || err === false) return 'Lỗi tải dữ liệu';
		if (typeof err === 'string') return err;
		if (typeof err === 'object') {
			if (err.message) return String(err.message);
			if (err.error) return errText(err.error);
			try {
				return JSON.stringify(err);
			} catch (e) {
				return 'Lỗi tải dữ liệu';
			}
		}
		return String(err);
	}

	function api(params) {
		var data = $.extend({ module: 'Home', action: 'AdminKpiApi' }, params || {});

		function normalize(res) {
			if (res && res.result && typeof res.result === 'object') {
				res = res.result;
			}
			if (!res || typeof res !== 'object') {
				return $.Deferred().reject('Phản hồi không hợp lệ').promise();
			}
			if (res.success === false) {
				return $.Deferred().reject(errText(res.error || res.message)).promise();
			}
			return res;
		}

		// Prefer GET for read aggregates (avoids CSRF HTML error pages → JSON parse fails).
		return $.ajax({
			url: 'index.php',
			type: 'GET',
			dataType: 'json',
			cache: false,
			data: data,
		}).then(
			function (res) {
				return normalize(res);
			},
			function (xhr) {
				var msg = 'Lỗi tải dữ liệu';
				if (xhr && xhr.responseJSON) {
					msg = errText(xhr.responseJSON.error || xhr.responseJSON);
				} else if (xhr && xhr.responseText) {
					try {
						var parsed = JSON.parse(xhr.responseText);
						msg = errText(parsed.error || parsed);
					} catch (e) {
						msg = errText(xhr.statusText || e);
					}
				}
				return $.Deferred().reject(msg).promise();
			}
		);
	}

	function setLoading($el) {
		$el.html('<div class="mk-admin-kpi-detail-loading">Đang tải…</div>');
	}

	function setError($el, msg) {
		$el.html(
			'<div class="mk-admin-kpi-detail-error">' +
				$('<div/>').text(errText(msg)).html() +
				'</div>'
		);
	}

	function escapeHtml(s) {
		return $('<div/>').text(s == null ? '' : String(s)).html();
	}

	function decodeHtml(s) {
		if (s == null) return '';
		var str = String(s);
		try {
			var ta = document.createElement('textarea');
			ta.innerHTML = str;
			str = ta.value;
			ta.innerHTML = str;
			str = ta.value;
		} catch (e) {}
		return str;
	}

	function safeLabel(s) {
		return escapeHtml(decodeHtml(s));
	}

	function loadSummary($root) {
		return api({ mode: 'summary' }).done(function (data) {
			var s = (data && data.summary) || {};
			if (s.business_year) {
				state.chartYear = parseInt(s.business_year, 10) || state.chartYear;
			}
			$root.find('[data-key="customers"]').text(num(s.customers));
			$root.find('[data-key="leads_today"]').text(num(s.leads_today));
			$root.find('[data-key="revenue_month"]').text(money(s.revenue_month));
			$root.find('[data-key="quotes_pending"]').text(num(s.quotes_pending));
			$root.find('[data-key="orders_processing"]').text(num(s.orders_processing));
			$root.find('[data-key="franchise_contracts"]').text(num(s.franchise_contracts));
		});
	}

	function loadDetail($root) {
		var $detail = $root.find('#mkAdminKpiDetail');
		setLoading($detail);
		var params = { mode: 'detail', section: state.section };
		if (state.section === 'revenue') {
			params.revenue_mode = state.revenueMode;
			params.period = state.period;
			params.full = state.fullSales ? 1 : 0;
			if (state.saleId) params.sale_id = state.saleId;
		}
		return api(params)
			.done(function (data) {
				$detail.html(renderDetail(state.section, (data && data.detail) || {}));
			})
			.fail(function (msg) {
				setError($detail, msg);
			});
	}

	function loadWidgets($root) {
		var params = {
			mode: 'widgets',
			group: state.chartDimension === 'none' ? state.chartGroup : state.chartGroup,
			dimension: state.chartDimension,
			year: state.chartYear,
			stage_period: state.stagePeriod,
			stage_offset: state.stageOffset,
		};
		if (state.chartDimension !== 'none') {
			params.group = state.chartDimension;
			params.dimension = state.chartDimension;
		}
		return api(params)
			.done(function (data) {
				state.widgetsCache = data || {};
				state.paintedPanels = {};
				renderAlerts($root, (data && data.alerts) || { items: [] });
				renderFunnel($root, (data && data.funnel) || { stages: [] });
				renderChart($root, (data && data.revenue_chart) || {});
				renderPerf($root, (data && data.performance) || {});
				renderCompany($root, (data && data.company_report) || {});
				syncStagePeriodNav($root, (data && data.stage_nav) || null);
				paintActiveStagePanel($root);
			})
			.fail(function (msg) {
				setError($root.find('#mkAdminKpiFunnelBody'), msg);
				setError($root.find('#mkAdminKpiChartBody'), msg);
				setError($root.find('#mkAdminKpiPerfBody'), msg);
				setError($root.find('#mkAdminKpiOfflineBody'), msg);
				setError($root.find('#mkAdminKpiOnlineBody'), msg);
				setError($root.find('#mkAdminKpiGd14Body'), msg);
				setError($root.find('#mkAdminKpiPcthBody'), msg);
				setError($root.find('#mkAdminKpiMqbbBody'), msg);
				setError($root.find('#mkAdminKpiComboBody'), msg);
				setError($root.find('#mkAdminKpiNlBody'), msg);
			});
	}

	function paintActiveStagePanel($root) {
		var zone = state.stagePanel || 'offline';
		var data = state.widgetsCache || {};
		if (state.paintedPanels[zone]) {
			return;
		}
		if (zone === 'offline') {
			renderOffline($root, data.offline_gd11 || {});
		} else if (zone === 'online') {
			renderOnline($root, data.online_gd12 || {});
		} else if (zone === 'gd14') {
			renderGd14($root, data.gd14 || {});
		} else if (zone === 'pcth') {
			renderGd14Course($root, 'pcth', data.gd14_pcth || {});
		} else if (zone === 'mqbb') {
			renderGd14Course($root, 'mqbb', data.gd14_mqbb || {});
		} else if (zone === 'combo') {
			renderGd14Course($root, 'combo', data.gd14_combo || {});
		} else if (zone === 'nl') {
			renderMaterials($root, data.materials || {});
		}
		state.paintedPanels[zone] = true;
	}

	function loadChartOnly($root) {
		var params = {
			mode: 'revenue_chart',
			group: state.chartGroup,
			dimension: state.chartDimension,
			year: state.chartYear,
		};
		$root.find('#mkAdminKpiChartDrill').attr('hidden', true).empty();
		setLoading($root.find('#mkAdminKpiChartBody'));
		return api(params)
			.done(function (data) {
				renderChart($root, (data && data.revenue_chart) || {});
			})
			.fail(function (msg) {
				setError($root.find('#mkAdminKpiChartBody'), msg);
			});
	}

	function renderAlerts($root, alerts) {
		var $box = $root.find('#mkAdminKpiAlerts');
		var items = (alerts && alerts.items) || [];
		if (!items.length) {
			items = [
				{ level: 'muted', count: 0, label: 'KH tiềm năng chưa gọi', drill: { type: 'leads_urgency', key: 'not_contacted' } },
				{ level: 'muted', count: 0, label: 'Đơn hàng đang nháp', drill: { type: 'orders_status', key: 'draft' } },
				{ level: 'muted', count: 0, label: 'Báo giá đang nháp', drill: { type: 'quotes_status', key: 'draft' } },
				{ level: 'muted', count: 0, label: 'KH nhượng quyền chưa nghe máy', drill: { type: 'franchise_missed', key: '' } },
			];
		}
		var html = '';
		items.forEach(function (a) {
			var lvl = a.level || (Number(a.count) > 0 ? 'warn' : 'muted');
			var drill = a.drill || null;
			if (drill && drill.type) {
				html +=
					'<button type="button" class="mk-admin-kpi-alert mk-admin-kpi-alert--' +
					escapeHtml(lvl) +
					'" data-drill-zone="alert" data-drill-type="' +
					escapeHtml(drill.type) +
					'" data-drill-key="' +
					escapeHtml(drill.key || '') +
					'">⚠ ' +
					num(a.count) +
					' ' +
					safeLabel(a.label) +
					'</button>';
			} else {
				html +=
					'<a class="mk-admin-kpi-alert mk-admin-kpi-alert--' +
					escapeHtml(lvl) +
					'" href="' +
					escapeHtml(a.url || '#') +
					'">⚠ ' +
					num(a.count) +
					' ' +
					safeLabel(a.label) +
					'</a>';
			}
		});
		$box.html(html).removeAttr('hidden');
	}

	var PERF_PIE_COLORS = ['#2563eb', '#10b981', '#7c3aed', '#f59e0b', '#f43f5e', '#06b6d4'];
	var FUNNEL_COLORS = ['#2563eb', '#06b6d4', '#10b981', '#f59e0b', '#7c3aed', '#f43f5e'];
	var OFFLINE_COLORS = ['#2563eb', '#f59e0b', '#06b6d4', '#10b981', '#f43f5e', '#64748b'];
	var ONLINE_COLORS = ['#2563eb', '#f59e0b', '#06b6d4', '#10b981', '#f43f5e', '#64748b', '#8b5cf6', '#a855f7'];

	function polarToCartesian(cx, cy, r, angleDeg) {
		var rad = ((angleDeg - 90) * Math.PI) / 180;
		return { x: cx + r * Math.cos(rad), y: cy + r * Math.sin(rad) };
	}

	function describePieSlice(cx, cy, r, startAngle, endAngle) {
		if (endAngle - startAngle >= 359.99) {
			// Full circle
			return (
				'M ' +
				cx +
				' ' +
				(cy - r) +
				' A ' +
				r +
				' ' +
				r +
				' 0 1 1 ' +
				cx +
				' ' +
				(cy + r) +
				' A ' +
				r +
				' ' +
				r +
				' 0 1 1 ' +
				cx +
				' ' +
				(cy - r) +
				' Z'
			);
		}
		var start = polarToCartesian(cx, cy, r, endAngle);
		var end = polarToCartesian(cx, cy, r, startAngle);
		var large = endAngle - startAngle > 180 ? 1 : 0;
		return (
			'M ' +
			cx +
			' ' +
			cy +
			' L ' +
			end.x +
			' ' +
			end.y +
			' A ' +
			r +
			' ' +
			r +
			' 0 ' +
			large +
			' 1 ' +
			start.x +
			' ' +
			start.y +
			' Z'
		);
	}

	function renderPerfPie(items) {
		var list = (items || []).slice(0, 5);
		if (!list.length) {
			return '<div class="mk-admin-kpi-placeholder">Chưa có dữ liệu</div>';
		}
		var total = 0;
		list.forEach(function (it) {
			total += Math.max(0, Number(it.score) || 0);
		});
		var cx = 70;
		var cy = 70;
		var r = 58;
		var svg =
			'<svg class="mk-admin-kpi-pie" viewBox="0 0 140 140" width="140" height="140" aria-hidden="true">';
		if (total <= 0) {
			svg +=
				'<circle cx="70" cy="70" r="58" fill="#e8eee9"></circle>' +
				'<text x="70" y="74" text-anchor="middle" fill="#6b7280" font-size="12">0</text>';
		} else {
			var angle = 0;
			list.forEach(function (it, i) {
				var score = Math.max(0, Number(it.score) || 0);
				var slice = (score / total) * 360;
				if (slice <= 0) return;
				var next = angle + slice;
				var color = PERF_PIE_COLORS[i % PERF_PIE_COLORS.length];
				svg +=
					'<path d="' +
					describePieSlice(cx, cy, r, angle, next) +
					'" fill="' +
					color +
					'" stroke="#fff" stroke-width="1.5">' +
					'<title>' +
					escapeHtml(it.name || '') +
					': ' +
					(it.percent != null ? it.percent : Math.round((score / total) * 100)) +
					'%</title></path>';
				angle = next;
			});
		}
		svg += '</svg>';
		var legend = '<ul class="mk-admin-kpi-pie-legend">';
		list.forEach(function (it, i) {
			var pct = it.percent != null ? Number(it.percent) : 0;
			legend +=
				'<li><span class="mk-admin-kpi-pie-dot" style="background:' +
				PERF_PIE_COLORS[i % PERF_PIE_COLORS.length] +
				'"></span><span class="mk-admin-kpi-pie-name">' +
				safeLabel(it.name) +
				'</span><strong>' +
				pct +
				'%</strong></li>';
		});
		legend += '</ul>';
		return '<div class="mk-admin-kpi-pie-wrap">' + svg + legend + '</div>';
	}

	function renderPerfColumn(title, items) {
		return (
			'<div class="mk-admin-kpi-perf-col"><h3 class="mk-admin-kpi-section-title">' +
			escapeHtml(title) +
			'</h3>' +
			renderPerfPie(items) +
			'</div>'
		);
	}

	function renderPerf($root, perf) {
		var html =
			renderPerfColumn('Top 5 NV bán hàng', perf.sale || []) +
			renderPerfColumn('Top 5 quản lý dự án', perf.pm || []) +
			renderPerfColumn('Top 5 hỗ trợ', perf.support || []);
		$root.find('#mkAdminKpiPerfBody').html(html);
	}

	function renderDonut(items, colors) {
		colors = colors || PERF_PIE_COLORS;
		var list = (items || []).filter(function (it) {
			return Number(it.count != null ? it.count : it.value) > 0;
		});
		if (!list.length) {
			return '<div class="mk-admin-kpi-placeholder">Chưa có dữ liệu</div>';
		}
		var total = 0;
		list.forEach(function (it) {
			total += Number(it.count != null ? it.count : it.value) || 0;
		});
		if (total <= 0) {
			return '<div class="mk-admin-kpi-placeholder">Chưa có dữ liệu</div>';
		}
		var r = 54;
		var c = 2 * Math.PI * r;
		var offset = 0;
		var circles = '';
		list.forEach(function (it, i) {
			var val = Number(it.count != null ? it.count : it.value) || 0;
			var len = (val / total) * c;
			var color = it.color || colors[i % colors.length];
			circles +=
				'<circle class="mk-admin-kpi-donut-seg" cx="70" cy="70" r="' +
				r +
				'" fill="none" stroke="' +
				color +
				'" stroke-width="16" stroke-linecap="butt" style="--donut-len:' +
				len +
				';--donut-gap:' +
				(c - len) +
				';--donut-off:' +
				(-offset) +
				';--donut-delay:' +
				(i * 0.08) +
				's" transform="rotate(-90 70 70)"></circle>';
			offset += len;
		});
		return (
			'<div class="mk-admin-kpi-pie-wrap mk-admin-kpi-pie-wrap--anim">' +
			'<svg class="mk-admin-kpi-donut-svg" viewBox="0 0 140 140" width="140" height="140" aria-hidden="true">' +
			'<circle cx="70" cy="70" r="' +
			r +
			'" fill="none" stroke="#e2e8f0" stroke-width="16"></circle>' +
			circles +
			'<text x="70" y="74" text-anchor="middle" font-size="18" font-weight="800" fill="#0f172a">' +
			num(total) +
			'</text></svg></div>'
		);
	}

	function stageIconSvg(kind) {
		var paths = {
			rate: '<path d="M8 14a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 4.5v3.7l2.2 1.3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
			users: '<path d="M6 7.2a2.2 2.2 0 1 0 0-4.4 2.2 2.2 0 0 0 0 4.4Zm4.8.4a1.8 1.8 0 1 0-1.5-1" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M1.8 13.2c.4-2.2 2.1-3.4 4.2-3.4s3.8 1.2 4.2 3.4M10.4 9.2c1.5.2 2.7 1.1 3.1 2.8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
			check: '<path d="M3.2 8.2 6.4 11.2 12.8 4.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
			money: '<path d="M2.5 5.2h11v6.6h-11V5.2Z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 6.4v4.2M6.2 8.5h3.6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
			warn: '<path d="M8 2.8 14.2 13.2H1.8L8 2.8Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M8 6.4v3.2M8 11.4h.01" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
			target: '<circle cx="8" cy="8" r="5.2" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="8" cy="8" r="2.2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 1.6v1.8M8 12.6v1.8M1.6 8h1.8M12.6 8h1.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
			box: '<path d="M2.4 5.2 8 2.4l5.6 2.8v5.6L8 13.6 2.4 10.8V5.2Z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M2.4 5.2 8 8l5.6-2.8M8 8v5.6" fill="none" stroke="currentColor" stroke-width="1.5"/>',
			phone: '<path d="M5 2.8h2.4l1 2.4-1.4 1.2a8.5 8.5 0 0 0 3.6 3.6l1.2-1.4 2.4 1v2.4A1.4 1.4 0 0 1 12.8 13.4 10.6 10.6 0 0 1 2.6 3.2 1.4 1.4 0 0 1 5 2.8Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>',
		};
		var d = paths[kind] || paths.target;
		return (
			'<svg class="mk-admin-kpi-stat-ico" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true">' +
			d +
			'</svg>'
		);
	}

	function stageIconKind(label, isRate) {
		var s = String(label || '').toLowerCase();
		if (isRate) return 'rate';
		if (/doanh thu|giá trị|đơn|thanh toán|money|revenue/.test(s)) return 'money';
		if (/liên hệ|gọi|phone|nghe máy/.test(s)) return 'phone';
		if (/hồ sơ|khách|người|combo|pcth|mqbb|990/.test(s)) return 'users';
		if (/xác minh|tham gia|chốt|đủ|xác nhận/.test(s)) return 'check';
		if (/sai|hủy|rủi|cảnh|quá hạn|trễ|mâu thuẫn|chặn/.test(s)) return 'warn';
		if (/nguyên liệu|sku|giao|kho|nl0|ql0/.test(s)) return 'box';
		return 'target';
	}

	function parsePct(value) {
		var m = String(value || '').match(/(-?\d+(?:\.\d+)?)\s*%/);
		return m ? Math.max(0, Math.min(100, parseFloat(m[1]))) : null;
	}

	function stageHit(item, zone, index) {
		var label = safeLabel(item.label);
		var value = item.value != null ? String(item.value) : num(item.count);
		var delay = Math.min(0.2, Math.min(8, Number(index) || 0) * 0.025);
		var pct = parsePct(value);
		var isRate = pct !== null;
		var icon = stageIconSvg(stageIconKind(item.label, isRate));
		var tone = item.color || (isRate ? '#047857' : '#2563eb');
		var ring =
			isRate
				? '<span class="mk-admin-kpi-stat-ring" style="--pct:' +
				  pct +
				  ';--tone:' +
				  escapeHtml(tone) +
				  '"><i></i></span>'
				: '';
		if (item.soon) {
			return (
				'<div class="mk-admin-kpi-offline-stat is-soon mk-admin-kpi-stat-card" style="--stagger:' +
				delay +
				's"><span class="mk-admin-kpi-stat-top">' +
				icon +
				'<span>' +
				label +
				'</span></span><strong>Coming soon</strong></div>'
			);
		}
		var body =
			'<span class="mk-admin-kpi-stat-top">' +
			icon +
			'<span>' +
			label +
			(item.hint ? '<em class="mk-admin-kpi-card-label"> · ' + escapeHtml(item.hint) + '</em>' : '') +
			'</span></span>' +
			'<span class="mk-admin-kpi-stat-bottom">' +
			ring +
			'<strong style="color:' +
			escapeHtml(tone) +
			'">' +
			escapeHtml(value) +
			'</strong></span>';
		if (item.nodrill) {
			return (
				'<div class="mk-admin-kpi-offline-stat mk-admin-kpi-stat-card" style="--stagger:' +
				delay +
				's">' +
				body +
				'</div>'
			);
		}
		var drill = item.drill || {};
		return (
			'<button type="button" class="mk-admin-kpi-offline-stat mk-admin-kpi-stage-hit mk-admin-kpi-stat-card" style="--stagger:' +
			delay +
			's" data-drill-zone="' +
			escapeHtml(zone) +
			'" data-drill-type="' +
			escapeHtml(drill.type || 'stage_people') +
			'" data-drill-key="' +
			escapeHtml(drill.key || '') +
			'">' +
			body +
			'</button>'
		);
	}

	function renderStageBoard(data, zone) {
		data = data || {};
		var rates = data.rates || [];
		var stages = data.stages || [];
		var splits = data.splits || [];
		var soon = data.soon || [];
		if (!stages.length && !rates.length && !splits.length) {
			return (
				'<div class="mk-admin-kpi-empty-hud">' +
				'<div class="mk-admin-kpi-empty-hud__glow"></div>' +
				'<p class="mk-admin-kpi-placeholder">Chưa có hồ sơ trong kỳ này</p>' +
				'</div>' +
				renderSoon(soon)
			);
		}
		var colColors = ['#2563eb', '#0f766e', '#b45309', '#7c3aed', '#0891b2', '#e11d48'];
		var donut = [];
		var statusRows = [];
		var statusTotal = 0;
		stages.forEach(function (item, i) {
			var key = String((item.drill && item.drill.key) || '');
			var count = Number(item.count) || 0;
			var row = stageItemToHudRow(item, i);
			if (key.indexOf(':all') >= 0) {
				statusTotal = count;
				row.code = 'ALL';
				row.muted = false;
				statusRows.unshift(row);
				return;
			}
			row.muted = count <= 0;
			statusRows.push(row);
			if (count > 0) {
				donut.push({
					label: item.label,
					count: count,
					color: item.color || colColors[donut.length % colColors.length],
				});
			}
		});
		if (!statusTotal) {
			statusRows.forEach(function (r) {
				if (String(r.code).toUpperCase() !== 'ALL') {
					statusTotal += Number(r.count) || 0;
				}
			});
		}
		var groups = [];
		if (statusRows.length) {
			groups.push({
				key: 'TT',
				title: 'Trạng thái',
				total: statusTotal,
				color: '#2563eb',
				open: statusRows,
				zero_count: 0,
				zero_codes: [],
				empty_text: 'Chưa có trạng thái',
				total_label: 'hồ sơ',
			});
		}
		splits.forEach(function (sp, si) {
			var rows = [];
			var sum = 0;
			(sp.items || []).forEach(function (item, i) {
				var row = stageItemToHudRow(item, i);
				var count = Number(item.count) || 0;
				var val = item.value != null ? String(item.value) : '';
				row.muted = !count && (val === '' || val === '0' || val === '0%' || val === '—');
				sum += count;
				rows.push(row);
			});
			groups.push({
				key: String(si + 1),
				title: sp.title || 'Nhóm',
				total: sum,
				color: colColors[(si + 1) % colColors.length],
				open: rows,
				zero_count: 0,
				zero_codes: [],
				empty_text: 'Chưa có dữ liệu',
			});
		});
		return renderHudBoard({
			zone: zone,
			rates: rates,
			donut: donut,
			groups: groups,
			footerHtml: renderSoon(soon),
		});
	}

	function stageItemToHudRow(item, index) {
		item = item || {};
		var label = String(item.label || '');
		var key = String((item.drill && item.drill.key) || '');
		var code = '';
		var m = label.match(/\b([A-Z]{1,4}\d{1,3})\b/);
		if (m) {
			code = m[1];
		} else {
			var parts = key.split(':');
			var last = parts.length ? parts[parts.length - 1] : '';
			if (last && last !== 'all' && last.length <= 18) {
				code = last.replace(/^gd1[124]_?/i, '').replace(/_/g, ' ').slice(0, 10);
			}
			if (!code) {
				code = String(index + 1);
			}
		}
		var display = item.value != null ? String(item.value) : num(item.count);
		return {
			code: code,
			label: label,
			count: Number(item.count) || 0,
			display: display,
			color: item.color || '',
			drill: item.drill || null,
			nodrill: !!item.nodrill || !item.drill,
		};
	}

	function renderHudRow(row, zone, fallbackColor) {
		row = row || {};
		var color = row.muted ? '#94a3b8' : row.color || fallbackColor || '#0f172a';
		var cls = 'mk-nl-row' + (row.muted ? ' is-muted' : ' is-hot');
		var body =
			'<span class="mk-nl-row__code">' +
			escapeHtml(row.code || '') +
			'</span><span class="mk-nl-row__label">' +
			safeLabel(row.label) +
			'</span><strong style="color:' +
			escapeHtml(color) +
			'">' +
			escapeHtml(row.display != null ? String(row.display) : num(row.count)) +
			'</strong>';
		if (row.nodrill || !row.drill || !zone) {
			return '<div class="' + cls + '">' + body + '</div>';
		}
		return (
			'<button type="button" class="' +
			cls +
			' mk-admin-kpi-stage-hit" data-drill-zone="' +
			escapeHtml(zone) +
			'" data-drill-type="' +
			escapeHtml(row.drill.type || 'stage_people') +
			'" data-drill-key="' +
			escapeHtml(row.drill.key || '') +
			'">' +
			body +
			'</button>'
		);
	}

	function renderHudBoard(opts) {
		opts = opts || {};
		var zone = opts.zone || '';
		var rates = opts.rates || [];
		var groups = opts.groups || [];
		var donut = opts.donut || [];
		var ql = opts.ql || null;
		var html = '<div class="mk-nl-board" data-board-zone="' + escapeHtml(zone) + '">';
		html += '<div class="mk-nl-hero">';
		html += '<div class="mk-nl-kpi' + (rates.length > 4 ? ' is-wrap' : '') + '">';
		rates.forEach(function (item, i) {
			html += stageHit(item, zone, i);
		});
		html += '</div>';
		if (donut.some(function (d) { return Number(d.count) > 0; })) {
			html +=
				'<div class="mk-nl-donut">' +
				renderDonut(donut, ['#2563eb', '#0f766e', '#b45309', '#7c3aed', '#0891b2', '#e11d48']) +
				'</div>';
		}
		html += '</div>';
		if (groups.length) {
			html += '<div class="mk-nl-cols' + (groups.length === 1 ? ' is-one' : groups.length === 2 ? ' is-two' : '') + '">';
			groups.forEach(function (g) {
				var totalLabel = g.total_label || 'mục';
				html += '<section class="mk-nl-col" style="--col:' + escapeHtml(g.color || '#64748b') + '">';
				html +=
					'<header class="mk-nl-col__head"><span class="mk-nl-col__key">' +
					escapeHtml(g.key || '') +
					'</span><div><strong>' +
					safeLabel(g.title) +
					'</strong><em>' +
					num(g.total) +
					' ' +
					escapeHtml(totalLabel) +
					'</em></div></header>';
				html += '<div class="mk-nl-col__body">';
				var open = g.open || [];
				if (!open.length) {
					html += '<p class="mk-nl-empty">' + safeLabel(g.empty_text || 'Không có dữ liệu') + '</p>';
				} else {
					open.forEach(function (row) {
						html += renderHudRow(row, zone, g.color);
					});
				}
				if (Number(g.zero_count) > 0) {
					var zeros = g.zero_codes || [];
					html +=
						'<div class="mk-nl-zero" title="' +
						escapeHtml(zeros.join(', ')) +
						'">+' +
						num(g.zero_count) +
						' mục = 0' +
						(zeros.length
							? ' · ' +
							  escapeHtml(
									zeros
										.slice(0, 5)
										.map(function (z) {
											return String(z).slice(0, 24);
										})
										.join(', ')
							  ) +
							  (zeros.length > 5 ? '…' : '')
							: '') +
						'</div>';
				}
				html += '</div></section>';
			});
			html += '</div>';
		}
		if (ql) {
			html += '<section class="mk-nl-ql">';
			html += '<h3 class="mk-nl-ql__title">' + safeLabel(ql.title || 'QL') + '</h3>';
			html += '<div class="mk-nl-ql__grid">';
			(ql.hot || []).forEach(function (row) {
				var muted = !!row.muted || !(Number(row.count) > 0);
				html +=
					'<div class="mk-nl-ql__item' +
					(muted ? ' is-muted' : ' is-hot') +
					'"><span>' +
					escapeHtml(row.code || '') +
					' — ' +
					safeLabel(row.label) +
					'</span><strong style="color:' +
					escapeHtml(muted ? '#94a3b8' : row.color || '#b45309') +
					'">' +
					num(row.count) +
					'</strong></div>';
			});
			html += '</div>';
			if ((ql.cold || []).length) {
				html +=
					'<p class="mk-nl-ql__cold">Theo dõi (chưa có số): ' +
					(ql.cold || [])
						.map(function (r) {
							return escapeHtml(r.code);
						})
						.join(', ') +
					'</p>';
			}
			html += '</section>';
		}
		if (opts.footerHtml) {
			html += opts.footerHtml;
		}
		html += '</div>';
		return html;
	}

	function syncStagePeriodNav($root, nav) {
		nav = nav || {};
		if (typeof nav.offset === 'number') {
			state.stageOffset = Math.max(0, nav.offset);
		}
		var label = nav.label || fallbackPeriodLabel();
		$root.find('#mkAdminKpiPeriodLabel').text(label);
		$root.find('[data-stage-nav="next"]').prop('disabled', !(nav.can_next || state.stageOffset > 0));
		$root.find('[data-stage-nav="prev"]').prop('disabled', nav.can_prev === false);
		$root.find('[data-stage-period]').removeClass('is-active');
		$root.find('[data-stage-period="' + (state.stagePeriod || 'month') + '"]').addClass('is-active');
	}

	function fallbackPeriodLabel() {
		var now = new Date();
		if (state.stagePeriod === 'year') {
			return 'Năm ' + (now.getFullYear() - state.stageOffset);
		}
		if (state.stagePeriod === 'quarter') {
			var q = Math.ceil((now.getMonth() + 1) / 3);
			var idx = now.getFullYear() * 4 + q - 1 - state.stageOffset;
			return 'Quý ' + ((idx % 4) + 1) + '/' + Math.floor(idx / 4);
		}
		var d = new Date(now.getFullYear(), now.getMonth() - state.stageOffset, 1);
		var m = d.getMonth() + 1;
		return 'Tháng ' + (m < 10 ? '0' : '') + m + '/' + d.getFullYear();
	}

	function renderSoon(items) {
		if (!items || !items.length) return '';
		var html = '<div class="mk-admin-kpi-soon"><span class="mk-admin-kpi-soon-label">Coming soon</span>';
		items.forEach(function (label) {
			html += '<span class="mk-admin-kpi-soon-chip">' + safeLabel(label) + '</span>';
		});
		html += '</div>';
		return html;
	}

	function renderCompany($root, data) {
		$root.find('#mkAdminKpiBiz').html(renderPlainCards(data.business));
		$root.find('#mkAdminKpiCourse').html(renderPlainCards(data.courses));
	}

	function renderPlainCards(items) {
		items = items || [];
		if (!items.length) {
			return '<div class="mk-admin-kpi-detail-loading">Chưa đủ dữ liệu</div>';
		}
		var tones = ['violet', 'emerald', 'blue', 'amber', 'rose', 'cyan'];
		var html = '';
		items.forEach(function (item, index) {
			var missing = item.missing || item.value === 'Chưa đủ dữ liệu';
			var tone = item.tone || tones[index % tones.length];
			html +=
				'<div class="mk-admin-kpi-card mk-admin-kpi-card--static' + (missing ? ' is-missing' : '') + '" data-tone="' + escapeHtml(tone) + '" role="listitem">' +
				'<span class="mk-admin-kpi-card-label">' + escapeHtml(item.label || '') + '</span>' +
				(missing
					? '<span class="mk-admin-kpi-card-empty">Chưa đủ dữ liệu</span>'
					: '<span class="mk-admin-kpi-card-value">' + escapeHtml(item.value || '—') + '</span>') +
				(item.hint ? '<span class="mk-admin-kpi-card-label">' + escapeHtml(item.hint) + '</span>' : '') +
				'</div>';
		});
		return html;
	}

	function renderOffline($root, data) {
		data = data || {};
		$root.find('#mkAdminKpiOfflineRate').text(data.period_label || 'Tháng này · SỐ TẠM');
		$root.find('#mkAdminKpiOfflineBody').html(renderStageBoard(data, 'offline'));
	}

	function renderOnline($root, data) {
		data = data || {};
		$root.find('#mkAdminKpiOnlineFormRate').text(data.period_label || 'Tháng này · SỐ TẠM');
		$root.find('#mkAdminKpiOnlineQualifyRate').text(
			'Đủ ĐK: ' + (data.qualify_rate != null ? data.qualify_rate + '%' : '—')
		);
		$root.find('#mkAdminKpiOnlineBody').html(renderStageBoard(data, 'online'));
	}

	function renderGd14($root, data) {
		data = data || {};
		$root.find('#mkAdminKpiGd14Period').text(data.period_label || 'Tháng này · SỐ TẠM');
		$root.find('#mkAdminKpiGd14Body').html(renderStageBoard(data, 'gd14'));
	}

	function renderGd14Course($root, course, data) {
		data = data || {};
		var idMap = { pcth: 'Pcth', mqbb: 'Mqbb', combo: 'Combo' };
		var suffix = idMap[course] || 'Gd14';
		$root.find('#mkAdminKpi' + suffix + 'Period').text(data.period_label || 'Tháng này · SỐ TẠM');
		$root.find('#mkAdminKpi' + suffix + 'Body').html(renderStageBoard(data, 'gd14_' + course));
	}

	function renderMaterials($root, data) {
		data = data || {};
		$root.find('#mkAdminKpiNlBody').html(renderMaterialsBoard(data));
	}

	function renderMaterialsBoard(data) {
		data = data || {};
		if (data.layout !== 'materials') {
			return renderStageBoard(data, 'nl');
		}
		return renderHudBoard({
			zone: 'nl',
			rates: data.rates || [],
			donut: data.donut || [],
			groups: (data.groups || []).map(function (g) {
				return {
					key: g.key,
					title: g.title,
					total: g.total,
					color: g.color,
					open: (g.open || []).map(function (row) {
						return {
							code: row.code,
							label: row.label,
							count: row.count,
							display: num(row.count),
							color: row.color || g.color,
							muted: !!row.muted || !(Number(row.count) > 0),
							nodrill: true,
						};
					}),
					zero_count: 0,
					zero_codes: [],
					empty_text: 'Không có việc mở',
					total_label: 'việc',
				};
			}),
			ql: data.ql || null,
		});
	}

	function renderFunnel($root, funnel) {
		var stages = funnel.stages || [];
		if (!stages.length) {
			$root.find('#mkAdminKpiFunnelBody').html('<div class="mk-admin-kpi-placeholder">Chưa có dữ liệu phễu bán hàng</div>');
			return;
		}
		var donutItems = stages.map(function (s, i) {
			return {
				count: s.count,
				label: s.label,
				color: FUNNEL_COLORS[i % FUNNEL_COLORS.length],
			};
		});
		var html = '<div class="mk-admin-kpi-funnel-layout">' + renderDonut(donutItems, FUNNEL_COLORS);
		html += '<div class="mk-admin-kpi-funnel-steps">';
		stages.forEach(function (s, i) {
			html +=
				'<a class="mk-admin-kpi-funnel-step" href="' +
				escapeHtml(s.url || '#') +
				'" style="--w:' +
				Math.max(18, Number(s.percent) || 0) +
				'%; border-left:4px solid ' +
				FUNNEL_COLORS[i % FUNNEL_COLORS.length] +
				'">' +
				'<span class="mk-admin-kpi-funnel-label">' +
				safeLabel(s.label) +
				'</span>' +
				'<span class="mk-admin-kpi-funnel-count">' +
				num(s.count) +
				'</span></a>';
			if (i < stages.length - 1) {
				html += '<span class="mk-admin-kpi-funnel-arrow" aria-hidden="true">→</span>';
			}
		});
		html += '</div></div>';
		$root.find('#mkAdminKpiFunnelBody').html(html);
	}

	function renderChart($root, chart) {
		var labels = chart.labels || [];
		var series = chart.series || [];
		var keys = chart.keys || [];
		var drillType = chart.drill_type || '';
		var chartYear = chart.year || state.chartYear || '';
		var max = 1;
		series.forEach(function (v) {
			if (Number(v) > max) max = Number(v);
		});
		$root.find('#mkAdminKpiChartTotal').text(
			'Tổng: ' + money(chart.total || 0) + (chart.year ? ' · Năm ' + chart.year : '')
		);
		if (!labels.length) {
			$root.find('#mkAdminKpiChartBody').html('<div class="mk-admin-kpi-placeholder">Chưa có doanh thu</div>');
			return;
		}
		var html = '<div class="mk-admin-kpi-vbars">';
		labels.forEach(function (lab, i) {
			var v = Number(series[i]) || 0;
			var h = Math.round((v / max) * 100);
			var key = keys[i] != null ? String(keys[i]) : '';
			var clickable = drillType && key !== '';
			var tag = clickable ? 'button' : 'div';
			var attrs = ' class="mk-admin-kpi-vbar' + (clickable ? ' is-clickable' : '') + '"';
			if (clickable) {
				attrs +=
					' type="button" data-drill-type="' +
					escapeHtml(drillType) +
					'" data-drill-key="' +
					escapeHtml(key) +
					'"' +
					(drillType === 'revenue_sale' ? ' data-drill-id="' + escapeHtml(key) + '"' : '') +
					(chartYear ? ' data-drill-year="' + escapeHtml(String(chartYear)) + '"' : '') +
					' title="' +
					escapeHtml(lab) +
					': ' +
					money(v) +
					' — bấm để xem đơn"';
			} else {
				attrs += ' title="' + escapeHtml(lab) + ': ' + money(v) + '"';
			}
			html +=
				'<' +
				tag +
				attrs +
				'>' +
				'<div class="mk-admin-kpi-vbar-track">' +
				'<div class="mk-admin-kpi-vbar-fill" style="--bar-h:' +
				Math.max(h, 4) +
				'%; --bar-delay:' +
				(i * 0.05) +
				's"></div></div>' +
				'<span class="mk-admin-kpi-vbar-val">' +
				(v >= 1000000 ? num(Math.round(v / 1000000)) + 'tr' : num(Math.round(v))) +
				'</span>' +
				'<span class="mk-admin-kpi-vbar-lab">' +
				safeLabel(lab) +
				'</span></' +
				tag +
				'>';
		});
		html += '</div>';
		$root.find('#mkAdminKpiChartBody').html(html);
		// Retrigger grow animation on each re-render (filter change)
		window.requestAnimationFrame(function () {
			window.requestAnimationFrame(function () {
				$root.find('#mkAdminKpiChartBody .mk-admin-kpi-vbar-fill').addClass('is-grown');
			});
		});
	}

	function renderStatRow(items) {
		var html = '<div class="mk-admin-kpi-stat-row">';
		items.forEach(function (it) {
			var drill = it.drill || null;
			var tag = drill ? 'button' : 'div';
			var attrs = ' class="mk-admin-kpi-stat' + (drill ? ' is-clickable' : '') + '"';
			if (drill) {
				attrs +=
					' type="button" data-drill-type="' +
					escapeHtml(drill.type || '') +
					'" data-drill-key="' +
					escapeHtml(drill.key || '') +
					'"' +
					(drill.id ? ' data-drill-id="' + escapeHtml(String(drill.id)) + '"' : '');
			}
			html +=
				'<' +
				tag +
				attrs +
				'>' +
				'<span class="mk-admin-kpi-stat-label">' +
				escapeHtml(it.label) +
				'</span>' +
				'<span class="mk-admin-kpi-stat-value">' +
				escapeHtml(it.value) +
				'</span></' +
				tag +
				'>';
		});
		html += '</div>';
		return html;
	}

	function renderTable(headers, rows) {
		var html = '<table class="mk-admin-kpi-table"><thead><tr>';
		headers.forEach(function (h) {
			html += '<th class="' + (h.num ? 'num' : '') + '">' + escapeHtml(h.label) + '</th>';
		});
		html += '</tr></thead><tbody>';
		if (!rows.length) {
			html += '<tr><td colspan="' + headers.length + '">Chưa có dữ liệu</td></tr>';
		} else {
			rows.forEach(function (cols) {
				html += '<tr>';
				cols.forEach(function (c, i) {
					html +=
						'<td class="' +
						(headers[i] && headers[i].num ? 'num' : '') +
						'">' +
						c +
						'</td>';
				});
				html += '</tr>';
			});
		}
		html += '</tbody></table>';
		return html;
	}

	function renderBars(items) {
		var html = '<div class="mk-admin-kpi-bars">';
		items.forEach(function (it) {
			var pct = Math.max(0, Math.min(100, Number(it.percent) || 0));
			html +=
				'<div class="mk-admin-kpi-bar-row">' +
				'<span>' +
				safeLabel(it.label) +
				'</span>' +
				'<div class="mk-admin-kpi-bar-track"><div class="mk-admin-kpi-bar-fill" style="width:' +
				pct +
				'%"></div></div>' +
				'<span class="num">' +
				escapeHtml(String(pct)) +
				'%</span></div>';
		});
		html += '</div>';
		return html;
	}

	function isChartDrillType(type) {
		return (
			type === 'revenue_region' ||
			type === 'revenue_product' ||
			type === 'revenue_sale' ||
			type === 'revenue_month' ||
			type === 'revenue_quarter' ||
			type === 'revenue_year'
		);
	}

	function drillSig(zone, type, key, id) {
		return String(zone || 'detail') + '|' + String(type || '') + '|' + String(key || '') + '|' + String(id || 0);
	}

	function drillTargetSel(zone, type) {
		if (zone === 'offline') return '#mkAdminKpiOfflineDrill';
		if (zone === 'online') return '#mkAdminKpiOnlineDrill';
		if (zone === 'gd14') return '#mkAdminKpiGd14Drill';
		if (zone === 'gd14_pcth') return '#mkAdminKpiPcthDrill';
		if (zone === 'gd14_mqbb') return '#mkAdminKpiMqbbDrill';
		if (zone === 'gd14_combo') return '#mkAdminKpiComboDrill';
		if (zone === 'alert') return '#mkAdminKpiAlertDrill';
		if (zone === 'chart' || isChartDrillType(type)) return '#mkAdminKpiChartDrill';
		return '#mkAdminKpiDrill';
	}

	function clearDrillActive($root) {
		$root.find('.mk-admin-kpi-alert.is-open, .mk-admin-kpi-stat.is-open, .mk-admin-kpi-vbar.is-open, .mk-admin-kpi-stage-hit.is-open').removeClass('is-open');
	}

	function loadDrilldown(type, key, id, year, zone) {
		var $root = $(ROOT_SEL);
		zone = zone || (isChartDrillType(type) ? 'chart' : 'detail');
		var sel = drillTargetSel(zone, type);
		var $drill = $root.find(sel);
		$root.find('#mkAdminKpiDrill, #mkAdminKpiChartDrill, #mkAdminKpiAlertDrill, #mkAdminKpiOfflineDrill, #mkAdminKpiOnlineDrill, #mkAdminKpiGd14Drill, #mkAdminKpiPcthDrill, #mkAdminKpiMqbbDrill, #mkAdminKpiComboDrill').not(sel).attr('hidden', true).empty();
		// Không auto-scroll — bảng hiện ngay dưới vùng vừa bấm
		$drill.removeAttr('hidden').html('<div class="mk-admin-kpi-detail-loading">Đang tải danh sách…</div>');
		var params = {
			mode: 'drilldown',
			type: type,
			key: key || '',
			id: id || 0,
		};
		if (year) params.year = year;
		params.stage_period = state.stagePeriod;
		params.stage_offset = state.stageOffset;
		return api(params)
			.done(function (data) {
				$drill.html(renderDrillPanel((data && data.drilldown) || {}));
			})
			.fail(function (msg) {
				setError($drill, msg);
			});
	}

	function hideDrilldown($root) {
		$root = $root || $(ROOT_SEL);
		$root.find('#mkAdminKpiDrill, #mkAdminKpiChartDrill, #mkAdminKpiAlertDrill, #mkAdminKpiOfflineDrill, #mkAdminKpiOnlineDrill, #mkAdminKpiGd14Drill').attr('hidden', true).empty();
		state.openDrillSig = '';
		clearDrillActive($root);
	}

	function renderDrillPanel(dd) {
		var rows = dd.rows || [];
		var html =
			'<div class="mk-admin-kpi-drill-inner">' +
			'<div class="mk-admin-kpi-drill-head">' +
			'<h2 class="mk-admin-kpi-detail-title">' +
			safeLabel(dd.title || 'Chi tiết') +
			'</h2>' +
			'<button type="button" class="mk-admin-kpi-link-btn" data-close-drill="1">Đóng</button></div>';
		if (dd.hint) {
			html += '<p class="mk-admin-kpi-chart-total">' + safeLabel(dd.hint) + '</p>';
		}
		var module = dd.module || '';
		if (module === 'SalesOrder') {
			html += renderTable(
				[
					{ label: 'Mã ĐH' },
					{ label: 'Khách hàng' },
					{ label: 'Trạng thái' },
					{ label: 'Tiền', num: true },
					{ label: 'Thao tác' },
				],
				rows.map(function (r) {
					return [
						safeLabel(r.no || '#' + r.id),
						safeLabel(r.contact),
						safeLabel(r.status),
						money(r.total),
						'<a class="mk-admin-kpi-row-link" href="' +
							escapeHtml(r.detail_url || '#') +
							'" target="_blank" rel="noopener">Chi tiết</a>' +
							(r.print_url
								? ' · <a class="mk-admin-kpi-row-link" href="' +
								  escapeHtml(r.print_url) +
								  '" target="_blank" rel="noopener">In phiếu</a>'
								: ''),
					];
				})
			);
		} else if (module === 'Quotes') {
			html += renderTable(
				[
					{ label: 'Mã BG' },
					{ label: 'Khách hàng' },
					{ label: 'Trạng thái' },
					{ label: 'Tiền', num: true },
					{ label: 'Thao tác' },
				],
				rows.map(function (r) {
					return [
						safeLabel(r.no || '#' + r.id),
						safeLabel(r.contact),
						safeLabel(r.status),
						money(r.total),
						'<a class="mk-admin-kpi-row-link" href="' +
							escapeHtml(r.detail_url || '#') +
							'" target="_blank" rel="noopener">Chi tiết</a>',
					];
				})
			);
		} else if (module === 'Contacts') {
			html += renderTable(
				[
					{ label: 'Tên' },
					{ label: 'SĐT' },
					{ label: 'Thao tác' },
				],
				rows.map(function (r) {
					return [
						safeLabel(r.name),
						safeLabel(r.phone || '—'),
						'<a class="mk-admin-kpi-row-link" href="' +
							escapeHtml(r.detail_url || '#') +
							'" target="_blank" rel="noopener">Chi tiết</a>' +
							(r.orders_drill
								? ' · <button type="button" class="mk-admin-kpi-row-link mk-admin-kpi-row-btn" data-drill-type="customer_orders" data-drill-id="' +
								  r.id +
								  '">Đơn / In phiếu</button>'
								: ''),
					];
				})
			);
		} else if (module === 'Leads') {
			html += renderTable(
				[
					{ label: 'Tên' },
					{ label: 'Nguồn' },
					{ label: 'Trạng thái' },
					{ label: 'Thao tác' },
				],
				rows.map(function (r) {
					return [
						safeLabel(r.name),
						safeLabel(r.source || '—'),
						safeLabel(r.status || '—'),
						'<a class="mk-admin-kpi-row-link" href="' +
							escapeHtml(r.detail_url || '#') +
							'" target="_blank" rel="noopener">Chi tiết</a>',
					];
				})
			);
		} else if (module === 'StageRoster') {
			html += renderTable(
				[
					{ label: 'Tên' },
					{ label: 'SĐT' },
					{ label: 'Trạng thái' },
					{ label: 'Thao tác' },
				],
				rows.map(function (r) {
					return [
						safeLabel(r.name),
						safeLabel(r.phone || '—'),
						safeLabel(r.status || '—'),
						'<a class="mk-admin-kpi-row-link" href="' +
							escapeHtml(r.detail_url || '#') +
							'" target="_blank" rel="noopener">Chi tiết</a>',
					];
				})
			);
		} else if (module === 'ServiceContracts') {
			html += renderTable(
				[
					{ label: 'Mã HĐ' },
					{ label: 'Tiêu đề' },
					{ label: 'Trạng thái' },
					{ label: 'Thao tác' },
				],
				rows.map(function (r) {
					return [
						safeLabel(r.no || '#' + r.id),
						safeLabel(r.name),
						safeLabel(r.status),
						'<a class="mk-admin-kpi-row-link" href="' +
							escapeHtml(r.detail_url || '#') +
							'" target="_blank" rel="noopener">Chi tiết</a>',
					];
				})
			);
		} else {
			html += '<div class="mk-admin-kpi-placeholder">Không có dữ liệu</div>';
		}
		html += '</div>';
		return html;
	}

	function renderDetail(section, d) {
		switch (section) {
			case 'customers':
				return renderCustomers(d);
			case 'leads':
				return renderLeads(d);
			case 'revenue':
				return renderRevenue(d);
			case 'quotes':
				return renderQuotes(d);
			case 'orders':
				return renderOrders(d);
			case 'franchise':
				return (
					'<h2 class="mk-admin-kpi-detail-title">Hợp đồng nhượng quyền</h2>' +
					'<p class="mk-admin-kpi-chart-total">Tổng: <button type="button" class="mk-admin-kpi-inline-drill" data-drill-type="franchise">' +
					num(d.total) +
					'</button> — bấm số để xem danh sách</p>' +
					'<div class="mk-admin-kpi-placeholder">' +
					escapeHtml(d.message || 'Chi tiết nâng cao đang cập nhật. Bấm tổng ở trên để mở danh sách HĐ.') +
					'</div>'
				);
			default:
				return '<div class="mk-admin-kpi-placeholder">Không có dữ liệu</div>';
		}
	}

	function renderCustomers(d) {
		var html =
			'<h2 class="mk-admin-kpi-detail-title">Khách hàng</h2>' +
			renderStatRow([
				{ label: 'Tổng khách hàng', value: num(d.total), drill: { type: 'customers', key: 'all' } },
				{ label: 'Mới tháng này', value: num(d.new_month), drill: { type: 'customers', key: 'new_month' } },
				{ label: 'Đang hoạt động', value: num(d.active), drill: { type: 'customers', key: 'active' } },
			]);
		var tiers = d.tiers || {};
		html +=
			'<div class="mk-admin-kpi-section-title">Hạng khách</div>' +
			renderStatRow([
				{ label: 'Khách Vàng', value: num(tiers.gold), drill: { type: 'customers', key: 'gold' } },
				{ label: 'Khách Bạc', value: num(tiers.silver), drill: { type: 'customers', key: 'silver' } },
				{ label: 'Khách Đồng', value: num(tiers.bronze), drill: { type: 'customers', key: 'bronze' } },
			]);
		html += '<div class="mk-admin-kpi-section-title">Top 5 khách hàng theo doanh thu <span class="mk-admin-kpi-hint">(bấm tên → đơn / in phiếu)</span></div>';
		html += '<table class="mk-admin-kpi-table"><thead><tr><th>Khách hàng</th><th class="num">Doanh thu</th></tr></thead><tbody>';
		var tops = d.top_customers || [];
		if (!tops.length) {
			html += '<tr><td colspan="2">Chưa có dữ liệu</td></tr>';
		} else {
			tops.forEach(function (r) {
				html +=
					'<tr class="is-clickable-row" data-drill-type="customer_orders" data-drill-id="' +
					r.id +
					'"><td><button type="button" class="mk-admin-kpi-row-btn" data-drill-type="customer_orders" data-drill-id="' +
					r.id +
					'">' +
					safeLabel(r.name) +
					'</button></td><td class="num">' +
					money(r.revenue) +
					'</td></tr>';
			});
		}
		html += '</tbody></table>';
		return html;
	}

	function renderLeads(d) {
		var html =
			'<h2 class="mk-admin-kpi-detail-title">Khách hàng tiềm năng</h2>' +
			renderStatRow([
				{ label: 'Hôm nay', value: num(d.today), drill: { type: 'leads_period', key: 'today' } },
				{ label: 'Tuần này', value: num(d.week), drill: { type: 'leads_period', key: 'week' } },
				{ label: 'Tháng này', value: num(d.month), drill: { type: 'leads_period', key: 'month' } },
			]);
		html += '<div class="mk-admin-kpi-section-title">Nguồn (%)</div>';
		html += renderBars(
			((d.sources && d.sources.items) || []).map(function (it) {
				return { label: it.label, percent: it.percent };
			})
		);
		html += '<div class="mk-admin-kpi-section-title">Top NV bán hàng đang xử lý</div>';
		html += renderTable(
			[
				{ label: 'NV bán hàng' },
				{ label: 'Số lượng', num: true },
			],
			(d.top_sales || []).map(function (r) {
				return [safeLabel(r.name), num(r.count)];
			})
		);
		html +=
			'<div class="mk-admin-kpi-section-title">Độ ưu tiên liên hệ <span class="mk-admin-kpi-hint">(bấm ô → danh sách tương ứng)</span></div>' +
			renderStatRow([
				{ label: 'Chưa liên hệ', value: num(d.not_contacted), drill: { type: 'leads_urgency', key: 'not_contacted' } },
				{ label: 'Quá 24 giờ', value: num(d.over_24h), drill: { type: 'leads_urgency', key: 'over_24h' } },
				{ label: 'Quá 72 giờ', value: num(d.over_72h), drill: { type: 'leads_urgency', key: 'over_72h' } },
			]);
		return html;
	}

	function renderRevenueModes() {
		return (
			'<div class="mk-admin-kpi-modes">' +
			'<button type="button" class="mk-admin-kpi-mode-btn' +
			(state.revenueMode === 'total' ? ' is-active' : '') +
			'" data-revenue-mode="total">Tổng doanh thu</button>' +
			'<button type="button" class="mk-admin-kpi-mode-btn' +
			(state.revenueMode === 'product' ? ' is-active' : '') +
			'" data-revenue-mode="product">Theo sản phẩm</button>' +
			'<button type="button" class="mk-admin-kpi-mode-btn' +
			(state.revenueMode === 'sale' ? ' is-active' : '') +
			'" data-revenue-mode="sale">Theo NV bán hàng</button></div>'
		);
	}

	function renderPeriodToolbar() {
		var periods = [
			{ key: 'today', label: 'Hôm nay' },
			{ key: 'week', label: 'Tuần' },
			{ key: 'month', label: 'Tháng' },
			{ key: 'year', label: 'Năm' },
		];
		var html = '<div class="mk-admin-kpi-toolbar">';
		periods.forEach(function (p) {
			html +=
				'<button type="button" class="mk-admin-kpi-mode-btn' +
				(state.period === p.key ? ' is-active' : '') +
				'" data-period="' +
				p.key +
				'">' +
				p.label +
				'</button>';
		});
		html += '</div>';
		return html;
	}

	function renderRevenue(d) {
		var html = '<h2 class="mk-admin-kpi-detail-title">Doanh thu</h2>' + renderRevenueModes();
		if (d.mode === 'total' || state.revenueMode === 'total') {
			html += renderStatRow([
				{ label: 'Hôm nay', value: money(d.today), drill: { type: 'revenue_period', key: 'today' } },
				{ label: 'Tuần này', value: money(d.week), drill: { type: 'revenue_period', key: 'week' } },
				{ label: 'Tháng này', value: money(d.month), drill: { type: 'revenue_period', key: 'month' } },
				{ label: 'Năm nay', value: money(d.year), drill: { type: 'revenue_period', key: 'year' } },
			]);
			return html;
		}
		if (d.mode === 'product' || state.revenueMode === 'product') {
			html += renderPeriodToolbar();
			html +=
				'<div class="mk-admin-kpi-section-title">Tổng: <button type="button" class="mk-admin-kpi-inline-drill" data-drill-type="revenue_period" data-drill-key="' +
				escapeHtml(state.period || 'month') +
				'">' +
				money(d.total) +
				'</button></div>';
			html += renderBars(d.items || []);
			return html;
		}
		if (d.mode === 'sale_detail') {
			var sale = d.sale || {};
			html +=
				'<div class="mk-admin-kpi-toolbar"><button type="button" class="mk-admin-kpi-link-btn" data-back-sales="1">← Danh sách NV bán hàng</button></div>';
			html += renderPeriodToolbar();
			html +=
				'<div class="mk-admin-kpi-section-title">' + safeLabel(sale.name || 'NV bán hàng') + '</div>';
			html += renderStatRow([
				{ label: 'Doanh thu', value: money(sale.revenue) },
				{ label: 'Số đơn', value: num(sale.orders) },
			]);
			html += '<div class="mk-admin-kpi-section-title">Đơn gần đây</div>';
			html += renderTable(
				[
					{ label: 'Mã ĐH' },
					{ label: 'Trạng thái' },
					{ label: 'Tiền', num: true },
					{ label: 'Thao tác' },
				],
				(d.recent_orders || []).map(function (o) {
					var detail =
						'index.php?module=SalesOrder&view=Detail&record=' + o.id + '&app=SALES';
					var printU =
						'index.php?module=SalesOrder&view=Print&record=' + o.id + '&app=SALES';
					return [
						safeLabel(o.no || '#' + o.id),
						safeLabel(o.status),
						money(o.total),
						'<a class="mk-admin-kpi-row-link" href="' +
							detail +
							'" target="_blank" rel="noopener">Chi tiết</a> · <a class="mk-admin-kpi-row-link" href="' +
							printU +
							'" target="_blank" rel="noopener">In phiếu</a>',
					];
				})
			);
			return html;
		}
		html += renderPeriodToolbar();
		html +=
			'<div class="mk-admin-kpi-toolbar"><button type="button" class="mk-admin-kpi-link-btn" data-toggle-full-sales="1">' +
			(state.fullSales ? 'Thu gọn danh sách' : 'Xem tất cả NV bán hàng') +
			'</button></div><div class="mk-admin-kpi-sale-grid">';
		(d.items || []).forEach(function (it) {
			html +=
				'<button type="button" class="mk-admin-kpi-sale-card" data-sale-id="' +
				it.id +
				'"><span class="mk-admin-kpi-sale-name">' +
				safeLabel(it.name) +
				'</span><span class="mk-admin-kpi-sale-meta">' +
				money(it.revenue) +
				' · ' +
				num(it.orders) +
				' đơn</span></button>';
		});
		if (!(d.items || []).length) {
			html += '<div class="mk-admin-kpi-placeholder">Chưa có doanh thu theo NV bán hàng</div>';
		}
		html += '</div>';
		return html;
	}

	function renderQuotes(d) {
		var html =
			'<h2 class="mk-admin-kpi-detail-title">Báo giá</h2>' +
			renderStatRow(
				(d.status || []).map(function (s) {
					return {
						label: s.label,
						value: num(s.count),
						drill: s.drill || { type: 'quotes_status', key: s.key },
					};
				})
			);
		html += '<div class="mk-admin-kpi-section-title">Theo NV bán hàng</div>';
		html += renderTable(
			[
				{ label: 'Tên' },
				{ label: 'Số lượng', num: true },
			],
			(d.by_sale || []).map(function (r) {
				return [safeLabel(r.name), num(r.count)];
			})
		);
		return html;
	}

	function renderOrders(d) {
		var html =
			'<h2 class="mk-admin-kpi-detail-title">Đơn hàng</h2>' +
			'<div class="mk-admin-kpi-section-title">Theo trạng thái</div>' +
			renderStatRow(
				(d.status || []).map(function (s) {
					return {
						label: s.label,
						value: num(s.count),
						drill: s.drill || { type: 'orders_status', key: s.key },
					};
				})
			) +
			'<div class="mk-admin-kpi-section-title">Theo kho</div>' +
			renderStatRow(
				(d.warehouse || []).map(function (s) {
					return {
						label: s.label,
						value: num(s.count),
						drill: s.drill || { type: 'orders_warehouse', key: s.key },
					};
				})
			);
		return html;
	}

	function syncChartFilterUi($root) {
		var $f = $root.find('#mkAdminKpiChartFilters');
		$f.find('[data-chart-group]').removeClass('is-active');
		$f.find('[data-chart-dimension]').removeClass('is-active');
		if (state.chartDimension === 'none') {
			$f.find('[data-chart-group="' + state.chartGroup + '"]').addClass('is-active');
		} else {
			$f.find('[data-chart-dimension="' + state.chartDimension + '"]').addClass('is-active');
		}
	}

	function bind($root) {
		$root.on('click', '.mk-admin-kpi-card', function () {
			var section = $(this).data('section');
			if (!section) return;
			state.section = section;
			state.saleId = 0;
			state.fullSales = false;
			if (section === 'revenue') state.revenueMode = 'total';
			$root.find('.mk-admin-kpi-card').removeClass('is-active');
			$(this).addClass('is-active');
			// Bước 1→2: chỉ hiện Summary (vd. Doanh thu Hôm nay/Tuần/Tháng/Năm).
			// Chưa mở Data Table — chỉ khi bấm tiếp vào ô summary.
			hideDrilldown($root);
			loadDetail($root);
		});

		$root.on('click', '[data-drill-type]', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(this);
			var type = String($btn.data('drill-type') || '');
			var key = String($btn.data('drill-key') || '');
			var id = parseInt($btn.data('drill-id'), 10) || 0;
			var year = parseInt($btn.data('drill-year'), 10) || state.chartYear || 0;
			var zone = String($btn.data('drill-zone') || '');
			if (!zone) {
				if ($btn.closest('#mkAdminKpiAlerts').length || $btn.hasClass('mk-admin-kpi-alert')) {
					zone = 'alert';
				} else if (isChartDrillType(type) || $btn.hasClass('mk-admin-kpi-vbar')) {
					zone = 'chart';
				} else {
					zone = 'detail';
				}
			}
			if (!type) return;

			var sig = drillSig(zone, type, key, id);
			// Bấm lại cùng chỗ → đóng bảng
			if (state.openDrillSig === sig) {
				hideDrilldown($root);
				return;
			}

			clearDrillActive($root);
			$btn.addClass('is-open');
			state.openDrillSig = sig;
			loadDrilldown(type, key, id, year, zone);
		});

		$root.on('click', '[data-stage-panel]', function () {
			var zone = String($(this).data('stage-panel') || 'offline');
			state.stagePanel = zone;
			$root.find('[data-stage-panel]').removeClass('is-active');
			$(this).addClass('is-active');
			$root.find('#mkAdminKpiOffline, #mkAdminKpiOnline, #mkAdminKpiGd14, #mkAdminKpiPcth, #mkAdminKpiMqbb, #mkAdminKpiCombo, #mkAdminKpiNl').attr('hidden', true);
			var map = {
				offline: '#mkAdminKpiOffline',
				online: '#mkAdminKpiOnline',
				gd14: '#mkAdminKpiGd14',
				pcth: '#mkAdminKpiPcth',
				mqbb: '#mkAdminKpiMqbb',
				combo: '#mkAdminKpiCombo',
				nl: '#mkAdminKpiNl',
			};
			$root.find(map[zone] || '#mkAdminKpiOffline').removeAttr('hidden');
			paintActiveStagePanel($root);
		});
		$root.on('click', '[data-stage-period]', function () {
			state.stagePeriod = String($(this).data('stage-period') || 'month');
			state.stageOffset = 0;
			$root.find('[data-stage-period]').removeClass('is-active');
			$(this).addClass('is-active');
			hideDrilldown($root);
			loadWidgets($root);
		});
		$root.on('click', '[data-stage-nav]', function () {
			var dir = String($(this).data('stage-nav') || '');
			if (dir === 'prev') {
				if (state.stageOffset >= 120) return;
				state.stageOffset += 1;
			} else if (dir === 'next') {
				if (state.stageOffset <= 0) return;
				state.stageOffset -= 1;
			} else {
				return;
			}
			hideDrilldown($root);
			loadWidgets($root);
		});

		$root.on('click', '[data-close-drill]', function () {
			hideDrilldown($root);
		});

		$root.on('click', '[data-revenue-mode]', function () {
			state.revenueMode = String($(this).data('revenue-mode') || 'total');
			state.saleId = 0;
			state.fullSales = false;
			hideDrilldown($root);
			loadDetail($root);
		});
		$root.on('click', '[data-period]', function () {
			state.period = String($(this).data('period') || 'month');
			hideDrilldown($root);
			loadDetail($root);
		});
		$root.on('click', '[data-toggle-full-sales]', function () {
			state.fullSales = !state.fullSales;
			state.saleId = 0;
			hideDrilldown($root);
			loadDetail($root);
		});
		$root.on('click', '[data-sale-id]', function () {
			state.saleId = parseInt($(this).data('sale-id'), 10) || 0;
			state.revenueMode = 'sale';
			hideDrilldown($root);
			loadDetail($root);
		});
		$root.on('click', '[data-back-sales]', function () {
			state.saleId = 0;
			state.revenueMode = 'sale';
			hideDrilldown($root);
			loadDetail($root);
		});

		$root.on('click', '[data-chart-group]', function () {
			state.chartGroup = String($(this).data('chart-group') || 'month');
			state.chartDimension = 'none';
			syncChartFilterUi($root);
			loadChartOnly($root);
		});
		$root.on('click', '[data-chart-dimension]', function () {
			state.chartDimension = String($(this).data('chart-dimension') || 'none');
			syncChartFilterUi($root);
			loadChartOnly($root);
		});
	}

	function init() {
		var $root = $(ROOT_SEL);
		if (!$root.length || $root.data('mk-kpi-bound')) return;
		$root.data('mk-kpi-bound', 1);
		bind($root);
		syncChartFilterUi($root);
		syncStagePeriodNav($root, { offset: 0, label: fallbackPeriodLabel(), can_next: false, can_prev: true });
		loadSummary($root).always(function () {
			loadDetail($root);
			loadWidgets($root);
		});
	}

	$(init);
	$(document).on('ajaxComplete mk.pjax.complete', function () {
		init();
	});
})(jQuery);
