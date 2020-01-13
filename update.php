<?php

// function definitions at the end

$localbinpath = "./otabin/";
$binext=".bin";

/* scommentare
 * ********************
 */
checkHeaderMetas();
/* Anche all'interno di loadHeaderMetas()
*/

loadHeaderMetas();

// $servername = "localhost";
// $username = "myusername";
// $password = "mypasswd";
// $dbname = "mydbname";
require "securities.php";

// Create connection
// $conn1 = mysqli_connect($servername, $username, $password, $dbname);
$conn1 = new mysqli ($servername, $username, $password, $dbname);

// Check connection
if ($conn1->connect_error) {
 	header($_SERVER["SERVER_PROTOCOL"].' 574 Internal Server Error - Connect' . $conn1->connect_errno, true, 574);
 	error_log("574 Internal Server Error - Connect " . $conn1->connect_error . $conn1->connect_errno,0);
	exit("574 Internal Server Error - Connect " . $conn1->connect_errno . $conn1->connect_error);
//  die("Connection failed: " . mysqli_connect_error());
}
error_log ("Connected successfully", 0);

logRequest($conn1);
error_log ("Logged!", 0);

$filename=searchFw($conn1);
// error_log("ricevuto " . $filename);

$conn1->close();

if (strcmp($filename, "no_no") == 0) {
	header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
	error_log("No fw to send");
}
else {
	$localbinary = $localbinpath.$filename.$binext;
	error_log("localbinary " . $localbinary);
	// security check in case of version/name/file mismatch
	if (file_exists($localbinary)) {
	  if (strcmp($sketch_md5, md5_file($localbinary)) != 0) {
			error_log("Sending ".$filename." ");
	  	sendfile($localbinary);
	  }
	  else {
	  	header($_SERVER["SERVER_PROTOCOL"].' 590 Different versions but same MD5 ', true, 590);
	  	error_log("Different versions but same MD5 ".$filename." and ".$version);
	  }
  }
  else {
		header($_SERVER["SERVER_PROTOCOL"].' 591 File Not Found ', true, 591);
		error_log("File not found: ".$localbinary);
	}
}

/*
 * ************** START OF FUNCTION DEFINITIONS ***********************
*/

function logRequest($conn) {
  global $user_agent, $chip_id, $sta_mac, $ap_mac, $free_space,
         $sketch_size, $sketch_md5, $chip_size, $sdk_version,
         $fw_mode, $version;  // sets meta HTTP_xxxx  
  // log request
  $sql = "INSERT INTO OTA_ACCESS(TIMEST, USER_AGENT, CHIP_ID, STA_MAC,";
  $sql=$sql . "AP_MAC, FREE_SPACE, SKETCH_SIZE, SKETCH_MD5, CHIP_SIZE,";
  $sql=$sql . "SDK_VERSION, FW_MODE, VERSION)";
  $sql=$sql . "VALUES(NOW(), ?, ?, UCASE(?), UCASE(?), ?, ?, ?, ?, ?, ?, ?)";
  
  // echo $sql;
  // $stmt = mysqli_prepare($conn, $sql);
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
  	header($_SERVER["SERVER_PROTOCOL"].' 571 Internal Server Error - Prepare ' . $conn->errno, true, 571);
  	error_log ("571 Prepared failed: " . $conn->error, 0);
  	exit("571 Internal Server Error - Prepare " . $conn->errno . $conn->error);
  	// die("Prepared failed: " . $conn->error);
  }
  // $ret=mysqli_stmt_bind_param($stmt, "ssssddsdsss",
  $ret=$stmt->bind_param("ssssddsdsss",
     $user_agent, $chip_id, $sta_mac, $ap_mac, $free_space, $sketch_size,
     $sketch_md5, $chip_size, $sdk_version, $fw_mode,
     $version);
  if (!$ret) {
  	header($_SERVER["SERVER_PROTOCOL"].' 572 Internal Server Error - Bind ' . $conn->errno, true, 572);
  	error_log ("572 Bind failed: " . $conn->error, 0);
  	exit("572 Internal Server Error - Bind " . $conn->errno . $conn->error);
  	// die("Bind failed: " . $conn->error);
  }

  $ret=$stmt->execute();
  if (!$ret) {
  	header($_SERVER["SERVER_PROTOCOL"].' 573 Internal Server Error - Exec ' . $conn->errno, true, 573);
  	error_log ("573 Exec failed: " . $conn->error, 0);
  	exit("573 Internal Server Error - Exec " . $conn->errno . $conn->error);
  	// die("Exec failed: " . $conn->error);
  }
  $stmt->close();
} // logRequest


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


