/**
 * Opportunity Detail — Lịch sử mua hàng (SALES UI).
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

		if (title) {
			title.textContent = 'Lịch sử mua hàng (' + allOrders.length + ')';
		}
		if (ordersHost) {
			ordersHost.innerHTML = orderTableHtml(
				monthOrders,
				metrics,
				'Không có đơn trong 30 ngày gần nhất.'
			);
		}
		if (productsHost) {
			productsHost.innerHTML = orderTableHtml(allOrders, metrics, 'Chưa có đơn mua hàng.');
		}
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
		var root = byId('mk-opp-section-purchases');
		if (!root) return;
		root.querySelectorAll('[data-mk-commerce-tab]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				activateCommerceSubTab(btn.getAttribute('data-mk-commerce-tab'));
			});
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

	function bindLinkOrder() {
		var btn = byId('mk-opp-link-order');
		if (!btn || typeof app === 'undefined' || !app.request) return;
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

	function init() {
		if (!byId('mk-opp-section-purchases')) return;
		bindCommerceSubTabs();
		bindLinkOrder();
		fetchPurchases().then(renderPanels);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
