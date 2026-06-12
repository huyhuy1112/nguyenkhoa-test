{* Related-tab module icons for Opportunity Detail SALES *}
{assign var=MK_TAB_MOD value=$MODULE|default:''}
{if $MK_TAB_MOD eq 'ModComments'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3 3V9a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'Documents'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8l4 4v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M16 4v4h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Calendar' or $MK_TAB_MOD eq 'Events'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Contacts'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M4 20c0-3.3 2.7-6 6-6h2c3.3 0 6 2.7 6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="17" cy="9" r="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M14 20c.4-2.2 1.8-4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Potentials'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l2.2 6.8H21l-5.5 4 2.1 6.7L12 16.4 6.4 20.5l2.1-6.7L3 9.8h6.8L12 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'HelpDesk'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12v10H6V8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 8V6a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $MK_TAB_MOD eq 'Quotes' or $MK_TAB_MOD eq 'SalesOrder' or $MK_TAB_MOD eq 'Invoice'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v16H7V4z" stroke="currentColor" stroke-width="1.6"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Products' or $MK_TAB_MOD eq 'ProductsServices'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9l8-4 8 4v6l-8 4-8-4V9z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 13v6M4 9l8 4 8-4" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $MK_TAB_MOD eq 'PurchaseHistory'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L6 6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.5" fill="currentColor"/><circle cx="17" cy="20" r="1.5" fill="currentColor"/></svg>
{elseif $MK_TAB_MOD eq 'OrdersMonth'}
<svg class="mk-lead-tab-svg mk-lead-tab-svg--orders-month" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h9l3 3v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M15 3v3h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 10h6M8 13h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="17.5" cy="8.5" r="4.2" fill="currentColor" fill-opacity="0.12" stroke="currentColor" stroke-width="1.3"/><path d="M17.5 6.8V8.5l1.3 1.1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'ProductsPurchased'}
<svg class="mk-lead-tab-svg mk-lead-tab-svg--products-purchased" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 15.5 12 11l8.5 4.5L12 20 3.5 15.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M3.5 11.5 12 7l8.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 7V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="18.5" cy="5.5" r="1.1" fill="currentColor"/><circle cx="20.5" cy="7.8" r="1.1" fill="currentColor"/><circle cx="16.8" cy="7.8" r="1.1" fill="currentColor"/><circle cx="18.5" cy="10.1" r="1.1" fill="currentColor"/></svg>
{elseif $MK_TAB_MOD eq 'ActivityLog'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'PhoneCalls'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.5 4h3l2 4-2.5 1.5a11 11 0 0 0 5 5L15 12l4 2v3a2 2 0 0 1-2 2A13 13 0 0 1 4 6.5a2 2 0 0 1 2-2.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'Emails'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Project' or $MK_TAB_MOD eq 'ProjectTask'}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v12H4V7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="1.6"/></svg>
{else}
<svg class="mk-lead-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{/if}