function searchFw($conn) {
  global $user_agent, $chip_id, $sta_mac, $ap_mac, $free_space,
         $sketch_size, $sketch_md5, $chip_size, $sdk_version,
         $fw_mode, $version;  // sets meta HTTP_xxxx  
  
  $seppos=strpos($version,"_"); // searches separator
  if ($seppos > 6) {
		$sepposeff = 6; // truncated
	}
	else {
		$sepposeff = $seppos;
	}
	$swid = trim(substr($version, 0, $sepposeff));
	$swvers = trim(substr($version, $seppos+1));
	
	// echo "$swid \n";
	// echo "$swvers \n";
	
	// init of return values;
	$tswid = "no";
	$tswvers = "no";
	
	// check if there's a specific release for that card
	$sql =        "SELECT r.SWID AS tswid, r.SWVERS AS tswvers FROM ";
	$sql = $sql . "OTA_RELEASES AS r, OTA_FORBOARD AS f ";
	$sql = $sql . "WHERE ";
	$sql = $sql . "r.SWID = f.SWID AND r.SWVERS AND f.SWVERS AND ";
	$sql = $sql . "r.MACRESTRICTED AND r.ENABLED AND f.ENABLED AND ";
	$sql = $sql . "UCASE(f.STA_MAC) = UCASE(?) AND r.EXPIRES > NOW() ";
	$sql = $sql . "ORDER BY r.RELEASED DESC ";
	$sql = $sql . "LIMIT 1  ";

  // echo $sql . "\n";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
  	header($_SERVER["SERVER_PROTOCOL"].' 581 Internal Server Error - Prepare ' . $conn->errno, true, 581);
  	error_log ("581 Prepared failed: " . $conn->error, 0);
  	exit("581 Internal Server Error - Prepare " . $conn->errno . $conn->error);
  	// die("Prepared failed: " . $conn->error);
  }
  
  $ret=$stmt->bind_param("s", $sta_mac);
  if (!$ret) {
  	header($_SERVER["SERVER_PROTOCOL"].' 582 Internal Server Error - Bind ' . $conn->errno, true, 582);
  	error_log ("582 Bind failed: " . $conn->error, 0);
  	exit("582 Internal Server Error - Bind " . $conn->errno . $conn->error);
  	// die("Bind failed: " . $conn->error);
  }

  $ret=$stmt->execute();
  if (!$ret) {
  	header($_SERVER["SERVER_PROTOCOL"].' 583 Internal Server Error - Exec ' . $conn->errno, true, 583);
  	error_log ("583 Exec failed: " . $conn->error, 0);
  	exit("583 Internal Server Error - Exec " . $conn->errno . $conn->error);
  	// die("Exec failed: " . $conn->error);
  }
  $ret = $stmt->get_result();
  if($ret->num_rows > 0) {
		// no need for loop, 1 row only (LIMIT 1)
		$row = $ret->fetch_assoc(); 
		$tswid   = $row['tswid'];
		$tswvers = $row['tswvers'];
	} // end of specific board fw
	else { // no specific board fw
		// echo " no specific ";
		// search a generic fw
	  $sql =        "SELECT SWID AS tswid, SWVERS AS tswvers FROM ";
	  $sql = $sql . "OTA_RELEASES ";
	  $sql = $sql . "WHERE ";
	  $sql = $sql . "SWID = ? AND EXPIRES > NOW() AND ENABLED ";
	  $sql = $sql . "AND NOT(MACRESTRICTED) ";
	  $sql = $sql . "ORDER BY RELEASED DESC ";
	  $sql = $sql . "LIMIT 1 ";

    // echo $sql . "\n";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
    	header($_SERVER["SERVER_PROTOCOL"].' 584 Internal Server Error - Prepare ' . $conn->errno, true, 584);
    	error_log ("584 Prepared failed: " . $conn->error, 0);
    	exit("584 Internal Server Error - Prepare " . $conn->errno . $conn->error);
    	// die("Prepared failed: " . $conn->error);
    }

    $ret=$stmt->bind_param("s", $swid);
    if (!$ret) {
    	header($_SERVER["SERVER_PROTOCOL"].' 585 Internal Server Error - Bind ' . $conn->errno, true, 585);
    	error_log ("585 Bind failed: " . $conn->error, 0);
    	exit("585 Internal Server Error - Bind " . $conn->errno . $conn->error);
    	// die("Bind failed: " . $conn->error);
    }

    $ret=$stmt->execute();
    if (!$ret) {
    	header($_SERVER["SERVER_PROTOCOL"].' 586 Internal Server Error - Exec ' . $conn->errno, true, 586);
    	error_log ("586 Exec failed: " . $conn->error, 0);
    	exit("586 Internal Server Error - Exec " . $conn->errno . $conn->error);
    	// die("Exec failed: " . $conn->error);
    }
    $ret = $stmt->get_result();
    if($ret->num_rows > 0) {
	  	// no need for loop, 1 row only (LIMIT 1)
	  	$row = $ret->fetch_assoc(); 
	  	$tswid   = $row['tswid'];
	  	$tswvers = $row['tswvers'];
	  	// echo "<br>";
	  	// echo "tswid  : " . $tswid . "<br>";
	  	// echo "tswvers: " . $tswvers . "<br>";
	  }
	} // end no specific board fw
  $stmt->close();
  $tswid = trim($tswid);
  $tswvers = trim($tswvers);
  if (strcmp($tswvers, $swvers) == 0) {
		// no change
	  $tswid = "no";
	  $tswvers = "no";
	}
  return $tswid . "_" . $tswvers;
} // searchFw


