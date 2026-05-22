{* Lead Detail — tag-driven CRM (UI demo). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Leads&view=List&app=SALES'}
<div class="mk-ld-detail" id="mk-ld-detail">
	<header class="mk-ld-detail__top">
		<nav class="mk-ld-detail__crumb" aria-label="Breadcrumb">
			<a href="{$MK_LIST_URL}">Leads</a>
			<span class="mk-ld-detail__crumb-sep">/</span>
			<span id="mk-ld-crumb-id">—</span>
		</nav>
		<div class="mk-ld-detail__head-row">
			<div class="mk-ld-detail__identity">
				<h1 class="mk-ld-detail__title" id="mk-ld-name">—</h1>
				<p class="mk-ld-detail__phone" id="mk-ld-phone">—</p>
			</div>
			<div class="mk-ld-detail__head-actions">
				<button type="button" class="mk-ld-btn mk-ld-btn--outline" id="mk-ld-btn-call">
					<span aria-hidden="true">📞</span> Call
				</button>
				<button type="button" class="mk-ld-btn mk-ld-btn--outline" id="mk-ld-btn-email">
					<span aria-hidden="true">✉</span> Email
				</button>
				<button type="button" class="mk-ld-btn mk-ld-btn--dark" id="mk-ld-btn-save">Save</button>
			</div>
		</div>
	</header>

	<div class="mk-ld-detail__layout">
		<div class="mk-ld-detail__main">
			<section class="mk-ld-card mk-ld-card--summary">
				<div class="mk-ld-summary">
					<div class="mk-ld-summary__left">
						<h2 class="mk-ld-summary__name" id="mk-ld-summary-name">—</h2>
						<p class="mk-ld-summary__phone" id="mk-ld-summary-phone">—</p>
						<div class="mk-ld-summary__pills" id="mk-ld-summary-pills"></div>
					</div>
					<div class="mk-ld-summary__deal">
						<span class="mk-ld-summary__deal-label">Deal value</span>
						<span class="mk-ld-summary__deal-value" id="mk-ld-deal-value">—</span>
					</div>
				</div>
			</section>

			<section class="mk-ld-card">
				<h2 class="mk-ld-card__title">Log activity</h2>
				<textarea id="mk-ld-log-input" class="mk-ld-textarea" rows="4" placeholder="Add note / log call / add task..."></textarea>
				<div class="mk-ld-log-actions">
					<div class="mk-ld-log-actions__left">
						<button type="button" class="mk-ld-btn mk-ld-btn--outline mk-ld-btn--sm" data-log="note">Add Note</button>
						<button type="button" class="mk-ld-btn mk-ld-btn--outline mk-ld-btn--sm" data-log="call">Log Call</button>
						<button type="button" class="mk-ld-btn mk-ld-btn--outline mk-ld-btn--sm" data-log="meeting">Log Meeting</button>
					</div>
					<button type="button" class="mk-ld-btn mk-ld-btn--dark mk-ld-btn--sm" id="mk-ld-btn-create-task">
						<span aria-hidden="true">☑</span> Create Task
					</button>
				</div>
			</section>

			<section class="mk-ld-card">
				<div class="mk-ld-card__head-row">
					<h2 class="mk-ld-card__title">Activity timeline</h2>
					<span class="mk-ld-card__meta" id="mk-ld-timeline-count">0 items</span>
				</div>
				<div class="mk-ld-timeline" id="mk-ld-timeline"></div>
			</section>
		</div>

		<aside class="mk-ld-detail__aside">
			<section class="mk-ld-card">
				<h2 class="mk-ld-card__title">Lead info</h2>
				<div class="mk-ld-field">
					<label class="mk-ld-label" for="mk-ld-owner">Owner</label>
					<select id="mk-ld-owner" class="mk-ld-select">
						<option value="Hà">Hà</option>
						<option value="Linh">Linh</option>
						<option value="Minh">Minh</option>
					</select>
				</div>
				<div class="mk-ld-field">
					<label class="mk-ld-label" for="mk-ld-value">Value (VND)</label>
					<input type="text" id="mk-ld-value" class="mk-ld-input" inputmode="numeric" />
				</div>
				<div class="mk-ld-field">
					<label class="mk-ld-label">Last touch</label>
					<p class="mk-ld-readonly" id="mk-ld-last-touch">—</p>
				</div>
				<div class="mk-ld-field">
					<label class="mk-ld-label" for="mk-ld-next-action">Next action</label>
					<input type="text" id="mk-ld-next-action" class="mk-ld-input" />
				</div>
			</section>

			<section class="mk-ld-card">
				<h2 class="mk-ld-card__title">Tags</h2>
				<p class="mk-ld-card__desc">Tags drive Type, Stage, Tier &amp; Priority.</p>
				<div class="mk-ld-tags" id="mk-ld-tags"></div>
				<div class="mk-ld-tag-add">
					<input type="text" id="mk-ld-tag-input" class="mk-ld-input" placeholder="Add new tag (e.g. vang)" />
					<button type="button" class="mk-ld-btn mk-ld-btn--outline mk-ld-btn--sm" id="mk-ld-tag-add">+ Add</button>
				</div>
			</section>

			<section class="mk-ld-card">
				<h2 class="mk-ld-card__title">Quick insights</h2>
				<div class="mk-ld-insights">
					<div class="mk-ld-insight-row">
						<span class="mk-ld-insight-label">Type</span>
						<span class="mk-ld-insight-val" id="mk-ld-insight-type"></span>
					</div>
					<div class="mk-ld-insight-row">
						<span class="mk-ld-insight-label">Stage</span>
						<span class="mk-ld-insight-val" id="mk-ld-insight-stage"></span>
					</div>
					<div class="mk-ld-insight-row">
						<span class="mk-ld-insight-label">Tier</span>
						<span class="mk-ld-insight-val" id="mk-ld-insight-tier"></span>
					</div>
				</div>
			</section>
		</aside>
	</div>
</div>
{/strip}
