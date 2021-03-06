<?php
/*   Luxobot © Luxo 2007

    Minor modifications and fixes by Steinsplitter, 2014 - 2015

    This file is part of Luxobot.

    Luxobot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Luxobot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Luxobot.  If not, see <http://www.gnu.org/licenses/>.

    */

// TO DO: Rewrite, code complety outdated and possible security vulnerabilities

// ############### EDIT WIKIPEDIA - FUNCTION ###############
function wikiedit($project,$page,$newtext,$description,$minor)
{
global $cookies;
  logfile("Funktion gestartet...");
  logfile("Schreibe Text am ".date("r",time())." in die Seite '$page'.");

  $useragent = "Steinsplitter (wmflabs; php) steinsplitter-wiki@live.com";

//$cookies
if(!$cookies["commonswikiUserName"] OR !$cookies["commonswikiUserID"])
{
  include "ac.php";
  logfile("Login to $project!\n");
  wikilogin($username,$password,$project,$useragent);
  logfile("logged in to $project!\n");
  print_r($cookies);
}
else
{
  logfile("already logged in to $project!\n");
}




//echo $header."\n\n";

// Response Body auslesen
/*while (!feof($fp)) {
$linew=fgets($fp,255);
$bodyw.=$linew;
}
echo $bodyw;*/
$header = "";


//Angemeldet, Cookies ausgelesen, editieren kann beginnen**************
$fpb = fsockopen ($project, 80, $errno, $errstr, 30);

//Bearbeiten-Seite aufrufen, um wpEditToken & cookie zu erhalten ***************
$getrequest = "/w/index.php?title=".urlencode($page)."&action=edit";
fputs($fpb, "GET $getrequest HTTP/1.1\n");
fputs($fpb, "Host: $project\n");
fputs($fpb, "User-Agent: $useragent\n");
fputs($fpb, "Accept: $accept\n");
fputs($fpb, "Accept-Language: de\n");

foreach ($cookies as $key=>$value)
{
  $cookie .= trim($value).";";
}
$cookie = substr($cookie,0,-1);



logfile("Lade Seite; Cookies: $cookie\n");

fputs($fpb, "Cookie: ".$cookie."\n");

fputs($fpb, "Connection: close\n");
fputs($fpb, "\n");


//Response Header auslesen forallem cooke********************
do {
$linex=fgets($fpb,255);
$headerrx.=$linex;

//auf cookie prüfen
if(substr($linex,0,11) == "Set-Cookie:")
{
$rawcookie = substr($line,11,strpos($line,";")-11); //Format: session=DFJ3ASD2S
  $cookiename = trim(substr($rawcookie,0,strpos($rawcookie,"=")));
$cookies[$cookiename] = $rawcookie;
}

} while (trim($linex)!="");

//cookie-header erneut generieren
$cookie = "";
foreach ($cookies as $key=>$value)
{
  $cookie .= trim($value).";";
}
$cookie = substr($cookie,0,-1);


logfile("Neue Cookies: $cookie\n");

//echo $headerrx."\n\n";
// Response Body auslesen**********************
while (!feof($fpb)) {
$line=fgets($fpb,255);
$bodyy.=$line;
//Die verschiedenen form-data's auslesen
if(strstr($line, "wpStarttime"))
{
$formdata['wpStarttime'] = $line;
}
if(strstr($line, "wpEdittime"))
{
$formdata['wpEdittime'] = $line;
}
if(strstr($line, "wpScrolltop"))
{
$formdata['wpScrolltop'] = $line;
}
if(strstr($line, "wpEditToken"))
{
$formdata['wpEditToken'] = $line;
}
if(strstr($line, "wpAutoSummary"))
{
$formdata['wpAutoSummary'] = $line;
}
if(strstr($line, "wpUltimateParam"))
{
$formdata['wpUltimateParam'] = $line;
}
if(strstr($line, "wpSave"))
{
$formdata['wpSave'] = $line;
}
}
logfile("Seite geladen, Anmeldung prueffen.");

if(strstr($bodyy,'"wgUserName":"SteinsplitterBot",'))
{
logfile("Anmeldung erfolgreich!");

//ende auslesen, verbindung schliessen
fclose($fpb);

//aus formdatas nur values nehmen

foreach($formdata as $type => $formcontent)
{

$t1 = strstr($formcontent,'value="');
$t2 = strpos($t1,'"',7);
$t1 = substr($t1,7,$t2-7);

$formdata["$type"] = $t1;
}


// ########################### POST-CONTENT VORBEREITEN #####################

//content vorbereiten
$content = "wpSection=&wpStarttime=".urlencode($formdata['wpStarttime'])."&wpEdittime=".urlencode($formdata['wpEdittime'])."&wpScrolltop=".urlencode($formdata['wpScrolltop'])."&wpTextbox1=".urlencode($newtext)."&wpSummary=".urlencode($description)."&wpMinoredit=".urlencode($minor)."&wpWatchthis=1&wpSave=".$formdata['wpSave']."&wpEditToken=".urlencode($formdata['wpEditToken'])."&wpAutoSummary=".urlencode($formdata['wpAutoSummary'])."&wpUltimateParam=".urlencode($formdata['wpUltimateParam']);



logfile("Content (".strlen($content)." Zeichen) vorbereitet, verbinde zum Speichern!");

//######## POST-Content vorbereitet, verbinden & POST-header senden #########

//zum speichern verbinden
$fpc = fsockopen ($project, 80, $errno, $errstr, 30);
//Speichern per Post.. ***************

$referer = "http://$project/w/index.php?title=".urlencode($page)."&action=edit";

fputs($fpc, "POST /w/index.php?title=".urlencode($page)."&action=submit HTTP/1.1\n");
fputs($fpc, "Host: $project\n");
fputs($fpc, "User-Agent: $useragent\n");
fputs($fpc, "Accept: $accept\n");
fputs($fpc, "Accept-Language: de\n");
//fputs($fpc, "Accept-Encoding: gzip,deflate\n"); //gzip --> seite komprimiert!
fputs($fpc, "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\n");
fputs($fpc, "Keep-Alive: 300\n");
fputs($fpc, "Connection: keep-alive\n");
fputs($fpc, "Referer: $referer\n");
fputs($fpc, "Cookie: ".$cookie."\n");
fputs($fpc, "Content-Type: application/x-www-form-urlencoded\n");
fputs($fpc, "Content-Length: ".strlen($content)."\n");
fputs($fpc, "\n");
fputs($fpc, $content);
logfile("Header gesendet.");

//Response Header auslesen vorallem cooke********************
$xxx = 0;
do {
$linex=fgets($fpc,255);
if($xxx == 0)
{
  $linexx = $linex;
}
$xxx += 1;

$headerrx.=$linex;

//auf cookie prüfen
if(substr($linex,0,11) == "Set-Cookie:")
{
$rawcookie = substr($linex,11,strpos($linex,";")-11); //Format: session=DFJ3ASD2S
  $cookiename = trim(substr($rawcookie,0,strpos($rawcookie,"=")));
$cookies[$cookiename] = $rawcookie;
}

} while (trim($linex)!="");




if(strstr($linexx,"Moved Temporarily"))
{
logfile("Bearbeitung Erfolgreich.");
return true;
}
else
{
logfile("BEARBEITUNG FEHLGESCHLAGEN!.\nFehler-Header: $linexx");

//      var_dump($headerrx);
//      suicide("");
return false;
}




/*
while (!feof($fpc)) {
$linew=fgets($fpc,255);
$bodyw.=$linew;
}
logfile("-------\n".$bodyw."----------\n"); */
fclose($fpc);

echo"ende.";
}
else
{
logfile("ANMELDUNG FEHLGESCHLAGEN, KONNTE NICHT ANMELDEN!\n");
suicide();
}

}

