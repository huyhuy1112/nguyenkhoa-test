{* Activities detail icons (aligned with design mockup) *}
{strip}
{if $ICON eq 'MEGAPHONE'}
<svg class="mk-act-detail-svg mk-act-detail-svg--hero" width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<path d="M4.5 14.5V9.5C4.5 8.12 5.62 7 7 7h5.5L16 4v10.5L11 16.5H7C5.62 16.5 4.5 15.38 4.5 14Z" fill="currentColor"/>
	<path d="M9 11.5V14.5H7.5V11.5H9Z" fill="currentColor"/>
</svg>
{elseif $ICON eq 'PLUS'}
<svg class="mk-act-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{elseif $ICON eq 'IMPORT'}
<svg class="mk-act-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'CUSTOMIZE'}
<svg class="mk-act-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.75"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.36 0 .7.07 1 .2H21a2 2 0 1 1 0 4h-.09c-.36 0-.7.07-1 .2Z" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'STAR'}
<svg class="mk-act-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'EDIT'}
<svg class="mk-act-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'MORE'}
<svg class="mk-act-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6M12 9l6 6 6-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'INFO'}
<svg class="mk-act-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{elseif $ICON eq 'CALENDAR'}
<svg class="mk-act-detail-svg mk-act-detail-svg--sm" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'LOG_CREATED'}
<svg class="mk-act-detail-svg mk-act-detail-svg--log" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 8v8M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{elseif $ICON eq 'LOG_MODIFIED'}
<svg class="mk-act-detail-svg mk-act-detail-svg--log" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" stroke="currentColor" stroke-width="1.75"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{/if}
{/strip}