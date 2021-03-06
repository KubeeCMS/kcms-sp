'use strict';

( function ( $ ) {
	$( '#fspGetAccessToken' ).on( 'click', function () {
		let appID = $( '#fspModalAppSelector' ).val().trim();

		window.open( `https://oauth.vk.com/authorize?client_id=${ appID }&redirect_uri=https://oauth.vk.com/blank.html&display=page&scope=offline,wall,groups,email,photos,video&response_type=token&v=5.69`, '', 'width=750, height=550' );
	} );

	$( '.fsp-modal-footer > #fspModalAddButton' ).on( 'click', function () {
		let _this = $( this );
		let accessToken = $( '#fspAccessToken' ).val().trim();
		let appID = $( '#fspModalAppSelector' ).val().trim();
		let proxy = $( '#fspProxy' ).val().trim();

		FSPoster.ajax( 'add_vk_account', { 'at': accessToken, 'app': appID, proxy }, function () {
			accountAdded();
		} );
	} );
} )( jQuery );


