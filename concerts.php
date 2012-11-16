<? 

/*
Concert information generator
Matt Montag
2:34 PM 12/20/2010
*/

require("_config.include.php");
require("_functions.include.php");

?>
<html>
<head><title><?=$CONFERENCE_TITLE?> Concert Info Generator</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<h1>Concert Information Generator</h1>

Select a Concert: 

<form action="concerts.php" method="post">
<select name="concert">
<? 
foreach($concerts as $key=>$concert) {
	$ids = implode(",",$concert);
	$key = $key+1;
	?>
	<option value="<?=$key?>;<?=$ids?>">Concert <?=$key?> (<?=$ids?>)</option>
	<?
}
?>
</select>

<p>
<input type="submit" name="submit" value="Submit">

</form>
<?

if($_POST['submit']) {

	$a = split(";",$_POST['concert']);
	$concertnum = $a[0];
	$c = split(",",$a[1]);
	
	foreach($c as $sub_id) {
		unset($title,$programnoteshtml,$biohtml,$duration,$firstname,$lastname,$bio);
	
		if($sub_id == 0) {
			$title = "[Featured Performance]";
			$firstname = "[Featured Performer]";
		} else {
	
			$query = "select * from ".$wp_table_prefix."cformsdata where sub_id = '$sub_id'";
			$result2 = mysql_query($query);
			
			while($row2 = mysql_fetch_assoc($result2)) {
				//program notes
				if($row2['field_name'] == "Title of Work") {
					$title = nl2br(UTF8ToEntities(htmlspecialchars(stripslashes($row2['field_val']))));
				}
				if($row2['field_name'] == "Program Notes<br> (up to 500 words)") {
					if($row2['field_val'])
						$programnoteshtml = nl2br(UTF8ToEntities(htmlspecialchars(stripslashes($row2['field_val']))));
					else 
						$programnoteshtml = "[no program notes]";
				}
				if($row2['field_name'] == "Bio (up to 500 words)<!1>") {
					$biohtml = nl2br(UTF8ToEntities(htmlspecialchars(stripslashes($row2['field_val']))));
				}
				if($row2['field_name'] == "Duration (minutes:seconds)") {
					$duration = nl2br(UTF8ToEntities(htmlspecialchars(stripslashes($row2['field_val']))));
				}
				
				//check all composer fields. non-unique field names must be addressed by f_id
				for($i = 2; $i <= 5; $i++)  {
					
					if($row2['field_name'] == "First Name<!$i>") { // first name
						if($i == 0) unset($composers);
						$firstname = nl2br(UTF8ToEntities(htmlspecialchars(stripslashes($row2['field_val']))));
					}
					if($row2['field_name'] == "Last Name<!$i>" && $row2['field_val']) { // last name
						$lastname = nl2br(UTF8ToEntities(htmlspecialchars(stripslashes($row2['field_val']))));
						if($composers) $composers .= ", "; $composers .= "$firstname $lastname";
					}
					if($row2['field_name'] == "Bio (up to 500 words)<!$i>" && $row2['field_val']) { // bio
						$bio = nl2br(UTF8ToEntities(htmlspecialchars(stripslashes($row2['field_val']))));
						$biolist .=		"<div class='unit'><p><strong>$firstname $lastname</strong> ($title)<p>$bio</div>";
					}
				}
								
				//additional composers
				if($row2['field_name'] == "Additional Composers" && $row2['field_val']) {
					$composers .= (", ".nl2br(UTF8ToEntities(htmlspecialchars(stripslashes($row2['field_val'])))));
				}
			}
		}
		
		$concertlist .= "<tr><td>$composers</td><td>$title</td><td>$duration</td></tr>";
		$programlist .= "<div class='unit'><p><strong>$title</strong><p>$programnoteshtml</div>";
	}
	
	echo "<h1>Generated Output: <span style='color: green'>Concert $concertnum</span></h1>";
	echo "<h2>Concert Program</h2><table>$concertlist</table>";
	echo "<h2>Program Notes</h2>$programlist";
	echo "<h2>Composer Biographies</h2>$biolist";
}

?>
</body>
</html>