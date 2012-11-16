<?php
require("_config.include.php");
require("_functions.include.php");
echo mysql_error();

// get judge information
$query = "SELECT * FROM `$judges_table` WHERE `key` = '{$_GET['key']}'";
$result = mysql_query($query);
echo mysql_error();
if(@mysql_num_rows($result) == 0) {
	if($_GET['key']) {
		?>
		Invalid judging key. Please email <a href="mailto:<?=$ADMIN_EMAIL?>"><?=$ADMIN_NAME?></a> if you have difficulty logging in.
		<?
	}
	?>
	<p>
	<form action="judge.php" method=get>
	Please enter your <?=$CONFERENCE_TITLE?> Judging Key: <input name="key"><input type="submit" value="Submit">
	</form>
	<?
	die();
}

$upload_file_array = uploadScan($upload_relative_path);

$row = mysql_fetch_assoc($result);
$judge_id = $row['id'];
$judge_name = "{$row['first_name']} {$row['last_name']}";

if ($row['entries'] == '*') {
	$query = "SELECT `id` FROM `".$wp_table_prefix."cformssubmissions` GROUP BY `id`";
	$result = mysql_query($query);
	while($row = mysql_fetch_assoc($result)) {
		$judge_entries[] = $row['id'];
	}
} else {
	$judge_entries = split(",", $row['entries']); // entries this judge is responsible for
		array_walk($judge_entries, 'trim_value');
		function trim_value(&$value) { $value = trim($value); }
}

$judge_completed[$row['completed']] = "checked";
$judge_reviewed = 0;
$welcome_html = "Welcome, $judge_name!";

// process rating request
if($_POST['save']) {

	// get existing rating records
	$query = "SELECT * FROM `$ratings_table` WHERE judge_id = '$judge_id'";
	$result = mysql_query($query);
	echo mysql_error();
	
	while($row = mysql_fetch_assoc($result)) {
		$existing_entries[] = $row['entry_id'];
	}
	
	// scan through form input for each of this judge's entries
	foreach($judge_entries as $entry_id) {
	
		$entry_id = trim($entry_id);
		$rating = $_POST[$entry_id.'_rating'] + 0.0;
		if($rating < 1 || !is_numeric($rating)) $rating = "NULL";
		
		if(is_array($existing_entries) && in_array($entry_id, $existing_entries)) {
			$query = "
			UPDATE `$ratings_table` 
			SET 
				rating 			= $rating, 
				private_notes 	= '{$_POST[$entry_id.'_private_notes']}',
				public_notes 	= '{$_POST[$entry_id.'_public_notes']}',
				flag 			= '{$_POST[$entry_id.'_flag']}'
			WHERE 
				judge_id = '$judge_id' 
				AND entry_id = '$entry_id' 
				LIMIT 1";
		} else {
			$query = "
			INSERT INTO `$ratings_table` 
				(rating, private_notes, public_notes, flag, judge_id, entry_id)
			VALUES
				($rating,
				'{$_POST[$entry_id.'_private_notes']}',
				'{$_POST[$entry_id.'_public_notes']}',
				'{$_POST[$entry_id.'_flag']}',
				'$judge_id',
				'$entry_id')";
		}
		$result = mysql_query($query);
		
		// echo "<p> debug::  $query <p>";
		// echo "<p>".mysql_error();
		$rows_affected += mysql_affected_rows();
	}
	if($rows_affected != 1) $plural = "s";
	$message_html = "<div class='message'>$rows_affected record$plural updated.</div>"; // disable this?

	// update rating completion status
	if($_POST['completed']) 
		{ $completed = 1; } else { $completed = 0; }
	$query = "UPDATE `$judges_table` SET completed = '$completed' WHERE id = '$judge_id'";
	$result = mysql_query($query);
	if(mysql_affected_rows() > 0) { 
		$message_html .= "<div class='message'>Completion status updated.</div>"; 
		// if commit was successful, update completion for HTML since we won't be reloading this from db
		if($completed == 1) { $judge_completed[1] = "checked"; } else { $judge_completed[1] = ""; }
	}
}

