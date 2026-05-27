{* Related-tab module icons for Contact Detail SALES *}
{assign var=MK_TAB_MOD value=$MODULE|default:''}
{if $MK_TAB_MOD eq 'ModComments'}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3 3V9a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'Documents'}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8l4 4v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M16 4v4h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Calendar' or $MK_TAB_MOD eq 'Events'}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Potentials'}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l2.2 6.8H21l-5.5 4 2.1 6.7L12 16.4 6.4 20.5l2.1-6.7L3 9.8h6.8L12 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'HelpDesk'}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12v10H6V8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 8V6a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $MK_TAB_MOD eq 'Quotes' or $MK_TAB_MOD eq 'SalesOrder' or $MK_TAB_MOD eq 'Invoice'}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v16H7V4z" stroke="currentColor" stroke-width="1.6"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Products'}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9l8-4 8 4v6l-8 4-8-4V9z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 13v6M4 9l8 4 8-4" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $MK_TAB_MOD eq 'Emails'}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{else}
<svg class="mk-contact-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{/if}
