{strip}
{assign var=MK_SO_COL_CLASS value=''}
{if $FIELD_NAME eq 'subject' || $FIELD_NAME eq 'invoice_no'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-order-no'}
{elseif $FIELD_NAME eq 'salesorder_id' || $FIELD_NAME eq 'account_id' || $FIELD_NAME eq 'contact_id'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-customer'}
{elseif $FIELD_NAME eq 'invoicestatus' || $FIELD_NAME eq 'status' || $FIELD_NAME eq 'sostatus'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-status'}
{elseif $FIELD_NAME eq 'hdnGrandTotal' || $FIELD_NAME eq 'total'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-due'}
{elseif $FIELD_NAME eq 'assigned_user_id' || $FIELD_NAME eq 'created_user_id' || $FIELD_NAME eq 'smcreatorid'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-assigned'}
{elseif $FIELD_NAME eq 'createdtime'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-time'}
{/if}
{/strip}
