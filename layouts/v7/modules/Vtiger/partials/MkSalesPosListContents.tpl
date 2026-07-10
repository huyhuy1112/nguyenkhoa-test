{* Shared SALES POS list table — Quotes / ServiceContracts / Accounts *}
{include file="PicklistColorMap.tpl"|vtemplate_path:$MODULE}

{if !isset($SELECTED_MENU_CATEGORY)}
	{assign var=SELECTED_MENU_CATEGORY value=""}
{/if}

<div class="col-sm-12 col-xs-12 mk-so-pos-list-enabled">
	<input type="hidden" name="view" id="view" value="{$VIEW}" />
	<input type="hidden" name="cvid" value="{(isset($VIEWID)) ? $VIEWID : ''}" />
	<input type="hidden" name="pageStartRange" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
	<input type="hidden" name="pageEndRange" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
	<input type="hidden" name="previousPageExist" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
	<input type="hidden" name="nextPageExist" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
	<input type="hidden" name="alphabetSearchKey" id="alphabetSearchKey" value="{$MODULE_MODEL->getAlphabetSearchField()}" />
	<input type="hidden" name="Operator" id="Operator" value="{$OPERATOR}" />
	<input type="hidden" name="totalCount" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type='hidden' name="pageNumber" value="{$PAGE_NUMBER}" id='pageNumber'>
	<input type='hidden' name="pageLimit" value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
	<input type="hidden" name="noOfEntries" value="{(isset($LISTVIEW_ENTRIES_COUNT)) ? $LISTVIEW_ENTRIES_COUNT : ''}" id="noOfEntries">
	<input type="hidden" name="currentSearchParams" value="{(isset($SEARCH_DETAILS)) ? Vtiger_Util_Helper::toSafeHTML(Zend_JSON::encode($SEARCH_DETAILS)) : ''}" id="currentSearchParams" />
	<input type="hidden" name="currentTagParams" value="{(isset($TAG_DETAILS)) ? Vtiger_Util_Helper::toSafeHTML(Zend_JSON::encode($TAG_DETAILS)) : ''}" id="currentTagParams" />
	<input type="hidden" name="noFilterCache" value="{(isset($NO_SEARCH_PARAMS_CACHE)) ? $NO_SEARCH_PARAMS_CACHE : ''}" id="noFilterCache" >
	<input type="hidden" name="orderBy" value="{(isset($ORDER_BY)) ? $ORDER_BY : ''}" id="orderBy">
	<input type="hidden" name="sortOrder" value="{(isset($SORT_ORDER)) ? $SORT_ORDER : ''}" id="sortOrder">
	<input type="hidden" name="list_headers" value='{(isset($LIST_HEADER_FIELDS)) ? $LIST_HEADER_FIELDS : ""}'/>
	<input type="hidden" name="tag" value="{(isset($CURRENT_TAG)) ? $CURRENT_TAG : ''}" />
	<input type="hidden" name="folder_id" value="{$FOLDER_ID}" />
	<input type="hidden" name="folder_value" value="{$FOLDER_VALUE}" />
	<input type="hidden" name="viewType" value="{$VIEWTYPE}" />
	<input type="hidden" name="app" id="appName" value="{$SELECTED_MENU_CATEGORY}">
	<input type="hidden" id="isExcelEditSupported" value="{if $MODULE_MODEL->isExcelEditAllowed()}yes{else}no{/if}" />
	{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
		<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
	{/if}

	{if $SEARCH_MODE_RESULTS neq true}
		<div class="mk-so-pos-actions-src hide" aria-hidden="true">
			{include file="ListViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
	{/if}

	<div id="table-content" class="table-container">
		<form name='list' id='listedit' action='' onsubmit="return false;">
			<table id="listview-table" class="table {if isset($LISTVIEW_ENTRIES_COUNT) && $LISTVIEW_ENTRIES_COUNT eq '0'}listview-table-norecords {/if}listview-table mk-so-table mk-so-table-layout">
				<thead>
					<tr class="listViewContentHeader">
						<th class="mk-so-col-star mk-so-pos-control-th">
							<span class="mk-so-pos-star-hdr" aria-hidden="true"><i class="fa fa-star-o"></i></span>
							{if $MODULE_MODEL->isFilterColumnEnabled()}
								<div id="listColumnFilterContainer" class="hide">
									<div class="listColumnFilter {if $CURRENT_CV_MODEL and !($CURRENT_CV_MODEL->isCvEditable())}disabled{/if}"
										 {if $CURRENT_CV_MODEL->isCvEditable()}
											 title="{vtranslate('LBL_CLICK_HERE_TO_MANAGE_LIST_COLUMNS',$MODULE)}"
										 {/if}
										 data-toggle="tooltip" data-placement="bottom" data-container="body">
										<i class="fa fa-th-large"></i>
									</div>
								</div>
							{/if}
						</th>
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
							{if $SEARCH_MODE_RESULTS || ($LISTVIEW_HEADER->getFieldDataType() eq 'multipicklist')}
								{assign var=NO_SORTING value=1}
							{else}
								{assign var=NO_SORTING value=0}
							{/if}
							<th {if isset($COLUMN_NAME) && $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')} nowrap="nowrap" {/if}>
								<a href="#" class="{if $NO_SORTING}noSorting{else}listViewContentHeaderValues{/if}" {if !$NO_SORTING}data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('name')}"{/if} data-field-id='{$LISTVIEW_HEADER->getId()}'>
									{if !$NO_SORTING}
										{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}
											<i class="fa fa-sort {$FASORT_IMAGE}"></i>
										{else}
											<i class="fa fa-sort customsort"></i>
										{/if}
									{/if}
									{assign var=HEADER_TRANSLATED_LABEL value=vtranslate($LISTVIEW_HEADER->get('label'), $LISTVIEW_HEADER->getModuleName())}
									<span class="mk-so-pos-th-label">{$HEADER_TRANSLATED_LABEL}</span>
								</a>
								{if isset($COLUMN_NAME) && $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}
									<a href="#" class="removeSorting"><i class="fa fa-remove"></i></a>
								{/if}
							</th>
						{/foreach}
					</tr>
				</thead>
				<tbody class="overflow-y">
					{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
						{assign var=DATA_ID value=$LISTVIEW_ENTRY->getId()}
						{assign var=DATA_URL value=$LISTVIEW_ENTRY->getDetailViewUrl()}
						<tr class="listViewEntries" data-id='{$DATA_ID}' data-recordUrl='{$DATA_URL}&app={$SELECTED_MENU_CATEGORY}' id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}">
							<td class="listViewRecordActions mk-so-pos-control-td">
								{include file="ListViewRecordActions.tpl"|vtemplate_path:$MODULE}
							</td>
							{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
								{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
								{assign var=LISTVIEW_ENTRY_RAWVALUE value=$LISTVIEW_ENTRY->getRaw($LISTVIEW_HEADER->get('column'))}
								{if $LISTVIEW_HEADER->getFieldDataType() eq 'currency' || $LISTVIEW_HEADER->getFieldDataType() eq 'text'}
									{assign var=LISTVIEW_ENTRY_RAWVALUE value=$LISTVIEW_ENTRY->getTitle($LISTVIEW_HEADER)}
								{/if}
								{assign var=LISTVIEW_ENTRY_VALUE value=$LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME)}
								<td class="listViewEntryValue" data-name="{$LISTVIEW_HEADER->get('name')}" title="{$LISTVIEW_ENTRY->getTitle($LISTVIEW_HEADER)}" data-rawvalue="{$LISTVIEW_ENTRY_RAWVALUE}" data-field-type="{$LISTVIEW_HEADER->getFieldDataType()}">
									<span class="fieldValue">
										<span class="value">
											{if ($LISTVIEW_HEADER->isNameField() eq true or $LISTVIEW_HEADER->get('uitype') eq '4') and $MODULE_MODEL->isListViewNameFieldNavigationEnabled() eq true }
												<span class="mk-so-pos-order-no">{$LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME)}</span>
											{else if $LISTVIEW_HEADER->get('uitype') eq '72'}
												{assign var=CURRENCY_SYMBOL_PLACEMENT value={$CURRENT_USER_MODEL->get('currency_symbol_placement')}}
												{if $CURRENCY_SYMBOL_PLACEMENT eq '1.0$'}
													{$LISTVIEW_ENTRY_VALUE}{$LISTVIEW_ENTRY->get('currencySymbol')}
												{else}
													{$LISTVIEW_ENTRY->get('currencySymbol')}{$LISTVIEW_ENTRY_VALUE}
												{/if}
											{else if $LISTVIEW_HEADER->get('uitype') eq '71'}
												{assign var=CURRENCY_SYMBOL value=$LISTVIEW_ENTRY->get('userCurrencySymbol')}
												{if $LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME) neq NULL}
													{CurrencyField::appendCurrencySymbol($LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME), $CURRENCY_SYMBOL)}
												{/if}
											{else if $LISTVIEW_HEADER->getFieldDataType() eq 'picklist'}
												{assign var=PICKLIST_FIELD_ID value={$LISTVIEW_HEADER->getId()}}
												<span {if !empty($LISTVIEW_ENTRY_VALUE)} class="picklist-color picklist-{$PICKLIST_FIELD_ID}-{Vtiger_Util_Helper::convertSpaceToHyphen($LISTVIEW_ENTRY_RAWVALUE)}" {/if}> {$LISTVIEW_ENTRY_VALUE} </span>
											{else}
												{$LISTVIEW_ENTRY_VALUE}
											{/if}
										</span>
									</span>
								</td>
							{/foreach}
						</tr>
					{/foreach}
					{if isset($LISTVIEW_ENTRIES_COUNT) && $LISTVIEW_ENTRIES_COUNT eq '0'}
						<tr class="emptyRecordsDiv">
							{assign var=COLSPAN_WIDTH value={php7_count($LISTVIEW_HEADERS)}+1}
							<td colspan="{$COLSPAN_WIDTH}">
								<div class="emptyRecordsContent">
									{vtranslate('LBL_NO')} {vtranslate($MODULE, $MODULE)} {vtranslate('LBL_FOUND')}.
								</div>
							</td>
						</tr>
					{/if}
				</tbody>
			</table>
		</form>
	</div>
	<div id="scroller_wrapper" class="bottom-fixed-scroll">
		<div id="scroller" class="scroller-div"></div>
	</div>
</div>
