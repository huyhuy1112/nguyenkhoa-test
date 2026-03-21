{* Invoice-only VAT-style layout. Same field names/ids as core; visual grouping only. *}
{strip}
{assign var=INV_IBLOCK value=$RECORD_STRUCTURE.LBL_INVOICE_INFORMATION}
{assign var=INV_ADDR_BLOCK value=$RECORD_STRUCTURE.LBL_ADDRESS_INFORMATION}

<div name='editContent' class="invoice-vat-edit-layout">

	{* ---- 1. Invoice Header ---- *}
	<div class="panel panel-default invoice-vat-section invoice-vat-header-panel">
		<div class="panel-heading"><strong>{vtranslate('LBL_INVOICE_HEADER_VAT', $MODULE)}</strong></div>
		<div class="panel-body">
			<div class="row">
				{foreach from=array('subject','invoice_no','invoicedate','duedate','invoicestatus') item=HFN}
					{if isset($INV_IBLOCK.$HFN)}
						{assign var=FM value=$INV_IBLOCK.$HFN}
						{if $FM->isEditable() eq true}
							<div class="col-sm-6">
								<table class="table table-borderless invoice-vat-field-table"><tr>
									{assign var=isReferenceField value=$FM->getFieldDataType()}
									{assign var=refrenceList value=$FM->getReferenceList()}
									{assign var=refrenceListCount value=php7_count($refrenceList)}
									<td class="fieldLabel alignMiddle" style="width:38%;">
										{if $FM->isMandatory() eq true}<span class="redColor">*</span>{/if}
										{if $isReferenceField eq "reference" && $refrenceListCount > 1}
											{assign var=REFERENCED_MODULE_ID value=$FM->get('fieldvalue')}
											{assign var=REFERENCED_MODULE_STRUCTURE value=$FM->getUITypeModel()->getReferenceModule($REFERENCED_MODULE_ID)}
											{if !empty($REFERENCED_MODULE_STRUCTURE)}{assign var=REFERENCED_MODULE_NAME value=$REFERENCED_MODULE_STRUCTURE->get('name')}{/if}
											<select style="width: 140px;" class="select2 referenceModulesList">
												{foreach key=index item=value from=$refrenceList}
													<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME}selected{/if}>{vtranslate($value, $value)}</option>
												{/foreach}
											</select>
										{else}
											{vtranslate($FM->get('label'), $MODULE)}
										{/if}
									</td>
									<td class="fieldValue {if in_array($FM->get('uitype'),array('19','69'))}fieldValueWidth80{/if}" {if in_array($FM->get('uitype'),array('19','69'))}colspan="3"{/if}>
										{if $FM->getFieldDataType() eq 'image' || $FM->getFieldDataType() eq 'file'}
											<div class='col-lg-4 col-md-4 redColor'>{vtranslate('LBL_NOTE_EXISTING_ATTACHMENTS_WILL_BE_REPLACED', $MODULE)}</div>
										{/if}
										{include file=vtemplate_path($FM->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$FM MODULE=$MODULE}
									</td>
								</tr></table>
							</div>
						{/if}
					{/if}
				{/foreach}
			</div>
			{* Currency + tax mode: identical markup/ids as LineItemsEdit (single instance on page) *}
			<div class="row invoice-vat-header-currency-tax">
				<div class="col-sm-6">
					<div class="form-group">
						<label class="control-label"><i class="fa fa-info-circle"></i>&nbsp;{vtranslate('LBL_CURRENCY',$MODULE)}</label>
						{assign var=SELECTED_CURRENCY value=$CURRENCINFO}
						{if $SELECTED_CURRENCY eq ''}
							{assign var=USER_CURRENCY_ID value=$USER_MODEL->get('currency_id')}
							{foreach item=currency_details from=$CURRENCIES}
								{if $currency_details.curid eq $USER_CURRENCY_ID}
									{assign var=SELECTED_CURRENCY value=$currency_details}
								{/if}
							{/foreach}
						{/if}
						<div>
							<select class="select2" id="currency_id" name="currency_id" style="width: 100%; max-width:280px;">
								{foreach item=currency_details key=count from=$CURRENCIES}
									<option value="{$currency_details.curid}" class="textShadowNone" data-conversion-rate="{$currency_details.conversionrate}" {if $SELECTED_CURRENCY.currency_id eq $currency_details.curid}selected{/if}>
										{$currency_details.currencylabel|@getTranslatedCurrencyString} ({$currency_details.currencysymbol})
									</option>
								{/foreach}
							</select>
						</div>
						{assign var="RECORD_CURRENCY_RATE" value=$RECORD_STRUCTURE_MODEL->getRecord()->get('conversion_rate')}
						{if $RECORD_CURRENCY_RATE eq ''}{assign var="RECORD_CURRENCY_RATE" value=$SELECTED_CURRENCY.conversionrate}{/if}
						<input type="hidden" name="conversion_rate" id="conversion_rate" value="{$RECORD_CURRENCY_RATE}" />
						<input type="hidden" value="{$SELECTED_CURRENCY.currency_id}" id="prev_selected_currency_id" />
						<input type="hidden" id="default_currency_id" value="{$CURRENCIES.0.curid}" />
						<input type="hidden" value="{$SELECTED_CURRENCY.currency_id}" id="selectedCurrencyId" />
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label class="control-label"><i class="fa fa-info-circle"></i>&nbsp;{vtranslate('LBL_TAX_MODE',$MODULE)}</label>
						<div>
							{assign var="IS_INDIVIDUAL_TAX_TYPE" value=false}
							{assign var="IS_GROUP_TAX_TYPE" value=true}
							{if $TAX_TYPE eq 'individual'}
								{assign var="IS_GROUP_TAX_TYPE" value=false}
								{assign var="IS_INDIVIDUAL_TAX_TYPE" value=true}
							{/if}
							<select class="select2 lineItemTax" id="taxtype" name="taxtype" style="width: 100%; max-width:280px;">
								<option value="individual" {if $IS_INDIVIDUAL_TAX_TYPE}selected{/if}>{vtranslate('LBL_INDIVIDUAL', $MODULE)}</option>
								<option value="group" {if $IS_GROUP_TAX_TYPE}selected{/if}>{vtranslate('LBL_GROUP', $MODULE)}</option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	{* ---- 2. Seller Information + 3. Buyer Information in one address table (keep bill_*/ship_* names for save/copy JS) ---- *}
	{if isset($INV_ADDR_BLOCK) && $INV_ADDR_BLOCK|@count gt 0}
		<div class='fieldBlockContainer invoice-vat-address-block' data-block="LBL_ADDRESS_INFORMATION">
			<h4 class='fieldBlockHeader'>{vtranslate('LBL_ADDRESS_INFORMATION', $MODULE)}</h4>
			<hr>
			<table class="table table-borderless addressBlock">
				<tr>
					<td colspan="4" class="invoice-vat-subsection-title"><strong>{vtranslate('LBL_SELLER_INFORMATION', $MODULE)}</strong></td>
				</tr>
				<tr>
					<td colspan="4" class="small text-muted" style="padding-bottom:10px;">{vtranslate('LBL_INVOICE_SELLER_PHASE1_NOTE', $MODULE)}</td>
				</tr>
				{foreach from=array('ship_street','ship_pobox','ship_city','ship_state','ship_code','ship_country') item=SELLER_FN}
					{if isset($INV_ADDR_BLOCK.$SELLER_FN)}
						{assign var=SFM value=$INV_ADDR_BLOCK.$SELLER_FN}
						{if $SFM->isEditable() eq true}
							<tr>
								<td class="fieldLabel alignMiddle" style="width:18%;">
									{if $SFM->isMandatory() eq true}<span class="redColor">*</span>{/if}
									{if $SELLER_FN eq 'ship_street'}{vtranslate('LBL_INVOICE_SELLER_ADDRESS', $MODULE)}
									{elseif $SELLER_FN eq 'ship_pobox'}{vtranslate('LBL_INVOICE_SELLER_POBOX', $MODULE)}
									{elseif $SELLER_FN eq 'ship_city'}{vtranslate('LBL_INVOICE_SELLER_CITY', $MODULE)}
									{elseif $SELLER_FN eq 'ship_state'}{vtranslate('LBL_INVOICE_SELLER_STATE', $MODULE)}
									{elseif $SELLER_FN eq 'ship_code'}{vtranslate('LBL_INVOICE_SELLER_POSTAL', $MODULE)}
									{elseif $SELLER_FN eq 'ship_country'}{vtranslate('LBL_INVOICE_SELLER_COUNTRY', $MODULE)}
									{else}{vtranslate($SFM->get('label'), $MODULE)}{/if}
								</td>
								<td class="fieldValue" colspan="3">
									{include file=vtemplate_path($SFM->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$SFM MODULE=$MODULE}
								</td>
							</tr>
						{/if}
					{/if}
				{/foreach}
				<tr>
					<td colspan="4" class="invoice-vat-subsection-title" style="padding-top:16px;"><strong>{vtranslate('LBL_BUYER_BILLING_SECTION', $MODULE)}</strong></td>
				</tr>
				<tr>
					<td colspan="2" class="text-center" style="border-bottom:1px solid #eee;"><strong>{vtranslate('LBL_BUYER_BILLING_COLUMN', $MODULE)}</strong></td>
					<td colspan="2" class="text-center" style="border-bottom:1px solid #eee;"><strong>{vtranslate('LBL_SELLER_SHIPPING_COLUMN', $MODULE)}</strong></td>
				</tr>
				<tr>
					<td class="fieldLabel " name="copyHeader1">
						<label name="togglingHeader">{vtranslate('LBL_BUYER_ADDRESS_SOURCE', $MODULE)}</label>
					</td>
					<td class="fieldValue" name="copyAddress1">
						<div class="radio"><label><input type="radio" name="copyAddressFromRight" class="accountAddress" data-copy-address="billing" checked="checked">&nbsp;{vtranslate('LBL_FROM_ACCOUNT', $MODULE)}</label></div>
						<div class="radio"><label><input type="radio" name="copyAddressFromRight" class="contactAddress" data-copy-address="billing" checked="checked">&nbsp;{vtranslate('LBL_FROM_CONTACT', $MODULE)}</label></div>
						<div class="radio" name="togglingAddressContainerRight"><label><input type="radio" name="copyAddressFromRight" class="shippingAddress" data-target="shipping" checked="checked">&nbsp;{vtranslate('LBL_COPY_FROM_SELLER_ADDRESS', $MODULE)}</label></div>
						<div class="radio hide" name="togglingAddressContainerLeft"><label><input type="radio" name="copyAddressFromRight" class="billingAddress" data-target="billing" checked="checked">&nbsp;{vtranslate('LBL_COPY_FROM_BUYER_ADDRESS', $MODULE)}</label></div>
					</td>
					<td class="fieldLabel" name="copyHeader2">
						<label name="togglingHeader">{vtranslate('LBL_SELLER_ADDRESS_SOURCE', $MODULE)}</label>
					</td>
					<td class="fieldValue" name="copyAddress2">
						<div class="radio"><label><input type="radio" name="copyAddressFromLeft" class="accountAddress" data-copy-address="shipping" checked="checked">&nbsp;{vtranslate('LBL_FROM_ACCOUNT', $MODULE)}</label></div>
						<div class="radio"><label><input type="radio" name="copyAddressFromLeft" class="contactAddress" data-copy-address="shipping" checked="checked">&nbsp;{vtranslate('LBL_FROM_CONTACT', $MODULE)}</label></div>
						<div class="radio" name="togglingAddressContainerLeft"><label><input type="radio" name="copyAddressFromLeft" class="billingAddress" data-target="billing" checked="checked">&nbsp;{vtranslate('LBL_COPY_FROM_BUYER_ADDRESS', $MODULE)}</label></div>
						<div class="radio hide" name="togglingAddressContainerRight"><label><input type="radio" name="copyAddressFromLeft" class="shippingAddress" data-target="shipping" checked="checked">&nbsp;{vtranslate('LBL_COPY_FROM_SELLER_ADDRESS', $MODULE)}</label></div>
					</td>
				</tr>
				<tr>
				{assign var=COUNTER value=0}
				{foreach key=FIELD_NAME item=FIELD_MODEL from=$INV_ADDR_BLOCK name=blockfields}
					{if strpos($FIELD_NAME,'ship_') === 0}{continue}{/if}
					{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
					{assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
					{assign var="refrenceListCount" value=php7_count($refrenceList)}
					{if $FIELD_MODEL->isEditable() eq true}
						{if $FIELD_MODEL->get('uitype') eq "19"}
							{if $COUNTER eq '1'}<td></td><td></td></tr><tr>{assign var=COUNTER value=0}{/if}
						{/if}
						{if $COUNTER eq 2}</tr><tr>{assign var=COUNTER value=1}{else}{assign var=COUNTER value=$COUNTER+1}{/if}
						<td class="fieldLabel alignMiddle">
							{if $FIELD_MODEL->isMandatory() eq true}<span class="redColor">*</span>{/if}
							{if $isReferenceField eq "reference"}
								{if $refrenceListCount > 1}
									{assign var="REFERENCED_MODULE_ID" value=$FIELD_MODEL->get('fieldvalue')}
									{assign var="REFERENCED_MODULE_STRUCTURE" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($REFERENCED_MODULE_ID)}
									{if !empty($REFERENCED_MODULE_STRUCTURE)}{assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCTURE->get('name')}{/if}
									<select style="width: 140px;" class="select2 referenceModulesList">
										{foreach key=index item=value from=$refrenceList}
											<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME}selected{/if}>{vtranslate($value, $value)}</option>
										{/foreach}
									</select>
								{else}{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{/if}
							{else}{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{/if}&nbsp;&nbsp;
						</td>
						<td {if in_array($FIELD_MODEL->get('uitype'),array('19','69')) || $FIELD_NAME eq 'description'} class="fieldValue fieldValueWidth80" colspan="3" {assign var=COUNTER value=$COUNTER+1} {else} class="fieldValue" {/if}>
							{if $FIELD_MODEL->getFieldDataType() eq 'image' || $FIELD_MODEL->getFieldDataType() eq 'file'}
								<div class='col-lg-4 col-md-4 redColor'>{vtranslate('LBL_NOTE_EXISTING_ATTACHMENTS_WILL_BE_REPLACED', $MODULE)}</div>
							{/if}
							{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$FIELD_MODEL MODULE=$MODULE}
						</td>
					{/if}
				{/foreach}
				{if $COUNTER is odd}<td></td><td></td>{/if}
				</tr>
			</table>
		</div>
	{/if}

	{* ---- 3. Buyer Information: account, contact ---- *}
	<div class="panel panel-default invoice-vat-section invoice-vat-buyer-panel">
		<div class="panel-heading"><strong>{vtranslate('LBL_BUYER_INFORMATION', $MODULE)}</strong></div>
		<div class="panel-body">
			<div class="row">
				{foreach from=array('account_id','contact_id') item=BUYFN}
					{if isset($INV_IBLOCK.$BUYFN)}
						{assign var=FM value=$INV_IBLOCK.$BUYFN}
						{if $FM->isEditable() eq true}
							<div class="col-sm-6">
								<table class="table table-borderless invoice-vat-field-table"><tr>
									{assign var=isReferenceField value=$FM->getFieldDataType()}
									{assign var=refrenceList value=$FM->getReferenceList()}
									{assign var=refrenceListCount value=php7_count($refrenceList)}
									<td class="fieldLabel alignMiddle" style="width:38%;">
										{if $FM->isMandatory() eq true}<span class="redColor">*</span>{/if}
										{if $isReferenceField eq "reference" && $refrenceListCount > 1}
											{assign var=REFERENCED_MODULE_ID value=$FM->get('fieldvalue')}
											{assign var=REFERENCED_MODULE_STRUCTURE value=$FM->getUITypeModel()->getReferenceModule($REFERENCED_MODULE_ID)}
											{if !empty($REFERENCED_MODULE_STRUCTURE)}{assign var=REFERENCED_MODULE_NAME value=$REFERENCED_MODULE_STRUCTURE->get('name')}{/if}
											<select style="width: 140px;" class="select2 referenceModulesList">
												{foreach key=index item=value from=$refrenceList}
													<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME}selected{/if}>{vtranslate($value, $value)}</option>
												{/foreach}
											</select>
										{else}
											{vtranslate($FM->get('label'), $MODULE)}
										{/if}
									</td>
									<td class="fieldValue">
										{include file=vtemplate_path($FM->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$FM MODULE=$MODULE}
									</td>
								</tr></table>
							</div>
						{/if}
					{/if}
				{/foreach}
			</div>
		</div>
	</div>

	{* ---- Additional Details (collapsible) ---- *}
	<div class="panel panel-default invoice-vat-additional-panel">
		<div class="panel-heading">
			<a class="accordion-toggle" data-toggle="collapse" href="#invoiceVatAdditionalDetails" aria-expanded="false">
				<strong>{vtranslate('LBL_ADDITIONAL_DETAILS', $MODULE)}</strong> <span class="caret"></span>
			</a>
		</div>
		<div id="invoiceVatAdditionalDetails" class="panel-collapse collapse">
			<div class="panel-body">
				<table class="table table-borderless">
				{foreach from=array('potential_id','opportunity_id','salescommission','purchaseorder','vtiger_purchaseorder') item=ADFN}
					{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
						{if $BLOCK_LABEL eq 'LBL_ITEM_DETAILS'}{continue}{/if}
						{if isset($BLOCK_FIELDS.$ADFN)}
							{assign var=FM value=$BLOCK_FIELDS.$ADFN}
							{if $FM->isEditable() eq true}
								<tr>
									{assign var=isReferenceField value=$FM->getFieldDataType()}
									{assign var=refrenceList value=$FM->getReferenceList()}
									{assign var=refrenceListCount value=php7_count($refrenceList)}
									<td class="fieldLabel alignMiddle" style="width:30%;">
										{if $FM->isMandatory() eq true}<span class="redColor">*</span>{/if}
										{if $isReferenceField eq "reference" && $refrenceListCount > 1}
											{assign var=REFERENCED_MODULE_ID value=$FM->get('fieldvalue')}
											{assign var=REFERENCED_MODULE_STRUCTURE value=$FM->getUITypeModel()->getReferenceModule($REFERENCED_MODULE_ID)}
											{if !empty($REFERENCED_MODULE_STRUCTURE)}{assign var=REFERENCED_MODULE_NAME value=$REFERENCED_MODULE_STRUCTURE->get('name')}{/if}
											<select style="width: 140px;" class="select2 referenceModulesList">
												{foreach key=index item=value from=$refrenceList}
													<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME}selected{/if}>{vtranslate($value, $value)}</option>
												{/foreach}
											</select>
										{else}{vtranslate($FM->get('label'), $MODULE)}{/if}
									</td>
									<td class="fieldValue {if in_array($FM->get('uitype'),array('19','69'))}fieldValueWidth80{/if}">
										{include file=vtemplate_path($FM->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$FM MODULE=$MODULE}
									</td>
								</tr>
							{/if}
						{/if}
					{/foreach}
				{/foreach}
				</table>
			</div>
		</div>
	</div>

	{* ---- 6. Signature / Notes ---- *}
	<div class="panel panel-default invoice-vat-section invoice-vat-signature-notes-panel">
		<div class="panel-heading"><strong>{vtranslate('LBL_SIGNATURE_NOTES', $MODULE)}</strong></div>
		<div class="panel-body">
			<table class="table table-borderless">
				{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
					{if isset($BLOCK_FIELDS.description)}
						{assign var=FM value=$BLOCK_FIELDS.description}
						{if $FM->isEditable() eq true}
							<tr>
								<td class="fieldLabel alignMiddle" style="width:30%;">{vtranslate('LBL_INVOICE_NOTES', $MODULE)}</td>
								<td class="fieldValue fieldValueWidth80">
									{include file=vtemplate_path($FM->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$FM MODULE=$MODULE}
								</td>
							</tr>
						{/if}
					{/if}
				{/foreach}
				{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
					{if isset($BLOCK_FIELDS.terms_conditions)}
						{assign var=FM value=$BLOCK_FIELDS.terms_conditions}
						{if $FM->isEditable() eq true}
							<tr>
								<td class="fieldLabel alignMiddle" style="width:30%;">{vtranslate('LBL_SIGNATURE', $MODULE)}</td>
								<td class="fieldValue fieldValueWidth80">
									{include file=vtemplate_path($FM->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$FM MODULE=$MODULE}
								</td>
							</tr>
						{/if}
					{/if}
				{/foreach}
			</table>
		</div>
	</div>

	{* ---- Remaining blocks / fields ---- *}
	{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE name=invRem}
		{if $BLOCK_LABEL eq 'LBL_ITEM_DETAILS' || $BLOCK_LABEL eq 'LBL_ADDRESS_INFORMATION' || $BLOCK_LABEL eq 'LBL_TERMS_INFORMATION'}{continue}{/if}
		{if $BLOCK_LABEL eq 'LBL_INVOICE_INFORMATION'}
			{assign var=HAS_REM value=false}
			{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
				{if $FIELD_MODEL->isEditable() eq true}
					{if !in_array($FIELD_NAME, array('subject','invoice_no','invoicedate','duedate','invoicestatus','account_id','contact_id','currency_id','conversion_rate','hdnTaxType','taxtype','potential_id','opportunity_id','salescommission','purchaseorder','vtiger_purchaseorder','description','terms_conditions'))}
						{assign var=HAS_REM value=true}
					{/if}
				{/if}
			{/foreach}
			{if $HAS_REM}
				<div class='fieldBlockContainer' data-block="{$BLOCK_LABEL}-remaining">
					<h4 class='fieldBlockHeader'>{vtranslate($BLOCK_LABEL, $MODULE)}</h4>
					<hr>
					<table class="table table-borderless">
						<tr>
						{assign var=COUNTER value=0}
						{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=invIBlock}
							{if !in_array($FIELD_NAME, array('subject','invoice_no','invoicedate','duedate','invoicestatus','account_id','contact_id','currency_id','conversion_rate','hdnTaxType','taxtype','potential_id','opportunity_id','salescommission','purchaseorder','vtiger_purchaseorder','description','terms_conditions'))}
								{if $FIELD_MODEL->isEditable() eq true}
									{if $FIELD_MODEL->get('uitype') eq "19"}{if $COUNTER eq '1'}<td></td><td></td></tr><tr>{assign var=COUNTER value=0}{/if}{/if}
									{if $COUNTER eq 2}</tr><tr>{assign var=COUNTER value=1}{else}{assign var=COUNTER value=$COUNTER+1}{/if}
									{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
									{assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
									{assign var="refrenceListCount" value=php7_count($refrenceList)}
									<td class="fieldLabel alignMiddle">
										{if $FIELD_MODEL->isMandatory() eq true}<span class="redColor">*</span>{/if}
										{if $isReferenceField eq "reference" && $refrenceListCount > 1}
											{assign var="REFERENCED_MODULE_ID" value=$FIELD_MODEL->get('fieldvalue')}
											{assign var="REFERENCED_MODULE_STRUCTURE" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($REFERENCED_MODULE_ID)}
											{if !empty($REFERENCED_MODULE_STRUCTURE)}{assign var=REFERENCED_MODULE_NAME value=$REFERENCED_MODULE_STRUCTURE->get('name')}{/if}
											<select style="width: 140px;" class="select2 referenceModulesList">
												{foreach key=index item=value from=$refrenceList}
													<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME}selected{/if}>{vtranslate($value, $value)}</option>
												{/foreach}
											</select>
										{else}{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{/if}
									</td>
									<td {if in_array($FIELD_MODEL->get('uitype'),array('19','69')) || $FIELD_NAME eq 'description'} class="fieldValue fieldValueWidth80" colspan="3" {assign var=COUNTER value=$COUNTER+1} {else} class="fieldValue" {/if}>
										{if $FIELD_MODEL->getFieldDataType() eq 'image' || $FIELD_MODEL->getFieldDataType() eq 'file'}
											<div class='col-lg-4 col-md-4 redColor'>{vtranslate('LBL_NOTE_EXISTING_ATTACHMENTS_WILL_BE_REPLACED', $MODULE)}</div>
										{/if}
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$FIELD_MODEL MODULE=$MODULE}
									</td>
								{/if}
							{/if}
						{/foreach}
						{if $COUNTER is odd}<td></td><td></td>{/if}
						</tr>
					</table>
				</div>
			{/if}
		{else}
			{if $BLOCK_FIELDS|@count gt 0}
				<div class='fieldBlockContainer' data-block="{$BLOCK_LABEL}">
					<h4 class='fieldBlockHeader'>{vtranslate($BLOCK_LABEL, $MODULE)}</h4>
					<hr>
					<table class="table table-borderless">
						<tr>
						{assign var=COUNTER value=0}
						{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=invOBlock}
							{if $FIELD_MODEL->isEditable() eq true}
								{if $FIELD_MODEL->get('uitype') eq "19"}{if $COUNTER eq '1'}<td></td><td></td></tr><tr>{assign var=COUNTER value=0}{/if}{/if}
								{if $COUNTER eq 2}</tr><tr>{assign var=COUNTER value=1}{else}{assign var=COUNTER value=$COUNTER+1}{/if}
								{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
								{assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
								{assign var="refrenceListCount" value=php7_count($refrenceList)}
								<td class="fieldLabel alignMiddle">
									{if $FIELD_MODEL->isMandatory() eq true}<span class="redColor">*</span>{/if}
									{if $isReferenceField eq "reference" && $refrenceListCount > 1}
										{assign var="REFERENCED_MODULE_ID" value=$FIELD_MODEL->get('fieldvalue')}
										{assign var="REFERENCED_MODULE_STRUCTURE" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($REFERENCED_MODULE_ID)}
										{if !empty($REFERENCED_MODULE_STRUCTURE)}{assign var=REFERENCED_MODULE_NAME value=$REFERENCED_MODULE_STRUCTURE->get('name')}{/if}
										<select style="width: 140px;" class="select2 referenceModulesList">
											{foreach key=index item=value from=$refrenceList}
												<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME}selected{/if}>{vtranslate($value, $value)}</option>
											{/foreach}
										</select>
									{else}{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{/if}
								</td>
								<td {if in_array($FIELD_MODEL->get('uitype'),array('19','69')) || $FIELD_NAME eq 'description'} class="fieldValue fieldValueWidth80" colspan="3" {assign var=COUNTER value=$COUNTER+1} {else} class="fieldValue" {/if}>
									{if $FIELD_MODEL->getFieldDataType() eq 'image' || $FIELD_MODEL->getFieldDataType() eq 'file'}
										<div class='col-lg-4 col-md-4 redColor'>{vtranslate('LBL_NOTE_EXISTING_ATTACHMENTS_WILL_BE_REPLACED', $MODULE)}</div>
									{/if}
									{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) FIELD_MODEL=$FIELD_MODEL MODULE=$MODULE}
								</td>
							{/if}
						{/foreach}
						{if $COUNTER is odd}<td></td><td></td>{/if}
						</tr>
					</table>
				</div>
			{/if}
		{/if}
	{/foreach}

</div>
{/strip}
