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
		};
		if (state.chartDimension !== 'none') {
			params.group = state.chartDimension;
			params.dimension = state.chartDimension;
		}
		return api(params)
			.done(function (data) {
				renderAlerts($root, (data && data.alerts) || { items: [] });
				renderFunnel($root, (data && data.funnel) || { stages: [] });
				renderChart($root, (data && data.revenue_chart) || {});
				renderPerf($root, (data && data.performance) || {});
			})
			.fail(function (msg) {
				setError($root.find('#mkAdminKpiFunnelBody'), msg);
				setError($root.find('#mkAdminKpiChartBody'), msg);
				setError($root.find('#mkAdminKpiPerfBody'), msg);
			});
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

	var PERF_PIE_COLORS = ['#0f8a4b', '#14a85a', '#3ecf8e', '#7dd3a7', '#b8e6ce'];

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

	function renderFunnel($root, funnel) {
		var stages = funnel.stages || [];
		if (!stages.length) {
			$root.find('#mkAdminKpiFunnelBody').html('<div class="mk-admin-kpi-placeholder">Chưa có dữ liệu phễu bán hàng</div>');
			return;
		}
		var html = '<div class="mk-admin-kpi-funnel-steps">';
		stages.forEach(function (s, i) {
			html +=
				'<a class="mk-admin-kpi-funnel-step" href="' +
				escapeHtml(s.url || '#') +
				'" style="--w:' +
				Math.max(18, Number(s.percent) || 0) +
				'%">' +
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
		html += '</div>';
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
				'<div class="mk-admin-kpi-vbar-fill" style="height:' +
				h +
				'%"></div>' +
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
		if (zone === 'alert') return '#mkAdminKpiAlertDrill';
		if (zone === 'chart' || isChartDrillType(type)) return '#mkAdminKpiChartDrill';
		return '#mkAdminKpiDrill';
	}

	function clearDrillActive($root) {
		$root.find('.mk-admin-kpi-alert.is-open, .mk-admin-kpi-stat.is-open, .mk-admin-kpi-vbar.is-open').removeClass('is-open');
	}

	function loadDrilldown(type, key, id, year, zone) {
		var $root = $(ROOT_SEL);
		zone = zone || (isChartDrillType(type) ? 'chart' : 'detail');
		var sel = drillTargetSel(zone, type);
		var $drill = $root.find(sel);
		$root.find('#mkAdminKpiDrill, #mkAdminKpiChartDrill, #mkAdminKpiAlertDrill').not(sel).attr('hidden', true).empty();
		// Không auto-scroll — bảng hiện ngay dưới vùng vừa bấm
		$drill.removeAttr('hidden').html('<div class="mk-admin-kpi-detail-loading">Đang tải danh sách…</div>');
		var params = {
			mode: 'drilldown',
			type: type,
			key: key || '',
			id: id || 0,
		};
		if (year) params.year = year;
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
		$root.find('#mkAdminKpiDrill, #mkAdminKpiChartDrill, #mkAdminKpiAlertDrill').attr('hidden', true).empty();
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
