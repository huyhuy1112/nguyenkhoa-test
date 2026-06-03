{* Inventory (app KHO) cross-nav: Nhập kho / Tồn kho / Xuất kho *}
{strip}
<nav class="mk-gi-topnav {$MK_INV_NAV_CLASS|default:''}" aria-label="Các module kho">
	{if $MK_INV_NAV_ACTIVE eq 'GoodsReceipt'}
		{if !empty($LINKED_INBOUND_RECEIPT_ID) && $LINKED_INBOUND_RECEIPT_ID > 0}
			<a class="is-active" aria-current="page" href="index.php?module=GoodsReceipt&amp;view=Detail&amp;record={$LINKED_INBOUND_RECEIPT_ID}&amp;app=INVENTORY">{vtranslate('GoodsReceipt','GoodsReceipt')}</a>
		{else}
			<a class="is-active" aria-current="page" href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY">{vtranslate('GoodsReceipt','GoodsReceipt')}</a>
		{/if}
	{elseif !empty($LINKED_INBOUND_RECEIPT_ID) && $LINKED_INBOUND_RECEIPT_ID > 0}
		<a href="index.php?module=GoodsReceipt&amp;view=Detail&amp;record={$LINKED_INBOUND_RECEIPT_ID}&amp;app=INVENTORY">{vtranslate('GoodsReceipt','GoodsReceipt')}</a>
	{else}
		<a href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY">{vtranslate('GoodsReceipt','GoodsReceipt')}</a>
	{/if}

	{if $MK_INV_NAV_ACTIVE eq 'Warehouse'}
		{if !empty($LINKED_STORAGE_STOCK_ID) && $LINKED_STORAGE_STOCK_ID > 0}
			<a class="is-active" aria-current="page" href="index.php?module=Warehouse&amp;view=Detail&amp;record={$LINKED_STORAGE_STOCK_ID}&amp;app=INVENTORY">{vtranslate('Warehouse','Warehouse')}</a>
		{else}
			<a class="is-active" aria-current="page" href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">{vtranslate('Warehouse','Warehouse')}</a>
		{/if}
	{elseif !empty($LINKED_STORAGE_STOCK_ID) && $LINKED_STORAGE_STOCK_ID > 0}
		<a href="index.php?module=Warehouse&amp;view=Detail&amp;record={$LINKED_STORAGE_STOCK_ID}&amp;app=INVENTORY">{vtranslate('Warehouse','Warehouse')}</a>
	{else}
		<a href="index.php?module=Warehouse&amp;view=List&amp;app=INVENTORY">{vtranslate('Warehouse','Warehouse')}</a>
	{/if}

	{if $MK_INV_NAV_ACTIVE eq 'GoodsIssue'}
		{if !empty($LINKED_OUTBOUND_ISSUE_ID) && $LINKED_OUTBOUND_ISSUE_ID > 0}
			<a class="is-active" aria-current="page" href="index.php?module=GoodsIssue&amp;view=Detail&amp;record={$LINKED_OUTBOUND_ISSUE_ID}&amp;app=INVENTORY">{vtranslate('GoodsIssue','GoodsIssue')}</a>
		{else}
			<a class="is-active" aria-current="page" href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">{vtranslate('GoodsIssue','GoodsIssue')}</a>
		{/if}
	{elseif !empty($LINKED_OUTBOUND_ISSUE_ID) && $LINKED_OUTBOUND_ISSUE_ID > 0}
		<a href="index.php?module=GoodsIssue&amp;view=Detail&amp;record={$LINKED_OUTBOUND_ISSUE_ID}&amp;app=INVENTORY">{vtranslate('GoodsIssue','GoodsIssue')}</a>
	{else}
		<a href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">{vtranslate('GoodsIssue','GoodsIssue')}</a>
	{/if}
</nav>
{/strip}
