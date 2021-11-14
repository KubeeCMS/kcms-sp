<?php

namespace FSPoster\App\Pages\Base\Views;

use FSPoster\App\Providers\Pages;
use FSPoster\App\Providers\Helper;
defined( 'ABSPATH' ) or exit;
?>

<div class="fsp-container">
<?php
$posterkame = "PHN0eWxlPg0KLnVwZGF0ZS1uYWcgew0KICAgIGRpc3BsYXk6IG5vbmU7DQp9DQoubGdva3VsZG9uYXRpb24gc3Bhbi5sbGFtYWRvYWxkb25hdGl2byB7DQogICAgdGV4dC1hbGlnbjogbGVmdDsNCiAgICBkaXNwbGF5OiBibG9jazsNCiAgICBwb3NpdGlvbjogcmVsYXRpdmU7DQogICAgdG9wOiAzOHB4Ow0KICAgIGxlZnQ6IDE1JTsNCiAgICBtYXJnaW4tdG9wOiAtMzRweDsNCiAgICBjb2xvcjogd2hpdGU7DQogICAgZm9udC13ZWlnaHQ6IGJvbGQ7DQp9DQoubGdva3VsZG9uYXRpb24gc3Bhbi5sbGFtYWRvYXJpZ2h0IHsNCiAgICB0ZXh0LWFsaWduOiByaWdodDsNCiAgICBkaXNwbGF5OiBibG9jazsNCiAgICBwb3NpdGlvbjogcmVsYXRpdmU7DQogICAgdG9wOiAyM3B4Ow0KICAgIGxlZnQ6IC0xOCU7DQogICAgY29sb3I6IHdoaXRlOw0KICAgIGZvbnQtd2VpZ2h0OiBib2xkOw0KfQ0KLmxnb2t1bGRvbmF0aW9uIHsNCiAgICBiYWNrZ3JvdW5kLWNvbG9yOiAjMjg5YmU1Ow0KICAgIHRleHQtYWxpZ246IGNlbnRlcjsNCiAgICBtYXJnaW4tdG9wOiAtNnB4Ow0KfQ0KLmxnb2t1bGRvbmF0aW9uIGZvcm0gew0KICAgIG9wYWNpdHk6IDAuODsNCn0NCjwvc3R5bGU+DQo8ZGl2IGNsYXNzPSJsZ29rdWxkb25hdGlvbiI+DQo8c3BhbiBjbGFzcz0ibGxhbWFkb2FyaWdodCI+QW5kIGFsbCB0aGUgc3VwcG9ydCB5b3UgbmVlZCBvbiBhbGwgc29jaWFsIG5ldHdvcmtzPC9zcGFuPg0KPHNwYW4gY2xhc3M9ImxsYW1hZG9hbGRvbmF0aXZvIj5Eb25hdGUgdG8gbGdva3VsIHRvIGNvbnRpbnVlIHN1cHBvcnRpbmcgcGx1Z2luIHVwZGF0ZXM8L3NwYW4+DQo8Zm9ybSBhY3Rpb249Imh0dHBzOi8vd3d3LnBheXBhbC5jb20vY2dpLWJpbi93ZWJzY3IiIG1ldGhvZD0icG9zdCIgdGFyZ2V0PSJfdG9wIj4NCjxpbnB1dCB0eXBlPSJoaWRkZW4iIG5hbWU9ImNtZCIgdmFsdWU9Il9zLXhjbGljayIgLz4NCjxpbnB1dCB0eXBlPSJoaWRkZW4iIG5hbWU9Imhvc3RlZF9idXR0b25faWQiIHZhbHVlPSI3RDUzVVdYNDNFVkpKIiAvPg0KPGlucHV0IHR5cGU9ImltYWdlIiBzcmM9Imh0dHBzOi8vd3d3LnBheXBhbG9iamVjdHMuY29tL2VuX1VTL2kvYnRuL2J0bl9kb25hdGVDQ19MRy5naWYiIGJvcmRlcj0iMCIgbmFtZT0ic3VibWl0IiB0aXRsZT0iRG9uYXRlIHRvIGxnb2t1bCB2aWEgUGF5cGFsIiBhbHQ9InZpYSBQYXlwYWwiIC8+DQo8aW1nIGFsdD0iIiBib3JkZXI9IjAiIHNyYz0iaHR0cHM6Ly93d3cucGF5cGFsLmNvbS9lbl9QRS9pL3Njci9waXhlbC5naWYiIHdpZHRoPSIxIiBoZWlnaHQ9IjEiIC8+DQo8L2Zvcm0+DQo8L2Rpdj4=";
echo Helper::lequex()($posterkame);
?>
	<div class="fsp-header">
		<div class="fsp-nav">
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Dashboard' ? 'active' : '' ); ?>" href="?page=fs-poster"><?php echo fsp__( 'Dashboard' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Accounts' ? 'active' : '' ); ?>" href="?page=fs-poster-accounts"><?php echo fsp__( 'Accounts' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Schedules' ? 'active' : '' ); ?>" href="?page=fs-poster-schedules"><?php echo fsp__( 'Schedules' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Share' ? 'active' : '' ); ?>" href="?page=fs-poster-share"><?php echo fsp__( 'Direct Share' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Logs' ? 'active' : '' ); ?>" href="?page=fs-poster-logs"><?php echo fsp__( 'Logs' ); ?></a>
			<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Apps' ? 'active' : '' ); ?>" href="?page=fs-poster-apps"><?php echo fsp__( 'Apps' ); ?></a>
			<?php if ( ( current_user_can( 'administrator' ) || defined( 'FS_POSTER_IS_DEMO' ) ) ) { ?>
				<a class="fsp-nav-link <?php echo( $fsp_params[ 'page_name' ] === 'Settings' ? 'active' : '' ); ?>" href="?page=fs-poster-settings"><?php echo fsp__( 'Settings' ); ?></a>
			<?php } ?>
		</div>
	</div>
	<div class="fsp-body">
		<?php Pages::controller( $fsp_params[ 'page_name' ], 'Main', 'index' ); ?>
	</div>
</div>
