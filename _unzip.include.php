<?php
require("_config.include.php");
$dir = $upload_relative_path;
$files = scandir($dir);

foreach($files as $file) {
	if(substr($file,0,1)==".") continue;
	$ext = substr($file, strrpos($file,"."));
	$prefix = substr($file, 0, strpos($file, "-"));
	if($ext == '.zip') {
		if(!is_numeric($prefix) || !$prefix > 0) {
			echo "<p>Encountered zip file with non-numeric prefix: $prefix.\n";
			continue;
		} 
		if(is_dir("$dir/$prefix")) {
			echo "<P>Directory $dir/$prefix already exists. Extracting with overwrite.\n";
			//continue;
		}
		//mkdir("$dir/$prefix");
		$cmd = "/usr/local/bin/unzip -j -o $dir/". escapeshellcmd($file) . " -d $dir/$prefix";
		echo "<p>Executing <pre>$cmd</pre>";
		$result = exec($cmd);
		rename("$dir/$file", "$dir/.$file.done");
		echo "<p>Shell unzip output: <br><pre>$result</pre>\n";
	}
}
?>