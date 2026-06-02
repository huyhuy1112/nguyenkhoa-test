{* Campaigns detail action / hero icons *}
{strip}
{if $ICON eq 'MEGAPHONE'}
{* Designer megaphone — same paths as sidebar MARKETING (resources/sidebar-icons/marketing.svg) *}
<svg class="mk-camp-detail-svg mk-camp-detail-svg--designer" width="32" height="32" viewBox="24 62 20 17" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<path d="M40 71V69H44V71H40ZM41.2 78L38 75.6L39.2 74L42.4 76.4L41.2 78ZM39.2 66L38 64.4L41.2 62L42.4 63.6L39.2 66ZM27 77V73H26C25.45 73 24.9792 72.8042 24.5875 72.4125C24.1958 72.0208 24 71.55 24 71V69C24 68.45 24.1958 67.9792 24.5875 67.5875C24.9792 67.1958 25.45 67 26 67H30L35 64V76L30 73H29V77H27ZM33 72.45V67.55L30.55 69H26V71H30.55L33 72.45ZM36 73.35V66.65C36.45 67.05 36.8125 67.5375 37.0875 68.1125C37.3625 68.6875 37.5 69.3167 37.5 70C37.5 70.6833 37.3625 71.3125 37.0875 71.8875C36.8125 72.4625 36.45 72.95 36 73.35Z" fill="currentColor"/>
</svg>
{elseif $ICON eq 'FOLLOW'}
<svg class="mk-camp-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
</svg>
{elseif $ICON eq 'DUPLICATE'}
<svg class="mk-camp-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.75"/>
	<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.75"/>
</svg>
{elseif $ICON eq 'DELETE'}
<svg class="mk-camp-detail-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
	<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
</svg>
{/if}
{/strip}
