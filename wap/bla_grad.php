<?php /**/ ?><?

session_start(); 

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
   
   if(!empty($_COOKIE['kinobg-grad']))
   {
      header("Location: kino.php?grad=".$_COOKIE['kinobg-grad']);
      exit;
   }
}

$sqlres = mysql_query("SELECT * FROM $tbl_cities WHERE Active='yes' ORDER BY Priority ") or die(mysql_error());

echo '<?xml version="1.0"?><!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">';
echo "\n<wml>\n";

echo "<template>$template_header</template>\n";

echo "<card>\n";

echo "Izberete grad:";

$select_body = '';
$i = 0;
while( $r = mysql_fetch_assoc($sqlres) )
{
   if($r['ID']==$grad)
      $default_grad = $i;
   $select_body .= '<option value="' . $r['ID'] . '" onpick="kino.php?grad=$(grad)">' . transliterate($r['City']) . "</option>\n";
   $i++;
}

echo "<select name='grad' ivalue='$default_grad'>\n";
echo $select_body;
echo "</select>";

echo "</card>";
echo "</wml>";

?>