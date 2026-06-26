{* Contacts — phone fields limited to 10 digits *}
{strip}
{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
{if (!$FIELD_NAME)}
  {assign var="FIELD_NAME" value=$FIELD_MODEL->getFieldName()}
{/if}
<input id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" type="text" class="inputElement" name="{$FIELD_NAME}"
value="{$FIELD_MODEL->get('fieldvalue')}" data-fieldtype="phone" maxlength="10" inputmode="numeric" autocomplete="tel"
{if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if}
{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
{if php7_count($FIELD_INFO['validator'])}
    data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
{/if}
 />
{/strip}
