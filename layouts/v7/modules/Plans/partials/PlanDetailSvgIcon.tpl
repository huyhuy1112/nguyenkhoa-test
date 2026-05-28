{* Plans detail icons *}
{strip}
{if $ICON eq 'PLAN'}
<svg class="mk-plan-detail-svg" width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
	<rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="1.5"/>
	<path d="M9 12h6M9 16h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
</svg>
{elseif $ICON eq 'VIEW'}
<svg class="mk-plan-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'EDIT'}
<svg class="mk-plan-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.75"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'MORE'}
<svg class="mk-plan-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="5" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/></svg>
{elseif $ICON eq 'DUPLICATE'}
<svg class="mk-plan-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'DELETE'}
<svg class="mk-plan-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="1.75"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="1.75"/></svg>
{/if}
{/strip}
