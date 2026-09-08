{* Opportunity Detail action / hero icons — scope: Potentials Detail SALES only *}
{if $ICON eq 'OPPORTUNITY'}
<svg class="mk-opportunity-detail-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="3" y="6" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M7 10h10M7 14h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="4" r="2" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $ICON eq 'FOLLOW'}
<svg class="mk-opportunity-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" fill="none"/></svg>
{elseif $ICON eq 'FOLLOWING'}
<svg class="mk-opportunity-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="currentColor"/></svg>
{elseif $ICON eq 'EDIT'}
<svg class="mk-opportunity-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.12 2.12 0 0 0-3-3L5 17v3z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M13.5 6.5l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'MORE'}
<svg class="mk-opportunity-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="6" cy="12" r="1.75" fill="currentColor"/><circle cx="12" cy="12" r="1.75" fill="currentColor"/><circle cx="18" cy="12" r="1.75" fill="currentColor"/></svg>
{elseif $ICON eq 'EMAIL'}
<svg class="mk-opportunity-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'PHONE'}
<svg class="mk-opportunity-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'CONVERT'}
<svg class="mk-opportunity-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 3h6v6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 14 21 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'BUILDING'}
<svg class="mk-opportunity-detail-svg mk-opportunity-detail-svg--meta" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12H4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 20v-5h6v5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'AMOUNT'}
<svg class="mk-opportunity-detail-svg mk-opportunity-detail-svg--meta" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v10M9.5 9.5c0-1.4 1.1-2.5 2.5-2.5s2.5 1.1 2.5 2.5c0 2.5-5 2.5-5 5h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'CALENDAR'}
<svg class="mk-opportunity-detail-svg mk-opportunity-detail-svg--meta" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $ICON eq 'LOCATION'}
<svg class="mk-opportunity-detail-svg mk-opportunity-detail-svg--meta" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.25" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $ICON eq 'USER'}
<svg class="mk-opportunity-detail-svg mk-opportunity-detail-svg--meta" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/><path d="M5.5 19.5c.8-3.2 3.4-5 6.5-5s5.7 1.8 6.5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $ICON eq 'EMPTY_ACTIVITY'}
<svg class="mk-opportunity-detail-svg mk-opportunity-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'EMPTY_DOCUMENT'}
<svg class="mk-opportunity-detail-svg mk-opportunity-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 4h8l4 4v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M16 4v4h4M10 13h4M10 17h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
{elseif $ICON eq 'EMPTY_COMMENT'}
<svg class="mk-opportunity-detail-svg mk-opportunity-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3 3V9a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{/if}
