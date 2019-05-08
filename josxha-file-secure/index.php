<?php
/**
 * Created by PhpStorm.
 * User: Joscha Eckert
 * Date: 20.03.2019
 * Time: 13:13
 *
 * Plugin Name: Restrict File Access
 * Plugin URI: https://github.com/josxha/WordPress-Plugin-File-Secure
 * Description: Schütze hochgeladene Dateien vor unbefugtem Zugriff. (Im Admin Interface unter Medien->Geschützte Dateien zu finden)
 * Version: 1.0.0
 * Author: Joscha Eckert
 * License: GPLv2
 * Author URI: https://joscha-eckert.de
 */

if (!defined( 'ABSPATH')) exit;

// /LOCALPATH/wp-content/plugins/PLUGINFOLDER/index.php
define( 'JOSXHARFA_PLUGIN', __FILE__ );

// PLUGINFOLDER/index.php
define( 'JOSXHARFA_PLUGIN_BASENAME', plugin_basename( JOSXHARFA_PLUGIN ) );

// PLUGINFOLDER (plugin name)
define( 'JOSXHARFA_PLUGIN_NAME', trim( dirname( JOSXHARFA_PLUGIN_BASENAME ), '/' ) );

// /local/path/wp-content/plugins/PLUGINFOLDER
define( 'JOSXHARFA_PLUGIN_DIR', untrailingslashit( dirname( JOSXHARFA_PLUGIN ) ) );

/**
 * @return string
 * "http(s)://DOMAIN/wp-content/uploads/files"
 */
function josxharfa_upload_url() {
	return wp_upload_dir()["baseurl"] . "/files";
}

/**
 * @return string
 * "http(s)://DOMAIN/wp-content/uploads/files"
 */
function josxharfa_upload_dir() {
	return wp_upload_dir()["basedir"] . "/files";
}

/**
 * @param string $path
 * additional path
 * @return string
 * "http(s)://DOMAIN/wp-content/plugins/PLUGINFOLDER"
 */
function josxharfa_plugin_url( $path = '' ) {
    return josxharfa_useSslIfActive(plugins_url( $path, JOSXHARFA_PLUGIN ));
}

/**
 * @param $url
 * an url
 * @return string
 * to https converted url if ssl is enabled
 */
function josxharfa_useSslIfActive($url) {
	if ( is_ssl() and 'http:' == substr( $url, 0, 5 ) )
		return 'https:' . substr( $url, 5 );
	else return $url;
}

// run on plugin activation
add_action( 'init', 'josxharfa_init' );
function josxharfa_init() {
	$uploadDir = josxharfa_upload_dir();
    if (!file_exists($uploadDir))
        mkdir($uploadDir);
    if (!file_exists($uploadDir."/.htaccess"))
        file_put_contents($uploadDir."/.htaccess", "Deny from all");
    if (!file_exists($uploadDir."/index.html"))
        file_put_contents($uploadDir."/index.html", "");
}

require_once JOSXHARFA_PLUGIN_DIR.'/admin/admin.php';
require_once JOSXHARFA_PLUGIN_DIR.'/url_rewrite/url_rewrite.php';
