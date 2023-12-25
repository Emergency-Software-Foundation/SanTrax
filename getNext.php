<?php
include("db.php");
$conn = new mysqli($db_server, $db_user, $db_password, $db_db);
if ($conn->connect_error) {
}
$sql = "SELECT * FROM route WHERE time > CURRENT_TIMESTAMP ORDER BY time ASC LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		echo "{
			\"dwell\":".$row["dwell"].",
			\"next\": [".$row["x"].",".$row["y"]."]
		}";
	}
}
$conn->close();
?>