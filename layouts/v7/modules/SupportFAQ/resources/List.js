/*+***********************************************************************************
 * SupportFAQ list view
 *************************************************************************************/

Vtiger_Index_Js('SupportFAQ_List_Js', {}, {
	LAYOUT_STORAGE_KEY: 'mk_supportfaq_list_layout',

	getFaqPage: function () {
		return jQuery('.mk-sf-faq-page[data-mk-sf-faq="1"]');
	},

	getSavedLayoutMode: function () {
		try {
			var saved = window.localStorage.getItem(this.LAYOUT_STORAGE_KEY);
			if (saved === 'grid' || saved === 'list') {
				return saved;
			}
		} catch (e) {
			/* ignore */
		}
		return 'list';
	},

	applyViewMode: function (mode) {
		var $page = this.getFaqPage();
		if (!$page.length) {
			return;
		}
		var viewMode = mode === 'grid' ? 'grid' : 'list';
		$page.removeClass('mk-sf-faq-page--grid mk-sf-faq-page--list').addClass('mk-sf-faq-page--' + viewMode);

		$page.find('.mk-sf-faq-view-toggle__btn').each(function () {
			var $btn = jQuery(this);
			var active = String($btn.data('viewMode')) === viewMode;
			$btn.toggleClass('is-active', active).attr('aria-pressed', active ? 'true' : 'false');
		});

		var $listView = $page.find('.mk-sf-faq-list-view');
		var $gridView = $page.find('.mk-sf-faq-grid-view');
		if (viewMode === 'grid') {
			$listView.attr('hidden', 'hidden');
			$gridView.removeAttr('hidden');
		} else {
			$gridView.attr('hidden', 'hidden');
			$listView.removeAttr('hidden');
		}

		try {
			window.localStorage.setItem(this.LAYOUT_STORAGE_KEY, viewMode);
		} catch (e2) {
			/* ignore */
		}
	},

	registerViewToggle: function () {
		var thisInstance = this;
		var $page = this.getFaqPage();
		if (!$page.length) {
			return;
		}

		thisInstance.applyViewMode(thisInstance.getSavedLayoutMode());

		$page.off('click.mkSfFaqViewToggle', '.mk-sf-faq-view-toggle__btn');
		$page.on('click.mkSfFaqViewToggle', '.mk-sf-faq-view-toggle__btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var mode = jQuery(this).data('viewMode');
			thisInstance.applyViewMode(mode);
		});
	},

	registerBulkSelect: function () {
		var $page = this.getFaqPage();
		if (!$page.length) {
			return;
		}

		$page.off('change.mkSfFaqCheckAll', '.mk-sf-faq-check-all');
		$page.on('change.mkSfFaqCheckAll', '.mk-sf-faq-check-all', function () {
			var checked = jQuery(this).prop('checked');
			$page.find('.mk-sf-faq-row-check').prop('checked', checked);
		});
	},

	registerEvents: function () {
		this._super();

		var $page = this.getFaqPage();
		if (!$page.length) {
			return;
		}

		jQuery('.module-action-bar').hide();
		this.registerViewToggle();
		this.registerBulkSelect();
	}
});
