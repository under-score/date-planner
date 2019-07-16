<?php
date_default_timezone_set("Europe/Berlin");
ini_set('session.use_cookies', '0');
$h="https://".$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
$u='https://'.$_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

$to = isset($_GET['to']) ? filter_var($_GET['to'], FILTER_SANITIZE_STRING) : "";
$to = isset($_POST['to']) ? filter_var($_POST['to'], FILTER_SANITIZE_STRING) : $to;
if ($to=="") $to="en";
$to =="de" ? setlocale(LC_TIME, "de_DE") : setlocale (LC_TIME,"en_US");

/* -- functions --*/

function translate($phrase,$to){
    static $lang = array(
        'Veranstaltung' => 'meeting',
		'Kalendertage anklicken!' => 'select day',
		'Uhrzeit' => 'time',
        'speichern' => 'save',
		'<td>Mo</td><td>Di</td><td>Mi</td><td>Do</td><td>Fr</td><td>Sa</td><td>So</td>' => '<td>Mo</td><td>Tu</td><td>We</td><td>Th</td><td>Fr</td><td>Sa</td><td>Su</td>',
        'eintragen' => 'save',
  		'*vorhandenen Namen zum Ändern oder Ende eingeben' => 'edit earlier entry oder end',
        'mailen' => 'mail',
        'abonnieren' => 'subscribe'
    );
    if ($to=="de" && array_key_exists($phrase,$lang)) {
      return $phrase;
    }
    elseif ($to=="en") {
      return $lang[$phrase];
    }
}

function year2array($year) {
    $res = $year >= 1970;
    if ($res) {
      $dt = strtotime("-1 day", strtotime("$year-01-01 00:00:00"));
      $res = array();
      $week = array_fill(1, 7, false);
      $last_month = 1;
      $w = 1;
      do {
        $dt = strtotime('+1 day', $dt);
        $dta = getdate($dt);
        $wday = $dta['wday'] == 0 ? 7 : $dta['wday'];
        if (($dta['mon'] != $last_month) || ($wday == 1)) {
          if ($week[1] || $week[7]) $res[$last_month][] = $week;
          $week = array_fill(1, 7, false);
          $last_month = $dta['mon'];
          }
        $week[$wday] = $dta['mday'];
        }
      while ($dta['year'] == $year);
      }
    return $res;
}

