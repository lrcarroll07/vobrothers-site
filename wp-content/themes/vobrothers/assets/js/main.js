/**
 * VO Brothers Mechanical: mobile menu, project carousel and list filters.
 * No dependencies.
 */
( function () {
	'use strict';

	// Mobile menu ------------------------------------------------------------
	const toggle = document.querySelector( '[data-nav-toggle]' );
	if ( toggle ) {
		const header = document.querySelector( '[data-header]' );
		toggle.addEventListener( 'click', () => {
			// Start the overlay right under the header, wherever the page is scrolled.
			document.documentElement.style.setProperty( '--nav-top', `${ header.getBoundingClientRect().bottom }px` );
			const open = document.body.classList.toggle( 'nav-open' );
			toggle.setAttribute( 'aria-expanded', String( open ) );
		} );
		document.addEventListener( 'keydown', ( e ) => {
			if ( 'Escape' === e.key && document.body.classList.contains( 'nav-open' ) ) {
				document.body.classList.remove( 'nav-open' );
				toggle.setAttribute( 'aria-expanded', 'false' );
				toggle.focus();
			}
		} );
	}

	document.querySelectorAll( '.submenu-toggle' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', () => {
			const submenu = btn.parentElement.querySelector( '.sub-menu' );
			const open = submenu.classList.toggle( 'is-open' );
			btn.setAttribute( 'aria-expanded', String( open ) );
		} );
	} );

	// Carousel ---------------------------------------------------------------
	document.querySelectorAll( '[data-carousel]' ).forEach( ( track ) => {
		const section = track.closest( 'section' );
		const step = () => {
			const card = track.firstElementChild;
			return card ? card.getBoundingClientRect().width + parseFloat( getComputedStyle( track ).columnGap || 0 ) : track.clientWidth;
		};
		const move = ( dir ) => {
			const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
			const atStart = track.scrollLeft <= 4;
			if ( dir > 0 && atEnd ) {
				track.scrollTo( { left: 0 } );
			} else if ( dir < 0 && atStart ) {
				track.scrollTo( { left: track.scrollWidth } );
			} else {
				track.scrollBy( { left: dir * step() } );
			}
		};
		section.querySelector( '[data-carousel-prev]' )?.addEventListener( 'click', () => move( -1 ) );
		section.querySelector( '[data-carousel-next]' )?.addEventListener( 'click', () => move( 1 ) );
		track.addEventListener( 'keydown', ( e ) => {
			if ( 'ArrowRight' === e.key ) { e.preventDefault(); move( 1 ); }
			if ( 'ArrowLeft' === e.key ) { e.preventDefault(); move( -1 ); }
		} );
	} );

	// Filters (projects by type + area, jobs by department) ------------------
	document.querySelectorAll( '[data-vob-filter]' ).forEach( ( root ) => {
		const chips = root.querySelectorAll( '[data-filter]' );
		const select = root.querySelector( '[data-filter-location]' );
		const items = root.querySelectorAll( '[data-groups]' );
		const count = root.querySelector( '[data-filter-count]' );
		const empty = root.querySelector( '[data-filter-empty]' );
		const active = root.querySelector( '[data-filter].is-active' );
		let group = active ? active.dataset.filter : '*';

		const apply = () => {
			const area = select ? select.value : '*';
			let shown = 0;
			items.forEach( ( item ) => {
				const groups = ( item.dataset.groups || '' ).split( ' ' );
				const areas = ( item.dataset.location || '' ).split( ' ' );
				const match = ( '*' === group || groups.includes( group ) ) && ( '*' === area || areas.includes( area ) );
				item.hidden = ! match;
				shown += match ? 1 : 0;
			} );
			if ( count ) {
				count.textContent = `Showing ${ shown } of ${ items.length } projects`;
			}
			if ( empty ) {
				empty.hidden = shown > 0;
			}
			root.dispatchEvent( new CustomEvent( 'vob:filter', { detail: { group, area } } ) );
		};

		chips.forEach( ( chip ) => {
			chip.addEventListener( 'click', () => {
				group = chip.dataset.filter;
				chips.forEach( ( c ) => {
					c.classList.toggle( 'is-active', c === chip );
					c.setAttribute( 'aria-pressed', String( c === chip ) );
				} );
				apply();
			} );
		} );
		select?.addEventListener( 'change', apply );
		root.vobApplyFilter = apply;
		apply();
	} );
}() );
