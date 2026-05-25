{* HelpDesk Ticket detail icons *}
{strip}
{if $ICON eq 'TICKET'}
<svg class="mk-hd-detail-svg mk-hd-detail-svg--hero" width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<path d="M4 6.5C4 5.12 5.12 4 6.5 4h11C18.88 4 20 5.12 20 6.5v7c0 1.38-1.12 2.5-2.5 2.5H11l-4 3v-3H6.5C5.12 16 4 14.88 4 13.5v-7Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
	<path d="M8 9h8M8 12h5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
</svg>
{elseif $ICON eq 'PLUS'}
<svg class="mk-hd-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{elseif $ICON eq 'IMPORT'}
<svg class="mk-hd-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'CUSTOMIZE'}
<svg class="mk-hd-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.75"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.36 0 .7.07 1 .2H21a2 2 0 1 1 0 4h-.09c-.36 0-.7.07-1 .2Z" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'INFO'}
<svg class="mk-hd-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{elseif $ICON eq 'ACTIVITIES'}
<svg class="mk-hd-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="1.75"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'ASSIGN'}
<svg class="mk-hd-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.75"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.75"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'SLA'}
<svg class="mk-hd-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'STATUS'}
<svg class="mk-hd-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'USER'}
<svg class="mk-hd-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'ROCKET'}
<svg class="mk-hd-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z" stroke="currentColor" stroke-width="1.75"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z" stroke="currentColor" stroke-width="1.75"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'FAQ'}
<svg class="mk-hd-detail-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 2-3 4M12 17h.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'CHECK'}
<svg class="mk-hd-detail-svg mk-hd-detail-svg--empty" width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'CLOSE'}
<svg class="mk-hd-detail-svg mk-hd-detail-svg--close" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{/if}
{/strip}