function month2table($id, $year, $m, $calendar_array) {
    $res = '<div id="i'.$id.'">';
	switch ($id) {
    case 1:
		$res = '<div id="i1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . strftime('%B', mktime(0, 0, 0, $m, 10)) . " " . $year .'&nbsp;&nbsp;&nbsp;<a href="#" onclick="
document.getElementById(\'i1\').style.display = \'none\';
document.getElementById(\'i2\').style.display = \'block\';
return false;
">&gt;&gt;</a>';
		break;
	case 2:
		$res = '<div id="i2"><a href="#" onclick="
document.getElementById(\'i2\').style.display = \'none\';
document.getElementById(\'i1\').style.display = \'block\';
return false;
">&lt;&lt;</a>&nbsp;&nbsp;&nbsp;' . strftime('%B', mktime(0, 0, 0, $m, 10)) . " " . $year .'&nbsp;&nbsp;&nbsp;<a href="#" onclick="
document.getElementById(\'i2\').style.display = \'none\';
document.getElementById(\'i3\').style.display = \'block\';
return false;
">&gt;&gt;</a>';
		break;
	case 3:
		$res = '<div id="i3"><a href="#" onclick="
document.getElementById(\'i3\').style.display = \'none\';
document.getElementById(\'i2\').style.display = \'block\';
return false;
">&lt;&lt;</a>&nbsp;&nbsp;&nbsp;' . strftime('%B', mktime(0, 0, 0, $m, 10)) . " " . $year .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		break;
	}
    $res .= '<table class="selecttable"><tr><td>Mo</td><td>Di</td><td>Mi</td><td>Do</td><td>Fr</td><td>Sa</td><td>So</td></tr>';
    foreach ($calendar_array[$m] as $month=>$week) {
      $res .= '<tr>';
      foreach ($week as $day) {
        $f= $day==date("j") & $m==date("n") ? "numberCircle1 numberCircle3" : "numberCircle1";
        $res .= '<td>' . ( $day ? '<div class="day ' . $f .'" id="i' . $day . '_' . $m . '_' . substr( $year, -2) . '">' . $day . '</div>' : '' ) . '</td>';
        }
      $res .= '</tr>';
      }
    $res .= '</table></div>';
    return $res;
}

function gen_uuid() {
  return sprintf( '%04x%04X',
    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
  );
}

class MyDB extends SQLite3 {
  function __construct() {
    $this->open('index.sqlite3');
  }
}

/* -- database --*/

if (!file_exists('index.sqlite3')) {
  new SQLite3('index.sqlite3');
  chmod('index.sqlite3', 0777);
  sleep(3);

  $sql =<<<EOF
CREATE TABLE 'termine' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'uuid' TEXT NOT NULL, 'event' TEXT,'eventdate' DATETIME,'member' TEXT, 'choice' TEXT, 'ip' TEXT, 'active' BOOLEAN, 'entrydate' DATETIME DEFAULT CURRENT_TIMESTAMP, 'uuidics' TEXT NOT NULL , 'eventinfo' TEXT);
EOF;
  
  # CREATE INDEX 'uuidx' ON "termine" ("uuid" ASC);
  $db = new MyDB();
  $ret = $db->query($sql);
  $db->close();
  exit('reload now');
}

/* -- setup --*/

if ( !isset($_GET['uuid']) ) header('Location: ' . $u . '/index.php?to=' . $to . '&uuid=' . gen_uuid() );

$uuid=filter_var($_GET['uuid'], FILTER_SANITIZE_STRING);
$h2 = $u . '/?uuid=' . $uuid;

$event="";
$i=1;
$db = new MyDB();
$sql = 'SELECT * FROM termine WHERE uuid = "' . $uuid .'" AND active=1 LIMIT 1';
$ret = $db->query($sql);

$mode="ini";
while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
  $mode="append";
}

/* ics with webcal header */
if ( isset( $_GET['ics1']) || isset( $_GET['amp;ics1']) ) $mode="ics1";

/* ics with download header */
if ( isset( $_GET['ics2']) || isset( $_GET['amp;ics2']) ) $mode="ics2";

/* rss subscription */
if ( isset( $_GET['rss1']) || isset( $_GET['amp;rss1']) ) $mode="rss1";

/* new entry */
if ( isset($_POST['mode']) ) $mode="first";

$h=  str_replace("amp;","",$h);

/* -- rss --*/

if ($mode=="rss1" ) {
   header("Content-Type: application/rss+xml; charset=utf-8");
   $rssfeed = '<?xml version="1.0" encoding="utf-8"?>';
   $rssfeed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
   $rssfeed .= '<channel>';
   $rssfeed .= '<title>Terminplaner</title>';
   $rssfeed .= '<link>https://www.wjst.de/termine</link>';
   $rssfeed .= '<description>Terminplanner</description>';
   $rssfeed .= '<language>en-us</language>';
   $rssfeed .= '<copyright>(c) 2014 wjst.de</copyright>';
  $sql =<<<EOF
SELECT uuidics,event,entrydate,member,GROUP_CONCAT(member||' '||substr(eventdate,1,11)||choice) AS c FROM "termine" GROUP BY member LIMIT 10;
EOF;
  $sql =<<<EOF
SELECT id, event, uuid, entrydate, member FROM termine ORDER BY id DESC LIMIT 10;
EOF;
  $ret = $db->query($sql);
  while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
        $rssfeed .= '<item>';
        $rssfeed .= '<title>' . $row["event"] . ' '. $member . '</title>';
        $rssfeed .= '<link>https://www.wjst.de/termine/?uuid=' . $uuid . '</link>';
        $rssfeed .= '<guid isPermaLink="false">' . $row["uuidics"]  . md5($row["member"]) .  '</guid>';
        $rssfeed .= '<description>' . htmlspecialchars($row["member"]) . '</description>';
        $rssfeed .= '<pubDate>' .

date("D, d M Y H:i:s O", strtotime(  $row["entrydate"] ))

 . '</pubDate>';
        $rssfeed .= '</item>';
  }
  $rssfeed .= '</channel>';
  $rssfeed .= '</rss>';
  echo $rssfeed;
  $db->close();
  exit();
}

/* --save --*/

if ($mode=="first" ) {
  $db = new MyDB(); 
  
  $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
  $_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
  $ip = $_SERVER['REMOTE_ADDR'];
  $ip = substr($ip, 0, strrpos($ip, ".")) . ".xxx";
  $event = $_POST['event'];
  if ($event=="") { exit("Error: Event missing"); }
  $member = $_POST['p'];
  if ($member=="") { $member="empty"; }
  $j = $_POST['i'];
  $known=0;
  $sql =<<<EOF
SELECT * FROM termine WHERE uuid="{$uuid}" AND member="{$member}" AND active=1;
EOF;
  $ret = $db->query($sql);
  while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
    $known=1;
  }

  #file_put_contents('debug.log', print_r(get_defined_vars(),1) );

  for($i=0; $i<=$j; $i++) {
    #$uuidics = md5(uniqid(mt_rand(), true));
    $uuidics = md5( $_SERVER['HTTP_USER_AGENT'] );
    isset($_POST['o'.$i]) ? $eventdate = $_POST['o'.$i]." ".$_POST['t'.$i] : $eventdate="";
    if ($eventdate=="") continue;

    $suchmuster = '/^\s{0,2}([\d]{1,2})\.([\d]{1,2})\.([\d]{1,2})\s([\d]{1,2}):([\d]{1,2})/';

    $res = preg_match($suchmuster,$eventdate, $t);
    if ($res==0) { exit("Error: Dates wrong"); }

    $eventdate = date("Y-m-d H:i:s", mktime($t[4], $t[5], 0, $t[2], $t[1], "20".$t[3]));
    isset($_POST['p'.$i]) ? $choice=$_POST['p'.$i] : $choice="0";
    isset($_POST['c'.$i]) ? $eventinfo=$_POST['c'.$i] : $eventinfo="";
    $sql0 =<<<EOF
INSERT INTO termine (uuid,event,eventdate,eventinfo,member,choice,ip,active,uuidics)
VALUES ('{$uuid}','{$event}','{$eventdate}','{$eventinfo}','{$member}','{$choice}','{$ip}',1,'{$uuidics}');
EOF;
    $sql1 =<<<EOF
UPDATE termine
SET choice="{$choice}", ip="{$ip}"
WHERE uuid="{$uuid}" AND member="{$member}" AND eventdate="{$eventdate}" AND uuidics="{$uuidics}" AND active=1;
EOF;
    $known==0 ? $db->query($sql0) : $db->query($sql1);
  }
  $db->close();
  $mode="append";
  $i = $j;
}

/*-- close --*/

$db = new MyDB();
$end = array("end","Ende","END","ENDE");
$sql='SELECT eventdate,member,choice FROM termine WHERE uuid = "'.$uuid.'" AND active=1 ';
$ret = $db->query($sql);
$sd="";
while($row = $ret->fetchArray(SQLITE3_ASSOC) ){ 
  ($row['choice']==1 && in_array($row['member'], $end)) ? $sd=$row['eventdate'] : FALSE;
}
$db->close();	


/*-- append --*/

if ($mode=="append" ) {

  $db = new MyDB();
  $sql =<<<EOF
SELECT eventdate, event, eventinfo, COUNT(eventdate) FROM termine WHERE uuid = "{$uuid}" AND active=1 GROUP BY eventdate ORDER BY eventdate ASC;
EOF;
  $ret = $db->query($sql);
  while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
    $coltitle[] = $row['eventdate'];
    $colinfo[] = $row['eventinfo'];
    $redrow[ $row['eventdate'] ] = "lightgrey";
    $event = $row['event'];
  }
  if ( !isset($coltitle) ) exit("error: no data");

  $sql =<<<EOF
SELECT member, COUNT(member) FROM termine WHERE uuid = "{$uuid}" AND active=1 GROUP BY member ORDER BY id ASC;
EOF;
  $ret = $db->query($sql);
  while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
    $rowtitle[] = $row['member'];
  }

  foreach($rowtitle as $m) {
    $isrow=$redrow;
    $sql =<<<EOF
SELECT eventdate,choice FROM termine WHERE uuid = "{$uuid}" AND member="{$m}" AND active=1 ORDER BY id ASC;
EOF;
    $ret = $db->query($sql);
    while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
      foreach($coltitle as $v) {
        if ( $v == $row['eventdate']  && $row['choice']==1 ) $isrow[ $row['eventdate'] ] = "rgb(0,255,0)";
        if ( $v == $row['eventdate']  && $row['choice']==1 && $row['eventdate'] == $sd ) $isrow[ $row['eventdate'] ] = "rgb(0,190,0)";
      }
    }
    $full[] =$isrow;
  }
  $db->close();
  $mode="append";
  $i=sizeof($isrow);
}

/* --ics --*/

if ($mode=="ics1" || $mode=="ics2") {
  $db = new MyDB();
  $sql='SELECT event, min(eventdate) FROM termine WHERE uuid = "'.$uuid.'" AND active=1 GROUP BY eventdate LIMIT 1';
  $ret = $db->query($sql);
  while($row = $ret->fetchArray(SQLITE3_ASSOC) ){ $event = $row['event']; }

  $output = 'BEGIN:VCALENDAR
METHOD:PUBLISH
VERSION:2.0
X-WR-CALNAME:'.$event.'
PRODID:-//wjst.de//EN
';

  if ($sd=="") { 
    $sql = 'SELECT eventdate, event, count(eventdate) AS cnt, uuidics, entrydate FROM termine WHERE uuid = "' . $uuid . '" AND active=1 GROUP BY eventdate;';
  }
  else {
    $sql = 'SELECT eventdate, event, count(eventdate) AS cnt, uuidics, entrydate FROM termine WHERE uuid = "' . $uuid . '" AND active=1 AND eventdate = "' . $sd . '" GROUP BY eventdate;';
  }

  $ret = $db->query($sql);

  while($row = $ret->fetchArray(SQLITE3_ASSOC) ){

    $sql2 = 'SELECT count(eventdate) AS cnt2 FROM termine WHERE uuid = "' . $uuid . '" AND active=1  AND eventdate = "' . $row['eventdate'] . '" AND choice = "1"';
   
    $ret2 = $db->query($sql2);
    while($row2 = $ret2->fetchArray(SQLITE3_ASSOC) ){
      $cnt2 = $row2['cnt2'];
    }

    $suchmuster = '/^([\d]{4})-([\d]{2})-([\d]{2})\s([\d]{2}):([\d]{2}):([\d]{2})/';
    $sd=="" ? $stack = $row['eventdate'] : $stack = $sd;
    preg_match($suchmuster,$stack, $t);
    # as UTC+1
    $t4 = $t[4] -1;
    $eventdate = $t[1] . $t[2] . $t[3] . 'T' . $t4 . $t[5] . $t[6] . 'Z';
    preg_match($suchmuster,$stack, $t);
    $t4 = $t[4] -1;
    $entrydate = $t[1] . $t[2] . $t[3] . 'T' . $t4 . $t[5] . $t[6] . 'Z';

   $output .=
'BEGIN:VEVENT
ORGANIZER:mailto:m@wjst.de
VERSION:2.0
SUMMARY:(' . $cnt2 . '/' . $row['cnt'] . ') ' . $row['event'] . '
PROID:' . $h .'
DESCRIPTION:' . $h2 . '
URL;VALUE=URI:' . $h .'
UID:'. md5(uniqid(mt_rand(), true)) . '@wjst.de
STATUS:tentative
LOCATION:not set
DTSTART;TZID=Europe/London:' . $eventdate . '
DTEND;TZID=Europe/London:' . $eventdate . '
END:VEVENT
';

  }
  $db->close();
  $output .= "END:VCALENDAR";
  
  if ($mode=="ics2") {
    header('Content-type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="wjstde'.$uuid.'.ics"');  
    header('Content-Length: '.strlen($output));  
  }
  echo $output;
  exit();
}

/*-- ---- --*/
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href='index.css' type='text/css'>
<title>terminplanner</title>

</head>

<!-- page -->

<body>
<div id="main">

<div class="myh1">terminplanner</div>

<div class="legend">
<a href="<?php echo $u."?"."to=de&uuid=".$uuid;?>">DE</a> | <a href="<?php echo $u."?"."to=en&uuid=".$uuid;?>">EN</a>
</div>

<div class="mobile">
</div>

<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?uuid=" . $uuid;?>" name="mainform" id="mainform">

<div class="mobile">
<br>
</div>

<input type="text" id="event" name="event" placeholder="<?php echo translate('Veranstaltung',$to);?>" value="<?php echo $event;?>" tabindex="1" <?php if ($event!="") echo "readonly";?>>
<br><br>
<input type="hidden" id="i"  name="i" value="<?php echo $i;?>">
<input type="hidden" id="mode"  name="mode" value="<?php echo $mode;?>">
<input type="hidden" id="to"  name="to" value="<?php echo $to;?>">

<!-- new meeting -->

<?php
$tx=date("Y-m-d");
$tx2=date("d.m.y");

$tue = translate('Uhrzeit',$to);
$ini = $tue . '&nbsp;<input class="hm" name="hm" id="hm" value="20:00"><br><br>';
$tue = translate('Kalendertage anklicken!',$to);
$ini .= "<i>". $tue ."</i><br>";
$sp=translate('speichern',$to);

$calarr = year2array( date("Y") );
$ini .=  month2table(1, date("Y"), intval(date("m")), $calarr);
$ini .=  month2table(2, date("Y"), intval(date("m"))+1, $calarr);
$ini .=  month2table(3, date("Y"), intval(date("m"))+2, $calarr);

$ini .=<<<EOF
<div id="ScrollDiv">

<br>
<table class="mytable" id="mytable">
    <tr id="row1a"></tr>
    <tr id="row1b"></tr>
    <tr id="row1c"></tr>
    <tr id="row2"><td><br>
	<input type="text" id="p" name="p" placeholder="Name"></td></tr>
</table>
<br><input type="submit" id="formsubmit" value="$sp">
</div>

<br><br>

<div id="sdiv">
</div>

</form>'
EOF;

ob_start();
?>

<div id="ScrollDiv">
<table class="mytable">

    <tr id="row1">
      <td>
      </td>
      <?php $j=$i;
      for($i=1; $i<=$j; $i++) {
        $d1 = date_format( date_create( $coltitle[ ($i-1) ]), "j");
        $d2 = date_format( date_create( $coltitle[ ($i-1) ]), "m");
        $d3 = date_format( date_create( $coltitle[ ($i-1) ]), "y");
        $d4 = date_format( date_create( $coltitle[ ($i-1) ]), "G");
        $d5 = date_format( date_create( $coltitle[ ($i-1) ]), "i");      
        echo '<td><textarea readonly class="rd-time-selected" rows="2" cols="10" id="o'.$i.'" name="o'.$i.'">' .
        date_format( date_create( $coltitle[ ($i-1) ]), "j.n.y G:i") .
        '</textarea><div id="o'.$i.'" name="o'.$i.'" class="dc">' . 
        strftime("%a<br>%e. %m. %y<br>%H:%M ", mktime($d4, $d5, 0, $d2, $d1, $d3) ) .
		'</div></td>';
      } ?>       
    </tr>

    <tr id="row1b">
    <td>
    </td>
    <?php
    for($i=1; $i<=$j; $i++) {
      echo '<td><textarea readonly class="rd-time-selected" rows="2" cols="10" id="c'.$i.'" name="c'.$i.'">'. $colinfo[ ($i-1) ] .'</textarea><div id="c'.$i.'" name="c'.$i.'" class="dc">' . $colinfo[ ($i-1) ] .'</div></td>';
    }     
    echo '</tr>';

    $k=-1;
    $charJ = "&#10003";
    $charN = "&#10008";	$charN = "&nbsp;";
    
    foreach ($full as $ii => $row) {
      $k++;
      $sd = "";

      if ( !in_array($rowtitle[$k], $end) & !preg_match('/empty|leer/',$rowtitle[$k]) ) {
        echo '<tr><td><input type="text" value="'.$rowtitle[$k].'" readonly></td>';
        foreach ($row as $jj => $cell) {
          $cell == "rgb(0,255,0)" ? $char=$charJ : $char=$charN;      
          echo '<td style="background-color:'.$cell.'"><div class="ok">' . $char . '</div></td>';
        }
        echo "</tr>"; 
      }
    }

    if (!in_array($rowtitle[$k], $end)) {
      echo '<tr id="row2">';
      echo '<td><input type="text" id="p" name="p" placeholder="' . translate('Name*',$to) . '"></td>';
      for($i=1; $i<=$j; $i++) {       
        echo '<td><div class="divcheckbox"><input type="checkbox" value="1" id="p'.$i.'" name="p'.$i.'"/></div></td>';
      }
      echo '</tr>';
    }
    
    ?>
</table>
<span class="legend"><?php echo translate('*vorhandenen Namen zum Ändern oder Ende eingeben',$to);?></span>

</div>

<div style="clear: both"></div>
<div>
<?php
if (!in_array($rowtitle[$k], $end)) {
  echo '<br><input type="submit" id="formsubmit" value="' . translate('eintragen',$to) .  '">';
}
?>
</div>

</form>
<br><br>

<?php
$app = ob_get_contents();
ob_end_clean();

$footer ='

<a href="mailto:?subject=Veranstaltung Termin Anfrage&body=Bitte ergaenzen Sie Ihre freien Termine auf '.$h.'">' .  translate('mailen',$to) . '</a>&nbsp;&nbsp;
<a href="webcal://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '&amp;ics1=TRUE">' .  translate('abonnieren',$to) . '</a>&nbsp;&nbsp;
<a href="http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '&amp;ics2=TRUE" target="new">' .  translate('speichern',$to) . '</a>&nbsp;&nbsp;
<br>';

echo $mode=="ini" ? $ini : $app . $footer;

print <<<EOF

<!-- javascript -->
<script src="index.js" type="text/javascript"></script>

</div>
</body>
</html>
EOF;
?>