// get existing rating values for html output
$query = "SELECT * FROM `$ratings_table` WHERE judge_id = '$judge_id'";
$result = mysql_query($query);
while($row = mysql_fetch_assoc($result)) {
	$entry_id = $row['entry_id'];
	$html[$entry_id.'_rating'] 			= db2html($row['rating']);
	$html[$entry_id.'_private_notes'] 	= db2html($row['private_notes']);
	$html[$entry_id.'_public_notes'] 	= db2html($row['public_notes']);
	$html[$entry_id.'_flag'] 			= db2html($row['flag']);
	if($row['flag'] == 2) $judge_reviewed++;
}

// get entry information for this judge's entries
foreach($judge_entries as $entry) {
	if($entries_query_string) $entries_query_string .= "OR ";
	$entries_query_string .= "id = '$entry' ";
}

//$query = "SELECT f_id, sub_id FROM ".$wp_table_prefix."cformsdata 
//			WHERE $entries_query_string 
//			GROUP BY sub_id ORDER BY f_id";

$query = "SELECT id, form_id FROM ".$wp_table_prefix."cformssubmissions
			WHERE $entries_query_string";
$result = mysql_query($query);
while($row = mysql_fetch_assoc($result)) {

	$sub_id = $row['id'];
	$form_id = $row['form_id'];
	if($form_id == "") $form_id = 1;
	if(!array_key_exists($form_id, $form_ids_to_names)) continue; // if this form ID doesn't match form IDs of interest, skip the row
	$form_name = $form_ids_to_names[$form_id];
	if($form_id == 2) { $paper = true; } else { $paper = false; }
	
	//if this entry is NOT in the bypass array
	if(!in_array($sub_id, $bypass)) {
		unset($caption, $linkhtml, $titleofwork, $titleofpresentation, $abstract);
		unset($mediafiles, $mediahtml, $mediaclass, $linkclass);
		unset($supplemental, $supplementalhtml, $programnoteshtml);
		unset($files, $flaghtml, $comments, $email, $flag);
		$query = "select * from ".$wp_table_prefix."cformsdata where sub_id = '$sub_id'";
		$result2 = mysql_query($query);
		while($row2 = mysql_fetch_assoc($result2)) {
			
			//duration
			if($row2['field_name'] == "Duration (minutes:seconds)")
				$duration = htmlspecialchars(stripslashes($row2['field_val']));		
				
			//external video link
			if($row2['field_name'] == "Video Submission<br> (web link)") {
				if($row2['field_val']) { 
					$linkhtml = 
					//"<a href='{$row2['field_val']}' target=_blank>external media</a>";
					auto_link(stripslashes($row2['field_val']));
					$linkhtml = "<span class='label'>External Link:</span> $linkhtml";
				} else { 
					$linkclass = "class='empty'"; 
				}
			}
			
			//title of presentation
			if($row2['field_name'] == "Title of Presentation") {
				$titleofpresentation = auto_link(stripslashes($row2['field_val']));
			}
			
			//title of work
			if($row2['field_name'] == "Title of Work") {
				$titleofwork = auto_link(stripslashes($row2['field_val']));
			}
			
			//abstract
			if($row2['field_name'] == "Abstract<br>(up to 500 words)") {
				$abstract = auto_link(stripslashes($row2['field_val']));
			}
			
			//comments
			if($row2['field_name'] == "Additional Comments") {
				$comments = auto_link(stripslashes($row2['field_val']));
			}
			
			//email
			if($row2['field_name'] == "Email<!1>") {
				$email = htmlspecialchars(stripslashes($row2['field_val']));
			}
			
			//program notes
			if($row2['field_name'] == "Program Notes<br> (up to 500 words)") {
				$programnoteshtml = nl2br(htmlspecialchars(stripslashes(trim($row2['field_val']))));
				
				if (strlen($programnoteshtml) > 200) {
					$pnshort = substr($programnoteshtml, 0, 200);
					
					$programnoteshtml = "<div id='notes$sub_id' style='display:none'>$programnoteshtml <a href='javascript:void(0);' onClick='document.getElementById(\"notes$sub_id\").style.display = \"none\"; document.getElementById(\"notes_short$sub_id\").style.display = \"block\";'>Less</a></div>
						<div id='notes_short$sub_id' style='display:block'>$pnshort... <a href='javascript:void(0);' onClick='document.getElementById(\"notes$sub_id\").style.display = \"block\"; document.getElementById(\"notes_short$sub_id\").style.display = \"none\";'>More</a></div>";
				}
			}
		}
		//scan filesystem for submission materials
		$files = $upload_file_array[$sub_id];
		if(is_array($files)) {
			$m = 1; $s = 1;
			foreach($files as $key=>$file) {
				if(substr($file,0,1) == ".") continue;
				$expart = substr($file,strrpos($file,"."));
				$filepart = substr($file,strrpos($file,"/")+1);
				$file = htmlspecialchars($file, ENT_QUOTES);
				if(in_array($expart, $media)){
					$mediahtml .="<nobr><a href='get.php?f=$sub_id,$key'>Media File ". $m++ ."$expart</a></nobr><br>\n";
				} else {
					$supplementalhtml .="<nobr><a href='get.php?f=$sub_id,$key'>Supplemental File ". $s++ ."$expart</a></nobr><br>\n";
				}
			}
		}
		
		//tag paper submissions
		if($paper) {
			$captionhtml = "<span class='label'>Paper Submission</span>";
			$contenthtml = "<td colspan=2><b>$titleofpresentation</b><p>$abstract";
			if($mediahtml) $contenthtml .= "<p>$mediahtml";
			if($supplementalhtml) $contenthtml .= "<p>$supplementalhtml";
			$contenthtml .= "</td>";
		} else {
			$captionhtml = "<span class='label'><br>Duration:</span><br>$duration";
			$contenthtml = "<td $mediaclass><small>$mediahtml<p>
				$supplementalhtml</small></td>
			<td><b>$titleofwork</b><p><small>$programnoteshtml</small></td>";
		}
		
		if(!$mediahtml && !$supplementalhtml) { $mediaclass="class='empty'"; }
		
		//flag submissions with no media
		if(!$mediahtml && !$supplementalhtml && !$linkhtml && !$paper) {
			$flaghtml = "class='flagged'";
			$linkhtml = "<small><em>$comments</em></small>";
			// Email address hidden - montag - Oct 29 1:08 PM
			// $linkhtml = "<small><a href='mailto:$email'>$email</a><br><em>$comments</em></small>";
		}
		
		//set flag checked status
		if(!$html[$sub_id.'_flag']) $html[$sub_id.'_flag'] = "0";
		$flag[$html[$sub_id.'_flag']] = "checked";
		
		//display section
		$html_entries .= "
			<tr style='border-top: 2px solid #aaa' $flaghtml>
			<td rowspan=2 id='$sub_id"."_idcell' class='flag".$html[$sub_id.'_flag']."'><span class='id'>$sub_id</span><br>
				$captionhtml</td>
			$contenthtml
			<td>
				<select name='$sub_id"."_rating' style='font-size: 150%' onChange='setCheckedValue(document.forms[\"ratings\"].elements[\"$sub_id"."_flag\"],\"2\"); document.getElementById(\"$sub_id"."_idcell\").className = \"flag\"+getCheckedValue(document.forms[\"ratings\"].elements[\"$sub_id"."_flag\"]);'>
					<option value='{$html[$sub_id.'_rating']}'>{$html[$sub_id.'_rating']}
					<option value=''>----</option>
					<option value='1'>1
					<option value='2'>2
					<option value='3'>3
					<option value='4'>4
					<option value='5'>5
					<option value='6'>6
					<option value='7'>7
					<option value='8'>8
					<option value='9'>9
					<option value='10'>10
				</select>
			</td>
			<td>
				Notes <small>(may be viewed by conference host)</small>: <br>
				<textarea name='$sub_id"."_public_notes' class='text'>{$html[$sub_id.'_public_notes']}</textarea><br>
				Private Notes: <br>
				<textarea name='$sub_id"."_private_notes' class='text'>{$html[$sub_id.'_private_notes']}</textarea>
			</td>
			<td><small>
				<nobr><input type='radio' name='$sub_id"."_flag' value='0' {$flag[0]} 
					onChange='document.getElementById(\"$sub_id"."_idcell\").className = \"flag\"+getCheckedValue(document.forms[\"ratings\"].elements[\"$sub_id"."_flag\"]).toString()'> 
					<span class='flag0'>Unread</span></nobr><br>
				<nobr><input type='radio' name='$sub_id"."_flag' value='1' {$flag[1]}
					onChange='document.getElementById(\"$sub_id"."_idcell\").className = \"flag\"+getCheckedValue(document.forms[\"ratings\"].elements[\"$sub_id"."_flag\"]).toString()'> 
					<span class='flag1'>Needs Review</span></nobr><br>
				<nobr><input type='radio' name='$sub_id"."_flag' value='2' {$flag[2]}
					onChange='document.getElementById(\"$sub_id"."_idcell\").className = \"flag\"+getCheckedValue(document.forms[\"ratings\"].elements[\"$sub_id"."_flag\"]).toString()'> 
					<span class='flag2'>Done</span></nobr><br>
			</td>
			</tr>
			<tr $flaghtml><td colspan=6 $linkclass>$linkhtml</td></tr>";
	}
}

