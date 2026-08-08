{* SALES list pagination — page label + prev/next (keeps Vtiger #PreviousPageButton / #PageJump / #NextPageButton) *}
{if !$CLASS_VIEW_ACTION}
	{assign var=CLASS_VIEW_ACTION value='listViewActions'}
	{assign var=CLASS_VIEW_PAGING_INPUT value='listViewPagingInput'}
	{assign var=CLASS_VIEW_PAGING_INPUT_SUBMIT value='listViewPagingInputSubmit'}
	{assign var=CLASS_VIEW_BASIC_ACTION value='listViewBasicAction'}
{/if}
{if !isset($PAGE_NUMBER) || $PAGE_NUMBER eq ''}
	{assign var=PAGE_NUMBER value=1}
{/if}
<div class="{$CLASS_VIEW_ACTION} mk-so-pagination__btns" role="navigation" aria-label="{vtranslate('LBL_PAGE', $MODULE)}">
	<div class="btn-group dropup mk-so-pagejump-group mk-so-pager">
		<button type="button"
			id="PreviousPageButton"
			class="btn btn-default mk-so-page-btn mk-so-page-btn--nav mk-so-page-btn--prev"
			title="{vtranslate('LBL_PREVIOUS', $MODULE)}"
			aria-label="{vtranslate('LBL_PREVIOUS', $MODULE)}"
			{if !$PAGING_MODEL->isPrevPageExists()} disabled{/if}>
			<i class="fa fa-angle-left" aria-hidden="true"></i>
			<span class="mk-so-page-btn__label">{vtranslate('LBL_PREVIOUS', $MODULE)}</span>
		</button>
		{if $SHOWPAGEJUMP}
			<button type="button"
				id="PageJump"
				data-toggle="dropdown"
				class="btn btn-default mk-so-page-btn mk-so-page-btn--current"
				title="{vtranslate('LBL_LISTVIEW_PAGE_JUMP', $MODULE)}"
				aria-label="{vtranslate('LBL_LISTVIEW_PAGE_JUMP', $MODULE)}"
				aria-haspopup="true"
				aria-expanded="false">
				<span class="mk-so-page-current">
					<span class="mk-so-page-current__label">{vtranslate('LBL_PAGE', $MODULE)}</span>
					<strong class="mk-so-page-current__num">{$PAGE_NUMBER}</strong>
					<span class="mk-so-page-current__sep" aria-hidden="true">/</span>
					<span id="totalPageCount" class="mk-so-page-current__total"></span>
				</span>
				<i class="fa fa-angle-down mk-so-page-btn__caret" aria-hidden="true"></i>
			</button>
			<ul class="{$CLASS_VIEW_BASIC_ACTION} dropdown-menu mk-so-pagejump-menu" id="PageJumpDropDown" role="menu">
				<li>
					<div class="listview-pagenum">
						<span>{vtranslate('LBL_PAGE', $MODULE)}</span>&nbsp;
						<strong><span class="mk-so-pagejump-cur">{$PAGE_NUMBER}</span></strong>&nbsp;
						<span>{vtranslate('LBL_OF', $MODULE)}</span>&nbsp;
						<strong><span class="mk-so-pagejump-total"></span></strong>
					</div>
					<div class="listview-pagejump">
						<input type="text"
							id="pageToJump"
							placeholder="{vtranslate('LBL_LISTVIEW_JUMP_TO', $MODULE)}"
							class="{$CLASS_VIEW_PAGING_INPUT} text-center"
							inputmode="numeric"
							autocomplete="off"/>&nbsp;
						<button type="button"
							id="pageToJumpSubmit"
							class="btn btn-success {$CLASS_VIEW_PAGING_INPUT_SUBMIT} text-center">GO</button>
					</div>
				</li>
			</ul>
		{/if}
		<button type="button"
			id="NextPageButton"
			class="btn btn-default mk-so-page-btn mk-so-page-btn--nav mk-so-page-btn--next"
			title="{vtranslate('LBL_NEXT', $MODULE)}"
			aria-label="{vtranslate('LBL_NEXT', $MODULE)}"
			{if !$PAGING_MODEL->isNextPageExists()} disabled{/if}>
			<span class="mk-so-page-btn__label">{vtranslate('LBL_NEXT', $MODULE)}</span>
			<i class="fa fa-angle-right" aria-hidden="true"></i>
		</button>
	</div>
</div>
