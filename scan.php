<?php
require("_config.include.php");

$file_array = uploadScan("2013/upload");

function uploadScan($dir) {
	global $media;
	$filemap = array();
	@$files = scandir($dir);
	foreach($files as $file) {
		if(substr($subfile,0,1)==".") continue;
		$prefix = sprintf("%d", substr($file, 0, strpos($file, "-")));
		if(is_numeric($prefix) && $prefix > 0) {
			$filemap[$prefix][] = "$dir/$file";
		} else if(is_dir("$dir/$file") && is_numeric($file) && $file > 0) {
			echo "scanning dir $dir/$file ... <br>";
			$prefix = sprintf("%d",$file);
			$subfiles = scandir("$dir/$file");
			foreach($subfiles as $subfile) {
				if(is_dir($subfile)) continue;
				if(substr($subfile,0,1)==".") continue;
				$filemap[$prefix][] = "$dir/$file/$subfile";
			}
		}
	}
	return $filemap;
}


?>
<pre><? print_r($file_array) ?></pre>