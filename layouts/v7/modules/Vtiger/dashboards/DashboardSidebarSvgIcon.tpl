{* Stroke SVG set aligned to dashboard Figma (currentColor). ICON: DASHBOARD|MARKETING|… *}
{if $ICON eq 'DASHBOARD'}
<svg class="mk-dash-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'MARKETING'}
<svg class="mk-dash-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 9v8l5-2.5V11.5L4 9zm5-.5L20 5v15l-11-3.5V8.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9 18.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" stroke="currentColor" stroke-width="1.5"/></svg>
{elseif $ICON eq 'SALES'}
<svg class="mk-dash-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 19h16M7 15l4-5 3 3 5-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'INVENTORY'}
<svg class="mk-dash-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 3 4 7v10l8 4 8-4V7l-8-4zM4 7l8 4 8-4M12 11v10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'SUPPORT'}
<svg class="mk-dash-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 21a8 8 0 0 0 8-8h-3a5 5 0 0 1-10 0H4a8 8 0 0 0 8 8zM5 13a7 7 0 0 1-.2-1.6A7 7 0 0 1 12 4v9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'MANAGEMENT'}
<svg class="mk-dash-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 3 4 7l8 4 8-4-8-4zm-8 6 8 4 8-4M4 17l8 4 8-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'TOOLS'}
<svg class="mk-dash-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="m14.7 6.3 1.4-1.4a2 2 0 0 1 2.8 2.8l-1.4 1.4M5 19l4.5-4.5M3 21l2.2-2.2m0 0L16 7.9a2.8 2.8 0 1 0-4-4L5.2 10.7 3 12.9 5.1 15l2.1-2.1z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'SETTINGS'}
<svg class="mk-dash-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" stroke="currentColor" stroke-width="1.5"/><path d="M19.4 15a1.7 1.7 0 0 0 .35 1.8l.05.05a2 2 0 1 1-2.8 2.8l-.06-.06a1.7 1.7 0 0 0-1.81-.33 1.7 1.7 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.84.33l-.06.06a2 2 0 1 1-2.8-2.8l.06-.06a1.7 1.7 0 0 0 .33-1.8 1.7 1.7 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.33-1.84l-.06-.06a2 2 0 1 1 2.8-2.8l.06.06a1.7 1.7 0 0 0 1.8.33H9a1.7 1.7 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.84-.33l.06-.06a2 2 0 1 1 2.8 2.8l-.06.06a1.7 1.7 0 0 0-.33 1.8V9c.26.31.48.67.48 1.05v.9c0 .38-.22.74-.48 1.05z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'CHEVRON'}
<svg class="mk-dash-svg mk-dash-svg--chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'MENU'}
<svg class="mk-dash-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
{/if}
