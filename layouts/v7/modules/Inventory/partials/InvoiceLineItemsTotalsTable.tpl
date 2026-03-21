{* Invoice-only: same tr content/ids as core; rows reordered for VAT-style summary. *}
{if $SH_PERCENT_EDITABLE}
	{assign var=CHARGE_AND_CHARGETAX_VALUES value=(isset($FINAL.chargesAndItsTaxes)) ? $FINAL.chargesAndItsTaxes :NULL}
{/if}
				<tr>
					<td width="83%">
						<div class="pull-right"><strong>{vtranslate('LBL_ITEMS_TOTAL',$MODULE)}</strong></div>
					</td>
					<td>
						<div id="netTotal" class="pull-right netTotal">{if !empty($FINAL.hdnSubTotal)}{$FINAL.hdnSubTotal}{else}0{/if}</div>
					</td>
				</tr>
				{if $DISCOUNT_AMOUNT_EDITABLE || $DISCOUNT_PERCENT_EDITABLE}
					<tr>
						<td width="83%">
							<span class="pull-right">(-)&nbsp;
								<strong><a href="javascript:void(0)" id="finalDiscount">{vtranslate('LBL_OVERALL_DISCOUNT',$MODULE)}&nbsp;
										<span id="overallDiscount">
											{if isset($FINAL.discount_type_final) && $DISCOUNT_PERCENT_EDITABLE && $FINAL.discount_type_final eq 'percentage'}
												({$FINAL.discount_percentage_final}%)
											{else if isset($FINAL.discount_type_final) && $DISCOUNT_AMOUNT_EDITABLE && $FINAL.discount_type_final eq 'amount'}
												({$FINAL.discount_amount_final})
											{else}
												(0)
											{/if}
										</span></a>
								</strong>
							</span>
						</td>
						<td>
							<span id="discountTotal_final" class="pull-right discountTotal_final">{if (isset($FINAL.discountTotal_final)) ? $FINAL.discountTotal_final : ""}{$FINAL.discountTotal_final}{else}0{/if}</span>
							<div id="finalDiscountUI" class="finalDiscountUI validCheck hide">
								{assign var=DISCOUNT_TYPE_FINAL value="zero"}
								{if !empty($FINAL.discount_type_final)}
									{assign var=DISCOUNT_TYPE_FINAL value=$FINAL.discount_type_final }
								{/if}
								<input type="hidden" id="discount_type_final" name="discount_type_final" value="{$DISCOUNT_TYPE_FINAL}" />
								<p class="popover_title hide">
									{vtranslate('LBL_SET_DISCOUNT_FOR',$MODULE)} : <span class="subTotalVal">{if !empty($FINAL.hdnSubTotal)}{$FINAL.hdnSubTotal}{else}0{/if}</span>
								</p>
								<table width="100%" border="0" cellpadding="5" cellspacing="0" class="table table-nobordered popupTable">
									<tbody>
										<tr>
											<td><input type="radio" name="discount_final" class="finalDiscounts" data-discount-type="zero" {if $DISCOUNT_TYPE_FINAL eq 'zero'}checked{/if} />&nbsp; {vtranslate('LBL_ZERO_DISCOUNT',$MODULE)}</td>
											<td class="lineOnTop">
												<input type="hidden" class="discountVal" value="0" />
											</td>
										</tr>
										{if $DISCOUNT_PERCENT_EDITABLE}
											<tr>
												<td><input type="radio" name="discount_final" class="finalDiscounts" data-discount-type="percentage" {if $DISCOUNT_TYPE_FINAL eq 'percentage'}checked{/if} />&nbsp; % {vtranslate('LBL_OF_PRICE',$MODULE)}</td>
												<td><span class="pull-right">&nbsp;%</span><input type="text" data-rule-positive=true data-rule-inventory_percentage=true id="discount_percentage_final" name="discount_percentage_final" value="{(isset($FINAL.discount_percentage_final)) ? $FINAL.discount_percentage_final : ''}" class="discount_percentage_final span1 pull-right discountVal {if $DISCOUNT_TYPE_FINAL neq 'percentage'}hide{/if}" /></td>
											</tr>
										{/if}
										{if $DISCOUNT_AMOUNT_EDITABLE}
											<tr>
												<td><input type="radio" name="discount_final" class="finalDiscounts" data-discount-type="amount" {if $DISCOUNT_TYPE_FINAL eq 'amount'}checked{/if} />&nbsp;{vtranslate('LBL_DIRECT_PRICE_REDUCTION',$MODULE)}</td>
												<td><input type="text" data-rule-positive=true id="discount_amount_final" name="discount_amount_final" value="{(isset($FINAL.discount_amount_final)) ? $FINAL.discount_amount_final : ''}" class="span1 pull-right discount_amount_final discountVal {if $DISCOUNT_TYPE_FINAL neq 'amount'}hide{/if}" /></td>
											</tr>
										{/if}
									</tbody>
								</table>
							</div>
						</td>
					</tr>
				{/if}
				<tr>
					<td width="83%">
						<span class="pull-right"><strong>{vtranslate('LBL_PRE_TAX_TOTAL', $MODULE)} </strong></span>
					</td>
					<td>
						{assign var=PRE_TAX_TOTAL value="{(isset($FINAL.preTaxTotal)) ? $FINAL.preTaxTotal:''}"}
						<span class="pull-right" id="preTaxTotal">{if $PRE_TAX_TOTAL}{$PRE_TAX_TOTAL}{else}0{/if}</span>
						<input type="hidden" id="pre_tax_total" name="pre_tax_total" value="{if $PRE_TAX_TOTAL}{$PRE_TAX_TOTAL}{else}0{/if}"/>
					</td>
				</tr>
				<tr id="group_tax_row" valign="top" class="{if $IS_INDIVIDUAL_TAX_TYPE}hide{/if}">
					<td width="83%">
						<span class="pull-right">(+)&nbsp;<strong><a href="javascript:void(0)" id="finalTax">{vtranslate('LBL_TAX',$MODULE)}</a></strong></span>
						<div class="hide finalTaxUI validCheck" id="group_tax_div">
							<input type="hidden" class="popover_title" value="{vtranslate('LBL_GROUP_TAX',$MODULE)}" />
							<table width="100%" border="0" cellpadding="5" cellspacing="0" class="table table-nobordered popupTable">
								{foreach item=tax_detail name=group_tax_loop key=loop_count from=$TAXES}
									<tr>
										<td class="lineOnTop">{$tax_detail.taxlabel}</td>
										<td class="lineOnTop">
											<input type="text" size="5" data-compound-on="{if $tax_detail['method'] eq 'Compound'}{Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($tax_detail['compoundon']))}{/if}"
												   name="{$tax_detail.taxname}_group_percentage" id="group_tax_percentage{$smarty.foreach.group_tax_loop.iteration}" value="{$tax_detail.percentage}" class="span1 groupTaxPercentage"
												   data-rule-positive=true data-rule-inventory_percentage=true />&nbsp;%
										</td>
										<td style="text-align: right;" class="lineOnTop">
											<input type="text" size="6" name="{$tax_detail.taxname}_group_amount" id="group_tax_amount{$smarty.foreach.group_tax_loop.iteration}" style="cursor:pointer;" value="{(isset($tax_detail.amount))?$tax_detail.amount:''}" readonly class="cursorPointer span1 groupTaxTotal" />
										</td>
									</tr>
								{/foreach}
								<input type="hidden" id="group_tax_count" value="{$smarty.foreach.group_tax_loop.iteration}" />
							</table>
						</div>
					</td>
					<td><span id="tax_final" class="pull-right tax_final">{if (isset($FINAL.tax_totalamount)) ? $FINAL.tax_totalamount : ""}{$FINAL.tax_totalamount}{else}0{/if}</span></td>
				</tr>
				{if $SH_PERCENT_EDITABLE}
					<tr>
						<td width="83%">
							<span class="pull-right">(+)&nbsp;<strong><a href="javascript:void(0)" id="chargeTaxes">{vtranslate('LBL_TAXES_ON_CHARGES',$MODULE)} </a></strong></span>
							<div id="chargeTaxesBlock" class="hide validCheck chargeTaxesBlock">
								<p class="popover_title hide">
									{vtranslate('LBL_TAXES_ON_CHARGES', $MODULE)} : <span id="SHChargeVal" class="SHChargeVal">{if (isset($FINAL.shipping_handling_charge)) ? $FINAL.shipping_handling_charge : ""}{$FINAL.shipping_handling_charge}{else}0{/if}</span>
								</p>
								<table class="table table-nobordered popupTable">
									<tbody>
										{foreach key=CHARGE_ID item=CHARGE_MODEL from=$INVENTORY_CHARGES}
											{foreach key=CHARGE_TAX_ID item=CHARGE_TAX_MODEL from=$RECORD->getChargeTaxModelsList($CHARGE_ID)}
												{if !isset($CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['taxes'][$CHARGE_TAX_ID]) && $CHARGE_TAX_MODEL->isDeleted()}
													{continue}
												{/if}
												{if !$RECORD_ID && $CHARGE_TAX_MODEL->isDeleted()}
													{continue}
												{/if}
												<tr>
													{assign var=SH_TAX_VALUE value=$CHARGE_TAX_MODEL->getTax()}
													{if isset($CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['value']) && $CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['value'] neq NULL}
														{assign var=SH_TAX_VALUE value=0}
														{if $CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['taxes'][$CHARGE_TAX_ID]}
															{assign var=SH_TAX_VALUE value=$CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['taxes'][$CHARGE_TAX_ID]}
														{/if}
													{/if}
													<td class="lineOnTop">{$CHARGE_MODEL->getName()} - {$CHARGE_TAX_MODEL->getName()}</td>
													<td class="lineOnTop">
														<input type="text" data-charge-id="{$CHARGE_ID}" data-compound-on="{if $CHARGE_TAX_MODEL->getTaxMethod() eq 'Compound'}{$CHARGE_TAX_MODEL->get('compoundon')}{/if}"
															   class="span1 chargeTaxPercentage" name="charges[{$CHARGE_ID}][taxes][{$CHARGE_TAX_ID}]" value="{$SH_TAX_VALUE}"
															   data-rule-positive=true data-rule-inventory_percentage=true />&nbsp;%
													</td>
													<td style="text-align: right;" class="lineOnTop">
														<input type="text" class="span1 chargeTaxValue cursorPointer pull-right chargeTax{$CHARGE_ID}{$CHARGE_TAX_ID}" size="5" value="0" readonly />&nbsp;
													</td>
												</tr>
											{/foreach}
										{/foreach}
									</tbody>
								</table>
							</div>
						</td>
						<td>
							<input type="hidden" id="chargeTaxTotalHidden" class="chargeTaxTotal" name="s_h_percent" value="{if (isset($FINAL.shtax_totalamount)) ? $FINAL.shtax_totalamount : ""}{$FINAL.shtax_totalamount}{else}0{/if}" />
							<span class="pull-right" id="chargeTaxTotal">{if (isset($FINAL.shtax_totalamount)) ? $FINAL.shtax_totalamount : ""}{$FINAL.shtax_totalamount}{else}0{/if}</span>
						</td>
					</tr>
					<tr>
						<td width="83%">
							<span class="pull-right">(+)&nbsp;<strong><a href="javascript:void(0)" id="charges">{vtranslate('LBL_CHARGES',$MODULE)}</a></strong></span>
							<div id="chargesBlock" class="validCheck hide chargesBlock">
								<table width="100%" border="0" cellpadding="5" cellspacing="0" class="table table-nobordered popupTable">
									{foreach key=CHARGE_ID item=CHARGE_MODEL from=$INVENTORY_CHARGES}
										<tr>
											{assign var=CHARGE_VALUE value= (isset($CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['value'])) ? $CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['value']:NULL}
											{assign var=CHARGE_PERCENT value=0}
											{if $CHARGE_MODEL->get('format') eq 'Percent' && $CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['percent'] neq NULL}
												{assign var=CHARGE_PERCENT value=$CHARGE_AND_CHARGETAX_VALUES[$CHARGE_ID]['percent']}
											{/if}
											<td class="lineOnTop chargeName" data-charge-id="{$CHARGE_ID}">{$CHARGE_MODEL->getName()}</td>
											<td class="lineOnTop">
												{if $CHARGE_MODEL->get('format') eq 'Percent'}
													<input type="text" class="span1 chargePercent" size="5" data-rule-positive=true data-rule-inventory_percentage=true name="charges[{$CHARGE_ID}][percent]" value="{if $CHARGE_PERCENT}{$CHARGE_PERCENT}{else if $RECORD_ID}0{else}{$CHARGE_MODEL->getValue()}{/if}" />&nbsp;%
												{/if}
											</td>
											<td style="text-align: right;" class="lineOnTop">
												<input type="text" class="span1 chargeValue" size="5" {if $CHARGE_MODEL->get('format') eq 'Percent'}readonly{/if} data-rule-positive=true name="charges[{$CHARGE_ID}][value]" value="{if $CHARGE_VALUE}{$CHARGE_VALUE}{else if $RECORD_ID}0{else}{$CHARGE_MODEL->getValue() * $USER_MODEL->get('conv_rate')}{/if}" />&nbsp;
											</td>
										</tr>
									{/foreach}
								</table>
							</div>
						</td>
						<td>
							<input type="hidden" class="lineItemInputBox" id="chargesTotal" name="shipping_handling_charge" value="{if (isset($FINAL.shipping_handling_charge)) ? $FINAL.shipping_handling_charge : ""}{$FINAL.shipping_handling_charge}{else}0{/if}" />
							<span id="chargesTotalDisplay" class="pull-right chargesTotalDisplay">{if (isset($FINAL.shipping_handling_charge)) ? $FINAL.shipping_handling_charge : ""}{$FINAL.shipping_handling_charge}{else}0{/if}</span>
						</td>
					</tr>
					<tr>
						<td width="83%">
							<span class="pull-right">(-)&nbsp;<strong><a href="javascript:void(0)" id="deductTaxes">{vtranslate('LBL_DEDUCTED_TAXES',$MODULE)} </a></strong></span>
							<div id="deductTaxesBlock" class="hide validCheck deductTaxesBlock">
								<table class="table table-nobordered popupTable">
									<tbody>
										{foreach key=DEDUCTED_TAX_ID item=DEDUCTED_TAX_INFO from=$DEDUCTED_TAXES}
											<tr>
												<td class="lineOnTop">{$DEDUCTED_TAX_INFO['taxlabel']}</td>
												<td class="lineOnTop">
													<input type="text" class="span1 deductTaxPercentage" name="{$DEDUCTED_TAX_INFO['taxname']}_group_percentage" value="{if $DEDUCTED_TAX_INFO['selected'] || !$RECORD_ID}{$DEDUCTED_TAX_INFO['percentage']}{else}0{/if}"
														   data-rule-positive=true data-rule-inventory_percentage=true />&nbsp;%
												</td>
												<td style="text-align: right;" class="lineOnTop">
													<input type="text" class="span1 deductTaxValue cursorPointer pull-right" name="{$DEDUCTED_TAX_INFO['taxname']}_group_amount" size="5" readonly value="{$DEDUCTED_TAX_INFO['amount']}"/>&nbsp;
												</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						</td>
						<td>
							<span class="pull-right" id="deductTaxesTotalAmount">{if isset($FINAL.deductTaxesTotalAmount) && $FINAL.deductTaxesTotalAmount}{$FINAL.deductTaxesTotalAmount}{else}0{/if}</span>
						</td>
					</tr>
				{/if}
				<tr valign="top">
					<td width="83%" >
						<div class="pull-right">
							<strong>{vtranslate('LBL_ADJUSTMENT',$MODULE)}&nbsp;&nbsp;</strong>
							<span>
								<input type="radio" name="adjustmentType" option value="+" {if isset($FINAL.adjustment) && $FINAL.adjustment gte 0}checked{/if}>&nbsp;{vtranslate('LBL_ADD',$MODULE)}&nbsp;&nbsp;
							</span>
							<span>
								<input type="radio" name="adjustmentType" option value="-" {if isset($FINAL.adjustment) && $FINAL.adjustment lt 0}checked{/if}>&nbsp;{vtranslate('LBL_DEDUCT',$MODULE)}
							</span>
						</div>
					</td>
					<td>
						<span class="pull-right">
							<input id="adjustment" name="adjustment" type="text" data-rule-positive="true" class="lineItemInputBox form-control" value="{if isset($FINAL.adjustment) && $FINAL.adjustment lt 0}{abs($FINAL.adjustment)}{elseif isset($FINAL.adjustment) && $FINAL.adjustment}{$FINAL.adjustment}{else}0{/if}">
						</span>
					</td>
				</tr>
				<tr valign="top">
					<td width="83%">
						<span class="pull-right"><strong>{vtranslate('LBL_GRAND_TOTAL',$MODULE)}</strong></span>
					</td>
					<td>
						<span id="grandTotal" name="grandTotal" class="pull-right grandTotal">{(isset($FINAL.grandTotal)) ? $FINAL.grandTotal : ""}</span>
					</td>
				</tr>
				<tr valign="top">
					<td width="83%" >
						<div class="pull-right">
							<strong>{vtranslate('LBL_RECEIVED',$MODULE)}</strong>
						</div>
					</td>
					<td>
						<span class="pull-right"><input id="received" name="received" type="text" class="lineItemInputBox form-control" value="{if $RECORD->getDisplayValue('received') && !($IS_DUPLICATE)}{$RECORD->getDisplayValue('received')}{else}0{/if}"></span>
					</td>
				</tr>
				<tr valign="top">
					<td width="83%" >
						<div class="pull-right">
							<strong>{vtranslate('LBL_BALANCE',$MODULE)}</strong>
						</div>
					</td>
					<td>
						<span class="pull-right"><input id="balance" name="balance" type="text" class="lineItemInputBox form-control" value="{if $RECORD->getDisplayValue('balance') && !($IS_DUPLICATE)}{$RECORD->getDisplayValue('balance')}{else}0{/if}" readonly></span>
					</td>
				</tr>
