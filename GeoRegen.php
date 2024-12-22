<?php
require './vendor/autoload.php';
use Tochka\GeoTimeZone\UpdaterData;
use Tochka\GeoTimeZone\Indexer;
echo "<h1>SanTrax GeoRegen</h1><hr>";
if (isset($_GET["confirm"])) {
	$updater = new UpdaterData('./data/geo.data');
	$dataPath = $updater->updateData();
} else {
	echo "<p>Action Not Confirmed.</p>";
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