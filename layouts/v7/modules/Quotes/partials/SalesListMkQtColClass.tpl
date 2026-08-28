{strip}
{assign var=MK_QT_COL_CLASS value=''}
{if $FIELD_NAME eq 'subject'}
	{assign var=MK_QT_COL_CLASS value='mk-so-col-subject'}
{elseif $FIELD_NAME eq 'quote_no'}
	{assign var=MK_QT_COL_CLASS value='mk-so-col-order-no'}
{elseif $FIELD_NAME eq 'createdtime'}
	{assign var=MK_QT_COL_CLASS value='mk-so-col-time'}
{elseif $FIELD_NAME eq 'account_id' || $FIELD_NAME eq 'contact_id'}
	{assign var=MK_QT_COL_CLASS value='mk-so-col-customer'}
{elseif $FIELD_NAME eq 'potential_id'}
	{assign var=MK_QT_COL_CLASS value='mk-so-col-customer'}
{elseif $FIELD_NAME eq 'hdnGrandTotal' || $FIELD_NAME eq 'total'}
	{assign var=MK_QT_COL_CLASS value='mk-so-col-due'}
{elseif $FIELD_NAME eq 'assigned_user_id' || $FIELD_NAME eq 'created_user_id' || $FIELD_NAME eq 'smcreatorid'}
	{assign var=MK_QT_COL_CLASS value='mk-so-col-assigned'}
{elseif $FIELD_NAME eq 'quotestage'}
	{assign var=MK_QT_COL_CLASS value='mk-so-col-status'}
{/if}
{/strip}
