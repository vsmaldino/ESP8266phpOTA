<?php
function check_header($name, $value = false) {
    if(!isset($_SERVER[$name])) {
        return false;
    }
    if($value && $_SERVER[$name] != $value) {
        return false;
    }
    return true;
} // check_header


function sendFile($path) {
    header($_SERVER["SERVER_PROTOCOL"].' 200 OK', true, 200);
    header('Content-Type: application/octet-stream', true);
    header('Content-Disposition: attachment; filename='.basename($path));
    header('Content-Length: '.filesize($path), true);
    header('x-MD5: '.md5_file($path), true);
    readfile($path);
} // sendFile

/*
 * SCOMMENTARE
 * 
if(!check_header('HTTP_USER_AGENT', 'ESP8266-http-Update')) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
    echo "only for ESP8266 updater!\n";
    exit();
}

if(
    !check_header('HTTP_X_ESP8266_STA_MAC') ||
    !check_header('HTTP_X_ESP8266_AP_MAC') ||
    !check_header('HTTP_X_ESP8266_FREE_SPACE') ||
    !check_header('HTTP_X_ESP8266_SKETCH_SIZE') ||
    !check_header('HTTP_X_ESP8266_SKETCH_MD5') ||
    !check_header('HTTP_X_ESP8266_CHIP_SIZE') ||
    !check_header('HTTP_X_ESP8266_SDK_VERSION') 
{
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
    echo "only for ESP8266 updater! (header)\n";
    exit();
}
*/
// $servername = "localhost";
// $username = "myusername";
// $password = "mypasswd";
// $dbname = "mydbname";
require "securities.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully";

// log request
$sql = "INSERT INTO OTA_ACCESS(TIMEST, USER_AGENT, CHIP_ID, STA_MAC,";
$sql=$sql."AP_MAC, FREE_SPACE, SKETCH_SIZE, SKETCH_MD5, CHIP_SIZE,";
$sql=$sql."SDK_VERSION, FW_MODE, VERSION)";
$sql=$sql."VALUES(NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// echo $sql;
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
	die("Prepared failed: " . mysqli_error($conn));
}

$ret=mysqli_stmt_bind_param($stmt, "ssssddsdsss",
   $user_agent, $chip_id, $sta_mac, $ap_mac, $free_space, $sketch_size,
   $sketch_md5, $chip_size, $sdk_version, $fw_mode,
   $version);
if (!$ret) {
	die("Bind failed: " . mysqli_error($conn));
}

$user_agent=substr($_SERVER["HTTP_USER_AGENT"],0,50); // alcuni user agent sono lunghissimi
// echo $user_agent;
// $chip_id=$_SERVER["HTTP_X_ESP8266_CHIP_ID"];
$chip_id="1212122";
// $sta_mac=$_SERVER["HTTP_X_ESP8266_STA_MAC"];
$sta_mac="84:F3:EB:B7:4F:BF";
// $ap_mac=$_SERVER["HTTP_X_ESP8266_AP_MAC"];
$ap_mac="84:F3:EB:B7:4F:BF";
// $free_space=$_SERVER["HTTP_X_ESP8266_FREE_SPACE"];
$free_space=1794043;
// $sketch_size=$_SERVER["HTTP_X_ESP8266_SKETCH_SIZE"];
$sketch_size=301231;
// $sketch_MD5=$_SERVER["HTTP_X_ESP8266_SKETCH_MD5"];
$sketch_md5="3016ef12ad231";
// $chip_size=$_SERVER["HTTP_X_ESP8266_CHIP_SIZE"];
$chip_size=4031231;
// $sdk_version=$_SERVER["HTTP_X_ESP8266_SDK_VERSION"];
$sdk_version="2.2.2-DEV(3A5B7)";
// $fw_mode=$_SERVER["HTTP_X_ESP8266_MODE"];
$fw_mode="sketch";
// $version=$_SERVER["HTTP_X_ESP8266_VERSION"];
$version="ddrt.433";

$ret=mysqli_stmt_execute($stmt);

if (!$ret) {
	die("Exec failed: " . mysqli_error($conn));
}


mysqli_close($conn);
?>
