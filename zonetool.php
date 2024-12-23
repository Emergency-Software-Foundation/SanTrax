<?php
echo "<h1>SantaRouter - TimeZone Loader Tool</h1><hr>";
if (isset($_GET["src"])) {
	$handle = fopen($_GET["src"], "r");
	$contents = fread($handle, filesize($_GET["src"]));
	fclose($handle);
	$rows = explode("\n", $contents);
	$nodes = array();
	foreach($rows as $row) {
		if (!empty($row) && !startsWith($row, "#")) {
			$node = explode(",", $row);
			$nodes[] = $node;
		}
	}
	foreach($nodes as $node) {
		if (sizeof($node) === 3) {
			echo $node[0].",".$node[1].",".$node[2]."<br>";
		} else {
			set_time_limit(120);
			$tz = floor(floatval(get_timezone($node[0],$node[1])));
			echo $node[0].",".$node[1].",".$tz."<br>";
		}
	}
	echo "<p>Done! <a href='./zonetool.php'>Change Source?</a>";
	echo "<br>Done! <a href='./SantaRouter.php'>Bake Route?</a></p>";
} else {
	echo "<p>Select a file below to continue.</p>";
	$files = array_diff(scandir('.'), array('..', '.'));
	$files = array_filter($files, "endsWith");
	echo "<ul>";
	foreach ($files as $file) {
		echo "<li><a href='?src=".$file."'>".$file."</a></li>";
	}
	echo "</ul>";
}
echo "<hr><i>SanTrax 2024 Edition | ".$_SERVER['SERVER_SOFTWARE']." Server at ".$_SERVER['HTTP_HOST']." Port ".$_SERVER['SERVER_PORT']."</i>";

//New local timezone Tool (santa's clock)
function get_timezone($latitude, $longitude) {
	$url = preg_replace('/\s+/','','http://localhost:3000/api/v1/timezone?lat='.$latitude.'&lng='.$longitude);
	$resp = file_get_contents($url);
	$decode = json_decode($resp, true);
	$tz = $decode["rawoffset"];
	return $tz;
}

//Old API based timezone tool
/*function get_timezone($latitude, $longitude) {
	$url = 'http://api.geonames.org/timezone?lat='.$latitude.'&lng='.$longitude.'&username=santrax';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$xml = curl_exec($ch);
	if (!$xml) { $xml; return -99; }
	curl_close($ch);
	$data = new SimpleXMLElement($xml);
	$tz = trim(strip_tags($data->timezone->rawOffset));
	if (!is_numeric($tz)) { return -99; }
	return $tz;
}*/

//Polyfill
function endsWith( $haystack, $needle=".csv" ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}
function startsWith( $haystack, $needle ) {
     $length = strlen( $needle );
     return substr( $haystack, 0, $length ) === $needle;
}
?>