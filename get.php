<?php
/*
File cloaking script - to hide filenames
This script requires PHP 5.3 or the PECL fileinfo.so extension.
*/
	require("_config.include.php");
	require("_functions.include.php");
	$f = $_GET["f"];
	list($idx1,$idx2) = explode(",",$f);
	$file_array = uploadScan($upload_relative_path);
	$file = $file_array[$idx1][$idx2];
	$extension = substr($file,strrpos($file,"."));
	$downloadname = "document-$idx1-$idx2$extension";
	$finfo = finfo_open(FILEINFO_MIME);
//echo "$idx1 $idx2<br>";
//echo "$file<br>";
//echo finfo_file($finfo,$file)."<br>";
//echo "<pre>"; print_r($file_array);
//die();
	if(!file_exists($file)) die("Couldn't locate this file.");
	header("Content-Type: ".finfo_file($finfo, $file));
	header("Content-Length: ".filesize($file));
	header("Content-Disposition: inline; filename=\"$downloadname\"");
	readfile($file);
	die();
?>