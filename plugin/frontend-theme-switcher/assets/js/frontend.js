/**
 * Keeps theme menu scripts from intercepting the native switcher controls.
 *
 * @package MediaGeni_Frontend_Theme_Switcher
 */

( function () {
	'use strict';

	function stopThemeMenuHandling( event ) {
		event.stopPropagation();
	}

	document.querySelectorAll( '.sgfts-switcher__summary, .sgfts-switcher__link' ).forEach( function ( control ) {
		control.addEventListener( 'click', stopThemeMenuHandling );
		control.addEventListener( 'pointerdown', stopThemeMenuHandling );
		control.addEventListener( 'touchstart', stopThemeMenuHandling, { passive: true } );
	} );

}() );
