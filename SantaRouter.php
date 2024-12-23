<?php
echo "<h1>SantaRouter</h1><hr>";
$startx = 89.999995;//64.963051;
$starty = 0;//-19.020835;
if (isset($_GET["src"])) {
	
	//profiling
	$before = microtime(true);
	
	//phase 1: load and tag stop data
	$handle = fopen($_GET["src"], "r");
	$contents = fread($handle, filesize($_GET["src"]));
	fclose($handle);
	$rows = explode("\n", $contents);
	$nodes = array();
	$untaggedcount = 0;
	foreach($rows as $row) {
		if (!empty($row) && !startsWith($row, "#")) {
			$node = explode(",", $row);
			if (sizeof($node) === 3) {
				$nodes[] = array(floatval($node[0]),floatval($node[1]),floatval($node[2]));
			} else {
				set_time_limit(30);
				$untaggedcount++;
				$tz = floor(floatval(get_timezone($node[0],$node[1])));
				$nodes[] = array(floatval($node[0]),floatval($node[1]),$tz);
			}
		}
	}
	set_time_limit(120);
	$zones = array('-12' => array(),'-11' => array(),'-10' => array(),'-9' => array(),'-8' => array(),'-7' => array(),'-6' => array(),'-5' => array(),'-4' => array(),'-3' => array(),'-2' => array(),'-1' => array(),'0' => array(),'1' => array(),'2' => array(),'3' => array(),'4' => array(),'5' => array(),'6' => array(),'7' => array(),'8' => array(),'9' => array(),'10' => array(),'11' => array(),'12' => array(),'13' => array(),'14' => array());
	foreach($nodes as $node) {
		$zones[$node[2]][] = array($node[0],$node[1]);
	}
	
	$firstZone = -12;
	$lastZone = 14;
	for ($z = 14; $z > -13; $z--) {
			$zone = $zones[$z];
			if (sizeof($zone) > 0) {
					if ($z > $firstZone) {
							$firstZone = $z;
					}
					if ($z < $lastZone) {
							$lastZone = $z;
					}
			}
	}
	$numZones = 1+(($firstZone+14)-($lastZone+14));
	$maximumTimePerStop = floor(60/((sizeof($nodes)+1)/($numZones*60)));
	echo "<p>Loaded ".sizeof($nodes)." points, and tagged ".$untaggedcount." across ".$numZones." timezones.</p>";
	echo "<p>Maximum Time per stop: ".$maximumTimePerStop."s.</p>";
	
	//Garbage collection for phase 1
	unset($contents);
	unset($rows);
	unset($nodes);
	
	//profiling
	$after = microtime(true);
	echo "<p> Phase I: " . ($after-$before) . " sec</p>";
	$before2 = microtime(true);
	
	//phase 2: routing (time agnostic)
	$route = array();
	$lastx = $startx;
	$lasty = $starty;
	//we want to find the shortest (lowest cost) hamiltonian path given a fully-connected, weighted, undirected graph (AKA nodes[]). This is hard, so here we have a DFS based approximation.
	for ($z = 14; $z > -13; $z--) {
		$zone = $zones[$z];
		$route[$z] = array();
		while (sizeof($zone) > 0) {
			$closest = array($lastx, $lasty);
			$distance = 9223372036854775807;
			foreach($zone as $node) {
				set_time_limit(120);
				$curDist = sqrt(pow(($node[0]-$lastx),2)+pow(($node[1]-$lasty),2));
				if ($curDist < $distance) {
					$distance = $curDist;
					$closest = array($node[0],$node[1]);
				}
			}
			$route[$z][] = array($closest[0],$closest[1]);
			$lastx = $closest[0];
			$lasty = $closest[1];
			unset($zone[array_search($closest, $zone)]);
		}
		//echo "<p>Routed Zone ".$z."</p>";
	}
	
	//Garbage collection for phase 2
	unset($zones);
	
	//profiling
	$after = microtime(true);
	echo "<p> Phase II: " . ($after-$before2) . " sec</p>";
	$before2 = microtime(true);
	
	
	//phase 3: time routes and load into database
	require("db.php");
	$conn = new mysqli($db_server, $db_user, $db_password, $db_db);
	if ($conn->connect_error) {
		echo("<p style='color:red;'>Connection failed: " . $conn->connect_error."</p>");
	} else {
		// reset table
		$sql = "DELETE FROM route;";

		if ($conn->query($sql) === TRUE) {
			echo "<p>Successfully Reset Route Table.</p>";
			date_default_timezone_set('UCT');
			$ts = mktime(10, 00, 00, 12, 24, date("Y"));
			echo "<p>Assigning Time Stamps for ".date("Y", $ts)."</p>";
			$sql = "INSERT INTO route (x, y, time, dwell) VALUES (".$startx.", ".$starty.", '".date("Y-m-d H:i:s")."', ".($ts-time()).")";
			if ($conn->query($sql) === TRUE) {
				echo "<p>Created starting point @ ".date("Y-m-d h:i:sa T")."</p>";
			} else {
				echo "<p style='color:red;'>Error creating record: " . $conn->error."</p>";
			}
			$liftoff = false;
			for ($z = 14; $z > -13; $z--) {
				$zone = $route[$z];
				if (sizeof($zone) > 0) {
					echo "<p>Proccessing route for zone ".$z;
					echo "<br>Number of stops: ".sizeof($zone);
					$bonusStop = ($liftoff)? 0:1;
					$end=date("Y-m-d H:00:00",strtotime("+1 hour", $ts));
					$secondsleft = (strtotime($end) - $ts);
					if ($secondsleft > 3599) {
						$secondsleft = 0;
					}
					$timePerStop = floor((3600+$secondsleft)/(sizeof($zone)+$bonusStop));
					if ($timePerStop > $maximumTimePerStop) {
						$timePerStop = $maximumTimePerStop;
					}
					echo "<br>Time per stop: ".$timePerStop;
					if (!$liftoff) {
						$sql = "INSERT INTO route (x, y, time, dwell) VALUES (".$startx.", ".$starty.", '".date("Y-m-d H:i:s", $ts)."', ".$timePerStop.")";
						if ($conn->query($sql) === TRUE) {
							echo "<br>Liftoff @ ".date("Y-m-d h:i:sa T", $ts);
						} else {
							echo "<p style='color:red;'>Error creating record: " . $conn->error."</p>";
						}
						$ts = strtotime("+".$timePerStop." seconds", $ts);
						$liftoff = true;
					}
					$first = true;
					foreach($zone as $node) {
						$sql = "INSERT INTO route (x, y, time, dwell) VALUES (".$node[0].", ".$node[1].", '".date("Y-m-d H:i:s T", $ts)."', ".$timePerStop.")";
						if ($conn->query($sql) === TRUE) {
							if ($first) {
								echo "<br>First Stop @ ".date("Y-m-d h:i:sa T", $ts);
								$first = false;
							}
						} else {
							echo "<p style='color:red;'>Error creating record: " . $conn->error."</p>";
						}
						$ts = strtotime("+".$timePerStop." seconds", $ts);
					}
					$ts = strtotime("-".$timePerStop." seconds", $ts);
					echo "<br>Final Stop @ ".date("Y-m-d h:i:sa T", $ts);
					$ts = strtotime("+".$timePerStop." seconds", $ts);
					echo "</p>";
					$end=date("Y-m-d H:00:00",strtotime("+1 hour", $ts));
					$secondsleft = (strtotime($end) - $ts);
					if ($secondsleft > 300 && $secondsleft < 3599) { //limit early arrival to no more than 5 minutes (ie. santa will not arrive before 11:55pm
						$sql = "INSERT INTO route (x, y, time, dwell) VALUES (".$startx.", ".$starty.", '".date("Y-m-d H:i:s T", $ts)."', ".$secondsleft.")";
						if ($conn->query($sql) === TRUE) {
							echo "<p>Returning to start @ ".date("Y-m-d h:i:sa T", $ts)." for ".$secondsleft." seconds.</p>";
						} else {
							echo "<p style='color:red;'>Error creating record: " . $conn->error."</p>";
						}
						$ts = strtotime("+".$secondsleft." seconds", $ts);
					}
				} else {
					if ($liftoff) {
						$sql = "INSERT INTO route (x, y, time, dwell) VALUES (".$startx.", ".$starty.", '".date("Y-m-d H:i:s T", $ts)."', 3600)";
						if ($conn->query($sql) === TRUE) {
						} else {
							echo "<p style='color:red;'>Error creating record: " . $conn->error."</p>";
						}
						echo "<p>No route stops in zone ".$z."; Returning to start @ ".date("Y-m-d h:i:sa T", $ts)."</p>";
					} else {
						echo "<p>No route stops in zone ".$z."</p>";
					}
					$ts = strtotime("+1 hour", $ts);
				}
			}
			$sql = "INSERT INTO route (x, y, time, dwell) VALUES (".$startx.", ".$starty.", '".date("Y-m-d H:i:s T", $ts)."', 3600)";
			if ($conn->query($sql) === TRUE) {
				echo "<p>Returning to start @ ".date("Y-m-d h:i:sa T", $ts)."</p>";
			} else {
				echo "<p style='color:red;'>Error creating record: " . $conn->error."</p>";
			}
			$ts = strtotime("+1 hour", $ts);
			$sql = "INSERT INTO route (x, y, time, dwell) VALUES (".$startx.", ".$starty.", '".date("Y-m-d H:i:s T", $ts)."', 0)";
			if ($conn->query($sql) === TRUE) {
			} else {
				echo "<p style='color:red;'>Error creating record: " . $conn->error."</p>";
			}
		
		} else {
			echo "<p style='color:red;'>Error deleting record: " . $conn->error."</p>";
		}
		$conn->close();
		
		//profiling
		$after = microtime(true);
		echo "<p> Phase III: " . ($after-$before2) . " sec</p>";
		echo "<p> Total: " . ($after-$before) . " sec</p>";
		
		echo "<p>Done! <a href='./SantaRouter.php'>Change Source?</a></p>";
	}
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

function get_timezone($latitude, $longitude) {
	$url = preg_replace('/\s+/','','http://localhost:3000/api/v1/timezone?lat='.$latitude.'&lng='.$longitude);
	$resp = file_get_contents($url);
	$decode = json_decode($resp, true);
	$tz = $decode["rawoffset"];
	return $tz;
}

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