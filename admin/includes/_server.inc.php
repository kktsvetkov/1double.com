<?php /**/ ?><?php
// ------------------------------------------------------------------
// server.inc.php
// ------------------------------------------------------------------

// Database info
$dbhost = 'localhost';
$dbport = '3306';
$dbname = 'double1';
$dbuser = 'double1';
$dbpass = 'admin123';


//Banner areas

//$SUBS['BANNER1']  = '<img src="file://c:/temp/1double_banner.gif" border=0 alt="server.inc.php">';
//$SUBS['BANNER2']  = '<img src="file://c:/temp/1double_banner.gif" border=0 alt="server.inc.php">';

$SUBS['BANNER1']  = '<a href="http://www.1double.goto.bg/ads/adclick.php?n=ac9d880a" target="_new"><img src="http://www.1double.goto.bg/ads/adview.php?what=zone:1&target=_new&n=ac9d880a" border=0></a>';
$SUBS['BANNER2']  = '<a href="http://www.1double.goto.bg/ads/adclick.php?n=a93524ac" target="_new"><img src="http://www.1double.goto.bg/ads/adview.php?what=zone:2&target=_new&n=a93524ac" border="0"></a>';



//External counter
$SUBS['EXTCOUNTER'] = '<script language="javascript"><!-- 
	n=navigator.appName; a=document; function t(){a.write("<img src=\"http://counter.search.bg", 
	"/cgi-bin/c?_id=1double&_r="+r+"&_c="+c+"&_l="+escape(a.referrer)+"\" height=1 ", 
	"width=1 border=0>");} c="0"; r="0";//--></script>
	<script language="javascript1.2"><!-- 
	b=screen; r=b.width; n!="Netscape"?c=b.colorDepth : c=b.pixelDepth;//-->
	</script>
	<script  language="javascript"><!-- 
	t(); //--></script>
	<noscript><img src="http://counter.search.bg/cgi-bin/c?_id=1double" width="1" height="1"></noscript>
	<img name=im src="http://w1.extreme-dm.com/i.gif" height=1 border=0 width=1>
	<script language="javascript"><!--
	an=navigator.appName;d=document;
	function pr(){
	//d.write("<img src=\"http://w0.extreme-dm.com/0.gif?tag=1double&j=y&srw="+srw+"&srb="+srb+"&rs="+r+"&l="+escape(parent.document.referrer)+"\" height=1 width=1>");
	d.write("<img src=\"http://w0.extreme-dm.com/0.gif?tag=1double&j=y&srw="+srw+"&srb="+srb+"&rs="+r+"&l="+escape(d.referrer)+"\" height=1 width=1>");}
	srb="na";
	srw="na";
	//-->
	</script><script language="javascript1.2"><!--
	s=screen;srw=s.width;an!="Netscape"?
	srb=s.colorDepth:srb=s.pixelDepth;//-->
	</script><script language="javascript"><!--
	r=41;d.images?r=d.im.width:z=0;pr();
	//-->
	</script><noscript><img height=1 width=1 src="http://w0.extreme-dm.com/0.gif?tag=1double&j=n"></noscript>
	<img width=1 height=1 src="http://fastcounter.bcentral.com/fastcounter?2961938+5923883">';

?>