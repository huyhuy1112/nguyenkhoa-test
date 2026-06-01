{* Placeholder icons for Settings shortcuts — replace with designer SVG when provided *}
{strip}
{assign var=_icon value=$ICON|default:'DEFAULT'}
{if $_icon eq 'Users' || $_icon eq 'LBL_USERS' || $_icon eq 'My Preferences'}
	<span class="fa fa-users mk-settings-shortcut-fa" aria-hidden="true"></span>
{elseif $_icon eq 'ModuleManager' || $_icon eq 'LBL_MODULES' || $_icon eq 'LBL_MODULE_MANAGER'}
	<span class="fa fa-th-large mk-settings-shortcut-fa" aria-hidden="true"></span>
{elseif $_icon eq 'Workflows' || $_icon eq 'LBL_WORKFLOWS'}
	<span class="fa fa-random mk-settings-shortcut-fa" aria-hidden="true"></span>
{elseif $_icon eq 'Picklist' || $_icon eq 'LBL_PICKLIST_FIELD_VALUES'}
	<span class="fa fa-list-ul mk-settings-shortcut-fa" aria-hidden="true"></span>
{elseif $_icon eq 'LBL_LEAD_MAPPING' || $_icon eq 'Leads'}
	<span class="fa fa-exchange mk-settings-shortcut-fa" aria-hidden="true"></span>
{elseif $_icon eq 'LBL_GOOGLE' || $_icon eq 'Google'}
	<span class="fa fa-google mk-settings-shortcut-fa" aria-hidden="true"></span>
{else}
	<span class="fa fa-cog mk-settings-shortcut-fa" aria-hidden="true"></span>
{/if}
{/strip}
