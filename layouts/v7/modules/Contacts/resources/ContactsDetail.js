/**
 * Contacts Detail: class registration log + credential status panel.
 * Scope: body[data-module="Contacts"][data-view="Detail"]
 */
(function ($) {
	'use strict';

	function isScopedBody() {
		var b = document.body;
		return !!(b
			&& b.getAttribute('data-module') === 'Contacts'
			&& b.getAttribute('data-view') === 'Detail'
			&& (b.getAttribute('data-app') === 'SALES' || b.getAttribute('data-app') === 'MARKETING'));
	}

	function refreshRelatedBadges() {
		var nodes = document.querySelectorAll(
			'.mk-contact-detail-related-tabs li[data-module] > a .numberCircle, .mk-contact-related-icons-row li[data-module] > a .numberCircle'
		);
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			var raw = (el.textContent || '').trim();
			var count = parseInt(raw, 10);
			if (isNaN(count)) {
				count = 0;
			}
			el.setAttribute('data-count', String(count));
			if (count === 0) {
				el.classList.add('hide');
			}
		}
	}

	function notifyError(msg) {
		if (typeof app !== 'undefined' && app.helper && app.helper.showErrorNotification) {
			app.helper.showErrorNotification({ message: String(msg) });
		} else {
			window.alert(String(msg));
		}
	}

	function notifyOk(msg) {
		if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
			app.helper.showSuccessNotification({ message: String(msg) });
		}
	}

	function apiPost(data, done) {
		if (typeof app !== 'undefined' && app.request && app.request.post) {
			app.request.post({ data: data }).then(function (err, res) {
				if (err) {
					done(err, null);
					return;
				}
				if (res && res.success === false) {
					done((res.error && res.error.message) || res.error || res, res);
					return;
				}
				done(null, res);
			});
			return;
		}
		$.ajax({ url: 'index.php', type: 'POST', dataType: 'json', data: data })
			.done(function (r) {
				if (r && r.success === false) {
					done((r.error && r.error.message) || r.error || r, null);
					return;
				}
				done(null, r && r.result ? r.result : r);
			})
			.fail(function (xhr) {
				done({ message: xhr && xhr.responseText ? xhr.responseText : 'Không kết nối được máy chủ.' }, null);
			});
	}

	function decodeEntities(s) {
		if (s == null || s === '') return '';
		var str = String(s);
		if (str.indexOf('&') === -1) return str;
		var ta = document.createElement('textarea');
		ta.innerHTML = str;
		var once = ta.value;
		if (once.indexOf('&') !== -1) {
			ta.innerHTML = once;
			return ta.value;
		}
		return once;
	}

	function setCredentialSelect($select, value) {
		if (!$select || !$select.length) return;
		var v = decodeEntities(value == null ? '' : String(value));
		if (!v) return;
		if ($select.find('option').filter(function () {
			return decodeEntities($(this).val()) === v;
		}).length) {
			$select.find('option').each(function () {
				if (decodeEntities($(this).val()) === v) {
					$select.val($(this).val());
					return false;
				}
			});
			return;
		}
		$select.val(v);
	}

	function applyCredentialState($panel, creds) {
		if (!$panel.length || !creds) return;
		setCredentialSelect($panel.find('[name="da_cap_bang"]'), creds.da_cap_bang);
		setCredentialSelect($panel.find('[name="da_cap_tai_khoan"]'), creds.da_cap_tai_khoan);
	}

	function getSelectedClassCode($panel) {
		var $sel = $panel.find('.mk-contact-class-panel__class-select, [name="class_code"]').first();
		return String($sel.val() || 'mqbb').trim() || 'mqbb';
	}

	function applyRegisterDateMin($panel, summary) {
		var $date = $panel.find('.mk-contact-class-panel__register-date').first();
		if (!$date.length) return;
		var code = getSelectedClassCode($panel);
		var byClass = (summary && summary.by_class) || {};
		var meta = byClass[code] || {};
		if (meta.date_min) {
			$date.attr('min', meta.date_min);
		} else {
			$date.removeAttr('min');
		}
		if (meta.date_max) {
			$date.attr('max', meta.date_max);
		} else {
			$date.removeAttr('max');
		}
	}

	function renderClassReg($panel, summary) {
		if (!$panel.length || !summary) {
			return;
		}
		$panel.data('mkClassRegSummary', summary);

		var $rights = $panel.find('.mk-contact-class-panel__rights');
		if (!$rights.length) {
			$rights = $('<div class="mk-contact-class-panel__rights"></div>');
			$panel.find('.mk-contact-class-panel__title').first().after($rights);
		}
		$rights.text(summary.rights_label || '');
		$rights.toggleClass('is-active', !!summary.retake_available);

		var $warn = $panel.find('.mk-contact-class-panel__warning');
		if (summary.warning) {
			if (!$warn.length) {
				$warn = $('<div class="mk-contact-class-panel__warning" role="status"></div>');
				$rights.after($warn);
			}
			$warn.text(summary.warning).show();
		} else if ($warn.length) {
			$warn.hide().text('');
		}

		var $hint = $panel.find('.mk-contact-class-panel__hint');
		if (!$hint.length) {
			$hint = $('<p class="mk-contact-class-panel__hint"></p>');
			($warn.length ? $warn : $rights).after($hint);
		}
		$hint.text(summary.hint || '');

		var $list = $panel.find('.mk-contact-class-panel__list');
		var logs = summary.logs || [];
		if (!logs.length) {
			$list.html('<li class="mk-contact-class-panel__empty">Chưa có lần đăng ký nào</li>');
		} else {
			$list.html(logs.map(function (log) {
				var badge = log.badge || ('Lần ' + (log.n || ''));
				var retakeClass = log.is_retake ? ' is-retake' : '';
				var classCode = log.class_code || 'mqbb';
				var classLabel = log.class_label || classCode.toUpperCase();
				var btn = log.show_retake_btn
					? '<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--retake mk-contact-class-panel__retake-open" data-class-code="' + classCode + '" title="Chọn ngày học lại lớp ' + classLabel + '">' +
						'<i class="fa fa-refresh" aria-hidden="true"></i><span>Học lại</span></button>'
					: '';
				return '<li class="mk-contact-class-panel__item' + retakeClass + '" data-id="' + (log.id || '') + '" data-kind="' + (log.kind || 'register') + '" data-class-code="' + classCode + '">' +
					'<div class="mk-contact-class-panel__item-main">' +
					'<span class="mk-contact-class-panel__n' + retakeClass + '">' + $('<div/>').text(badge).html() + '</span>' +
					'<span class="mk-contact-class-panel__class-tag">' + $('<div/>').text(classLabel).html() + '</span>' +
					'<span class="mk-contact-class-panel__text">' + $('<div/>').text(log.label || '').html() + '</span>' +
					'</div>' + btn +
					'</li>';
			}).join(''));
		}

		var $retakeForm = $panel.find('[data-retake-form="1"]');
		if (!$retakeForm.length) {
			$retakeForm = $(
				'<div class="mk-contact-class-panel__retake-form hide" data-retake-form="1" data-class-code="">' +
					'<label class="mk-contact-class-panel__retake-label">Chọn ngày Học lại</label>' +
					'<div class="mk-contact-class-panel__retake-row">' +
					'<input type="date" class="mk-contact-class-panel__date mk-contact-class-panel__retake-date inputElement" aria-label="Chọn ngày học lại" />' +
					'<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--retake mk-contact-class-panel__retake-save">' +
					'<i class="fa fa-check" aria-hidden="true"></i><span>Lưu học lại</span></button>' +
					'<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--outline mk-contact-class-panel__retake-cancel"><span>Hủy</span></button>' +
					'</div></div>'
			);
			$list.after($retakeForm);
		}
		$retakeForm.addClass('hide').attr('data-class-code', '');

		var $add = $panel.find('.mk-contact-class-panel__add');
		if (summary.can_add === false) {
			$add.hide();
		} else {
			$add.show();
			var $classSel = $add.find('.mk-contact-class-panel__class-select');
			if ($classSel.length && summary.class_options && summary.class_options.length) {
				var current = $classSel.val() || 'mqbb';
				$classSel.html(summary.class_options.map(function (opt) {
					return '<option value="' + opt.code + '">' + $('<div/>').text(opt.label).html() + '</option>';
				}).join(''));
				if ($classSel.find('option[value="' + current + '"]').length) {
					$classSel.val(current);
				}
			}
			applyRegisterDateMin($panel, summary);
			$panel.find('.mk-contact-class-panel__register-date').val('');
		}
	}

	function postClassReg($panel, recordId, dateVal, kind, classCode, okMsg, $busyBtn) {
		if ($busyBtn && $busyBtn.length) {
			$busyBtn.prop('disabled', true);
		}
		apiPost({
			module: 'Contacts',
			action: 'ModernApi',
			mode: 'class_reg_add',
			record: recordId,
			registered_on: dateVal,
			entry_kind: kind || 'register',
			class_code: classCode || getSelectedClassCode($panel)
		}, function (err, res) {
			if ($busyBtn && $busyBtn.length) {
				$busyBtn.prop('disabled', false);
			}
			if (err || !res || res.success === false) {
				var msg = (err && err.message) || (res && res.error) || 'Không thêm được đăng ký.';
				if (typeof msg === 'object' && msg.message) {
					msg = msg.message;
				}
				notifyError(msg);
				return;
			}
			var summary = res.class_reg || (res.result && res.result.class_reg) || null;
			if (summary) {
				renderClassReg($panel, summary);
			}
			notifyOk(okMsg || 'Đã ghi nhận.');
		});
	}

	function bindClassPanel() {
		var $panel = $('.mk-contact-class-panel[data-mk-class-panel="1"]');
		if (!$panel.length) {
			return;
		}
		var recordId = parseInt($panel.attr('data-record-id'), 10) || 0;
		if (recordId <= 0) {
			return;
		}

		$panel.off('click.mkClassReg').on('click.mkClassReg', '.mk-contact-class-panel__add-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $date = $panel.find('.mk-contact-class-panel__register-date');
			if (!$date.length) {
				$date = $panel.find('.mk-contact-class-panel__add .mk-contact-class-panel__date').first();
			}
			var dateVal = String($date.val() || '').trim();
			if (!dateVal) {
				notifyError('Vui lòng chọn ngày đăng ký trên lịch.');
				$date.focus();
				return;
			}
			var classCode = getSelectedClassCode($panel);
			postClassReg($panel, recordId, dateVal, 'register', classCode, 'Đã ghi nhận đăng ký học.', $btn);
		});

		$panel.off('change.mkClassReg').on('change.mkClassReg', '.mk-contact-class-panel__class-select', function () {
			var summary = $panel.data('mkClassRegSummary');
			applyRegisterDateMin($panel, summary);
			$panel.find('.mk-contact-class-panel__register-date').val('');
		});

		$panel.on('click.mkClassReg', '.mk-contact-class-panel__retake-open', function (e) {
			e.preventDefault();
			var $item = $(this).closest('.mk-contact-class-panel__item');
			var classCode = $item.attr('data-class-code') || $(this).attr('data-class-code') || 'mqbb';
			var summary = $panel.data('mkClassRegSummary') || {};
			var byClass = summary.by_class || {};
			var meta = byClass[classCode] || {};
			var $form = $panel.find('[data-retake-form="1"]');
			if (!$form.length) {
				notifyError('Không còn quyền học lại.');
				return;
			}
			$form.attr('data-class-code', classCode);
			var classLabel = (meta.class_label || classCode.toUpperCase());
			$form.find('.mk-contact-class-panel__retake-label').text('Chọn ngày Học lại lớp ' + classLabel);
			var $rDate = $form.find('.mk-contact-class-panel__retake-date');
			if (meta.retake_date_min) {
				$rDate.attr('min', meta.retake_date_min);
			} else if (meta.date_min) {
				$rDate.attr('min', meta.date_min);
			} else {
				$rDate.removeAttr('min');
			}
			if (meta.retake_date_max) {
				$rDate.attr('max', meta.retake_date_max);
			} else {
				$rDate.removeAttr('max');
			}
			$rDate.val('');
			$form.removeClass('hide');
			$rDate.focus();
		});

		$panel.on('click.mkClassReg', '.mk-contact-class-panel__retake-cancel', function (e) {
			e.preventDefault();
			var $form = $panel.find('[data-retake-form="1"]');
			$form.addClass('hide');
			$form.find('.mk-contact-class-panel__retake-date').val('');
		});

		$panel.on('click.mkClassReg', '.mk-contact-class-panel__retake-save', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $date = $panel.find('.mk-contact-class-panel__retake-date');
			var dateVal = String($date.val() || '').trim();
			if (!dateVal) {
				notifyError('Vui lòng chọn ngày học lại trên lịch.');
				$date.focus();
				return;
			}
			var $form = $panel.find('[data-retake-form="1"]');
			var classCode = $form.attr('data-class-code') || 'mqbb';
			postClassReg($panel, recordId, dateVal, 'retake', classCode, 'Đã ghi nhận Học lại.', $btn);
		});

		$panel.off('click.mkCreds').on('click.mkCreds', '.mk-contact-class-panel__creds-save', function (e) {
			e.preventDefault();
			var $btn = $(this);
			$btn.prop('disabled', true);
			apiPost({
				module: 'Contacts',
				action: 'ModernApi',
				mode: 'credential_save',
				record: recordId,
				da_cap_bang: $panel.find('[name="da_cap_bang"]').val(),
				da_cap_tai_khoan: $panel.find('[name="da_cap_tai_khoan"]').val()
			}, function (err, res) {
				$btn.prop('disabled', false);
				if (err || !res || res.success === false) {
					var msg = (err && err.message) || (res && res.error) || 'Không lưu được trạng thái.';
					if (typeof msg === 'object' && msg.message) {
						msg = msg.message;
					}
					notifyError(msg);
					return;
				}
				var creds = res.credentials || (res.result && res.result.credentials) || null;
				applyCredentialState($panel, creds);
				notifyOk('Đã lưu trạng thái cấp bằng / tài khoản.');
			});
		});
	}

	function escHtml(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function injectCccdField() {
		var cccd = typeof window.MK_CONTACT_CCCD === 'string' ? window.MK_CONTACT_CCCD.trim() : '';
		if (!cccd) {
			return;
		}
		if (document.querySelector('.mk-contact-cccd-row, [data-name="mk_cccd"], [data-field="mk_cccd"]')) {
			return;
		}
		var display = escHtml(cccd);
		var label = 'Căn cước công dân';

		// Full detail block ("Thông tin cơ bản")
		var $detailBody = $('#detailView .blockData table.detailview-table > tbody').first();
		if ($detailBody.length) {
			var $last = $detailBody.children('tr').last();
			var cells = $last.children('td').length;
			if (cells >= 4) {
				$detailBody.append(
					'<tr class="mk-contact-cccd-row">' +
						'<td class="fieldLabel" data-name="mk_cccd"><label>' + label + '</label></td>' +
						'<td class="fieldValue" data-name="mk_cccd"><span class="value" data-field-type="string">' + display + '</span></td>' +
						'<td class="fieldLabel"></td><td class="fieldValue"></td>' +
					'</tr>'
				);
			} else if (cells === 2) {
				$detailBody.append(
					'<tr class="mk-contact-cccd-row">' +
						'<td class="fieldLabel" data-name="mk_cccd"><label>' + label + '</label></td>' +
						'<td class="fieldValue" data-name="mk_cccd"><span class="value">' + display + '</span></td>' +
					'</tr>'
				);
			} else {
				$detailBody.append(
					'<tr class="mk-contact-cccd-row">' +
						'<td class="fieldLabel" data-name="mk_cccd"><label>' + label + '</label></td>' +
						'<td class="fieldValue" colspan="3" data-name="mk_cccd"><span class="value">' + display + '</span></td>' +
					'</tr>'
				);
			}
		}

		// Summary key fields card
		var $summaryBody = $('.mk-contact-detail-kv-wrap .summary-table > tbody').first();
		if ($summaryBody.length && !$summaryBody.find('.mk-contact-cccd-row').length) {
			$summaryBody.prepend(
				'<tr class="summaryViewEntries mk-contact-cccd-row">' +
					'<td class="fieldLabel"><label>' + label + '</label></td>' +
					'<td class="fieldValue"><div class="value">' + display + '</div></td>' +
				'</tr>'
			);
		}
	}

	function initRelatedTabsToggle() {
		if (typeof window.MkSalesRelatedTabsToggle === 'function') {
			window.MkSalesRelatedTabsToggle();
		}
	}

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		refreshRelatedBadges();
		initRelatedTabsToggle();
		bindClassPanel();
		injectCccdField();

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.summaryview.load', function () {
				refreshRelatedBadges();
				initRelatedTabsToggle();
				bindClassPanel();
				injectCccdField();
			});
			app.event.on('post.detailedview.load', function () {
				refreshRelatedBadges();
				initRelatedTabsToggle();
				injectCccdField();
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(typeof jQuery !== 'undefined' ? jQuery : null);
