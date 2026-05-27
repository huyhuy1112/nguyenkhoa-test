{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-so-detail-hero__left">
		<div class="mk-so-detail-hero__identity">
			<div class="mk-so-detail-hero__icon recordImage bgsalesorder app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}">
					{/if}
				{/foreach}
				{assign var=MK_HAS_SO_IMAGE value=false}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}{assign var=MK_HAS_SO_IMAGE value=true}{/if}
				{/foreach}
				{if !$MK_HAS_SO_IMAGE}
					<span class="mk-so-detail-hero__icon-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 4h10v16H7V4z"/><path d="M9 8h6M9 12h6M9 16h4"/><path d="M5 8h2M5 12h2M5 16h2"/></svg></span>
				{/if}
			</div>
			<div class="mk-so-detail-hero__text mk-so-detail-hero__content recordBasicInfo">
				<h1 class="mk-so-detail-hero__title">
					<span class="recordLabel" title="{$RECORD->getName()|escape:'html'}">
						{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
							{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
							{if $FIELD_MODEL->getPermissions()}
								<span class="{$NAME_FIELD}">{trim($RECORD->get($NAME_FIELD))}</span>
							{/if}
						{/foreach}
					</span>
				</h1>
				<div class="mk-so-detail-hero__meta">
					{assign var=ASSIGNED_FIELD value=$MODULE_MODEL->getField('assigned_user_id')}
					{if $ASSIGNED_FIELD && $ASSIGNED_FIELD->getPermissions()}
						{assign var=MK_ASSIGNED_ID value=$RECORD->get('assigned_user_id')}
						{assign var=MK_ASSIGNED_NAME value=''}
						{if !empty($MK_ASSIGNED_ID)}
							{assign var=MK_ASSIGNED_NAME value=getUserFullName($MK_ASSIGNED_ID)}
						{/if}
						<span class="mk-so-detail-hero__meta-item mk-so-detail-hero__meta-item--user">
							{if $MK_ASSIGNED_NAME ne ''}
								<span class="mk-so-detail-user-chip" aria-hidden="true">
									{assign var=MK_ASSIGNED_PARTS value=' '|explode:$MK_ASSIGNED_NAME}
									{if $MK_ASSIGNED_PARTS|@count gt 1}
										{$MK_ASSIGNED_PARTS[0]|substr:0:1|upper}{$MK_ASSIGNED_PARTS[$MK_ASSIGNED_PARTS|@count-1]|substr:0:1|upper}
									{else}
										{$MK_ASSIGNED_NAME|substr:0:2|upper}
									{/if}
								</span>
								<span class="mk-so-detail-hero__meta-text">{$RECORD->getDisplayValue('assigned_user_id')}</span>
							{/if}
						</span>
					{/if}
					{assign var=CREATED_FIELD value=$MODULE_MODEL->getField('createdtime')}
					{if $CREATED_FIELD && $CREATED_FIELD->getPermissions() && $RECORD->get('createdtime')}
						<span class="mk-so-detail-hero__meta-item mk-so-detail-hero__meta-item--date">
							<span class="mk-so-meta-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
							<span class="mk-so-detail-hero__meta-text">{$RECORD->getDisplayValue('createdtime')}</span>
						</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-sm-6">
		<div class="record-header clearfix">
			<div class="recordImage bgsalesorder app-{$SELECTED_MENU_CATEGORY}">
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
