{* Inventory detail cross-nav: deep-link to related detail when linked ids exist *}
{strip}
<nav class="mk-gi-topnav {$MK_INV_NAV_CLASS|default:''}" aria-label="Inventory modules">
	{if $MK_INV_NAV_ACTIVE eq 'GoodsReceipt'}
		<a class="is-active" aria-current="page" href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY">Inbound</a>
	{elseif !empty($LINKED_INBOUND_RECEIPT_ID) && $LINKED_INBOUND_RECEIPT_ID > 0}
		<a href="index.php?module=GoodsReceipt&amp;view=Detail&amp;record={$LINKED_INBOUND_RECEIPT_ID}&amp;app=INVENTORY">Inbound</a>
	{else}
		<a href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY">Inbound</a>
	{/if}

	{if $MK_INV_NAV_ACTIVE eq 'Warehouse'}
		<a class="is-active" aria-current="page" href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">Storage</a>
	{elseif !empty($LINKED_STORAGE_STOCK_ID) && $LINKED_STORAGE_STOCK_ID > 0}
		<a href="index.php?module=Warehouse&amp;view=Detail&amp;record={$LINKED_STORAGE_STOCK_ID}&amp;app=INVENTORY">Storage</a>
	{else}
		<a href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">Storage</a>
	{/if}

	{if $MK_INV_NAV_ACTIVE eq 'GoodsIssue'}
		<a class="is-active" aria-current="page" href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">Outbound</a>
	{elseif !empty($LINKED_OUTBOUND_ISSUE_ID) && $LINKED_OUTBOUND_ISSUE_ID > 0}
		<a href="index.php?module=GoodsIssue&amp;view=Detail&amp;record={$LINKED_OUTBOUND_ISSUE_ID}&amp;app=INVENTORY">Outbound</a>
	{else}
		<a href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">Outbound</a>
	{/if}
</nav>
{/strip}
