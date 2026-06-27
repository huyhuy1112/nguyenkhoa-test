{*+**********************************************************************************
 * Convert Opportunity → Project (SALES modern modal)
 ************************************************************************************}
{strip}
   <div class="modal-dialog modal-md mk-convert-potential-dialog">
      <div id="convertPotentialContainer" class="modelContainer modal-content mk-convert-potential">
         {assign var=PROJECT_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Project')}
         {if !$CONVERT_POTENTIAL_FIELDS['Project']}
            <input type="hidden" id="convertPotentialErrorTitle" value="{vtranslate('LBL_CONVERT_ERROR_TITLE',$MODULE)}"/>
            <input id="converPotentialtError" class="convertPotentialError" type="hidden" value="{vtranslate('LBL_CONVERT_POTENTIALS_ERROR',$MODULE)}"/>
         {else}
            {assign var=HEADER_TITLE value={vtranslate('LBL_CONVERT_POTENTIAL', $MODULE)}|cat:" "|cat:{$RECORD->getName()}}
            <div class="modal-header mk-convert-potential__header">
               <div class="mk-convert-potential__header-inner">
                  <div class="mk-convert-potential__header-text">
                     <span class="mk-convert-potential__eyebrow">{vtranslate('LBL_CONVERT_POTENTIAL', $MODULE)}</span>
                     <h4 class="mk-convert-potential__title">{$RECORD->getName()}</h4>
                  </div>
                  <button type="button" class="close mk-convert-potential__close" aria-label="Close" data-dismiss="modal">
                     <span aria-hidden="true" class="fa fa-close"></span>
                  </button>
               </div>
            </div>
            <form class="form-horizontal mk-convert-potential__form" id="convertPotentialForm" method="post" action="index.php">
               <input type="hidden" name="module" value="{$MODULE}"/>
               <input type="hidden" name="view" value="SaveConvertPotential"/>
               <input type="hidden" name="record" value="{$RECORD->getId()}"/>
               <input type="hidden" name="modules" value=''/>
               <div class="modal-body mk-convert-potential__body accordion" id="potentialAccordion">
                  {foreach item=MODULE_FIELD_MODEL key=MODULE_NAME from=$CONVERT_POTENTIAL_FIELDS}
                     <section class="mk-convert-potential__card moduleContent">
                        <div class="accordion-group convertPotentialModules">
                           <div class="header accordion-heading mk-convert-potential__card-head">
                              <label class="mk-convert-potential__module-toggle moduleSelection" data-parent="#potentialAccordion" data-toggle="collapse" href="#{$MODULE_NAME}_FieldInfo">
                                 <input id="{$MODULE_NAME}Module" class="convertPotentialModuleSelection" data-module="{vtranslate($MODULE_NAME,$MODULE_NAME)}" value="{$MODULE_NAME}" type="checkbox" {if $MODULE_NAME eq 'Project'} checked="" {/if}/>
                                 <span class="mk-convert-potential__check" aria-hidden="true"></span>
                                 {assign var=SINGLE_MODULE_NAME value="SINGLE_$MODULE_NAME"}
                                 <span class="mk-convert-potential__module-label">{vtranslate('LBL_CREATE', $MODULE)} {vtranslate($SINGLE_MODULE_NAME, $MODULE_NAME)}</span>
                              </label>
                           </div>
                           <div id="{$MODULE_NAME}_FieldInfo" class="{$MODULE_NAME}_FieldInfo accordion-body collapse fieldInfo mk-convert-potential__fields {if $CONVERT_POTENTIAL_FIELDS['Project'] && $MODULE_NAME == "Project"} in {/if}">
                              {foreach item=FIELD_MODEL from=$MODULE_FIELD_MODEL}
                                 <div class="mk-convert-potential__field-row row">
                                    <div class="fieldLabel col-lg-4 col-md-4 col-sm-4">
                                       <label class="mk-convert-potential__label">
                                          {vtranslate($FIELD_MODEL->get('label'), $MODULE_NAME)}
                                          {if $FIELD_MODEL->isMandatory() eq true}<span class="mk-convert-potential__req">*</span>{/if}
                                       </label>
                                    </div>
                                    <div class="fieldValue col-lg-8 col-md-8 col-sm-8">
                                       {include file=$FIELD_MODEL->getUITypeModel()->getTemplateName()|@vtemplate_path}
                                    </div>
                                 </div>
                              {/foreach}
                           </div>
                        </div>
                     </section>
                  {/foreach}
                  <section class="mk-convert-potential__card mk-convert-potential__card--assign defaultFields">
                     <div class="mk-convert-potential__assign-head">{vtranslate('Assigned To', $MODULE_NAME)}</div>
                     {assign var=FIELD_MODEL value=$ASSIGN_TO}
                     <div class="mk-convert-potential__field-row row">
                        <div class="fieldLabel col-lg-4 col-md-4 col-sm-4">
                           <label class="mk-convert-potential__label">
                              {vtranslate($FIELD_MODEL->get('label'), $MODULE_NAME)}
                              <span class="mk-convert-potential__req">*</span>
                           </label>
                        </div>
                        <div class="fieldValue col-lg-8 col-md-8 col-sm-8">
                           {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
                        </div>
                     </div>
                  </section>
               </div>
               <div class="modal-footer mk-convert-potential__footer">
                  <button class="btn mk-convert-potential__btn-save" type="submit" name="saveButton">
                     <strong>{vtranslate('LBL_SAVE', $MODULE)}</strong>
                  </button>
                  <button type="button" class="btn mk-convert-potential__btn-cancel cancelLink" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</button>
               </div>
            </form>
         {/if}
      </div>
   </div>
{/strip}
