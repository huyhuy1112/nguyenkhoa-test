{strip}
{assign var=MK_INV_MK_DETAIL value=false}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SUPPORT' || $SELECTED_MENU_CATEGORY eq 'TOOLS')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SUPPORT' || $smarty.get.app eq 'TOOLS')) || (isset($smarty.request.app) && ($smarty.request.app eq 'SUPPORT' || $smarty.request.app eq 'TOOLS'))}
	{assign var=MK_INV_MK_DETAIL value=true}
{/if}
{if $MK_INV_MK_DETAIL}
	{assign var=MK_INV_STATUS value=''}
	{assign var=MK_INV_ACCOUNT value=''}
	{assign var=MK_INV_CONTACT value=''}
	{assign var=MK_INV_ASSIGNED value=''}
	{assign var=MK_INV_TOTAL value=''}
	{assign var=MK_INV_NO value=''}
	{assign var=STATUS_FIELD value=$MODULE_MODEL->getField('invoicestatus')}
	{if !$STATUS_FIELD}{assign var=STATUS_FIELD value=$MODULE_MODEL->getField('status')}{/if}
	{if $STATUS_FIELD && $STATUS_FIELD->getPermissions()}
		{assign var=MK_INV_STATUS value=$RECORD->getDisplayValue($STATUS_FIELD->getName())|trim}
	{/if}
	{assign var=ACC_FIELD value=$MODULE_MODEL->getField('account_id')}
	{if $ACC_FIELD && $ACC_FIELD->getPermissions()}
		{assign var=MK_INV_ACCOUNT value=$RECORD->getDisplayValue('account_id')|trim}
	{/if}
	{assign var=CON_FIELD value=$MODULE_MODEL->getField('contact_id')}
	{if $CON_FIELD && $CON_FIELD->getPermissions()}
		{assign var=MK_INV_CONTACT value=$RECORD->getDisplayValue('contact_id')|trim}
	{/if}
	{assign var=ASS_FIELD value=$MODULE_MODEL->getField('assigned_user_id')}
	{if $ASS_FIELD && $ASS_FIELD->getPermissions()}
		{assign var=MK_INV_ASSIGNED value=$RECORD->getDisplayValue('assigned_user_id')|trim}
	{/if}
	{assign var=TOT_FIELD value=$MODULE_MODEL->getField('hdnGrandTotal')}
	{if $TOT_FIELD && $TOT_FIELD->getPermissions()}
		{assign var=MK_INV_TOTAL value=$RECORD->getDisplayValue('hdnGrandTotal')|trim}
	{/if}
	{assign var=NO_FIELD value=$MODULE_MODEL->getField('customerno')}
	{if !$NO_FIELD}{assign var=NO_FIELD value=$MODULE_MODEL->getField('invoice_no')}{/if}
	{if $NO_FIELD && $NO_FIELD->getPermissions()}
		{assign var=MK_INV_NO value=$RECORD->getDisplayValue($NO_FIELD->getName())|trim}
	{/if}
	<div class="mk-inv-detail-hero__left">
		<div class="mk-inv-detail-hero__identity clearfix">
			<div class="mk-inv-detail-hero__icon recordImage bginvoice app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}">
					{/if}
				{/foreach}
				{assign var=MK_HAS_INV_IMAGE value=false}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}{assign var=MK_HAS_INV_IMAGE value=true}{/if}
				{/foreach}
				{if !$MK_HAS_INV_IMAGE}
					<span class="mk-inv-detail-hero__icon-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
				{/if}
			</div>
			<div class="mk-inv-detail-hero__text mk-inv-detail-hero__content recordBasicInfo">
				<h1 class="mk-inv-detail-hero__title">
					<span class="recordLabel" title="{$RECORD->getName()|escape:'html'}">
						{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
							{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
							{if $FIELD_MODEL->getPermissions()}
								<span class="{$NAME_FIELD}">{decode_html(trim($RECORD->get($NAME_FIELD)))}</span>
							{/if}
						{/foreach}
					</span>
				</h1>
				<div class="mk-inv-detail-hero__meta">
					{if $MK_INV_STATUS ne ''}
						<span class="mk-inv-detail-hero__stage" title="{vtranslate('Status', $MODULE)}">{$MK_INV_STATUS}</span>
					{/if}
					{if $MK_INV_NO ne ''}
						<span class="mk-inv-detail-hero__meta-item mk-inv-detail-hero__meta-item--code" title="{vtranslate('Invoice No', $MODULE)}">
							<span class="mk-inv-detail-hero__meta-text">{$MK_INV_NO}</span>
						</span>
					{/if}
					{if $MK_INV_CONTACT ne ''}
						<span class="mk-inv-detail-hero__meta-item mk-inv-detail-hero__meta-item--contact" title="{vtranslate('contact_id', $MODULE)}">
							<span class="mk-inv-detail-hero__meta-text">{$MK_INV_CONTACT}</span>
						</span>
					{/if}
					{if $MK_INV_ACCOUNT ne ''}
						<span class="mk-inv-detail-hero__meta-item mk-inv-detail-hero__meta-item--org" title="{vtranslate('account_id', $MODULE)}">
							<span class="mk-inv-detail-hero__meta-text">{$MK_INV_ACCOUNT}</span>
						</span>
					{/if}
					{if $MK_INV_TOTAL ne ''}
						<span class="mk-inv-detail-hero__meta-item mk-inv-detail-hero__meta-item--total" title="{vtranslate('hdnGrandTotal', $MODULE)}">
							<span class="mk-inv-detail-hero__meta-text">{$MK_INV_TOTAL}</span>
						</span>
					{/if}
					{if $MK_INV_ASSIGNED ne ''}
						<span class="mk-inv-detail-hero__meta-item mk-inv-detail-hero__meta-item--owner" title="{vtranslate('assigned_user_id', $MODULE)}">
							<span class="mk-inv-detail-hero__meta-text">{$MK_INV_ASSIGNED}</span>
						</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
    <div class="col-sm-6">
        <div class="record-header clearfix">
            <div class="recordImage bginvoice app-{$SELECTED_MENU_CATEGORY}">
                {assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
                {foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
                    {if !empty($IMAGE_INFO.url)}
                        <img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname}" title="{$IMAGE_INFO.orgname}" width="100%" height="100%" align="left"><br>
                    {else}
                        <img src="{vimage_path('summary_organizations.png')}" class="summaryImg"/>
                    {/if}
                {/foreach}
				{if empty($IMAGE_DETAILS)}
					<div class="name"><span><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span></div>
				{/if}
            </div>
            <div class="recordBasicInfo">
                <div class="info-row" >
                    <h4>
                        <span class="recordLabel pushDown" title="{$RECORD->getName()}">
                            {foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
                                {assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
                                {if $FIELD_MODEL->getPermissions()}
                                    <span class="{$NAME_FIELD}">{trim($RECORD->get($NAME_FIELD))}</span>&nbsp;
                                {/if}
                            {/foreach}
                        </span>
                    </h4>
                </div>
                {include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
            </div>
        </div>
    </div>
{/if}
{/strip}
