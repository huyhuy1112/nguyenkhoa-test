{strip}
{assign var=MK_SO_COL_CLASS value=''}
{if $FIELD_NAME eq 'subject'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-subject'}
{elseif $FIELD_NAME eq 'salesorder_no'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-order-no'}
{elseif $FIELD_NAME eq 'createdtime'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-time'}
{elseif $FIELD_NAME eq 'customerno'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-customer-code'}
{elseif $FIELD_NAME eq 'account_id' || $FIELD_NAME eq 'contact_id' || $FIELD_NAME eq 'related_to' || $FIELD_NAME eq 'sc_related_to'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-customer'}
{elseif $FIELD_NAME eq 'mk_warehouse_name'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-warehouse'}
{elseif isset($MK_SO_STATUS_FIELD) && $MK_SO_STATUS_FIELD neq '' && $FIELD_NAME eq $MK_SO_STATUS_FIELD}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-status'}
{elseif $FIELD_NAME eq 'hdnGrandTotal' || $FIELD_NAME eq 'total'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-due'}
{elseif $FIELD_NAME eq 'received' || $FIELD_NAME eq 'paid'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-paid'}
{elseif $FIELD_NAME eq 'assigned_user_id' || $FIELD_NAME eq 'created_user_id' || $FIELD_NAME eq 'smcreatorid'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-assigned'}
{/if}
{/strip}
