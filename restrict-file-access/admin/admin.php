<?php
/**
 * Created by PhpStorm.
 * User: Joscha Eckert
 * Date: 20.03.2019
 * Time: 14:59
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JosxhaRfaAdmin {
	private $documents_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'documents_add_plugin_page' ) );
	}

	public function documents_add_plugin_page() {
		add_media_page(
			'Protected Files', // page_title
			'Protected Files', // menu_title
			'manage_options', // capability
			JOSXHARFA_PLUGIN_NAME, // menu_slug
			array( $this, 'documents_create_admin_page' ) // function
		);
	}

	public function action_links( $links ) {
		$links[] = '<a href="' . menu_page_url( "upload.php?page=" . JOSXHARFA_PLUGIN_NAME, false ) . '">Settings</a>';
		return $links;
	}

	public function documents_create_admin_page() {
		global $wp;
        $allowedExtensions = array("png", "jpg", "jpeg", "pdf", "mp3", "mp4", "xlsx", "docx", "doc", "xls", "ppt", "pptx", "txt", "csv", "gif");
		$this->documents_options = get_option( 'documents_option_name' );
		$url                     = home_url( $wp->request ) . '/?file=[NAME DER DATEI]';
		$uploadDir               = josxharfa_upload_dir() . "/";
		$uploadUrl               = josxharfa_upload_url() . "/";
		$pluginUrl               = josxharfa_plugin_url() . "/";
		wp_enqueue_style( "josxha-rfa-admin-style", $pluginUrl . "admin/style.css" );
		wp_enqueue_script( "josxha-rfa-admin-js", $pluginUrl . "admin/functions.js" );

		// test if a file has been submitted
		if ( isset( $_FILES['file'] ) && ! empty( $_FILES['file'] ) ) {
            $amountFiles = count($_FILES['file']['name']);
            $message = '';
            for($i=0; $i<$amountFiles; $i++) {
                $tmpFileObject = $_FILES['file']['name'][$i];
                $fileNameNew = str_replace(" ", "_", pathinfo($tmpFileObject, PATHINFO_FILENAME));
                $fileExtension = pathinfo($tmpFileObject, PATHINFO_EXTENSION);
                $pathNewFile = $uploadDir . $fileNameNew . "." . $fileExtension;

                // rename file if name already exists
                $counter = 0;
                while (file_exists($pathNewFile)) {
                    $counter++;
                    $pathNewFile = $uploadDir . $fileNameNew . "_" . $counter . "." . $fileExtension;
                }

                // test if the file type is allowed
                if (in_array(strtolower($fileExtension), $allowedExtensions, true)) {
                    // move file from tmp to files directory
                    if (move_uploaded_file($_FILES['file']['tmp_name'][$i], $pathNewFile)) {
                        $message .= '<p style="color: green">"' . basename($pathNewFile) . '" was successfully uploaded.</p>';
                    } else {
                        $message .= "<p style='color: red'>The file \"" . basename($pathNewFile) . "\" could not be uploaded. " . error_get_last() . "</p>";
                    }
                } else {
                    $message .= "<p style='color: red'>Files with the Dateiformat ." . $fileExtension . " must not be uploaded (\"" . basename($pathNewFile) . "\").</p>";
                }
            }
		}


		// test if file has been submitted that should be deleted
		if ( isset( $_POST['deleteFile'] ) && ! empty( $_POST['deleteFile'] ) ) {
			unlink( $uploadDir . $_POST['deleteFile'] );
		}


		// test if user changed settings
        if (isset($_POST['onAccess'])) {
            // save submitted preferences
            $allowedRoles = $_POST['userRole'];
            $array = array();
            foreach (josxharfa_get_wordpress_roles() as $roleName => $roleData) {
                $array[$roleName] = key_exists($roleName, $allowedRoles) ? true : false;
            }
            $settings = array(
                "onAccess" => $_POST['onAccess'],
                "notFound" => $_POST['notFound'],
                "userRole" => $array
            );

            // write to database
	        update_option(JOSXHARFA_PLUGIN_NAME, $settings, "yes");
        } else {
            // read settings from database
	        $settings = get_option(JOSXHARFA_PLUGIN_NAME);
        }

        $maxUploadSize = ini_get("upload_max_filesize");
		// echo content of the admin page ?>
        <div class="wrap">
            <h2>Protected files</h2>
			<?php settings_errors(); ?>
            <br>

            <ul class="nav nav-tabs">
                <li onclick="switchTab(event)" id="tabFiles" class="active"><a href="#files">Upload files</a></li>
                <li onclick="switchTab(event)" id="tabSettings"><a href="#settings">Settings</a></li>
                <li onclick="switchTab(event)" id="tabHelp"><a href="#help">Help</a></li>
            </ul>


            <div class="tab-content">
                <div id="files" class="tab-pane active">
                    <h2>Instructions</h2>
                    <p class="josxhaText">
                        You can link to the uploaded files. Images can also be integrated into pages and articles via the URL. <br/>
                        Only registered users can access the files; direct access is also not possible.
                    </p>
                    <p class="josxhaText">
                        The structure of the link is as follows:<br>
		                <?php echo $url ?>
                    </p>
                    <p class="josxhaText">
                        Allowed file formats are<?php foreach($allowedExtensions as $item) echo " $item" ?>
                    </p>
                    <br>
                    <h2>Upload file</h2>
                    <form action="" id="uploadFile" enctype="multipart/form-data" method="post">
                        <p class="josxhaText">Multiple files can be selected. It can be a maximum <?php echo $maxUploadSize; ?> uploaded per request.</p>
                        <input type="file" name="file[]" id="file" multiple>
                        <button class="button button-primary" type="submit" form="uploadFile">Upload</button>
                    </form>
					<?php if ( isset( $message ) )
						echo "<p>" . $message . "</p>" ?>

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
                    <h2>Uploaded files</h2>
                    <table class="josxharfaTable" style="max-width:100%">
                        <tr class="">
                            <td class="josxharfaTable josxharfaTableRow"><b>Filename</b></td>
                            <td class="josxharfaTable josxharfaTableRow" style="text-align: right"><b>Filesize</b>
                            </td>
                            <td class="josxharfaTable josxharfaTableRow" style="text-align: right"><b>Uploaded on</b>
                            </td>
                            <td colspan="2" class="josxharfaTable josxharfaTableRow"><b>Actions</b></td>
                        </tr>
						<?php
                        // insert uploaded files as content of the table
						$imgCopy   = $pluginUrl . "admin/ic_copy.png";
						$imgDelete = $pluginUrl . "admin/ic_delete.png";
						foreach ( scandir( $uploadDir ) as $file ) {
							if ( $file != ".htaccess" && $file != "index.html" && $file != "." && $file != ".." ) {
								$fileUrl    = home_url( $wp->request ) . "/?file=" . $file;
								$uploadedAt = date( "d.m.Y", filemtime( $uploadDir . $file ) );
								$fileSize   = floatval( filesize( $uploadDir . $file ) );
								if ( $fileSize < 0 ) {
									$fileSize = "> 2 GB";
								} elseif ( $fileSize < 1000 ) {
									$fileSize .= " Bytes";
								} elseif ( $fileSize < 1000000 ) {
									$fileSize = round( $fileSize / 1000 ) . " KB";
								} elseif ( $fileSize < 1000000000 ) {
									$fileSize = round( $fileSize / 1000000, 1 ) . " MB";
								} else {
									$fileSize = round( $fileSize / 100000000, 2 ) . " GB";
								}
								?>
                                <tr class="josxharfaTable josxharfaTableRow">
                                    <td class="josxharfaTable josxharfaTableRow">
                                        <a style='text-decoration: none' target='_blank' href='<?php echo $fileUrl; ?>'>
                                            <?php echo $file; ?>
                                        </a>
                                    </td>
                                    <td class="josxharfaTable josxharfaTableRow">
                                        <p class="josxharfaRowTextRight"><?php echo $fileSize ?></p></td>
                                    <td class="josxharfaTable josxharfaTableRow">
                                        <p class="josxharfaRowTextRight"><?php echo $uploadedAt; ?></p>
                                    </td>
                                    <td class="josxharfaTable josxharfaTableRow">
                                        <a title="Copy the URL to the clipboard" href='' onclick='josxhaRfaCopy(event,"<?php echo $fileUrl; ?>"); return false;'>
                                            <img src='<?php echo $imgCopy; ?>' style='height: 15px; width: 15px; padding: 4px' alt="URL kopieren">
                                        </a>
                                    </td>
                                    <td class="josxharfaTable josxharfaTableRow">
                                        <form method="post" action="?page=<?php echo JOSXHARFA_PLUGIN_NAME ?>">
                                            <input type="hidden" name="deleteFile" value="<?php echo $file; ?>">
                                            <button class="josxharfaImageButton" title="Delete file" type="submit">
                                                <img src='<?php echo $imgDelete; ?>' style='height: 15px; width: 15px; padding: 4px' alt="Löschen">
                                            </button>
                                        </form>
                                    </td>
                                </tr>
								<?php
							}
						}
						?>
                    </table>
                </div>

                <div id="settings" class="tab-pane">
                    <form action="?page=<?php echo JOSXHARFA_PLUGIN_NAME ?>#settings" enctype="multipart/form-data" method="post" id="formSettings">
                        <h2>With unauthorized access</h2>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding-bottom: 10px">
                                    <label class="josxhaText">
                                        <input
                                               type="radio"
	                                            <?php if ($settings['onAccess']['action'] === "text") echo "checked";  ?>
                                               value="text"
                                               name="onAccess[action]">
                                        Show message (html)
                                    </label>
                                </td>
                                <td style="padding-bottom: 10px">
                                    <label class="josxhaText">
                                        <input
                                                type="radio"
	                                            <?php if ($settings['onAccess']['action'] === "redirect") echo "checked";  ?>
                                                value="redirect"
                                                name="onAccess[action]">
                                        Forwarding
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="josxhaText">
                                        <textarea
                                                maxlength="10000"
                                                placeholder="<?php echo JOSXHARFA_NOT_PERMITTED_DEFAULT_TEXT ?>"
                                                name="onAccess[text]"
                                                style="width: 90%; min-height: 50px"
                                        ><?php echo stripslashes($settings['onAccess']['text']); ?></textarea>
                                    </label>
                                </td>
                                <td style="vertical-align: top">
                                    <label class="josxhaText">
                                        <input
                                                style="width: 50%"
                                                type="text"
                                                name="onAccess[url]"
                                                value="<?php echo stripslashes($settings['onAccess']['url']); ?>"
                                                placeholder="<?php echo JOSXHARFA_DEFAULT_URL ?>">
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <br><br>

                        <h2>If file does not exist</h2>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding-bottom: 10px">
                                    <label class="josxhaText">
                                        <input
                                                value="text"
	                                            <?php if ($settings['notFound']['action'] === "text") echo "checked";  ?>
                                                type="radio"
                                                name="notFound[action]">
                                        Show message (html)
                                    </label>
                                </td>
                                <td style="padding-bottom: 10px">
                                    <label class="josxhaText">
                                        <input
                                                value="redirect"
	                                            <?php if ($settings['notFound']['action'] === "redirect") echo "checked";  ?>
                                                type="radio"
                                                name="notFound[action]">
                                        Forwarding
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="josxhaText">
                                        <textarea
                                                placeholder="<?php echo JOSXHARFA_FILE_NOT_FOUND_DEFAULT_TEXT ?>"
                                                maxlength="10000"
                                                name="notFound[text]"
                                                style="width: 90%;
                                                min-height: 50px"
                                        ><?php echo stripslashes($settings['notFound']['text']); ?></textarea>
                                    </label>
                                </td>
                                <td style="vertical-align: top">
                                    <label class="josxhaText">
                                        <input
                                                style="width: 50%"
                                                type="text"
                                                name="notFound[url]"
                                                value="<?php echo stripslashes($settings['notFound']['url']); ?>"
                                                placeholder="<?php echo JOSXHARFA_DEFAULT_URL ?>">
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <br><br>

                        <h2>Allowed user groups</h2>
						<?php
                        $allowedRoles = $settings['userRole'];
                        foreach ( josxharfa_get_wordpress_roles() as $roleName => $roleData ) {
                            ?>
                            <label class="josxhaText" style="padding-right: 30px">
                                <input
                                        value="1"
                                        <?php if (key_exists($roleName, $allowedRoles) && $allowedRoles[$roleName]) echo "checked";  ?>
                                        name="userRole[<?php echo $roleName ?>]"
                                        type="checkbox">
                                <?php echo $roleData["name"] ?>
                            </label>
						<?php } ?>

                        <br><br><br>
                        <button class="button button-primary button-large" type="submit" form="formSettings">Speichern</button>
                    </form>
                </div>

                <div id="help" class="tab-pane">
                    <h2>Support</h2>
                    <p class="josxhaText">Do you need help or have you found a mistake? This plugin has a forum below <a target="_blank" href="https://wordpress.org/support/plugin/restrict-file-access/">https://wordpress.org/support/plugin/restrict-file-access/</a></p>
                    <br>
                    <h2>Expand</h2>
                    <p class="josxhaText">Would you like to expand the plugin or fix bugs? The source code is managed on GitHub: <a target="_blank" href="https://github.com/josxha/WordPress-Plugin-File-Secure">https://github.com/josxha/WordPress-Plugin-File-Secure</a></p>
                </div>
            </div>
        </div>
		<?php
	}
}

if ( is_admin() ) {
	new JosxhaRfaAdmin();
}