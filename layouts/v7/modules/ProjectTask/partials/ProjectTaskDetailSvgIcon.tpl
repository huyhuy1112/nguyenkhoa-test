{* ProjectTask Detail — MANAGEMENT toolbar icons *}
{if $ICON eq 'TASK'}
<svg class="mk-projecttask-detail-svg mk-projecttask-detail-svg--task" width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'FOLLOW'}
<svg class="mk-projecttask-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'EDIT'}
<svg class="mk-projecttask-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.12 2.12 0 0 0-3-3L5 17v3z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M13.5 6.5l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'DELETE'}
<svg class="mk-projecttask-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M10 11v6M14 11v6M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'DETAIL'}
<svg class="mk-projecttask-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'MORE'}
<svg class="mk-projecttask-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="6" cy="12" r="1.75" fill="currentColor"/><circle cx="12" cy="12" r="1.75" fill="currentColor"/><circle cx="18" cy="12" r="1.75" fill="currentColor"/></svg>
{/if}
