{* Leads: expose stable data-tag for Create/Edit palette (LeadsMkEdit.css). *}
<span class="tag {if $ACTIVE eq true} active {/if}" title="{$TAG_MODEL->getName()}" data-type="{$TAG_MODEL->getType()}" data-id="{$TAG_MODEL->getId()}" data-tag="{$TAG_MODEL->getName()|escape:'html'}">
   <i class="activeToggleIcon fa {if $ACTIVE eq true} fa-circle-o {else} fa-circle {/if}"></i>
   <span class="tagLabel display-inline-block textOverflowEllipsis" title="{$TAG_MODEL->getName()}">{$TAG_MODEL->getName()}</span>
   {if !$NO_EDIT}
       <i class="editTag fa fa-pencil"></i>
   {/if}
   {if !$NO_DELETE}
       <i class="deleteTag fa fa-times"></i>
   {/if}
</span>
