<?php
/* Smarty version 4.5.4, created on 2026-02-23 09:08:42
  from '/var/www/html/layouts/v7/modules/Calendar/partials/SidebarEssentials.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.4',
  'unifunc' => 'content_699c191ab989c8_34400510',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a7aa44e4eadd6b4be42676c58b0814b9860e80ec' => 
    array (
      0 => '/var/www/html/layouts/v7/modules/Calendar/partials/SidebarEssentials.tpl',
      1 => 1770003503,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_699c191ab989c8_34400510 (Smarty_Internal_Template $_smarty_tpl) {
if ($_GET['view'] == 'Calendar' || $_GET['view'] == 'SharedCalendar') {?>
<div class="calendar-sidebar-google">
	<?php if ($_smarty_tpl->tpl_vars['IS_CREATE_PERMITTED']->value) {?>
	<div class="calendar-google-create">
		<div class="dropdown">
			<button type="button" class="btn calendar-create-btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				<span class="fa fa-plus"></span> <?php echo vtranslate('LBL_CREATE','Calendar');?>
 <span class="fa fa-chevron-down"></span>
			</button>
			<ul class="dropdown-menu dropdown-menu-right">
				<li><a href="javascript:void(0);" onclick="Calendar_Calendar_Js.showCreateEventModal();"><span class="fa fa-calendar-plus-o"></span> <?php echo vtranslate('LBL_ADD_EVENT',$_smarty_tpl->tpl_vars['MODULE']->value);?>
</a></li>
				<li><a href="javascript:void(0);" onclick="Calendar_Calendar_Js.showCreateTaskModal();"><span class="fa fa-tasks"></span> <?php echo vtranslate('LBL_ADD_TASK',$_smarty_tpl->tpl_vars['MODULE']->value);?>
</a></li>
				<?php if ($_smarty_tpl->tpl_vars['SHOW_LEAVE_REQUEST']->value) {?>
				<li><a href="javascript:void(0);" onclick="Calendar_Calendar_Js.showLeaveRequestCreateModal();"><span class="fa fa-calendar-minus-o"></span> <?php echo vtranslate('LBL_LEAVE_REQUEST',$_smarty_tpl->tpl_vars['MODULE']->value);?>
</a></li>
				<?php }?>
			</ul>
		</div>
	</div>
	<?php }?>
	<?php if ($_smarty_tpl->tpl_vars['SHOW_MINI_CALENDAR_LEAVE']->value) {?>
	<div class="calendar-mini-wrap" id="calendar-mini-wrap" title="<?php echo vtranslate('LBL_MINI_CALENDAR','Calendar');?>
">
		<div class="calendar-mini-label"><?php echo vtranslate('LBL_MINI_CALENDAR_LEAVE','Calendar');?>
</div>
		<div id="calendar-mini"></div>
	</div>
	<?php }?>
</div>
<div class="sidebar-menu calendar-sidebar-mycalendars">
	<div class="module-filters" id="module-filters">
		<div class="sidebar-container lists-menu-container">
			<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['QUICK_LINKS']->value['SIDEBARWIDGET'], 'SIDEBARWIDGET');
$_smarty_tpl->tpl_vars['SIDEBARWIDGET']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['SIDEBARWIDGET']->value) {
$_smarty_tpl->tpl_vars['SIDEBARWIDGET']->do_else = false;
?>
			<?php if ($_smarty_tpl->tpl_vars['SIDEBARWIDGET']->value->get('linklabel') == 'LBL_ACTIVITY_TYPES' || $_smarty_tpl->tpl_vars['SIDEBARWIDGET']->value->get('linklabel') == 'LBL_ADDED_CALENDARS') {?>
			<div class="calendar-sidebar-tabs sidebar-widget" id="<?php echo $_smarty_tpl->tpl_vars['SIDEBARWIDGET']->value->get('linklabel');?>
-accordion" role="tablist" data-widget-name="<?php echo $_smarty_tpl->tpl_vars['SIDEBARWIDGET']->value->get('linklabel');?>
">
				<div class="calendar-sidebar-tab">
					<div class="sidebar-widget-header" role="tab" data-url="<?php echo $_smarty_tpl->tpl_vars['SIDEBARWIDGET']->value->getUrl();?>
">
						<div class="sidebar-header clearfix">
							<h5 class="pull-left"><?php echo vtranslate($_smarty_tpl->tpl_vars['SIDEBARWIDGET']->value->get('linklabel'),$_smarty_tpl->tpl_vars['MODULE']->value);?>
</h5>
							<button class="btn btn-default pull-right sidebar-btn add-calendar-feed">
								<div class="fa fa-plus" aria-hidden="true"></div>
							</button>
						</div>
					</div>
					<div class="list-menu-content">
						<div id="<?php echo $_smarty_tpl->tpl_vars['SIDEBARWIDGET']->value->get('linklabel');?>
" class="sidebar-widget-body activitytypes">
							<div style="text-align:center;"><img src="layouts/v7/skins/images/loading.gif"></div>
						</div>
					</div>
				</div>
			</div>
			<?php }?>
			<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
		</div>
	</div>
</div>
<?php } else { ?>
	<?php $_smarty_tpl->_subTemplateRender(call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'vtemplate_path' ][ 0 ], array( "partials/SidebarEssentials.tpl",'Vtiger' )), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
}
}
}
