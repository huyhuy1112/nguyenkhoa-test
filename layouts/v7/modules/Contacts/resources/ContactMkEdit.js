/**
 * Contacts Create/Edit — slim form per product shots + Opp picker for customer name.
 * Keep: phone, account→Tên KH (Opp), email, owner, addresses, credentials, event times, tags.
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260803_ct_polish1';
	var selectedTags = {};
	var oppSearchCache = null;
	var slimLayoutDone = false;

	/** Visible / kept stock fields */
	var KEEP_FIELD_NAMES = {
		phone: 1,
		account_id: 1,
		email: 1,
		assigned_user_id: 1,
		da_cap_bang: 1,
		da_cap_tai_khoan: 1,
		thoigian_dangky: 1,
		thoigian_pcth: 1,
		thoigian_mqbb: 1
	};

	var FIELD_LAYOUT_ORDER = [
		'account_id',
		'phone',
		'email',
		'assigned_user_id',
		'thoigian_dangky',
		'thoigian_pcth',
		'thoigian_mqbb',
		'da_cap_bang',
		'da_cap_tai_khoan'
	];

	var SYSTEM_FIELD_NAMES = {
		module: 1,
		action: 1,
		record: 1,
		mode: 1,
		app: 1,
		view: 1,
		returnmodule: 1,
		returnview: 1,
		returnrecord: 1,
		returnmode: 1,
		returnrelatedmodule: 1,
		returnrelatedtab: 1,
		relationoperation: 1,
		sourceModule: 1,
		sourceRecord: 1,
		relationId: 1,
		__vtrftk: 1,
		mk_tags: 1,
		mk_source_opp_id: 1
	};

	var HIDE_BLOCK_KEYS = [
		'LBL_CUSTOM_INFORMATION',
		'LBL_IMAGE_INFORMATION',
		'LBL_PORTAL_INFORMATION',
		'LBL_DESCRIPTION_INFORMATION',
		'LBL_ADDRESS_INFORMATION'
	];

	var BLOCK_ICONS = {
		LBL_CONTACT_INFORMATION: 'fa-user',
		LBL_ADDRESS_INFORMATION: 'fa-map-marker'
	};

	function ref() {
		return window.ContactsLovableRef || null;
	}

	function isScoped() {
		return (
			$('body').data('module') === 'Contacts' &&
			$('body').data('view') === 'Edit' &&
			(
				$('body').data('app') === 'SALES' ||
				$('body').data('app') === 'MARKETING' ||
				!$('body').data('app')
			) &&
			$('#mkCtCreateWorkspace').length > 0
		);
	}

	function $form() {
		return $('#mkCtFormHost').find('form#EditView, form[name="EditView"]').first();
	}

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function readBootTags() {
		var el = document.getElementById('mkCtEditTagsBoot');
		if (!el) return [];
		try {
			var parsed = JSON.parse(el.textContent || '[]');
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			return [];
		}
	}

	function normalizeTag(tag) {
		var r = ref();
		return r && r.normalizeTag ? r.normalizeTag(tag) : String(tag || '').toLowerCase();
	}

	function catalogForForm() {
		var r = ref();
		var catalog = r && r.getCreateTagCatalog ? r.getCreateTagCatalog() : [];
		var skip = { da_cap_bang: 1, da_cap_tai_khoan: 1 };
		return catalog
			.map(function (g) {
				return {
					id: g.id,
					label: g.label,
					tags: (g.tags || []).filter(function (t) {
						return t && t.key && !skip[t.key];
					})
				};
			})
			.filter(function (g) {
				return (g.tags || []).length > 0;
			});
	}

	function baseFieldName(name) {
		return String(name || '')
			.replace(/\[\]$/, '')
			.replace(/_display$/, '');
	}

	function forceHideEl($el) {
		if (!$el || !$el.length) return;
		$el.addClass('mk-ct-hide-field');
		$el.each(function () {
			this.style.setProperty('display', 'none', 'important');
			this.style.setProperty('height', '0', 'important');
			this.style.setProperty('overflow', 'hidden', 'important');
			this.style.setProperty('margin', '0', 'important');
			this.style.setProperty('padding', '0', 'important');
		});
	}

	function hideFieldPair($input) {
		if (!$input || !$input.length) return;
		var $valueTd = $input.closest('td.fieldValue');
		if ($valueTd.length) {
			var $labelTd = $valueTd.prev('td.fieldLabel');
			forceHideEl($valueTd);
			if ($labelTd.length) forceHideEl($labelTd);
			var $row = $valueTd.closest('tr');
			if ($row.length) {
				var visibleCells = $row.children('td').not('.mk-ct-hide-field');
				if (!visibleCells.length) {
					forceHideEl($row);
				}
			}
			return;
		}
		forceHideEl($input.closest('tr, .form-group'));
	}

	function hideStockNoise() {
		var $f = $form();
		if (!$f.length) return;

		$f.find('input[name], select[name], textarea[name]').each(function () {
			var $el = $(this);
			var raw = $el.attr('name') || '';
			var key = baseFieldName(raw);
			if (!key || SYSTEM_FIELD_NAMES[key] || KEEP_FIELD_NAMES[key]) {
				return;
			}
			if ($el.attr('type') === 'hidden') {
				return;
			}
			hideFieldPair($el);
		});

		// Always hide lastname/firstname UI + address leftovers (lastname still filled on save)
		[
			'lastname',
			'firstname',
			'salutationtype',
			'support_start_date',
			'support_end_date',
			'mailingstreet',
			'otherstreet',
			'mailingcity',
			'mailingstate',
			'mailingzip',
			'mailingcountry',
			'mailingpobox',
			'othercity',
			'otherstate',
			'otherzip',
			'othercountry',
			'otherpobox'
		].forEach(function (name) {
			$f.find('[name="' + name + '"]').each(function () {
				hideFieldPair($(this));
			});
		});

		// Orphan labels (Địa chỉ khác / Địa chỉ email …) with no usable control
		$f.find('td.fieldLabel').each(function () {
			var $lab = $(this);
			if ($lab.hasClass('mk-ct-hide-field')) return;
			var text = String($lab.text() || '')
				.replace(/\*/g, '')
				.trim()
				.toLowerCase();
			if (
				text.indexOf('địa chỉ khác') >= 0 ||
				text.indexOf('địa chỉ email') >= 0 ||
				text === 'địa chỉ' ||
				text.indexOf('dia chi') >= 0
			) {
				var $val = $lab.next('td.fieldValue');
				forceHideEl($lab);
				if ($val.length) forceHideEl($val);
			}
		});

		HIDE_BLOCK_KEYS.forEach(function (key) {
			$f.find('.fieldBlockContainer[data-block="' + key + '"]').each(function () {
				var $b = $(this);
				$b.addClass('mk-ct-hide-block');
				this.style.setProperty('display', 'none', 'important');
			});
		});

		$f.find('.fieldBlockContainer[data-block]').each(function () {
			var $block = $(this);
			if ($block.hasClass('mk-ct-hide-block')) return;
			var $visible = $block.find('td.fieldValue').not('.mk-ct-hide-field');
			if (!$visible.length) {
				$block.addClass('mk-ct-hide-block');
				this.style.setProperty('display', 'none', 'important');
			}
		});
	}

	function ensureHidden($f, name, value) {
		var $input = $f.find('input[name="' + name + '"]');
		if (!$input.length) {
			$input = $('<input>', { type: 'hidden', name: name });
			$f.append($input);
		}
		$input.val(value == null ? '' : value);
	}

	function softDefaultRequired($f) {
		if (!$f || !$f.length) return;
		var name =
			String($('.mk-ct-cust-display').val() || '').trim() ||
			String($f.find('[name="lastname"]').val() || '').trim() ||
			'Khách hàng';
		var $ln = $f.find('[name="lastname"]');
		if ($ln.length) {
			$ln.val(name);
			$ln.removeAttr('data-rule-required').removeClass('validate[required]').attr('aria-required', 'false');
		}
		// Do not bind Opp → Accounts; clear org reference
		$f.find('[name="account_id"]').val('');
		$f.find('[name="account_id_display"]').val('');
	}

	function syncHiddenTags() {
		var $f = $form();
		if (!$f.length) return;
		var keys = Object.keys(selectedTags).filter(function (k) {
			return selectedTags[k];
		});
		var $input = $f.find('input[name="mk_tags"]');
		if (!$input.length) {
			$input = $('<input>', { type: 'hidden', name: 'mk_tags' });
			$f.append($input);
		}
		$input.val(JSON.stringify(keys));
	}

	function loadOppSearchCache() {
		if (oppSearchCache) {
			return $.Deferred().resolve(oppSearchCache).promise();
		}
		return $.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data: {
				module: 'Potentials',
				action: 'ModernApi',
				mode: 'list'
			}
		})
			.then(function (res) {
				var list =
					(res && res.result && res.result.opportunities) ||
					(res && res.opportunities) ||
					[];
				oppSearchCache = Array.isArray(list) ? list : [];
				return oppSearchCache;
			})
			.fail(function () {
				oppSearchCache = [];
				return oppSearchCache;
			});
	}

	function oppLabel(o) {
		return String((o && (o.contact || o.account || o.name)) || '').trim();
	}

	function filterOpps(q) {
		q = String(q || '')
			.trim()
			.toLowerCase();
		var list = oppSearchCache || [];
		if (!q) return list.slice(0, 12);
		return list
			.filter(function (o) {
				var hay = [oppLabel(o), o.name, o.phone, o.address, o.owner, (o.tags || []).join(' ')]
					.join(' ')
					.toLowerCase();
				return hay.indexOf(q) >= 0;
			})
			.slice(0, 12);
	}

	function findOppById(id) {
		id = String(id || '');
		var list = oppSearchCache || [];
		for (var i = 0; i < list.length; i++) {
			var o = list[i];
			var oid = String(o.crmid || o.id || '');
			if (oid && oid === id) return o;
		}
		return null;
	}

	function applyOppToForm($f, opp) {
		if (!opp) return;
		var label = oppLabel(opp) || String(opp.name || '').trim();
		var $display = $('.mk-ct-cust-display');
		if (label) {
			$display.val(label);
			$f.find('[name="lastname"]').val(label);
			$('.mk-ct-cust-ref').addClass('selected');
			$('.mk-ct-cust-ref .clearReferenceSelection').removeClass('hide');
		}
		if (opp.phone) $f.find('[name="phone"]').val(opp.phone);
		if (opp.address) {
			var $addr = $f.find('[name="mailingstreet"]');
			if ($addr.length) $addr.val(opp.address);
		}
		ensureHidden($f, 'mk_source_opp_id', opp.crmid || opp.id || '');
		$f.find('[name="account_id"]').val('');

		selectedTags = {};
		(opp.tags || []).forEach(function (tg) {
			var k = normalizeTag(tg);
			if (k && k !== 'da_cap_bang' && k !== 'da_cap_tai_khoan') {
				selectedTags[k] = true;
			}
		});
		renderTagsPanel();
		syncHiddenTags();
	}

	function renderOppSuggest($wrap, items) {
		var $box = $wrap.find('.mk-ct-cust-suggest');
		if (!$box.length) return;
		if (!items || !items.length) {
			$box.empty().hide();
			return;
		}
		$box
			.html(
				items
					.map(function (o) {
						var id = o.crmid || o.id || '';
						var label = oppLabel(o) || o.name || '#' + id;
						var sub = [o.phone, o.address].filter(Boolean).join(' · ');
						return (
							'<button type="button" class="mk-ct-cust-suggest__item" data-id="' +
							esc(id) +
							'" data-name="' +
							esc(label) +
							'">' +
							'<span class="mk-ct-cust-suggest__name">' +
							esc(label) +
							'</span>' +
							(sub
								? '<span class="mk-ct-cust-suggest__sub">' + esc(sub) + '</span>'
								: '') +
							'</button>'
						);
					})
					.join('')
			)
			.show();
	}

	function openOppPicker($f, $wrap) {
		var $modal = $('#mkCtOppPicker');
		if (!$modal.length) {
			$modal = $(
				'<div class="mk-ct-opp-picker" id="mkCtOppPicker" hidden>' +
					'<div class="mk-ct-opp-picker__backdrop" data-close="1"></div>' +
					'<div class="mk-ct-opp-picker__panel" role="dialog" aria-modal="true" aria-label="Chọn Cơ hội">' +
					'<header class="mk-ct-opp-picker__head">' +
					'<div class="mk-ct-opp-picker__head-text">' +
					'<p class="mk-ct-opp-picker__eyebrow">Cơ hội</p>' +
					'<h3>Chọn khách hàng từ Cơ hội</h3>' +
					'<p class="mk-ct-opp-picker__sub">Tìm và chọn để điền nhanh tên, SĐT, địa chỉ, tags.</p>' +
					'</div>' +
					'<button type="button" class="mk-ct-opp-picker__close" data-close="1" aria-label="Đóng">' +
					'<i class="fa fa-times" aria-hidden="true"></i></button>' +
					'</header>' +
					'<div class="mk-ct-opp-picker__search">' +
					'<span class="mk-ct-opp-picker__search-ic" aria-hidden="true"><i class="fa fa-search"></i></span>' +
					'<input type="search" id="mkCtOppPickerQ" class="inputElement" placeholder="Tìm theo tên, SĐT, địa chỉ, phụ trách..." autocomplete="off" />' +
					'</div>' +
					'<div class="mk-ct-opp-picker__meta"><span id="mkCtOppPickerCount">0</span> cơ hội</div>' +
					'<div class="mk-ct-opp-picker__table-wrap">' +
					'<table class="mk-ct-opp-picker__table"><thead><tr>' +
					'<th>Tên khách hàng</th><th>Điện thoại</th><th>Địa chỉ</th><th>Phụ trách</th>' +
					'</tr></thead><tbody id="mkCtOppPickerBody"></tbody></table></div>' +
					'</div></div>'
			);
			$('body').append($modal);
			$modal.on('click', '[data-close="1"]', function (e) {
				e.preventDefault();
				closeOppPicker();
			});
			$modal.on('click', '.mk-ct-opp-picker__row', function (e) {
				e.preventDefault();
				var id = $(this).attr('data-id');
				var opp = findOppById(id);
				if (!opp) return;
				applyOppToForm($f, opp);
				$wrap.find('.mk-ct-cust-suggest').hide().empty();
				closeOppPicker();
			});
			$modal.on('input', '#mkCtOppPickerQ', function () {
				paintOppPickerRows($(this).val());
			});
		}

		function paintOppPickerRows(q) {
			var list = oppSearchCache || [];
			var qq = String(q || '')
				.trim()
				.toLowerCase();
			var items = !qq
				? list.slice(0, 80)
				: list
						.filter(function (o) {
							var hay = [oppLabel(o), o.name, o.phone, o.address, o.owner, (o.tags || []).join(' ')]
								.join(' ')
								.toLowerCase();
							return hay.indexOf(qq) >= 0;
						})
						.slice(0, 80);
			$('#mkCtOppPickerCount').text(String(items.length));
			var $body = $('#mkCtOppPickerBody');
			if (!items.length) {
				$body.html(
					'<tr><td colspan="4" class="mk-ct-opp-picker__empty">' +
						'<div class="mk-ct-opp-picker__empty-card">Không có cơ hội phù hợp.</div>' +
						'</td></tr>'
				);
				return;
			}
			$body.html(
				items
					.map(function (o) {
						var id = o.crmid || o.id || '';
						var label = oppLabel(o) || o.name || '#' + id;
						var initials = String(label || '?')
							.trim()
							.split(/\s+/)
							.slice(0, 2)
							.map(function (w) {
								return w.charAt(0).toUpperCase();
							})
							.join('');
						return (
							'<tr class="mk-ct-opp-picker__row" data-id="' +
							esc(id) +
							'">' +
							'<td><div class="mk-ct-opp-picker__name">' +
							'<span class="mk-ct-opp-picker__avatar" aria-hidden="true">' +
							esc(initials || '?') +
							'</span>' +
							'<span class="mk-ct-opp-picker__name-text">' +
							esc(label) +
							'</span></div></td>' +
							'<td>' +
							esc(o.phone || '—') +
							'</td>' +
							'<td><span class="mk-ct-opp-picker__addr">' +
							esc(o.address || '—') +
							'</span></td>' +
							'<td>' +
							esc(o.owner || '—') +
							'</td></tr>'
						);
					})
					.join('')
			);
		}

		$modal.removeAttr('hidden');
		$('body').addClass('mk-ct-opp-picker-open');
		loadOppSearchCache().always(function () {
			$('#mkCtOppPickerQ').val('').focus();
			paintOppPickerRows('');
		});
	}

	function closeOppPicker() {
		$('#mkCtOppPicker').attr('hidden', 'hidden');
		$('body').removeClass('mk-ct-opp-picker-open');
	}

	function enhanceCustomerNameField($f) {
		var $account = $f.find('[name="account_id"]');
		var $displayStock = $f.find('[name="account_id_display"]');
		var $td =
			$displayStock.closest('td.fieldValue').length
				? $displayStock.closest('td.fieldValue')
				: $account.closest('td.fieldValue');
		if (!$td.length) {
			// Fallback: inject after phone
			var $phoneTd = $f.find('[name="phone"]').closest('td.fieldValue');
			if (!$phoneTd.length) return;
			var $row = $phoneTd.closest('tr');
			$row.after(
				'<tr>' +
					'<td class="fieldLabel"><label>Tên khách hàng <span class="redColor">*</span></label></td>' +
					'<td class="fieldValue" id="mkCtCustNameHost"></td>' +
					'<td class="fieldLabel"></td><td class="fieldValue"></td></tr>'
			);
			$td = $('#mkCtCustNameHost');
		}

		var $label = $td.prev('td.fieldLabel');
		if (!$label.length) {
			$label = $td.closest('tr').find('td.fieldLabel').first();
		}
		if ($label.length) {
			$label.html('Tên khách hàng <span class="redColor">*</span>');
		}

		if ($td.find('.mk-ct-cust-ref').length) return;

		$td.find('.referencefield-wrapper, .input-group').addClass('mk-ct-hide-legacy').hide();
		$displayStock.addClass('mk-ct-hide-legacy').hide();
		$account.attr('type', 'hidden');

		var seed =
			String($f.find('[name="lastname"]').val() || '').trim() ||
			String($displayStock.val() || '').trim();

		var $wrap = $(
			'<div class="mk-ct-cust-ref' +
				(seed ? ' selected' : '') +
				'">' +
				'<div class="mk-ct-cust-bar">' +
				'<input type="text" class="inputElement mk-ct-cust-display" ' +
				'placeholder="Nhập để tìm Cơ hội hoặc gõ tên khách hàng" autocomplete="off" />' +
				'<div class="mk-ct-cust-actions" role="group" aria-label="Tìm Cơ hội">' +
				'<button type="button" class="mk-ct-cust-btn clearReferenceSelection' +
				(seed ? '' : ' hide') +
				'" title="Xóa" aria-label="Xóa"><i class="fa fa-times" aria-hidden="true"></i></button>' +
				'<button type="button" class="mk-ct-cust-btn mk-ct-cust-search" title="Tìm Cơ hội" aria-label="Tìm Cơ hội">' +
				'<i class="fa fa-search" aria-hidden="true"></i></button>' +
				'</div></div>' +
				'<div class="mk-ct-cust-suggest" style="display:none"></div>' +
				'</div>'
		);
		$td.append($wrap);
		var $display = $wrap.find('.mk-ct-cust-display');
		$display.val(seed);
		if (seed) $f.find('[name="lastname"]').val(seed);

		loadOppSearchCache();

		var suggestTimer = null;
		$display.on('input.mkCtCust', function () {
			var val = String($display.val() || '');
			$f.find('[name="lastname"]').val(val);
			$f.find('[name="account_id"]').val('');
			$wrap.toggleClass('selected', !!val.trim());
			$wrap.find('.clearReferenceSelection').toggleClass('hide', !val.trim());
			clearTimeout(suggestTimer);
			suggestTimer = setTimeout(function () {
				loadOppSearchCache().always(function () {
					renderOppSuggest($wrap, filterOpps(val));
				});
			}, 180);
		});

		$display.on('focus.mkCtCust', function () {
			loadOppSearchCache().always(function () {
				renderOppSuggest($wrap, filterOpps($display.val()));
			});
		});

		$wrap.on('click.mkCtCust', '.mk-ct-cust-suggest__item', function (e) {
			e.preventDefault();
			var id = $(this).attr('data-id');
			var opp = findOppById(id);
			if (opp) applyOppToForm($f, opp);
			else {
				var label = $(this).attr('data-name') || '';
				$display.val(label);
				$f.find('[name="lastname"]').val(label);
			}
			$wrap.find('.mk-ct-cust-suggest').hide().empty();
		});

		$wrap.on('click.mkCtCust', '.clearReferenceSelection', function (e) {
			e.preventDefault();
			$display.val('').focus();
			$f.find('[name="lastname"]').val('');
			$f.find('[name="account_id"]').val('');
			ensureHidden($f, 'mk_source_opp_id', '');
			$wrap.removeClass('selected');
			$(this).addClass('hide');
			$wrap.find('.mk-ct-cust-suggest').hide().empty();
		});

		$wrap.on('click.mkCtCust', '.mk-ct-cust-search', function (e) {
			e.preventDefault();
			e.stopPropagation();
			openOppPicker($f, $wrap);
		});

		$(document)
			.off('click.mkCtCustSuggest')
			.on('click.mkCtCustSuggest', function (e) {
				if (!$(e.target).closest('.mk-ct-cust-ref').length) {
					$('.mk-ct-cust-suggest').hide();
				}
			});
	}

	function renderTagsPanel() {
		var $host = $('#mkCtFormHost');
		if (!$host.length) return;
		var catalog = catalogForForm();
		var $panel = $('#mkCtListParity');
		if (!$panel.length) {
			$panel = $(
				'<section class="mk-ct-extras" id="mkCtListParity">' +
					'<div class="mk-ct-extras__divider" aria-hidden="true"></div>' +
					'<div class="mk-ct-extras__head">' +
					'<h4 class="mk-ct-extras__title">Tags</h4>' +
					'<p class="mk-ct-extras__hint">Cùng bộ thẻ hiển thị trên danh sách khách hàng.</p>' +
					'</div>' +
					'<div class="mk-ct-list-parity__body" id="mkCtTagsBody"></div>' +
					'</section>'
			);
			var $main = $host.find('.fieldBlockContainer').not('.mk-ct-hide-block').first();
			if ($main.length) {
				$main.append($panel);
				$main.addClass('mk-ct-block mk-ct-block--merged');
			} else {
				$host.append($panel);
			}
		}
		var body = document.getElementById('mkCtTagsBody');
		if (!body) return;
		body.innerHTML = catalog
			.map(function (g) {
				var chips = (g.tags || [])
					.map(function (item) {
						var on = !!selectedTags[item.key];
						var cls = item.cls || '';
						if (!cls && ref() && ref().tagMeta) {
							cls = (ref().tagMeta(item.key) || {}).cls || '';
						}
						return (
							'<button type="button" class="mk-ct-tag-chip' +
							(cls ? ' ' + esc(cls) : '') +
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
					'<div class="mk-ct-tag-group" data-group="' +
					esc(g.id) +
					'">' +
					'<div class="mk-ct-tag-group__title">' +
					esc(g.label) +
					'</div>' +
					'<div class="mk-ct-tag-group__chips">' +
					chips +
					'</div></div>'
				);
			})
			.join('');
		syncHiddenTags();
	}

	function bindTagsPanel() {
		$(document)
			.off('click.mkCtTags', '#mkCtTagsBody .mk-ct-tag-chip')
			.on('click.mkCtTags', '#mkCtTagsBody .mk-ct-tag-chip', function (e) {
				e.preventDefault();
				var $chip = $(this);
				var key = $chip.attr('data-tag');
				var group = $chip.attr('data-group');
				var turningOn = !$chip.hasClass('is-on');
				if (group && turningOn) {
					$('#mkCtTagsBody .mk-ct-tag-group[data-group="' + group + '"] .mk-ct-tag-chip.is-on').each(
						function () {
							var k = $(this).attr('data-tag');
							if (k) selectedTags[k] = false;
							$(this).removeClass('is-on').attr('aria-pressed', 'false');
						}
					);
				}
				selectedTags[key] = turningOn;
				$chip.toggleClass('is-on', turningOn).attr('aria-pressed', turningOn ? 'true' : 'false');
				syncHiddenTags();
			});
	}

	function seedTags() {
		selectedTags = {};
		readBootTags().forEach(function (tg) {
			var k = normalizeTag(tg);
			if (k && k !== 'da_cap_bang' && k !== 'da_cap_tai_khoan') {
				selectedTags[k] = true;
			}
		});
	}

	function hideLegacyChrome() {
		var $host = $('#mkCtFormHost');
		$host.find('#modnavigator').remove();
		$host.find('.editViewModNavigator, .module-nav').addClass('mk-ct-hide-legacy');
		$host.find('.editViewHeader').addClass('mk-ct-hide-legacy');
		$host.find('.modal-overlay-footer').addClass('mk-ct-form-footer');
		$host.find('.main-container').first().addClass('mk-ct-form-container');
	}

	function styleFieldBlocks() {
		$form()
			.find('.fieldBlockContainer[data-block]')
			.each(function () {
				var $block = $(this);
				if ($block.hasClass('mk-ct-hide-block')) return;
				$block.addClass('mk-ct-block');
				var blockKey = $block.attr('data-block') || '';
				var $header = $block.find('.fieldBlockHeader').first();
				$header.addClass('mk-ct-block__header');
				if (!$header.find('.mk-ct-block__icon').length && BLOCK_ICONS[blockKey]) {
					$header.prepend(
						$('<span>', { class: 'mk-ct-block__icon', 'aria-hidden': 'true' }).append(
							$('<i>', { class: 'fa ' + BLOCK_ICONS[blockKey] })
						)
					);
				}
				$block.find('> hr').addClass('mk-ct-hide-legacy');
				$block.find('table.table-borderless').addClass('mk-ct-fields-table');
			});
	}

	function relabelListFields() {
		var $f = $form();
		var map = {
			phone: 'Số điện thoại',
			email: 'Email liên hệ',
			da_cap_bang: 'Đã cấp bằng',
			da_cap_tai_khoan: 'Đã cấp tài khoản',
			thoigian_dangky: 'Thời gian đăng ký',
			thoigian_pcth: 'Thời gian tham gia PCTH',
			thoigian_mqbb: 'Thời gian tham gia MQBB',
			assigned_user_id: 'Phụ trách'
		};
		Object.keys(map).forEach(function (name) {
			var $input = $f.find('[name="' + name + '"]').first();
			if (!$input.length) return;
			var $td = $input.closest('td.fieldValue');
			var $label = $td.prev('td.fieldLabel');
			if (!$label.length) return;
			var req = $label.find('.redColor, .required').length ? ' <span class="redColor">*</span>' : '';
			$label.html(map[name] + req);
		});
	}

	function fieldNameOfValueTd($val) {
		var $ctrl = $val.find('[name]').filter(function () {
			var n = baseFieldName($(this).attr('name') || '');
			return !!n && n !== 'popupReferenceModule';
		}).first();
		if (!$ctrl.length && $val.find('.mk-ct-cust-ref').length) return 'account_id';
		return baseFieldName($ctrl.attr('name') || '');
	}

	function rebuildSlimLayout($f) {
		var $block = $f.find('.fieldBlockContainer.mk-ct-block').not('.mk-ct-hide-block').first();
		if (!$block.length) return;
		var $tbody = $block.find('> table > tbody').first();
		if (!$tbody.length) return;

		var byName = {};
		$tbody.find('td.fieldValue').not('.mk-ct-hide-field').each(function () {
			var $val = $(this);
			var name = fieldNameOfValueTd($val);
			if (!name || byName[name]) return;
			var $lab = $val.prev('td.fieldLabel');
			byName[name] = { label: $lab, value: $val };
		});

		var ordered = [];
		FIELD_LAYOUT_ORDER.forEach(function (name) {
			if (byName[name]) ordered.push({ name: name, pair: byName[name] });
		});
		Object.keys(byName).forEach(function (name) {
			if (FIELD_LAYOUT_ORDER.indexOf(name) < 0) {
				ordered.push({ name: name, pair: byName[name] });
			}
		});
		if (!ordered.length) return;

		var $rows = $();
		ordered.forEach(function (item) {
			var $lab = item.pair.label;
			var $val = item.pair.value;
			if (!$lab.length) {
				$lab = $('<td class="fieldLabel"></td>');
			}
			$lab.removeClass('mk-ct-hide-field');
			$val.removeClass('mk-ct-hide-field');
			if ($lab[0] && $lab[0].style) {
				$lab[0].style.removeProperty('display');
				$lab[0].style.removeProperty('height');
				$lab[0].style.removeProperty('visibility');
			}
			if ($val[0] && $val[0].style) {
				$val[0].style.removeProperty('display');
				$val[0].style.removeProperty('height');
				$val[0].style.removeProperty('visibility');
			}
			var $tr = $('<tr class="mk-ct-field-row"></tr>');
			$tr.append($lab).append($val);
			$rows = $rows.add($tr);
		});

		$tbody.find('> tr').remove();
		$tbody.append($rows);
		$block.addClass('mk-ct-block--slim');
		slimLayoutDone = true;
	}

	function markPainted() {
		document.documentElement.classList.add('mk-ct-painted');
	}

	function triggerSave() {
		syncHiddenTags();
		softDefaultRequired($form());
		var $save = $form().find('.saveButton').first();
		if ($save.length) {
			$save.trigger('click');
			return;
		}
		$form().trigger('submit');
	}

	function bindActions() {
		$('#mkCtSaveTop')
			.off('click.mkCtSave')
			.on('click.mkCtSave', function (e) {
				e.preventDefault();
				triggerSave();
			});

		$form()
			.off('submit.mkCtTags')
			.on('submit.mkCtTags', function () {
				syncHiddenTags();
				softDefaultRequired($(this));
			});

		$(document)
			.off('keydown.mkCtCreate')
			.on('keydown.mkCtCreate', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
					if (!$(e.target).closest('#mkCtFormHost').length) {
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
		relabelListFields();
		enhanceCustomerNameField($form());
		rebuildSlimLayout($form());
		renderTagsPanel();
		bindTagsPanel();
		bindActions();
		markPainted();
	}

	function init() {
		if (!isScoped()) {
			markPainted();
			return;
		}
		seedTags();
		runEnhancements();
		setTimeout(runEnhancements, 150);
		setTimeout(function () {
			runEnhancements();
			markPainted();
		}, 600);
		// Safety: never leave page invisible if JS errors mid-way
		setTimeout(markPainted, 1200);

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

	window.__mkCtCreateBuild = MK_BUILD;
})($);
