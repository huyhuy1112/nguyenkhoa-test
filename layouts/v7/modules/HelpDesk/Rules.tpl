{strip}

<div class="helpdesk-rules-page mk-hd-rules">

	<header class="mk-hd-rules__intro">
		<div class="mk-hd-rules__intro-row">
			<div class="mk-hd-rules__intro-copy">
				<nav class="mk-hd-rules__crumb" aria-label="Breadcrumb">
					<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SUPPORT">Home</a>
					<span aria-hidden="true">&gt;</span>
					<span class="mk-hd-rules__crumb-current">All Rules</span>
				</nav>
				<h1 class="mk-hd-rules__title">All rules</h1>
				<p class="mk-hd-rules__subtitle">Configure SLA response times for each support level.</p>
			</div>
			<div class="mk-hd-rules__intro-actions">
				<a class="mk-hd-rules__btn-add" href="index.php?module=HelpDesk&amp;view=RuleDetail&amp;rule_id=0&amp;app=SUPPORT">
					<span class="mk-hd-rules__btn-add-icon" aria-hidden="true">
						{include file="partials/RulesListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}
					</span>
					Add rule
				</a>
			</div>
		</div>

		<div class="mk-hd-rules__strip" role="presentation">
			<div class="mk-hd-rules__strip-item">
				<span class="mk-hd-rules__strip-k">Total</span>
				<span class="mk-hd-rules__strip-v">{$RULES_TOTAL}</span>
			</div>
			<div class="mk-hd-rules__strip-div" aria-hidden="true"></div>
			<div class="mk-hd-rules__strip-item">
				<span class="mk-hd-rules__strip-k">Active</span>
				<span class="mk-hd-rules__strip-v">{$RULES_ACTIVE_COUNT}</span>
			</div>
			<div class="mk-hd-rules__strip-div" aria-hidden="true"></div>
			<div class="mk-hd-rules__strip-item mk-hd-rules__strip-item--wide">
				<span class="mk-hd-rules__strip-k">Tip</span>
				<span class="mk-hd-rules__strip-tip">Each ticket uses the customer’s support level (1–3) to pick the matching SLA minutes.</span>
			</div>
		</div>
	</header>

	<section class="mk-hd-rules__card" aria-label="Rules table">
		<div class="mk-hd-rules__table-wrap">
			<table class="mk-hd-rules__table">
				<colgroup>
					<col />
					<col />
					<col span="3" />
					<col />
					<col class="mk-hd-rules__col-actions" />
				</colgroup>
				<thead>
					<tr>
						<th scope="col">Rule name</th>
						<th scope="col">Rule type</th>
						<th scope="col">Level 1 (min)</th>
						<th scope="col">Level 2 (min)</th>
						<th scope="col">Level 3 (min)</th>
						<th scope="col">Trạng thái</th>
						<th scope="col" class="mk-hd-rules__th-actions">Hành động</th>
					</tr>
				</thead>
				<tbody>
					{if $SUPPORT_RULES|@count gt 0}
						{foreach from=$SUPPORT_RULES item=R}
							<tr>
								<td class="mk-hd-rules__name">
									<span class="mk-hd-rules__name-text">{$R.rule_name|escape}</span>
								</td>
								<td>
									{if $R.rule_type eq 'first_response'}
										First Response
									{elseif $R.rule_type eq 'customer_update'}
										Customer Update
									{elseif $R.rule_type eq 'project_progress_update'}
										Project Progress Update
									{elseif $R.rule_type eq 'meeting_summary'}
										Meeting Summary
									{else}
										{$R.rule_type|escape}
									{/if}
								</td>
								<td><span class="mk-hd-rules__minutes">{$R.level_1_time_minutes|default:'–'}</span></td>
								<td><span class="mk-hd-rules__minutes">{$R.level_2_time_minutes|default:'–'}</span></td>
								<td><span class="mk-hd-rules__minutes">{$R.level_3_time_minutes|default:'–'}</span></td>
								<td>
									{if $R.is_active}
										<span class="mk-hd-rules__status mk-hd-rules__status--active">Active</span>
									{else}
										<span class="mk-hd-rules__status mk-hd-rules__status--inactive">Disabled</span>
									{/if}
								</td>
								<td class="mk-hd-rules__actions">
									<div class="mk-hd-rules__actions-inner">
										<a class="mk-hd-rules__icon-btn" href="index.php?module=HelpDesk&amp;view=RuleDetail&amp;rule_id={$R.id}&amp;app=SUPPORT" title="Edit" aria-label="Edit {$R.rule_name|escape}">
											{include file="partials/RulesListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}
										</a>
										{if $R.is_active}
											<a class="mk-hd-rules__icon-btn mk-hd-rules__icon-btn--danger js-mk-hd-rule-disable" href="index.php?module=HelpDesk&amp;view=Rules&amp;mode=disable&amp;rule_id={$R.id}&amp;app=SUPPORT" title="Disable" aria-label="Disable {$R.rule_name|escape}" data-rule-name="{$R.rule_name|escape:'html'}">
												{include file="partials/RulesListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='DELETE'}
											</a>
										{else}
											<a class="mk-hd-rules__icon-btn mk-hd-rules__icon-btn--enable" href="index.php?module=HelpDesk&amp;view=Rules&amp;mode=enable&amp;rule_id={$R.id}&amp;app=SUPPORT" title="Enable" aria-label="Enable {$R.rule_name|escape}">
												<span class="mk-hd-rules__enable-label">Enable</span>
											</a>
										{/if}
									</div>
								</td>
							</tr>
						{/foreach}
					{else}
						<tr>
							<td colspan="7" class="mk-hd-rules__empty">
								<div class="mk-hd-rules__empty-inner">
									<p class="mk-hd-rules__empty-title">Chưa có rule nào</p>
									<p class="mk-hd-rules__empty-text">Tạo rule đầu tiên để gán SLA theo từng mức hỗ trợ.</p>
									<a class="mk-hd-rules__btn-add mk-hd-rules__btn-add--inline" href="index.php?module=HelpDesk&amp;view=RuleDetail&amp;rule_id=0&amp;app=SUPPORT">
										<span class="mk-hd-rules__btn-add-icon" aria-hidden="true">
											{include file="partials/RulesListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}
										</span>
										Add rule
									</a>
								</div>
							</td>
						</tr>
					{/if}
				</tbody>
			</table>
		</div>

		<footer class="mk-hd-rules__footer">
			<p class="mk-hd-rules__range">
				Hiển thị <strong>{$RULES_SHOW_FROM}</strong>–<strong>{$RULES_SHOW_TO}</strong> trong số <strong>{$RULES_TOTAL}</strong> rules
			</p>
			<div class="mk-hd-rules__pager">
				{if $RULES_PAGE gt 1}
					<a class="mk-hd-rules__pager-btn" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;page={$RULES_PAGE-1}">Trước</a>
				{else}
					<span class="mk-hd-rules__pager-btn mk-hd-rules__pager-btn--disabled" aria-disabled="true">Trước</span>
				{/if}
				{if $RULES_PAGE lt $RULES_PAGES}
					<a class="mk-hd-rules__pager-btn" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;page={$RULES_PAGE+1}">Sau</a>
				{else}
					<span class="mk-hd-rules__pager-btn mk-hd-rules__pager-btn--disabled" aria-disabled="true">Sau</span>
				{/if}
			</div>
		</footer>
	</section>

</div>

{/strip}
