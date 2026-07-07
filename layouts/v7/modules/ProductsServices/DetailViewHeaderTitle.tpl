{strip}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
	<div class="mk-ps-detail-hero__left">
		<div class="mk-ps-detail-hero__identity">
			<div class="mk-ps-detail-hero__icon recordImage bgproductsservices app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}">
					{/if}
				{/foreach}
				{assign var=MK_HAS_PS_IMAGE value=false}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}{assign var=MK_HAS_PS_IMAGE value=true}{/if}
				{/foreach}
				{if !$MK_HAS_PS_IMAGE}
					<span class="mk-ps-detail-hero__icon-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l3 3v17H6V2z"/><path d="M14 2v4h4"/><path d="M9 12h6M9 16h4"/></svg></span>
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
				<div class="mk-ps-detail-hero__meta">
					{assign var=TYPE_FIELD value=$MODULE_MODEL->getField('item_type')}
					{if $TYPE_FIELD && $TYPE_FIELD->getPermissions() && $RECORD->get('item_type')}
						{assign var=MK_TYPE_VAL value=$RECORD->get('item_type')}
						{assign var=MK_TYPE_KEY value='other'}
						{if $MK_TYPE_VAL eq 'Service'}{assign var=MK_TYPE_KEY value='service'}{/if}
						{if $MK_TYPE_VAL eq 'Product'}{assign var=MK_TYPE_KEY value='product'}{/if}
						<span class="mk-ps-detail-hero__meta-item mk-ps-detail-hero__meta-item--type">
							<span class="mk-ps-detail-type-pill mk-ps-detail-type-pill--{$MK_TYPE_KEY}">{$RECORD->getDisplayValue('item_type')}</span>
						</span>
					{/if}
					{assign var=PRICE_FIELD value=$MODULE_MODEL->getField('price')}
					{if $PRICE_FIELD && $PRICE_FIELD->getPermissions() && $RECORD->get('price') ne ''}
						<span class="mk-ps-detail-hero__meta-item mk-ps-detail-hero__meta-item--price">
							<span class="mk-ps-meta-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
							<span class="mk-ps-detail-hero__meta-text mk-ps-detail-hero__meta-text--price">{$RECORD->getDisplayValue('price')}</span>
						</span>
					{/if}
					{assign var=ASSIGNED_FIELD value=$MODULE_MODEL->getField('assigned_user_id')}
					{if $ASSIGNED_FIELD && $ASSIGNED_FIELD->getPermissions()}
						{assign var=MK_ASSIGNED_ID value=$RECORD->get('assigned_user_id')}
						{assign var=MK_ASSIGNED_NAME value=''}
						{if !empty($MK_ASSIGNED_ID)}
							{assign var=MK_ASSIGNED_NAME value=getUserFullName($MK_ASSIGNED_ID)}
						{/if}
						{if $MK_ASSIGNED_NAME ne ''}
							<span class="mk-ps-detail-hero__meta-item mk-ps-detail-hero__meta-item--user">
								<span class="mk-ps-detail-user-chip" aria-hidden="true">
									{assign var=MK_ASSIGNED_PARTS value=' '|explode:$MK_ASSIGNED_NAME}
									{if $MK_ASSIGNED_PARTS|@count gt 1}
										{$MK_ASSIGNED_PARTS[0]|substr:0:1|upper}{$MK_ASSIGNED_PARTS[$MK_ASSIGNED_PARTS|@count-1]|substr:0:1|upper}
									{else}
										{$MK_ASSIGNED_NAME|substr:0:2|upper}
									{/if}
								</span>
								<span class="mk-ps-detail-hero__meta-text">{$RECORD->getDisplayValue('assigned_user_id')}</span>
							</span>
						{/if}
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
