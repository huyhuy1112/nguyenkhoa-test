{* GoodsIssue / Outbound list icons (designer inventory + row actions) *}
{strip}
{if $ICON eq 'OUTBOUND'}
{* Inventory module icon — sidebar inventory.svg crop *}
<svg class="mk-gi-svg mk-gi-svg--hero" width="32" height="32" viewBox="24 156 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<path d="M27 176C26.45 176 25.9792 175.804 25.5875 175.413C25.1958 175.021 25 174.55 25 174V162.725C24.7 162.542 24.4583 162.304 24.275 162.012C24.0917 161.721 24 161.383 24 161V158C24 157.45 24.1958 156.979 24.5875 156.587C24.9792 156.196 25.45 156 26 156H42C42.55 156 43.0208 156.196 43.4125 156.587C43.8042 156.979 44 157.45 44 158V161C44 161.383 43.9083 161.721 43.725 162.012C43.5417 162.304 43.3 162.542 43 162.725V174C43 174.55 42.8042 175.021 42.4125 175.413C42.0208 175.804 41.55 176 41 176H27ZM27 163V174H41V163H27ZM26 161H42V158H26V161ZM31 168H37V166H31V168Z" fill="currentColor"/>
</svg>
{elseif $ICON eq 'OUTBOUND_SM'}
<svg class="mk-gi-svg mk-gi-svg--sm" width="24" height="24" viewBox="24 156 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<path d="M27 176C26.45 176 25.9792 175.804 25.5875 175.413C25.1958 175.021 25 174.55 25 174V162.725C24.7 162.542 24.4583 162.304 24.275 162.012C24.0917 161.721 24 161.383 24 161V158C24 157.45 24.1958 156.979 24.5875 156.587C24.9792 156.196 25.45 156 26 156H42C42.55 156 43.0208 156.196 43.4125 156.587C43.8042 156.979 44 157.45 44 158V161C44 161.383 43.9083 161.721 43.725 162.012C43.5417 162.304 43.3 162.542 43 162.725V174C43 174.55 42.8042 175.021 42.4125 175.413C42.0208 175.804 41.55 176 41 176H27ZM27 163V174H41V163H27ZM26 161H42V158H26V161ZM31 168H37V166H31V168Z" fill="currentColor"/>
</svg>
{elseif $ICON eq 'VIEW'}
<svg class="mk-gi-svg mk-gi-svg--action" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'EDIT'}
<svg class="mk-gi-svg mk-gi-svg--action" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.75"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'DELETE'}
<svg class="mk-gi-svg mk-gi-svg--action" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="1.75"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="1.75"/></svg>
{elseif $ICON eq 'FILTER'}
<svg class="mk-gi-svg mk-gi-svg--btn" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16l-6.5 7.5V19l-3 1.5v-7.5L4 5z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
{elseif $ICON eq 'RESET'}
<svg class="mk-gi-svg mk-gi-svg--btn" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{elseif $ICON eq 'PLUS'}
<svg class="mk-gi-svg mk-gi-svg--btn" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
{/if}
{/strip}
