/**
 * Settings → Notification preferences (per-user + company R1 for admin)
 */
(function ($) {
	'use strict';

	function collectChannels($root) {
		var out = {};
		$root.find('.js-nk-notif-channel').each(function () {
			var key = String($(this).data('channel') || '');
			if (!key) return;
			out[key] = $(this).is(':checked') ? 1 : 0;
		});
		return out;
	}

	function collectR1Days($form) {
		var days = [];
		$form.find('.js-nk-r1-day:checked').each(function () {
			days.push(String($(this).val()));
		});
		return days;
	}

	$(function () {
		var $root = $('#nk-notif-prefs');
		if (!$root.length) return;

		$('#nk-notif-prefs-form').on('submit', function (e) {
			e.preventDefault();
			var $status = $('#nk-notif-prefs-status');
			$status.text('Đang lưu…');
			var payload = {
				module: 'Vtiger',
				parent: 'Settings',
				action: 'NotificationPrefsAjax',
				mode: 'saveChannels',
				channels: JSON.stringify(collectChannels($root)),
				sound_enabled: $root.find('[name="sound_enabled"]').is(':checked') ? 1 : 0
			};
			app.request.post({ data: payload }).then(function (err, data) {
				if (err) {
					$status.text(err.message || 'Không lưu được');
					if (app.helper && app.helper.showErrorNotification) {
						app.helper.showErrorNotification({ message: err.message || 'Không lưu được' });
					}
					return;
				}
				$status.text('Đã lưu');
				if (app.helper && app.helper.showSuccessNotification) {
					app.helper.showSuccessNotification({ message: (data && data.message) || 'Đã lưu tùy chọn thông báo' });
				}
			});
		});

		$('#nk-r1-settings-form').on('submit', function (e) {
			e.preventDefault();
			var $form = $(this);
			var $status = $('#nk-r1-settings-status');
			var days = collectR1Days($form);
			if (!days.length) {
				$status.text('Chọn ít nhất 1 ngày làm việc');
				return;
			}
			$status.text('Đang lưu…');
			var payload = {
				module: 'Vtiger',
				parent: 'Settings',
				action: 'NotificationPrefsAjax',
				mode: 'saveR1Settings',
				work_start: $form.find('[name="work_start"]').val(),
				work_end: $form.find('[name="work_end"]').val(),
				gap_hours: $form.find('[name="gap_hours"]').val(),
				max_attempts: $form.find('[name="max_attempts"]').val(),
				work_days: JSON.stringify(days)
			};
			app.request.post({ data: payload }).then(function (err, data) {
				if (err) {
					$status.text(err.message || 'Không lưu được');
					if (app.helper && app.helper.showErrorNotification) {
						app.helper.showErrorNotification({ message: err.message || 'Không lưu được' });
					}
					return;
				}
				$status.text('Đã lưu lịch R1');
				if (app.helper && app.helper.showSuccessNotification) {
					app.helper.showSuccessNotification({ message: (data && data.message) || 'Đã lưu lịch R1' });
				}
			});
		});
	});
})(jQuery);
