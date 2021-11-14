<?php
/*
 * Plugin Name: Social Poster
 * Description: Auto-publish on Social Media platforms.
 * Version: 5.2.3
 * Author: Kubee
 * Author URI: https://github.com/KubeeCMS/kcms-sp/
 * Developer: KubeeCMS
 * Developer URI: https://github.com/KubeeCMS/
 * Requires at least: 4.4
 * Tested up to: 5.9
 * WC requires at least: 3.0.0
 * WC tested up to: 5.8
 * Text Domain: fs-poster
 * Domain Path: /languages
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 
 */

namespace FSPoster;

use FSPoster\App\Providers\Bootstrap;

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

new Bootstrap();
