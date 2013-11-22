<div class="wrap">
	<?php screen_icon( 'themes' ); ?>
	<h2>Subscribers</h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<form method="post" action="?page=<?php echo esc_js(esc_html($_GET['page'])); ?>">
            		<input name="dm_remove" value="1" type="hidden" />
<?php 
						/* REMOVE PROCESS */
						if ($_SERVER['REQUEST_METHOD']=="POST" and $_POST['dm_remove']) {
							if ($_GET['rem']) $_POST['rem'][] = $_GET['rem'];
							$count = 0;
							if (is_array($_POST['rem'])) {
								foreach ($_POST['rem'] as $id) { 
									$wpdb->query("delete from ".$wpdb->prefix."dm where id = '".$wpdb->escape($id)."' limit 1"); 
									$count++; 
								}
								$message = $count." subscribers have been removed successfully.";
							}
						}
						/* EXPORT PROCESS */
						$file = fopen(dirname(__FILE__)."/file.csv", "w");
						$results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."dm");
						fwrite($file, "Email Address\r\n");
						if (count($results))  {
							foreach($results as $row) {
								fwrite($file, $row->dm_email."\r\n");
							}
						}

						$message = NULL;
						if ($_SERVER['REQUEST_METHOD']=="POST" and $_POST['dm_import']) {
							$correct = 0;
							if($_FILES['file']['tmp_name']) {
								if(!$_FILES['file']['error'])  {
									$file = file_get_contents ($_FILES['file']['tmp_name']);
									$lines = preg_split('/\r\n|\r|\n/', $file);
									if (count($lines)) {
										$sql = array();
										foreach ($lines as $data) {
											$data = explode(',', $data);
											$num = count($data);
											$row++;
											
											if (is_email(trim($data[0]))) {
												$c = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."dm where dm_email LIKE '".$wpdb->escape(trim($data[0]))."' limit 1", ARRAY_A);
												if (!is_array($c)) {											
													$wpdb->query("INSERT INTO ".$wpdb->prefix."dm (dm_email) VALUES ('".$wpdb->escape(trim($data[0]))."')");
													$correct++;
												} else { $exists++; }
											} else { $invalid++; }
										}
										
									} else { $message = 'Oh no! Your CSV file does not apear to be valid, please check the format and upload again.'; }
								
									if (!$message) {
										$message = $correct.' records have been imported. '.($invalid?$invalid.' could not be imported due to invalid email addresses. ':'').($exists?$exists.' already exists. ':'');
									}
								
								} else {
									$message = 'Ooops! There seems to of been a problem uploading your csv';
								}
							}								
						}
						//echo $sql;
						if ($message) { echo '<div style="padding: 5px;" class="updated"><p>'.$message.'</p></div>'; }
						
?>
						<table cellspacing="0" class="wp-list-table widefat fixed subscribers">
                          <thead>
                            <tr>
                                <th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
                                <th style="" class="manage-column column-email" id="email" scope="col"><span>Email Address</span><span class="sorting-indicator"></span></th>
                            </thead>
                            <tfoot>
                            <tr>
                                <th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
                                <th style="" class="manage-column column-email" scope="col"><span>Email Address</span><span class="sorting-indicator"></span></th>
                            </tfoot>
                            <tbody id="the-list">
<?php 
								$results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."dm");
								if (count($results)<1) echo '<tr class="no-items"><td colspan="3" class="colspanchange">No mailing list subscribers have been added.</td></tr>';
								else {
									foreach($results as $row) {
	
										echo '<tr>
													<th class="check-column" style="padding:5px 0 2px 0"><input type="checkbox" name="rem[]" value="'.esc_js(esc_html($row->id)).'"></th>
  													<td>'.esc_js(esc_html($row->dm_email)).'</td>
											  </tr>';
									}
								}
?>
                            </tbody>
                        </table>
                        <br class="clear">
						<input class="button" name="submit" type="submit" value="Remove Selected" /> <a class="button" href="<?php echo plugins_url( 'export-csv.php?file=file.csv', __FILE__ ); ?>">Export as CSV</a>
				</form>
				<br class="clear">
                <div class="meta-box-sortables">
                        <div class="postbox">
                       	  <h3><span>Import your own CSV File</span></h3>
                          <div class="inside">
                <p>This feature allows you to import your own csv (comma seperated values) file</p>
                <form id="import-csv" method="post" enctype="multipart/form-data" action="?page=<?=esc_js(esc_html($_GET['page']));?>">
                <input name="dm_import" value="1" type="hidden" />
                <p><label><input name="file" type="file" value="" /> CSV File</label></p>
                <p class="submit"><input type="submit" value="Upload and Import CSV File" class="button-secondary" id="submit" name="submit"></p></form>
                </div></div></div>
                
         
                
			</div> 
	</div>
</div> 