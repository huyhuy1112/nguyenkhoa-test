/*+***********************************************************************************
 * SupportFAQ list view
 *************************************************************************************/

Vtiger_Index_Js('SupportFAQ_List_Js', {}, {
	registerEvents: function () {
		this._super();

		var $page = jQuery('.mk-sf-faq-page');
		if (!$page.length) {
			return;
		}

		jQuery('.module-action-bar').hide();

		$page.on('click', '.mk-sf-faq-view-toggle__btn', function () {
			var $btn = jQuery(this);
			$page.find('.mk-sf-faq-view-toggle__btn').removeClass('is-active').attr('aria-pressed', 'false');
			$btn.addClass('is-active').attr('aria-pressed', 'true');
		});

		$page.on('change', '.mk-sf-faq-check-all', function () {
			var checked = jQuery(this).prop('checked');
			$page.find('.mk-sf-faq-row-check').prop('checked', checked);
		});
	}
});
