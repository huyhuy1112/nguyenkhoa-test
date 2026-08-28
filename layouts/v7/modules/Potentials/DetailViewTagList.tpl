{* Potentials SALES — tag strip + modals (Lovable chips) *}
<div class="tagContainer mk-opp-tag-container">
    <div class="tag-contents {if empty($TAGS_LIST)} hide{/if}">
        <div class="detailTagList" data-num-of-tags-to-show="999">
            {foreach from=$TAGS_LIST item=TAG_MODEL}
                {assign var=TAG_LABEL value=$TAG_MODEL->getName()}
                {include file="Tag.tpl"|vtemplate_path:$MODULE}
            {/foreach}

            <a href="javascript:void(0);" class="moreTags mk-opp-tags-more hide" aria-hidden="true">
                <span class="tagMoreCount">0</span>
                &nbsp;{vtranslate('LBL_MORE',$MODULE)|strtolower}
            </a>
        </div>
    </div>
    <div id="addTagContainer">
        <a id="addTagTriggerer" class="badge mk-opp-add-tag-btn">
            <i class="fa fa-plus"></i>
            {vtranslate('LBL_ADD_NEW_TAG',$MODULE)}
        </a>
    </div>
    <div class="viewAllTagsContainer hide">
        <div class="modal-dialog mk-opp-tags-modal-dialog mk-opp-tags-modal-dialog--view">
            <div class="modal-content mk-opp-tags-modal">
                {assign var="TITLE" value="{vtranslate('LBL_TAG_FOR',$MODULE,$RECORD->getName())}"}
                {include file="ModalHeader.tpl"|vtemplate_path:$MODULE}
                <div class="modal-body mk-opp-tags-modal__body detailShowAllModal">
                    <div class="mk-opp-tags-modal__grid mk-opp-tags-modal__grid--view currentTag multiLevelTagList">
                        {foreach item=TAG_MODEL from=$TAGS_LIST}
                            {include file="Tag.tpl"|vtemplate_path:$MODULE }
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    </div>
   {include file="AddTagUI.tpl"|vtemplate_path:$MODULE RECORD_NAME=$RECORD->getName()}
</div>
<div id="dummyTagElement" class="hide">
{assign var=TAG_MODEL value=Vtiger_Tag_Model::getCleanInstance()}
{include file="Tag.tpl"|vtemplate_path:$MODULE}
</div>
<div>
    <div class="editTagContainer hide">
        <input type="hidden" name="id" value="" />
        <div class="editTagContents">
            <div>
                <input type="text" name="tagName" value="" style="width:100%" />
            </div>
            <div>
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="visibility" value="{Vtiger_Tag_Model::PRIVATE_TYPE}"/>
                        <input type="checkbox" name="visibility" value="{Vtiger_Tag_Model::PUBLIC_TYPE}" />
                        &nbsp; {vtranslate('LBL_SHARE_TAG',$MODULE)}
                    </label>
                </div>
            </div>
        </div>
        <div>
            <button class="btn btn-mini btn-success saveTag" type="button" style="width:50%;float:left">
                <center> <i class="fa fa-check"></i> </center>
            </button>
            <button class="btn btn-mini btn-danger cancelSaveTag" type="button" style="width:50%">
                <center> <i class="fa fa-close"></i> </center>
            </button>
        </div>
    </div>
</div>
