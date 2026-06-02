/**
 * Shared demo lead records for Leads SALES UI (List / Detail / Edit).
 */
(function (global) {
	'use strict';

	var DEMO_PURCHASES_L004 = [
		{ product: 'Syrup Caramel B-ACE 750ml', qty: 12, value: 1440000, date: '12/4/2026' },
		{ product: 'Cà phê hạt Arabica 1kg', qty: 8, value: 3040000, date: '12/5/2026' },
	];

	var DEMO_ACTIVITY_LOG_L004 = [
		{ type: 'call', label: 'Cuộc gọi', time: '11:25 21/05/2026', text: 'Gọi tư vấn lần đầu.' },
		{ type: 'note', label: 'Ghi chú', time: '11:25 19/05/2026', text: 'Khách hỏi lịch học buổi tối.' },
		{ type: 'meeting', label: 'Cuộc họp', time: '11:25 16/05/2026', text: 'Hẹn gặp tại văn phòng để demo.' },
	];

	var LEADS = [
		{
			id: 'L004',
			name: 'Phạm Quốc Dũng',
			company: 'CÔNG TY TNHH ĐÀO TẠO 751',
			phone: '0978 111 222',
			email: 'dung.pham@example.com',
			leadsource: 'Zalo',
			leadstatus: 'New Purchase',
			owner: 'Cao Thanh Nam',
			value: 48000000,
			closeDate: '22-05-2026',
			tags: ['zalo', 'da_hoc', 'nhuong_quyen'],
			edit: {
				customerType: 'company',
				leadSource: 'zalo',
				intent: 'da_hoc',
				entry: 'pcth',
				entryBranch: 'nhuong_quyen',
				purchaseStatus: 'mua_lan_dau',
				tier: 'vang',
				notes: 'Khách quan tâm gói nhượng quyền khu vực.',
			},
			comments: [
				{ text: 'Khách quan tâm gói nhượng quyền khu vực.', when: '20/05/2026' },
			],
			activities: [{ subject: 'Gọi lại xác nhận hợp đồng', when: '25/05/2026' }],
			activityLog: DEMO_ACTIVITY_LOG_L004.slice(),
			purchases: DEMO_PURCHASES_L004.slice(),
			badges: {
				contacts: 1,
				comments: 1,
				'activity-log': 3,
				purchases: 2,
				calendar: 1,
				tasks: 0,
				documents: 0,
				emails: 0,
			},
		},
		{
			id: 'L005',
			name: 'Cao Thanh Tùng',
			company: 'PCTH Marketing Co.',
			phone: '0904 555 013',
			email: 'tung.cao@example.com',
			leadsource: 'Website',
			leadstatus: 'Repeat Purchase',
			owner: 'Linh',
			value: 28400000,
			closeDate: '30-06-2026',
			tags: ['pcth', 'mkt'],
			edit: {
				customerType: 'company',
				leadSource: 'website',
				intent: 'nguyen_lieu_chuoi',
				entry: 'pcth',
				entryBranch: 'mkt',
				purchaseStatus: 'mua_lai',
				tier: 'bac',
				notes: 'Quan tâm nhánh Marketing.',
			},
			comments: [],
			activities: [],
			activityLog: [
				{ type: 'call', label: 'CALL', time: '10:00 20/05/2026', text: 'Follow-up sau demo.' },
				{ type: 'note', label: 'NOTE', time: '09:30 18/05/2026', text: 'Quan tâm nhánh Marketing.' },
			],
			purchases: [{ product: 'Gói PCTH Advanced', qty: 1, value: 28400000, date: '18/5/2026' }],
			badges: {
				contacts: 1,
				comments: 0,
				'activity-log': 2,
				purchases: 1,
				calendar: 0,
				tasks: 0,
				documents: 0,
				emails: 0,
			},
		},
		{
			id: 'L001',
			name: 'Nguyễn Văn An',
			company: '',
			phone: '0901 234 567',
			email: 'an.nguyen@example.com',
			leadsource: 'Facebook',
			leadstatus: 'New Purchase',
			owner: 'Linh',
			value: 12000000,
			closeDate: '15-06-2026',
			tags: ['facebook', 'mien_phi'],
			edit: {
				customerType: 'individual',
				leadSource: 'facebook',
				intent: 'chua_hoc',
				entry: 'mien_phi_online',
				entryBranch: null,
				purchaseStatus: 'mua_lan_dau',
				tier: 'dong',
				notes: 'Đăng ký lớp miễn phí online.',
			},
			comments: [],
			activities: [],
			activityLog: [
				{ type: 'note', label: 'NOTE', time: '14:00 19/05/2026', text: 'Đăng ký lớp miễn phí online.' },
			],
			purchases: [],
			badges: {
				contacts: 0,
				comments: 0,
				'activity-log': 1,
				purchases: 0,
				calendar: 0,
				tasks: 0,
				documents: 0,
				emails: 0,
			},
		},
	];

	function resolveLead(recordId) {
		var id = recordId == null ? '' : String(recordId).trim();
		if (!id) {
			return LEADS[0];
		}
		for (var i = 0; i < LEADS.length; i++) {
			if (LEADS[i].id === id) {
				return LEADS[i];
			}
		}
		return LEADS[0];
	}

	global.MK_LEADS_DEMO = {
		LEADS: LEADS,
		resolveLead: resolveLead,
	};
})(typeof window !== 'undefined' ? window : this);
