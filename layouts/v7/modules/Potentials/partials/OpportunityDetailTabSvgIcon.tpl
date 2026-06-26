{* Related-tab module icons for Opportunity Detail SALES *}
{assign var=MK_TAB_MOD value=$MODULE|default:''}
{if $MK_TAB_MOD eq 'ModComments'}
<svg class="mk-opportunity-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3 3V9a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'ServiceContracts'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--service-contract" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4.5h10a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 18V6A1.5 1.5 0 0 1 7 4.5z" stroke="currentColor" stroke-width="1.6"/><path d="M8.5 9h7M8.5 12h7M8.5 15h4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M15.5 15.5l1.5 1.5 3-3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'Documents'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--documents" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8l4 4v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M16 4v4h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Calendar' or $MK_TAB_MOD eq 'Events'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--calendar" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Contacts'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--contacts" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M4 20c0-3.3 2.7-6 6-6h2c3.3 0 6 2.7 6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="17" cy="9" r="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M14 20c.4-2.2 1.8-4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Potentials'}
<svg class="mk-opportunity-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l2.2 6.8H21l-5.5 4 2.1 6.7L12 16.4 6.4 20.5l2.1-6.7L3 9.8h6.8L12 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
{elseif $MK_TAB_MOD eq 'HelpDesk'}
<svg class="mk-opportunity-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12v10H6V8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 8V6a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $MK_TAB_MOD eq 'Quotes'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--quotes" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8l3 3v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M16 4v3h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M9 12h4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.5 9.5c0-1 .8-1.5 1.5-1s1.5.5 1.5 1.5-.8 1.5-1.5 1.5H9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'SalesOrder'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--sales-order" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6h14l-1.4 8.5H7.4L6 6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 20a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5zM16 20a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5z" stroke="currentColor" stroke-width="1.6"/><path d="M6 6L5 3H3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Invoice'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--invoice" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v16H7V4z" stroke="currentColor" stroke-width="1.6"/><path d="M9 8h6M9 11h6M9 14h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="16" cy="17" r="2.5" stroke="currentColor" stroke-width="1.4"/><path d="M15.2 17l.55.55 1.25-1.25" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Products' or $MK_TAB_MOD eq 'ProductsServices'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--products" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9l8-4 8 4v6l-8 4-8-4V9z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 13v6M4 9l8 4 8-4" stroke="currentColor" stroke-width="1.6"/></svg>
{elseif $MK_TAB_MOD eq 'Emails'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--emails" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{elseif $MK_TAB_MOD eq 'Project' or $MK_TAB_MOD eq 'ProjectTask'}
<svg class="mk-opportunity-tab-svg mk-opportunity-tab-svg--project" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v12H4V7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="1.6"/></svg>
{else}
<svg class="mk-opportunity-tab-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
{/if}
