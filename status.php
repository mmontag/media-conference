<?
require("_config.include.php");
require("_functions.include.php");
?>
<html>
<head><title><?=$CONFERENCE_TITLE?> Adjudication Status</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<h1><?=$CONFERENCE_TITLE?> Adjudication Status</h1>

<h2><?=$CONFERENCE_TITLE?> Judge Summary <? if($_GET['view'] == "ascap") { echo "(Student Submissions)"; } ?></h2>
<a href="status.php?view=ascap">View ASCAP judging</a> 
<p>
<table>
<tr>
<td>Judge ID</td>
<td>Name</td>
<td>Ratings Completed</td>
<td>Average Rating</td>
<td>Std. Deviation</td>
<td>Distribution</td>
</tr>
<?


// JUDGE TABULATION

$judge_min = "1";
$q = "select judge_id, first_name, last_name, avg(rating) as `ar`, stddev(rating) as `sdr`, count($ratings_table.id) as `cr` from $ratings_table left join $judges_table on $judges_table.id = $ratings_table.judge_id where judge_id >= $judge_min and rating > 0 group by judge_id";
$r = mysql_query($q);
while($row = mysql_fetch_assoc($r)) {
	
	unset($hs, $histhtml, $max, $ra);
	$q4 = "select rating, count(*) as `c` from $ratings_table where judge_id = '".$row['judge_id']."' group by rating order by rating asc";
	$r4 = mysql_query($q4);
	while($row4 = mysql_fetch_assoc($r4)) {
		if(!is_numeric($row4['rating'])) continue;
		$ra = $row4['rating'];
		$hs[$ra] = $row4['c'];
		$max = max($max, $row4['c']);
	}
	for($i = 1; $i <= 10; $i++) {
		$h = $hs[$i];
		$histhtml .= "<span style='display: inline-block; overflow: hidden; margin-right: 2px; background: red; width: 6px; height:".round(24*$h/$max)."px'></span>";
	}
	$judges_html .= "<tr>
	<td>". $row['judge_id'] . "</td>
	<td>". $row['first_name'] . " " . $row['last_name'] . "</td>
	<td>" . $row['cr'] . "</td>
	<td>" . $row['ar'] . "</td>
	<td>" . $row['sdr'] . "</td>
	<td><span style='position: absolute; background-color: #eee; height: 24px; width: 80px; display:inline-block; z-index:-1;'></span>" . $histhtml . "</td>
	</tr>";
}

?>
<?= $judges_html ?>
</table>

<h2><?=$CONFERENCE_TITLE?> Entry Rank Summary</h2>

<table>
<tr>
	<td>Entry ID</td>
	<td><a href="status.php?sort=type">Type</a></td>
	<td>Contact</td>
	<td><a href="status.php?sort=avg">Avg Rating</a></td>
	<td><a href="status.php?sort=max">Max Rating</a></td>
	<td>Num Ratings</td>
	<td>Title</td>
	<td>Student</td>
	<td>Duration</td>
	<td>Providing Performers</td>
<!--<td>Topic</td>
	<td>Headphone Concert</td>-->
	<td>Media</td>
</tr>
<?

// ENTRY TABULATION
$upload_file_array = uploadScan($upload_relative_path);
$form_names_to_ids = array_flip($form_ids_to_names);

ob_start();

$order_key = $_GET['sort'] ? $_GET['sort'] : "avg";
$order_map = array(
 "avg" =>	"order by avg(rating) desc, count(rating) desc",
 "max" =>	"order by max(rating) desc, avg(rating) desc, count(rating) desc",
 "type" =>	"order by form_id asc, avg(rating) desc, count(rating) desc"
);
if(array_key_exists($order_key, $order_map)) {
	$order_query = $order_map[$order_key];
}

$q = "select email, form_id, entry_id, avg(rating) as `ar`, max(rating) as `mr`, count(rating) as `nr` from $ratings_table left join ".$wp_table_prefix."cformssubmissions on entry_id = ".$wp_table_prefix."cformssubmissions.id where judge_id >= $judge_min group by entry_id $order_query";
$r = mysql_query($q);
while($row = mysql_fetch_assoc($r)) {


	unset($fname, $lname, $rating, $judges, $ratings, $d, $linkhtml, $mediahtml, $supplementalhtml);

	$id = $row['entry_id'];
	$sub_id = $id;
	if(in_array($sub_id, $bypass)) continue;
	$type = $form_ids_to_names[$row['form_id']];

	$ar = sprintf("%0.2f",$row['ar']);

	
	// get additional info from submissions table
	$q2 = "select * from ".$wp_table_prefix."cformsdata where sub_id = '$id'";
	$r2 = mysql_query($q2);
	while($row2 = mysql_fetch_assoc($r2)) {
		$name = $row2['field_name'];
		$val = $row2['field_val'];

		if($name == "First Name<!1>")
			$fname = htmlspecialchars(stripslashes($val));
		else if($name == "Last Name<!1>") 
			$lname = htmlspecialchars(stripslashes($val));
		else if($name == "Title of Work") 
			$d['title'] = stripslashes($val);
		else if($name == "Is this a student submission?") 
			$d['student'] = $val;
		else if($name == "Duration (minutes:seconds)") 
			$d['duration'] = stripslashes($val);
		else if($name == "Will you provide all performers?") 
			$d['providing'] = $val;
		else if($name == "Select a Topic") 
			$d['topic'] = $val;
		else if($name == "Would you consider presenting this work on a headphone concert?") 
			$d['headphone'] = $val;
	}
	
	//external video link
	if($row2['field_name'] == "Video Submission<br> (web link)") {
		if($row2['field_val']) { 
			$linkhtml = 
			//"<a href='{$row2['field_val']}' target=_blank>external media</a>";
			auto_link(stripslashes($row2['field_val']));
			$linkhtml = "<span class='label'>External Link:</span> $linkhtml <br>";
		} else { 
			$linkclass = "class='empty'"; 
		}
	}
	
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

	
	// get every rating
	$q3 = "select * from $ratings_table left join $judges_table on judge_id = $judges_table.id where entry_id = '$id' and judge_id >= $judge_min";
	$r3 = mysql_query($q3);
	while($row3 = mysql_fetch_assoc($r3)) {
		if($row3['rating'] > 0) 
			$ratings[] = $row3['rating'];
			$judges[] = $row3['first_name'] . " " . $row3['last_name'];
	}
	if(is_array($ratings)) $rating = "<small>(" . implode(", ", $ratings) . ")</small>";
	
	echo("
	<tr class='" . strtolower($type) . "'>
	<td>". $id . "</td>
	<td>". $type . "</td>
	<td> $fname <small style='text-transform:uppercase'>$lname</small> <br><small><a href='mailto:". $row['email'] . "'>". $row['email'] . "</a></small></td>
	<td><big><b>$ar</b></big><br>$rating</td>
	<td>" . $row['mr']. "</td>
	<td>" . $row['nr'] . "</td>
	<td>" . $d['title'] . "</td>
	<td>" . $d['student'] . "</td>
	<td>" . $d['duration'] . "</td>
	<td>" . $d['providing'] . "</td>
<!--<td>" . $d['topic'] . "</td>
	<td>" . $d['headphone'] . "</td>-->
	<td>" . $linkhtml . $mediahtml . $supplementalhtml . "</td>
	</tr>");
	
	ob_flush();
}

?>
</table>
</body>
</html>