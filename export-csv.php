<?php
if( is_admin() ) {
	header("Content-type: application/force-download");
	header('Content-Disposition: inline; filename="subscribers'.date('YmdHis').'.csv"');
	$filename = $_REQUEST['file'];
	$file = fopen(dirname(__FILE__)."/".$filename,'r');
	$contents = fread($file, filesize($filename));
	echo $contents;
}
else {
	echo "Unauthorized access";
}
?>