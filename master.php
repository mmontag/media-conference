<?php
require("_config.include.php");
require("_functions.include.php");
?>
<html>
<head><title><?=$CONFERENCE_TITLE?> Judges' Portal</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<h1><?=$CONFERENCE_TITLE?> Judges' Portal</h1>
<h2>Submission Index</h2>
<p>This is the master submission index, designed for identification of duplicate and incomplete submissions.  Uploaded materials and external links are displayed for each submission. If a submission lacks both uploads and external links, an email address and notes from 'Additional Comments' are displayed to help track down the author and resolve discrepancies.
<p><em><span class="flagged" style="display: inline-block; height: 16px; width: 16px;"></span> = No supporting media found</em>
<p>
<table>
<thead><tr><td>ID</td><td>Duration</td><td>Files and External Links</td><td>Email and Additional Comments</td></tr></thead>
<?

$upload_file_array = uploadScan($upload_relative_path);

//$query = "select f_id, sub_id from ".$wp_table_prefix."cformsdata group by sub_id order by f_id";
$query = "select * from ".$wp_table_prefix."cformssubmissions";
$result = mysql_query($query);

ob_start();
while($row = mysql_fetch_assoc($result)) {

	$sub_id = $row['id'];
	if($row['form_id'] == "") $row['form_id'] = 1; 
	$form_name = $form_ids_to_names[$row['form_id']];
	
	//if this entry is NOT in the bypass array
	if(!in_array($sub_id, $bypass)) {
		unset($titleofwork, $duration, $linkhtml);
		unset($mediafiles, $mediahtml);
		unset($supplemental, $supplementalhtml);
		unset($files, $flaghtml, $comments, $email);
		$query = "select * from ".$wp_table_prefix."cformsdata where sub_id = '$sub_id'";
		$result2 = mysql_query($query);
		while($row2 = mysql_fetch_assoc($result2)) {
		
			
			//title
			if($row2['field_name'] == "Title of Work")
				$titleofwork = "<b>".htmlspecialchars(stripslashes($row2['field_val']))."</b><br>";
			
			//duration
			if($row2['field_name'] == "Duration (minutes:seconds)")
				$duration = htmlspecialchars(stripslashes($row2['field_val']));
				
			//external video link
			if($row2['field_name'] == "Video Submission<br> (web link)") {
				$linkhtml = auto_link(stripslashes($row2['field_val']));
			}
			
			//comments
			if($row2['field_name'] == "Additional Comments") {
				$comments = auto_link(stripslashes($row2['field_val']));
			}
			
			//name
			if($row2['field_name'] == "First Name<!1>") {
				$fname = htmlspecialchars(stripslashes($row2['field_val']));
			}
			if($row2['field_name'] == "Last Name<!1>") {
				$lname = htmlspecialchars(stripslashes($row2['field_val']));
			}
			
			//email
			if($row2['field_name'] == "Email<!1>") {
				$email = htmlspecialchars(stripslashes($row2['field_val']));
			}
		}
		
		//scan filesystem for submission materials
		$files = $upload_file_array[$sub_id];
		if(is_array($files)) {
			foreach($files as $file) {
				if(substr($file,0,1) == ".") continue;
				$expart = substr($file,strrpos($file,"."));
				$filepart = substr($file,strrpos($file,"/")+1);
				$file = htmlspecialchars($file, ENT_QUOTES);
				if(in_array($expart, $media)){
					$mediahtml .="<a href='$file'>$filepart</a><br>\n";
				} else {
					$supplementalhtml .="<a href='$file'>$filepart</a><br>\n";
				}
			}
		}
		
		//flag submissions with no media
		if(!$mediahtml && !$supplementalhtml && !$linkhtml) {
			$flaghtml = "class='flagged'";
		}
		
		//always display comments...?
		$commenthtml = "<small>$fname $lname / <a href='mailto:$email'>$email</a><br><em>$comments</em></small>";
		
		//display section
		?>
		<tr <?=$flaghtml?>>
		<td class="<?=$form_name?>"><strong><?=$sub_id?></strong><br><small><?=$form_name?></small></td>
		<td><?=$duration?></td>
		<td>
			<?= $titleofwork ?>
			<? if($mediahtml) { ?><span class="media"><?=$mediahtml?></span><? } ?>
			<? if($mediahtml && $supplementalhtml) echo "<hr>" ?>
			<span class="supplemental"><?=$supplementalhtml?></span>
			<? if($supplementalhtml && $linkhtml || $linkhtml && $mediahtml) echo "<hr>" ?>
			<span class="external"><?=$linkhtml?></span></td>
		<td><?=$commenthtml?></a></td>
		</tr>
		<?
	}
	ob_flush();
}

?>

</table>
</body>
</html>