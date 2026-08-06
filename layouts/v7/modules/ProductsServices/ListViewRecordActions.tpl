{*+**********************************************************************************
 * ProductsServices list row actions — circular select checkbox only (hình 2).
 * Cần QC stays in form / inline panel — not next to the row select.
 ************************************************************************************}
{strip}
<!--LIST VIEW RECORD ACTIONS-->
{assign var=RECORD_ID value=$LISTVIEW_ENTRY->getId()}

<div class="table-actions mk-list-row-actions mk-ps-row-actions">
	{if !$SEARCH_MODE_RESULTS}
		<span class="input mk-ps-row-check-wrap">
			<input type="checkbox" value="{$RECORD_ID}" class="listViewEntriesCheckBox" />
		</span>
	{/if}

	<div class="btn-group inline-save hide">
		<button class="button btn-success btn-small save" type="button" name="save"><i class="fa fa-check"></i></button>
		<button class="button btn-danger btn-small cancel" type="button" name="Cancel"><i class="fa fa-close"></i></button>
	</div>
</div>
{/strip}
