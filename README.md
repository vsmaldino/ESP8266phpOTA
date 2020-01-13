# ESP8266phpOTA

## Very short

Web server for ESP8266-based board OTA updates management.

## Main Functions

1. Supplies firmware updates
2. Logs update requests and checks.

## Firmware update management

#### Firmware string, id and version

Each firmware is identificated by a 15 char (max) string (**Firmware string**) containing two information separated by a mandatory underscore (_):

1. **Firmware id.** First 6 chars (max)
2. **Firmware version**. Last 8 chars (max) 

###### Example:

Firmware string: envm01_0.3.4
where
Firmware id: envm01
Version: 0.3.4

When checking for updates, *Firmware string* is sent to the web server; this means that the Firmware string needs to be included in the code.

The file containing the firmware must have the same name as the Firmware string and the extension .bin (ex. envm01_0.3.4.bin)

###### Example:

```
#define fw_string "envm01_0.3.4"
//  ======= ^ 
.
.
void setup  () {  
.
.
  Serial.println("");
  Serial.print("WiFi connected: ");
  Serial.println(WiFi.SSID());
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());

  ESPhttpUpdate.rebootOnUpdate(false);
  t_httpUpdate_return ret = ESPhttpUpdate.update(espClient, otaurl, fw_string);
  // ================================================================= ^
  switch (ret) {
.
.
} // setup
```



#### Update delivery policy

Updates can be delivered in 2 ways:

1. Based on *Firmware id*
2. Based on MAC Address

With policy 1)  can be delivered only firmware with the same *Firmware id* of the actual running one. With policy 2) can also be delivered firmware with different *Firmware id* of the actual running one.

Policy 2) has higher priority than 1).

## How to setup an update

1. Prepare the bin file containing the firmware.
2. Upload the bin file on the web server
3. Load info into the DB

#### Prepare the bin file

From the arduino IDE go to **Sketch->Export compiled Binary**. The file is created in the same directory of the source file.

Be sure to:

- Include the *Firmware string* in the code
- Name the file as the *Firmware string* plus .bin extension.

#### Upload the bin file on the web server

The path of the directory containing the bin files is stored in $localbinpath variable (file update.php).

#### Load info into the DB

The OTA_RELEASES table must be filled for each release, even in the case of MAC Address-based delivery.

#### OTA_RELEASES  Table

This table contains all released software versions

- SWID. The Firmware Id
- SWVERS.  The firmware version
- RELEASED. Release date/time, used only for log. Typically NOW().
- EXPIRES. Expire date/time. Typically 2030-12-31 23:59:59
- ENABLED. If false, the firmware will not be delivered
- MACRESTRICTED. If true, the firmware is available ONLY for MAC Address-based delivery

SWID and SWVERS must match the Firmware string (and its Firmware id and version) and the filename.

If are available more than 1 firmware (i.e. not expired), is delivered only the newer, based on the RELEASED field.

#### OTA_FORBOARD Table

This table is filled only when releasing software for specific board(s).

- STA_MAC. The MAC address of the board
- SWID. The Firmware Id - refers to the homonym field of OTA_RELEASES table
- SWVERS.  The firmware version - refers to the homonym field of OTA_RELEASES table
- ENABLED. If false, the firmware will not be delivered

## Request LOG

All access to the server are recorded into OTA_ACCESS table.

## Installation

#### Prerequisites

- An active Web server with PHP support
- MySQL/MariaDB DBMS

#### DB creation

Use OTA.sql contained in the db directory. Be carefully, each time OTA.sql script is executed, all existing OTA_ tables are deleted and recreated.

#### PHP files

- update.php. The file to be invocated; don't forget to set $localbinpath appropriately
- securities.php. The file containing private infos (dbname, dbusername, ..), not included for obvious reasons. Create it and put it into the same directory of update.php.

#### securities.php template

```
<?php
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "dbname";
?>
```

## ESP8266 example and test

testota directory contains am example usable for test. Create the appropriate securities.h file 

#### securities.h template

```
#define ssid1 "ssid1"
#define password1 "mypassword"
#define ssid2 "ssid2"
#define password2 "mypass1"
// ssid1 & ssid2 are two alternative ssid
// if you have only 1 ssid, set both to it

#define otahost "otahost.domain.it"
#define otaport 80
#define otapath "/esp8266ota/update.php"
#define otaprotocol "https" // http or https

```



## Future works

- Black list of boards not enabled for receiving updates
- Define board groups for specific updates and black listing
- Web front end for all operations

## References

- http://arduino.esp8266.com/Arduino/versions/2.0.0/doc/ota_updates/ota_updates.html#introduction
- http://esp8266.fancon.cz/esp8266-ota-from-server-arduino/arduino-simple-server-ota.html
- https://arduino-esp8266.readthedocs.io/en/2.4.0/ota_updates/readme.html
- https://8266iot.blogspot.com/2017/11/automatic-unattended-update-of-esp8266.html