function wikilogin($username,$password,$project,$useragent)
{
  global $cookies;

  $getrequest = (substr($project,-1) == "/") ? "w/api.php?action=login" : "/w/api.php?action=login";
  $project = (substr($project,0,7) == "http://") ? $project : "http://".$project;

  logfile("Login via API to $project as $username...");

  $postlogin = "lgname=".urlencode($username)."&lgpassword=".urlencode($password)."&format=php";

  if(!$useragent) { $useragent = "Luxobot (wmflabs; php) steinsplitter-wiki@live.com";  }
  $ch = curl_init($project.$getrequest);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postlogin);
  curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_COOKIEJAR, "/data/project/sbot/Rotatebot/cks");
  curl_setopt($ch, CURLOPT_HEADER, true);

  $rx = curl_exec($ch);
  list($headers, $data) = explode("\r\n\r\n", $rx, 2);
  $data = unserialize($data);
  curl_close($ch);

  foreach (explode("\n", $headers) as $header) {
    if (substr_compare($header, 'Set-Cookie:', 0, 11, true) === 0) {
      $header_value = trim( explode(':', $header, 2)[1] );
      $cookie_pair = trim( explode(';', $header_value, 2)[0] );
      list($raw_key, $raw_value) = explode('=', $cookie_pair, 2);
      $cookies[$raw_key] = $raw_key . '=' . $raw_value;
    }
  }

  if($data['login']['result'] == "NeedToken")
  {
    $postlogin = "lgname=".urlencode($username)."&lgpassword=".urlencode($password)."&lgtoken=".urlencode($data['login']['token'])."&format=php";
    $ch = curl_init($project.$getrequest);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postlogin);
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "/data/project/sbot/Rotatebot/cks");
    curl_setopt($ch, CURLOPT_COOKIEJAR, "/data/project/sbot/Rotatebot/cks");
    curl_setopt($ch, CURLOPT_COOKIE, implode('; ', array_values($cookies)));
    curl_setopt($ch, CURLOPT_HEADER, true);

    $rx = curl_exec($ch);
    list($headers, $data) = explode("\r\n\r\n", $rx, 2);
    $data = unserialize($data);
    curl_close($ch);

    if($data['login']['result'] == "Success")
    {
      logfile("Login erfolgreich");

      foreach (explode("\n", $headers) as $header) {
        if (substr_compare($header, 'Set-Cookie:', 0, 11, true) === 0) {
          $header_value = trim( explode(':', $header, 2)[1] );
          $cookie_pair = trim( explode(';', $header_value, 2)[0] );
          list($raw_key, $raw_value) = explode('=', $cookie_pair, 2);
          $cookies[$raw_key] = $raw_key . '=' . $raw_value;
        }
      }

    } else {
      suicide("Login nicht erfolgreich! (".$data['login']['result'].")");
    }
  }

  return $cookies;
}

?>
