/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger_Edit_Js("Contacts_Edit_Js",{},{

	PHONE_MAX_DIGITS: 10,
	PHONE_FIELD_SELECTOR: 'input[data-fieldtype="phone"], input[name="phone"], input[name="mobile"], input[name="homephone"], input[name="otherphone"], input[name="fax"], input[name="assistantphone"]',

	sanitizePhoneValue: function (value) {
		var digits = String(value || '').replace(/\D/g, '');
		return digits.slice(0, this.PHONE_MAX_DIGITS);
	},

	registerPhoneDigitLimit: function (container) {
		var thisInstance = this;
		if (!container || !container.length) {
			container = this.getForm();
		}
		container.find(thisInstance.PHONE_FIELD_SELECTOR).each(function () {
			var $input = jQuery(this);
			if ($input.data('mkPhoneLimit')) {
				return;
			}
			$input.data('mkPhoneLimit', true);
			$input.attr('maxlength', thisInstance.PHONE_MAX_DIGITS);
			$input.attr('inputmode', 'numeric');
			var current = thisInstance.sanitizePhoneValue($input.val());
			if ($input.val() !== current) {
				$input.val(current);
			}
			$input.on('input.mkPhoneLimit paste.mkPhoneLimit', function () {
				var sanitized = thisInstance.sanitizePhoneValue(this.value);
				if (this.value !== sanitized) {
					this.value = sanitized;
				}
			});
		});
	},

	validatePhoneFields: function (container) {
		var thisInstance = this;
		if (!container || !container.length) {
			container = this.getForm();
		}
		var invalidField = null;
		container.find(thisInstance.PHONE_FIELD_SELECTOR).each(function () {
			var val = jQuery(this).val();
			if (!val) {
				return;
			}
			var digits = String(val).replace(/\D/g, '');
			if (digits.length > thisInstance.PHONE_MAX_DIGITS) {
				invalidField = this;
				return false;
			}
		});
		if (invalidField) {
			app.helper.showErrorNotification({ message: 'Số điện thoại chỉ được nhập tối đa 10 số.' });
			jQuery(invalidField).focus();
			return false;
		}
		return true;
	},

	registerQuickCreatePhoneLimit: function () {
		var thisInstance = this;
		app.event.off('post.QuickCreateForm.show.mkContactPhone');
		app.event.on('post.QuickCreateForm.show.mkContactPhone', function (event, form) {
			var $form = jQuery(form);
			if ($form.find('input[name="module"]').val() !== 'Contacts') {
				return;
			}
			thisInstance.registerPhoneDigitLimit($form);
		});
	},
	
	//Will have the mapping of address fields based on the modules
	addressFieldsMapping : {'Accounts' :
									{'mailingstreet' : 'bill_street',  
									'otherstreet' : 'ship_street', 
									'mailingpobox' : 'bill_pobox',
									'otherpobox' : 'ship_pobox',
									'mailingcity' : 'bill_city',
									'othercity' : 'ship_city',
									'mailingstate' : 'bill_state',
									'otherstate' : 'ship_state',
									'mailingzip' : 'bill_code',
									'otherzip' : 'ship_code',
									'mailingcountry' : 'bill_country',
									'othercountry' : 'ship_country'
									}
							},
							
	//Address field mapping within module
	addressFieldsMappingInModule : {
										'otherstreet' : 'mailingstreet',
										'otherpobox' : 'mailingpobox',
										'othercity' : 'mailingcity',
										'otherstate' : 'mailingstate',
										'otherzip' : 'mailingzip',
										'othercountry' : 'mailingcountry'
								},
	
        /* Function which will register event for Reference Fields Selection
        */
	registerReferenceSelectionEvent : function(container) {
            var thisInstance = this;

           jQuery('input[name="account_id"]', container).on(Vtiger_Edit_Js.referenceSelectionEvent,function(e,data){
                thisInstance.referenceSelectionEventHandler(data, container);
            });
	},
		
	/**
	 * Reference Fields Selection Event Handler
	 * On Confirmation It will copy the address details
	 */
	referenceSelectionEventHandler :  function(data, container) {
		var thisInstance = this;
		var message = app.vtranslate('OVERWRITE_EXISTING_MSG1')+app.vtranslate('SINGLE_'+data['source_module'])+' ('+data['selectedName']+') '+app.vtranslate('OVERWRITE_EXISTING_MSG2');
		app.helper.showConfirmationBox({'message' : message}).then(function(e){
			thisInstance.copyAddressDetails(data, container);
		},
		function(error,err){});
	},
	
	/**
	 * Function which will copy the address details - without Confirmation
	 */
	copyAddressDetails : function(data, container) {
		var thisInstance = this;
		var sourceModule = data['source_module'];
		thisInstance.getRecordDetails(data).then(
			function(response){
				thisInstance.mapAddressDetails(thisInstance.addressFieldsMapping[sourceModule], response['data'], container);
			},
			function(error, err){

			});
	},
	
	/**
	 * Function which will map the address details of the selected record
	 */
	mapAddressDetails : function(addressDetails, result, container) {
		for(var key in addressDetails) {
            if(container.find('[name="'+key+'"]').length == 0) {
                var create = container.append("<input type='hidden' name='"+key+"'>");
            }
			container.find('[name="'+key+'"]').val(result[addressDetails[key]]);
			container.find('[name="'+key+'"]').trigger('change');
		}
	},
	
	/**
	 * Function to swap array
	 * @param Array that need to be swapped
	 */ 
	swapObject : function(objectToSwap){
		var swappedArray = {};
		var newKey,newValue;
		for(var key in objectToSwap){
			newKey = objectToSwap[key];
			newValue = key;
			swappedArray[newKey] = newValue;
		}
		return swappedArray;
	},
	
	/**
	 * Function to copy address between fields
	 * @param strings which accepts value as either odd or even
	 */
	copyAddress : function(swapMode, container){
		var thisInstance = this;
		var addressMapping = this.addressFieldsMappingInModule;
		if(swapMode == "false"){
			for(var key in addressMapping) {
				var fromElement = container.find('[name="'+key+'"]');
				var toElement = container.find('[name="'+addressMapping[key]+'"]');
				toElement.val(fromElement.val());
				if((jQuery("#massEditContainer").length) && (toElement.val()!= "") && (typeof(toElement.attr('data-validation-engine')) == "undefined")){
					toElement.attr('data-validation-engine', toElement.data('invalidValidationEngine'));
				}
			}
		} else if(swapMode){
			var swappedArray = thisInstance.swapObject(addressMapping);
			for(var key in swappedArray) {
				var fromElement = container.find('[name="'+key+'"]');
				var toElement = container.find('[name="'+swappedArray[key]+'"]');
				toElement.val(fromElement.val());
				if((jQuery("#massEditContainer").length) && (toElement.val()!= "")  && (typeof(toElement.attr('data-validation-engine')) == "undefined")){
					toElement.attr('data-validation-engine', toElement.data('invalidValidationEngine'));
				}
			}
		}
	},
	
	
	/**
	 * Function to register event for copying address between two fileds
	 */
	registerEventForCopyingAddress : function(container){
		var thisInstance = this;
		var swapMode;
		jQuery('[name="copyAddress"]').on('click',function(e){
			var element = jQuery(e.currentTarget);
			var target = element.data('target');
			if(target == "other"){
				swapMode = "false";
			} else if(target == "mailing"){
				swapMode = "true";
			}
			thisInstance.copyAddress(swapMode, container);
		})
	},

	/**
	 * Function to check for Portal User
	 */
	checkForPortalUser: function (form) {
		var element = jQuery('[name="portal"]', form);
		var response = element.is(':checked');
		var primaryEmailField = jQuery('[name="email"]');
		var primaryEmailValue = primaryEmailField.val();
		if (response) {
			if (primaryEmailField.length == 0) {
				app.helper.showErrorNotification({message: app.vtranslate('JS_PRIMARY_EMAIL_FIELD_DOES_NOT_EXISTS')});
				return false;
			}
			if (primaryEmailValue == "") {
				app.helper.showErrorNotification({message: app.vtranslate('JS_PLEASE_ENTER_PRIMARY_EMAIL_VALUE_TO_ENABLE_PORTAL_USER')});
				return false;
			}
		}
		return true;
	},
	/**
	 * Function to register recordpresave event
	 */
	registerRecordPreSaveEvent: function (form) {
		var thisInstance = this;
		if (typeof form == 'undefined') {
			form = this.getForm();
		}

		app.event.on(Vtiger_Edit_Js.recordPresaveEvent, function (e) {
			var result = thisInstance.checkForPortalUser(form);
			if (!result) {
				e.preventDefault();
				return;
			}
			if (!thisInstance.validatePhoneFields(form)) {
				e.preventDefault();
			}
		});

	},

	registerBasicEvents: function (container) {
		this._super(container);
		this.registerEventForCopyingAddress(container);
		this.registerRecordPreSaveEvent(container);
		this.registerReferenceSelectionEvent(container);
		this.registerPhoneDigitLimit(container);
	},

	init: function () {
		this._super();
		this.registerQuickCreatePhoneLimit();
	}
})