'use strict';

( function ( $ ) {
	let doc = $( document );

	doc.ready( function () {
		let currentPage = 1;

		doc.on( 'change', '#fspRowsSelector, #fspFilterSelector', function () {
			FSPLoadLogs( 1 );
		} )
		   .on( 'change', '#fspLogsPageSelector', function () {
			let page = $( this ).val();

			FSPLoadLogs( page );
		} )
		   .on( 'click', '.fsp-logs-page', function () {
			let _this = $( this );
			let page = _this.data( 'page' );

			if ( page === currentPage )
			{
				return;
			}
			else
			{
				currentPage = page;
			}

			FSPLoadLogs( page );
		} ).on( 'change', '#fspDeleteLogs', function () {

			let confirmMessage = '';

			let data = {
				'type': $( this ).val()
			}


			if ( data.type === 'all' )
			{
				confirmMessage = fsp__( 'Deleting logs will also delete the related insights in the dashboard. Are you sure you want to delete all logs?' );
			}
			else if ( data.type === 'only_errors' )
			{
				confirmMessage = fsp__( 'Are you sure you want to delete the error logs?' );
			}
			else if ( data.type === 'only_successful_logs' )
			{
				confirmMessage = fsp__( 'Deleting the successful logs will also delete the related insights in the dashboard. Are you sure you want to delete all successful logs?' );
			}
			else if ( data.type === 'only_selected_logs' )
			{
				let selected_accounts = [];

				$( '.fsp-log-clear-checkbox:checked' ).each( function () {
					selected_accounts.push( $( this ).data( 'id' ) );
				} );

				data.selected_accounts = selected_accounts;

				confirmMessage = fsp__( 'Deleting the successful logs will also delete the related insights in the dashboard. Are you sure you want to delete all successful logs?' );
			}

			if ( confirmMessage !== '' )
			{
				FSPoster.confirm( confirmMessage, function () {

					FSPoster.ajax( 'fs_clear_logs', data, function () {
						let url = window.location.href;

						if ( url.indexOf( 'logs_page' ) > -1 )
						{
							url = url.replace( /logs_page=([0-9]+)/, `logs_page=1` );
						}

						window.location.href = url;
					} );
				} );

			}

			$( this ).children( '#fspDeleteLogsDefault' ).prop( 'selected', true );
		} )
		   .on( 'click', '.fsp-logs-retry', function () {
			let _this = $( this );
			let feedId = _this.data( 'feed-id' );

			if ( feedId )
			{
				FSPoster.ajax(
					'get_feed_details',
					{
						'feed_id': feedId
					},
					function ( data ) {
						FSPoster.ajax(
							'share_saved_post',
							{
								'post_id': data.result.post_id,
								'nodes': data.result.nodes,
								'background': 0,
								'custom_messages': data.result.customMessages,
								'shared_from': data.result.sharedFrom,
								'retried': 1
							},
							function () {
								FSPoster.loadModal( 'share_feeds', { 'post_id': data.result.post_id }, true );
							}
						);
					}
				);
			}
		} )

		   .on( 'click', '#fspExportLogs', function () {
			FSPoster.ajax(
				'export_logs_to_csv',
				{},
				function ( result )
				{
					let a = $( '<a>' );

					a.attr( 'href', result.file );


					$( 'body' ).append( a );

					a.attr('download', result.filename );
					a[ 0 ].click();
					a.remove();
				}
			);
		} );
		FSPLoadLogs( FSPObject.page );
	} );
} )( jQuery );

