<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<link rel='stylesheet' href='./libraries/leaflet/leaflet.css'/>
<script src='./libraries/leaflet/leaflet.js'></script>
<div>
	<div style='height: 100%; width: 100%;' id='map'></div>
</div>
<script>
	var mymap = L.map('map').setView(new L.LatLng(0, 0), 1);
	L.control.scale().addTo(mymap);
	L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {attribution: 'Tiles &copy; Open Street Map'}).addTo(mymap);
	var markerGroup = L.layerGroup().addTo(mymap);
	<?php
		require("db.php");
		$conn = new mysqli($db_server, $db_user, $db_password, $db_db);
		if ($conn->connect_error) {
			echo "alert('Failed to Connect to DB')";
		}
		$sql = "SELECT * FROM route ORDER BY time ASC";
		$result = $conn->query($sql);
		$out = "";
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				//echo "L.marker([".$row["x"].", ".$row["y"]."]).addTo(markerGroup);";
				$out .= "[".$row["x"].", ".$row["y"]."],";
			}
		}
		echo "	var latlngs = [".$out."];";
		$conn->close();
	?>

	var polyline = L.polyline(latlngs, {color: 'red'}).addTo(mymap);

	// zoom the map to the polyline
	map.fitBounds(polyline.getBounds());
</script>