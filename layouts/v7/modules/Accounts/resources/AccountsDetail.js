/**
 * Accounts Detail (SALES/SUPPORT): badge + more menu; slim franchise Chi tiết like Create.
 */
(function ($) {
	'use strict';

	var KEEP_FIELDS = {
		accountname: true,
		phone: true,
		email1: true,
		assigned_user_id: true,
		createdtime: true,
		tb_contract_no: true,
		tb_sign_date: true,
		tb_party_b_cccd: true,
		tb_party_b_cccd_date: true,
		tb_party_b_cccd_place: true,
		tb_party_b_permanent_addr: true,
		tb_store_address: true,
		tb_fee_franchise: true,
		tb_fee_marketing: true,
		tb_fee_consult: true,
		tb_fee_opening: true,
		tb_fee_deposit: true,
		tb_order_min_free: true,
		tb_order_min_ship: true,
		tb_order_ship_fee: true,
		tb_order_min_pickup: true
	};

	var FIELD_LABELS = {
		accountname: 'Họ tên Bên B',
		phone: 'Điện thoại',
		email1: 'Email',
		assigned_user_id: 'Phụ trách',
		createdtime: 'Ngày tạo',
		tb_contract_no: 'Số hợp đồng',
		tb_sign_date: 'Ngày ký',
		tb_party_b_cccd: 'CCCD / CMND',
		tb_party_b_cccd_date: 'Ngày cấp',
		tb_party_b_cccd_place: 'Nơi cấp',
		tb_party_b_permanent_addr: 'Địa chỉ thường trú / liên hệ',
		tb_store_address: 'Địa chỉ cửa hàng',
		tb_fee_franchise: 'Phí nhượng quyền',
		tb_fee_marketing: 'Phí marketing thương hiệu',
		tb_fee_consult: 'Phí tư vấn / hỗ trợ vận hành',
		tb_fee_opening: 'Phí marketing khai trương',
		tb_fee_deposit: 'Tiền ký quỹ bảo đảm (Đợt 1)',
		tb_order_min_free: 'Đơn hàng tối thiểu miễn ship',
		tb_order_min_ship: 'Đơn hàng dưới mức (có ship)',
		tb_order_ship_fee: 'Phí ship nội thành',
		tb_order_min_pickup: 'Đơn hàng tự đến kho lấy'
	};

	var BLOCK_TITLES = {
		LBL_ACCOUNT_INFORMATION: 'Thông tin Bên B',
		LBL_TB_FRANCHISE_CONTRACT: 'Hợp đồng nhượng quyền'
	};

	var HIDE_BLOCKS = {
		LBL_CUSTOM_INFORMATION: true,
		LBL_ADDRESS_INFORMATION: true,
		LBL_DESCRIPTION_INFORMATION: true
	};

	function isScopedBody() {
		var b = document.body;
		var app = b && b.getAttribute('data-app');
		return !!(
			b &&
			b.getAttribute('data-module') === 'Accounts' &&
			b.getAttribute('data-view') === 'Detail' &&
			(app === 'SALES' || app === 'MARKETING' || app === 'SUPPORT')
		);
	}

	function isSalesApp() {
		var app = document.body && document.body.getAttribute('data-app');
		return app === 'SALES' || !app;
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-acc-detail-related-tabs li[data-module] > a .numberCircle'
		);
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			el.classList.remove('hide');
			var raw = (el.textContent || '').trim();
			var count = parseInt(raw, 10);
			if (isNaN(count)) {
				count = 0;
			}
			el.setAttribute('data-count', String(count));
			if (raw === '') {
				el.textContent = '0';
			}
		}
	}

	function toggleDropdown($parent, open) {
		if (!$parent || !$parent.length) {
			return;
		}
		if (open) {
			$parent.addClass('open');
			$parent.find('.dropdown-toggle').attr('aria-expanded', 'true');
		} else {
			$parent.removeClass('open');
			$parent.find('.dropdown-toggle').attr('aria-expanded', 'false');
		}
	}

	function bindDropdownClicks() {
		var $doc = $(document);

		$doc.off(
			'click.mkAccHeaderMore',
			'.mk-acc-detail-actions__group > .dropdown-toggle'
		);
		$doc.on(
			'click.mkAccHeaderMore',
			'.mk-acc-detail-actions__group > .dropdown-toggle',
			function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $group = $(this).closest('.mk-acc-detail-actions__group');
				var shouldOpen = !$group.hasClass('open');
				$(
					'.mk-acc-detail-actions__group, .mk-acc-detail-related-tabs .related-tab-more-element'
				).removeClass('open');
				$(
					'.mk-acc-detail-actions__group .dropdown-toggle, .mk-acc-detail-related-tabs .related-tab-more-element .dropdown-toggle'
				).attr('aria-expanded', 'false');
				if (shouldOpen) {
					toggleDropdown($group, true);
				}
			}
		);

		$doc.off(
			'click.mkAccTabMore',
			'.mk-acc-detail-related-tabs .related-tab-more-element > .dropdown-toggle'
		);
		$doc.on(
			'click.mkAccTabMore',
			'.mk-acc-detail-related-tabs .related-tab-more-element > .dropdown-toggle',
			function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $li = $(this).closest('.related-tab-more-element');
				var shouldOpen = !$li.hasClass('open');
				$(
					'.mk-acc-detail-actions__group, .mk-acc-detail-related-tabs .related-tab-more-element'
				).removeClass('open');
				$(
					'.mk-acc-detail-actions__group .dropdown-toggle, .mk-acc-detail-related-tabs .related-tab-more-element .dropdown-toggle'
				).attr('aria-expanded', 'false');
				if (shouldOpen) {
					toggleDropdown($li, true);
				}
			}
		);

		$doc.off('click.mkAccDropdownClose');
		$doc.on('click.mkAccDropdownClose', function (e) {
			if (
				$(e.target).closest(
					'.mk-acc-detail-actions__group, .related-tab-more-element'
				).length
			) {
				return;
			}
			$(
				'.mk-acc-detail-actions__group, .mk-acc-detail-related-tabs .related-tab-more-element'
			).removeClass('open');
			$(
				'.mk-acc-detail-actions__group .dropdown-toggle, .mk-acc-detail-related-tabs .related-tab-more-element .dropdown-toggle'
			).attr('aria-expanded', 'false');
		});
	}

	var FIELD_ORDER_PARTY = [
		'accountname',
		'phone',
		'email1',
		'assigned_user_id',
		'createdtime'
	];

	var FIELD_ORDER_CONTRACT = [
		'tb_contract_no',
		'tb_sign_date',
		'tb_party_b_cccd',
		'tb_party_b_cccd_date',
		'tb_party_b_cccd_place',
		'tb_party_b_permanent_addr',
		'tb_store_address',
		'tb_fee_franchise',
		'tb_fee_marketing',
		'tb_fee_consult',
		'tb_fee_opening',
		'tb_fee_deposit',
		'tb_order_min_free',
		'tb_order_min_ship',
		'tb_order_ship_fee',
		'tb_order_min_pickup'
	];

	var WIDE_FIELDS = {
		accountname: true,
		tb_party_b_permanent_addr: true,
		tb_store_address: true,
		tb_party_b_cccd_place: true
	};

	var MONEY_FIELDS = {
		tb_fee_franchise: true,
		tb_fee_marketing: true,
		tb_fee_consult: true,
		tb_fee_opening: true,
		tb_fee_deposit: true,
		tb_order_min_free: true,
		tb_order_min_ship: true,
		tb_order_ship_fee: true,
		tb_order_min_pickup: true
	};

	function isEmptyDisplayValue(valueCell) {
		if (!valueCell) {
			return true;
		}
		var clone = valueCell.cloneNode(true);
		var extras = clone.querySelectorAll('.action, .edit, .editAction, .hide');
		for (var e = 0; e < extras.length; e++) {
			if (extras[e].parentNode) {
				extras[e].parentNode.removeChild(extras[e]);
			}
		}
		var text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
		text = text.replace(/^[\s\u00a0—–\-]+|[\s\u00a0—–\-]+$/g, '');
		return !text || text === '--' || text === '—';
	}

	function buildFieldCard(name, valueNode, labelOverride) {
		var card = document.createElement('div');
		card.className = 'mk-acc-fr-field';
		card.setAttribute('data-field', name);
		if (WIDE_FIELDS[name]) {
			card.className += ' mk-acc-fr-field--wide';
		}
		if (MONEY_FIELDS[name]) {
			card.className += ' mk-acc-fr-field--money';
		}

		var lab = document.createElement('div');
		lab.className = 'mk-acc-fr-field__label';
		lab.textContent = labelOverride || FIELD_LABELS[name] || name;

		var val = document.createElement('div');
		val.className = 'mk-acc-fr-field__value';
		var valInner = valueNode.querySelector('.value');
		if (valInner) {
			val.appendChild(valInner);
		} else {
			while (valueNode.firstChild) {
				val.appendChild(valueNode.firstChild);
			}
		}
		var action = valueNode.querySelector('.action');
		if (action) {
			action.classList.add('mk-acc-fr-field__edit');
			val.appendChild(action);
		}
		var edit = valueNode.querySelector('.edit');
		if (edit) {
			val.appendChild(edit);
		}

		// Clean ugliness: standalone "0" for empty party name should not look like a header
		var plain = (val.textContent || '').replace(/\s+/g, ' ').trim();
		if (name === 'accountname' && (plain === '0' || plain === '')) {
			val.innerHTML = '<span class="mk-acc-fr-field__empty">—</span>';
		}

		card.appendChild(lab);
		card.appendChild(val);
		return card;
	}

	function collectBlockFields(block) {
		var map = {};
		var labels = block.querySelectorAll(
			'td.fieldLabel[id^="Accounts_detailView_fieldLabel_"]'
		);
		for (var i = 0; i < labels.length; i++) {
			var lab = labels[i];
			var name = (lab.id || '').replace(
				'Accounts_detailView_fieldLabel_',
				''
			);
			var val = document.getElementById(
				'Accounts_detailView_fieldValue_' + name
			);
			if (!name || !val || !KEEP_FIELDS[name]) {
				continue;
			}
			map[name] = val;
		}
		return map;
	}

	function rebuildBlockGrid(block, blockKey, fieldOrder) {
		if (block.querySelector('.mk-acc-fr-grid')) {
			return true;
		}
		var fields = collectBlockFields(block);
		var order = fieldOrder || [];
		var grid = document.createElement('div');
		grid.className = 'mk-acc-fr-grid';
		if (blockKey === 'LBL_TB_FRANCHISE_CONTRACT') {
			grid.className += ' mk-acc-fr-grid--contract';
		} else {
			grid.className += ' mk-acc-fr-grid--party';
		}

		var sectionMeta = document.createElement('div');
		sectionMeta.className = 'mk-acc-fr-block__meta';

		var count = 0;
		for (var oi = 0; oi < order.length; oi++) {
			var fname = order[oi];
			if (!fields[fname]) {
				continue;
			}
			// Skip empty optional fields except core party identity/contact
			var alwaysShow =
				fname === 'accountname' ||
				fname === 'phone' ||
				fname === 'email1' ||
				fname === 'assigned_user_id' ||
				fname === 'tb_contract_no' ||
				fname === 'tb_sign_date';
			if (!alwaysShow && isEmptyDisplayValue(fields[fname])) {
				continue;
			}
			grid.appendChild(
				buildFieldCard(fname, fields[fname], FIELD_LABELS[fname])
			);
			count++;
		}

		if (!count) {
			return false;
		}

		var table = block.querySelector('table.detailview-table');
		if (table) {
			table.style.display = 'none';
		}
		var blockData = block.querySelector('.blockData') || block;
		// Place grid after header
		var head = block.querySelector('h4');
		if (head && head.parentNode) {
			// keep head + hide hr visual clutter by class
			var hr = block.querySelector('hr');
			if (hr) {
				hr.style.display = 'none';
			}
		}
		if (blockData) {
			blockData.appendChild(grid);
		} else {
			block.appendChild(grid);
		}

		// Modern title bar
		if (head) {
			head.classList.add('mk-acc-fr-block__title');
			var titleText = BLOCK_TITLES[blockKey] || head.textContent;
			var imgs = head.querySelectorAll('img.blockToggle');
			head.innerHTML = '';
			for (var ti = 0; ti < imgs.length; ti++) {
				imgs[ti].style.display = 'none';
			}
			var badge = document.createElement('span');
			badge.className = 'mk-acc-fr-block__badge';
			badge.setAttribute('aria-hidden', 'true');
			if (blockKey === 'LBL_TB_FRANCHISE_CONTRACT') {
				badge.innerHTML =
					'<i class="fa fa-file-text-o" aria-hidden="true"></i>';
			} else {
				badge.innerHTML = '<i class="fa fa-user" aria-hidden="true"></i>';
			}
			var tspan = document.createElement('span');
			tspan.className = 'mk-acc-fr-block__title-text';
			tspan.textContent = titleText;
			head.appendChild(badge);
			head.appendChild(tspan);
		}

		block.classList.add('mk-acc-fr-block');
		if (blockKey === 'LBL_TB_FRANCHISE_CONTRACT') {
			block.classList.add('mk-acc-fr-block--contract');
		} else if (blockKey === 'LBL_ACCOUNT_INFORMATION') {
			block.classList.add('mk-acc-fr-block--party');
		}
		return true;
	}

	/**
	 * Chi tiết tab: modern field-card grid (Create-aligned).
	 */
	function slimFranchiseDetailFields() {
		if (!isSalesApp()) {
			return;
		}
		var root =
			document.querySelector('.mk-acc-detailview-content #detailView') ||
			document.querySelector('.detailview-content #detailView') ||
			document.querySelector('form#detailView') ||
			document.querySelector('.detailview-content');
		if (!root) {
			return;
		}
		if (root.getAttribute('data-mk-acc-franchise-slim') === '1') {
			if (root.querySelector('.mk-acc-fr-grid')) {
				return;
			}
		}

		var hasTbField = !!root.querySelector(
			'[id^="Accounts_detailView_fieldLabel_tb_"], [id^="Accounts_detailView_fieldValue_tb_"]'
		);
		var hasFranchiseBlock = !!root.querySelector(
			'.block[data-block="LBL_TB_FRANCHISE_CONTRACT"], .block_LBL_TB_FRANCHISE_CONTRACT'
		);
		if (!hasTbField && !hasFranchiseBlock) {
			return;
		}

		document.body.classList.add('mk-acc-detail-franchise');
		root.classList.add('mk-acc-fr-detail');

		var blocks = root.querySelectorAll('.block[data-block], .block[data-blockid]');
		for (var bi = 0; bi < blocks.length; bi++) {
			var block = blocks[bi];
			var blockKey = block.getAttribute('data-block') || '';
			if (HIDE_BLOCKS[blockKey]) {
				block.style.display = 'none';
				continue;
			}
			if (blockKey === 'LBL_ACCOUNT_INFORMATION') {
				rebuildBlockGrid(block, blockKey, FIELD_ORDER_PARTY);
				continue;
			}
			if (blockKey === 'LBL_TB_FRANCHISE_CONTRACT') {
				rebuildBlockGrid(block, blockKey, FIELD_ORDER_CONTRACT);
				continue;
			}
			// other blocks not in keep list — hide
			block.style.display = 'none';
		}

		var $root = $(root);
		var $acct = $root
			.find('.block[data-block="LBL_ACCOUNT_INFORMATION"]')
			.first();
		var $fr = $root
			.find('.block[data-block="LBL_TB_FRANCHISE_CONTRACT"]')
			.first();
		if ($acct.length && $fr.length && !$fr.data('mk-moved')) {
			$fr.insertAfter($acct);
			$fr.data('mk-moved', 1);
		}

		root.setAttribute('data-mk-acc-franchise-slim', '1');
	}

	function hideHealthOverview() {
		// Defense in depth if template still injects health card
		$(
			'.mk-acc-detail-card--health, .mk-acc-detail-grid__health, #mk-acc-detail-health-title'
		)
			.closest('.mk-acc-detail-card, section')
			.addBack()
			.filter('.mk-acc-detail-card--health, .mk-acc-detail-grid__health')
			.hide();
	}

	function runDetailPolishes() {
		hideHealthOverview();
		slimFranchiseDetailFields();
	}

	function resolveFranchiseRecordId($btn) {
		return (
			String($btn.data('record-id') || $btn.attr('data-record-id') || '').trim() ||
			String($('#recordId').val() || $('input[name="record_id"]').val() || '').trim()
		);
	}

	function resolveWordUrl($btn, rid) {
		var wordUrl = String(
			$btn.data('word-url') ||
				$btn.attr('data-word-url') ||
				$btn.data('print-download-url') ||
				'index.php?module=Accounts&action=ExportFranchiseWord&record=' + encodeURIComponent(rid)
		);
		return wordUrl
			.replace(/([?&])preview=1(&?)/g, function (m, a, b) {
				return b ? a : '';
			})
			.replace(/([?&])autoprint=1(&?)/g, function (m, a, b) {
				return b ? a : '';
			})
			.replace(/\?&/, '?')
			.replace(/&&+/g, '&')
			.replace(/[?&]$/, '');
	}

	function bindFranchisePrintButton() {
		$(document)
			.off('click.mkAccFranchisePrint', '#Accounts_detailView_printFranchiseContract')
			.on('click.mkAccFranchisePrint', '#Accounts_detailView_printFranchiseContract', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $btn = $(this);
				var rid = resolveFranchiseRecordId($btn);
				if (!rid) {
					return;
				}
				var wordUrl = resolveWordUrl($btn, rid);
				if (window.MkSalesPosInline && typeof window.MkSalesPosInline.openFranchisePrint === 'function') {
					window.MkSalesPosInline.openFranchisePrint(rid, { wordUrl: wordUrl });
					return;
				}
				window.location.href = wordUrl;
			});

		$(document)
			.off('click.mkAccFranchisePreview', '#Accounts_detailView_previewFranchiseContract')
			.on('click.mkAccFranchisePreview', '#Accounts_detailView_previewFranchiseContract', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $btn = $(this);
				var rid = resolveFranchiseRecordId($btn);
				if (!rid) {
					return;
				}
				var wordUrl = resolveWordUrl($btn, rid);
				var previewUrl = String(
					$btn.data('preview-url') ||
						$btn.attr('data-preview-url') ||
						'index.php?module=Accounts&action=ExportFranchiseWord&record=' +
							encodeURIComponent(rid) +
							'&preview=1'
				);
				if (window.MkSalesPosInline && typeof window.MkSalesPosInline.openFranchisePreview === 'function') {
					window.MkSalesPosInline.openFranchisePreview(rid, {
						previewUrl: previewUrl,
						wordUrl: wordUrl,
					});
					return;
				}
				window.open(previewUrl, '_blank');
			});
	}

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		document.body.classList.add('mk-acc-detail-modern');
		refreshRelatedBadges();
		bindDropdownClicks();
		bindFranchisePrintButton();
		runDetailPolishes();

		var tabs = document.querySelector('.mk-acc-detail-related-tabs');
		if (tabs && typeof MutationObserver !== 'undefined') {
			var mo = new MutationObserver(function () {
				refreshRelatedBadges();
			});
			mo.observe(tabs, {
				childList: true,
				subtree: true,
				characterData: true,
			});
		}

		// Full / summary content AJAX
		var content =
			document.querySelector('.detailview-content') ||
			document.querySelector('.mk-acc-detailview-content');
		if (content && typeof MutationObserver !== 'undefined') {
			var mo2 = new MutationObserver(function () {
				// clear flag on #detailView if replaced
				var dv = content.querySelector('#detailView, form#detailView');
				if (dv && !dv.getAttribute('data-mk-acc-franchise-slim')) {
					slimFranchiseDetailFields();
				}
				hideHealthOverview();
			});
			mo2.observe(content, { childList: true, subtree: true });
		}

		$(document)
			.off('click.mkAccSlimDetail', '.related-tabs .tab-item a, .related-tabs a')
			.on(
				'click.mkAccSlimDetail',
				'.related-tabs .tab-item a, .related-tabs a',
				function () {
					setTimeout(runDetailPolishes, 400);
					setTimeout(runDetailPolishes, 1000);
				}
			);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// Vtiger detail loads content async after DOM ready
	if (typeof app !== 'undefined' && app.event) {
		app.event.on('post.relatedListLoad.click', function () {
			setTimeout(runDetailPolishes, 200);
		});
	}
	setTimeout(runDetailPolishes, 300);
	setTimeout(runDetailPolishes, 900);
})(jQuery);
