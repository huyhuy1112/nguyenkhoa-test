/*+**********************************************************************************
 * Luxury Quick Create — prevent forced tall scroll area on compact modals.
 ***********************************************************************************/
(function ($) {
	'use strict';

	function resetQuickCreateBody(form) {
		var $form = $(form);
		var $dlg = $form.closest('.mk-qc-sales-modal, .mk-qc-event-modal');
		if (!$dlg.length) {
			return;
		}
		var $body = $form.find('.modal-body');
		if ($body.data('mCS')) {
			$body.mCustomScrollbar('destroy');
		}
		$body.css({
			maxHeight: 'none',
			height: 'auto',
			overflow: 'visible'
		});
		$body.find('.mCustomScrollBox, .mCSB_container').css({
			maxHeight: 'none',
			height: 'auto',
			overflow: 'visible'
		});
	}

	$(function () {
		if (typeof app === 'undefined' || !app.event) {
			return;
		}
		app.event.on('post.QuickCreateForm.show', function (event, form) {
			resetQuickCreateBody(form);
			setTimeout(function () {
				resetQuickCreateBody(form);
			}, 0);
		});
	});
})(jQuery);
