<head>
	<link rel="stylesheet" href="./libraries/bootstrap-3.4.1-dist/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="./libraries/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
	<link rel='stylesheet' href='./libraries/leaflet/leaflet.css'/>
	<script src='./libraries/leaflet/leaflet.js'></script>
	<script src="./libraries/leaflet.motion.min.js"></script>
	<meta charset="UTF-8" />
	<title>SanTrax | Emergency Software Foundation</title>
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<style>
		#countdown-box {
		  position: relative;
		  width: 40%;
		  max-height: 20%;
		  border: 5px solid grey;
		  text-align: center;
		  font-family: sans-serif;
		  background: white;
		  z-index: 999999;
		  left: 30%;
		  top: 40%;
		}
		#time {
		  font-size: 1.2em;
		  margin-top: 10px;
		  font-weight: bold;
		}
		
		.play-btn {
		  width: 48px;
		  height: 48px;
		  background: none;
		  border: none;
		  cursor: pointer;
		  position: relative;
		}
		.play-btn::before {
		  content: "";
		  position: absolute;
		  top: 50%;
		  left: 50%;
		  transform: translate(-40%, -50%);
		  width: 0;
		  height: 0;
		  border-top: 12px solid transparent;
		  border-bottom: 12px solid transparent;
		  border-left: 18px solid #000;
		}
		.play-btn.playing::before {
		  content: "";
		  width: 18px;
		  height: 18px;
		  background: #000;
		  border: none;
		  transform: translate(-50%, -50%);
		}
		.play-btn.playing::after {
		  display: none;
		}
		.play-btn:hover {
		  opacity: 0.85;
		}
		#now-playing-container {
		  width: 100%;
		  overflow: hidden;
		  white-space: nowrap;
		  position: relative;
		}
		#now-playing {
		  display: inline-block;
		}
		#now-playing.scrolling {
		  animation: scroll-text 12s linear infinite;
		}
		@keyframes scroll-text {
		  from {
			transform: translateX(0%);
		  }
		  to {
			transform: translateX(-100%);
		  }
		}
	</style>
