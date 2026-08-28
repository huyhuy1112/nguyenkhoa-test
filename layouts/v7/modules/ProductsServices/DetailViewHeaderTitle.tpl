{strip}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
	{assign var=MK_HAS_PS_IMAGE value=false}
	{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
	{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
		{if !empty($IMAGE_INFO.url)}{assign var=MK_HAS_PS_IMAGE value=true}{/if}
	{/foreach}
	{assign var=MK_SKU value=$RECORD->get('sku')}
	{assign var=MK_QC_RAW value=$RECORD->get('needs_qc')}
	{if $MK_QC_RAW eq 1 || $MK_QC_RAW eq '1' || $MK_QC_RAW eq 'on' || $MK_QC_RAW eq vtranslate('LBL_YES', $MODULE)}
		{assign var=MK_QC_ON value=true}
	{else}
		{assign var=MK_QC_ON value=false}
	{/if}
	{assign var=MK_PRICE_RAW value=$RECORD->get('price')}
	{assign var=MK_PRICE_DISP value=$RECORD->getDisplayValue('price')}
	{assign var=MK_PRICE_LABEL value=$MK_PRICE_DISP|replace:'$':'₫'|replace:'USD':'₫'|replace:'US$':'₫'|replace:'€':'₫'|replace:'&nbsp;':' '|regex_replace:'/\s+/':' '|trim}
	{if $MK_PRICE_LABEL neq '' && $MK_PRICE_LABEL|strstr:'₫' eq false}
		{assign var=MK_PRICE_LABEL value="₫ `$MK_PRICE_LABEL`"}
	{/if}

	<div class="mk-ps-detail-hero__left mk-ps-v2-hero">
		<div class="mk-ps-detail-hero__identity">
			<div class="mk-ps-detail-hero__icon recordImage bgproductsservices app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}">
					{/if}
				{/foreach}
				{if !$MK_HAS_PS_IMAGE}
					<span class="mk-ps-detail-hero__icon-svg" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
					</span>
				{/if}
			</div>
			<div class="mk-ps-detail-hero__text mk-ps-detail-hero__content recordBasicInfo">
				<h1 class="mk-ps-detail-hero__title">
					<span class="recordLabel" title="{$RECORD->getName()|escape:'html'}">
						{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
							{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
							{if $FIELD_MODEL->getPermissions()}
								<span class="{$NAME_FIELD}">{trim($RECORD->get($NAME_FIELD))}</span>
							{/if}
						{/foreach}
					</span>
				</h1>
				{if $MK_SKU ne ''}
					<div class="mk-ps-v2-hero__sku">SKU · <strong>{$MK_SKU|escape:'html'}</strong></div>
				{/if}
				<div class="mk-ps-detail-hero__meta mk-ps-v2-hero__meta">
					{assign var=TYPE_FIELD value=$MODULE_MODEL->getField('item_type')}
					{if $TYPE_FIELD && $TYPE_FIELD->getPermissions() && $RECORD->get('item_type')}
						{assign var=MK_TYPE_VAL value=$RECORD->get('item_type')}
						{assign var=MK_TYPE_KEY value='other'}
						{if $MK_TYPE_VAL eq 'Service'}{assign var=MK_TYPE_KEY value='service'}{/if}
						{if $MK_TYPE_VAL eq 'Product'}{assign var=MK_TYPE_KEY value='product'}{/if}
						<span class="mk-ps-detail-type-pill mk-ps-detail-type-pill--{$MK_TYPE_KEY}">{$RECORD->getDisplayValue('item_type')}</span>
					{/if}
					{if $MK_PRICE_LABEL ne ''}
						<span class="mk-ps-v2-hero__price">{$MK_PRICE_LABEL}</span>
					{/if}
					{if $MK_QC_ON}
						<span class="mk-ps-v2-hero__qc is-on" title="{vtranslate('LBL_NEEDS_QC_HINT', $MODULE)}">Cần QC</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-sm-6">
		<div class="record-header clearfix">
			<div class="recordImage bgproductsservices app-{$SELECTED_MENU_CATEGORY}">
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
				<div class="info-row">
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
