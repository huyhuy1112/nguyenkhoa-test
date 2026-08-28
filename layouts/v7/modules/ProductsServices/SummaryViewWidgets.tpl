{* ProductsServices Summary — clean catalog layout (info + documents only). *}
{strip}
{if isset($DETAILVIEW_LINKS) && isset($DETAILVIEW_LINKS['DETAILVIEWWIDGET'])}
{foreach item=DETAIL_VIEW_WIDGET from=$DETAILVIEW_LINKS['DETAILVIEWWIDGET']}
	{if ($DETAIL_VIEW_WIDGET->getLabel() eq 'Documents') }
		{assign var=DOCUMENT_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
	{/if}
{/foreach}
{/if}

{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
	<div class="mk-ps-v2-summary" data-mk-ps-detail-v2="1">
		<section class="mk-ps-v2-card mk-ps-v2-card--info" aria-labelledby="mk-ps-v2-info-title">
			<header class="mk-ps-v2-card__head">
				<span class="mk-ps-v2-card__accent" aria-hidden="true"></span>
				<div class="mk-ps-v2-card__head-text">
					<h2 id="mk-ps-v2-info-title" class="mk-ps-v2-card__title">Thông tin hàng hoá</h2>
					<p class="mk-ps-v2-card__hint">Các trường chính — xem tab Chi tiết để xem đầy đủ</p>
				</div>
			</header>
			<div class="summaryView mk-ps-v2-summaryView">
				<div class="summaryViewFields mk-ps-v2-fields">
					{if $MODULE_SUMMARY}
						{$MODULE_SUMMARY}
					{else}
						<p class="mk-ps-v2-empty">Không có trường tóm tắt để hiển thị. Mở tab <strong>Chi tiết</strong> để xem đầy đủ.</p>
					{/if}
				</div>
			</div>
		</section>

		{if $DOCUMENT_WIDGET_MODEL}
		<section class="mk-ps-v2-card mk-ps-v2-card--docs" aria-labelledby="mk-ps-v2-docs-title">
			<div class="summaryWidgetContainer mk-ps-v2-widget-host">
				<div class="widgetContainer_documents" data-url="{$DOCUMENT_WIDGET_MODEL->getUrl()}" data-name="{$DOCUMENT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix mk-ps-v2-card__head mk-ps-v2-docs__head">
						<input type="hidden" name="relatedModule" value="{$DOCUMENT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left hide" aria-hidden="true"><i class="fa fa-angle-down"></i></span>
						<span class="mk-ps-v2-card__accent" aria-hidden="true"></span>
						<div class="mk-ps-v2-card__head-text">
							<h2 id="mk-ps-v2-docs-title" class="mk-ps-v2-card__title">Tài liệu</h2>
							<p class="mk-ps-v2-card__hint">Đính kèm hồ sơ, chứng nhận, hình ảnh sản phẩm</p>
						</div>
						{if $DOCUMENT_WIDGET_MODEL->get('action')}
							{assign var=PARENT_ID value=$RECORD->getId()}
							<div class="mk-ps-v2-docs__actions pull-right">
								<div class="dropdown">
									<button type="button" class="btn btn-default dropdown-toggle mk-ps-v2-btn mk-ps-v2-btn--ghost" data-toggle="dropdown">
										<span class="fa fa-plus"></span>&nbsp;Thêm tài liệu&nbsp;<span class="caret"></span>
									</button>
									<ul class="dropdown-menu dropdown-menu-right">
										<li class="dropdown-header"><i class="fa fa-upload"></i> {vtranslate('LBL_FILE_UPLOAD', 'Documents')}</li>
										<li id="VtigerAction">
											<a href="javascript:Documents_Index_Js.uploadTo('Vtiger',{$PARENT_ID},'{$MODULE_NAME}')">
												{vtranslate('LBL_TO_SERVICE', 'Documents', {vtranslate('LBL_VTIGER', 'Documents')})}
											</a>
										</li>
										<li role="separator" class="divider"></li>
										<li class="dropdown-header"><i class="fa fa-link"></i> {vtranslate('LBL_LINK_EXTERNAL_DOCUMENT', 'Documents')}</li>
										<li id="shareDocument"><a href="javascript:Documents_Index_Js.createDocument('E',{$PARENT_ID},'{$MODULE_NAME}')">{vtranslate('LBL_FROM_SERVICE', 'Documents', {vtranslate('LBL_FILE_URL', 'Documents')})}</a></li>
										<li role="separator" class="divider"></li>
										<li id="createDocument"><a href="javascript:Documents_Index_Js.createDocument('W',{$PARENT_ID},'{$MODULE_NAME}')">{vtranslate('LBL_CREATE_NEW', 'Documents', {vtranslate('SINGLE_Documents', 'Documents')})}</a></li>
									</ul>
								</div>
							</div>
						{/if}
					</div>
					<div class="widget_contents mk-ps-v2-docs__body"></div>
				</div>
			</div>
		</section>
		{/if}

		{* Activities kept out of layout (catalog master). *}
		<div id="relatedActivities" class="hide" aria-hidden="true">{if isset($RELATED_ACTIVITIES)}{$RELATED_ACTIVITIES}{/if}</div>
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
