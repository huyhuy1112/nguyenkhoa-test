{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{assign var="COMMENT_TEXTAREA_DEFAULT_ROWS" value="2"}
<div class = "summaryWidgetContainer">
    <div class="recentComments">
        <div class="commentsBody container-fluid" style = "height:100%">
            {if !empty($COMMENTS)}
                <div class="recentCommentsBody row">
                    <br>
                    {foreach key=index item=COMMENT from=$COMMENTS}
                        {assign var=CREATOR_NAME value={decode_html($COMMENT->getCommentedByName())}}
                        <div class="commentDetails">
                            <div class="singleComment">
                                {assign var=PARENT_COMMENT_MODEL value=$COMMENT->getParentCommentModel()}
                                {assign var=CHILD_COMMENTS_MODEL value=$COMMENT->getChildComments()}
                                <div class="container-fluid">
                                    <div class="row">
                                         <div class="col-lg-2 recordImage commentInfoHeader" data-commentid="{$COMMENT->getId()}" data-parentcommentid="{$COMMENT->get('parent_comments')}" data-relatedto = "{$COMMENT->get('related_to')}">
                                            {assign var=IMAGE_PATH value=$COMMENT->getImagePath()}
                                                {if !empty($IMAGE_PATH)}
                                                    <img src="{$IMAGE_PATH}" width="100%" height="100%" align="left">
                                                {else}
                                                    <div class="name"><span><strong> {$CREATOR_NAME|substr:0:2} </strong></span></div>
                                                {/if}
                                        </div>
                                        <div class="comment col-lg-10">
                                            <span class="creatorName">
                                                {$CREATOR_NAME}
                                            </span>&nbsp;&nbsp;
                                            <div class="">
                                                <span class="commentInfoContent">
                                                    {nl2br($COMMENT->get('commentcontent'))}
                                                </span>
                                            </div>
                                            <br>
                                            <div class="commentActionsContainer">      
                                                <span class="commentTime pull-right">
                                                    <p class="muted"><small title="{Vtiger_Util_Helper::formatDateTimeIntoDayString($COMMENT->getCommentedTime())}">{Vtiger_Util_Helper::formatCommentDateTime($COMMENT->getCommentedTime())}</small></p>
                                                </span>
                                            </div>
                                            <div style="margin-top:5px;">
												{include file="partials/CommentAttachments.tpl"|vtemplate_path:'Vtiger' COMMENT=$COMMENT MODULE_NAME=$MODULE_NAME}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                    {/foreach}
                </div>
            {else}
                {include file="NoComments.tpl"|@vtemplate_path}
            {/if}
        </div>
    </div>
</div>
