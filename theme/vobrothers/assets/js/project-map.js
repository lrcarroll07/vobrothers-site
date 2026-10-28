/**
 * Projects map (Leaflet). Reads markers from the JSON next to the map, follows the
 * project filters (type chips + area select), and groups markers that would overlap
 * into a numbered bubble that zooms in when clicked.
 */
( function () {
	'use strict';

	const el = document.querySelector( '[data-project-map]' );
	const dataEl = document.querySelector( '[data-project-map-data]' );
	if ( ! el || ! dataEl || ! window.L ) {
		return;
	}

	const projects = JSON.parse( dataEl.textContent );
	const root = el.closest( '[data-vob-filter]' );
	const CLUSTER_PX = 42;

	const MAX_ZOOM = 13;
	const map = L.map( el, { scrollWheelZoom: false, zoomControl: true, attributionControl: true, minZoom: 6, maxZoom: MAX_ZOOM } );

	// USGS National Map shaded relief: public domain, no API key or usage fees.
	// Styled dark in CSS (.map-tiles) to match the site.
	L.tileLayer( 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSShadedReliefOnly/MapServer/tile/{z}/{y}/{x}', {
		maxZoom: MAX_ZOOM,
		className: 'map-tiles',
		attribution: 'Relief: <a href="https://www.usgs.gov/programs/national-geospatial-program/national-map">USGS The National Map</a>',
	} ).addTo( map );

	// The relief has no place names, so label the cities our projects are in.
	[ [ 'Brigham City', 41.510, -112.015 ], [ 'Kaysville', 41.035, -111.938 ], [ 'Logan', 41.737, -111.834 ], [ 'Ogden', 41.223, -111.973 ], [ 'Salt Lake City', 40.76, -111.891 ], [ 'Provo', 40.234, -111.659 ], [ 'Park City', 40.646, -111.498 ] ]
		.forEach( ( [ name, lat, lng ] ) => L.marker( [ lat, lng ], {
			icon: L.divIcon( { className: 'map-label', html: name, iconSize: null } ),
			interactive: false,
			keyboard: false,
			zIndexOffset: -1000,
		} ).addTo( map ) );

	// Only scroll-zoom once the visitor has clicked into the map, so the page scrolls normally.
	map.on( 'click', () => map.scrollWheelZoom.enable() );
	map.on( 'mouseout', () => map.scrollWheelZoom.disable() );

	const layer = L.layerGroup().addTo( map );
	let visible = projects;

	// Hexagon pins, matching the hexagon motif used across the site.
	const HEX = '<svg viewBox="0 0 100 115.47" aria-hidden="true"><polygon points="50,4 96,30.5 96,85 50,111.5 4,85 4,30.5" /></svg>';
	const pin = ( count ) => L.divIcon( {
		className: '',
		html: count > 1 ? `<span class="map-pin map-pin--cluster">${ HEX }<b>${ count }</b></span>` : `<span class="map-pin">${ HEX }</span>`,
		iconSize: count > 1 ? [ 42, 48 ] : [ 24, 28 ],
		iconAnchor: count > 1 ? [ 21, 24 ] : [ 12, 14 ],
		popupAnchor: [ 0, -14 ],
	} );

	// Popup built from DOM nodes so project text is never parsed as HTML.
	const popup = ( p ) => {
		const box = document.createElement( 'a' );
		box.className = 'map-popup';
		box.href = p.url;
		if ( p.image ) {
			const img = document.createElement( 'img' );
			img.src = p.image;
			img.alt = '';
			box.append( img );
		}
		const body = document.createElement( 'span' );
		body.className = 'map-popup__body';
		[ [ 'map-popup__sector', p.sector ], [ 'map-popup__title', p.title ], [ 'map-popup__meta', [ p.location, p.size ].filter( Boolean ).join( ' · ' ) ], [ 'map-popup__more', 'View project →' ] ]
			.forEach( ( [ cls, text ] ) => {
				if ( text ) {
					const s = document.createElement( 'span' );
					s.className = cls;
					s.textContent = text;
					body.append( s );
				}
			} );
		box.append( body );
		return box;
	};

	// Greedy pixel clustering at the current zoom.
	const render = () => {
		layer.clearLayers();
		const groups = [];
		visible.forEach( ( p ) => {
			const pt = map.latLngToLayerPoint( [ p.lat, p.lng ] );
			const near = groups.find( ( g ) => g.pt.distanceTo( pt ) < CLUSTER_PX );
			if ( near ) {
				near.items.push( p );
			} else {
				groups.push( { pt, items: [ p ] } );
			}
		} );
		groups.forEach( ( g ) => {
			if ( 1 === g.items.length ) {
				const p = g.items[ 0 ];
				L.marker( [ p.lat, p.lng ], { icon: pin( 1 ), title: p.title, keyboard: true } )
					.bindPopup( popup( p ), { maxWidth: 250, minWidth: 250 } )
					.addTo( layer );
				return;
			}
			const bounds = L.latLngBounds( g.items.map( ( p ) => [ p.lat, p.lng ] ) );
			L.marker( bounds.getCenter(), { icon: pin( g.items.length ), title: `${ g.items.length } projects`, keyboard: true } )
				.on( 'click', () => {
					// Same spot at any zoom (e.g. one campus): list them instead of zooming forever.
					if ( map.getZoom() >= MAX_ZOOM || bounds.getNorthEast().equals( bounds.getSouthWest(), 0.0005 ) ) {
						const list = document.createElement( 'div' );
						list.className = 'map-popup-list';
						g.items.forEach( ( p ) => list.append( popup( p ) ) );
						L.popup( { maxWidth: 260, minWidth: 260, maxHeight: 340 } ).setLatLng( bounds.getCenter() ).setContent( list ).openOn( map );
					} else {
						map.fitBounds( bounds.pad( 0.6 ), { maxZoom: MAX_ZOOM } );
					}
				} )
				.addTo( layer );
		} );
	};

	const fit = () => {
		if ( ! visible.length ) {
			return;
		}
		const bounds = L.latLngBounds( visible.map( ( p ) => [ p.lat, p.lng ] ) );
		map.fitBounds( bounds.pad( 0.25 ), { maxZoom: 12, animate: true } );
	};

	// City labels crowd the pins at statewide zoom; show them once zoomed in.
	const toggleLabels = () => el.classList.toggle( 'map--labels', map.getZoom() >= 9 );
	map.on( 'zoomend', render );
	map.on( 'zoomend', toggleLabels );

	const update = ( { group, area } ) => {
		visible = projects.filter( ( p ) => ( '*' === group || p.groups.includes( group ) ) && ( '*' === area || p.areas.includes( area ) ) );
		map.closePopup();
		fit();
		render();
	};

	root?.addEventListener( 'vob:filter', ( e ) => update( e.detail ) );

	// Start on all projects, then sync with any filter already selected on the page.
	map.fitBounds( L.latLngBounds( projects.map( ( p ) => [ p.lat, p.lng ] ) ).pad( 0.25 ) );
	render();
	root?.vobApplyFilter?.();
}() );
