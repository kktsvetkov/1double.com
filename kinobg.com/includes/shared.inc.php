<?php /**/ ?><?php
// ------------------------------------------------------------------
// shared.inc.php
// ------------------------------------------------------------------

include('./includes/lang.en.inc.php');
include('./includes/db_body.inc.php');
include('./includes/config.inc.php');
include('./includes/server.inc.php');

include('./includes/cache.inc.php');

if (get_magic_quotes_gpc())
 	set_magic_quotes_runtime(0);
$magic_quotes_gpc = get_magic_quotes_gpc();
  
// function init() // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 function init() {
	global $HTTP_POST_VARS, $HTTP_GET_VARS, $PARAM;
	global $debugFP, $dbh, $dbuser, $dbhost, $dbport, $dbpass, $dbname, $debugLogFile;

	////---- [Mrasnika's] Edition Aug-01-2008
	global $Cache;
	$Cache = new Cache('CACHE2_');

	//assume that the variables order is "GP"
	$PARAM = array_merge($_GET, $_POST);

	if (defined('DEBUG') && (DEBUG == 1))	// If DEBUG is true, try to open log file:
		if (!$debugFP = @fopen($debugLogFile,"a"))
			{
			// fopen failed, set program status:
			setLogAndStatus('','',$debugLogFile,'init()','DEBUG_LOG_OPEN');
			
			////---- [Mrasnika's] Edition Aug-01-2008
			//return 0;
			$Cache->force();
			}

	if (!$dbh = @db_connect("$dbhost:$dbport",$dbuser,$dbpass))
		{
		// database connection failed, set program status:
		setLogAndStatus('',db_errno($dbh),db_error($dbh),'init()','DB_CONNECT');

		////---- [Mrasnika's] Edition Aug-01-2008
		//return 0;
		$Cache->force();
		}

	if (!@db_select_db($dbname, $dbh))
		{
		// database selection failed, set program status:
		setLogAndStatus('', db_errno($dbh), db_error($dbh), 'init()', 'DB_SELECT');

		////---- [Mrasnika's] Edition Aug-01-2008
		//return 0;
		$Cache->force();
		}

//mysql_query('set names utf8');

	////----[Mrasnika's] Edition 20.06.2003
	if (!defined('INDEX')) {
		session_name('v');
		session_start('');
		}

	return 1;
	}

 // function setLogAndStatus() // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 function setLogAndStatus($query, $errcode, $errmessage, $funcname, $ppoint) {
 	global $debugFP;
 	if (defined('DEBUG') && (DEBUG == 1))
 		{
		$currentDate = date("Y/m/d h:i:s");
		$logError = "$currentDate\t$errcode\t$errmessage\t$funcname\t$query\t$ppoint\n";
		@fwrite($debugFP, $logError);
		
		reset($_SERVER);
		global $_srv;
		array_walk($_SERVER, create_function('&$v,$k','
			global $_srv;
			if(strstr($k,"HTTP_")
				|| in_array($k, array(
					"REMOTE_ADDR",
					"REMOTE_PORT",
					"REQUEST_METHOD"))){

					$_srv .= "$k = $v\n";
					}')
			);
		$logError .= "\n$_srv";
		
		//@mail ("webmaster@kinobg.com", "DEBUG-LOG", $logError, "\nFrom:debug-log@1double.com");
 		}
	}	
 
 // function halt() // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 function halt() {
 	global $debugFP;
 	if (defined('DEBUG') && (DEBUG == 1))
 		fclose($debugFP);
	
	////---- [Mrasnika's] Edition Aug-01-2008
	global $Cache;
	$Cache->End();
	
	die();
	}

// function dbQuote() // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 function dbQuote ($text) {
 	global $magic_quotes;
 	if (!$magic_quotes)
		return ("'" . addslashes($text) . "'");
		else return ("'" . $text . "'");
	}

// function htmlEncode() // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 function htmlEncode($text) {
	return (htmlSpecialChars(stripSlashes($text)));
	}

// function fileToString() // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 function fileToString ($filename) {
	$text = array();
	if (!$text =  @file($filename))
		setLogAndStatus('','',$filename,'fileToString()','FILE_TO_STRING_READ');
	return implode('', $text);
}

