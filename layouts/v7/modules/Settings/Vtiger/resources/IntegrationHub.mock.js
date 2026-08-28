/**
 * Integration Hub — UI mock data (Phase UI).
 * TODO(backend): replace with IntegrationsAjax / NkApiConnection APIs.
 */
(function (global) {
	'use strict';

	var STATUS_LABELS = {
		active: 'Đang hoạt động',
		warning: 'Cảnh báo',
		error: 'Lỗi kết nối',
		inactive: 'Chưa kết nối'
	};

	var INTEG_URL = 'index.php?module=Vtiger&parent=Settings&view=Integrations';

	function cfgUrl(code) {
		return INTEG_URL + '#code=' + code;
	}

	var CONNECTIONS = [
		{
			code: 'google_sheet',
			label: 'Google Sheet',
			subtitle: 'Google Sheets API',
			icon: 'google_sheet',
			enabled: true,
			hub_status: 'active',
			hub_status_label: STATUS_LABELS.active,
			last_sync_hint: '5 phút trước',
			base_url: 'https://sheets.googleapis.com/v4',
			api_key_masked: '••••••••••••',
			webhook_url: '—',
			two_way: false,
			notes: 'Đồng bộ Lead từ Google Sheet.',
			configure_url: cfgUrl('google_sheet'),
			test_result: 'success'
		},
		{
			code: 'zalo_oa',
			label: 'Zalo OA',
			subtitle: 'Zalo Official Account',
			icon: 'zalo_oa',
			enabled: true,
			hub_status: 'warning',
			hub_status_label: STATUS_LABELS.warning,
			last_sync_hint: '1 giờ trước',
			base_url: 'https://openapi.zalo.me/v2.0',
			api_key_masked: '••••••••••••',
			webhook_url: 'https://crm.example.com/webhook/zalo',
			two_way: true,
			notes: 'Token OAuth cần gia hạn định kỳ.',
			configure_url: cfgUrl('zalo_oa'),
			test_result: 'warning'
		},
		{
			code: 'edubit',
			label: 'Edubit',
			subtitle: 'Edubit LMS API',
			icon: 'edubit',
			enabled: true,
			hub_status: 'active',
			hub_status_label: STATUS_LABELS.active,
			last_sync_hint: '20 phút trước',
			base_url: 'https://api.edubit.vn/v1',
			api_key_masked: '••••••••••••',
			webhook_url: '—',
			two_way: false,
			notes: 'Đồng bộ học viên và khóa học.',
			configure_url: cfgUrl('edubit'),
			test_result: 'success'
		},
		{
			code: 'misa',
			label: 'MISA',
			subtitle: 'MISA AMIS API',
			icon: 'misa',
			enabled: true,
			hub_status: 'active',
			hub_status_label: STATUS_LABELS.active,
			last_sync_hint: '15 phút trước',
			base_url: 'https://amisapp.misa.vn/api',
			api_key_masked: '••••••••••••',
			webhook_url: '—',
			two_way: false,
			notes: 'Chuyển hóa đơn sang MISA.',
			configure_url: cfgUrl('misa'),
			test_result: 'success'
		},
		{
			code: 'ecommerce',
			label: 'Website',
			subtitle: 'Website / E-commerce',
			icon: 'website',
			enabled: true,
			hub_status: 'warning',
			hub_status_label: STATUS_LABELS.warning,
			last_sync_hint: '30 phút trước',
			base_url: 'https://shop.example.com/api',
			api_key_masked: '••••••••••••',
			webhook_url: 'https://crm.example.com/webhook/orders',
			two_way: true,
			notes: 'Import đơn hàng từ website.',
			configure_url: cfgUrl('ecommerce'),
			test_result: 'warning'
		},
		{
			code: 'shopee_express',
			label: 'Shopee Express',
			subtitle: 'Shopee Logistics API',
			icon: 'shopee',
			enabled: true,
			hub_status: 'active',
			hub_status_label: STATUS_LABELS.active,
			last_sync_hint: '45 phút trước',
			base_url: 'https://partner.shopee.vn/api/v2',
			api_key_masked: '••••••••••••',
			webhook_url: '—',
			two_way: false,
			notes: 'Cập nhật trạng thái vận đơn.',
			configure_url: cfgUrl('shopee_express'),
			test_result: 'success'
		},
		{
			code: 'ghtk',
			label: 'GHTK',
			subtitle: 'Giao Hàng Tiết Kiệm',
			icon: 'ghtk',
			enabled: false,
			hub_status: 'error',
			hub_status_label: STATUS_LABELS.error,
			last_sync_hint: '2 giờ trước',
			base_url: 'https://services.giaohangtietkiem.vn',
			api_key_masked: '••••••••••••',
			webhook_url: '—',
			two_way: false,
			notes: 'Lỗi timeout khi gọi API tạo vận đơn.',
			configure_url: cfgUrl('ghtk'),
			test_result: 'error'
		},
		{
			code: 'email_smtp',
			label: 'Email / SMTP',
			subtitle: 'Outgoing mail server',
			icon: 'email',
			enabled: true,
			hub_status: 'active',
			hub_status_label: STATUS_LABELS.active,
			last_sync_hint: '10 phút trước',
			base_url: 'smtp.gmail.com:587',
			api_key_masked: '••••••••••••',
			webhook_url: '—',
			two_way: false,
			notes: 'Dùng cấu hình máy chủ gửi mail hệ thống.',
			configure_url: 'index.php?module=Vtiger&parent=Settings&view=OutgoingServerDetail',
			test_result: 'success'
		}
	];

	function countStatus(status) {
		var n = 0;
		for (var i = 0; i < CONNECTIONS.length; i++) {
			if (CONNECTIONS[i].hub_status === status) {
				n += 1;
			}
		}
		return n;
	}

	function pct(n) {
		return CONNECTIONS.length ? Math.round((n / CONNECTIONS.length) * 100) : 0;
	}

	var active = countStatus('active');
	var warning = countStatus('warning');
	var error = countStatus('error');

	global.NK_INTEGRATION_HUB_MOCK = {
		useMock: true,
		summary: {
			total: CONNECTIONS.length,
			active: active,
			active_pct: pct(active),
			warning: warning,
			warning_pct: pct(warning),
			error: error,
			error_pct: pct(error),
			synced_today: 1280
		},
		connections: CONNECTIONS,
		activity: [
			{ type: 'success', title: 'Đồng bộ Google Sheet thành công', detail: '42 bản ghi Lead', time: '2 phút trước' },
			{ type: 'success', title: 'Đồng bộ MISA thành công', detail: '18 hóa đơn', time: '15 phút trước' },
			{ type: 'warning', title: 'Zalo OA — token sắp hết hạn', detail: 'Gia hạn trong 3 ngày', time: '1 giờ trước' },
			{ type: 'error', title: 'GHTK — lỗi kết nối API', detail: 'HTTP 503 timeout', time: '2 giờ trước' },
			{ type: 'success', title: 'Website — đơn hàng mới', detail: '7 đơn đã import', time: '3 giờ trước' },
			{ type: 'success', title: 'Email SMTP — gửi thông báo', detail: '124 email đã gửi', time: '5 giờ trước' },
			{ type: 'warning', title: 'Edubit — đồng bộ chậm', detail: 'Hàng đợi 120 bản ghi', time: '6 giờ trước' },
			{ type: 'success', title: 'Shopee Express — cập nhật vận đơn', detail: '33 vận đơn', time: '8 giờ trước' },
			{ type: 'success', title: 'CRM → MISA đồng bộ xong', detail: '12 chứng từ', time: '9 giờ trước' },
			{ type: 'warning', title: 'Website webhook retry', detail: '3 lần thử lại', time: '11 giờ trước' }
		],
		addCatalog: [
			{ code: 'google_sheet', label: 'Google Sheet', icon: 'google_sheet' },
			{ code: 'zalo_oa', label: 'Zalo OA', icon: 'zalo_oa' },
			{ code: 'misa', label: 'MISA', icon: 'misa' },
			{ code: 'ecommerce', label: 'Website', icon: 'website' },
			{ code: 'edubit', label: 'Edubit', icon: 'edubit' },
			{ code: 'shopee_express', label: 'Shopee Express', icon: 'shopee' },
			{ code: 'ghtk', label: 'GHTK', icon: 'ghtk' },
			{ code: 'email_smtp', label: 'Email / SMTP', icon: 'email' }
		]
	};
})(typeof window !== 'undefined' ? window : this);
