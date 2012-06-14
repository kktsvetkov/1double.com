<?php /**/ ?><?

if($_SERVER['HTTP_HOST']=='nbi')
{
   $mysql_host = "localhost";
   $mysql_user="root";
   $mysql_pass="root";
   $mysql_db= "tempmc";
}
else
{
   $mysql_host = "mysql.mrasnika.info";
   $mysql_user="kinobg";
   $mysql_pass="kinobg";
   $mysql_db= "1double";
}

$link = mysql_connect($mysql_host, $mysql_user, $mysql_pass) or die (mysql_error());
mysql_select_db( $mysql_db, $link ) or die(mysql_error());


