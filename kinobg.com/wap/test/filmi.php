<?php /**/ ?><?

//session_start(); 

header("Content-type: text/vnd.wap.wml");

include "dbconnect.inc.php";
include "cyr2lat.inc.php";
include "kino-commons.php";

/*/////////
if(false)
{
   $q = "UPDATE $tbl_agenda SET tsWhen=1105698180 WHERE tsWhen=1095393601";
   mysql_query($q) or die(mysql_error());
   exit('SUCCESS.');
}
/////////*/

if(isset($_REQUEST))
   $RQ = $_REQUEST;


//exit;

if(!isset($RQ['kino']))
{
   //echo "njama izbrano kino";
   //header('Location: kino.php');
   //exit;
   $RQ['kino'] = 25;
}

$kinoid = $RQ['kino'];

setcookie("kinobg-kino", $kinoid);

//$_SESSION['kino'] = $kinoid;

//$q = "SELECT * FROM $tbl_agenda WHERE (CinemaID=$kinoid) AND (tsWhen<=NOW()) ORDER BY tsWhen DESC";
$q = "SELECT * FROM $tbl_agenda WHERE (CinemaID=$kinoid) AND (FROM_UNIXTIME(tsWhen) BETWEEN (DATE_SUB(NOW(), INTERVAL '7' DAY)) AND NOW()) ORDER BY tsWhen DESC";
//echo $q;
$sqlres = mysql_query($q) or die(mysql_error() . "<br> IN SQL -----<br> $q");

/*if(mysql_num_rows($sqlres)==1)
{
   $r = mysql_fetch_assoc($sqlres);
   header("Location: film.php?film=".$r['ID']);
   exit;
}*/

echo '<?xml version="1.0"?><!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">' . "\n";
echo "<wml>\n";

$grad = CityIDFromCinemaID($kinoid);

//echo "<template></template>";

$kinoname = CinemaNameByID($kinoid);
echo "<card title='Kino: $kinoname'>\n";
echo "<p>$template_header <br/> <a href='kino.php?grad=$grad'>izbor kina</a></p>\n";
echo "<p>\n";

//var_dump($_REQUEST);

if(mysql_num_rows($sqlres)==0)
{
   //no movies this week :)
   //var_dump($RQ);
   echo "Niama informacia za filmite w momenta. Molia, opitajte po-kysno.";
}
else
{
   echo "<b>Izberete film: </b>";
   echo "<select name='film'>\n";

   //echo "<option value='0' selected='selected'>-Izbor kina-</option>";
   
   $date = '';
   
   while( $r = mysql_fetch_assoc($sqlres) )
   {
      if($date=='')
         $date = $r['tsWhen'];
         
      if( $date != $r['tsWhen'] )
         break;

      $agendaid = $r['ID'];

      $onpick = 'onpick="film.php?kino='.$kinoid.'&amp;film=$(film)"';

      if($r['Type']=='list')
      {
         //var_dump($r);
         $filmid = $r['Film'];
        
         //avoid repeatance
         if(isset($added[$filmid]))
            continue;
         $added[$filmid] = true;
         
         $q = "SELECT * FROM $tbl_film_info WHERE ID=$filmid";
         $filmres = mysql_query($q) or die(mysql_error());
         if(mysql_num_rows($filmres)==0)
            die("no movie $filmid");
         $film = mysql_fetch_assoc($filmres);
         $title = transliterate($film['Title']);
         
         echo "<option value='$agendaid' $onpick>$title" ."</option>";
         
         mysql_free_result($filmres);
      }
      else
      if($r['Type']=='raw')
      {
         $title = stripslashes(transliterate($r['Film']));
         echo "<option value='$agendaid' $onpick>$title</option>";
      }
   }
   
   mysql_free_result($sqlres);
   echo "</select>";
}


echo "</p>";
echo "</card>";
echo "</wml>";

?>