<?php /**/ ?><?

//session_start(); 

header("Content-type: text/vnd.wap.wml");

include "dbconnect.inc.php";
include "cyr2lat.inc.php";
include "kino-commons.php";

if(isset($_REQUEST))
   $RQ = $_REQUEST;

//var_dump($RQ);

if(isset($RQ['grad']))
{
   $grad = $_REQUEST['grad'];
}
else
{
   if(!empty($_COOKIE['kinobg-kino']))
   {
      header("Location: filmi.php?kino=".$_COOKIE['kinobg-kino']);
      exit;
   }
   else
   {
      //header("Location: $page_grad");
      echo "<wml><card ontimer='$page_grad'><timer value='100'/>";
      //var_dump($RQ);
      echo "njama izbran grad</card></wml>";
      exit;
   }//*/
}



setcookie("kinobg-grad", $grad);
setcookie("kinobg-izbrangrad", "");
setcookie("kinobg-kino", "");

$_SESSION['grad'] = $grad;

$sqlres = mysql_query("SELECT * FROM $tbl_cinemas WHERE CityID=$grad ORDER BY Priority ") or die(mysql_error());

echo "<?xml version=\"1.0\"?>\n<!DOCTYPE wml PUBLIC \"-//WAPFORUM//DTD WML 1.1//EN\" \"http://www.wapforum.org/DTD/wml_1.1.xml\">\n";
echo "<wml>\n";

$gradname = CityNameByID($grad);

echo "<template>Izbor na kino</template>\n";

echo "<card id=\"kina\" title=\"$gradname\">\n";

////echo '<do type="accept" label="izberi"><go href="filmi.php"><postfield name="kino" value="$(kino)"/></go></do>';
echo "<p>$template_header <br/>\n <a href=\"$page_grad?grad=$grad\">promiana grad</a></p>\n";
echo "<p>\n";

echo "<b>Izberete kino:</b>\n";
echo "<select name=\"kino\" ivalue=\"0\">\n";
//echo "<postfield name=\"kino\" value=$(kino)/>\n";
while( $r = mysql_fetch_assoc($sqlres) )
{
   $kinoid = $r['ID'];
   if(NumberOfMovies($kinoid)>0)
      echo "<option value=\"" . $kinoid . "\" onpick=\"filmi.php?kino=$(kino)\">" . stripslashes(transliterate($r['Cinema'])) . "</option>\n";
}

echo "</select>\n";//*/
echo "</p>\n";

echo "</card>\n";
echo "</wml>";

?>