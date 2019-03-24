<?php
/**
 * Created by PhpStorm.
 * User: Joscha Eckert
 * Date: 20.03.2019
 * Time: 14:59
 */

if (!defined( 'ABSPATH')) exit;

class JosxhaFileSecureAdmin {
    private $documents_options;

    static function generateRandomString($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++)
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        return $randomString;
    }

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'documents_add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'documents_page_init' ) );
    }

    public function documents_add_plugin_page() {
        add_media_page(
            'secured_files', // page_title
            'Geschützte Dateien', // menu_title
            'manage_options', // capability
            'josxhaFileSecure', // menu_slug
            array( $this, 'documents_create_admin_page' ) // function
        );
    }

    public function documents_create_admin_page() {
        global $wp;
        $this->documents_options = get_option( 'documents_option_name' );
        $url = home_url($wp->request) . '/?file=[NAME DER DATEI]';

        if(isset($_FILES['file']) && !empty($_FILES['file'])) {
            $filename = $_FILES['file']['name'];
            if (in_array(pathinfo($filename, PATHINFO_EXTENSION), array("png","jpg","jpeg","pdf","mp3", "mp4"), true)) {
                $path = JOSXHA_FILE_SECURE_FILES . basename($filename);
                if (move_uploaded_file($_FILES['file']['tmp_name'], $path))
                    $message = "<p style='color: green'>" . basename($filename) . " wurde erfolgreich hochgeladen.</p>";
                else
                    $message = "<p style='color: red'>Die Datei konnte nicht hochgeladen werden.</p>";
            } else $message = "<p style='color: red'>Dateien mit diesem Dateiformat dürfen nicht hochgeladen werden.</p>";
        }
        ?>

        <div class="wrap">
            <h2>Geschützte Dateien</h2>
            <?php settings_errors(); ?>

            <h2>Anleitung</h2>
            <p>Auf die hochgeladenen Dateien kann verlinkt werden. Bilder können zudem auf Seiten und Beiträgen über die URL eingebunden werden. <br />Es können nur angemeldete Nutzer auf die Dateien zugreifen, ein direkter Zugriff ist ebenfalls nicht möglich.</p>
            <p><?php echo $url?></p>
            <p>Erlaubte Dateiformate sind .pdf .png .jpg .jpeg .mp3</p>

            <br />
            <h2>Datei hochladen</h2>
            <form action="" id="uploadFile" enctype="multipart/form-data" method="post">
                <input type="file" name="file" >
                <button class="button button-primary" type="submit" form="uploadFile">Hochladen</button>
            </form>
            <?php if (isset($message)) echo "<p>".$message."</p>" ?>

            <br />
            <h2>Hochgeladene Dateien</h2>
            <ul>
                <?php
                    foreach (scandir(JOSXHA_FILE_SECURE_FILES) as $file)
                        if ($file != ".htaccess" && $file != "index.html"
                            && $file != "." && $file != "..") {
                            echo "<li><a target='_blank' href='" . home_url($wp->request) . '/?file=' . $file . "'>$file</a></li>";
                        }
                ?>
            </ul>
        </div>
    <?php }

    public function documents_page_init() {
        register_setting(
            'documents_option_group', // option_group
            'documents_option_name', // option_name
            array( $this, 'documents_sanitize' ) // sanitize_callback
        );

        add_settings_section(
            'documents_setting_section', // id
            'Settings', // title
            array( $this, 'documents_section_info' ), // callback
            'documents-admin' // page
        );

        add_settings_field(
            'test_text_0', // id
            'test text', // title
            array( $this, 'test_text_0_callback' ), // callback
            'documents-admin', // page
            'documents_setting_section' // section
        );
    }

    public function documents_sanitize($input) {
        $sanitary_values = array();
        if ( isset( $input['test_text_0'] ) ) {
            $sanitary_values['test_text_0'] = sanitize_text_field( $input['test_text_0'] );
        }

        return $sanitary_values;
    }

    public function documents_section_info() {

    }

    public function test_text_0_callback() {
        printf(
            '<input class="regular-text" type="text" name="documents_option_name[test_text_0]" id="test_text_0" value="%s">',
            isset( $this->documents_options['test_text_0'] ) ? esc_attr( $this->documents_options['test_text_0']) : ''
        );
    }

}
if ( is_admin() )
    $documents = new JosxhaFileSecureAdmin();

/*
 * Retrieve this value with:
 * $documents_options = get_option( 'documents_option_name' ); // Array of All Options
 * $test_text_0 = $documents_options['test_text_0']; // test text
 */