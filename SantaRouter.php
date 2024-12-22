<?php
echo "<h1>SantaRouter</h1><hr>";
$startx = 64.963051;//90;
$starty = -19.020835;//0;
if (isset($_GET["src"])) {
	$handle = fopen($_GET["src"], "r");
	$contents = fread($handle, filesize($_GET["src"]));
	fclose($handle);
	//var_dump($contents);
	//TODO: at some point I'd like to sort the data points by timezone so that we progress through the time zones in order, but for now we'll count CONUS as one time zone.
	$rows = explode("\n", $contents);
	$nodes = array();
	foreach($rows as $row) {
		if (!empty($row) && !startsWith($row, "#")) {
			$node = explode(",", $row);
			$nodes[] = array(floatval($node[0]),floatval($node[1]));
		}
	}
	$timePerStop = floor(60/(sizeof($nodes)/(4*60))); //this assumes 4 timezones/4 hours of deviation from start to finish, result is in seconds
	echo "<p>Stops: ".sizeof($nodes).".</p>";
	echo "<p>Time per stop: ".$timePerStop."s.</p>";
	$route = array();
	//we want to find the shortest (lowest cost) hamiltonian path given a fully-connected, weighted, undirected graph (AKA nodes[]). This is hard, so here we have a DFS based approximation.
	$route[] = array($startx, $starty);
	while (sizeof($nodes) > 0) {
		$lastx = floatval(end($route)[0]);
		$lasty = floatval(end($route)[1]);
		$closest = array($lastx, $lasty);
		$distance = 9223372036854775807;
		foreach($nodes as $node) {
			$curDist = sqrt(pow(($node[0]-$lastx),2)+pow(($node[1]-$lasty),2));
			if ($curDist < $distance) {
				$distance = $curDist;
				$closest = array($node[0],$node[1]);
			}
		}
		$route[] = array($closest[0],$closest[1]);
		unset($nodes[array_search($closest, $nodes)]);
		//echo "Nodes Remaining: ".sizeof($nodes)."<br>";
	}
	//var_dump($route);
	
	require("db.php");
	$conn = new mysqli($db_server, $db_user, $db_password, $db_db);
	if ($conn->connect_error) {
		die("<p style='color:red;'>Connection failed: " . $conn->connect_error."</p>");
	}	

	// reset table
	$sql = "DELETE FROM route";

	if ($conn->query($sql) === TRUE) {
		echo "<p>Successfully Reset Route Table.</p>";
		$ts = mktime(00, 00, 00, 12, 25, date("Y"));
		echo "<p>Assigning Time Stamps for ".date("Y", $ts)."</p>";
		echo "<p>Liftoff @ ".date("Y-m-d h:i:sa", $ts)."</p>";
		foreach($route as $node) {
			$sql = "INSERT INTO route (x, y, time, dwell) VALUES (".$node[0].", ".$node[1].", '".date("Y-m-d H:i:s", $ts)."', ".$timePerStop.")";
			if ($conn->query($sql) === TRUE) {
				
			} else {
				echo "<p style='color:red;'>Error creating record: " . $conn->error."</p>";
			}
			$ts = strtotime("+".$timePerStop." seconds", $ts);
		}
	
		$ts = strtotime("-".$timePerStop." seconds", $ts);
		echo "<p>Final Stop @ ".date("Y-m-d h:i:sa", $ts)."</p>";
	} else {
		echo "<p style='color:red;'>Error deleting record: " . $conn->error."</p>";
	}
	$conn->close();
	echo "<p>Done! <a href='./SantaRouter.php'>Change Source?</a></p>";
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