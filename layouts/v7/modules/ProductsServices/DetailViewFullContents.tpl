{* ProductsServices Detail: use standard blocks + image gallery for used_projects *}
{strip}
<form id="detailView" method="POST">
    {include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}

    {assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
    {if $IMAGE_DETAILS|@count gt 0}
        <div class="block">
            <h4>Project Images</h4>
            {foreach from=$IMAGE_DETAILS item=IMAGE_INFO}
                {if !empty($IMAGE_INFO.url)}
                    <img src="{$IMAGE_INFO.url}" style="max-width:400px;border-radius:8px;margin:10px 10px 0 0;">
                {/if}
            {/foreach}
        </div>
    {/if}
</form>
{/strip}

