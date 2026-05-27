{strip}
{assign var=MK_SO_COL_CLASS value=''}
{if $FIELD_NAME eq 'subject'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-subject'}
{elseif $FIELD_NAME eq 'account_id' || $FIELD_NAME eq 'related_to' || $FIELD_NAME eq 'sc_related_to'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-organization'}
{elseif isset($MK_SO_STATUS_FIELD) && $MK_SO_STATUS_FIELD neq '' && $FIELD_NAME eq $MK_SO_STATUS_FIELD}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-status'}
{elseif $FIELD_NAME eq 'hdnGrandTotal' || $FIELD_NAME eq 'total'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-total'}
{elseif $FIELD_NAME eq 'assigned_user_id' || $FIELD_NAME eq 'created_user_id' || $FIELD_NAME eq 'smcreatorid'}
	{assign var=MK_SO_COL_CLASS value='mk-so-col-assigned'}
{/if}
{/strip}
