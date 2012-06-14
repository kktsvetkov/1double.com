<?php /**/ ?><?php
echo date("r\r\n");
$i = 0;
foreach (glob('/home/kinobgc/public_html/cache2/*.*') as $filename) { /**/
    	unlink($filename);
    	echo "Deleted {$filename} \n";
    	if ($i++ > 100) {
    		break;
    		}
	}
