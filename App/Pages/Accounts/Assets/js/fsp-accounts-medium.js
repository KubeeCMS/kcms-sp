'use strict';

( function ( $ ) {
	$( '.fsp-modal-footer > #fspModalAddButton' ).on( 'click', function () {
		let _this = $( this );
		let selectedMethod = String( $( '.fsp-modal-option.fsp-is-selected' ).data( 'step' ) );

		if ( selectedMethod === '1' )
		{
			let appID = $( '#fspModalStep_1 #fspModalAppSelector' ).val().trim();
			let proxy = $( '#fspProxy' ).val().trim();
			let openURL = `${ fspConfig.siteURL }/?medium_app_redirect=${ appID }&proxy=${ proxy }`;

			if ( $( '#fspModalAppSelector > option:selected' ).data( 'is-standart' ).toString() === '1' )
			{
				openURL = `${ fspConfig.standartAppURL }&proxy=${ proxy }&encode=true`;
			}

			window.open( openURL, 'fs-app', 'width=750, height=550' );
		}
		else if ( selectedMethod === '2' )
		{
			let access_token = $( '#fspModalStep_2 #fspMediumAccessToken' ).val();
			let proxy        = $( '#fspProxy' ).val().trim();

			FSPoster.ajax( 'add_new_medium_account_with_token', { access_token, proxy }, function () {
				accountAdded();
			} );
		}
	} );
} )( jQuery );