/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("Contacts_Detail_Js", {}, {
	registerMkContactTagShowAll: function () {
		var $list = jQuery('.mk-contact-detail-hero__tags .detailTagList');
		if (!$list.length) return;
		$list.attr('data-num-of-tags-to-show', '999');
		$list.find('.moreTags').addClass('hide');
	},
	registerMkContactTagModalPatch: function () {
		if (window.__MK_CONTACT_TAG_MODAL_PATCHED__) {
			return;
		}
		window.__MK_CONTACT_TAG_MODAL_PATCHED__ = true;
		if (typeof Vtiger_Tag_Js === 'undefined' || !Vtiger_Tag_Js.prototype) {
			return;
		}
		Vtiger_Tag_Js.prototype.viewAllTags = function (container) {
			var viewAllTagContainer = container.find('.viewAllTagsContainer').clone(true);
			viewAllTagContainer.find('.deleteTag').remove();
			app.helper.showModal(viewAllTagContainer.find('.modal-dialog'), {
				cb: function (modalContainer) {
					modalContainer.find('.modal-content').addClass('mk-contact-tags-modal');
					var holder = modalContainer.find('.currentTag');
					holder.css({ height: 'auto', maxHeight: 'none', overflow: 'visible' });
					if (window.MkLeadsDetailTags && MkLeadsDetailTags.paintAll) {
						var root = modalContainer[0] || modalContainer;
						MkLeadsDetailTags.paintAll(root);
						window.setTimeout(function () {
							MkLeadsDetailTags.paintAll(root);
						}, 80);
					}
				}
			});
		};
	},
	registerAjaxPreSaveEvents: function (container) {
		var thisInstance = this;
		app.event.on(Vtiger_Detail_Js.PreAjaxSaveEvent, function (e) {
			if (!thisInstance.checkForPortalUser(container)) {
				e.preventDefault();
			}
		});
	},
	/**
	 * Function to check for Portal User
	 */
	checkForPortalUser: function (form) {
		var element = jQuery('[name="portal"]', form);
		var response = element.is(':checked');
		
		if (response) {
			var primaryEmailField = jQuery('[data-name="email"]');

			if (primaryEmailField.length == 0) {
				app.helper.showErrorNotification({message: app.vtranslate('JS_PRIMARY_EMAIL_FIELD_DOES_NOT_EXISTS')});
				return false;
			}

			var primaryEmailValue = primaryEmailField.data("value");
			if (primaryEmailValue == "") {
				app.helper.showErrorNotification({message: app.vtranslate('JS_PLEASE_ENTER_PRIMARY_EMAIL_VALUE_TO_ENABLE_PORTAL_USER')});
				return false;
			}
		}
		return true;
	},
	/**
	 * Function which will register all the events
	 */
	registerEvents: function () {
		var form = this.getForm();
		this._super();
		this.registerMkContactTagShowAll();
		this.registerMkContactTagModalPatch();
		this.registerAjaxPreSaveEvents(form);
	}
})
