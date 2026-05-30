{* Project Detail header title — MANAGEMENT hero | legacy elsewhere *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
	<div class="col-lg-6 col-md-6 col-sm-6 mk-project-detail-hero__left">
		<div class="record-header clearfix mk-project-detail-hero__identity">
			<div class="recordImage bgproject app-{$SELECTED_MENU_CATEGORY} mk-project-detail-hero__avatar">
				<span class="mk-project-detail-hero__icon-glyph" aria-hidden="true">{include file="partials/ProjectDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PROJECT'}</span>
			</div>
			<div class="recordBasicInfo mk-project-detail-hero__text">
				<div class="info-row">
					<h1 class="mk-project-detail-hero__title">
						<div class="recordLabel pushDown" title="{$RECORD->getName()}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{$RECORD->get($NAME_FIELD)}</span>&nbsp;
								{/if}
							{/foreach}
						</div>
					</h1>
				</div>
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
{else}
    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="record-header clearfix">
            <div class="recordImage bgproject app-{$SELECTED_MENU_CATEGORY}">
                <div class="name"><span><strong> <i class="vicon-project"></i> </strong></span></div>
            </div>
            <div class="recordBasicInfo">
                <div class="info-row">
                    <h4>
                        <div class="recordLabel pushDown" title="{$RECORD->getName()}">
                            {foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
                                {assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
                                {if $FIELD_MODEL->getPermissions()}
                                    <span class="{$NAME_FIELD}">{$RECORD->get($NAME_FIELD)}</span>&nbsp;
                                {/if}
                            {/foreach}
                        </div>
                    </h4>
                </div>
                {include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
            </div>
        </div>
    </div>
{/if}
{/strip}