function checkHeaderMetas() {
  if(!check_header('HTTP_USER_AGENT', 'ESP8266-http-Update')) {
      header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
      error_log ("only for ESP8266 updater!", 0);
      exit();
  }
  
  if(
      !check_header('HTTP_X_ESP8266_STA_MAC') ||
      !check_header('HTTP_X_ESP8266_AP_MAC') ||
      !check_header('HTTP_X_ESP8266_FREE_SPACE') ||
      !check_header('HTTP_X_ESP8266_SKETCH_SIZE') ||
      !check_header('HTTP_X_ESP8266_SKETCH_MD5') ||
      !check_header('HTTP_X_ESP8266_CHIP_SIZE') ||
      !check_header('HTTP_X_ESP8266_SDK_VERSION') )
  {
      header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
      error_log ("only for ESP8266 updater! (header)\n", 0);
      exit();
  }
} // checkHeaderMetas

function loadHeaderMetas () {
  global $user_agent, $chip_id, $sta_mac, $ap_mac, $free_space,
         $sketch_size, $sketch_md5, $chip_size, $sdk_version,
         $fw_mode, $version;
  
  $user_agent=substr($_SERVER["HTTP_USER_AGENT"],0,50); // alcuni user agent sono lunghissimi
  // echo $user_agent;
  error_log ("HTTP_USER_AGENT " . $user_agent);
  $chip_id=$_SERVER["HTTP_X_ESP8266_CHIP_ID"];
  // $chip_id="1212122";
  error_log ("HTTP_X_ESP8266_CHIP_ID " . $chip_id);
  $sta_mac=$_SERVER["HTTP_X_ESP8266_STA_MAC"];
  // $sta_mac="84:F3:EB:B7:4F:BF";
  error_log ("HTTP_X_ESP8266_STA_MAC " . $sta_mac);
  $ap_mac=$_SERVER["HTTP_X_ESP8266_AP_MAC"];
  // $ap_mac="84:F3:EB:B7:4F:BF";
  error_log ("HTTP_X_ESP8266_AP_MAC " . $ap_mac);
  $free_space=$_SERVER["HTTP_X_ESP8266_FREE_SPACE"];
  // $free_space=1794043;
  error_log ("HTTP_X_ESP8266_FREE_SPACE " . $free_space);
  $sketch_size=$_SERVER["HTTP_X_ESP8266_SKETCH_SIZE"];
  // $sketch_size=301231;
  error_log ("HTTP_X_ESP8266_SKETCH_SIZE " . $sketch_size);
  $sketch_md5=$_SERVER["HTTP_X_ESP8266_SKETCH_MD5"];
  // $sketch_md5="3016ef12ad231";
  error_log ("HTTP_X_ESP8266_SKETCH_MD5 " . $sketch_MD5);
  $chip_size=$_SERVER["HTTP_X_ESP8266_CHIP_SIZE"];
  // $chip_size=4031231;
  error_log ("HTTP_X_ESP8266_CHIP_SIZE " . $chip_size);
  $sdk_version=$_SERVER["HTTP_X_ESP8266_SDK_VERSION"];
  // $sdk_version="2.2.2-DEV(3A5B7)";
  error_log ("HTTP_X_ESP8266_SDK_VERSION " . $sdk_version);
  $fw_mode=$_SERVER["HTTP_X_ESP8266_MODE"];
  // $fw_mode="sketch";
  error_log ("HTTP_X_ESP8266_MODE " . $fw_mode);
  $version=$_SERVER["HTTP_X_ESP8266_VERSION"];
  // $version="envm01_5.3.4";
  error_log ("HTTP_X_ESP8266_VERSION " . $version);
  
} // loadHeaderMetas

?>
