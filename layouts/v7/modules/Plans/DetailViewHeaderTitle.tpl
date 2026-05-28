{* Plans Detail header title — Marketing hero (Figma) *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	{assign var=MK_PLAN_STATUS value=$RECORD->getDisplayValue('plan_status')}
	{assign var=MK_PLAN_CODE value=$RECORD->get('plan_code')}
	<div class="mk-plan-detail-hero__left">
		<div class="mk-plan-detail-hero__identity clearfix">
			<div class="mk-plan-detail-hero__icon recordImage bg{$MODULE|lower} app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				<span class="mk-plan-detail-hero__icon-glyph" aria-hidden="true">{include file="partials/PlanDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='PLAN'}</span>
			</div>
			<div class="mk-plan-detail-hero__text recordBasicInfo">
				<div class="info-row mk-plan-detail-hero__name-row">
					<h1 class="mk-plan-detail-hero__title">
						<span class="recordLabel pushDown" title="{$RECORD->getName()|escape:'html'}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{decode_html($RECORD->get($NAME_FIELD))}</span>&nbsp;
								{/if}
							{/foreach}
						</span>
					</h1>
				</div>
				<div class="mk-plan-detail-hero__meta">
					{if !empty($MK_PLAN_STATUS)}
						<span class="mk-plan-detail-hero__type-pill">{$MK_PLAN_STATUS}</span>
					{/if}
					{if !empty($MK_PLAN_CODE)}
						<span class="mk-plan-detail-hero__created">{$MK_PLAN_CODE|escape:'html'}</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-lg-6 col-md-6 col-sm-6">
		<div class="record-header clearfix">
			{if !$MODULE}{assign var=MODULE value=$MODULE_NAME}{/if}
			<div class="recordImage bg_{$MODULE}">
				<div class="name"><strong><i class="fa fa-calendar-o"></i></strong></div>
			</div>
			<div class="recordBasicInfo">
				<div class="info-row">
					<h4><span class="recordLabel pushDown" title="{$RECORD->getName()}">
						{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
							{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
							{if $FIELD_MODEL->getPermissions()}<span class="{$NAME_FIELD}">{decode_html($RECORD->get($NAME_FIELD))}</span>&nbsp;{/if}
						{/foreach}
					</span></h4>
				</div>
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
{/if}
{/strip}
