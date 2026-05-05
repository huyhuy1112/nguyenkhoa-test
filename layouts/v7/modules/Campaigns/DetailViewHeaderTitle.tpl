{*+**********************************************************************************
* Campaigns: Modern, premium header (UI only)
*************************************************************************************}

{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Campaigns/resources/CampaignDetail.css')}" />
<style>
	.mk-campaign-record-header{
		border: 1px solid rgba(226,232,240,0.95);
		background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.96));
		border-radius: 16px;
		padding: 14px 14px;
		box-shadow: 0 12px 34px rgba(15,23,42,0.05);
	}
	.mk-campaign-record-header .recordImage{
		border-radius: 14px;
		overflow: hidden;
		box-shadow: 0 10px 26px rgba(15,23,42,0.06);
	}
	.mk-campaign-record-header .recordBasicInfo h4{
		margin: 0;
		font-weight: 900;
		font-size: 16px;
		color: #0f172a;
	}
	.mk-campaign-record-header .recordLabel{
		display: inline-block;
		max-width: 100%;
	}
	.mk-campaign-record-header .record-header{
		background: transparent;
	}
</style>

	<div class="col-lg-6 col-md-6 col-sm-6">
		<div class="record-header clearfix mk-campaign-record-header">
			{if !$MODULE}
				{assign var=MODULE value=$MODULE_NAME}
			{/if}
			<div class="recordImage bg_{$MODULE} app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				<div class="name">
					<span class="mk-campaign-header-icon-wrap">
						<strong><i class="fa fa-bullhorn mk-campaign-header-icon" aria-hidden="true"></i></strong>
					</span>
				</div>
			</div>

			<div class="recordBasicInfo">
				<div class="info-row">
					<h4>
						<span class="recordLabel pushDown mk-campaign-record-label" title="{$RECORD->getName()}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{decode_html($RECORD->get($NAME_FIELD))}</span>&nbsp;
								{/if}
							{/foreach}
						</span>
					</h4>
				</div>
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
{/strip}