function FSPLoadLogs ( page )
{
	if ( typeof jQuery !== 'undefined' )
	{
		$ = jQuery;
	}

	let rowsCount = $( '#fspRowsSelector' ).val();
	let filter = $( '#fspFilterSelector' ).val();
	let scheduleID = $( '#fspLogsScheduleID' ).val();

	FSPoster.ajax( 'report3_data', {
		page,
		'schedule_id': scheduleID,
		'rows_count': rowsCount,
		'filter_results': filter
	}, function ( result ) {
		let url = window.location.href;

		if ( url.indexOf( 'filter_by' ) > -1 )
		{
			url = url.replace( /filter_by=([a-zA-Z]+)/, `filter_by=${ filter }` );
		}
		else
		{
			url += `${ ( url.indexOf( '?' ) > -1 ? '&' : '?' ) }filter_by=${ filter }`;
		}

		if ( url.indexOf( 'logs_page' ) > -1 )
		{
			url = url.replace( /logs_page=([0-9]+)/, `logs_page=${ page }` );
		}
		else
		{
			url += `${ ( url.indexOf( '?' ) > -1 ? '&' : '?' ) }logs_page=${ page }`;
		}

		window.history.pushState( '', '', url );

		$( '#fspLogs' ).empty();

		$( '#fspLogsCount' ).text( result[ 'total' ] );

		for ( let i in result[ 'data' ] )
		{
			let statusBtn;

			if ( result[ 'data' ][ i ][ 'status' ] === 'ok' )
			{
				statusBtn = `<div class="fsp-status fsp-is-success"><i class="fas fa-check"></i>${ fsp__( 'SUCCESS' ) }</div>`;
			}
			else if ( result[ 'data' ][ i ][ 'status' ] === 'error' )
			{
				statusBtn = `<div class="fsp-status fsp-is-danger fsp-tooltip" data-title="${ result[ 'data' ][ i ][ 'error_msg' ] }"><i class="fas fa-times"></i>${ fsp__( 'ERROR' ) }</div>
						<button class="fsp-button fsp-is-warning fsp-logs-retry" data-feed-id="${ result[ 'data' ][ i ][ 'id' ] }"><i class="fas fa-sync"></i>${ fsp__( 'RETRY' ) }</button>`;
			}
			else
			{
				statusBtn = `<div class="fsp-status fsp-is-warning"><i class="fas fa-check"></i>${ fsp__( 'NOT SENT' ) }</div>`;
			}

			let  post_link = ``;

			if (  result[ 'data' ][ i ][ 'has_post_link' ] )
			{
				post_link = `<a target="_blank" href="${ fspConfig.siteURL }/?p=${ result[ 'data' ][ i ][ 'wp_post_id' ] }" class="fsp-tooltip" data-title="${ fsp__( 'Post permalink' ) }"><i class="fas fa-external-link-alt"></i></a>`;
			}

			let account_link = ``;

			if ( ! result[ 'data' ][ i ][ 'is_deleted' ] )
			{
				account_link = `<a target="_blank" href="${ result[ 'data' ][ i ][ 'profile_link' ] }" class="fsp-tooltip" data-title="${ fsp__( 'Profile link' ) }"><i class="fas fa-external-link-alt"></i></a>`;
			}

			let driverIcon = result[ 'data' ][ i ][ 'icon' ];

			$( '#fspLogs' ).append( `
				<div class="fsp-log">
					<div class="fsp-is-second">
						<input type="checkbox" class="fsp-form-checkbox fsp-log-clear-checkbox" data-id="${ result[ 'data' ][ i ][ 'id' ] }">
					</div>
					&nbsp;
					<div class="fsp-log-image">
						<img src="${ result[ 'data' ][ i ][ 'cover' ] }" onerror="FSPoster.no_photo( this );">
					</div>
					<div class="fsp-log-title">
						<div class="fsp-log-title-text">
							${ result[ 'data' ][ i ][ 'name' ] }
							${ account_link }
						</div>
						<div class="fsp-log-title-subtext">
							${ result[ 'data' ][ i ][ 'date' ] }

							${ post_link }

							<span class="fsp-tooltip" data-title="${ result[ 'data' ][ i ][ 'shared_from' ] }"><i class="fa fa-info-circle"></i></span>
						</div>
					</div>
					<div class="fsp-log-title fsp-is-second">
						<div class="fsp-log-title-link">
							<a target="_blank" href="${ result[ 'data' ][ i ][ 'post_link' ] }">
								<i class="fas fa-external-link-alt"></i>
								${ fsp__( 'Shared post link' ) }
							</a>
						</div>
						<div class="fsp-log-title-subtext fsp-log-title-sublink">
							<i class="${ driverIcon }"></i>&nbsp;${ result[ 'data' ][ i ][ 'driver' ][ 0 ].toUpperCase() + result[ 'data' ][ i ][ 'driver' ].substring( 1 ) }&nbsp;>&nbsp;${ result[ 'data' ][ i ][ 'node_type' ] + ( result[ 'data' ][ i ][ 'feed_type' ] !== '' ? ' > ' + result[ 'data' ][ i ][ 'feed_type' ] : '' ) }
						</div>
					</div>
					<div class="fsp-log-status-container">
						${ statusBtn }
					</div>
					<div class="fsp-log-stats">
						${ result[ 'data' ][ i ][ 'hide_stats' ] ? '' : `
							<div class="fsp-log-stat">
								<i class="far fa-eye"></i> ${ fsp__( 'Hits' ) }: <span class="fsp-log-stat-value">${ result[ 'data' ][ i ][ 'hits' ] }</span>
							</div>
							<div class="fsp-log-stat">
								<i class="far fa-comments"></i> ${ fsp__( 'Comments' ) }: <span class="fsp-log-stat-value">${ typeof result[ 'data' ][ i ][ 'insights' ][ 'comments' ] != 'undefined' ? result[ 'data' ][ i ][ 'insights' ][ 'comments' ] : 0 }</span>
							</div>
							<div class="fsp-log-stat">
								<i class="far fa-thumbs-up"></i> ${ fsp__( 'Likes' ) }: <span class="fsp-log-stat-value">${ result[ 'data' ][ i ][ 'insights' ][ 'like' ] }</span>
							</div>
							<div class="fsp-log-stat">
								<i class="fas fa-share-alt"></i> ${ fsp__( 'Shares' ) }: <span class="fsp-log-stat-value">${ typeof result[ 'data' ][ i ][ 'insights' ][ 'shares' ] != 'undefined' ? result[ 'data' ][ i ][ 'insights' ][ 'shares' ] : 0 }</span>
							</div>
						` }
					</div>
				</div>
			` );
		}

		let logsPages = '';
		let j = 0;

		result[ 'pages' ][ 'page_number' ].forEach( function ( i ) {
			logsPages += `<button class="fsp-button fsp-is-${ i === parseInt( result[ 'pages' ][ 'current_page' ] ) ? 'danger' : 'white' } fsp-logs-page" data-page="${ i }">${ i }</button>`;

			if ( typeof result[ 'pages' ][ 'page_number' ][ j + 1 ] !== 'undefined' && result[ 'pages' ][ 'page_number' ][ j + 1 ] !== i + 1 )
			{
				logsPages += '<button class="fsp-button fsp-is-white" disabled>...</button>';
			}

			j++;
		} );

		logsPages += `<select id="fspLogsPageSelector" class="fsp-form-select">`;

		for ( let i = 1; i <= result[ 'pages' ][ 'count' ]; i++ )
		{
			logsPages += `<option value="${ i }" ${ i === parseInt( result[ 'pages' ][ 'current_page' ] ) ? 'selected' : '' }>${ i }</option>`;
		}

		logsPages += `</select>`;

		$( '#fspLogsPages' ).html( logsPages );
	} );
}