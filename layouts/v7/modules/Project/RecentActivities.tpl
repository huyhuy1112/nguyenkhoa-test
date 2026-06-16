{* Project MANAGEMENT — premium update history timeline *}
{strip}
	<div class="recentActivitiesContainer mk-proj-updates-panel" id="updates">
		<input type="hidden" id="updatesCurrentPage" value="{$PAGING_MODEL->get('page')}"/>
		{if isset($TOTAL_UPDATES_COUNT)}
			<input type="hidden" name="totalUpdatesCount" value="{$TOTAL_UPDATES_COUNT}" />
		{/if}
		<div class="mk-proj-updates-panel__body history">
			{if !empty($RECENT_ACTIVITIES)}
				<ul class="updates_timeline mk-proj-updates-timeline">
					{foreach item=RECENT_ACTIVITY from=$RECENT_ACTIVITIES}
						{assign var=PROCEED value=TRUE}
						{if ($RECENT_ACTIVITY->isRelationLink()) or ($RECENT_ACTIVITY->isRelationUnLink())}
							{assign var=RELATION value=$RECENT_ACTIVITY->getRelationInstance()}
							{if !($RELATION->getLinkedRecord())}
								{assign var=PROCEED value=FALSE}
							{/if}
						{/if}
						{if $PROCEED}
							{if $RECENT_ACTIVITY->isCreate()}
								<li class="mk-proj-update-item mk-proj-update-item--create">
									<time class="update_time mk-proj-update-time cursorDefault">
										<small title="{Vtiger_Util_Helper::formatDateTimeIntoDayString($RECENT_ACTIVITY->getParent()->get('createdtime'))}">
											{Vtiger_Util_Helper::formatDateDiffInStrings($RECENT_ACTIVITY->getParent()->get('createdtime'))}
										</small>
									</time>
									{assign var=USER_MODEL value=$RECENT_ACTIVITY->getModifiedBy()}
									{assign var=IMAGE_DETAILS value=$USER_MODEL->getImageDetails()}
									{if $IMAGE_DETAILS neq '' && $IMAGE_DETAILS[0] neq '' && $IMAGE_DETAILS[0].url eq ''}
										<div class="update_icon mk-proj-update-icon bg-info">
											<i class="update_image vicon-vtigeruser"></i>
										</div>
									{else}
										{foreach item=IMAGE_INFO from=$IMAGE_DETAILS}
											{if !empty($IMAGE_INFO.url)}
												<div class="update_icon mk-proj-update-icon">
													<img class="update_image" src="{$IMAGE_INFO.url}" alt="">
												</div>
											{/if}
										{/foreach}
									{/if}
									<div class="update_info mk-proj-update-info">
										<h5 class="mk-proj-update-title">
											<span class="field-name">{$RECENT_ACTIVITY->getModifiedBy()->getName()}</span>
											{vtranslate('LBL_CREATED', $MODULE_NAME)}
										</h5>
									</div>
								</li>
							{else if $RECENT_ACTIVITY->isUpdate()}
								<li class="mk-proj-update-item mk-proj-update-item--update">
									<time class="update_time mk-proj-update-time cursorDefault">
										<small title="{Vtiger_Util_Helper::formatDateTimeIntoDayString($RECENT_ACTIVITY->getActivityTime())}">
											{Vtiger_Util_Helper::formatDateDiffInStrings($RECENT_ACTIVITY->getActivityTime())}
										</small>
									</time>
									{assign var=USER_MODEL value=$RECENT_ACTIVITY->getModifiedBy()}
									{assign var=IMAGE_DETAILS value=$USER_MODEL->getImageDetails()}
									{if $IMAGE_DETAILS neq '' && $IMAGE_DETAILS[0] neq '' && $IMAGE_DETAILS[0].url eq ''}
										<div class="update_icon mk-proj-update-icon bg-info">
											<i class="update_image vicon-vtigeruser"></i>
										</div>
									{else}
										{foreach item=IMAGE_INFO from=$IMAGE_DETAILS}
											{if !empty($IMAGE_INFO.url)}
												<div class="update_icon mk-proj-update-icon">
													<img class="update_image" src="{$IMAGE_INFO.url}" alt="">
												</div>
											{/if}
										{/foreach}
									{/if}
									<div class="update_info mk-proj-update-info">
										<h5 class="mk-proj-update-title">
											<span class="field-name">{$RECENT_ACTIVITY->getModifiedBy()->getDisplayName()}</span>
											{vtranslate('LBL_UPDATED', $MODULE_NAME)}
										</h5>
										{foreach item=FIELDMODEL from=$RECENT_ACTIVITY->getFieldInstances()}
											{assign var=FIELD_NAME value=$FIELDMODEL->getFieldInstance()->getName()}
											{assign var=PRE_DISPLAY_VALUE value=$FIELDMODEL->getDisplayValue(decode_html($FIELDMODEL->get('prevalue')))}
											{assign var=POST_DISPLAY_VALUE value=$FIELDMODEL->getDisplayValue(decode_html($FIELDMODEL->get('postvalue')))}
											{assign var=TIME_PRE_DISPLAY_VALUE value=$FIELDMODEL->getDisplayValue(decode_html($FIELDMODEL->get('prevalue')))}
											{if in_array($FIELD_NAME,array('time_start','time_end')) && in_array($MODULE_NAME,array('Events','Calendar'))}
												{assign var=CALENDAR_RECORD_MODEL value=Vtiger_Record_Model::getInstanceById($RECORD_ID)}
												{assign var=TIME_PRE_DISPLAY_VALUE value={Calendar_Time_UIType::getModTrackerDisplayValue($FIELD_NAME,$FIELDMODEL->get('prevalue'),$CALENDAR_RECORD_MODEL)}}
												{assign var=TIME_POST_DISPLAY_VALUE value={Calendar_Time_UIType::getModTrackerDisplayValue($FIELD_NAME,$FIELDMODEL->get('postvalue'),$CALENDAR_RECORD_MODEL)}}
												{assign var=PRE_DISPLAY_VALUE value=$TIME_PRE_DISPLAY_VALUE}
												{assign var=POST_DISPLAY_VALUE value=$TIME_POST_DISPLAY_VALUE}
											{/if}
											{if isset($TIME_PRE_DISPLAY_VALUE)}
												{assign var=PRE_DISPLAY_TITLE value=$TIME_PRE_DISPLAY_VALUE}
											{else}
												{assign var=PRE_DISPLAY_TITLE value=''}
											{/if}
											{if $FIELDMODEL && $FIELDMODEL->getFieldInstance() && $FIELDMODEL->getFieldInstance()->isViewable() && $FIELDMODEL->getFieldInstance()->getDisplayType() neq '5'}
												<div class="font-x-small updateInfoContainer mk-proj-update-change textOverflowEllipsis">
													<div class="update-name mk-proj-update-change__name">
														<span class="field-name">{vtranslate($FIELDMODEL->getName(),$MODULE_NAME)}</span>
														{if $FIELDMODEL->get('prevalue') neq '' && $FIELDMODEL->get('postvalue') neq '' && !($FIELDMODEL->getFieldInstance()->getFieldDataType() eq 'reference' && ($FIELDMODEL->get('postvalue') eq '0' || $FIELDMODEL->get('prevalue') eq '0'))}
															<span>{vtranslate('LBL_CHANGED')}</span>
														{else if $FIELDMODEL->get('postvalue') eq '' || ($FIELDMODEL->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELDMODEL->get('postvalue') eq '0')}
															<span>(<del>{Vtiger_Util_Helper::toVtiger6SafeHTML($PRE_DISPLAY_VALUE)}</del>) {vtranslate('LBL_IS_REMOVED')}</span>
														{else if $FIELDMODEL->get('postvalue') neq '' && !($FIELDMODEL->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELDMODEL->get('postvalue') eq '0')}
															<span>{vtranslate('LBL_UPDATED')}</span>
														{else}
															<span>{vtranslate('LBL_CHANGED')}</span>
														{/if}
													</div>
													{if $FIELDMODEL->get('prevalue') neq '' && $FIELDMODEL->get('postvalue') neq '' && !($FIELDMODEL->getFieldInstance()->getFieldDataType() eq 'reference' && ($FIELDMODEL->get('postvalue') eq '0' || $FIELDMODEL->get('prevalue') eq '0'))}
														<div class="update-from mk-proj-update-change__from">
															<span class="field-name">{vtranslate('LBL_FROM')}</span>
															<em title="{strip_tags({Vtiger_Util_Helper::toVtiger6SafeHTML($PRE_DISPLAY_TITLE)})}">{Vtiger_Util_Helper::toVtiger6SafeHTML($PRE_DISPLAY_VALUE)}</em>
														</div>
													{/if}
													{if $FIELDMODEL->get('postvalue') neq '' && !($FIELDMODEL->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELDMODEL->get('postvalue') eq '0')}
														<div class="update-to mk-proj-update-change__to">
															<span class="field-name">{vtranslate('LBL_TO')}</span>
															<em>{Vtiger_Util_Helper::toVtiger6SafeHTML($POST_DISPLAY_VALUE)}</em>
														</div>
													{/if}
												</div>
											{/if}
										{/foreach}
									</div>
								</li>
							{else if ($RECENT_ACTIVITY->isRelationLink() || $RECENT_ACTIVITY->isRelationUnLink())}
								{assign var=RELATED_MODULE value=$RELATION->getLinkedRecord()->getModuleName()}
								<li class="mk-proj-update-item mk-proj-update-item--relation">
									<time class="update_time mk-proj-update-time cursorDefault">
										<small title="{Vtiger_Util_Helper::formatDateTimeIntoDayString($RELATION->get('changedon'))}">
											{Vtiger_Util_Helper::formatDateDiffInStrings($RELATION->get('changedon'))}
										</small>
									</time>
									<div class="update_icon mk-proj-update-icon bg-info-{$RELATED_MODULE|strtolower}">
										{if {$RELATED_MODULE|strtolower eq 'modcomments'}}
											<i class="update_image vicon-chat"></i>
										{else}
											<span class="update_image">{Vtiger_Module_Model::getModuleIconPath($RELATED_MODULE)}</span>
										{/if}
									</div>
									<div class="update_info mk-proj-update-info">
										<h5 class="mk-proj-update-title">
											<span class="field-name">{vtranslate($RELATION->getLinkedRecord()->getModuleName(), $RELATION->getLinkedRecord()->getModuleName())}</span>
											{if $RECENT_ACTIVITY->isRelationLink()}
												{vtranslate('LBL_LINKED', $MODULE_NAME)}
											{else}
												{vtranslate('LBL_UNLINKED', $MODULE_NAME)}
											{/if}
										</h5>
										<div class="font-x-small updateInfoContainer mk-proj-update-change textOverflowEllipsis">
											{if $RELATION->getLinkedRecord()->getModuleName() eq 'Calendar'}
												{if isPermitted('Calendar', 'DetailView', $RELATION->getLinkedRecord()->getId()) eq 'yes'}
													{assign var=PERMITTED value=1}
												{else}
													{assign var=PERMITTED value=0}
												{/if}
											{else}
												{assign var=PERMITTED value=1}
											{/if}
											{if $PERMITTED}
												{if $RELATED_MODULE eq 'ModComments'}
													{$RELATION->getLinkedRecord()->getName()}
												{else}
													{assign var=DETAILVIEW_URL value=$RELATION->getRecordDetailViewUrl()}
													{if $DETAILVIEW_URL}<a {if stripos($DETAILVIEW_URL, 'javascript:') === 0}onclick{else}href{/if}='{$DETAILVIEW_URL}'>{/if}
														<strong>{$RELATION->getLinkedRecord()->getName()}</strong>
													{if $DETAILVIEW_URL}</a>{/if}
												{/if}
											{/if}
										</div>
									</div>
								</li>
							{else if $RECENT_ACTIVITY->isRestore()}
							{/if}
						{/if}
					{/foreach}
					{if isset($TOTAL_UPDATES_COUNT)}
						{assign var=UPDATES_TOTAL_COUNT value=$TOTAL_UPDATES_COUNT}
					{else}
						{assign var=UPDATES_TOTAL_COUNT value=php7_count($RECENT_ACTIVITIES)}
					{/if}
					{if $UPDATES_TOTAL_COUNT gt 5 && $PAGING_MODEL->isNextPageExists()}
						<li id="more_button" class="mk-proj-updates-more-item">
							<div class="mk-proj-updates-more-footer" id="moreLink">
								<button type="button" class="btn moreRecentUpdates mk-proj-updates-more-btn">{vtranslate('LBL_MORE',$MODULE_NAME)}</button>
							</div>
						</li>
					{/if}
				</ul>
			{else}
				<div class="mk-proj-updates-empty summaryWidgetContainer">
					<p class="textAlignCenter">{vtranslate('LBL_NO_RECENT_UPDATES')}</p>
				</div>
			{/if}
		</div>
	</div>
{/strip}
