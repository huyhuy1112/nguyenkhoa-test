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
{strip}
    {if php7_count($DATA) gt 0 }
        <script type="application/json" class="widgetData">{ZEND_JSON::encode($DATA)}</script>
        <input class="yAxisFieldType" type="hidden" value="{if isset($YAXIS_FIELD_TYPE)}$YAXIS_FIELD_TYPE{/if}" />
        <div class="row mk-dashboard-chart-row" style="margin:0;">
            <div class="col-xs-12 mk-chart-col">
                <div class="widgetChartContainer mk-chart-stage" name="chartcontent"></div>
                <div class="mk-dash-chart-xlabels" aria-label="Salesperson">
                {if isset($CHART_USER_LABELS) && $CHART_USER_LABELS|@count gt 0}
                    {foreach from=$CHART_USER_LABELS item=USER_NAME}
                        <span class="mk-dash-chart-xlabel" title="{$USER_NAME|escape:'html'}">{$USER_NAME|escape:'html'}</span>
                    {/foreach}
                {/if}
                </div>
            </div>
        </div>
    {else}
        <span class="noDataMsg">
            {vtranslate('LBL_NO')} {vtranslate($MODULE_NAME, $MODULE_NAME)} {vtranslate('LBL_MATCHED_THIS_CRITERIA')}
        </span>
    {/if}
{/strip}
