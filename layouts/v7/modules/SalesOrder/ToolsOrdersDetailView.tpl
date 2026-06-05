{* Tools internal order — premium detail cards *}
{strip}
<div class="mk-so-tools-detail-body">
	<div class="mk-so-tools-detail-grid">
		<section class="mk-so-tools-detail-card">
			<header class="mk-so-tools-detail-card__head">
				<span class="mk-so-tools-detail-card__icon mk-so-tools-detail-card__icon--order" aria-hidden="true"><i class="fa fa-file-text-o"></i></span>
				<h2 class="mk-so-tools-detail-card__title">Order Info</h2>
			</header>
			<div class="mk-so-tools-field-grid">
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Name</span>
					<span class="mk-so-tools-field__value">{$RECORD->getDisplayValue('subject')}</span>
				</div>
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Team Group</span>
					<span class="mk-so-tools-field__value">{$RECORD->getDisplayValue('team_group')}</span>
				</div>
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Purpose</span>
					<span class="mk-so-tools-field__value">{$RECORD->getDisplayValue('purpose')}</span>
				</div>
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Cost</span>
					<span class="mk-so-tools-field__value mk-so-tools-field__value--emphasis">{$RECORD->getDisplayValue('internal_cost')}</span>
				</div>
				<div class="mk-so-tools-field mk-so-tools-field--wide">
					<span class="mk-so-tools-field__label">Needed Time</span>
					<span class="mk-so-tools-field__value">{$RECORD->getDisplayValue('needed_time')}</span>
				</div>
			</div>
		</section>

		<section class="mk-so-tools-detail-card">
			<header class="mk-so-tools-detail-card__head">
				<span class="mk-so-tools-detail-card__icon mk-so-tools-detail-card__icon--approval" aria-hidden="true"><i class="fa fa-check-circle"></i></span>
				<h2 class="mk-so-tools-detail-card__title">Approval Info</h2>
			</header>
			<div class="mk-so-tools-field-grid">
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Status</span>
					<span class="mk-so-tools-field__value">
						<span class="mk-so-tools-status-pill mk-so-tools-status-pill--inline">{$RECORD->getDisplayValue('internal_order_status')}</span>
					</span>
				</div>
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Approved By</span>
					<span class="mk-so-tools-field__value mk-so-tools-field__value--link">{$RECORD->getDisplayValue('approved_by')}</span>
				</div>
				<div class="mk-so-tools-field mk-so-tools-field--full">
					<span class="mk-so-tools-field__label">Approval Note</span>
					<span class="mk-so-tools-field__value">{$RECORD->getDisplayValue('approval_note')}</span>
				</div>
			</div>
		</section>

		<section class="mk-so-tools-detail-card">
			<header class="mk-so-tools-detail-card__head">
				<span class="mk-so-tools-detail-card__icon mk-so-tools-detail-card__icon--system" aria-hidden="true"><i class="fa fa-clock-o"></i></span>
				<h2 class="mk-so-tools-detail-card__title">System</h2>
			</header>
			<div class="mk-so-tools-field-grid">
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Ordered By</span>
					<span class="mk-so-tools-field__value mk-so-tools-field__value--link">{$RECORD->getDisplayValue('created_user_id')}</span>
				</div>
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Created Time</span>
					<span class="mk-so-tools-field__value">{$RECORD->getDisplayValue('createdtime')}</span>
				</div>
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Modified By</span>
					<span class="mk-so-tools-field__value mk-so-tools-field__value--link">{$RECORD->getDisplayValue('modifiedby')}</span>
				</div>
				<div class="mk-so-tools-field">
					<span class="mk-so-tools-field__label">Modified Time</span>
					<span class="mk-so-tools-field__value">{$RECORD->getDisplayValue('modifiedtime')}</span>
				</div>
			</div>
		</section>
	</div>
</div>
{/strip}
