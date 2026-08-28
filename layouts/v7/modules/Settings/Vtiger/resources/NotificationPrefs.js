/**
 * Settings → Notification preferences (per-user channel toggles)
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
	});
})(jQuery);
