/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Inventory_Detail_Js("SalesOrder_Detail_Js", {}, {
	registerCancelOrder: function () {
		var self = this;
		jQuery(document)
			.off('click.mkSoDetailCancel', '.mk-so-detail-cancel-order-btn')
			.on('click.mkSoDetailCancel', '.mk-so-detail-cancel-order-btn', function (e) {
				e.preventDefault();
				var $btn = jQuery(this);
				if ($btn.data('mkBusy')) {
					return;
				}
				var recordId = String($btn.attr('data-record-id') || jQuery('#recordId').val() || '');
				if (!recordId) {
					return;
				}
				var message = 'Huỷ đơn sẽ hoàn kho nếu đã trừ tồn. Tiếp tục?';
				var run = function () {
					$btn.data('mkBusy', 1).prop('disabled', true);
					var postData = {
						module: 'SalesOrder',
						action: 'CancelOrder',
						record: recordId,
						app: 'SALES',
					};
					if (app.helper && app.helper.showProgress) {
						app.helper.showProgress();
					}
					var finish = function () {
						if (app.helper && app.helper.hideProgress) {
							app.helper.hideProgress();
						}
						$btn.data('mkBusy', 0).prop('disabled', false);
					};
					var onOk = function (res) {
						finish();
						var payload = res && res.result ? res.result : res;
						if (!payload || payload.success === false) {
							var err = (payload && payload.message) || 'Không huỷ được đơn hàng.';
							if (app.helper && app.helper.showErrorNotification) {
								app.helper.showErrorNotification({ message: err });
							} else {
								alert(err);
							}
							return;
						}
						if (app.helper && app.helper.showSuccessNotification) {
							app.helper.showSuccessNotification({
								message: payload.message || 'Đã huỷ đơn hàng.',
							});
						}
						window.setTimeout(function () {
							window.location.reload();
						}, 400);
					};
					var onFail = function (err) {
						finish();
						var msg = (err && err.message) || String(err || 'Không huỷ được đơn hàng.');
						if (app.helper && app.helper.showErrorNotification) {
							app.helper.showErrorNotification({ message: msg });
						} else {
							alert(msg);
						}
					};
					if (app.request && app.request.post) {
						app.request.post({ data: postData }).then(function (err, res) {
							if (err) {
								onFail(err);
								return;
							}
							onOk(res);
						});
					} else {
						jQuery.ajax({
							url: 'index.php',
							method: 'POST',
							dataType: 'json',
							data: postData,
						}).done(onOk).fail(onFail);
					}
				};
				if (app.helper && app.helper.showConfirmationBox) {
					app.helper.showConfirmationBox({ message: message }).then(function () {
						run();
					});
				} else if (window.confirm(message)) {
					run();
				}
			});
	},

	registerEvents: function () {
		this._super();
		this.registerCancelOrder();
	},
});
