/* 
*/
#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266httpUpdate.h>
#include "securities.h"
/*
 * securities.h
 * 
#define ssid1 "first ssd"
#define password1 "secret password 1"
#define ssid2 "second ssd"
#define password2 "secret password 2"

#define otahost "otahost.domain.tld"
#define otaport 80
#define otapath "/update.php"
#define otaprotocol "http" // http or https
*/

#define fw_vers "nomef_322"

#define ledPin D3
#define delay1 100
#define delay2 1000
#define maxRetr 10

int retr=0;
ESP8266WiFiMulti wifiMulti;     // Create an instance of the ESP8266WiFiMulti class, called 'wifiMulti'
WiFiClient espClient;

// the setup function runs once when you press reset or power the board
void setup() {
  String otaurl;
  // initialize digital pin as an output.
  Serial.begin(19200);
  pinMode(ledPin, OUTPUT);

  Serial.println();
  Serial.println();
  Serial.print("Versione: ");
  Serial.println(fw_vers);
  Serial.println("Connecting ");

  /* Explicitly set the ESP8266 to be a WiFi-client, otherwise, it by default,
     would try to act as both a client and an access-point and could cause
     network-issues with your other WiFi-devices on your WiFi-network. */
  
  WiFi.mode(WIFI_STA);
  wifiMulti.addAP(ssid1, password1);
  wifiMulti.addAP(ssid2, password2);
  
  retr=0;
  while ((wifiMulti.run() != WL_CONNECTED) && (retr<maxRetr)){
    delay(500);
    Serial.print(".");
    retr++;
  }
  
  Serial.println("");
  Serial.print("WiFi connected: ");
  Serial.println(WiFi.SSID());
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());

  /* warning - the update is done after each reboot now */
  // t_httpUpdate_return ret = ESPhttpUpdate.update(otahost, otaport, otapath, fw_vers); // deprecated
  otaurl = String(otaprotocol);
  otaurl = String(otaurl + "://");
  otaurl = String(otaurl + otahost);
  otaurl = String(otaurl + ":");
  otaurl = String(otaurl + otaport);
  otaurl = String(otaurl + otapath);
  Serial.print("OTA URL: ");
  Serial.println(otaurl);
  ESPhttpUpdate.rebootOnUpdate(false);
  t_httpUpdate_return ret = ESPhttpUpdate.update(espClient, otaurl, fw_vers); //Location of your binary file
  /*upload information only */
  switch (ret) {
    case HTTP_UPDATE_FAILED:
      Serial.printf("HTTP_UPDATE_FAILED Error (%d): %s", ESPhttpUpdate.getLastError(), ESPhttpUpdate.getLastErrorString().c_str());
      break;
    case HTTP_UPDATE_NO_UPDATES:
      Serial.println("HTTP_UPDATE_NO_UPDATES");
      break;
    case HTTP_UPDATE_OK:
      // actually this branch is never activated because the board restarts immediately after update
      Serial.println("HTTP_UPDATE_OK");
      Serial.println("Restarting ....");
      delay(5000);
      ESP.restart();
      break;
  }
 
} // setup

// the loop function runs over and over again forever
void loop() {
  digitalWrite(ledPin, HIGH);    // turn the LED off by making the voltage LOW
  delay(delay1);                       // wait for a second
  digitalWrite(ledPin, LOW);    // turn the LED off by making the voltage LOW
  delay(delay1);                       // wait for a second
  digitalWrite(ledPin, HIGH);    // turn the LED off by making the voltage LOW
  delay(delay1);                       // wait for a second
  digitalWrite(ledPin, LOW);    // turn the LED off by making the voltage LOW
  delay(delay1);                       // wait for a second
  digitalWrite(ledPin, HIGH);    // turn the LED off by making the voltage LOW
  delay(delay1);                       // wait for a second
  digitalWrite(ledPin, LOW);    // turn the LED off by making the voltage LOW
  delay(delay2);                       // wait for a second
}
