<?php
/**
 * Created by PhpStorm.
 * User: Joscha Eckert
 * Date: 20.03.2019
 * Time: 15:00
 */

class FileSecure {

    function activate() {
        global $wp_rewrite;
        $this->flush_rewrite_rules();
    }

    // Took out the $wp_rewrite->rules replacement so the rewrite rules filter could handle this.
    function create_rewrite_rules($rules) {
        global $wp_rewrite;
        $newRule = array('file/(.+)' => 'index.php?file='.$wp_rewrite->preg_index(1));
        $newRules = $newRule + $rules;
        return $newRules;
    }

    function add_query_vars($qvars) {
        $qvars[] = 'file';
        return $qvars;
    }

    function flush_rewrite_rules() {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    function template_redirect_intercept() {
        global $wp_query;
        if ($wp_query->get('file')) {
            $this->pushoutput($wp_query->get('file'));
            exit;
        }
    }

    function pushoutput($message) {
        $this->output($message);
    }

    function output( $filename ) {
        is_user_logged_in() or die("You need to be logged in to access this file.");

        $file_path = JOSXHA_FILE_SECURE_FILES.$filename;
        header( 'Cache-Control: no-cache, must-revalidate' );
        header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
        if (!file_exists($file_path))
            die("File not found.");
        header('Content-Length: '. filesize($file_path));
        switch (pathinfo($filename, PATHINFO_EXTENSION)) {
            case "png":
                header("Content-type: image/png");
                break;
            case "jpg":
            case "jpeg":
                header("Content-type: image/jpeg");
                break;
            case "mp3":
                header("Content-type:audio/mpeg");
                break;
            case "pdf":
                header("Content-type:application/pdf");
                break;
            default:
                die("No valid file format.");
        }
        header("Content-disposition: inline;filename=".$filename); //Content-Disposition:attachment;
        readfile($file_path);
    }
}

$FileSecureCode = new FileSecure();
register_activation_hook( __file__, array($FileSecureCode, 'activate') );

// Using a filter instead of an action to create the rewrite rules.
// Write rules -> Add query vars -> Recalculate rewrite rules
add_filter('rewrite_rules_array', array($FileSecureCode, 'create_rewrite_rules'));
add_filter('query_vars',array($FileSecureCode, 'add_query_vars'));

// Recalculates rewrite rules during admin init to save resourcees.
// Could probably run it once as long as it isn't going to change or check the
// $wp_rewrite rules to see if it's active.
add_filter('admin_init', array($FileSecureCode, 'flush_rewrite_rules'));
add_action( 'template_redirect', array($FileSecureCode, 'template_redirect_intercept') );