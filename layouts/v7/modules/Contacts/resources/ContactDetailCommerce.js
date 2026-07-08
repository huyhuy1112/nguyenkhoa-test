/**
 * Contacts Detail — Lịch sử mua hàng (Sales Orders) for Customer BA workflow.
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
		var el = byId('recordId') || document.querySelector('input[name="record_id"]');
		return el && el.value ? String(el.value) : '';
	}

	function orderTotal(orders) {
		var sum = 0;
		for (var i = 0; i < orders.length; i++) {
			sum += orders[i].value || 0;
		}
		return sum;
	}

	function groupOrdersLocal(purchases) {
		var map = {};
		(purchases || []).forEach(function (p) {
			var key = p.crmid || p.orderId || p.orderName || 'order';
			if (!map[key]) {
				map[key] = {
					orderId: p.orderId,
					orderName: p.orderName || p.orderId || 'Đơn hàng',
					qty: 0,
					value: 0,
					date: p.date,
					dateTs: 0,
					crmid: p.crmid,
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

	function ordersInLastDaysLocal(orders, days) {
		var cutoff = Date.now() - days * 24 * 60 * 60 * 1000;
		return (orders || []).filter(function (o) {
			var dt = parsePurchaseDate(o.date);
			return dt && dt.getTime() >= cutoff;
		});
	}

	function recentOrderValueLocal(orders) {
		if (!orders || !orders.length) return 0;
		return orders[0].value || 0;
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

	function orderTableHtml(orders, recentValue, emptyMsg) {
		if (!orders.length) {
			return '<p class="mk-contact-purchase__empty">' + esc(emptyMsg || 'Chưa có đơn mua hàng.') + '</p>';
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
		if (recentValue) {
			recentRow =
				'<tr><td colspan="2" class="mk-contact-purchase__total-label">Đơn gần nhất:</td>' +
				'<td class="mk-contact-purchase__total-value" colspan="2">' +
				esc(formatVnd(recentValue)) +
				'</td></tr>';
		}
		return (
			'<table class="mk-contact-purchase__table">' +
			'<thead><tr><th>Đơn hàng</th><th>SL</th><th>Giá trị</th><th>Ngày</th></tr></thead>' +
			'<tbody>' +
			rows +
			'</tbody>' +
			'<tfoot>' +
			recentRow +
			'<tr><td colspan="2" class="mk-contact-purchase__total-label">Tổng:</td>' +
			'<td class="mk-contact-purchase__total-value" colspan="2">' +
			esc(formatVnd(total)) +
			'</td></tr></tfoot></table>'
		);
	}

	function productTableHtml(products, emptyMsg) {
		if (!products.length) {
			return '<p class="mk-contact-purchase__empty">' + esc(emptyMsg || 'Chưa có sản phẩm mua hàng.') + '</p>';
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
			'<table class="mk-contact-purchase__table">' +
			'<thead><tr><th>Sản phẩm</th><th>SL</th><th>Giá trị</th><th>Ngày</th></tr></thead>' +
			'<tbody>' +
			rows +
			'</tbody>' +
			'<tfoot>' +
			'<tr><td colspan="2" class="mk-contact-purchase__total-label">Tổng SL:</td>' +
			'<td class="mk-contact-purchase__total-value" colspan="2">' +
			esc(totalQty) +
			'</td></tr>' +
			'<tr><td colspan="2" class="mk-contact-purchase__total-label">Tổng giá trị:</td>' +
			'<td class="mk-contact-purchase__total-value" colspan="2">' +
			esc(formatVnd(totalValue)) +
			'</td></tr></tfoot></table>'
		);
	}

	function renderPanels(purchases) {
		var title = byId('mk-contact-purchase-title');
		var ordersHost = byId('mk-contact-commerce-orders-month');
		var productsHost = byId('mk-contact-commerce-products-total');
		var logic = window.LeadsLeadsLogic;
		var allItems = purchases || [];
		var allOrders = logic && logic.groupOrders ? logic.groupOrders(allItems) : groupOrdersLocal(allItems);
		var monthOrders =
			logic && logic.ordersInLastDays
				? logic.ordersInLastDays({ purchases: allItems }, 30)
				: ordersInLastDaysLocal(allOrders, 30);
		var monthEmptyMsg = 'Không có đơn trong 30 ngày gần nhất.';
		if (!monthOrders.length && allOrders.length) {
			monthOrders = allOrders;
			monthEmptyMsg = 'Chưa có đơn mua hàng.';
		}
		var allProducts = groupProducts(allItems);
		var recentValue =
			logic && logic.recentOrderValue
				? logic.recentOrderValue({ purchases: allItems })
				: recentOrderValueLocal(allOrders);

		if (title) {
			title.textContent = 'Lịch sử mua hàng (' + allOrders.length + ')';
		}
		if (ordersHost) {
			ordersHost.innerHTML = orderTableHtml(monthOrders, recentValue, monthEmptyMsg);
		}
		if (productsHost) {
			productsHost.innerHTML = productTableHtml(allProducts, 'Chưa có sản phẩm mua hàng.');
		}
	}

	function activateCommerceSubTab(key) {
		var root = byId('mk-contact-section-purchases');
		if (!root) return;
		var subKey = key || 'orders-month';
		root.querySelectorAll('[data-mk-contact-commerce-tab]').forEach(function (btn) {
			var active = btn.getAttribute('data-mk-contact-commerce-tab') === subKey;
			btn.classList.toggle('is-active', active);
			btn.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		root.querySelectorAll('[data-mk-contact-commerce-panel]').forEach(function (panel) {
			panel.classList.toggle('hide', panel.getAttribute('data-mk-contact-commerce-panel') !== subKey);
		});
	}

	function bindCommerceSubTabs() {
		if (document.documentElement.getAttribute('data-mk-contact-commerce-tabs') === '1') {
			return;
		}
		document.documentElement.setAttribute('data-mk-contact-commerce-tabs', '1');
		document.addEventListener('click', function (e) {
			var btn = e.target && e.target.closest ? e.target.closest('[data-mk-contact-commerce-tab]') : null;
			if (!btn || !byId('mk-contact-section-purchases') || !byId('mk-contact-section-purchases').contains(btn)) {
				return;
			}
			activateCommerceSubTab(btn.getAttribute('data-mk-contact-commerce-tab'));
		});
		activateCommerceSubTab('orders-month');
	}

	function fetchPurchases() {
		var id = recordId();
		if (!id || typeof app === 'undefined' || !app.request) {
			return Promise.resolve([]);
		}
		return app.request
			.post({
				data: {
					module: 'Contacts',
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

	function bindLinkOrder() {
		var btn = byId('mk-contact-link-order');
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
						module: 'Contacts',
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
								module: 'Contacts',
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
								app.helper.showSuccessNotification({ message: 'Đã liên kết đơn ' + (orders[idx].orderNo || orders[idx].label || '') });
							}
						});
				});
		});
	}

	function refreshPurchaseHistory() {
		var ordersHost = byId('mk-contact-commerce-orders-month');
		if (ordersHost && !ordersHost.getAttribute('data-mk-loading')) {
			ordersHost.setAttribute('data-mk-loading', '1');
			ordersHost.innerHTML = '<p class="mk-contact-purchase__empty">Đang tải lịch sử mua hàng…</p>';
		}
		return fetchPurchases().then(function (purchases) {
			if (ordersHost) {
				ordersHost.removeAttribute('data-mk-loading');
			}
			renderPanels(purchases);
		});
	}

	function mkContactCommerceOnSummaryLoad() {
		if (!byId('mk-contact-section-purchases')) {
			return;
		}
		bindCommerceSubTabs();
		bindLinkOrder();
		window.mkContactRefreshPurchaseHistory = refreshPurchaseHistory;
		refreshPurchaseHistory();
	}
	window.mkContactCommerceOnSummaryLoad = mkContactCommerceOnSummaryLoad;

	function boot() {
		var body = document.body;
		if (
			!body ||
			body.getAttribute('data-module') !== 'Contacts' ||
			body.getAttribute('data-view') !== 'Detail'
		) {
			return;
		}
		mkContactCommerceOnSummaryLoad();
		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.summaryview.load', mkContactCommerceOnSummaryLoad);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
