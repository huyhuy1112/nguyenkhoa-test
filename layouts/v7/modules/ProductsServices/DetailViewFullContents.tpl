{* ProductsServices Detail: use standard blocks + image gallery for used_projects *}
{strip}
<form id="detailView" method="POST" class="mk-ps-v2-detail-form">
    {include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}

    {assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
    {if $IMAGE_DETAILS|@count gt 0}
        <div class="block mk-ps-v2-detail-images" data-block="images">
            <div class="mk-ps-v2-df-head">
                <span class="mk-ps-v2-df-head__accent" aria-hidden="true"></span>
                <div class="mk-ps-v2-df-head__title">Hình ảnh</div>
            </div>
            <div class="blockData mk-ps-v2-detail-images__body">
            {foreach from=$IMAGE_DETAILS item=IMAGE_INFO}
                {if !empty($IMAGE_INFO.url)}
                    <img src="{$IMAGE_INFO.url}" alt="" class="mk-ps-v2-detail-images__img">
                {/if}
            {/foreach}
            </div>
        </div>
    {/if}
</form>
{/strip}

