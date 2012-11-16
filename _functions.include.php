<?php

//============ generic functions ==============

function auto_link($text) {
  $pattern = "/(((http[s]?:\/\/)|(www\.))?(([a-z][-a-z0-9]*\.)?[a-z][-a-z0-9]+\.[a-z]+(\.[a-z]{2,2})?)\/?[a-z0-9._\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1})/is";
  $text = preg_replace($pattern, " <a href='$1'>$1</a>", $text);
  // fix URLs without protocols
  $text = preg_replace("/href=\"www/", "href=\"http://www", $text);
  return $text;
}

function db2html($string) {
	$string = htmlspecialchars($string, ENT_QUOTES);
	//$string = nl2br($string);
	return $string;
}

function UTF8ToEntities($string) {
    /* note: apply htmlspecialchars if desired /before/ applying this function
    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string))
        return $string;
    
    // reject too-short sequences
    $string = preg_replace("/[\302-\375]([\001-\177])/", "&#65533;\\1", $string); 
    $string = preg_replace("/[\340-\375].([\001-\177])/", "&#65533;\\1", $string); 
    $string = preg_replace("/[\360-\375]..([\001-\177])/", "&#65533;\\1", $string); 
    $string = preg_replace("/[\370-\375]...([\001-\177])/", "&#65533;\\1", $string); 
    $string = preg_replace("/[\374-\375]....([\001-\177])/", "&#65533;\\1", $string); 
    
    // reject illegal bytes & sequences
        // 2-byte characters in ASCII range
    $string = preg_replace("/[\300-\301]./", "&#65533;", $string);
        // 4-byte illegal codepoints (RFC 3629)
    $string = preg_replace("/\364[\220-\277]../", "&#65533;", $string);
        // 4-byte illegal codepoints (RFC 3629)
    $string = preg_replace("/[\365-\367].../", "&#65533;", $string);
        // 5-byte illegal codepoints (RFC 3629)
    $string = preg_replace("/[\370-\373]..../", "&#65533;", $string);
        // 6-byte illegal codepoints (RFC 3629)
    $string = preg_replace("/[\374-\375]...../", "&#65533;", $string);
        // undefined bytes
    $string = preg_replace("/[\376-\377]/", "&#65533;", $string); 

    // reject consecutive start-bytes
    $string = preg_replace("/[\302-\364]{2,}/", "&#65533;", $string); 
    
    // decode four byte unicode characters
    $string = preg_replace(
        "/([\360-\364])([\200-\277])([\200-\277])([\200-\277])/e",
        "'&#'.((ord('\\1')&7)<<18 | (ord('\\2')&63)<<12 |" .
        " (ord('\\3')&63)<<6 | (ord('\\4')&63)).';'",
    $string);
    
    // decode three byte unicode characters
    $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
"'&#'.((ord('\\1')&15)<<12 | (ord('\\2')&63)<<6 | (ord('\\3')&63)).';'",
    $string);
    
    // decode two byte unicode characters
    $string = preg_replace("/([\300-\337])([\200-\277])/e",
    "'&#'.((ord('\\1')&31)<<6 | (ord('\\2')&63)).';'",
    $string);
    
    // reject leftover continuation bytes
    $string = preg_replace("/[\200-\277]/", "&#65533;", $string);
    
    return $string;
}

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