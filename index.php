<link rel="stylesheet" href="./libraries/bootstrap-3.4.1-dist/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="./libraries/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
<link rel='stylesheet' href='./libraries/leaflet/leaflet.css'/>
<script src='./libraries/leaflet/leaflet.js'></script>
<script src="./libraries/leaflet.motion.min.js"></script>
<div>
	<div style='height: 100%' class='col-sm-9' id='map'></div>
	<div style='height: 100%' class='col-sm-3'>
		<div style='height:80%;'>
			<center>
				<h1>SanTrax</h1><h3>Santa Tracking System</h3><br>
			</center>
			<div style='outline: 1px solid black;'>
				<center>
					<h4><b><u>Estimated Air Speed</u></b></h4>
					<h3><span id='speed'>
						<span style='outline: 1px solid black;'>0</span><span style='outline: 1px solid black;'>0</span><span style='outline: 1px solid black;'>0</span><span style='outline: 1px solid black;'>0</span><span style='outline: 1px solid black;'>0</span><span style='outline: 1px solid black;'>0</span>
					</span> MPH</h3><br/>
				</center>
			</div>
		</div>
		<div style='max-height: 5%; width:100%; bottom: 0;'>
			<hr>
			<img style='right: 10%; width:15%; float:left;' src='./assets/img/Emergency Software Foundation Logo.png'></img>
			<center>
				<p>(c) Emergency Software Foundation <?php echo date('Y'); ?></p>
				<p><a href='https://github.com/Emergency-Software-Foundation/SanTrax'>Contribute</a> | <a href='./attributions.html'>Acknowledgements</a></p>
			</center>
		</div>
	</div>
</div>
<script>
	var mymap = L.map('map').setView(new L.LatLng(0, 0), 2);
	L.control.scale().addTo(mymap);
	L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner_background/{z}/{x}/{y}{r}.png', {attribution: '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(mymap);
	var markerGroup = L.layerGroup().addTo(mymap);
	<?php
		require("db.php");
		$conn = new mysqli($db_server, $db_user, $db_password, $db_db);
		if ($conn->connect_error) {
			echo "alert('Failed to Connect to DB')";
		}
		if (isset($_GET["debug"])) {
			$sql = "SELECT * FROM route ORDER BY time ASC";
			$result = $conn->query($sql);
			$out = "";
			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc()) {
					$out .= "[".$row["x"].", ".$row["y"]."],";
				}
			}
			echo "	var latlngs = [".$out."];";
		}
		$last = "[0,0]";
		$next = "[0,0]";
		$dwell = 4;
		$sql = "SELECT * FROM route WHERE time < UTC_TIMESTAMP(6) ORDER BY time DESC LIMIT 1";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$last = "[".$row["x"].", ".$row["y"]."]";
			}
			$sql = "SELECT * FROM route WHERE time > UTC_TIMESTAMP(6) ORDER BY time ASC LIMIT 1";
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc()) {
					$next = "[".$row["x"].", ".$row["y"]."]";
					$dwell = $row["dwell"];
				}
			} else {
				$next = $last;
			}
		}
		if (isset($_GET["debug"]) && isset($_GET["cTime"])) {
			echo "	var curPath = [".$out."];";
			echo "	var dwell = 5;";
		} else {
			echo "	var curPath = [".$last.",".$next."];";
			echo "	var last = ".$next.";";
			echo "	var queue = ".$next.";";
			echo "	var dwell = ".$dwell.";";
		}
		if (isset($_GET["debug"])) { echo "var polyline = L.polyline(latlngs, {color: 'red'}).addTo(mymap);"; }
		
		$conn->close();
	?>
	L.motion.polyline(curPath,  {color: "transparent"}, {auto: true,duration: (dwell*1000),easing: L.Motion.Ease.linear}, {removeOnEnd: true,showMarker: true,icon: L.icon({
		iconUrl: './assets/img/santa.png',
		iconSize: [30, 30],
		iconAnchor: [15, 15]
	})}).addTo(mymap);
	//var polyline = L.polyline(latlngs, {color: 'transparent'}).addTo(mymap);
	// zoom the map to the polyline
	//mymap.fitBounds(polyline.getBounds());
	setTimeout(nextPoint, dwell*1000);
	function nextPoint() {
		L.motion.polyline([last,queue],  {color: "transparent"}, {auto: true,duration: (dwell*1000),easing: L.Motion.Ease.linear}, {removeOnEnd: true,showMarker: true,icon: L.icon({
			iconUrl: './assets/img/santa.png',
			iconSize: [30, 30],
			iconAnchor: [15, 15]
		})}).addTo(mymap);
		last = queue;
		setTimeout(nextPoint, dwell*1000);
		fetch("./getNext.php?t="+Date.now()).then(x => x.text()).then((txt) => {
			let data = JSON.parse(txt);
			dwell = data["dwell"];
			queue = data["next"];
			updateAirSpeed(getAirSpeed(last, queue, dwell));
		});
	}
	function getDistanceFromLatLonInMi(lat1, lon1, lat2, lon2) {
		const R = 3958.8;
		const dLat = deg2rad(lat2 - lat1);
		const dLon = deg2rad(lon2 - lon1);
		const a =
			Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
			Math.sin(dLon / 2) * Math.sin(dLon / 2);
		const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		return R * c;
	}
	function deg2rad(deg) {
		return deg * (Math.PI / 180);
	}
	function getAirSpeed(last, next, dwell) {
		if (last[0] == next[0] && last[1] == next[1]) {
			return "000000";
		}
		if (dwell == 0) {
			return "999999";
		}
		const dist = getDistanceFromLatLonInMi(last[0], last[1], next[0], next[1]);
		const speed = (dist / dwell)*360;
		if (speed > 999999) {
			return "999999";
		}
		return String(Math.round(speed)).padStart(6, '0');;
	}
	function updateAirSpeed(speed) {
		document.getElementById('speed').innerHTML = speed.split('')
		  .map(ch => "<span style='outline: 1px solid black;'>"+ch+"</span>")
		  .join("");
	}
</script>