{* Activities list icons *}
{strip}
{if $ICON eq 'ACTIVITY'}
<svg class="mk-hd-svg mk-hd-svg--hero" width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
	<path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
</svg>
{elseif $ICON eq 'PLUS'}
<svg class="mk-hd-svg mk-hd-svg--btn" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{elseif $ICON eq 'IMPORT'}
<svg class="mk-hd-svg mk-hd-svg--btn" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'CUSTOMIZE'}
<svg class="mk-hd-svg mk-hd-svg--btn" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.75"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.36 0 .7.07 1 .2H21a2 2 0 1 1 0 4h-.09c-.36 0-.7.07-1 .2Z" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'FILTER'}
<svg class="mk-hd-svg mk-hd-svg--btn" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16l-6.5 7.5V19l-3 1.5v-7.5L4 5z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'TICKETS'}
<svg class="mk-hd-svg mk-hd-svg--toolbar" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6.5C4 5.12 5.12 4 6.5 4h11C18.88 4 20 5.12 20 6.5v7c0 1.38-1.12 2.5-2.5 2.5H11l-4 3v-3H6.5C5.12 16 4 14.88 4 13.5v-7Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M8 9h8M8 12h5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{elseif $ICON eq 'RULES'}
<svg class="mk-hd-svg mk-hd-svg--toolbar" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.75"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.36 0 .7.07 1 .2H21a2 2 0 1 1 0 4h-.09c-.36 0-.7.07-1 .2Z" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'GRID'}
<svg class="mk-hd-svg mk-hd-svg--toolbar" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.75"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.75"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.75"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'LIST'}
<svg class="mk-hd-svg mk-hd-svg--toolbar" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{/if}
{/strip}
