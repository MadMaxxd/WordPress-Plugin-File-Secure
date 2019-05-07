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
        $jsScriptUrl = JOSXHA_FILE_SECURE_PATH_PUBLIC. "admin/functions.js";

        if(isset($_FILES['file']) && !empty($_FILES['file'])) {
            $tmpFileObject = $_FILES['file']['name'];
            $fileNameNew = str_replace(" ", "_", pathinfo($tmpFileObject, PATHINFO_FILENAME));
            $fileExtension = pathinfo($tmpFileObject, PATHINFO_EXTENSION);
            if (in_array(strtolower($fileExtension), array("png","jpg","jpeg","pdf","mp3", "mp4", "xlsx", "docx", "doc", "xls", "ppt", "pptx", "txt", "csv", "gif"), true)) {
                $pathNewFile = JOSXHA_FILE_SECURE_FILES.$fileNameNew.".".$fileExtension;
                $counter = 0;
                while (file_exists($pathNewFile)) {
                    $counter++;
                    $pathNewFile = JOSXHA_FILE_SECURE_FILES.$fileNameNew."_".$counter.".".$fileExtension;
                }

                if (move_uploaded_file($_FILES['file']['tmp_name'], $pathNewFile))
                    $message = "<p style='color: green'>\"" . basename($pathNewFile) . "\" wurde erfolgreich hochgeladen.</p>";
                else
                    $message = "<p style='color: red'>Die Datei konnte nicht hochgeladen werden.</p>";
            } else $message = "<p style='color: red'>Dateien mit diesem Dateiformat dürfen nicht hochgeladen werden.</p>";
        }

        if (isset($_POST['deleteFile']) && !empty($_POST['deleteFile'])) {
            unlink(JOSXHA_FILE_SECURE_FILES . $_POST['deleteFile']);
            header("Location: ?page=josxhaFileSecure");
        }
        ?>

        <div class="wrap">
            <h2>Geschützte Dateien</h2>
            <?php settings_errors(); ?>

            <h2>Anleitung</h2>
            <script type='text/javascript' src='<?php echo $jsScriptUrl; ?>'></script>
            <p>Auf die hochgeladenen Dateien kann verlinkt werden. Bilder können zudem auf Seiten und Beiträgen über die URL eingebunden werden. <br />Es können nur angemeldete Nutzer auf die Dateien zugreifen, ein direkter Zugriff ist ebenfalls nicht möglich.</p>
            <p><?php echo $url?></p>
            <p>Erlaubte Dateiformate sind .png .jpg .jpeg .pdf .mp3 .mp4 .xlsx .docx .doc .xls .ppt .pptx .txt .csv .gif</p>

            <br />
            <h2>Datei hochladen</h2>
            <form action="" id="uploadFile" enctype="multipart/form-data" method="post">
                <input type="file" name="file" >
                <button class="button button-primary" type="submit" form="uploadFile">Hochladen</button>
            </form>
            <?php if (isset($message)) echo "<p>".$message."</p>" ?>

            <br><br>

            <style>
                .josxhaTable {
                    border: 1px solid black;
                    border-collapse: collapse;
                    text-align: left;
                }
                .josxhaTableRow {
                    padding: 5px;
                }

                .josxhaImageButton {
                    background: none;
                    border: none;
                    cursor: pointer;
                    padding: 0;
                }
                .josxhaImageButton::selection {

                }
            </style>

            <h2>Hochgeladene Dateien</h2>
            <table class="josxhaTable" style="max-width:100%">
                <tr class="">
                    <td class="josxhaTable josxhaTableRow"><b>Dateiname</b></td>
                    <td class="josxhaTable josxhaTableRow"><b>Hochgeladen am</b></td>
                    <td colspan="2" class="josxhaTable josxhaTableRow"><b>Aktionen</b></td>
                </tr>
                <?php
                $imgCopy = JOSXHA_FILE_SECURE_PATH_PUBLIC . "admin/ic_copy.png";
                $imgDelete = JOSXHA_FILE_SECURE_PATH_PUBLIC . "admin/ic_delete.png";
                foreach (scandir(JOSXHA_FILE_SECURE_FILES) as $file)
                    if ($file != ".htaccess" && $file != "index.html"
                        && $file != "." && $file != "..") {
                        $fileUrl = home_url($wp->request) . "/?file=" . $file;
                        $uploadedAt = date("d.m.Y",filemtime(JOSXHA_FILE_SECURE_FILES . $file))
                        ?>
                        <tr class="josxhaTable josxhaTableRow">
                            <td class="josxhaTable josxhaTableRow"><a style='text-decoration: none' target='_blank' href='<?php echo $fileUrl; ?>'><?php echo $file; ?></a></td>
                            <td class="josxhaTable josxhaTableRow"><?php echo $uploadedAt; ?></td>
                            <td class="josxhaTable josxhaTableRow"><a title="URL in die Zwischenablage kopieren" href='' onclick='copy(event,"<?php echo $fileUrl; ?>"); return false;'><img src='<?php echo $imgCopy; ?>' style='height: 15px; width: 15px; padding: 4px' alt="URL kopieren"></a></td>
                            <td class="josxhaTable josxhaTableRow">
                                <form method="post" action="?page=josxhaFileSecure">
                                    <input type="hidden" name="deleteFile" value="<?php echo $file; ?>">
                                    <button class="josxhaImageButton" title="Datei löschen" type="submit"><img src='<?php echo $imgDelete; ?>' style='height: 15px; width: 15px; padding: 4px' alt="Löschen"></button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                ?>

            </table>
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