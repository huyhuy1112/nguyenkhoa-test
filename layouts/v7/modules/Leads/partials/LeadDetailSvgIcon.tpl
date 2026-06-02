{* Lead Detail icons — SALES detail scope *}
{if $ICON eq 'LEAD'}
<svg class="mk-lead-detail-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/><path d="M5 20c0-3.3 3.1-6 7-6s7 2.7 7 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $ICON eq 'FOLLOW'}
<svg class="mk-lead-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" fill="none"/></svg>
{elseif $ICON eq 'EDIT'}
<svg class="mk-lead-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.12 2.12 0 0 0-3-3L5 17v3z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M13.5 6.5l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'MORE'}
<svg class="mk-lead-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="6" cy="12" r="1.75" fill="currentColor"/><circle cx="12" cy="12" r="1.75" fill="currentColor"/><circle cx="18" cy="12" r="1.75" fill="currentColor"/></svg>
{elseif $ICON eq 'EMAIL'}
<svg class="mk-lead-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'CONVERT'}
<svg class="mk-lead-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M7 7h10v10H7V7z" stroke="currentColor" stroke-width="1.6"/><path d="M17 3h4v4M14 10l7-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $ICON eq 'PHONE'}
<svg class="mk-lead-detail-svg mk-lead-detail-svg--meta" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6.5 4h3l2 4-2.5 1.5a11 11 0 0 0 5 5L15 12l4 2v3a2 2 0 0 1-2 2A13 13 0 0 1 4 6.5a2 2 0 0 1 2-2.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'EMAIL_META'}
<svg class="mk-lead-detail-svg mk-lead-detail-svg--meta" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 6h16v10H4V6z" stroke="currentColor" stroke-width="1.5"/><path d="m4 7 8 5 8-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'SOURCE'}
<svg class="mk-lead-detail-svg mk-lead-detail-svg--meta" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'EMPTY_ACTIVITY'}
<svg class="mk-lead-detail-svg mk-lead-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'EMPTY_DOCUMENT'}
<svg class="mk-lead-detail-svg mk-lead-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 4h8l4 4v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M16 4v4h4M10 13h4M10 17h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'EMPTY_COMMENT'}
<svg class="mk-lead-detail-svg mk-lead-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3 3V9a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{/if}
