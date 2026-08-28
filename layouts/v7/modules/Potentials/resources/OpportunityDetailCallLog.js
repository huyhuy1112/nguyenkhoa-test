/**
 * Opportunity Detail — Gọi khách hàng + Lịch sử tương tác (Lead → Opp).
 * Initial log is embedded in #mk-opp-interaction-log-boot (no blocking AJAX).
 */
(function () {
	'use strict';

	var cachedLog = null;
	var loading = false;
	var bound = false;
	var contactEnhanceTries = 0;

	function byId(id) {
		return document.getElementById(id);
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

	function esc(s) {
		var d = document.createElement('div');
		d.textContent = decodeEntities(s);
		return d.innerHTML;
	}

	function recordId() {
		var el = byId('recordId');
		return el && el.value ? String(el.value) : '';
	}

	function isOppDetail() {
		var body = document.body;
		return (
			body &&
			body.getAttribute('data-module') === 'Potentials' &&
			body.getAttribute('data-view') === 'Detail'
		);
	}

	function toast(msg, type) {
		if (typeof app !== 'undefined' && app.helper) {
			if (type === 'error' && app.helper.showErrorNotification) {
				app.helper.showErrorNotification({ message: msg });
				return;
			}
			if (app.helper.showSuccessNotification) {
				app.helper.showSuccessNotification({ message: msg });
				return;
			}
		}
		try {
			window.alert(msg);
		} catch (e) {
			/* ignore */
		}
	}

	/**
	 * Prefer direct jQuery.ajax with timeout so Opp detail never hangs.
	 * Falls back to app.request.post (Vtiger err,res) without native Promise traps.
	 */
	function apiPost(data, timeoutMs) {
		timeoutMs = timeoutMs || 10000;
		return new Promise(function (resolve, reject) {
			var settled = false;
			function done(ok, payload) {
				if (settled) return;
				settled = true;
				if (ok) resolve(payload || {});
				else reject(payload || 'request failed');
			}

			if (typeof jQuery !== 'undefined' && jQuery.ajax) {
				jQuery
					.ajax({
						url: 'index.php',
						type: 'POST',
						dataType: 'json',
						timeout: timeoutMs,
						data: data,
					})
					.done(function (raw) {
						if (raw && raw.success === false) {
							done(false, (raw.error && raw.error.message) || 'API error');
							return;
						}
						var res = raw && Object.prototype.hasOwnProperty.call(raw, 'result') ? raw.result : raw;
						done(true, res || {});
					})
					.fail(function (xhr, status) {
						done(false, status === 'timeout' ? 'timeout' : (xhr && xhr.responseText) || 'request failed');
					});
				return;
			}

			if (typeof app !== 'undefined' && app.request && app.request.post) {
				var timer = window.setTimeout(function () {
					done(false, 'timeout');
				}, timeoutMs);
				try {
					app.request.post({ data: data }).then(function (err, res) {
						window.clearTimeout(timer);
						if (err) {
							done(false, err);
							return;
						}
						done(true, res || {});
					});
				} catch (e) {
					window.clearTimeout(timer);
					done(false, e);
				}
				return;
			}
			done(false, 'Không gọi được API.');
		});
	}

	function digitsOnly(phone) {
		return String(phone || '').replace(/[^\d+]/g, '');
	}

	function dialPhone(phone, crmId) {
		var raw = String(phone || '').trim();
		if (!raw) {
			toast('Chưa có số điện thoại khách hàng.', 'error');
			return;
		}
		var num = digitsOnly(raw);
		if (
			typeof Vtiger_PBXManager_Js !== 'undefined' &&
			typeof Vtiger_PBXManager_Js.registerPBXOutboundCall === 'function' &&
			num
		) {
			try {
				Vtiger_PBXManager_Js.registerPBXOutboundCall(num, crmId || recordId());
				return;
			} catch (e) {
				/* fall through */
			}
		}
		window.location.href = 'tel:' + encodeURIComponent(num || raw);
	}

	function typeLabel(type) {
		if (type === 'call') return 'Cuộc gọi';
		if (type === 'meeting') return 'Họp';
		if (type === 'stage') return 'Giai đoạn';
		if (type === 'note') return 'Ghi chú';
		return 'Công việc';
	}

	function readBootLog() {
		var el = byId('mk-opp-interaction-log-boot');
		if (!el) return null;
		var raw = (el.textContent || el.innerText || '').trim();
		if (!raw) return null;
		try {
			return JSON.parse(raw);
		} catch (e) {
			return null;
		}
	}

	function renderLog(log) {
		cachedLog = log || null;
		var body = byId('mk-opp-interaction-log-body');
		if (!body) return;
		var items = (log && log.items) || [];
		if (!items.length) {
			body.innerHTML =
				'<p class="mk-opp-interaction-log__empty">Chưa có lịch sử tương tác. Gọi khách hoặc ghi cuộc gọi để bắt đầu.</p>';
			return;
		}
		body.innerHTML =
			'<ul class="mk-opp-ilog">' +
			items
				.map(function (it) {
					var type = it.type || 'task';
					var stage = it.stage || 'opportunity';
					var meta =
						'<span class="mk-opp-ilog__stage mk-opp-ilog__stage--' +
						esc(stage) +
						'">' +
						esc(it.stage_label || stage) +
						'</span>' +
						'<span>' +
						esc(typeLabel(type)) +
						'</span>' +
						(it.at_label ? '<span>' + esc(it.at_label) + '</span>' : '') +
						(it.by ? '<span>' + esc(it.by) + '</span>' : '');
					return (
						'<li class="mk-opp-ilog__item mk-opp-ilog__item--' +
						esc(type) +
						'">' +
						'<span class="mk-opp-ilog__dot" aria-hidden="true"></span>' +
						'<div class="mk-opp-ilog__meta">' +
						meta +
						'</div>' +
						'<p class="mk-opp-ilog__title">' +
						esc(it.title || '') +
						'</p>' +
						(it.text
							? '<p class="mk-opp-ilog__text">' + esc(it.text) + '</p>'
							: '') +
						'</li>'
					);
				})
				.join('') +
			'</ul>';
	}

	function loadLog(force) {
		var id = recordId();
		var body = byId('mk-opp-interaction-log-body');
		if (!id || !body || (loading && !force)) return;
		loading = true;
		apiPost(
			{
				module: 'Potentials',
				action: 'CommerceApi',
				mode: 'interaction_log',
				record: id,
			},
			8000
		)
			.then(function (res) {
				loading = false;
				var log = res && res.log ? res.log : null;
				if (!log && res && Array.isArray(res.items)) {
					log = res;
				}
				if (!log) {
					if (!cachedLog) {
						body.innerHTML =
							'<p class="mk-opp-interaction-log__empty">Không tải được lịch sử.</p>';
					}
					return;
				}
				renderLog(log);
				scheduleEnhanceContacts();
			})
			.catch(function () {
				loading = false;
				if (!cachedLog && body) {
					body.innerHTML =
						'<p class="mk-opp-interaction-log__empty">Không tải được lịch sử tương tác.</p>';
				}
			});
	}

	function enhanceContactCallButtons(log) {
		if (typeof jQuery === 'undefined') return false;
		var phone = log && log.phone ? String(log.phone).trim() : '';
		if (!phone) return true;
		var contactId = log && log.contact_id ? String(log.contact_id) : '';
		var $host = jQuery('.widgetContainer_contacts .widget_contents');
		if (!$host.length) return false;
		if (!$host.children().length) return false;

		if ($host.find('.mk-opp-contact-call').length) return true;

		var added = false;
		$host.find('tr, .listViewEntries').each(function () {
			var $row = jQuery(this);
			if ($row.find('.mk-opp-contact-call').length) return;
			var phoneCell = $row
				.find('td')
				.filter(function () {
					return /\d{8,}/.test(jQuery(this).text().replace(/\s+/g, ''));
				})
				.first();
			var $btn = jQuery(
				'<a href="tel:' +
					esc(digitsOnly(phone)) +
					'" class="mk-opp-contact-call" data-mk-opp-call="1" title="Gọi ' +
					esc(phone) +
					'">📞 Gọi</a>'
			);
			$btn.on('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				dialPhone(phone, contactId || recordId());
			});
			if (phoneCell.length) {
				phoneCell.append($btn);
				added = true;
			}
		});

		if (!added) {
			var $name = $host.find('a[href*="Contacts"], .listViewEntriesName').first();
			if ($name.length) {
				var $btn2 = jQuery(
					'<a href="tel:' +
						esc(digitsOnly(phone)) +
						'" class="mk-opp-contact-call" data-mk-opp-call="1">📞 Gọi</a>'
				);
				$btn2.on('click', function (e) {
					e.preventDefault();
					dialPhone(phone, contactId || recordId());
				});
				$name.after($btn2);
				added = true;
			}
		}
		return added;
	}

	function scheduleEnhanceContacts() {
		contactEnhanceTries = 0;
		function tick() {
			contactEnhanceTries += 1;
			if (!cachedLog) return;
			if (enhanceContactCallButtons(cachedLog)) return;
			if (contactEnhanceTries < 8) {
				window.setTimeout(tick, 400);
			}
		}
		window.setTimeout(tick, 300);
	}

	function openLogCallModal() {
		var existing = byId('mkOppCallLogModal');
		if (existing) existing.parentNode.removeChild(existing);

		var wrap = document.createElement('div');
		wrap.id = 'mkOppCallLogModal';
		wrap.className = 'mk-opp-call-modal-backdrop';
		wrap.innerHTML =
			'<div class="mk-opp-call-modal" role="dialog" aria-modal="true" aria-labelledby="mkOppCallLogTitle">' +
			'<header class="mk-opp-call-modal__head">' +
			'<h3 id="mkOppCallLogTitle">Ghi cuộc gọi</h3>' +
			'<button type="button" class="mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost" data-mk-close="1" aria-label="Đóng">×</button>' +
			'</header>' +
			'<div class="mk-opp-call-modal__body">' +
			'<label class="mk-opp-call-modal__label" for="mkOppCallResult">Kết quả cuộc gọi</label>' +
			'<select id="mkOppCallResult">' +
			'<option value="Nghe máy">Nghe máy</option>' +
			'<option value="Không nghe máy">Không nghe máy</option>' +
			'<option value="Gọi">Gọi / khác</option>' +
			'</select>' +
			'<label class="mk-opp-call-modal__label" for="mkOppCallNote">Ghi chú</label>' +
			'<textarea id="mkOppCallNote" rows="3" placeholder="Nội dung trao đổi…"></textarea>' +
			'</div>' +
			'<div class="mk-opp-call-modal__foot">' +
			'<button type="button" class="btn btn-default" data-mk-close="1">Hủy</button>' +
			'<button type="button" class="btn btn-success" id="mkOppCallLogSave">Lưu cuộc gọi</button>' +
			'</div>' +
			'</div>';
		document.body.appendChild(wrap);

		function close() {
			if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
		}
		wrap.addEventListener('click', function (e) {
			if (e.target === wrap || (e.target.getAttribute && e.target.getAttribute('data-mk-close') === '1')) {
				close();
			}
		});
		byId('mkOppCallLogSave').addEventListener('click', function () {
			var result = (byId('mkOppCallResult').value || '').trim();
			var note = (byId('mkOppCallNote').value || '').trim();
			var btn = byId('mkOppCallLogSave');
			btn.disabled = true;
			apiPost(
				{
					module: 'Potentials',
					action: 'CommerceApi',
					mode: 'log_call',
					record: recordId(),
					result: result,
					note: note,
				},
				12000
			)
				.then(function (res) {
					btn.disabled = false;
					if (res && res.log) {
						renderLog(res.log);
						scheduleEnhanceContacts();
					} else {
						loadLog(true);
					}
					close();
					toast('Đã ghi cuộc gọi.');
				})
				.catch(function (err) {
					btn.disabled = false;
					toast((err && err.message) || String(err) || 'Không lưu được cuộc gọi.', 'error');
				});
		});
	}

	function onCallCustomer() {
		function go(log) {
			var phone = log && log.phone ? log.phone : '';
			var contactId = log && log.contact_id ? log.contact_id : recordId();
			if (!phone) {
				toast('Chưa có số điện thoại trên liên hệ / Lead nguồn.', 'error');
				return;
			}
			dialPhone(phone, contactId);
		}
		if (cachedLog && cachedLog.phone) {
			go(cachedLog);
			return;
		}
		apiPost(
			{
				module: 'Potentials',
				action: 'CommerceApi',
				mode: 'interaction_log',
				record: recordId(),
			},
			8000
		)
			.then(function (res) {
				var log = res && res.log ? res.log : res;
				cachedLog = log;
				go(log);
			})
			.catch(function () {
				toast('Không lấy được số điện thoại.', 'error');
			});
	}

	function hydrateFromBoot() {
		var boot = readBootLog();
		if (boot) {
			renderLog(boot);
			scheduleEnhanceContacts();
			return true;
		}
		return false;
	}

	function bind() {
		if (!isOppDetail()) return;

		var callBtn = byId('mkOppCallCustomerBtn');
		if (callBtn && !callBtn.__mkBound) {
			callBtn.__mkBound = true;
			callBtn.addEventListener('click', function (e) {
				e.preventDefault();
				onCallCustomer();
			});
		}
		var logBtn = byId('mkOppLogCallBtn');
		if (logBtn && !logBtn.__mkBound) {
			logBtn.__mkBound = true;
			logBtn.addEventListener('click', function (e) {
				e.preventDefault();
				openLogCallModal();
			});
		}

		if (byId('mk-opp-interaction-log-body')) {
			if (!hydrateFromBoot() && !cachedLog) {
				loadLog(false);
			}
		}

		if (!bound && typeof app !== 'undefined' && app.event && typeof app.event.on === 'function') {
			bound = true;
			app.event.on('post.summaryview.load', function () {
				window.setTimeout(function () {
					if (!hydrateFromBoot() && !cachedLog) {
						loadLog(false);
					} else {
						scheduleEnhanceContacts();
					}
				}, 50);
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}
	window.setTimeout(bind, 300);
})();
