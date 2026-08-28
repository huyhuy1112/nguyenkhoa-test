/**
 * Opportunity Detail — Lịch sử mua hàng + Hợp đồng dịch vụ (SALES UI).
 */
(function () {
	'use strict';

	function byId(id) {
		return document.getElementById(id);
	}

	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}

	function formatVnd(n) {
		try {
			return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0);
		} catch (e) {
			return (n || 0).toLocaleString('vi-VN') + ' đ';
		}
	}

	function parsePurchaseDate(dateStr) {
		if (!dateStr) return null;
		var parts = String(dateStr).split('/');
		if (parts.length !== 3) return null;
		var d = parseInt(parts[0], 10);
		var m = parseInt(parts[1], 10) - 1;
		var y = parseInt(parts[2], 10);
		if (!d || m < 0 || !y) return null;
		return new Date(y, m, d);
	}

	function recordId() {
		var el = byId('recordId');
		return el && el.value ? String(el.value) : '';
	}

	function orderTotal(orders) {
		var sum = 0;
		for (var i = 0; i < orders.length; i++) {
			sum += orders[i].value || 0;
		}
		return sum;
	}

	function commerceMetrics(purchases) {
		var logic = window.LeadsLeadsLogic;
		var leadLike = { purchases: purchases || [] };
		if (!logic) {
			return { recentOrder: 0 };
		}
		return {
			recentOrder: logic.recentOrderValue(leadLike),
		};
	}

	function groupProducts(purchases) {
		var map = {};
		(purchases || []).forEach(function (p) {
			var key = p.product || p.orderName || 'Sản phẩm';
			if (!map[key]) {
				map[key] = {
					product: key,
					qty: 0,
					value: 0,
					date: p.date,
					dateTs: 0,
				};
			}
			map[key].qty += parseInt(p.qty, 10) || 0;
			map[key].value += p.value || 0;
			var dt = parsePurchaseDate(p.date);
			var ts = dt ? dt.getTime() : 0;
			if (ts >= map[key].dateTs) {
				map[key].dateTs = ts;
				map[key].date = p.date;
			}
		});
		return Object.keys(map)
			.map(function (k) {
				return map[k];
			})
			.sort(function (a, b) {
				return b.dateTs - a.dateTs;
			});
	}

	function orderTableHtml(orders, metrics, emptyMsg) {
		if (!orders.length) {
			return '<p class="mk-opp-purchase__empty">' + esc(emptyMsg || 'Chưa có đơn mua hàng.') + '</p>';
		}
		var total = orderTotal(orders);
		var rows = orders
			.map(function (o) {
				return (
					'<tr><td>' +
					esc(o.orderName || o.orderId || 'Đơn hàng') +
					'</td><td>' +
					esc(o.qty) +
					'</td><td>' +
					esc(formatVnd(o.value)) +
					'</td><td>' +
					esc(o.date) +
					'</td></tr>'
				);
			})
			.join('');
		var recentRow = '';
		if (metrics.recentOrder) {
			recentRow =
				'<tr><td colspan="2" class="mk-opp-purchase__total-label">Đơn gần nhất:</td>' +
				'<td class="mk-opp-purchase__total-value" colspan="2">' +
				esc(formatVnd(metrics.recentOrder)) +
				'</td></tr>';
		}
		return (
			'<table class="mk-opp-purchase__table">' +
			'<thead><tr><th>Đơn hàng</th><th>SL</th><th>Giá trị</th><th>Ngày</th></tr></thead>' +
			'<tbody>' +
			rows +
			'</tbody>' +
			'<tfoot>' +
			recentRow +
			'<tr><td colspan="2" class="mk-opp-purchase__total-label">Tổng:</td>' +
			'<td class="mk-opp-purchase__total-value" colspan="2">' +
			esc(formatVnd(total)) +
			'</td></tr></tfoot></table>'
		);
	}

	function productTableHtml(products, emptyMsg) {
		if (!products.length) {
			return '<p class="mk-opp-purchase__empty">' + esc(emptyMsg || 'Chưa có sản phẩm mua hàng.') + '</p>';
		}
		var totalValue = 0;
		var totalQty = 0;
		var rows = products
			.map(function (p) {
				totalValue += p.value || 0;
				totalQty += p.qty || 0;
				return (
					'<tr><td>' +
					esc(p.product) +
					'</td><td>' +
					esc(p.qty) +
					'</td><td>' +
					esc(formatVnd(p.value)) +
					'</td><td>' +
					esc(p.date) +
					'</td></tr>'
				);
			})
			.join('');
		return (
			'<table class="mk-opp-purchase__table">' +
			'<thead><tr><th>Sản phẩm</th><th>SL</th><th>Giá trị</th><th>Ngày</th></tr></thead>' +
			'<tbody>' +
			rows +
			'</tbody>' +
			'<tfoot>' +
			'<tr><td colspan="2" class="mk-opp-purchase__total-label">Tổng SL:</td>' +
			'<td class="mk-opp-purchase__total-value" colspan="2">' +
			esc(totalQty) +
			'</td></tr>' +
			'<tr><td colspan="2" class="mk-opp-purchase__total-label">Tổng giá trị:</td>' +
			'<td class="mk-opp-purchase__total-value" colspan="2">' +
			esc(formatVnd(totalValue)) +
			'</td></tr></tfoot></table>'
		);
	}

	function serviceContractsTableHtml(contracts) {
		if (!contracts.length) {
			return '<p class="mk-opp-purchase__empty">Chưa có hợp đồng dịch vụ.</p>';
		}
		var rows = contracts
			.map(function (c) {
				var label = c.subject || c.contractNo || ('HĐ #' + c.id);
				var period = [c.startDate, c.endDate].filter(Boolean).join(' – ');
				return (
					'<tr><td><a href="' +
					esc(c.detailUrl) +
					'">' +
					esc(label) +
					'</a></td><td>' +
					esc(c.contractNo || '—') +
					'</td><td>' +
					esc(c.status || '—') +
					'</td><td>' +
					esc(period || '—') +
					'</td></tr>'
				);
			})
			.join('');
		return (
			'<table class="mk-opp-purchase__table mk-opp-service-contracts__table">' +
			'<thead><tr><th>Hợp đồng</th><th>Mã</th><th>Trạng thái</th><th>Thời hạn</th></tr></thead>' +
			'<tbody>' +
			rows +
			'</tbody></table>'
		);
	}

	function renderPanels(purchases) {
		var logic = window.LeadsLeadsLogic;
		var metrics = commerceMetrics(purchases);
		var title = byId('mk-opp-purchase-title');
		var ordersHost = byId('mk-opp-commerce-orders-month');
		var productsHost = byId('mk-opp-commerce-products-total');
		var allItems = purchases || [];
		var allOrders = logic && logic.groupOrders ? logic.groupOrders(allItems) : allItems;
		var monthOrders =
			logic && logic.ordersInLastDays
				? logic.ordersInLastDays({ purchases: allItems }, 30)
				: allOrders.slice();
		var monthEmptyMsg = 'Không có đơn trong 30 ngày gần nhất.';
		if (!monthOrders.length && allOrders.length) {
			monthOrders = allOrders;
			monthEmptyMsg = 'Chưa có đơn mua hàng.';
		}
		var allProducts = groupProducts(allItems);

		if (title) {
			title.textContent = 'Lịch sử mua hàng (' + allOrders.length + ')';
		}
		if (ordersHost) {
			ordersHost.innerHTML = orderTableHtml(monthOrders, metrics, monthEmptyMsg);
		}
		if (productsHost) {
			productsHost.innerHTML = productTableHtml(allProducts, 'Chưa có sản phẩm mua hàng.');
		}
	}

	function updateServiceContractsTabBadge(count) {
		var badge = document.querySelector(
			'.mk-opportunity-detail-related-tabs li.mk-opp-service-contracts-tab .numberCircle'
		);
		if (!badge) return;
		var n = parseInt(count, 10);
		if (isNaN(n)) n = 0;
		badge.textContent = String(n);
		badge.classList.remove('hide');
		badge.setAttribute('data-count', String(n));
	}

	function renderServiceContracts(contracts) {
		var title = byId('mk-opp-service-contracts-title');
		var host = byId('mk-opp-service-contracts-body');
		var addBtn = byId('mk-opp-add-service-contract');
		var id = recordId();
		var list = contracts || [];
		if (title) {
			title.textContent = 'Hợp đồng dịch vụ (' + list.length + ')';
		}
		if (host) {
			host.innerHTML = serviceContractsTableHtml(list);
		}
		if (addBtn && id) {
			addBtn.href =
				'index.php?module=ServiceContracts&view=Edit&app=SALES&sourceModule=Potentials&sourceRecord=' +
				encodeURIComponent(id);
		}
		updateServiceContractsTabBadge(list.length);
	}

	function activateCommerceSubTab(key) {
		var root = byId('mk-opp-section-purchases');
		if (!root) return;
		var subKey = key || 'orders-month';
		root.querySelectorAll('[data-mk-commerce-tab]').forEach(function (btn) {
			var active = btn.getAttribute('data-mk-commerce-tab') === subKey;
			btn.classList.toggle('is-active', active);
			btn.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		root.querySelectorAll('[data-mk-commerce-panel]').forEach(function (panel) {
			panel.classList.toggle('hide', panel.getAttribute('data-mk-commerce-panel') !== subKey);
		});
	}

	function bindCommerceSubTabs() {
		if (document.documentElement.getAttribute('data-mk-opp-commerce-tabs') === '1') {
			return;
		}
		document.documentElement.setAttribute('data-mk-opp-commerce-tabs', '1');
		document.addEventListener('click', function (e) {
			var btn = e.target && e.target.closest ? e.target.closest('[data-mk-commerce-tab]') : null;
			if (!btn || !byId('mk-opp-section-purchases') || !byId('mk-opp-section-purchases').contains(btn)) {
				return;
			}
			activateCommerceSubTab(btn.getAttribute('data-mk-commerce-tab'));
		});
		activateCommerceSubTab('orders-month');
	}

	function consumeCommerceRefreshFlag() {
		try {
			var key = 'mkOppCommerceRefresh';
			var flag = sessionStorage.getItem(key);
			var id = recordId();
			if (flag && id && String(flag) === String(id)) {
				sessionStorage.removeItem(key);
				return true;
			}
			if (flag && id) {
				sessionStorage.removeItem(key);
			}
		} catch (e) {
			/* ignore */
		}
		return false;
	}

	function mkOppCommerceOnSummaryLoad() {
		bindCommerceSubTabs();
		bindLinkOrder();
		window.mkOppRefreshPurchaseHistory = refreshCommercePanels;
		refreshCommercePanels();
		if (consumeCommerceRefreshFlag()) {
			window.setTimeout(refreshCommercePanels, 400);
			window.setTimeout(refreshCommercePanels, 1200);
		}
	}
	window.mkOppCommerceOnSummaryLoad = mkOppCommerceOnSummaryLoad;

	function fetchPurchases() {
		var id = recordId();
		if (!id || typeof app === 'undefined' || !app.request) {
			return Promise.resolve([]);
		}
		return app.request
			.post({
				data: {
					module: 'Potentials',
					action: 'CommerceApi',
					mode: 'get',
					record: id,
				},
			})
			.then(function (err, res) {
				if (err || !res || res.success === false) {
					return [];
				}
				return res.purchases || [];
			});
	}

	function fetchServiceContracts() {
		var id = recordId();
		if (!id || typeof app === 'undefined' || !app.request) {
			return Promise.resolve([]);
		}
		return app.request
			.post({
				data: {
					module: 'Potentials',
					action: 'CommerceApi',
					mode: 'get_service_contracts',
					record: id,
				},
			})
			.then(function (err, res) {
				if (err || !res || res.success === false) {
					return [];
				}
				return res.contracts || [];
			});
	}

	function bindLinkOrder() {
		var btn = byId('mk-opp-link-order');
		if (!btn || btn.getAttribute('data-mk-link-bound') === '1' || typeof app === 'undefined' || !app.request) {
			return;
		}
		btn.setAttribute('data-mk-link-bound', '1');
		btn.addEventListener('click', function () {
			var id = recordId();
			var q = window.prompt('Tìm đơn hàng (mã SO hoặc tên):', '');
			if (q === null) return;
			app.request
				.post({
					data: {
						module: 'Potentials',
						action: 'CommerceApi',
						mode: 'search_orders',
						q: q,
					},
				})
				.then(function (err, res) {
					if (err) {
						window.alert(typeof err === 'string' ? err : err.message || 'Lỗi tìm đơn');
						return;
					}
					var orders = (res && res.orders) || [];
					if (!orders.length) {
						window.alert('Không tìm thấy Sales Order. Tạo SO trước rồi liên kết.');
						return;
					}
					var lines = orders
						.map(function (o, i) {
							return i + 1 + '. ' + o.label + ' (' + formatVnd(o.total) + ')';
						})
						.join('\n');
					var pick = window.prompt('Chọn số thứ tự đơn:\n' + lines, '1');
					if (pick === null) return;
					var idx = parseInt(pick, 10) - 1;
					if (isNaN(idx) || idx < 0 || idx >= orders.length) {
						window.alert('Số không hợp lệ.');
						return;
					}
					app.request
						.post({
							data: {
								module: 'Potentials',
								action: 'CommerceApi',
								mode: 'link_order',
								record: id,
								salesorder_id: orders[idx].id,
							},
						})
						.then(function (linkErr, linkRes) {
							if (linkErr) {
								window.alert(typeof linkErr === 'string' ? linkErr : linkErr.message || 'Liên kết thất bại');
								return;
							}
							renderPanels((linkRes && linkRes.purchases) || []);
							if (app.helper && app.helper.showSuccessNotification) {
								app.helper.showSuccessNotification({ message: 'Đã liên kết đơn ' + orders[idx].orderNo });
							}
						});
				});
		});
	}

	function refreshPurchaseHistory() {
		return fetchPurchases().then(renderPanels);
	}

	function refreshServiceContracts() {
		return fetchServiceContracts().then(renderServiceContracts);
	}

	function refreshCommercePanels() {
		return Promise.all([refreshPurchaseHistory(), refreshServiceContracts()]);
	}

	function parseRequestMeta(data) {
		var module = '';
		var action = '';
		var relatedModule = '';
		var mode = '';
		if (typeof FormData !== 'undefined' && data instanceof FormData) {
			module = String(data.get('module') || '');
			action = String(data.get('action') || '');
			relatedModule = String(data.get('related_module') || data.get('returnmodule') || '');
			mode = String(data.get('mode') || '');
		} else if (typeof data === 'object' && data && !Array.isArray(data)) {
			module = String(data.module || '');
			action = String(data.action || '');
			relatedModule = String(data.related_module || data.returnmodule || '');
			mode = String(data.mode || '');
		} else if (typeof data === 'string') {
			data.split('&').forEach(function (pair) {
				if (!pair) return;
				var parts = pair.split('=');
				var key = decodeURIComponent(parts[0] || '');
				var val = decodeURIComponent((parts[1] || '').replace(/\+/g, ' '));
				if (key === 'module') module = val;
				if (key === 'action') action = val;
				if (key === 'related_module' || key === 'returnmodule') relatedModule = val;
				if (key === 'mode') mode = val;
			});
		}
		return { module: module, action: action, relatedModule: relatedModule, mode: mode };
	}

	function shouldRefreshFromRequestMeta(meta) {
		if (!meta) return false;
		if (meta.module === 'SalesOrder' && /^Save$/i.test(meta.action)) {
			return true;
		}
		if (meta.module === 'Potentials' && /^CommerceApi$/i.test(meta.action)) {
			var mode = String(meta.mode || '').toLowerCase();
			// Read-only / history modes must never re-trigger commerce refresh loops.
			if (
				mode === 'get' ||
				mode === 'get_service_contracts' ||
				mode === 'search_orders' ||
				mode === 'interaction_log' ||
				mode === 'log_call'
			) {
				return false;
			}
			return true;
		}
		if (meta.module === 'Potentials' && /^RelationAjax$/i.test(meta.action)) {
			if (/^SalesOrder$/i.test(meta.relatedModule)) return true;
			if (/addRelation/i.test(meta.mode) && /SalesOrder/i.test(meta.relatedModule)) return true;
		}
		return false;
	}

	function shouldRefreshFromAjax(settings) {
		if (!settings || settings.data == null) return false;
		return shouldRefreshFromRequestMeta(parseRequestMeta(settings.data));
	}

	function isOpportunityDetailPage() {
		var body = document.body;
		return (
			body &&
			body.getAttribute('data-module') === 'Potentials' &&
			body.getAttribute('data-view') === 'Detail'
		);
	}

	function scheduleCommerceRefresh() {
		if (!isOpportunityDetailPage()) return;
		window.setTimeout(refreshCommercePanels, 500);
	}

	function wrapAppRequestPost() {
		if (typeof app === 'undefined' || !app.request || app.request.__mkOppCommercePostWrapped) {
			return;
		}
		app.request.__mkOppCommercePostWrapped = true;
		var originalPost = app.request.post;
		app.request.post = function (params) {
			var meta = parseRequestMeta(params && params.data);
			var promise = originalPost.call(app.request, params);
			promise.then(function (err) {
				if (!err && shouldRefreshFromRequestMeta(meta)) {
					scheduleCommerceRefresh();
				}
			});
			return promise;
		};
	}

	function bindRefreshEvents() {
		wrapAppRequestPost();
		if (typeof app !== 'undefined' && app.event && typeof app.event.on === 'function') {
			app.event.on('post.summaryview.load', mkOppCommerceOnSummaryLoad);
			app.event.on('post.detailedview.load', refreshCommercePanels);
			app.event.on('post.QuickCreateForm.save', scheduleCommerceRefresh);
			app.event.on('post.relatedListLoad.click', function (event, container) {
				var relatedModule = '';
				if (typeof jQuery !== 'undefined' && container) {
					relatedModule = jQuery(container).closest('.relatedContainer').find('input.relatedModuleName').val() || '';
				}
				if (!relatedModule || relatedModule === 'SalesOrder') {
					scheduleCommerceRefresh();
					try {
						var id = recordId();
						if (id) sessionStorage.setItem('mkOppCommerceRefresh', String(id));
					} catch (e) {}
				}
			});
			app.event.on('post.RecordList.click', function () {
				scheduleCommerceRefresh();
				try {
					var rid = recordId();
					if (rid) sessionStorage.setItem('mkOppCommerceRefresh', String(rid));
				} catch (e) {}
			});
			app.event.on('post.overLayEditView.loaded.mkOppCommerce', function () {
				if (typeof jQuery === 'undefined') return;
				jQuery('#EditView')
					.off('submit.mkOppCommerceFlag')
					.on('submit.mkOppCommerceFlag', function () {
						try {
							var rid = recordId();
							if (rid) sessionStorage.setItem('mkOppCommerceRefresh', String(rid));
						} catch (e) {}
					});
			});
		}
		window.addEventListener('pageshow', function () {
			if (!isOpportunityDetailPage()) return;
			if (byId('mk-opp-section-purchases')) {
				mkOppCommerceOnSummaryLoad();
			}
		});
		document.addEventListener('visibilitychange', function () {
			if (!document.hidden && isOpportunityDetailPage() && byId('mk-opp-section-purchases')) {
				scheduleCommerceRefresh();
			}
		});
		if (typeof jQuery !== 'undefined') {
			jQuery(document).ajaxSuccess(function (event, xhr, settings) {
				if (!isOpportunityDetailPage()) return;
				if (shouldRefreshFromAjax(settings)) {
					scheduleCommerceRefresh();
					try {
						var id = recordId();
						if (id) sessionStorage.setItem('mkOppCommerceRefresh', String(id));
					} catch (e) {}
				}
			});
			jQuery(document).on('pjax:success.mkOppCommerce', function () {
				if (!isOpportunityDetailPage()) return;
				window.setTimeout(function () {
					if (byId('mk-opp-section-purchases')) {
						mkOppCommerceOnSummaryLoad();
					}
				}, 200);
			});
		}
	}

	var refreshEventsBound = false;
	function bindRefreshEventsOnce() {
		if (refreshEventsBound) return;
		refreshEventsBound = true;
		bindRefreshEvents();
	}

	function init() {
		bindRefreshEventsOnce();
		if (!byId('mk-opp-section-purchases') && !byId('mk-opp-section-service-contracts')) {
			return;
		}
		mkOppCommerceOnSummaryLoad();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
