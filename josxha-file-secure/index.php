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
define("JOSXHA_FILE_SECURE_PATH", WP_PLUGIN_DIR ."/josxha-file-secure");
define("JOSXHA_FILE_SECURE_PATH_PUBLIC", "/wp-content/plugins/josxha-file-secure/");
define("JOSXHA_FILE_SECURE_FILES", ABSPATH."wp-content/uploads/files/");


add_action( 'init', 'josxha_file_secure_init' );
function josxha_file_secure_init() {
    if (!file_exists(JOSXHA_FILE_SECURE_FILES))
        mkdir(JOSXHA_FILE_SECURE_FILES);
    if (!file_exists(JOSXHA_FILE_SECURE_FILES.".htaccess"))
        file_put_contents(JOSXHA_FILE_SECURE_FILES.".htaccess", "Deny from all");
    if (!file_exists(JOSXHA_FILE_SECURE_FILES."index.html"))
        file_put_contents(JOSXHA_FILE_SECURE_FILES."index.html", "");
}


require_once JOSXHA_FILE_SECURE_PATH.'/admin/admin.php';
require_once JOSXHA_FILE_SECURE_PATH.'/url_rewrite/url_rewrite.php';
