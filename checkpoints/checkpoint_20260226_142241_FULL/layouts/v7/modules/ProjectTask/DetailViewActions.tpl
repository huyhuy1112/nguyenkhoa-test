{* ProjectTask: subtask uses compact ellipsis dropdown *}
{strip}
{if $RECORD->get('parent_projecttaskid')}
<div class="dropdown" style="display:inline-block;">
    <a class="btn btn-default btn-sm" data-toggle="dropdown" aria-haspopup="true"><i class="far fa-ellipsis-v"></i></a>
    <ul class="dropdown-menu dropdown-menu-right">
        {if !empty($DETAILVIEW_LINKS['DETAILVIEWBASIC'])}
        {foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
        <li>
            <a {if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}href="{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}"{else}href="javascript:void(0);" onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"{/if}>{vtranslate($DETAIL_VIEW_BASIC_LINK->getLabel(), $MODULE_NAME)}</a>
        </li>
        {/foreach}
        {/if}
        {if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0)}
        {foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
            {if $DETAIL_VIEW_LINK->getLabel() eq ""}
            <li class="divider"></li>
            {else}
            <li>
                {if $DETAIL_VIEW_LINK->getUrl()|strstr:"javascript"}
                <a href="javascript:void(0);" onclick="{$DETAIL_VIEW_LINK->getUrl()}">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
                {else}
                <a href="{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
                {/if}
            </li>
            {/if}
        {/foreach}
        {/if}
    </ul>
</div>
{else}
{include file="DetailViewActions.tpl"|vtemplate_path:'Vtiger'}
{/if}
{/strip}
