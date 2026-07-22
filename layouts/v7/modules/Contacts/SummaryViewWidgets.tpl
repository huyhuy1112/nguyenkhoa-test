{* Contacts SummaryViewWidgets: SALES 2-column grid; legacy layout for other apps. *}
{strip}
{foreach item=DETAIL_VIEW_WIDGET from=$DETAILVIEW_LINKS['DETAILVIEWWIDGET']}
	{if ($DETAIL_VIEW_WIDGET->getLabel() eq 'Documents') }
		{assign var=DOCUMENT_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
	{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'ModComments')}
		{assign var=COMMENTS_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
	{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'LBL_UPDATES')}
		{assign var=UPDATES_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
	{/if}
{/foreach}

{if !empty($MK_CONTACT_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
	<div class="mk-contact-summary-wrap">
		<div class="mk-contact-class-reg-strip mk-contact-class-reg-strip--summary">
			{include file="partials/ContactClassRegPanel.tpl"|vtemplate_path:$MODULE}
		</div>
	<div class="mk-contact-summary-grid mk-contact-detail-summary-grid">
		<section class="mk-contact-detail-card mk-contact-detail-card--key mk-contact-detail-grid__key" aria-labelledby="mk-contact-detail-keyfields-title">
			<div class="mk-contact-detail-card__head">
				<h2 id="mk-contact-detail-keyfields-title" class="mk-contact-detail-card__title">{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h2>
			</div>
			<div class="summaryView mk-contact-detail-summaryView">
				<div class="summaryViewFields mk-contact-detail-kv-wrap">
					{$MODULE_SUMMARY}
				</div>
			</div>
		</section>

		<section class="mk-contact-detail-card mk-contact-detail-card--activities mk-contact-detail-grid__activities" aria-labelledby="mk-contact-detail-activities-title">
			<div id="relatedActivities" class="mk-contact-detail-related-activities">
				{$RELATED_ACTIVITIES}
			</div>
		</section>

		<section class="mk-contact-detail-card mk-contact-detail-card--purchases mk-contact-detail-grid__salesorders" id="mk-contact-section-purchases" aria-labelledby="mk-contact-purchase-title">
			<div class="mk-contact-detail-card__head mk-contact-purchase__head">
				<span class="mk-contact-purchase__cart-ic" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12L6 6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.5" fill="currentColor"/><circle cx="17" cy="20" r="1.5" fill="currentColor"/></svg>
				</span>
				<h2 id="mk-contact-purchase-title" class="mk-contact-detail-card__title">Lịch sử mua hàng (0)</h2>
				<div class="mk-contact-detail-card__actions pull-right">
					<button type="button" class="btn btn-sm btn-default mk-contact-detail-btn mk-contact-detail-btn--ghost" id="mk-contact-link-order">Liên kết đơn hàng</button>
				</div>
			</div>
			<div class="mk-contact-commerce-tabs mk-contact-purchase__tabs" role="tablist" aria-label="Lịch sử mua hàng">
				<button type="button" class="mk-contact-commerce-tabs__btn is-active" data-mk-contact-commerce-tab="orders-month" role="tab" aria-selected="true">Đơn hàng trong 1 tháng</button>
				<button type="button" class="mk-contact-commerce-tabs__btn" data-mk-contact-commerce-tab="products-total" role="tab" aria-selected="false">Tổng sản phẩm mua hàng</button>
			</div>
			<div class="mk-contact-purchase__body">
				<div class="mk-contact-commerce-panel" id="mk-contact-commerce-orders-month" data-mk-contact-commerce-panel="orders-month" role="tabpanel">
					<p class="mk-contact-purchase__empty">Không có đơn trong 30 ngày gần nhất.</p>
				</div>
				<div class="mk-contact-commerce-panel hide" id="mk-contact-commerce-products-total" data-mk-contact-commerce-panel="products-total" role="tabpanel">
					<p class="mk-contact-purchase__empty">Chưa có sản phẩm mua hàng.</p>
				</div>
			</div>
		</section>

	</div>
	</div>
{else}
	<div class="left-block col-lg-4">
		<div class="summaryView">
			<div class="summaryViewHeader">
				<h4 class="display-inline-block">{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h4>
			</div>
			<div class="summaryViewFields">
				{$MODULE_SUMMARY}
			</div>
		</div>

		{if $DOCUMENT_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_documents" data-url="{$DOCUMENT_WIDGET_MODEL->getUrl()}" data-name="{$DOCUMENT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix">
						<input type="hidden" name="relatedModule" value="{$DOCUMENT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h4 class="display-inline-block pull-left">{vtranslate($DOCUMENT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>

						{if $DOCUMENT_WIDGET_MODEL->get('action')}
							{assign var=PARENT_ID value=$RECORD->getId()}
							<div class="pull-right">
								<div class="dropdown">
									<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
										<span class="fa fa-plus" title="{vtranslate('LBL_NEW_DOCUMENT', $MODULE_NAME)}"></span>&nbsp;{vtranslate('LBL_NEW_DOCUMENT', 'Documents')}&nbsp; <span class="caret"></span>
									</button>
									<ul class="dropdown-menu">
										<li class="dropdown-header"><i class="fa fa-upload"></i> {vtranslate('LBL_FILE_UPLOAD', 'Documents')}</li>
										<li id="VtigerAction">
											<a href="javascript:Documents_Index_Js.uploadTo('Vtiger',{$PARENT_ID},'{$MODULE_NAME}')">
												<img style="  margin-top: -3px;margin-right: 4%;" title="Vtiger" alt="Vtiger" src="layouts/v7/skins//images/Vtiger.png">
												{vtranslate('LBL_TO_SERVICE', 'Documents', {vtranslate('LBL_VTIGER', 'Documents')})}
											</a>
										</li>
										<li class="dropdown-header"><i class="fa fa-link"></i> {vtranslate('LBL_LINK_EXTERNAL_DOCUMENT', 'Documents')}</li>
										<li id="shareDocument"><a href="javascript:Documents_Index_Js.createDocument('E',{$PARENT_ID},'{$MODULE_NAME}')">&nbsp;<i class="fa fa-external-link"></i>&nbsp;&nbsp; {vtranslate('LBL_FROM_SERVICE', 'Documents', {vtranslate('LBL_FILE_URL', 'Documents')})}</a></li>
										<li role="separator" class="divider"></li>
										<li id="createDocument"><a href="javascript:Documents_Index_Js.createDocument('W',{$PARENT_ID},'{$MODULE_NAME}')"><i class="fa fa-file-text"></i> {vtranslate('LBL_CREATE_NEW', 'Documents', {vtranslate('SINGLE_Documents', 'Documents')})}</a></li>
									</ul>
								</div>
							</div>
						{/if}
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		{/if}
	</div>

	<div class="middle-block col-lg-8">
		<div id="relatedActivities">
			{$RELATED_ACTIVITIES}
		</div>

		{if $COMMENTS_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_comments" data-url="{$COMMENTS_WIDGET_MODEL->getUrl()}" data-name="{$COMMENTS_WIDGET_MODEL->getLabel()}">
					<div class="widget_header">
						<input type="hidden" name="relatedModule" value="{$COMMENTS_WIDGET_MODEL->get('linkName')}" />
						<h4 class="display-inline-block">{vtranslate($COMMENTS_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		{/if}
	</div>
{/if}
{/strip}
