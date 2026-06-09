{* Related-tab module icons — Organizations Detail (Sales + Marketing) *}
{assign var=MK_TAB_MOD value=$MODULE|default:''}
{if $MK_TAB_MOD eq 'Contacts'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="2.5" stroke="currentColor" stroke-width="1.6"/><circle cx="15" cy="8" r="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M5 19c0-2.2 1.8-4 4-4s4 1.8 4 4M11 19c0-2.2 1.8-4 4-4s4 1.8 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Potentials'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5l1.8 5.5H19l-4.6 3.4 1.8 5.5L12 15.8 7.8 16.9l1.8-5.5L5 8h5.2L12 2.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 15.8V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M9.5 19h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'SalesOrder'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="20" r="1.25" fill="currentColor"/><circle cx="18" cy="20" r="1.25" fill="currentColor"/><path d="M3 4h2l2.2 11.2a1 1 0 0 0 1 .8h9.3a1 1 0 0 0 .95-.7L20 8H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'Documents'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7a2 2 0 0 1 2-2h5l3 3h6a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'HelpDesk'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="1.6"/><path d="M9 12h6M12 9v6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 16l1.5-2M16 16l-1.5-2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Products'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l7 3.5v5c0 4.2-3 7.8-7 9-4-1.2-7-4.8-7-9v-5L12 3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9.5 12.5l2 2 3.5-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'ModComments'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3 3V9a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'Calendar' or $MK_TAB_MOD eq 'Events'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Quotes' or $MK_TAB_MOD eq 'Invoice'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v16H7V4z" stroke="currentColor" stroke-width="1.6"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Emails'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'ServiceContracts'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 4h12v16H6V4z" stroke="currentColor" stroke-width="1.6"/><path d="M9 8h6M9 12h5M9 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M14 4v3h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'ProductsServices'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9l8-4 8 4v6l-8 4-8-4V9z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 13v6M4 9l8 4 8-4" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $MK_TAB_MOD eq 'Campaigns'}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 11V6l12-3v11" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 14a3 3 0 1 0 6 0v-1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{else}
<svg class="mk-acc-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{/if}
