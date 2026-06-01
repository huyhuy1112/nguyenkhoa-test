{* modules/Settings/Roles/views/Index.php — modern hierarchy card *}
{strip}
<div class="listViewPageDiv mk-settings-roles-page" id="listViewContent">
	<div class="mk-settings-roles-panel">
		<div class="mk-settings-roles-panel__top">
			<div class="mk-settings-roles-panel__intro">
				<p class="mk-settings-roles-panel__desc">{vtranslate('LBL_CLICK_TO_EDIT_OR_DRAG_TO_MOVE', $QUALIFIED_MODULE)}</p>
			</div>
			<a href="{$ROOT_ROLE->getCreateChildUrl()}" data-url="{$ROOT_ROLE->getCreateChildUrl()}" data-action="modal" class="mk-settings-btn mk-settings-btn--primary">
				<span class="fa fa-plus" aria-hidden="true"></span>
				{vtranslate('LBL_ADD_RECORD', $QUALIFIED_MODULE)}
			</a>
		</div>
		<div class="mk-settings-roles-tree treeView" role="tree" aria-label="{vtranslate('Roles', $QUALIFIED_MODULE)}">
			<ul class="mk-settings-roles-tree__root">
				<li data-role="{$ROOT_ROLE->getParentRoleString()}" data-roleid="{$ROOT_ROLE->getId()}">
					<div class="toolbar-handle">
						<a href="javascript:;" class="mk-settings-role-node mk-settings-role-node--root draggable droppable">{$ROOT_ROLE->getName()}</a>
						<div class="toolbar mk-settings-role-toolbar" title="{vtranslate('LBL_ADD_RECORD', $QUALIFIED_MODULE)}">
							<a href="{$ROOT_ROLE->getCreateChildUrl()}" data-url="{$ROOT_ROLE->getCreateChildUrl()}" data-action="modal" class="mk-settings-role-toolbar__btn" aria-label="{vtranslate('LBL_ADD_RECORD', $QUALIFIED_MODULE)}">
								<span class="fa fa-plus" aria-hidden="true"></span>
							</a>
						</div>
					</div>
					{assign var=ROLE value=$ROOT_ROLE}
					{include file=vtemplate_path("RoleTree.tpl", "Settings:Roles")}
				</li>
			</ul>
		</div>
	</div>
</div>
{/strip}