?>
<html>
<head><title><?=$CONFERENCE_TITLE?> Judges' Portal</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<script>
// return the value of the radio button that is checked
// return an empty string if none are checked, or
// there are no radio buttons
function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}

// set the radio button with the given value as being checked
// do nothing if there are no radio buttons
// if the given value does not exist, all the radio buttons
// are reset to unchecked
function setCheckedValue(radioObj, newValue) {
	if(!radioObj)
		return;
	var radioLength = radioObj.length;
	if(radioLength == undefined) {
		radioObj.checked = (radioObj.value == newValue.toString());
		return;
	}
	for(var i = 0; i < radioLength; i++) {
		radioObj[i].checked = false;
		if(radioObj[i].value == newValue.toString()) {
			radioObj[i].checked = true;
		}
	}
}
</script>
<h1><?=$CONFERENCE_TITLE?> Judges' Portal</h1>
<h2><?= $welcome_html ?></h2>
<p><?= count($judge_entries) ?> assigned submissions; <?= $judge_reviewed ?> reviewed.
<?= $message_html ?>
<!-- <em><span class="flagged" style="display: inline-block; height: 16px; width: 16px;"></span> = No supporting media found</em> -->
<p>
<small><em>The Judges' Portal is designed to let you save your ratings so you can close your browser and pick up where you left off later. The color-coded entry flag is designed to help you keep track of your progress as you review submissions. All submissions start as Unread, and switch to Done when you assign a rating. You can override this function by setting the flag manually in the rightmost column.</em></small>
<p>
<em><span class="flag0" style="display: inline-block; height: 16px; width: 16px;"></span> = Unread</em> &nbsp;
<em><span class="flag1" style="display: inline-block; height: 16px; width: 16px;"></span> = Needs Review</em> &nbsp;
<em><span class="flag2" style="display: inline-block; height: 16px; width: 16px;"></span> = Done</em> &nbsp;
<em><span class="flagged" style="display: inline-block; height: 16px; width: 16px;"></span> = No Supporting Media <small>(Check artist's notes)</small></em>
<p>
<form name="ratings" action="judge.php?key=<?=$_GET['key']?>" method="post">
<table>
<thead><tr>
	<td>ID</td>
	<td colspan=2>Media Files/Abstract/Program Notes</td>
	<td>Rating</td>
	<td>Judge's Notes</td>
	<td>Flag</td>
</tr></thead>

<?= $html_entries ?>

</table>
<p>
<input type="checkbox" name="completed" value="Mark as completed" <?=$judge_completed[1]?>> Mark my ratings as completed <br>
<input type="submit" name="save" value="Save Changes" style="font-size: 150%; margin-top: 10px;"> 
</form>
<p style="color: gray; font-size: 70%; font-style: italic;"><?=$CONFERENCE_TITLE?> Judges' Portal - contact matt.montag@gmail.com for support.</p>
</html>