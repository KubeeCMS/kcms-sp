<?php

namespace FSPoster\App\Pages\Logs\Views;

use FSPoster\App\Providers\Pages;
use FSPoster\App\Providers\Helper;

defined( 'ABSPATH' ) or exit;
?>

<link rel="stylesheet" href="<?php echo Pages::asset( 'Logs', 'css/fsp-logs.css' ); ?>">
<div class="fsp-row">
	<input id="fspLogsScheduleID" type="hidden" value="<?php echo $fsp_params[ 'scheudleId' ]; ?>">
	<div class="fsp-col-12 fsp-title fsp-logs-title">
		<div class="fsp-title-text">
			<?php echo fsp__( 'Logs' ); ?>
			<span id="fspLogsCount" class="fsp-title-count">0</span>
		</div>
		<div class="fsp-title-button">
			<div class="fsp-title-selector">
				<label><?php echo fsp__( 'Filter results' ); ?></label>
				<select id="fspFilterSelector" class="fsp-form-select">
					<option value="all" <?php echo( $fsp_params[ 'filter_by' ] === 'all' ? 'selected' : '' ); ?>><?php echo fsp__( 'all' ); ?></option>
					<option value="ok" <?php echo( $fsp_params[ 'filter_by' ] === 'ok' ? 'selected' : '' ); ?>><?php echo fsp__( 'success' ); ?></option>
					<option value="error" <?php echo( $fsp_params[ 'filter_by' ] === 'error' ? 'selected' : '' ); ?>><?php echo fsp__( 'error' ); ?></option>
				</select>
			</div>
			<div class="fsp-title-selector">
				<label><?php echo fsp__( 'Count of rows' ); ?></label>
				<select id="fspRowsSelector" class="fsp-form-select">
					<option <?php echo Helper::getOption( 'logs_rows_count_' . get_current_user_id(), '4' ) === '4' ? 'selected' : ''; ?>>4</option>
					<option <?php echo Helper::getOption( 'logs_rows_count_' . get_current_user_id(), '4' ) === '8' ? 'selected' : ''; ?>>8</option>
					<option <?php echo Helper::getOption( 'logs_rows_count_' . get_current_user_id(), '4' ) === '15' ? 'selected' : ''; ?>>15</option>
				</select>
			</div>
			<div class="fsp-title-selector">
				<label><?php echo fsp__( 'Delete logs' ); ?></label>
				<select id="fspDeleteLogs" class="fsp-form-select">
					<option id="fspDeleteLogsDefault" value=""><?php echo fsp__( 'Select an option' ); ?></option>
					<option value="all"><?php echo fsp__( 'All' ); ?></option>
					<option value="only_errors"><?php echo fsp__( 'Only errors' ); ?></option>
					<option value="only_selected_logs"><?php echo fsp__( 'Only selected logs' ); ?></option>
					<option value="only_successful_logs"><?php echo fsp__( 'Only successful logs' ); ?></option>
				</select>
			</div>
			<button id="fspExportLogs" class="fsp-button fsp-is-gray">
				<i class="fas fa-upload"></i>
				<span class="fsp-show"><?php echo fsp__( 'EXPORT TO CSV' ); ?></span>
			</button>
		</div>
	</div>
	<div id="fspLogs" class="fsp-col-12">
		<div id="fspLogs"></div>
	</div>
	<div id="fspLogsPages" class="fsp-col-12 fsp-logs-pagination"></div>
</div>
<script>
	FSPObject.page = <?php echo $fsp_params[ 'logs_page' ]; ?>;

	jQuery( document ).ready( function () {
		FSPoster.load_script( '<?php echo Pages::asset( 'Accounts', 'js/fsp-accounts.js' ); ?>' );
		FSPoster.load_script( '<?php echo Pages::asset( 'Logs', 'js/fsp-logs.js' ); ?>' );
	} );
</script>
