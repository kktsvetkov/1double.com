<?php /**/ ?><?

//@session_start(); 

header("Content-type: text/vnd.wap.wml");

//var_dump($_SERVER);

include "dbconnect.inc.php";
include "cyr2lat.inc.php";
include "kino-commons.php";

if(isset($_REQUEST['grad']))
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
   elseif(!empty($_COOKIE['kinobg-grad']))
   {
      header("Location: kino.php?grad=".$_COOKIE['kinobg-grad']);
      exit;
   }
   else//*/
   {
      $grad = '';
   }
}

$sqlres = mysql_query("SELECT * FROM $tbl_cities WHERE Active='yes' ORDER BY Priority ") or die(mysql_error());

echo "<?xml version=\"1.0\"?>\n<!DOCTYPE wml PUBLIC \"-//WAPFORUM//DTD WML 1.1//EN\" \"http://www.wapforum.org/DTD/wml_1.1.xml\">";
echo "\n<wml>\n";

echo "<template>$template_header</template>";

echo "<card id=\"c1\" title=\"wap.kinobg.com\">\n";


$select_body = '';
$default_grad = '';
$i = 0;
while( $r = mysql_fetch_assoc($sqlres) )
{
   //var_dump($r);
   if($r['ID']==$grad)
      $default_grad = $i;
   $select_body .= '<option value="' . $r['ID'] . '" onpick="kino.php?grad=$(grad)">' . transliterate($r['City']) . "</option>\n";
   $i++;
}

echo "<p>";
echo "Izberete grad:<br/>";
echo "<select name=\"grad\" value=\"$default_grad\">\n";
echo $select_body;
echo "</select>";//*/
echo "</p>";

echo "</card>";
echo "</wml>";

?>
