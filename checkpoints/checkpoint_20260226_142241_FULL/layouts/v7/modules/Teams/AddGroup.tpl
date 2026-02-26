{strip}
<div class="col-sm-12 col-xs-12">
	<form class="form-horizontal" method="post" action="index.php">
		<input type="hidden" name="module" value="Teams" />
		<input type="hidden" name="action" value="SaveGroup" />
		<input type="hidden" name="app" value="Management" />

		<div class="form-group">
			<label class="control-label col-sm-3">Group Name *</label>
			<div class="col-sm-6">
				<input type="text" name="group_name" class="form-control" required />
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-3">Assign Type *</label>
			<div class="col-sm-6">
				<select name="assign_type" class="form-control teams-assign-type">
					<option value="ALL">ALL</option>
					<option value="USERS">USERS</option>
					<option value="GROUPS">GROUPS</option>
				</select>
			</div>
		</div>

		<div class="form-group teams-users-field">
			<label class="control-label col-sm-3">Users</label>
			<div class="col-sm-6">
				<select name="userids[]" multiple class="select2 inputElement form-control teams-user-selector" data-placeholder="{vtranslate('LBL_SELECT_USERS','Vtiger')}">
					{foreach item=U from=$TEAM_MEMBERS}
						<option value="{$U.id|escape}">{$U.first_name|escape} {$U.last_name|escape} ({$U.user_name|escape})</option>
					{/foreach}
				</select>
			</div>
		</div>

		<div class="form-group teams-groups-field" style="display: none;">
			<label class="control-label col-sm-3">Groups</label>
			<div class="col-sm-6">
				<select name="groupids[]" multiple class="select2 inputElement form-control teams-group-selector" data-placeholder="{vtranslate('LBL_SELECT_GROUPS','Vtiger')}">
					{foreach item=G from=$EXISTING_GROUPS}
						<option value="{$G.groupid|escape}">{$G.group_name|escape}</option>
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
<script type="text/javascript">
(function($){
	$(document).ready(function(){
		function initSelect2($selector) {
			if ($selector.length > 0) {
				if (typeof vtUtils !== 'undefined' && typeof vtUtils.showSelect2ElementView === 'function') {
					vtUtils.showSelect2ElementView($selector, {
						placeholder: $selector.data('placeholder') || 'Select...',
						allowClear: false,
						closeOnSelect: false
					});
				} else if (typeof app !== 'undefined' && typeof app.showSelect2ElementView === 'function') {
					app.showSelect2ElementView($selector, {
						placeholder: $selector.data('placeholder') || 'Select...',
						allowClear: false,
						closeOnSelect: false
					});
				} else if (typeof $.fn.select2 !== 'undefined') {
					$selector.select2({
						placeholder: $selector.data('placeholder') || 'Select...',
						allowClear: false,
						closeOnSelect: false,
						width: '100%'
					});
				}
			}
		}

		// Initialize Select2 for user and group selectors
		initSelect2($('.teams-user-selector'));
		initSelect2($('.teams-group-selector'));

		// Handle Assign Type change - show/hide Users or Groups field
		$('.teams-assign-type').on('change', function(){
			var assignType = $(this).val();
			
			if (assignType === 'GROUPS') {
				$('.teams-users-field').hide();
				$('.teams-groups-field').show();
			} else if (assignType === 'USERS') {
				$('.teams-users-field').show();
				$('.teams-groups-field').hide();
			} else { // ALL
				$('.teams-users-field').hide();
				$('.teams-groups-field').hide();
			}
		});

		// Trigger change on page load to set initial state
		$('.teams-assign-type').trigger('change');
	});
})(jQuery);
</script>
