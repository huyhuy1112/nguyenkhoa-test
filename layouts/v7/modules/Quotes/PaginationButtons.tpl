{* Prev / page jump / next — matches QuoteList.css .mk-qt-pagination *}
{if !$CLASS_VIEW_ACTION}
    {assign var=CLASS_VIEW_ACTION value='listViewActions'}
    {assign var=CLASS_VIEW_PAGING_INPUT value='listViewPagingInput'}
    {assign var=CLASS_VIEW_PAGING_INPUT_SUBMIT value='listViewPagingInputSubmit'}
    {assign var=CLASS_VIEW_BASIC_ACTION value='listViewBasicAction'}
{/if}
<div class="{$CLASS_VIEW_ACTION} mk-qt-pagination__btns">
	<div class="btn-group">
		<button type="button" id="PreviousPageButton" class="btn btn-default mk-qt-page-btn" {if !$PAGING_MODEL->isPrevPageExists()} disabled {/if}><i class="fa fa-caret-left"></i></button>
		{if $SHOWPAGEJUMP}
			<button type="button" id="PageJump" data-toggle="dropdown" class="btn btn-default mk-qt-page-btn">
				<i class="fa fa-ellipsis-h icon" title="{vtranslate('LBL_LISTVIEW_PAGE_JUMP',$MODULE)}"></i>
			</button>
			<ul class="{$CLASS_VIEW_BASIC_ACTION} dropdown-menu" id="PageJumpDropDown">
				<li>
					<div class="listview-pagenum">
						<span>{vtranslate('LBL_PAGE',$MODULE)}</span>&nbsp;
						<strong><span>{$PAGE_NUMBER}</span></strong>&nbsp;
						<span>{vtranslate('LBL_OF',$MODULE)}</span>&nbsp;
						<strong><span id="totalPageCount"></span></strong>
					</div>
					<div class="listview-pagejump">
						<input type="text" id="pageToJump" placeholder="{vtranslate('LBL_LISTVIEW_JUMP_TO',$MODULE)}" class="{$CLASS_VIEW_PAGING_INPUT} text-center"/>&nbsp;
						<button type="button" id="pageToJumpSubmit" class="btn btn-success {$CLASS_VIEW_PAGING_INPUT_SUBMIT} text-center">{'GO'}</button>
					</div>
				</li>
			</ul>
		{/if}
		<button type="button" id="NextPageButton" class="btn btn-default mk-qt-page-btn" {if !$PAGING_MODEL->isNextPageExists()}disabled{/if}><i class="fa fa-caret-right"></i></button>
	</div>
</div>
