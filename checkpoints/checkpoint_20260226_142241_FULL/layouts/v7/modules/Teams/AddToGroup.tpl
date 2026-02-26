{strip}
<div class="col-sm-12 col-xs-12">
	<form class="form-horizontal" method="post" action="index.php">
		<input type="hidden" name="module" value="Teams" />
		<input type="hidden" name="action" value="AddToGroup" />
		<input type="hidden" name="app" value="Management" />
		<input type="hidden" name="userid" value="{$USERID|escape}" />

		<div class="form-group">
			<label class="control-label col-sm-3">Group *</label>
			<div class="col-sm-6">
				<select name="groupid" class="form-control" required>
					<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
					{foreach item=G from=$GROUPS}
						<option value="{$G.groupid|escape}" {if $SELECTED_GROUPID eq $G.groupid}selected{/if}>{$G.group_name|escape}</option>
					{/foreach}
				</select>
			</div>
		</div>

		<div class="form-group">
			<div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">{vtranslate('LBL_SAVE','Vtiger')}</button>
				<a class="btn btn-default" href="index.php?module=Teams&view=List&app=Management">{vtranslate('LBL_CANCEL','Vtiger')}</a>
			</div>
		</div>
	</form>
</div>
{/strip}