</head>
<div>
	<div style='height: 100%' class='col-sm-9' id='map'>
		<div id="countdown-box">
			<h3>Countdown to Take-off</h3>
			<div id="time">Loading…</div>
		</div>
	</div>
	<div style='height: 100%; background: white;' class='col-sm-3'>
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
			<br/>
			<div id="music-player" style="outline: 1px solid black;">
				<center>
					<h4><u>North Pole Radio</u></h4>
				</center>
				<div style="width:100%;display:inline-block;">
					<div style="width:25%;display:inline-block;">
						<button id="music-toggle" class="play-btn" aria-label="Play music"></button>
					</div>
					<div style="width:70%;text-align:center;display:inline-block;">
						<div style="margin-top:8px; background:#ccc; height:6px; width:100%;">
							<div id="music-progress" style="background:grey; height:6px; width:0%;"></div>
						</div>
						<div id="now-playing-container">
							<span id="now-playing">-</span>
						</div>
					</div>
				</div>
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
	var mymap = L.map('map', {
		zoomSnap: 0.1,
		minZoom: 1.6,
		maxBoundsViscosity: 0.5,
	}).setView(new L.LatLng(0, 0), 1.6);
	var southWest = L.latLng(-89.98155760646617, -180),
	northEast = L.latLng(89.99346179538875, 180);
	var bounds = L.latLngBounds(southWest, northEast);
	mymap.setMaxBounds(bounds);
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
		if (isset($_GET["debug"])) {
			echo "	var follow = true;";
		} else {
			echo "	var follow = false;";	
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
		const prevlast = last;
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
	(function () {
		const box = document.getElementById("countdown-box");
		const timeEl = document.getElementById("time");

		function getTargetDateUTC() {
			const now = new Date();
			const year = now.getUTCFullYear();

			// Dec 24, 10:00 UTC (5:00 AM EST)
			let target = new Date(Date.UTC(year, 11, 24, 10, 0, 0));

			// If we've already passed it this year, hide immediately
			if (now >= target) {
				box.style.display = "none";
				return null;
			}

			return target;
		}

		const targetDate = getTargetDateUTC();
		if (!targetDate) return;

		function updateCountdown() {
			const now = new Date();
			const diff = targetDate - now;

			if (diff <= 0) {
				box.style.display = "none";
				clearInterval(timer);
				return;
			}

			const totalSeconds = Math.floor(diff / 1000);
			const days = Math.floor(totalSeconds / 86400);
			const hours = Math.floor((totalSeconds % 86400) / 3600);
			const minutes = Math.floor((totalSeconds % 3600) / 60);
			const seconds = totalSeconds % 60;

			timeEl.innerHTML =
				"<h4>T- "+days+"d "+hours+"h "+minutes+"m "+seconds+"s</h4>";
		}

		updateCountdown();
		const timer = setInterval(updateCountdown, 1000);
	})();
	const SONG_URLS = [
		"./assets/audio/deckhalls.mp3",
		"./assets/audio/jinglebells.mp3",
		"./assets/audio/merrychristmas.mp3"
	];
	const nowPlayingLookup = {
		"deckhalls.mp3" : "Deck The Halls",
		"jinglebells.mp3" : "Jingle Bells",
		"merrychristmas.mp3" : "We Wish You A Merry Christmas",
	}
	let audio = new Audio();
	audio.preload = "auto";

	let playlist = [];
	let currentIndex = 0;
	let hasStarted = false;
	let isPlaying = false;
	
	function shuffle(array) {
		const a = array.slice();
		for (let i = a.length - 1; i > 0; i--) {
			const j = Math.floor(Math.random() * (i + 1));
			[a[i], a[j]] = [a[j], a[i]];
		}
		return a;
	}

	function buildNewPlaylist() {
		playlist = shuffle(SONG_URLS);
		currentIndex = 0;
	}

	function playCurrentSong() {
		if (!playlist.length) buildNewPlaylist();

		audio.src = playlist[currentIndex];
		audio.play();
		isPlaying = true;
		updateButton();
		updateNowPlaying();
	}
	
	function startBackgroundMusic() {
		if (!playlist.length) buildNewPlaylist();

		if (!isPlaying) {
			playCurrentSong();
		}
		
		hasStarted = true;
	}
	
	function toggleBackgroundMusic() {
		if (!hasStarted) {
			startBackgroundMusic();
		} else if (!isPlaying) {
			audio.play();
			isPlaying = true;
			updateButton();
		} else {
			audio.pause();
			isPlaying = false;
			updateButton();
		}
	}
	
	audio.addEventListener("ended", () => {
		currentIndex++;

		// Restart with a new shuffle when exhausted
		if (currentIndex >= playlist.length) {
			buildNewPlaylist();
		}

		playCurrentSong();
	});
	
	audio.addEventListener("timeupdate", () => {
		const progress = document.getElementById("music-progress");
		if (!audio.duration) return;

		const percent = (audio.currentTime / audio.duration) * 100;
		progress.style.width = percent + "%";
	});
	
	const toggleBtn = document.getElementById("music-toggle");

	function updateButton() {
		toggleBtn.classList.toggle("playing", isPlaying);
		toggleBtn.setAttribute(
			"aria-label",
			isPlaying ? "Pause music" : "Play music"
		);
	}

	toggleBtn.addEventListener("click", toggleBackgroundMusic);
	
	function getTrackTitle(url) {
		const track = url.split("/").pop();
		if (nowPlayingLookup.hasOwnProperty(track)) {
			return nowPlayingLookup[track];
		}
		return decodeURIComponent(
			track.replace(/\.[^/.]+$/, "")
		);
	}
	
	const nowPlayingEl = document.getElementById("now-playing");

	function updateNowPlaying() {
		if (!playlist.length || !isPlaying) {
			return;
		}
		const currentUrl = playlist[currentIndex];
		nowPlayingEl.textContent = "Now Playing: " + getTrackTitle(currentUrl);
		nowPlayingEl.className = "playing";
		updateScrolling();
	}
	
	function updateScrolling() {
		const container = document.getElementById("now-playing-container");
		const text = document.getElementById("now-playing");
		text.classList.remove("scrolling");
		void text.offsetWidth;
		if (text.scrollWidth > container.clientWidth) {
			text.classList.add("scrolling");
		}
	}
</script>