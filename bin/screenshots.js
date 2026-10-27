// Full-page screenshots of the key pages, desktop and mobile.
// Usage: node bin/screenshots.js [baseUrl] [outDir]   (site must be running: bin/serve.sh)
const { chromium } = require( 'playwright' );
const path = require( 'path' );

const base = process.argv[ 2 ] || 'http://localhost:8080';
const out = process.argv[ 3 ] || path.join( __dirname, '..', 'screenshots' );
const pages = {
	home: '/',
	services: '/services/',
	'service-group': '/services/steam-hydronics/',
	'service-maintenance': '/services/service-maintenance/',
	'service-single': '/services/process-pure-water/reverse-osmosis/',
	projects: '/projects/',
	project: '/projects/weber-state-chiller-plant/',
	about: '/about/',
	careers: '/careers/',
	contact: '/contact/',
};
const viewports = {
	desktop: { width: 1440, height: 900 },
	mobile: { width: 390, height: 844 },
};

( async () => {
	const browser = await chromium.launch();
	require( 'fs' ).mkdirSync( out, { recursive: true } );
	for ( const [ device, viewport ] of Object.entries( viewports ) ) {
		const context = await browser.newContext( { viewport, deviceScaleFactor: 1 } );
		const page = await context.newPage();
		for ( const [ name, url ] of Object.entries( pages ) ) {
			await page.goto( base + url, { waitUntil: 'networkidle' } );
			// Scroll like a visitor so lazy images load, then pin the sticky header to the
			// top so it doesn't appear mid-page in the full-page capture.
			const height = await page.evaluate( () => document.body.scrollHeight );
			for ( let y = 0; y < height; y += 400 ) {
				await page.mouse.wheel( 0, 400 );
				await page.waitForTimeout( 100 );
			}
			await page.waitForLoadState( 'networkidle' );
			await page.evaluate( () => window.scrollTo( 0, 0 ) );
			await page.addStyleTag( { content: '.site-header { position: relative !important; }' } );
			await page.waitForTimeout( 500 );
			await page.screenshot( { path: path.join( out, `${ device }-${ name }.png` ), fullPage: true } );
			console.log( `${ device }-${ name }.png` );
		}
		await context.close();
	}
	await browser.close();
} )();
