{strip}
<div class="mk-hd-rule-detail-root">

	<nav class="mk-hd-rule-detail-breadcrumb" aria-label="Breadcrumb">
		<a href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT">Home</a>
		<span class="mk-hd-rule-detail-bc-sep" aria-hidden="true">&gt;</span>
		<a href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT">All Rules</a>
		<span class="mk-hd-rule-detail-bc-sep" aria-hidden="true">&gt;</span>
		{if isset($RULE_IS_NEW) && $RULE_IS_NEW}
			<span class="mk-hd-rule-detail-bc-current">New rule</span>
		{else}
			<span class="mk-hd-rule-detail-bc-current">{$RULE.rule_name|escape}</span>
		{/if}
	</nav>

	<section class="mk-hd-rule-detail-hero" aria-labelledby="mk-hd-rule-detail-title">
		<div class="mk-hd-rule-detail-hero-left">
			<div class="mk-hd-rule-detail-hero-icon" aria-hidden="true">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 4L4 8v4c0 5 3.5 9.5 8 10 4.5-.5 8-5 8-10V8l-8-4z" fill="#271900"/>
					<path d="M10 10h4v6h-4v-6z" fill="#FDBB2C"/>
				</svg>
			</div>
			<div class="mk-hd-rule-detail-hero-text">
				{if isset($RULE_IS_NEW) && $RULE_IS_NEW}
					<h1 id="mk-hd-rule-detail-title" class="mk-hd-rule-detail-hero-title">New support rule</h1>
					<p class="mk-hd-rule-detail-hero-sub">Define SLA minutes per support level, then save. You can enable the rule after it is created.</p>
				{else}
					<h1 id="mk-hd-rule-detail-title" class="mk-hd-rule-detail-hero-title">{$RULE.rule_name|escape}</h1>
					{if $RULE_CREATED_LABEL neq ''}
						<p class="mk-hd-rule-detail-hero-sub">Created at {$RULE_CREATED_LABEL|escape}</p>
					{/if}
				{/if}
			</div>
		</div>
		{if !isset($RULE_IS_NEW) || !$RULE_IS_NEW}
		<div class="mk-hd-rule-detail-hero-toggle-wrap">
			<span class="mk-hd-rule-detail-toggle-label mk-hd-rule-detail-toggle-label--muted">Enable</span>
			{if !isset($RULE.is_active) || $RULE.is_active}
				<a href="index.php?module=HelpDesk&amp;view=RuleDetail&amp;rule_id={$RULE.id}&amp;mode=disable&amp;app=SUPPORT"
				   class="mk-hd-rule-detail-toggle mk-hd-rule-detail-toggle--on"
				   title="{vtranslate('LBL_DISABLE', $MODULE)}"
				   aria-pressed="true">
					<span class="mk-hd-rule-detail-toggle-knob"></span>
				</a>
			{else}
				<a href="index.php?module=HelpDesk&amp;view=RuleDetail&amp;rule_id={$RULE.id}&amp;mode=enable&amp;app=SUPPORT"
				   class="mk-hd-rule-detail-toggle mk-hd-rule-detail-toggle--off"
				   title="{vtranslate('LBL_ENABLE', $MODULE)}"
				   aria-pressed="false">
					<span class="mk-hd-rule-detail-toggle-knob"></span>
				</a>
			{/if}
			<span class="mk-hd-rule-detail-toggle-label">Disable</span>
		</div>
		{/if}
	</section>

	<div class="mk-hd-rule-detail-grid">
		<div class="mk-hd-rule-detail-form-card">
			<div class="mk-hd-rule-detail-form-card-head">
				<h2 class="mk-hd-rule-detail-form-card-title">Rule Configuration</h2>
				<button type="button" class="mk-hd-rule-detail-info-btn" title="Info" aria-label="Info">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="12" cy="12" r="10" stroke="#94A3B8" stroke-width="1.5"/>
						<path d="M12 16v-5" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round"/>
						<circle cx="12" cy="8" r="1" fill="#94A3B8"/>
					</svg>
				</button>
			</div>
			<div class="mk-hd-rule-detail-form-card-body">
				<form class="mk-hd-rule-detail-form" method="post" action="index.php">
					<input type="hidden" name="module" value="HelpDesk" />
					<input type="hidden" name="action" value="SaveRule" />
					<input type="hidden" name="rule_id" value="{$RULE.id}" />
					<input type="hidden" name="return_view" value="RuleDetail" />
					<input type="hidden" name="app" value="SUPPORT" />

					<div class="mk-hd-rule-detail-field">
						<label class="mk-hd-rule-detail-label" for="mk_rule_name">Rule Name</label>
						<input id="mk_rule_name" type="text" name="rule_name" class="mk-hd-rule-detail-input"
							value="{$RULE.rule_name|escape}" required="required" />
					</div>

					<div class="mk-hd-rule-detail-field">
						<label class="mk-hd-rule-detail-label" for="mk_rule_type">Rule Type</label>
						<select id="mk_rule_type" name="rule_type" class="mk-hd-rule-detail-input" required="required">
							<option value="">-- Select --</option>
							{foreach from=$RULE_TYPES key=TYPE item=LABEL}
								<option value="{$TYPE|escape}"
									{if $RULE.rule_type eq $TYPE}selected="selected"{/if}>
									{$LABEL}
								</option>
							{/foreach}
						</select>
					</div>

					<div class="mk-hd-rule-detail-field">
						<label class="mk-hd-rule-detail-label" for="mk_rule_desc">Description</label>
						<textarea id="mk_rule_desc" name="description" class="mk-hd-rule-detail-input mk-hd-rule-detail-textarea" rows="3">{$RULE.description|escape}</textarea>
					</div>

					<div class="mk-hd-rule-detail-field mk-hd-rule-detail-field--inline">
						<label class="mk-hd-rule-detail-label" for="mk_rule_active">Active</label>
						<div class="mk-hd-rule-detail-active-wrap">
							<label class="mk-hd-rule-detail-checkbox">
								<input id="mk_rule_active" type="checkbox" name="is_active" value="1"
									{if !isset($RULE.is_active) || $RULE.is_active}checked="checked"{/if} />
								<span>Enabled</span>
							</label>
						</div>
					</div>

					<hr class="mk-hd-rule-detail-sep" />

					<div class="mk-hd-rule-detail-field mk-hd-rule-detail-field--level">
						<label class="mk-hd-rule-detail-label" for="mk_l1">Level 1 (VIP)</label>
						<div class="mk-hd-rule-detail-level-row">
							<input id="mk_l1" type="number" name="level_1_time_minutes" min="1"
								class="mk-hd-rule-detail-input mk-hd-rule-detail-input--narrow"
								value="{$RULE.level_1_time_minutes}" />
							<span class="mk-hd-rule-detail-help">Minutes – e.g. 15 for VIP first response.</span>
						</div>
					</div>

					<div class="mk-hd-rule-detail-field mk-hd-rule-detail-field--level">
						<label class="mk-hd-rule-detail-label" for="mk_l2">Level 2 (Standard)</label>
						<div class="mk-hd-rule-detail-level-row">
							<input id="mk_l2" type="number" name="level_2_time_minutes" min="1"
								class="mk-hd-rule-detail-input mk-hd-rule-detail-input--narrow"
								value="{$RULE.level_2_time_minutes}" />
							<span class="mk-hd-rule-detail-help">Minutes – e.g. 240 for standard response.</span>
						</div>
					</div>

					<div class="mk-hd-rule-detail-field mk-hd-rule-detail-field--level">
						<label class="mk-hd-rule-detail-label" for="mk_l3">Level 3 (Basic)</label>
						<div class="mk-hd-rule-detail-level-row">
							<input id="mk_l3" type="number" name="level_3_time_minutes" min="1"
								class="mk-hd-rule-detail-input mk-hd-rule-detail-input--narrow"
								value="{$RULE.level_3_time_minutes}" />
							<span class="mk-hd-rule-detail-help">Minutes – e.g. 480 for basic support.</span>
						</div>
					</div>

					<div class="mk-hd-rule-detail-actions">
						<button type="submit" class="mk-hd-rule-detail-btn mk-hd-rule-detail-btn--primary">Save Rule</button>
						<a href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT" class="mk-hd-rule-detail-btn mk-hd-rule-detail-btn--secondary">Cancel</a>
					</div>
				</form>
			</div>
		</div>

		<aside class="mk-hd-rule-detail-guide" aria-labelledby="mk-hd-rule-detail-guide-title">
			<h2 id="mk-hd-rule-detail-guide-title" class="mk-hd-rule-detail-guide-title">Support Levels Guide</h2>
			<div class="mk-hd-rule-detail-guide-cards">
				<div class="mk-hd-rule-detail-guide-card">
					<h3 class="mk-hd-rule-detail-guide-card-title">Level 1 – VIP</h3>
					<p class="mk-hd-rule-detail-guide-card-text">Fastest response and follow-up.</p>
				</div>
				<div class="mk-hd-rule-detail-guide-card">
					<h3 class="mk-hd-rule-detail-guide-card-title">Level 2 – Standard</h3>
					<p class="mk-hd-rule-detail-guide-card-text">Normal SLA for most customers.</p>
				</div>
				<div class="mk-hd-rule-detail-guide-card">
					<h3 class="mk-hd-rule-detail-guide-card-title">Level 3 – Basic</h3>
					<p class="mk-hd-rule-detail-guide-card-text">Relaxed SLA for low-priority accounts.</p>
				</div>
			</div>
		</aside>
	</div>
</div>
{/strip}
