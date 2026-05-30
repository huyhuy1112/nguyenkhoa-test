{* Documents Detail header title — MANAGEMENT hero *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
	<div class="col-lg-6 col-md-6 col-sm-6 mk-documents-detail-hero__left">
		<div class="record-header clearfix mk-documents-detail-hero__identity">
			<div class="recordImage bgdocuments app-{$SELECTED_MENU_CATEGORY} mk-documents-detail-hero__avatar">
				<span class="mk-documents-detail-hero__icon-glyph" aria-hidden="true"><i class="fa fa-file-text-o"></i></span>
			</div>
			<div class="recordBasicInfo mk-documents-detail-hero__text">
				<div class="info-row">
					<h1 class="mk-documents-detail-hero__title">
						<div class="recordLabel pushDown" title="{$RECORD->getName()|escape:'html'}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{decode_html($RECORD->get($NAME_FIELD))}</span>&nbsp;
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
	{include file="DetailViewHeaderTitle.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
