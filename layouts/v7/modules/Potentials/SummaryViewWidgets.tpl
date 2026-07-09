{*<!--
/*********************************************************************************
** Potentials SummaryViewWidgets.tpl
** SALES app: render Key Fields / Documents / Activities / Comments / Products /
** Contacts inside a modern 2-column grid (.mk-opportunity-detail-summary-grid).
** Non-SALES paths fall back to the legacy 3-column layout to keep parity.
********************************************************************************/
-->*}
{strip}
	{foreach item=DETAIL_VIEW_WIDGET from=$DETAILVIEW_LINKS['DETAILVIEWWIDGET']}
		{if ($DETAIL_VIEW_WIDGET->getLabel() eq 'Documents') }
			{assign var=DOCUMENT_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'LBL_RELATED_CONTACTS')}
			{assign var=CONTACT_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->get('linkName') eq 'ProductsServices')}
			{assign var=PRODUCT_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'ModComments')}
			{assign var=COMMENTS_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'LBL_UPDATES')}
			{assign var=UPDATES_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{/if}
	{/foreach}

{if $MODULE eq 'Potentials' || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY|trim eq 'SALES')) || (isset($smarty.get.app) && ($smarty.get.app|trim eq 'SALES'))}
	<div class="mk-opportunity-detail-summary-grid">
		<section class="mk-opportunity-detail-card mk-opportunity-detail-card--key mk-opportunity-detail-grid__key" aria-labelledby="mk-opportunity-detail-keyfields-title">
			<div class="mk-opportunity-detail-card__head">
				<h2 id="mk-opportunity-detail-keyfields-title" class="mk-opportunity-detail-card__title">{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h2>
			</div>
			<div class="summaryView mk-opportunity-detail-summaryView">
				<div class="summaryViewFields mk-opportunity-detail-kv-wrap">
					{$MODULE_SUMMARY}
				</div>
				{if !empty($MK_OPP_FULL_ADDRESS)}
				<div class="mk-opportunity-detail-key-address" aria-label="{vtranslate('LBL_MK_OPP_ADDRESS', 'Potentials')}">
					<span class="mk-opportunity-detail-key-address__ic" aria-hidden="true">📍</span>
					<div class="mk-opportunity-detail-key-address__body">
						<div class="mk-opportunity-detail-key-address__label">{vtranslate('LBL_MK_OPP_ADDRESS', 'Potentials')}</div>
						<p class="mk-opportunity-detail-key-address__text">{$MK_OPP_FULL_ADDRESS|escape}</p>
					</div>
				</div>
				{/if}
			</div>
		</section>

		<section class="mk-opportunity-detail-card mk-opportunity-detail-card--activities mk-opportunity-detail-grid__activities" aria-labelledby="mk-opportunity-detail-activities-title">
			<div id="relatedActivities" class="mk-opportunity-detail-related-activities">
				{$RELATED_ACTIVITIES}
			</div>
		</section>

		{if $COMMENTS_WIDGET_MODEL}
		<section class="mk-opportunity-detail-card mk-opportunity-detail-card--comments mk-opportunity-detail-grid__comments" aria-labelledby="mk-opportunity-detail-comments-title">
			<div class="summaryWidgetContainer mk-opportunity-detail-widget-host">
				<div class="widgetContainer_comments" data-url="{$COMMENTS_WIDGET_MODEL->getUrl()}" data-name="{$COMMENTS_WIDGET_MODEL->getLabel()}">
					<div class="widget_header mk-opportunity-detail-card__head">
						<input type="hidden" name="relatedModule" value="{$COMMENTS_WIDGET_MODEL->get('linkName')}" />
						<h2 id="mk-opportunity-detail-comments-title" class="mk-opportunity-detail-card__title">{vtranslate($COMMENTS_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h2>
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		</section>
		{/if}

		{if $CONTACT_WIDGET_MODEL}
		<section class="mk-opportunity-detail-card mk-opportunity-detail-card--contacts mk-opportunity-detail-grid__contacts" aria-labelledby="mk-opportunity-detail-contacts-title">
			<div class="summaryWidgetContainer mk-opportunity-detail-widget-host">
				<div class="widgetContainer_contacts" data-url="{$CONTACT_WIDGET_MODEL->getUrl()}" data-name="{$CONTACT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix mk-opportunity-detail-card__head mk-opportunity-detail-contacts__head">
						<input type="hidden" name="relatedModule" value="{$CONTACT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h2 id="mk-opportunity-detail-contacts-title" class="mk-opportunity-detail-card__title display-inline-block pull-left">{vtranslate($CONTACT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h2>

						{if $CONTACT_WIDGET_MODEL->get('action')}
							<div class="pull-right">
								<button class="btn addButton btn-sm btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost createRecord" type="button" data-url="{$CONTACT_WIDGET_MODEL->get('actionURL')}">
									<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD',$MODULE_NAME)}
								</button>
							</div>
						{/if}
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		</section>
		{/if}

		<section class="mk-opportunity-detail-card mk-opportunity-detail-card--service-contracts mk-opportunity-detail-grid__service-contracts" id="mk-opp-section-service-contracts" aria-labelledby="mk-opp-service-contracts-title">
			<div class="mk-opportunity-detail-card__head mk-opp-service-contracts__head">
				<span class="mk-opp-service-contracts__ic" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M7 4h10v16H7z" stroke="currentColor" stroke-width="1.6"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
				</span>
				<h2 id="mk-opp-service-contracts-title" class="mk-opportunity-detail-card__title">Hợp đồng dịch vụ (0)</h2>
				<div class="mk-opportunity-detail-card__actions pull-right">
					<a class="btn btn-sm btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost" id="mk-opp-add-service-contract" href="index.php?module=ServiceContracts&amp;view=Edit&amp;app=SALES">+ Tạo hợp đồng</a>
				</div>
			</div>
			<div class="mk-opp-service-contracts__body" id="mk-opp-service-contracts-body"></div>
		</section>

		{if $DOCUMENT_WIDGET_MODEL}
		<section class="mk-opportunity-detail-card mk-opportunity-detail-card--documents mk-opportunity-detail-grid__documents" aria-labelledby="mk-opportunity-detail-documents-title">
			<div class="summaryWidgetContainer mk-opportunity-detail-widget-host">
				<div class="widgetContainer_documents" data-url="{$DOCUMENT_WIDGET_MODEL->getUrl()}" data-name="{$DOCUMENT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix mk-opportunity-detail-card__head mk-opportunity-detail-documents__head">
						<input type="hidden" name="relatedModule" value="{$DOCUMENT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h2 id="mk-opportunity-detail-documents-title" class="mk-opportunity-detail-card__title display-inline-block pull-left">{vtranslate($DOCUMENT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h2>

						{if $DOCUMENT_WIDGET_MODEL->get('action')}
							{assign var=PARENT_ID value=$RECORD->getId()}
							<div class="pull-right">
								<div class="dropdown">
									<button type="button" class="btn btn-default dropdown-toggle mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost" data-toggle="dropdown">
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
					<div class="widget_contents mk-opportunity-detail-documents__body"></div>
				</div>
			</div>
		</section>
		{/if}

		{if $PRODUCT_WIDGET_MODEL}
		<section class="mk-opportunity-detail-card mk-opportunity-detail-card--products mk-opportunity-detail-grid__products" aria-labelledby="mk-opportunity-detail-products-title">
			<div class="summaryWidgetContainer mk-opportunity-detail-widget-host">
				<div class="widgetContainer_products" data-url="{$PRODUCT_WIDGET_MODEL->getUrl()}" data-name="{$PRODUCT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix mk-opportunity-detail-card__head mk-opportunity-detail-products__head">
						<input type="hidden" name="relatedModule" value="{$PRODUCT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h2 id="mk-opportunity-detail-products-title" class="mk-opportunity-detail-card__title display-inline-block pull-left">{vtranslate($PRODUCT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h2>

						{if $PRODUCT_WIDGET_MODEL->get('action')}
							<div class="pull-right">
								<button class="btn addButton btn-sm btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost potentialsSummaryProductsServicesAdd" type="button">
									<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD',$MODULE_NAME)}
								</button>
							</div>
						{/if}
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		</section>
		{/if}
	</div>

	{* Viet Task PT12 - Add (preserve existing JS bridge) *}
	{if isset($RECORD) && $RECORD->get('related_to') neq ''}
		{assign var="related_to" value=$RECORD->get('related_to')}
		{literal}
		<script>
			window.potential_account_id = "{/literal}{$related_to|escape:'html'}{literal}";
		</script>
		{/literal}
	{/if}
{else}
	{* ----- legacy 3-column layout for non-SALES apps (unchanged) ----- *}
	<div class="left-block col-lg-4 col-md-4 col-sm-4">
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
												<img style="margin-top: -3px;margin-right: 4%;" title="Vtiger" alt="Vtiger" src="layouts/v7/skins//images/Vtiger.png">
												{vtranslate('LBL_TO_SERVICE', 'Documents', {vtranslate('LBL_VTIGER', 'Documents')})}
											</a>
										</li>
										<li role="separator" class="divider"></li>
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

	<div class="middle-block col-lg-4 col-md-4 col-sm-4">
		<div id="relatedActivities">
			{$RELATED_ACTIVITIES}
		</div>
		{if $COMMENTS_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_comments" data-url="{$COMMENTS_WIDGET_MODEL->getUrl()}" data-name="{$COMMENTS_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix">
						<input type="hidden" name="relatedModule" value="{$COMMENTS_WIDGET_MODEL->get('linkName')}" />
						<h4 class="display-inline-block">{vtranslate($COMMENTS_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		{/if}
	</div>

	<div class="right-block col-lg-4 col-sm-4 col-md-4">
		{if $PRODUCT_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_products" data-url="{$PRODUCT_WIDGET_MODEL->getUrl()}" data-name="{$PRODUCT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix">
						<input type="hidden" name="relatedModule" value="{$PRODUCT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h4 class="display-inline-block pull-left">{vtranslate($PRODUCT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>

						{if $PRODUCT_WIDGET_MODEL->get('action')}
							<div class="pull-right">
								<button class="btn addButton btn-sm btn-default potentialsSummaryProductsServicesAdd" type="button">
									<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD',$MODULE_NAME)}
								</button>
							</div>
						{/if}
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		{/if}

		{if $CONTACT_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_contacts" data-url="{$CONTACT_WIDGET_MODEL->getUrl()}" data-name="{$CONTACT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix">
						<input type="hidden" name="relatedModule" value="{$CONTACT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h4 class="display-inline-block pull-left">{vtranslate($CONTACT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>

						{if $CONTACT_WIDGET_MODEL->get('action')}
							<div class="pull-right">
								<button class="btn addButton btn-sm btn-default createRecord" type="button" data-url="{$CONTACT_WIDGET_MODEL->get('actionURL')}">
									<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD',$MODULE_NAME)}
								</button>
							</div>
						{/if}
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		{/if}
	</div>

	{if isset($RECORD) && $RECORD->get('related_to') neq ''}
		{assign var="related_to" value=$RECORD->get('related_to')}
		{literal}
		<script>
			window.potential_account_id = "{/literal}{$related_to|escape:'html'}{literal}";
		</script>
		{/literal}
	{/if}
{/if}
{/strip}
