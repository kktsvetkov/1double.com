<?php /**/ ?><?

//session_start(); 

header("Content-type: text/vnd.wap.wml");

$tbl_cities="tbl_city";
$tbl_cinemas="tbl_cinema";
$tbl_agenda="tbl_agenda";
$tbl_film_info = "tbl_film_info";

include "dbconnect.inc.php";
include "cyr2lat.inc.php";
include "kino-commons.php";

/*/////////
if(false)
{
   $q = "UPDATE $tbl_agenda SET tsWhen=1105698180 WHERE id IN (9143, 9463, 9607)";
   mysql_query($q) or die(mysql_error());
   exit('SUCCESS.');
}
//////////*/

if(isset($_REQUEST))
   $RQ = $_REQUEST;

if(empty($RQ['kino']))
{
   header("location: /");
   exit;
}
$kinoid = intval($RQ['kino']);

if(!isset($RQ['film']))
{
   header('Location: filmi.php');
   exit;
}

$agendaid = $RQ['film'];
if(!isset($agendaid))
{
   header('Location: kino.php');
   exit;
}

$q = "SELECT * FROM $tbl_agenda WHERE ID=$agendaid";
$sqlres = mysql_query($q) or die(mysql_error());

/*$q = "SELECT * FROM $tbl_agenda WHERE CinemaID=$kino AND
    now() BETWEEN FROM_UNIXTIME(tsWhen) 
    and ADDDATE(FROM_UNIXTIME(tsWhen), INTERVAL 7 DAY)";
$sqlres = mysql_query($q) or die(mysql_error() . "<br> IN SQL -----<br> $q");*/

echo '<?xml version="1.0"?><!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">';
echo "\n<wml>\n";

//echo "<template>$template_header</template>";

// FETCH CINEMA NAME
$kinoname = CinemaNameByID($kinoid);

// FETCH MOVIE NAME / DATE
$q = "SELECT * FROM $tbl_agenda WHERE ID=$agendaid";
$sqlFilm = mysql_query($q) or die(mysql_error() . "<br/> IN SQL -----<br> $q");
$r = mysql_fetch_assoc($sqlFilm);
$from = date("d.m.Y", $r["tsWhen"]);
//var_dump($r);
if($r["Type"]=='raw')
{
   $film = transliterate($r['Film']);
}
else
{
   $filmid = mysql_escape_string($r['Film']);
   mysql_free_result($sqlFilm);
   $sqlFilm = mysql_query("SELECT * FROM $tbl_film_info WHERE ID=$filmid");
   if(mysql_num_rows($sqlFilm)==0)
      die("no movie $filmid");
   $r = mysql_fetch_assoc($sqlFilm);
   $film = transliterate($r['Title']);
   //$film = $agendaid;
}
mysql_free_result($sqlFilm);
//$film = transliterate($r['Cinema']);


echo "<card title='Kino: $kinoname'>";
echo "<p>$template_header <br/> <a href='kino.php?grad=$grad'>izbor kina</a></p>";
echo "<p>";

if(!isset($sqlres) || mysql_num_rows($sqlres)==0)
{
   //no movies this week :)
   var_dump($_REQUEST);
   echo "Niama informacia za filma v momenta. Molia, opitajte po-kysno.";
}
else
{
   echo "Film: $film</p><p>";
   echo "Ot data: $from</p><p>";
   $r = mysql_fetch_assoc($sqlres);
   echo transliterate($r['Agenda']);
   /*echo '<do type="accept" label="izberi">';
   echo '<go href="film.php"><postfield name="" value="$(film)"/></go>';
   echo '</do>';

   echo "Izberete film:";
   echo "<select name=\"film\" ivalue=\"0\">";

   echo "<option value='0' selected='selected'>-Izbor kina-</option>";
   
   while( $r = mysql_fetch_assoc($sqlres) )
   {
      //var_dump($r);
      if($r['Type']=='list')
      {
         //var_dump($r);
         $id = $r['Film'];
        
         //avoid repeatance
         if(isset($added[$id]))
            continue;
         $added[$id] = true;
         
         $q = "SELECT * FROM $tbl_film_info WHERE ID=$id";
         $filmres = mysql_query($q) or die(mysql_error());
         if(mysql_num_rows($filmres)==0)
            die("no movie $id");
         $film = mysql_fetch_assoc($filmres);
         $title = transliterate($film['Title']);
         
         echo "<option value='$id'>$title</option>";
         
         mysql_free_result($filmres);
      }
      else
      {
         echo "NOT IMPLEMENTED";
      }
   }
   
   echo "</select>";*/
   mysql_free_result($sqlres);
}


echo "</p>";
echo "</card>";
echo "</wml>";
//*/
?>