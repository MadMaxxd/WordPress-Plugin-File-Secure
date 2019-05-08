<?php
/**
 * Created by PhpStorm.
 * User: Joscha Eckert
 * Date: 20.03.2019
 * Time: 14:59
 */

if (!defined( 'ABSPATH')) exit;

class JosxhaRfaAdmin {
    private $documents_options;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'documents_add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'documents_page_init' ) );
	    //add_filter( 'plugin_action_links_' . JOSXHARFA_PLUGIN_BASENAME, array($this, 'action_links') );
    }

	public function action_links( $links )
	{
		$links[] = '<a href="'. menu_page_url( "upload.php?page=".JOSXHARFA_PLUGIN_NAME, false ) .'">Einstellungen</a>';
		return $links;
	}


	public function documents_add_plugin_page() {
        add_media_page(
            'Geschützte Dateien', // page_title
            'Geschützte Dateien', // menu_title
            'manage_options', // capability
            JOSXHARFA_PLUGIN_NAME, // menu_slug
            array( $this, 'documents_create_admin_page' ) // function
        );
    }

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

    public function documents_create_admin_page() {
        global $wp;
        $this->documents_options = get_option( 'documents_option_name' );
        $url = home_url($wp->request) . '/?file=[NAME DER DATEI]';
        $jsScriptUrl = josxharfa_plugin_url("admin/functions.js");
        $uploadDir = josxharfa_upload_dir()."/";
        $uploadUrl = josxharfa_upload_url()."/";
        $pluginUrl = josxharfa_plugin_url()."/";

        if(isset($_FILES['file']) && !empty($_FILES['file'])) {
            $tmpFileObject = $_FILES['file']['name'];
            $fileNameNew = str_replace(" ", "_", pathinfo($tmpFileObject, PATHINFO_FILENAME));
            $fileExtension = pathinfo($tmpFileObject, PATHINFO_EXTENSION);
            if (in_array(strtolower($fileExtension), array("png","jpg","jpeg","pdf","mp3", "mp4", "xlsx", "docx", "doc", "xls", "ppt", "pptx", "txt", "csv", "gif"), true)) {
                $pathNewFile = $uploadDir.$fileNameNew.".".$fileExtension;
                $counter = 0;
                while (file_exists($pathNewFile)) {
                    $counter++;
                    $pathNewFile = $uploadDir.$fileNameNew."_".$counter.".".$fileExtension;
                }

                if (move_uploaded_file($_FILES['file']['tmp_name'], $pathNewFile))
                    $message = "<p style='color: green'>\"" . basename($pathNewFile) . "\" wurde erfolgreich hochgeladen.</p>";
                else {
	                $message = "<p style='color: red'>Die Datei konnte nicht hochgeladen werden.<br/>" . error_get_last() . "<br></p>";
                }
            } else $message = "<p style='color: red'>Dateien mit diesem Dateiformat dürfen nicht hochgeladen werden.</p>";
        }

        if (isset($_POST['deleteFile']) && !empty($_POST['deleteFile'])) {
            unlink($uploadDir . $_POST['deleteFile']);
            //header("Location: ?page=" . JOSXHARFA_PLUGIN_NAME);
        }
        ?>
        <style>
            .josxharfaTable {
                border: 1px solid black;
                border-collapse: collapse;
                text-align: left;
            }
            .josxharfaTableRow {
                padding: 5px;
            }

            .josxharfaImageButton {
                background: none;
                border: none;
                cursor: pointer;
                padding: 0;
            }
            .josxharfaRowTextRight {
                text-align: right;
                margin: 0;
            }
        </style>
        <script type='text/javascript' src='<?php echo $jsScriptUrl; ?>'></script>
        <script type="application/javascript">
            function josxhaRfaCopy(event, url) {
                if (!event)
                    event = window.event;
                const sender = event.srcElement || event.target;
                sender.src = "<?php echo $pluginUrl; ?>admin/ic_success.png";
                setTimeout(function () {
                    sender.src = "<?php echo $pluginUrl; ?>admin/ic_copy.png";
                }, 1000);

                josxhaCopyToClipboard(url);
            }
        </script>

        <div class="wrap">
            <h2>Geschützte Dateien</h2>
            <?php settings_errors(); ?>

            <h2>Anleitung</h2>
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

            <h2>Hochgeladene Dateien</h2>
            <table class="josxharfaTable" style="max-width:100%">
                <tr class="">
                    <td class="josxharfaTable josxharfaTableRow"><b>Dateiname</b></td>
                    <td class="josxharfaTable josxharfaTableRow" style="text-align: right"><b>Dateigröße</b></td>
                    <td class="josxharfaTable josxharfaTableRow" style="text-align: right"><b>Hochgeladen am</b></td>
                    <td colspan="2" class="josxharfaTable josxharfaTableRow"><b>Aktionen</b></td>
                </tr>
                <?php
                $imgCopy = $pluginUrl . "admin/ic_copy.png";
                $imgDelete = $pluginUrl . "admin/ic_delete.png";
                foreach (scandir($uploadDir) as $file)
                    if ($file != ".htaccess" && $file != "index.html" && $file != "." && $file != "..") {
                        $fileUrl = home_url($wp->request) . "/?file=" . $file;
                        $uploadedAt = date("d.m.Y",filemtime($uploadDir . $file));
                        $fileSize = floatval(filesize($uploadDir . $file));
                        if ($fileSize < 0)
                            $fileSize = "> 2 GB";
                        elseif ($fileSize < 1000)
                            $fileSize .= " Bytes";
                        elseif ($fileSize < 1000000)
                            $fileSize = round($fileSize / 1000)." KB";
                        elseif ($fileSize < 1000000000)
                            $fileSize = round($fileSize / 1000000, 1) . " MB";
                        else
                            $fileSize = round($fileSize / 100000000, 2). " GB";
                        ?>
                        <tr class="josxharfaTable josxharfaTableRow">
                            <td class="josxharfaTable josxharfaTableRow"><a style='text-decoration: none' target='_blank' href='<?php echo $fileUrl; ?>'><?php echo $file; ?></a></td>
                            <td class="josxharfaTable josxharfaTableRow"><p class="josxharfaRowTextRight"><?php echo $fileSize ?></p></td>
                            <td class="josxharfaTable josxharfaTableRow"><p class="josxharfaRowTextRight"><?php echo $uploadedAt; ?></p></td>
                            <td class="josxharfaTable josxharfaTableRow"><a title="URL in die Zwischenablage kopieren" href='' onclick='josxhaRfaCopy(event,"<?php echo $fileUrl; ?>"); return false;'><img src='<?php echo $imgCopy; ?>' style='height: 15px; width: 15px; padding: 4px' alt="URL kopieren"></a></td>
                            <td class="josxharfaTable josxharfaTableRow">
                                <form method="post" action="?page=<?php echo JOSXHARFA_PLUGIN_NAME ?>">
                                    <input type="hidden" name="deleteFile" value="<?php echo $file; ?>">
                                    <button class="josxharfaImageButton" title="Datei löschen" type="submit"><img src='<?php echo $imgDelete; ?>' style='height: 15px; width: 15px; padding: 4px' alt="Löschen"></button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                ?>

            </table>
        </div>
    <?php
    }

}
if ( is_admin() )
    new JosxhaRfaAdmin();