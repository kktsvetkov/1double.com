<?php /**/ ?><?

$page_grad = "index.php";

$tbl_cities="tbl_city";
$tbl_cinemas="tbl_cinema";
$tbl_agenda="tbl_agenda";
$tbl_film_info = "tbl_film_info";

$template_header = "kinobg.com - programata za kinata";

function CityNameByID($gradid)
{
   global $tbl_cities;
   $q = "SELECT * FROM $tbl_cities WHERE ID=$gradid";
   $sqlres = mysql_query($q) or die(mysql_error() . " in SQL: $q");
   if(mysql_num_rows($sqlres)==0)
      return "";
   $r = mysql_fetch_assoc($sqlres);
   mysql_free_result($sqlres);
   return transliterate($r['City']);
}

function CinemaNameByID($kinoid)
{
   global $tbl_cinemas;
   $q = "SELECT * FROM $tbl_cinemas WHERE ID=$kinoid";
   $sqlres = mysql_query($q) or die(mysql_error() . " in SQL: $q");
   if(mysql_num_rows($sqlres)==0)
      return "";
   $r = mysql_fetch_assoc($sqlres);
   mysql_free_result($sqlres);
   return stripslashes(transliterate($r['Cinema']));
}

function CityIDFromCinemaID($kinoid)
{
   global $tbl_cinemas;
   $q = "SELECT * FROM $tbl_cinemas WHERE ID=$kinoid";
   $sqlres = mysql_query($q) or die(mysql_error() . " in SQL: $q");
   if(mysql_num_rows($sqlres)==0)
      return "";
   $r = mysql_fetch_assoc($sqlres);
   mysql_free_result($sqlres);
   return $r['CityID'];
}

function NumberOfMovies($kinoid)
{
   global $tbl_agenda;
   $q = "SELECT count(tsWhen) AS cnt FROM $tbl_agenda WHERE (CinemaID=$kinoid) AND (FROM_UNIXTIME(tsWhen) BETWEEN (DATE_SUB(NOW(), INTERVAL '7' DAY)) AND NOW()) ORDER BY tsWhen DESC";
   $sqlres = mysql_query($q) or die(mysql_error() . " in SQL: $q");
   $r = mysql_fetch_assoc($sqlres);
   $cnt = $r['cnt'];
   mysql_free_result($sqlres);
   return $cnt;
}
