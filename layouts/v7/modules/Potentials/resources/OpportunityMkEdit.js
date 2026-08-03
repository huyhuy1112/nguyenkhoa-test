/**
 * Potentials Create/Edit (SALES) — slim form matched to list columns.
 * Hides stock CRM noise; keeps Contact + Opp name + owner + region/address/tags.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260803_opp_ui_polish2';
	var selectedTags = {};
	var meta = { tags: [], mk_region: '', mk_address: '' };
	var leadSearchCache = null;
	var LEAD_SOURCE_TAGS = ['facebook', 'tiktok', 'website', 'zalo', 'hotline', 'other', 'other_source', 'ladipage_fb'];
	var LEAD_CUSTOMER_TAGS = ['individual', 'company', 'ca_nhan', 'co_quan', 'chuan_bi_mo', 'gia_dinh'];
	var LEAD_REGION_LABELS = {
		kv1: 'Khu vực 1',
		kv2: 'Khu vực 2',
		kv3: 'Khu vực 3'
	};

	/** Fallback if PotentialsLovableRef fails to load */
	var FALLBACK_TAG_CATALOG = [
		{
			id: 'source',
			label: 'Nguồn data',
			tags: [
				{ key: 'facebook', label: 'Facebook', cls: 'mk-tag--facebook' },
				{ key: 'tiktok', label: 'TikTok', cls: 'mk-tag--tiktok' },
				{ key: 'zalo', label: 'Zalo', cls: 'mk-tag--zalo' },
				{ key: 'website', label: 'Website', cls: 'mk-tag--website' },
				{ key: 'hotline', label: 'Hotline', cls: 'mk-tag--hotline' },
				{ key: 'other', label: 'Khác', cls: 'mk-tag--other' }
			]
		},
		{
			id: 'customer',
			label: 'Dạng khách hàng',
			tags: [
				{ key: 'co_quan', label: 'Đã có quán', cls: 'mk-tag--co-quan' },
				{ key: 'chuan_bi_mo', label: 'CH chuẩn bị mở quán', cls: 'mk-tag--chuan-bi-mo' },
				{ key: 'gia_dinh', label: 'Gia đình', cls: 'mk-tag--gia-dinh' }
			]
		},
		{
			id: 'class',
			label: 'Tag lớp học',
			tags: [
				{ key: 'da_tg_free', label: 'Đã TG FREE', cls: 'mk-tag--da-tg-free' },
				{ key: 'da_tg_fb1', label: 'Đã TG F&B1', cls: 'mk-tag--da-tg-fb1' },
				{ key: 'thu_3', label: 'THỨ 3', cls: 'mk-tag--thu-3' },
				{ key: 'mien_phi_online', label: 'Miễn phí Online', cls: 'mk-tag--free-online' },
				{ key: 'mien_phi_offline', label: 'Miễn phí Offline', cls: 'mk-tag--free-offline' },
				{ key: 'pcth', label: 'PCTH', cls: 'mk-tag--pcth' }
			]
		},
		{
			id: 'material',
			label: 'Tag nguyên liệu',
			tags: [
				{ key: 'tiem_nang', label: 'Tiềm năng', cls: 'mk-tag--tiem-nang' },
				{ key: 'mua_lan_dau', label: 'Mua lần đầu', cls: 'mk-tag--mua-lan-dau' },
				{ key: 'mua_lai', label: 'Mua lại', cls: 'mk-tag--mua-lai' },
				{ key: 'dang_tu_van', label: 'Đang tư vấn', cls: 'mk-tag--dang-tu-van' },
				{ key: 'kh_can_nhac', label: 'KH Cân Nhắc', cls: 'mk-tag--kh-can-nhac' },
				{ key: 'khong_mua', label: 'Không mua', cls: 'mk-tag--khong-mua' }
			]
		},
		{
			id: 'franchise',
			label: 'Tag nhượng quyền',
			tags: [
				{ key: 'nhuong_quyen', label: 'Nhượng quyền', cls: 'mk-tag--nhuong-quyen' },
				{ key: 'da_ky_quy', label: 'Đã Ký Quỹ', cls: 'mk-tag--da-ky-quy' }
			]
		},
		{
			id: 'confirm',
			label: 'Xác nhận tham gia',
			tags: [
				{ key: 'xac_nhan_tham_gia', label: 'Xác nhận tham gia', cls: 'mk-tag--xac-nhan' },
				{ key: 'khong_xac_nhan_tham_gia', label: 'Không tham gia', cls: 'mk-tag--khong-xac-nhan' }
			]
		},
		{
			id: 'tier',
			label: 'Hạng khách',
			tags: [
				{ key: 'vang', label: 'Vàng', cls: 'mk-tag--vang' },
				{ key: 'bac', label: 'Bạc', cls: 'mk-tag--bac' },
				{ key: 'dong', label: 'Đồng', cls: 'mk-tag--dong' }
			]
		}
	];

	/** Stock fields removed from Create/Edit per product request */
	var HIDE_FIELD_NAMES = [
		'amount',
		'opportunity_type',
		'closingdate',
		'leadsource',
		'nextstep',
		'sales_stage',
		'campaignid',
		'probability',
		'forecast_amount',
		'forecastamount',
		'order_category',
		'related_to',
		'contact_id',
		'cf_859',
		'projectname',
		'description'
	];

	var HIDE_LABEL_NEEDLES = [
		'giá trị dự kiến',
		'loại cơ hội',
		'ngày dự kiến',
		'nguồn cơ hội',
		'bước tiếp theo',
		'nguồn chiến dịch',
		'xác suất',
		'dự đoán giá trị',
		'order category',
		'trạng thái cơ hội',
		'tên khách hàng',
		'tên liên hệ',
		'expected close',
		'sales stage',
		'lead source',
		'campaign',
		'probability',
		'forecast',
		'next step',
		'opportunity type',
		'amount'
	];

	var HIDE_BLOCK_KEYS = [
		'LBL_CUSTOM_INFORMATION',
		'LBL_DESCRIPTION_INFORMATION'
	];

	function ref() {
		return window.PotentialsLovableRef || null;
	}

	function isScoped() {
		return (
			$('body').data('module') === 'Potentials' &&
			$('body').data('view') === 'Edit' &&
			($('body').data('app') === 'SALES' || !$('body').data('app')) &&
			$('#mkOppCreateWorkspace').length > 0
		);
	}

	function $form() {
		return $('#mkOppFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function readBootMeta() {
		var el = document.getElementById('mkOppEditMetaBoot');
		if (!el) return { tags: [], mk_region: '', mk_address: '' };
		try {
			var parsed = JSON.parse(el.textContent || '{}');
			return {
				tags: Array.isArray(parsed.tags) ? parsed.tags : [],
				mk_region: parsed.mk_region || '',
				mk_address: parsed.mk_address || ''
			};
		} catch (e) {
			return { tags: [], mk_region: '', mk_address: '' };
		}
	}

	function normalizeTag(tag) {
		var r = ref();
		return r && r.normalizeTag ? r.normalizeTag(tag) : String(tag || '').toLowerCase();
	}

	function catalogForForm() {
		var r = ref();
		var catalog = r && r.getCreateTagCatalog ? r.getCreateTagCatalog() : [];
		if (!catalog || !catalog.length) {
			catalog = FALLBACK_TAG_CATALOG;
		}
		return catalog.filter(function (g) {
			return g && g.id !== 'area';
		});
	}

	function tagCls(key) {
		var r = ref();
		if (r && r.tagMeta) {
			var m = r.tagMeta(key);
			if (m && m.cls) return m.cls;
		}
		for (var i = 0; i < FALLBACK_TAG_CATALOG.length; i++) {
			var tags = FALLBACK_TAG_CATALOG[i].tags || [];
			for (var j = 0; j < tags.length; j++) {
				if (tags[j].key === key && tags[j].cls) return tags[j].cls;
			}
		}
		return 'mk-tag--other';
	}

	function ensureHidden($f, name, value) {
		var $input = $f.find('input[name="' + name + '"]');
		if (!$input.length) {
			$input = $('<input>', { type: 'hidden', name: name });
			$f.append($input);
		}
		$input.val(value == null ? '' : value);
		return $input;
	}

	function syncHidden() {
		var $f = $form();
		if (!$f.length) return;
		var keys = Object.keys(selectedTags).filter(function (k) {
			return selectedTags[k];
		});
		var region = ($('#mkOppRegion').val() || '').trim();
		var address = ($('#mkOppAddress').val() || '').trim();
		if (region) {
			['kv1', 'kv2', 'kv3'].forEach(function (k) {
				selectedTags[k] = false;
			});
			selectedTags[region] = true;
			keys = Object.keys(selectedTags).filter(function (k) {
				return selectedTags[k];
			});
		}
		ensureHidden($f, 'mk_tags', JSON.stringify(keys));
		ensureHidden($f, 'mk_region', region);
		ensureHidden($f, 'mk_address', address);
	}

	function softDefaultRequired($f) {
		var $close = $f.find('[name="closingdate"]');
		if ($close.length && !String($close.val() || '').trim()) {
			var d = new Date();
			d.setDate(d.getDate() + 30);
			var yyyy = d.getFullYear();
			var mm = ('0' + (d.getMonth() + 1)).slice(-2);
			var dd = ('0' + d.getDate()).slice(-2);
			$close.val(yyyy + '-' + mm + '-' + dd);
		}

		var $stage = $f.find('[name="sales_stage"]');
		if ($stage.length) {
			var cur = String($stage.val() || '').trim();
			if (!cur || cur === '--None--' || cur === 'None') {
				var $opt = $stage
					.find('option')
					.filter(function () {
						var v = String($(this).val() || '').trim();
						return v && v !== '--None--' && v !== 'None';
					})
					.first();
				if ($opt.length) $stage.val($opt.val());
			}
		}

		var $oc = $f.find('[name="order_category"]');
		if ($oc.length) {
			var ocv = String($oc.val() || '').trim();
			if (!ocv || ocv === '--None--' || ocv === 'None') {
				var $o = $oc
					.find('option')
					.filter(function () {
						var v = String($(this).val() || '').trim();
						return v && v !== '--None--' && v !== 'None';
					})
					.first();
				if ($o.length) $oc.val($o.val());
			}
		}

		HIDE_FIELD_NAMES.forEach(function (name) {
			$f.find('[name="' + name + '"]')
				.removeAttr('data-rule-required')
				.removeClass('validate[required]')
				.attr('aria-required', 'false');
		});
	}

	function hideFieldPair($input) {
		if (!$input || !$input.length) return;
		var $valueTd = $input.closest('td.fieldValue');
		if ($valueTd.length) {
			var $labelTd = $valueTd.prev('td.fieldLabel');
			$valueTd.addClass('mk-opp-hide-field').css('display', 'none');
			if ($labelTd.length) $labelTd.addClass('mk-opp-hide-field').css('display', 'none');
			var $row = $valueTd.closest('tr');
			if ($row.length) {
				var visibleCells = $row.children('td').not('.mk-opp-hide-field');
				if (!visibleCells.length) {
					$row.addClass('mk-opp-hide-field').css('display', 'none');
				}
			}
			return;
		}
		var $fallback = $input.closest('tr, .form-group');
		$fallback.addClass('mk-opp-hide-field').css('display', 'none');
	}

	function hideStockNoise() {
		var $f = $form();
		if (!$f.length) return;

		HIDE_FIELD_NAMES.forEach(function (name) {
			$f.find('[name="' + name + '"]').each(function () {
				hideFieldPair($(this));
			});
			$f.find('[name="' + name + '_display"]').each(function () {
				hideFieldPair($(this));
			});
		});

		// Fallback: hide by Vietnamese/English label text (layout may differ by locale)
		$f.find('td.fieldLabel').each(function () {
			var $label = $(this);
			var text = ($label.text() || '').replace(/\*/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
			if (!text) return;
			var hit = HIDE_LABEL_NEEDLES.some(function (n) {
				return text.indexOf(n) >= 0;
			});
			if (!hit) return;
			var $value = $label.next('td.fieldValue');
			$label.addClass('mk-opp-hide-field');
			if ($value.length) $value.addClass('mk-opp-hide-field');
			var $row = $label.closest('tr');
			if ($row.length && !$row.children('td').not('.mk-opp-hide-field').length) {
				$row.addClass('mk-opp-hide-field');
			}
		});

		HIDE_BLOCK_KEYS.forEach(function (key) {
			$f.find('.fieldBlockContainer[data-block="' + key + '"]').addClass('mk-opp-hide-block');
		});
		$f.find('.fieldBlockContainer').each(function () {
			var $b = $(this);
			var title = ($b.find('.fieldBlockHeader').first().text() || '').toLowerCase();
			if (
				title.indexOf('tùy chỉnh') >= 0 ||
				title.indexOf('custom') >= 0 ||
				title.indexOf('mô tả') >= 0 ||
				title.indexOf('description') >= 0
			) {
				$b.addClass('mk-opp-hide-block');
			}
		});

		softDefaultRequired($f);
		enhanceOppNameField($f);
	}

	function currentRecordId($f) {
		return String(($f.find('[name="record"]').val() || '').trim());
	}

	function isCreateMode($f) {
		var id = currentRecordId($f);
		return !id || id === '0';
	}

	function goToOppEdit(recordId) {
		var id = parseInt(recordId, 10) || 0;
		if (id <= 0) return;
		window.location.href =
			'index.php?module=Potentials&view=Edit&record=' + id + '&app=SALES';
	}

	function leadRef() {
		return window.LeadsLovableRef || null;
	}

	function leadTagLabel(key) {
		var lr = leadRef();
		if (lr && lr.tagMeta) {
			var m = lr.tagMeta(key);
			if (m && m.label) return m.label;
		}
		var r = ref();
		if (r && r.tagMeta) {
			var pm = r.tagMeta(key);
			if (pm && pm.label) return pm.label;
		}
		return key;
	}

	function leadTagCls(key) {
		var r = ref();
		if (r && r.tagMeta) {
			var m = r.tagMeta(key);
			if (m && m.cls) return m.cls;
		}
		var map = {
			facebook: 'mk-tag--facebook',
			tiktok: 'mk-tag--tiktok',
			zalo: 'mk-tag--zalo',
			website: 'mk-tag--website',
			hotline: 'mk-tag--hotline',
			other: 'mk-tag--other',
			co_quan: 'mk-tag--co-quan',
			chuan_bi_mo: 'mk-tag--chuan-bi-mo',
			gia_dinh: 'mk-tag--gia-dinh',
			individual: 'mk-tag--individual',
			company: 'mk-tag--company',
			kv1: 'mk-tag--kv1',
			kv2: 'mk-tag--kv2',
			kv3: 'mk-tag--kv3'
		};
		return map[key] || 'mk-tag--other';
	}

	function normalizeLeadKey(tag) {
		var lr = leadRef();
		if (lr && lr.normalizeTag) return lr.normalizeTag(tag);
		return normalizeTag(tag);
	}

	function regionKeyOfLead(lead) {
		var area = String((lead && (lead.area || lead.district)) || '').toLowerCase();
		if (/kv?1|khu\s*v[uư]c\s*1/.test(area)) return 'kv1';
		if (/kv?2|khu\s*v[uư]c\s*2/.test(area)) return 'kv2';
		if (/kv?3|khu\s*v[uư]c\s*3/.test(area)) return 'kv3';
		var tags = (lead && lead.tags) || [];
		for (var i = 0; i < tags.length; i++) {
			var k = normalizeLeadKey(tags[i]);
			if (k === 'kv1' || k === 'kv2' || k === 'kv3') return k;
		}
		return '';
	}

	function addressOfLead(lead) {
		return String((lead && (lead.address || lead.address_line || lead.lane)) || '').trim();
	}

	function sourceTagOfLead(lead) {
		var tags = (lead && lead.tags) || [];
		for (var i = 0; i < tags.length; i++) {
			var k = normalizeLeadKey(tags[i]);
			if (LEAD_SOURCE_TAGS.indexOf(k) >= 0) return k;
		}
		return '';
	}

	function customerTagOfLead(lead) {
		if (lead && lead.segment) {
			var seg = normalizeLeadKey(lead.segment);
			if (seg) return seg;
		}
		var tags = (lead && lead.tags) || [];
		for (var i = 0; i < tags.length; i++) {
			var k = normalizeLeadKey(tags[i]);
			if (k === 'ca_nhan') k = 'individual';
			if (LEAD_CUSTOMER_TAGS.indexOf(k) >= 0) return k;
		}
		return '';
	}

	function leadPill(key, labelOverride) {
		if (!key) return '<span class="mk-opp-lead-muted">—</span>';
		var label = labelOverride || leadTagLabel(key);
		return (
			'<span class="mk-tag mk-opp-lead-pill ' +
			esc(leadTagCls(key)) +
			'">' +
			esc(label) +
			'</span>'
		);
	}

	function loadLeadSearchCache() {
		if (leadSearchCache) {
			return $.Deferred().resolve(leadSearchCache).promise();
		}
		return $.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data: {
				module: 'Leads',
				action: 'ModernApi',
				mode: 'list'
			}
		}).then(function (res) {
			var list =
				(res && res.result && res.result.leads) ||
				(res && res.leads) ||
				[];
			leadSearchCache = Array.isArray(list) ? list : [];
			return leadSearchCache;
		}).fail(function () {
			leadSearchCache = [];
			return leadSearchCache;
		});
	}

	function filterLeads(q) {
		q = String(q || '')
			.trim()
			.toLowerCase();
		var list = leadSearchCache || [];
		if (!q) return list.slice(0, 12);
		return list
			.filter(function (l) {
				var hay = [l.name, l.phone, l.email, l.company, addressOfLead(l), (l.tags || []).join(' ')]
					.join(' ')
					.toLowerCase();
				return hay.indexOf(q) >= 0;
			})
			.slice(0, 12);
	}

	function renderLeadSuggest($wrap, items) {
		var $box = $wrap.find('.mk-opp-name-suggest');
		if (!$box.length) return;
		if (!items || !items.length) {
			$box.empty().addClass('is-empty').hide();
			return;
		}
		$box
			.removeClass('is-empty')
			.html(
				items
					.map(function (l) {
						var id = l.crmid || l.id || l.leadid || '';
						var label = l.name || ('#' + id);
						var sub = [l.phone, sourceTagOfLead(l)].filter(Boolean).join(' · ');
						return (
							'<button type="button" class="mk-opp-name-suggest__item" data-id="' +
							esc(id) +
							'" data-name="' +
							esc(label) +
							'">' +
							'<span class="mk-opp-name-suggest__name">' +
							esc(label) +
							'</span>' +
							(sub
								? '<span class="mk-opp-name-suggest__sub">' + esc(sub) + '</span>'
								: '') +
							'</button>'
						);
					})
					.join('')
			)
			.show();
	}

	function applyLeadToForm($f, lead) {
		if (!lead) return;
		var label = String(lead.name || '').trim();
		var $display = $('.mk-opp-name-display');
		var $name = $f.find('[name="potentialname"]');
		if (label) {
			$display.val(label);
			$name.val(label);
			$('.mk-opp-name-ref').addClass('selected');
			$('.mk-opp-name-ref .clearReferenceSelection').removeClass('hide');
		}

		var region = regionKeyOfLead(lead);
		var address = addressOfLead(lead);
		if (region) $('#mkOppRegion').val(region);
		if (address) $('#mkOppAddress').val(address);

		var src = sourceTagOfLead(lead);
		var cust = customerTagOfLead(lead);
		LEAD_SOURCE_TAGS.forEach(function (k) {
			selectedTags[k] = false;
		});
		LEAD_CUSTOMER_TAGS.forEach(function (k) {
			selectedTags[k] = false;
		});
		['kv1', 'kv2', 'kv3'].forEach(function (k) {
			selectedTags[k] = false;
		});
		if (region) selectedTags[region] = true;
		if (src) selectedTags[src] = true;
		if (cust) selectedTags[cust === 'ca_nhan' ? 'individual' : cust] = true;

		(lead.tags || []).forEach(function (tg) {
			var k = normalizeLeadKey(tg);
			if (k === 'ca_nhan') k = 'individual';
			if (k) selectedTags[k] = true;
		});

		renderListParityPanel();
		syncHidden();
	}

	function findLeadById(id) {
		id = String(id || '');
		var list = leadSearchCache || [];
		for (var i = 0; i < list.length; i++) {
			var lid = String(list[i].crmid || list[i].id || list[i].leadid || '');
			if (lid === id) return list[i];
		}
		return null;
	}

	function closeLeadPicker() {
		$('#mkOppLeadPicker').remove();
		$('body').removeClass('mk-opp-lead-picker-open');
	}

	function openLeadPicker($f, $wrap) {
		closeLeadPicker();
		var $modal = $(
			'<div class="mk-opp-lead-picker" id="mkOppLeadPicker" role="dialog" aria-modal="true" aria-label="Chọn Lead">' +
				'<div class="mk-opp-lead-picker__backdrop" data-close="1"></div>' +
				'<div class="mk-opp-lead-picker__panel">' +
				'<header class="mk-opp-lead-picker__head">' +
				'<h3 class="mk-opp-lead-picker__title">Chọn Lead</h3>' +
				'<button type="button" class="mk-opp-lead-picker__close" data-close="1" aria-label="Đóng">&times;</button>' +
				'</header>' +
				'<div class="mk-opp-lead-picker__toolbar">' +
				'<input type="search" class="mk-opp-lead-picker__search inputElement" placeholder="Tìm theo tên, SĐT, địa chỉ, tag…" />' +
				'<span class="mk-opp-lead-picker__count" id="mkOppLeadPickerCount"></span>' +
				'</div>' +
				'<div class="mk-opp-lead-picker__table-wrap">' +
				'<table class="mk-opp-lead-picker__table">' +
				'<thead><tr>' +
				'<th>Khách hàng</th><th>Điện thoại</th><th>Khu vực</th><th>Địa chỉ</th><th>Nguồn</th><th>Loại khác</th>' +
				'</tr></thead>' +
				'<tbody id="mkOppLeadPickerBody"><tr><td colspan="6" class="mk-opp-lead-muted">Đang tải…</td></tr></tbody>' +
				'</table></div></div></div>'
		);
		$('body').append($modal).addClass('mk-opp-lead-picker-open');

		function paint(rows) {
			var $body = $('#mkOppLeadPickerBody');
			$('#mkOppLeadPickerCount').text(rows.length + ' lead');
			if (!rows.length) {
				$body.html('<tr><td colspan="6" class="mk-opp-lead-muted">Không tìm thấy lead.</td></tr>');
				return;
			}
			$body.html(
				rows
					.map(function (l) {
						var id = l.crmid || l.id || l.leadid || '';
						var region = regionKeyOfLead(l);
						var regionLabel = region
							? LEAD_REGION_LABELS[region] || region
							: '— Chọn khu vực —';
						var addr = addressOfLead(l);
						var src = sourceTagOfLead(l);
						var cust = customerTagOfLead(l);
						if (cust === 'ca_nhan') cust = 'individual';
						return (
							'<tr class="mk-opp-lead-picker__row" data-id="' +
							esc(id) +
							'" tabindex="0">' +
							'<td><span class="mk-opp-lead-picker__name"><i class="fa fa-user-circle mk-opp-lead-picker__avatar" aria-hidden="true"></i>' +
							esc(l.name || '—') +
							'</span></td>' +
							'<td>' +
							(l.phone ? esc(l.phone) : '<span class="mk-opp-lead-muted">—</span>') +
							'</td>' +
							'<td><span class="mk-opp-lead-picker__region">' +
							esc(regionLabel) +
							'</span></td>' +
							'<td>' +
							(addr ? esc(addr) : '<span class="mk-opp-lead-muted">Nhập địa chỉ</span>') +
							'</td>' +
							'<td>' +
							leadPill(src) +
							'</td>' +
							'<td>' +
							leadPill(cust) +
							'</td>' +
							'</tr>'
						);
					})
					.join('')
			);
		}

		loadLeadSearchCache().always(function () {
			paint(filterLeads(''));
		});

		$modal.on('input', '.mk-opp-lead-picker__search', function () {
			var q = $(this).val();
			loadLeadSearchCache().always(function () {
				paint(filterLeads(q));
			});
		});

		$modal.on('click', '[data-close="1"]', function (e) {
			e.preventDefault();
			closeLeadPicker();
		});

		$modal.on('click', '.mk-opp-lead-picker__row', function (e) {
			e.preventDefault();
			var id = $(this).attr('data-id');
			var lead = findLeadById(id);
			if (!lead) return;
			applyLeadToForm($f, lead);
			$wrap.find('.mk-opp-name-suggest').hide().empty();
			closeLeadPicker();
		});
	}

	function openLeadQuickCreate($f, $wrap) {
		var $qc = $('#quickCreateModules').find('[data-name="Leads"]');
		if (!$qc.length) {
			$wrap.find('.mk-opp-name-display').prop('disabled', false).val('').focus();
			$f.find('[name="potentialname"]').val('');
			$wrap.removeClass('selected');
			$wrap.find('.clearReferenceSelection').addClass('hide');
			return;
		}
		$qc.trigger('click', [
			{
				callbackFunction: function (data) {
					var label = data && (data._recordLabel || data.label || data.name);
					if (label) {
						$wrap.find('.mk-opp-name-display').val(label);
						$f.find('[name="potentialname"]').val(label);
						$wrap.addClass('selected');
						$wrap.find('.clearReferenceSelection').removeClass('hide');
					}
					leadSearchCache = null;
				}
			}
		]);
	}

	function enhanceOppNameField($f) {
		var $name = $f.find('[name="potentialname"]');
		if (!$name.length) return;

		var $td = $name.closest('td.fieldValue');
		var $label = $td.prev('td.fieldLabel');
		if (!$label.length) {
			$label = $name.closest('tr').find('td.fieldLabel').first();
		}
		if ($label.length) {
			var req = $label.find('.redColor, .required').length || $name.data('rule-required')
				? ' <span class="redColor">*</span>'
				: '';
			$label.html('Tên khách hàng' + req);
		}

		$name.prop('readonly', false).removeAttr('readonly').removeClass('misa-auto-locked');
		$name.css({ background: '', cursor: '' });
		$td.find('.js-auto-generated-hint').remove();

		if ($td.find('.mk-opp-name-ref').length) {
			return;
		}

		var current = String($name.val() || '');
		$name.addClass('mk-opp-name-source').attr('type', 'hidden');

		var $wrap = $(
			'<div class="mk-opp-name-ref' +
				(current ? ' selected' : '') +
				'">' +
				'<input name="popupReferenceModule" type="hidden" value="Leads" />' +
				'<div class="mk-opp-name-bar">' +
				'<input type="text" class="inputElement mk-opp-name-display" ' +
				'placeholder="Nhập để tìm kiếm Lead hoặc tạo mới" autocomplete="off" />' +
				'<div class="mk-opp-name-actions" role="group" aria-label="Tìm / tạo Lead">' +
				'<button type="button" class="mk-opp-name-btn clearReferenceSelection' +
				(current ? '' : ' hide') +
				'" title="Xóa" aria-label="Xóa">' +
				'<i class="fa fa-times" aria-hidden="true"></i></button>' +
				'<button type="button" class="mk-opp-name-btn mk-opp-name-search" title="Tìm Lead" aria-label="Tìm Lead">' +
				'<i class="fa fa-search" aria-hidden="true"></i></button>' +
				'<button type="button" class="mk-opp-name-btn mk-opp-name-btn--accent mk-opp-name-create" title="Tạo nhanh Lead" aria-label="Tạo Lead">' +
				'<i class="fa fa-plus" aria-hidden="true"></i></button>' +
				'</div></div>' +
				'<div class="mk-opp-name-suggest" style="display:none"></div>' +
				'</div>'
		);
		$td.append($wrap);
		var $display = $wrap.find('.mk-opp-name-display');
		$display.val(current);

		loadLeadSearchCache();

		var suggestTimer = null;
		$display.on('input.mkOppName', function () {
			var val = String($display.val() || '');
			$name.val(val);
			$wrap.toggleClass('selected', !!val.trim());
			$wrap.find('.clearReferenceSelection').toggleClass('hide', !val.trim());
			clearTimeout(suggestTimer);
			suggestTimer = setTimeout(function () {
				loadLeadSearchCache().always(function () {
					renderLeadSuggest($wrap, filterLeads(val));
				});
			}, 180);
		});

		$display.on('focus.mkOppName', function () {
			loadLeadSearchCache().always(function () {
				renderLeadSuggest($wrap, filterLeads($display.val()));
			});
		});

		$wrap.on('click.mkOppName', '.mk-opp-name-suggest__item', function (e) {
			e.preventDefault();
			var id = $(this).attr('data-id');
			var lead = findLeadById(id);
			if (lead) {
				applyLeadToForm($f, lead);
			} else {
				var label = $(this).attr('data-name') || '';
				$display.val(label);
				$name.val(label);
			}
			$wrap.find('.mk-opp-name-suggest').hide().empty();
		});

		$wrap.on('click.mkOppName', '.clearReferenceSelection', function (e) {
			e.preventDefault();
			$display.prop('disabled', false).val('').focus();
			$name.val('');
			$wrap.removeClass('selected');
			$(this).addClass('hide');
			$wrap.find('.mk-opp-name-suggest').hide().empty();
		});

		$wrap.on('click.mkOppName', '.mk-opp-name-search', function (e) {
			e.preventDefault();
			e.stopPropagation();
			openLeadPicker($f, $wrap);
		});

		$wrap.on('click.mkOppName', '.mk-opp-name-create', function (e) {
			e.preventDefault();
			e.stopPropagation();
			openLeadQuickCreate($f, $wrap);
		});

		$(document)
			.off('click.mkOppNameSuggest')
			.on('click.mkOppNameSuggest', function (e) {
				if (!$(e.target).closest('.mk-opp-name-ref').length) {
					$('.mk-opp-name-suggest').hide();
				}
			});
	}

	function renderListParityPanel() {
		var $host = $('#mkOppFormHost');
		if (!$host.length) return;

		var $main = $host.find('.fieldBlockContainer').not('.mk-opp-hide-block').first();
		var $panel = $('#mkOppListParity');
		if (!$panel.length) {
			$panel = $(
				'<section class="mk-opp-extras" id="mkOppListParity">' +
					'<div class="mk-opp-extras__divider" aria-hidden="true"></div>' +
					'<div class="mk-opp-extras__head">' +
					'<h4 class="mk-opp-extras__title">Khu vực &amp; Tags</h4>' +
					'<p class="mk-opp-extras__hint">Cùng bộ trường hiển thị trên danh sách Cơ hội.</p>' +
					'</div>' +
					'<div class="mk-opp-list-parity__grid">' +
					'<div class="mk-opp-list-parity__field">' +
					'<span class="mk-opp-list-parity__label">Khu vực</span>' +
					'<select id="mkOppRegion" class="inputElement">' +
					'<option value="">— Chọn khu vực —</option>' +
					'<option value="kv1">Khu vực 1</option>' +
					'<option value="kv2">Khu vực 2</option>' +
					'<option value="kv3">Khu vực 3</option>' +
					'</select></div>' +
					'<div class="mk-opp-list-parity__field">' +
					'<span class="mk-opp-list-parity__label">Địa chỉ</span>' +
					'<input type="text" id="mkOppAddress" class="inputElement" placeholder="Nhập địa chỉ" autocomplete="off" />' +
					'</div></div>' +
					'<div class="mk-opp-list-parity__label mk-opp-list-parity__tags-label">Tags</div>' +
					'<div class="mk-opp-list-parity__body" id="mkOppTagsBody"></div>' +
					'</section>'
			);
			if ($main.length) {
				$main.append($panel);
				$main.addClass('mk-opp-block mk-opp-block--merged');
			} else {
				$host.append($panel);
			}
			$('#mkOppRegion').val(meta.mk_region || '');
			$('#mkOppAddress').val(meta.mk_address || '');
		}

		var catalog = catalogForForm();
		var body = document.getElementById('mkOppTagsBody');
		if (!body) return;
		body.innerHTML = catalog
			.map(function (g) {
				var chips = (g.tags || [])
					.map(function (item) {
						var on = !!selectedTags[item.key];
						var cls = item.cls || tagCls(item.key);
						return (
							'<button type="button" class="mk-opp-tag-chip mk-tag ' +
							esc(cls) +
							(on ? ' is-on' : '') +
							'" data-tag="' +
							esc(item.key) +
							'" data-group="' +
							esc(g.id) +
							'" aria-pressed="' +
							(on ? 'true' : 'false') +
							'">' +
							esc(item.label) +
							'</button>'
						);
					})
					.join('');
				return (
					'<div class="mk-opp-tag-group" data-group="' +
					esc(g.id) +
					'">' +
					'<div class="mk-opp-tag-group__title">' +
					esc(g.label) +
					'</div>' +
					'<div class="mk-opp-tag-group__chips">' +
					chips +
					'</div></div>'
				);
			})
			.join('');
		syncHidden();
	}

	function bindListParity() {
		$(document)
			.off('click.mkOppTags', '#mkOppTagsBody .mk-opp-tag-chip')
			.on('click.mkOppTags', '#mkOppTagsBody .mk-opp-tag-chip', function (e) {
				e.preventDefault();
				var $chip = $(this);
				var key = $chip.attr('data-tag');
				var group = $chip.attr('data-group');
				var turningOn = !$chip.hasClass('is-on');
				if (group && turningOn) {
					$('#mkOppTagsBody .mk-opp-tag-group[data-group="' + group + '"] .mk-opp-tag-chip.is-on').each(
						function () {
							var k = $(this).attr('data-tag');
							if (k) selectedTags[k] = false;
							$(this).removeClass('is-on').attr('aria-pressed', 'false');
						}
					);
				}
				selectedTags[key] = turningOn;
				$chip.toggleClass('is-on', turningOn).attr('aria-pressed', turningOn ? 'true' : 'false');
				syncHidden();
			});

		$(document)
			.off('change.mkOppLoc input.mkOppLoc', '#mkOppRegion, #mkOppAddress')
			.on('change.mkOppLoc input.mkOppLoc', '#mkOppRegion, #mkOppAddress', function () {
				syncHidden();
			});
	}

	function seedFromMeta() {
		meta = readBootMeta();
		selectedTags = {};
		(meta.tags || []).forEach(function (tg) {
			var k = normalizeTag(tg);
			if (k) selectedTags[k] = true;
		});
		if (meta.mk_region) {
			['kv1', 'kv2', 'kv3'].forEach(function (k) {
				selectedTags[k] = false;
			});
			selectedTags[meta.mk_region] = true;
		}
	}

	function hideLegacyChrome() {
		var $host = $('#mkOppFormHost');
		$host.find('#modnavigator, .editViewModNavigator').remove();
		$host.find('.editViewHeader').addClass('mk-opp-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-opp-form-footer');
		$host.find('.main-container').first().addClass('mk-opp-form-container');
		$('#mkOppCreateWorkspace .mk-opp-aside').remove();
		$('.mk-opp-create-body').addClass('mk-opp-create-body--solo');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-opp-hide-block')) return;
				$block.addClass('mk-opp-block');
				var $header = $block.find('.fieldBlockHeader').first();
				$header.addClass('mk-opp-block__header');
				if (!$header.find('.mk-opp-block__badge').length) {
					$header.prepend(
						'<span class="mk-opp-block__badge" aria-hidden="true"></span>'
					);
				}
				$block.find('> hr').addClass('mk-opp-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-opp-fields-table');
			});
	}

	function triggerSave() {
		syncHidden();
		softDefaultRequired($form());
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
	}

	function bindActions() {
		$('#mkOppSaveTop')
			.off('click.mkOppSave')
			.on('click.mkOppSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$form()
			.off('submit.mkOppExtras')
			.on('submit.mkOppExtras', function () {
				syncHidden();
				softDefaultRequired($form());
			});

		$(document)
			.off('keydown.mkOppCreate')
			.on('keydown.mkOppCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkOppFormHost').length) {
						return;
					}
					e.preventDefault();
					triggerSave();
				}
			});
	}

	function runEnhancements() {
		if (!isScoped()) {
			return;
		}
		hideLegacyChrome();
		hideStockNoise();
		styleFieldBlocks();
		renderListParityPanel();
		bindListParity();
		bindActions();
	}

	function init() {
		function tryRun() {
			if (!isScoped()) return false;
			seedFromMeta();
			runEnhancements();
			return true;
		}
		if (!tryRun()) {
			// Header scripts may run before workspace exists
			var tries = 0;
			var timer = setInterval(function () {
				tries++;
				if (tryRun() || tries > 40) clearInterval(timer);
			}, 100);
		} else {
			setTimeout(runEnhancements, 150);
			setTimeout(runEnhancements, 600);
			setTimeout(runEnhancements, 1200);
		}

		$(document).ajaxComplete(function () {
			if (isScoped()) {
				setTimeout(runEnhancements, 80);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	window.__mkOppCreateBuild = MK_BUILD;
	window.__mkOppRunEnhancements = runEnhancements;
})($);
