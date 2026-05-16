{* ServiceContracts Detail Header Title: Sales hero (Contacts layout) | stock for other apps. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	{assign var=MK_SC_STATUS value=''}
	{assign var=MK_SC_RELATED value=''}
	{assign var=MK_SC_CONTRACT_NO value=''}
	{assign var=MK_SC_DATE_RANGE value=''}
	{assign var=MK_REL_FNAME value='sc_related_to'}
	{assign var=STATUS_FIELD value=$MODULE_MODEL->getField('contract_status')}
	{if $STATUS_FIELD && $STATUS_FIELD->getPermissions()}
		{assign var=MK_SC_STATUS value=$RECORD->getDisplayValue('contract_status')|trim}
	{/if}
	{assign var=REL_FIELD value=$MODULE_MODEL->getField('sc_related_to')}
	{if !$REL_FIELD}{assign var=REL_FIELD value=$MODULE_MODEL->getField('related_to')}{/if}
	{if $REL_FIELD && $REL_FIELD->getPermissions()}
		{assign var=MK_REL_FNAME value=$REL_FIELD->getName()}
		{assign var=MK_SC_RELATED value=$RECORD->getDisplayValue($MK_REL_FNAME)|trim}
	{/if}
	{assign var=NO_FIELD value=$MODULE_MODEL->getField('contract_no')}
	{if $NO_FIELD && $NO_FIELD->getPermissions()}
		{assign var=MK_SC_CONTRACT_NO value=$RECORD->getDisplayValue('contract_no')|trim}
	{/if}
	{assign var=START_FIELD value=$MODULE_MODEL->getField('start_date')}
	{assign var=END_FIELD value=$MODULE_MODEL->getField('end_date')}
	{if $START_FIELD && $START_FIELD->getPermissions() && $RECORD->get('start_date')}
		{assign var=MK_SC_START value=$RECORD->getDisplayValue('start_date')|trim}
		{assign var=MK_SC_END value=''}
		{if $END_FIELD && $END_FIELD->getPermissions() && $RECORD->get('end_date')}
			{assign var=MK_SC_END value=$RECORD->getDisplayValue('end_date')|trim}
		{/if}
		{if $MK_SC_END ne ''}
			{assign var=MK_SC_DATE_RANGE value="{$MK_SC_START} – {$MK_SC_END}"}
		{else}
			{assign var=MK_SC_DATE_RANGE value=$MK_SC_START}
		{/if}
	{/if}
	<div class="mk-sc-detail-hero__left">
		<div class="mk-sc-detail-hero__identity clearfix">
			<div class="mk-sc-detail-hero__icon recordImage bg_ServiceContracts app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}">
					{/if}
				{/foreach}
				{assign var=MK_HAS_SC_IMAGE value=false}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}{assign var=MK_HAS_SC_IMAGE value=true}{/if}
				{/foreach}
				{if !$MK_HAS_SC_IMAGE}
					<span class="mk-sc-detail-hero__icon-glyph" aria-hidden="true"><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span>
				{/if}
			</div>
			<div class="mk-sc-detail-hero__text recordBasicInfo">
				<div class="info-row mk-sc-detail-hero__name-row">
					<h1 class="mk-sc-detail-hero__title">
						<span class="recordLabel pushDown" title="{$RECORD->getName()|escape:'html'}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{decode_html(trim($RECORD->get($NAME_FIELD)))}</span>
								{/if}
							{/foreach}
						</span>
					</h1>
				</div>
				<div class="mk-sc-detail-hero__meta info-row">
					{if $MK_SC_STATUS ne ''}
						<span class="mk-sc-stage-pill" title="{vtranslate('contract_status', $MODULE)}">{$MK_SC_STATUS}</span>
					{/if}
					{if $MK_SC_RELATED ne ''}
						<span class="mk-sc-detail-hero__meta-item mk-sc-detail-hero__meta-item--org" title="{vtranslate($MK_REL_FNAME, $MODULE)}">
							<i class="fa fa-building-o" aria-hidden="true"></i>
							<span class="mk-sc-detail-hero__meta-text">{$MK_SC_RELATED}</span>
						</span>
					{/if}
					{if $MK_SC_CONTRACT_NO ne ''}
						<span class="mk-sc-detail-hero__meta-item mk-sc-detail-hero__meta-item--no" title="{vtranslate('contract_no', $MODULE)}">
							<i class="fa fa-hashtag" aria-hidden="true"></i>
							<span class="mk-sc-detail-hero__meta-text">{$MK_SC_CONTRACT_NO}</span>
						</span>
					{/if}
					{if $MK_SC_DATE_RANGE ne ''}
						<span class="mk-sc-detail-hero__meta-item mk-sc-detail-hero__meta-item--date" title="{vtranslate('start_date', $MODULE)}">
							<i class="fa fa-calendar" aria-hidden="true"></i>
							<span class="mk-sc-detail-hero__meta-text">{$MK_SC_DATE_RANGE}</span>
						</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-lg-6 col-md-6 col-sm-6">
		<div class="record-header clearfix">
			<div class="recordImage bg_ServiceContracts app-{$SELECTED_MENU_CATEGORY}">
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
