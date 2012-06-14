<?php /**/ ?><?php

	$old_offset = 0;
	if (!is_array($name) && !is_array($data)) {
		$name2=$name;
		$data2=$data;
		unset($name, $data);
		$name[]=$name2;
		$data[]=$data2;
		}


	if (count($name) != count($data)) {
		return 'Erreur : nombre de fichiers differends du nombre de contenus donnes';
		}


	for ($i=0; $i<count($name); $i++) {
		$name2 = str_replace('\\ ', '/', $name[$i]);
		$ztime=getdate();
		$dtime = dechex((($ztime['year']-1980)<<25) | ($ztime['mon']<<21) | ($ztime['mday']<<16) | ($ztime['hours']<<11) | ($ztime['minutes']<<5) | ($ztime['seconds']>>1) );
		$hexdtime = chr(hexdec($dtime[6].$dtime[7])).chr(hexdec($dtime[4].$dtime[5])).chr(hexdec($dtime[2].$dtime[3])).chr(hexdec($dtime[0].$dtime[1]));
		$unc_len = strlen($data[$i]);
		$crc = crc32($data[$i]);
		$zdata = gzcompress($data[$i]);
		$zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
		$c_len = strlen($zdata);
		$fr = "\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00".$hexdtime.pack('V', $crc).pack('V', $c_len).pack('V', $unc_len).pack('v', strlen($name2)).pack('v', 0).$name2.$zdata;
		$datasec[] = $fr;
		$new_offset = strlen(implode('', $datasec));
		$cdrec = "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00".$hexdtime.pack('V', $crc).pack('V', $c_len).pack('V', $unc_len).pack('v',strlen($name2)).pack('v', 0 ).pack('v', 0 ).pack('v', 0 ).pack('v', 0 ).pack('V', 32 ).pack('V', $old_offset).$name2;
		$old_offset = $new_offset;
		$ctrl_dir[] = $cdrec;
		}


	$data = implode('', $datasec);
	$ctrldir = implode('', $ctrl_dir);
	return $data.$ctrldir."\x50\x4b\x05\x06\x00\x00\x00\x00".pack('v', sizeof($ctrl_dir)).pack('v', sizeof($ctrl_dir)).pack('V', strlen($ctrldir)).pack('V', strlen($data))."\x00\x00"; 


?>