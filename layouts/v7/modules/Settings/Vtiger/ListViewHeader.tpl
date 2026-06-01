{* Settings list — inject actions into header slot after LISTVIEW_LINKS load *}
{strip}
	<div class="listViewPageDiv" id="listViewContent">
		<div id="mk-settings-list-actions-mount" class="hide">
			{include file="partials/SettingsSubpageActions.tpl"|@vtemplate_path:'Settings:Vtiger'}
		</div>
		<script type="text/javascript">
			jQuery(function($) {
				var $slot = $('#mk-settings-subpage-actions-slot');
				var $mount = $('#mk-settings-list-actions-mount');
				if ($slot.length && $mount.length && $mount.children().length) {
					$slot.html($mount.html());
					$mount.remove();
				}
			});
		</script>
{/strip}
