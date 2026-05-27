{* Organizations Detail — hero, actions, card & empty-state icons (Sales + Marketing) *}
{if $ICON eq 'BUILDING'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--building" width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 21V9l8-5 8 5v12H4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9 21v-6h2v6M13 21v-4h2v4M9 12h2M13 10h2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'FOLLOW'}
<svg class="mk-acc-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'FOLLOWING'}
<svg class="mk-acc-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="currentColor"/></svg>
{elseif $ICON eq 'EDIT'}
<svg class="mk-acc-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.12 2.12 0 0 0-3-3L5 17v3z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M13.5 6.5l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'MORE'}
<svg class="mk-acc-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="6" cy="12" r="1.75" fill="currentColor"/><circle cx="12" cy="12" r="1.75" fill="currentColor"/><circle cx="18" cy="12" r="1.75" fill="currentColor"/></svg>
{elseif $ICON eq 'EMAIL'}
<svg class="mk-acc-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'MAP'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--map" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $ICON eq 'INFO'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--card" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 10v6M12 8h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $ICON eq 'CALENDAR'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--card" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $ICON eq 'FOLDER'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--card" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7a2 2 0 0 1 2-2h5l3 3h6a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'COMMENT'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--card" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3 3V9a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'EMPTY_ACTIVITY'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'EMPTY_DOCUMENT'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7a2 2 0 0 1 2-2h5l3 3h6a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'EMPTY_COMMENT'}
<svg class="mk-acc-detail-svg mk-acc-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3 3V9a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{/if}