// function strParse() // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 function strParse ($text) {
 	global $SUBS;

 	while (preg_match("/#%(.*?)%#/", $text,$matches))
 		{
 	 	$SUBS[$matches[1]] = str_replace('$',chr(27),$SUBS[$matches[1]]);
  		$text = preg_replace("/#%$matches[1]%#/",$SUBS[$matches[1]], $text);
 		}
 	return $text;
	}

// function fileParse
 function fileParse($filename) {
	global $SUBS;
 	$SUBS['IMAGESDIR'] = getAdmSetting('IMAGES_DIR');
 	$string = fileToString(getAdmSetting('TEMPLATES_DIR').$filename);
 	$string = strParse($string);
 	return $string;
	}

// function getAdmSetting($key)
function getAdmSetting($key) {
	global $ADMIN_SETTINGS;
	return $ADMIN_SETTINGS[$key];
	} //function getAdmSetting($key)

// function validateEmail($address)
function validateEmail($address) {
 	return (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'. '@'. '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.' . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $address));
	}

// function validatePassword()
function validatePassword($pass) {
 	return ((strlen($pass) >= getAdmSetting("MIN_PASSWORD_LEN")) &&
 		(strlen($pass) <= getAdmSetting("MAX_PASSWORD_LEN")) && (eregi('[a-z_0-9]',$pass)));
	}

// function runQuery($query, $errno, $function, $label)
function runQuery($query,$function, $label) {
	global $dbh;

	if (!$result = db_query($query, $dbh))
		{
		// query execution failed, set the log and status:
		setLogAndStatus($query, db_errno($dbh), db_error($dbh),$function, $label);
		return 0;
		} else return $result;
}

// function sentMail
function sentMail ($mail) {
	$mail = fileToString(getAdmSetting('EMAIL_TEMPLATES_DIR')."/$mail");
	$mail = strParse ($mail);
	eregi("To:([^\n]*)\n(From:[^\n]*)\nSubject:([^\n]*)\n[^[a-z0-9]]*(.*)", $mail, $R);

	$To = $R[1];
	$From = $R[2];
	$Subject = $R[3];
	$Msg = ltrim($R[4]);

	if (!@mail ($To, $Subject, $Msg, $From))
		{
		//SENT_MAIL
		setLogAndStatus("mail ($To, $Subject, $Msg, $From)", 0, 'Mailing failed','sentMail()', 'SENT_MAIL');
		return 0;
		}
	return 1;
	}

//function make_seed()
 function make_seed() {
	list($usec, $sec) = explode(' ', microtime());
	return (float) $sec + ((float) $usec * 100000);
	}

// function getRand()
function getRand($a, $b) {
	if (($a+1)==$b)
		return $a;
	mt_srand(make_seed());
	return mt_rand($a,$b-1);
	}

//function printPage()
//used in admin.php
 function printPage($page) {
	global $SUBS, $MONTHS, $PARAM, $TITLES;

	$ct = getdate();
	$SUBS['HTML_TITLE'] = $TITLES[$PARAM['cmd']];
 	$SUBS['TODAYSDATE'] = $ct['mday'].' '.$MONTHS[$ct['mon']].' '.$ct['year'];

	echo fileParse('_admin_header.htmlt');
	echo fileParse($page);
	echo fileParse('_admin_footer.htmlt');
 	}

//function index()
//used in index.php
 function index($page) {
	global $SUBS, $MONTHS, $PARAM, $TITLES;

	$ct = getdate();
	//$SUBS['HTML_TITLE'] = $TITLES[$PARAM['cmd']];
 	$SUBS['TODAYSDATE'] = $ct['mday'].' '.$MONTHS[$ct['mon']].' '.$ct['year'];

	////----[Mrasnika's] Edition 26.10.2002
	$koga = week();
	$dokoga = $koga + 604501;
	$godina1 = date('Y',$koga);
	$godina2 = date('Y',$dokoga);

	////---[Mrasnika's] Edition 04.11.2002
	/*
	$SUBS['WEEKDATE'] = "<img src=\"#%IMAGESDIR%#/day_".date ('d', $koga).".gif\"
		vspace=2><img src=\"#%IMAGESDIR%#/month_".date('m',$koga).".gif\" vspace=2>";
	if ($godina1 != $godina2)
		$SUBS['WEEKDATE'] .= "<img src=\"#%IMAGESDIR%#/year_$godina1.gif\" vspace=2>";

	$SUBS['WEEKDATE'] .= "<img src=\"#%IMAGESDIR%#/day_0.gif\" vspace=2><img
		src=\"#%IMAGESDIR%#/day_".date ('d', $dokoga).".gif\" vspace=2><img
		src=\"#%IMAGESDIR%#/month_".date('m',$dokoga).".gif\" vspace=2><img
		src=\"#%IMAGESDIR%#/year_$godina2.gif\" vspace=2>";
	*/
	if ($godina1 == $godina2)
		$godina1 = '';	//fix years


	$SUBS['WEEKDATE'] = date ('d ',$koga). $MONTHS[intval(date('m',$koga))]." $godina1
				- ".date('d ', $dokoga). $MONTHS[intval(date('m',$dokoga))]." $godina2";
	
	echo fileParse('_index_header.htmlt');
	echo fileParse($page);
	echo fileParse('_index_footer.htmlt');
 	}


// function emailParse
 function emailParse ($text) {
 	global $ESUBS;
 	while (preg_match("/%%(.*?)%%/", $text,$matches))
 		{
 	 	$ESUBS[$matches[1]] = str_replace('$',chr(27),$ESUBS[$matches[1]]);
  		$text = preg_replace("/%%$matches[1]%%/",$ESUBS[$matches[1]], $text);
 		}
  	$text = str_replace(chr(27),'$',$text);
 	return $text;
	}

 //function checkPicture()
 function checkPicture ($filename) {
	if (file_exists($filename))
		{
		$INFO = GetImageSize ($filename);
		if (($INFO[2]==2) || ($INFO[2]==2))
			return $INFO;
			else return 0;
		} else return 0;
 	}

 //function fixPicture()
 function fixPicture ($filename, $type, $id, $PICPARAM) {
	global $tbl_1d_pictures;

	$OK = 0;
	$name = $type."_".time()."_".md5($PICPARAM[0].$PICPARAM[1].$filename);

	switch ($type) {	//margins
		case 'dvd' :
			$MARGINS['IMG_WIDTH'] = getAdmSetting('DVD_WIDTH');
			$MARGINS['IMG_HEIGHT'] = getAdmSetting('DVD_HEIGHT');
			break;

		case 'video' :
			$MARGINS['IMG_WIDTH'] = getAdmSetting('VIDEO_WIDTH');
			$MARGINS['IMG_HEIGHT'] = getAdmSetting('VIDEO_HEIGHT');
			break;

		case 'thumb' :
			$MARGINS['IMG_WIDTH'] = getAdmSetting('THUMB_WIDTH');
			$MARGINS['IMG_HEIGHT'] = getAdmSetting('THUMB_HEIGHT');
			break;

		case 'article' :
			$MARGINS['IMG_WIDTH'] = getAdmSetting('ARTICLE_WIDTH');
			$MARGINS['IMG_HEIGHT'] = getAdmSetting('ARTICLE_HEIGHT');
			break;

		default :	//CASE film
			$MARGINS['IMG_WIDTH'] = getAdmSetting('IMG_WIDTH');
			$MARGINS['IMG_HEIGHT'] = getAdmSetting('IMG_HEIGHT');
		}

	switch ($PICPARAM[2]) {
		case 3 :	$src = @ImageCreateFromPNG($filename);
		case 2 :	if ($PICPARAM[2]==2)
				$src = @ImageCreateFromJPEG($filename);

			if ($PICPARAM[0] < $MARGINS['IMG_WIDTH'])
				{
				if ($PICPARAM[1] < $MARGINS['IMG_HEIGHT'])
					$OK = 1;
					else $percent = $PICPARAM[1]/$MARGINS['IMG_HEIGHT'];
				} else if ($PICPARAM[1]<$MARGINS['IMG_HEIGHT'])
					$percent = $PICPARAM[0]/$MARGINS['IMG_WIDTH'];
					else {
					$img_per[0] = $PICPARAM[0]/$MARGINS['IMG_WIDTH'];
					$img_per[1] = $PICPARAM[1]/$MARGINS['IMG_HEIGHT'];
					$percent = max ($img_per);
					}

			$new = getAdmSetting('UPLOAD_DIR')."$name.JPG";
			
			switch ($type) {
				case 'thumb' :
					$dst = @ImageCreateFromJPEG(getAdmSetting('TEMPLATES_DIR').'default.jpg');

					$img_per[0] = $PICPARAM[0]/$MARGINS['IMG_WIDTH'];
					$img_per[1] = $PICPARAM[1]/$MARGINS['IMG_HEIGHT'];
					$percent = min ($img_per);

					$img_2[0] = $MARGINS['IMG_WIDTH']*($percent);
					$img_2[1] = $MARGINS['IMG_HEIGHT']*($percent);

					$img_cor[0] = ($PICPARAM[0] - $MARGINS['IMG_WIDTH']*($percent))/2;
					$img_cor[1] = ($PICPARAM[1] - $MARGINS['IMG_HEIGHT']*($percent))/2;

					imagecopyresampled ($dst,$src,
						getAdmSetting ('THUMB_LEFT'),getAdmSetting ('THUMB_TOP'),
						$img_cor[0],$img_cor[1],
						$MARGINS['IMG_WIDTH'],$MARGINS['IMG_HEIGHT'],
						$img_2[0],$img_2[1]);

					ImageJPEG($dst, $new);
					$PICPARAM = getImageSize($new);
					break;
					
				case 'video' :
				case 'dvd' :
					$dst = @ImageCreateTrueColor($MARGINS['IMG_WIDTH'],$MARGINS['IMG_HEIGHT']);

					$img_per[0] = $PICPARAM[0]/$MARGINS['IMG_WIDTH'];
					$img_per[1] = $PICPARAM[1]/$MARGINS['IMG_HEIGHT'];
					$percent = min ($img_per);

					$img_2[0] = $MARGINS['IMG_WIDTH']*($percent);
					$img_2[1] = $MARGINS['IMG_HEIGHT']*($percent);

					$img_cor[0] = ($PICPARAM[0] - $MARGINS['IMG_WIDTH']*($percent))/2;
					$img_cor[1] = ($PICPARAM[1] - $MARGINS['IMG_HEIGHT']*($percent))/2;

					imagecopyresampled ($dst,$src,
						0,0,
						$img_cor[0],$img_cor[1],
						$MARGINS['IMG_WIDTH'],$MARGINS['IMG_HEIGHT'],
						$img_2[0],$img_2[1]);

					ImageJPEG($dst, $new);
					$PICPARAM = getImageSize($new);
					break;

				default :
					if ($OK == 1)	//no resize
						@ImageJPEG($src, $new);
						else {
						$img_2[0] = round($PICPARAM[0]/($percent));
						$img_2[1] = round($PICPARAM[1]/($percent));

						$dst = @ImageCreateTrueColor($img_2[0],$img_2[1]);

						imagecopyresampled ($dst,$src,		//image ids
								0,0,			//destination x,y
								0,0,			//src x,y
								$img_2[0],$img_2[1],	//destination width, height
								$PICPARAM[0],$PICPARAM[1]);	//src w,h
						ImageJPEG($dst, $new);
						$PICPARAM[0] = $img_2[0];
						$PICPARAM[1] = $img_2[1];
						}
					break;
				}

			$query = "INSERT INTO $tbl_1d_pictures
					(URL, RefID, RefType, Width, Height) VALUES
					(".dbQuote("$name.JPG").", $id, ".dbQuote($type).", $PICPARAM[0], $PICPARAM[1])";

			$result = runQuery($query,'fixPicture()','INSERT_PIC');
			$id = mysql_insert_id();

			switch ($type) {	//thumbnails
				case 'article' :	;
				case 'film' :
					fixPicture ($new, 'thumb', $id, $PICPARAM);
					break;
				}
			return 1;
			break;
		default :	return 0;
		};
 	}

//function getNextWeek
 function getNextWeek() {
 	$friday = getDate ();
 	if ($friday['wday']<5)
 		$friday = 5 - $friday['wday'];
 		else $friday = 12 - $friday['wday'];
 	$today = time() - date('s') - date('i')*60 - date('H')*3600 + 1;
 	$friday = $today + $friday*86400;	//sledwaschtiya petyk
 	return $friday;
 	}

//function getLastWeek
 function getLastWeek() {
	global $tbl_1d_films, $tbl_1d_videodvd;
	$query = "SELECT	max($tbl_1d_films.tsPremiere),
			max($tbl_1d_films.tsUSAPremiere),
			max($tbl_1d_videodvd.tsWhen)
		FROM $tbl_1d_films
		LEFT JOIN $tbl_1d_videodvd
			ON $tbl_1d_videodvd.FilmID = $tbl_1d_films.ID";
	$result = runQuery($query,'getLastWeek()','GET_DATES');
	if ($row = db_fetch_row($result))
		return max($row[0],$row[1],$row[2]);
		else return 0;
 	}

 //function getWeeks
 function getWeeks($week) {
 	global $MONTHS;
 	global $span;
 	
 	//razgrafawa se za 2 meseca ot tekuschtata data
 	//wsyaka data se pada petyk-a ot nachaloto na
 	//kino sedmicata
 	$Weeks = '';

 	$friday = getNextWeek ();	//sledwaschtiya petyk
 	$now = $friday - 604800;		//tozi petyk
	//if (!$week) $week = $friday;

	$range = 8;	//select boks za osem sedmici

	//manage offset
	if ($span > 0)
		{
		$range += intval (($now-$span)/604800);
		$now= $span;
		}

	if (($week) && ($week<$now))
		{
		$range += intval (($now-$week)/604800);
		$now= $week;
		}

	for ($i=0; $i<$range; $i++)
		{
		$koga = $now + $i*604800;
		$dokoga = $koga + 518401;
		$godina1 = date('Y',$koga);
		$godina2 = date('Y',$dokoga);
		if ($godina1 == $godina2)
			$godina1 = '';
		
		$datata = date ('d ', $koga).$MONTHS[intval(date('m',$koga))]." $godina1";
		$datata .= date (' - d ', $dokoga).$MONTHS[intval(date('m',$dokoga))]." $godina2";

		if (($week) && ($week >= $koga) && ($week <= $dokoga))
			$Weeks .= "<option value=\"$koga\" SELECTED>$datata";
			else $Weeks .= "<option value=\"$koga\">$datata";
		}
 	
 	return $Weeks;
 	}

//function showWeek()
function showWeek($week) {
	global $MON;
	
	$week = week($week);

	$koga = $week;
	$dokoga = $koga + 518401;
	$godina1 = date('.Y',$koga);
	$godina2 = date('.Y',$dokoga);
	if ($godina1 == $godina2)
		$godina1 = '';
	
	$datata = date ('d.', $koga).$MON[intval(date('m',$koga))]."$godina1";
	$datata .= date (' - d.', $dokoga).$MON[intval(date('m',$dokoga))]."$godina2";
	return $datata;
	}

//function week($ts)
function week($ts=0) {
	if ($ts == 0) $ts = time();

 	$friday = getDate ($ts);
 	if ($friday['wday']<5)
 		$friday = -2 - $friday['wday'];
 		else $friday = 5 - $friday['wday'];

 	//$today = $ts - date('s', $ts) - date('i')*60 - date('H')*3600+2;
 	//$today = $ts;
 	$today = $ts - date('s', $ts) - date('i', $ts)*60 - date('H', $ts)*3600+1;

 	////----[Mrasnika's] Edition 31.10.2002
 	//return $friday = $today + $friday*86400 ;
 	
 	$friday = $today + $friday*86400 ;
 	$ret = 1 + strToTime (date('d F Y', $friday));

 	return $ret;
	}

//function column()
function column($a) {
	$res = wordwrap ($a, getAdmSetting('COLUMN_LEN'), "\n   ");
	$res = htmlEncode ($res);
	return ereg_replace("  ", " &nbsp;", nl2br($res));
}

//function displayWeek()
function displayWeek($week) {
	global $MONTHS;
	
	$week = week($week);

	$koga = $week;
	$dokoga = $koga + 518401;
	$godina1 = date(' Y',$koga);
	$godina2 = date(' Y',$dokoga);
	if ($godina1 == $godina2)
		$godina1 = '';
	
	$datata = date ('d ', $koga).$MONTHS[intval(date('m',$koga))]."$godina1";
	$datata .= date (' - d ', $dokoga).$MONTHS[intval(date('m',$dokoga))]."$godina2";
	return $datata;
	}

?>