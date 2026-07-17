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
			'.mk-contact-detail-related-tabs li[data-module] > a .numberCircle'
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
				done(err, res);
			});
			return;
		}
		$.ajax({ url: 'index.php', type: 'POST', dataType: 'json', data: data })
			.done(function (r) { done(null, r && r.result ? r.result : r); })
			.fail(function () { done({ message: 'Không kết nối được máy chủ.' }, null); });
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
		$rights.toggleClass('is-expired', !!summary.first_on && !summary.retake_available && !summary.retake_used && !!summary.until_on);
		$rights.toggleClass('is-used', !!summary.retake_used);

		var $hint = $panel.find('.mk-contact-class-panel__hint');
		if (!$hint.length) {
			$hint = $('<p class="mk-contact-class-panel__hint"></p>');
			$rights.after($hint);
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
				var btn = log.show_retake_btn
					? '<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--retake mk-contact-class-panel__retake-open" title="Chọn ngày học lại">' +
						'<i class="fa fa-refresh" aria-hidden="true"></i><span>Học lại</span></button>'
					: '';
				return '<li class="mk-contact-class-panel__item' + retakeClass + '" data-id="' + (log.id || '') + '" data-kind="' + (log.kind || 'register') + '">' +
					'<div class="mk-contact-class-panel__item-main">' +
					'<span class="mk-contact-class-panel__n' + retakeClass + '">' + $('<div/>').text(badge).html() + '</span>' +
					'<span class="mk-contact-class-panel__text">' + $('<div/>').text(log.label || '').html() + '</span>' +
					'</div>' + btn +
					'</li>';
			}).join(''));
		}

		var $retakeForm = $panel.find('[data-retake-form="1"]');
		if (summary.retake_available) {
			if (!$retakeForm.length) {
				$retakeForm = $(
					'<div class="mk-contact-class-panel__retake-form hide" data-retake-form="1">' +
						'<label class="mk-contact-class-panel__retake-label">Chọn ngày Học lại lần 1</label>' +
						'<div class="mk-contact-class-panel__retake-row">' +
						'<input type="date" class="mk-contact-class-panel__date mk-contact-class-panel__retake-date inputElement" aria-label="Chọn ngày học lại" />' +
						'<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--retake mk-contact-class-panel__retake-save">' +
						'<i class="fa fa-check" aria-hidden="true"></i><span>Lưu học lại</span></button>' +
						'<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--outline mk-contact-class-panel__retake-cancel"><span>Hủy</span></button>' +
						'</div></div>'
				);
				$list.after($retakeForm);
			}
			$retakeForm.addClass('hide');
			var $rDate = $retakeForm.find('.mk-contact-class-panel__retake-date');
			if (summary.retake_date_min) {
				$rDate.attr('min', summary.retake_date_min);
			} else {
				$rDate.removeAttr('min');
			}
			if (summary.retake_date_max) {
				$rDate.attr('max', summary.retake_date_max);
			} else {
				$rDate.removeAttr('max');
			}
			$rDate.val('');
		} else {
			$retakeForm.remove();
		}

		var $add = $panel.find('.mk-contact-class-panel__add');
		var $date = $panel.find('.mk-contact-class-panel__register-date');
		if (!$date.length) {
			$date = $panel.find('.mk-contact-class-panel__add .mk-contact-class-panel__date').first();
		}
		if (summary.can_add === false) {
			$add.hide();
		} else {
			$add.show();
			if (summary.date_min) {
				$date.attr('min', summary.date_min);
			} else {
				$date.removeAttr('min');
			}
			if (summary.date_max) {
				$date.attr('max', summary.date_max);
			} else {
				$date.removeAttr('max');
			}
			$date.val('');
			$add.find('.mk-contact-class-panel__add-btn span').text(
				summary.first_on ? 'Đăng ký lần mới' : 'Thêm đăng ký'
			);
		}
	}

	function postClassReg($panel, recordId, dateVal, kind, okMsg, $busyBtn) {
		if ($busyBtn && $busyBtn.length) {
			$busyBtn.prop('disabled', true);
		}
		apiPost({
			module: 'Contacts',
			action: 'ModernApi',
			mode: 'class_reg_add',
			record: recordId,
			registered_on: dateVal,
			entry_kind: kind || 'register'
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
			postClassReg($panel, recordId, dateVal, 'register', 'Đã ghi nhận đăng ký học.', $btn);
		});

		$panel.on('click.mkClassReg', '.mk-contact-class-panel__retake-open', function (e) {
			e.preventDefault();
			var $form = $panel.find('[data-retake-form="1"]');
			if (!$form.length) {
				notifyError('Không còn quyền học lại.');
				return;
			}
			$form.removeClass('hide');
			$form.find('.mk-contact-class-panel__retake-date').focus();
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
			postClassReg($panel, recordId, dateVal, 'retake', 'Đã ghi nhận Học lại lần 1.', $btn);
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
				if (creds) {
					if (creds.da_cap_bang) {
						$panel.find('[name="da_cap_bang"]').val(creds.da_cap_bang);
					}
					if (creds.da_cap_tai_khoan) {
						$panel.find('[name="da_cap_tai_khoan"]').val(creds.da_cap_tai_khoan);
					}
				}
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

	function boot() {
		if (!isScopedBody()) {
			return;
		}
		refreshRelatedBadges();
		bindClassPanel();
		injectCccdField();

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.summaryview.load', function () {
				refreshRelatedBadges();
				injectCccdField();
			});
			app.event.on('post.detailedview.load', function () {
				refreshRelatedBadges();
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
