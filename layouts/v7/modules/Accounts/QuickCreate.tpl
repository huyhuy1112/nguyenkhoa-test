{* Accounts (Organization) Quick Create — luxury SALES UI *}
{strip}
	{foreach key=index item=jsModel from=$SCRIPTS}
		<script type="{$jsModel->getType()}" src="{$jsModel->getSrc()}"></script>
	{/foreach}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesQuickCreate.css')}&mk_v=20260605_mk_qc_accounts_v1" />
	<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesQuickCreate.js')}&mk_v=20260605_mk_qc_accounts_v1"></script>

	<div class="modal-dialog mk-qc-sales-modal mk-qc-sales-modal--accounts">
		<div class="modal-content">
			<form class="form-horizontal recordEditView" id="QuickCreate" name="QuickCreate" method="post" action="index.php">
				{assign var=HEADER_TITLE value={vtranslate('LBL_QUICK_CREATE', $MODULE)}|cat:" "|cat:{vtranslate($SINGLE_MODULE, $MODULE)}}
				{include file="partials/MkOrgQuickCreateModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}

				<div class="modal-body">
					{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
						<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
					{/if}
					<input type="hidden" name="module" value="{$MODULE}">
					<input type="hidden" name="action" value="SaveAjax">

					<div class="quickCreateContent">
						{include file="partials/MkSalesQuickCreateFields.tpl"|vtemplate_path:'Vtiger'}
					</div>
				</div>

				{include file="partials/MkSalesQuickCreateFooter.tpl"|vtemplate_path:'Vtiger'}
			</form>
		</div>
		{if $FIELDS_INFO neq null}
			<script type="text/javascript">
				var quickcreate_uimeta = (function() {
					var fieldInfo = {$FIELDS_INFO};
					return {
						field: {
							get: function(name, property) {
								if (name && property === undefined) { return fieldInfo[name]; }
								if (name && property) { return fieldInfo[name][property]; }
							},
							isMandatory: function(name) {
								if (fieldInfo[name]) { return fieldInfo[name].mandatory; }
								return false;
							},
							getType: function(name) {
								if (fieldInfo[name]) { return fieldInfo[name].type; }
								return false;
							}
						}
					};
				})();
			</script>
		{/if}
	</div>
{/strip}
