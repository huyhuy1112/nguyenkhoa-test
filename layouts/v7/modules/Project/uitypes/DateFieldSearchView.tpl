{* Project list — single-date calendar filter *}
{strip}
    {assign var="FIELD_INFO" value=Zend_Json::encode($FIELD_MODEL->getFieldInfo())}
    {assign var="dateFormat" value=$USER_MODEL->get('date_format')}
    <div class="input-group inputElement mk-date-search-group">
        <input type="text" name="{$FIELD_MODEL->get('name')}" class="listSearchContributor inputElement dateField mk-date-search-input ignore-ui-registration" data-date-format="{$dateFormat}" autocomplete="off" value="{if isset($SEARCH_INFO['searchValue'])}{$SEARCH_INFO['searchValue']}{/if}" data-fieldinfo='{$FIELD_INFO|escape}' data-field-type="{$FIELD_MODEL->getFieldDataType()}" placeholder="{vtranslate('LBL_SELECT', 'Vtiger')}" readonly="readonly" />
        <span class="input-group-addon mk-date-search-trigger" role="button" tabindex="-1" title="{vtranslate('LBL_SELECT', 'Vtiger')}"><i class="fa fa-calendar"></i></span>
    </div>
{/strip}
