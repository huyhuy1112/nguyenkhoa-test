<?php
/* Smarty version 4.5.4, created on 2026-02-23 09:07:23
  from '/var/www/html/layouts/v7/modules/Vtiger/partials/Menubar.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.4',
  'unifunc' => 'content_699c18cb57c6d6_04850160',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '76be47b5598063303a6e281b21399c3d52d3efa0' => 
    array (
      0 => '/var/www/html/layouts/v7/modules/Vtiger/partials/Menubar.tpl',
      1 => 1769759073,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_699c18cb57c6d6_04850160 (Smarty_Internal_Template $_smarty_tpl) {
if ($_smarty_tpl->tpl_vars['MENU_STRUCTURE']->value) {
$_smarty_tpl->_assignInScope('topMenus', $_smarty_tpl->tpl_vars['MENU_STRUCTURE']->value->getTop());
$_smarty_tpl->_assignInScope('moreMenus', $_smarty_tpl->tpl_vars['MENU_STRUCTURE']->value->getMore());?>

<div id="modules-menu" class="modules-menu">
	<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['SELECTED_CATEGORY_MENU_LIST']->value, 'moduleModel', false, 'moduleName');
$_smarty_tpl->tpl_vars['moduleModel']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['moduleName']->value => $_smarty_tpl->tpl_vars['moduleModel']->value) {
$_smarty_tpl->tpl_vars['moduleModel']->do_else = false;
?>
		<?php $_smarty_tpl->_assignInScope('translatedModuleLabel', vtranslate($_smarty_tpl->tpl_vars['moduleModel']->value->get('label'),$_smarty_tpl->tpl_vars['moduleName']->value));?>
				<?php if ($_smarty_tpl->tpl_vars['moduleName']->value == 'Calendar' && $_smarty_tpl->tpl_vars['SELECTED_MENU_CATEGORY']->value == 'MANAGEMENT') {?>
			<?php $_smarty_tpl->_assignInScope('translatedModuleLabel', vtranslate('LBL_SCHEDULE','Calendar'));?>
		<?php } elseif ($_smarty_tpl->tpl_vars['moduleName']->value == 'Calendar' && $_smarty_tpl->tpl_vars['SELECTED_MENU_CATEGORY']->value == 'SUPPORT') {?>
			<?php $_smarty_tpl->_assignInScope('translatedModuleLabel', vtranslate('LBL_ACTIVITIES','Calendar'));?>
		<?php }?>
		<ul title="<?php echo $_smarty_tpl->tpl_vars['translatedModuleLabel']->value;?>
" class="module-qtip">
			<li <?php if ($_smarty_tpl->tpl_vars['MODULE']->value == $_smarty_tpl->tpl_vars['moduleName']->value) {?>class="active"<?php } else { ?>class=""<?php }?>>
				<a href="<?php echo $_smarty_tpl->tpl_vars['moduleModel']->value->getDefaultUrl();?>
&app=<?php echo $_smarty_tpl->tpl_vars['SELECTED_MENU_CATEGORY']->value;?>
">
					<?php echo $_smarty_tpl->tpl_vars['moduleModel']->value->getModuleIcon();?>

					<span><?php echo $_smarty_tpl->tpl_vars['translatedModuleLabel']->value;?>
</span>
				</a>
			</li>
		</ul>
	<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
</div>
<?php }
}
}
