{* Invoice SummaryViewWidgets: TOOLS/SUPPORT grid — Key+Documents left, Activities+Comments right. *}
{strip}
{foreach item=DETAIL_VIEW_WIDGET from=$DETAILVIEW_LINKS['DETAILVIEWWIDGET']}
	{if ($DETAIL_VIEW_WIDGET->getLabel() eq 'Documents') }
		{assign var=DOCUMENT_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
	{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'LBL_UPDATES')}
		{assign var=UPDATES_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
	{/if}
{/foreach}

{assign var=MK_INV_MK_DETAIL value=false}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SUPPORT' || $SELECTED_MENU_CATEGORY eq 'TOOLS' || $SELECTED_MENU_CATEGORY eq 'SALES')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SUPPORT' || $smarty.get.app eq 'TOOLS' || $smarty.get.app eq 'SALES')) || (isset($smarty.request.app) && ($smarty.request.app eq 'SUPPORT' || $smarty.request.app eq 'TOOLS' || $smarty.request.app eq 'SALES'))}
	{assign var=MK_INV_MK_DETAIL value=true}
{/if}
{if $MK_INV_MK_DETAIL}
	<div class="mk-inv-detail-summary-grid">
		<section class="mk-inv-detail-card mk-inv-detail-card--key mk-inv-detail-summary-grid__key" aria-labelledby="mk-inv-detail-keyfields-title">
			<div class="mk-inv-detail-card__head">
				<h2 id="mk-inv-detail-keyfields-title" class="mk-inv-detail-card__title">{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h2>
			</div>
			<div class="summaryView mk-inv-detail-summaryView">
				<div class="summaryViewFields mk-inv-detail-kv-wrap">
					{$MODULE_SUMMARY}
				</div>
			</div>
		</section>

		<section class="mk-inv-detail-card mk-inv-detail-card--activities mk-inv-detail-summary-grid__activities">
			<div id="relatedActivities" class="mk-inv-detail-related-activities">
				{$RELATED_ACTIVITIES}
			</div>
		</section>

		{if isset($RECORD_STRUCTURE) && $RECORD_STRUCTURE|@count gt 0}
		<section class="mk-inv-detail-card mk-inv-detail-card--overview mk-inv-detail-summary-grid__overview" aria-labelledby="mk-inv-detail-overview-title">
			<div class="mk-inv-detail-card__head">
				<h2 id="mk-inv-detail-overview-title" class="mk-inv-detail-card__title">{vtranslate('LBL_DETAILS', $MODULE_NAME)}</h2>
			</div>
			<div class="mk-inv-detail-overview-body">
				{include file='DetailViewBlockView.tpl'|@vtemplate_path:'Inventory' RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
			</div>
		</section>
		{/if}

		{if $DOCUMENT_WIDGET_MODEL}
		<section class="mk-inv-detail-card mk-inv-detail-card--documents mk-inv-detail-summary-grid__documents" aria-labelledby="mk-inv-detail-documents-title">
			<div class="summaryWidgetContainer mk-inv-detail-widget-host">
				<div class="widgetContainer_documents" data-url="{$DOCUMENT_WIDGET_MODEL->getUrl()}" data-name="{$DOCUMENT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix mk-inv-detail-card__head mk-inv-detail-documents__head">
						<input type="hidden" name="relatedModule" value="{$DOCUMENT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h2 id="mk-inv-detail-documents-title" class="mk-inv-detail-card__title"><span class="mk-inv-detail-heading-svg mk-inv-detail-heading-svg--file" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>{vtranslate($DOCUMENT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h2>
						{if $DOCUMENT_WIDGET_MODEL->get('action')}
							{assign var=PARENT_ID value=$RECORD->getId()}
							<div class="pull-right">
								<div class="dropdown">
									<button type="button" class="btn btn-default dropdown-toggle mk-inv-detail-btn mk-inv-detail-btn--ghost" data-toggle="dropdown">
										<span class="fa fa-plus" title="{vtranslate('LBL_NEW_DOCUMENT', $MODULE_NAME)}"></span>&nbsp;{vtranslate('LBL_NEW_DOCUMENT', 'Documents')}&nbsp; <span class="caret"></span>
									</button>
									<ul class="dropdown-menu">
										<li class="dropdown-header"><i class="fa fa-upload"></i> {vtranslate('LBL_FILE_UPLOAD', 'Documents')}</li>
										<li id="VtigerAction">
											<a href="javascript:Documents_Index_Js.uploadTo('Vtiger',{$PARENT_ID},'{$MODULE_NAME}')">
												<img style="margin-top: -3px;margin-right: 4%;" title="Vtiger" alt="Vtiger" src="layouts/v7/skins//images/Vtiger.png">
												{vtranslate('LBL_TO_SERVICE', 'Documents', {vtranslate('LBL_VTIGER', 'Documents')})}
											</a>
										</li>
										<li role="separator" class="divider"></li>
										<li class="dropdown-header"><i class="fa fa-link"></i> {vtranslate('LBL_LINK_EXTERNAL_DOCUMENT', 'Documents')}</li>
										<li id="shareDocument"><a href="javascript:Documents_Index_Js.createDocument('E',{$PARENT_ID},'{$MODULE_NAME}')">&nbsp;<i class="fa fa-external-link"></i>&nbsp;&nbsp; {vtranslate('LBL_FROM_SERVICE', 'Documents', {vtranslate('LBL_FILE_URL', 'Documents')})}</a></li>
										<li role="separator" class="divider"></li>
										<li id="createDocument"><a href="javascript:Documents_Index_Js.createDocument('W',{$PARENT_ID},'{$MODULE_NAME}')"><i class="fa fa-file-text"></i> {vtranslate('LBL_CREATE_NEW', 'Documents', {vtranslate('SINGLE_Documents', 'Documents')})}</a></li>
									</ul>
								</div>
							</div>
						{/if}
					</div>
					<div class="widget_contents mk-inv-detail-documents__body"></div>
				</div>
			</div>
		</section>
		{/if}

		
	</div>
{else}
<div class="left-block col-lg-5">
	<div class="summaryView">
		<div class="summaryViewHeader"><h4>{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h4></div>
		<div class="summaryViewFields">{$MODULE_SUMMARY}</div>
	</div>
	{if $DOCUMENT_WIDGET_MODEL}
	<div class="summaryWidgetContainer">
		<div class="widgetContainer_documents" data-url="{$DOCUMENT_WIDGET_MODEL->getUrl()}" data-name="{$DOCUMENT_WIDGET_MODEL->getLabel()}">
			<div class="widget_header clearfix">
				<input type="hidden" name="relatedModule" value="{$DOCUMENT_WIDGET_MODEL->get('linkName')}" />
				<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
				<h3 class="display-inline-block pull-left">{vtranslate($DOCUMENT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h3>
			</div>
			<div class="widget_contents"></div>
		</div>
	</div>
	{/if}
</div>
<div class="middle-block col-lg-7">
	<div id="relatedActivities">{$RELATED_ACTIVITIES}</div>
	
</div>
{/if}
{/strip}
