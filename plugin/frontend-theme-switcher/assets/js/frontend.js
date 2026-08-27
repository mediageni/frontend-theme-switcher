/**
 * Keeps theme menu scripts from intercepting the native switcher controls.
 *
 * @package ScriptGeni_Frontend_Theme_Switcher
 */

( function () {
	'use strict';

	function stopThemeMenuHandling( event ) {
		event.stopPropagation();
	}

	document.querySelectorAll( '.sgfts-switcher' ).forEach( function ( switcher ) {
		switcher.addEventListener( 'click', stopThemeMenuHandling );
		switcher.addEventListener( 'pointerdown', stopThemeMenuHandling );
		switcher.addEventListener( 'touchstart', stopThemeMenuHandling, { passive: true } );
	} );
}() );
