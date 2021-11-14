'use strict';

( function ( $ ) {
	$( '.fsp-modal-footer > #fspModalAddButton' ).on( 'click', function () {
		let _this = $( this );
		let selectedMethod = String( $( '.fsp-modal-option.fsp-is-selected' ).data( 'step' ) );

		if ( selectedMethod === '1' ) // app method
		{
			let appID = $( '#fspModalStep_1 #fspModalAppSelector' ).val().trim();
			let proxy = $( '#fspProxy' ).val().trim();
			let openURL = `${ fspConfig.siteURL }/?pinterest_app_redirect=${ appID }&proxy=${ proxy }`;

			if ( $( '#fspModalStep_1 #fspModalAppSelector > option:selected' ).data( 'is-standart' ).toString() === '1' )
			{
				openURL = `${ fspConfig.standartAppURL }&proxy=${ proxy }&encode=true`;
			}

			window.open( openURL, 'fs-standart-app', 'width=750, height=550' );
		}
		else if ( selectedMethod === '2' ) // cookie method
		{
			let cookie_sess = $( '#fspModalStep_2 #fspCookie_sess' ).val().trim();
			let proxy = $( '#fspProxy' ).val().trim();

			FSPoster.ajax( 'add_pinterest_account_cookie_method', { cookie_sess, proxy }, function () {
				accountAdded();
			} );
		}
	} );

	$( '.fsp-modal-footer > #fspModalUpdateCookiesButton' ).on('click', function (  ){
		let account_id = $( '#account_to_update' ).val().trim();
		let cookie_sess = $( '#fspCookie_sess' ).val().trim();
		let proxy = $( '#fspProxy' ).val().trim();

		FSPoster.ajax( 'update_pinterest_account_cookie', { account_id, cookie_sess, proxy }, function () {
			accountUpdated();
		} );
	});
} )( jQuery );