<?php /**/ ?><?
// ------------------------------------------------------------------
// admin.php
// ------------------------------------------------------------------

define ('ADMIN', 1);

$HTTP_POST_VARS = $_POST;
$HTTP_GET_VARS = $_GET;

include('includes/shared.inc.php');
//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

// = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP
function makezip($name, $data) {
	return include 'includes/admin.makesip.php';
	}

// = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP = ZIP

//function setBackup()
function setBackup() {
	global $dbname, $dbh;
	global $PARAM, $SUBS, $MSG, $MONTHS;

	if (!is_dir(getAdmSetting('BACKUP_DIR')))
		MkDir (getAdmSetting('BACKUP_DIR'), 0777);

	if ($PARAM['upload']==1)
		{
		global $bckFile, $bckFile_name;
		if ($bckFile_name == '')
			{
			$SUBS['ERROR'] = $MSG[20108];
			$SUBS['BACKUP_ERROR'] = fileParse('_admin_error.htmlt');
			} else {
			if (!$UPLOAD = @file($bckFile))
				setLogAndStatus("Reading", $bckFile, 0, "setBackup()", 'READ_UPLOAD');
			$file = date('d F Y H_i_s');
			$filename = getAdmSetting('BACKUP_DIR')."/$file.sql";
			$upload = '## '.$MSG[20109].date(' d F Y H:i:s')."\n";
			$upload .= "## $MSG[20110] $bckFile_name\n";
			$upload .= join ('', $UPLOAD);
			if (!$fp = fopen($filename, 'w'))
				setLogAndStatus("Opening", $filename, 0, "setBackup()", 'OPEN_FILE');
			fwrite ($fp, $upload);
			fclose ($fp);

			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20050";
			printPage('_admin_done.htmlt');
			return;
			}
		}

	//export database backup
	if ($PARAM['export']==1)
		{
		$file = date('d F Y H_i_s');
		$filename = getAdmSetting('BACKUP_DIR')."/$file.sql";
		if (!$fp = fopen($filename, 'w'))
			setLogAndStatus("Opening", 0, $filename, "setBackup()", 'OPEN_FILE');

		//write comments if any
		if ($PARAM['bckComments'] != '')
			{
			$comments = '##' . ereg_replace ("\n", "\n##", $PARAM['bckComments'])."\n";
			fwrite ($fp, $comments);
			}

		if (!$res = db_list_tables($dbname, $dbh))
			setLogAndStatus("db_list_tables()", 0, $dbname, "setBackup()", 'LIST_TABLES');

		$num_tables = db_num_rows($res);
		$i = 0;
		while ($i < $num_tables)
			{
			$table = db_tablename($res, $i);
			$fields = db_list_fields($dbname, $table, $dbh);
			$columns = db_num_fields($fields);

			$tablelist = '';
			for ($j = 0; $j < $columns; $j++)
				if (($columns-$j)==1)
					$tablelist .= db_field_name($fields, $j);
					else $tablelist .= db_field_name($fields, $j).',';
			$schema = "REPLACE INTO $table ($tablelist) VALUES (";

			$query = "SELECT * FROM $dbname.$table";
			$result = runQuery($query, 'setBackup()', 'SELECT_TABLES');
			while ($row = db_fetch_row($result))
				{
				$schema_insert = '';
				for ($j = 0; $j < $columns; $j++)
                					if (!isset($row[$j]))
                						$schema_insert .= ' NULL,';
                						////---- [Mrasnika's] Edition 20.03.2001
                						//else $schema_insert .= " '".addSlashes ($row[$j])."',";
                						else $schema_insert .= ' '.dbQuote($row[$j]).',';
				$schema_insert = $schema. ereg_replace(',$', '', $schema_insert);
				$schema_insert .= ");\r\n";
				fwrite ($fp, $schema_insert);
            				}
			$i++;
			}



		fclose ($fp);

		// the ZIP thing --------------------
		$fp=fopen ($filename, "rb");
		$data=fread($fp, filesize($filename));
		fclose($fp);
		
		$name=array(baseName($filename));
		$data=array($data);

		$content = makezip($name, $data);
		$fp= fopen ('./zip/'.basename($filename).'.ZIP', "wb");
		fputs ($fp, $content);
		fclose ($fp);
		// the ZIP thing --------------------

		$SUBS['COMMAND'] = $PARAM['cmd']."&err=20052";
		printPage('_admin_done.htmlt');
		return;
		}

	//prepare for import or delete
	$backups = opendir(getAdmSetting('BACKUP_DIR'));
	while (($file = readdir($backups)) != false)
		if (!is_dir ($file))
			$BCKUPS[eregi_replace ('[^a-z0-9]','_', $file)] = getAdmSetting('BACKUP_DIR')."/$file";
	closedir($backups);

	reset ($PARAM);
	while (list($k,$v) = each($PARAM))
		if (ereg('^bck_(.*)$',$k,$R)) $BACKUPS[] = $R[1];
	reset ($PARAM);

	//delete backups
	if ($PARAM['delete']==1)
		{
		if (count($BACKUPS) == 0)
			{
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20008";
			printPage('_admin_done.htmlt');
			return;
			}

		for ($i=0; $i< count ($BACKUPS); $i++)
			if (!@unlink($BCKUPS[$BACKUPS[$i]]))
				setLogAndStatus("Deleting", $BCKUPS[$BACKUPS[$i]], "setBackup()", 'DEL_BACKUP');

		$SUBS['COMMAND'] = $PARAM['cmd']."&err=20054";
		printPage('_admin_done.htmlt');
		return;
		}

	//import database backup
	if ($PARAM['import']==1)
		{
		if (count($BACKUPS) > 1)
			{
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20053";
			printPage('_admin_done.htmlt');
			return;
			}

		if (count($BACKUPS) == 0)
			{
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20008";
			printPage('_admin_done.htmlt');
			return;
			}

		//get backup file
		$file = fread(fopen($BCKUPS[$BACKUPS[0]], 'r'), filesize($BCKUPS[$BACKUPS[0]]));

		////---- [Mrasnika's] Edition 21.03.2002
		split_sql_file($BACKUP, $file);

		//reset tables
		if (!$res = db_list_tables($dbname, $dbh))
			setLogAndStatus("db_list_tables()", 1, $dbname, "databaseBackup()", 'LIST_TABLES_2');
		$num_tables = db_num_rows($res);
		$i = 0;
		while ($i < $num_tables)
			{
			$table = db_tablename($res, $i);
			$query = "DELETE FROM $dbname.$table";
			$result = runQuery($query, 'setBackup()', 'RESET_TABLES');
			$i++;
			}

		//fill tables
		while (list($k, $query) = each($BACKUP))
			if (!ereg('^#', $query))	//not a comment
				{
				if (!$result = db_query($query, $dbh))
					{
					setLogAndStatus($query, db_errno($dbh), db_error($dbh), "databaseBackup()", 'RESTORE_DB');
					$SUBS['COMMAND'] = $PARAM['cmd']."&err=20055";
					printPage('_admin_done.htmlt');
					return;
					}
				}
		$SUBS['COMMAND'] = $PARAM['cmd']."&err=20056";
		printPage('_admin_done.htmlt');
		return;
		}

	$backups = opendir(getAdmSetting('BACKUP_DIR'));
	$last  = 0;
	while (($file = readdir($backups)) != false)
		if (!is_dir ($file))
			{
			$date = stat (getAdmSetting('BACKUP_DIR')."/$file");
			if ($last < $date[9])
				{
				$month = intval(date('m'));
				$SUBS['LAST'] = $MSG[20051].date(' d ', $date[9]).$MONTHS[$month].date(' Y H.i.s', $date[9]);
				}
			
			$SUBS['SIZE'] = sprintf('%0.2f KB', $date[7]/1024);

			$SUBS['NAME'] = eregi_replace ('_', ':', $file);

			$SUBS['CHECK'] = eregi_replace ('[^a-z0-9]','_', $file);	//checkbox name
			$SUBS['WHERE'] = getAdmSetting('BACKUP_DIR')."/$file";

			if (!$BACKUP = @file(getAdmSetting('BACKUP_DIR')."/$file"))
				setLogAndStatus("Reading",0,getAdmSetting('BACKUP_DIR')."/$file", "setBackup()", 'READ_FILE');

			$comments = '';	//get comments from the beginning of the file
			for ($i=0; $i<count($BACKUP); $i++)
				if (eregi('^##(.*)$',$BACKUP[$i], $R)) $comments .= $R[1];
			if ($comments != '')
				{
				$SUBS['COMMENTS'] = ' &nbsp; '. ereg_replace( "\n", '<BR> &nbsp; ', htmlEncode($comments));
				$SUBS['COMMENTS'] = ereg_replace ('<BR> &nbsp; $','', $SUBS['COMMENTS']);
				} else $SUBS['COMMENTS'] = '';
			$SUBS['BACKUPS'] .= fileParse('_admin_backup_row.htmlt');
			}
	closedir($backups);

	if ($PARAM['err'] != '')
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['BACKUP_ERROR'] = fileParse('_admin_error.htmlt');
		}

	printPage('_admin_backup.htmlt');
	}


//function adminMenu()
function adminMenu(){
	global $PARAM, $SUBS, $MONTHS;
	global $tbl_1d_cities, $tbl_1d_cinemas, $tbl_1d_charts;
	
	$PARAM['cmd'] = 'menu';	//menu title

	//gradowete i kina
	$query = "SELECT	$tbl_1d_cities.ID,
			City,
			$tbl_1d_cinemas.ID
		FROM	$tbl_1d_cities
		LEFT JOIN $tbl_1d_cinemas
			ON $tbl_1d_cinemas.CityID = $tbl_1d_cities.ID
		WHERE Active = 'yes'
		GROUP BY $tbl_1d_cities.ID
		ORDER BY $tbl_1d_cities.Priority";
	$result = runQuery($query,'adminMenu()','GET_CITIES');

	$SUBS['KOGA'] = getNextWeek();
	
	$godina1 = date('Y',$SUBS['KOGA']);
	$godina2 = date('Y',$SUBS['KOGA'] + 604799);
	if ($godina1 == $godina2)
		$godina1 = '';

	$SUBS['DATE'] .= date ('d ', $SUBS['KOGA']).$MONTHS[intval(date('m',$SUBS['KOGA']))]." $godina1";	
	$SUBS['DATE'] .= date (' - d ', $SUBS['KOGA'] + 604799).$MONTHS[intval(date('m',$SUBS['KOGA'] + 604799))]." $godina2";
	while ($row = db_fetch_row($result))
		{
		$SUBS['CITY'] = htmlEncode($row[1]);
		$SUBS['CITYID'] = $row[0];
		$SUBS['CINEMAID'] = $row[2];
		$SUBS['CITIES'] .= fileParse ('_admin_menu_city.htmlt');
		}

	//klasacii
	$query = "SELECT ID, Title
		FROM $tbl_1d_charts";
	$result = runQuery($query,'adminMenu()','GET_CHARTS');
	while ($row = db_fetch_row($result))
		{
		$SUBS['CHARTID'] = $row[0];
		$SUBS['CHART'] = htmlEncode($row[1]);
		$SUBS['CHARTS'] .= fileParse ('_admin_menu_chart.htmlt');
		}

	printPage('_admin_menu.htmlt');
	}

 //function adminPassword()
 function adminPassword() {
 	global $tbl_1d_admins;
 	global $adminID, $adminAlias;	//from session
 	global $SUBS, $PARAM, $MSG;

 	if ($PARAM['Password'])
 		{
 		$query = "SELECT Password
 			FROM $tbl_1d_admins
 			WHERE (ID=$adminID) AND
 			(Password = MD5(".dbQuote($PARAM['password0'])."))";
 		$result = runQuery($query, 'adminPassword()', 'CHECK_PASSWORD');

		if (!$row = db_fetch_row($result))
			{
			$SUBS['ERROR'] = $MSG[20005];
			$SUBS['LOGIN_ERROR'] = fileParse('_admin_error.htmlt');	
			adminLogout();
			return;
			}

		if ($PARAM['password1'] != $PARAM['password2'])
			$SUBS['ERROR'] = $MSG[20003];
		if (strlen($PARAM['password1'])<getAdmSetting('MIN_PASSWORD_LEN'))
			$SUBS['ERROR'] = $MSG[20004];
		if ($SUBS['ERROR'])
			$SUBS['PASSWORD_ERROR'] = fileParse('_admin_error.htmlt');
			else {
			$query = "UPDATE $tbl_1d_admins
				SET Password = ".dbQuote(md5($PARAM['password1']))."
				WHERE ID = $adminID";
			$result = runQuery($query, 'adminPassword()', 'WRITE_PASSWORD');
			
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20006";
			printPage('_admin_done.htmlt');
			return;
			}
 		}

	if ($PARAM['err'] != '')
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['PASSWORD_ERROR'] = fileParse('_admin_error.htmlt');
		}

 	printPage('_admin_password.htmlt');
 	}

 //function adminLogged()
 function adminLogged() {
 	global $SUBS;
 	global $adminAlias;	//from session
	if (!$SUBS['LOGGED'] = $adminAlias)
		return 0;
		else {
		$SUBS['LOGGED'] = fileParse('_admin_logged.htmlt');
		return 1;
		}
 	}

 //function logAdmin()
 function logAdmin() {
 	global $PARAM, $SUBS;
 	global $tbl_1d_admins;
 	global $adminID, $adminAlias;	//from session

	$password = dbQuote($PARAM['password']);
	$query = "SELECT ID, Account
		FROM $tbl_1d_admins
		WHERE	Account = ".dbQuote($PARAM['alias'])." AND
			Password = MD5($password)";
	$result = runQuery($query, 'adminLogin()', 'CHECK_ADMIN');
	if ($row = db_fetch_row($result))
		{
		if (!session_is_registered('adminID')) session_register('adminID');
		$adminID = $row[0];
		if (!session_is_registered('adminAlias')) session_register('adminAlias');
		$adminAlias = $row[1];
		$SUBS['LOGGED'] = $adminAlias;
		$SUBS['LOGGED'] = fileParse('_admin_logged.htmlt');
		return 1;
		} else {
		$SUBS['OLD'] = $PARAM ['alias'];
		if (defined('AUDIT'))
			{
			global $REMOTE_ADDR, $HTTP_REFERER, $HTTP_USER_AGENT;
			global $adminLogFile;

			if (!$fp = @fopen ($adminLogFile,'a'))
				// Label: OPEN_ADMIN_LOG
				setLogAndStatus("Opening $adminLogFile",'logAdmin ()','OPEN_ADMIN_LOG');
			fwrite ($fp, date('[l, j F Y, H.i:s] [B]')."\r\n");
			fwrite ($fp, "Address: $REMOTE_ADDR\r\n");
			fwrite ($fp, "Referer: $HTTP_REFERER\r\n");
			fwrite ($fp, "JS-Referer: ".$PARAM['Referer']."\r\n");
			fwrite ($fp, "User Agent: $HTTP_USER_AGENT\r\n");
			fwrite ($fp, "Username: ".$PARAM['alias']."\r\n");
			fwrite ($fp, "Password: ".$PARAM['password']."\r\n");
			fwrite ($fp, "\r\n");
			fclose ($fp);
			}
		return 0;
		}
 	}

 //function adminLogout()
 function adminLogout() {
 	global $adminID, $adminAlias;	//from session
 	global $SUBS;
 	$adminID = '';
 	$adminAlias = '';
 	$SUBS['LOGGED'] = '';
 	printPage('_admin_login.htmlt');
 	}

// - - - - - - 

//function insertFilm()
 function insertFilm() {
 	global $tbl_1d_films, $tbl_1d_pictures; 
 	global $SUBS, $PARAM, $MSG;
 	global $SNIMKA, $SNIMKA_name;	//file upload
 	global $PHOTOS;	//from session

	if ($PARAM['Add']==1)
		{
		$SUBS['ID'] = $PARAM['ID'];	//da ze zapische, ako e podadeno
		$SUBS['ERROR'] = '';

		if ($PARAM['TITLE'] != '')
			{
			$SUBS['TITLE'] = htmlEncode($PARAM['TITLE']);
			$SUBS['ORIGINAL'] = htmlEncode($PARAM['ORIGINAL']);
			} else  if ($PARAM['ORIGINAL'] != '')
				$SUBS['ORIGINAL'] = htmlEncode($PARAM['ORIGINAL']);
				else $SUBS['ERROR'] = $MSG[20014];

		////----[Mrasnika's] Edition 12.10.2002
		// if ($PARAM['DIRECTOR'] != '')
		//	$SUBS['DIRECTOR'] = htmlEncode($PARAM['DIRECTOR']);
		//	else if ($SUBS['ERROR']=='') $SUBS['ERROR'] = $MSG[20015];
		$SUBS['DIRECTOR'] = htmlEncode($PARAM['DIRECTOR']);

		// if ($PARAM['ACTORS'] != '')
		//	$SUBS['ACTORS'] = htmlEncode($PARAM['ACTORS']);
		//	else if ($SUBS['ERROR']=='') $SUBS['ERROR'] = $MSG[20016];
		$SUBS['ACTORS'] = htmlEncode($PARAM['ACTORS']);

		$SUBS['ADDITIONAL'] = htmlEncode($PARAM['ADDITIONAL']);	//ne e zadaljitelno
		$SUBS['SITE'] = htmlEncode($PARAM['URL']);
		$SUBS['GENRE'] = htmlEncode($PARAM['GENRE']);

		if  ($PARAM['INFO'] != '')
			$SUBS['INFO'] = htmlEncode($PARAM['INFO']);
			else if ($SUBS['ERROR']=='') $SUBS['ERROR'] = $MSG[20017];

		if ($SUBS['ERROR'] == '')
			{	//wsichko e ok, zapiswane w DB
			if ($PARAM['ID'] != '')
				$query = "UPDATE $tbl_1d_films SET
					Title = ".dbQuote($PARAM['TITLE']).",
					OriginalTitle = ".dbQuote($PARAM['ORIGINAL']).",
					Director = ".dbQuote($PARAM['DIRECTOR']).",
					Actors = ".dbQuote($PARAM['ACTORS']).",
					Additional = ".dbQuote($PARAM['ADDITIONAL']).",
					Description = ".dbQuote($PARAM['INFO']).",
					Genre = ".dbQuote($PARAM['GENRE']).",
					URL = ".dbQuote($PARAM['URL']).",
					tsLast = ".dbQuote(time())."
					WHERE ID=".$PARAM['ID'];
				else $query = "INSERT INTO $tbl_1d_films
				(Title, OriginalTitle, Director, Actors, Additional, Description, Genre, URL, tsLast)
				VALUES
				(".dbQuote($PARAM['TITLE']).",
				".dbQuote($PARAM['ORIGINAL']).",
				".dbQuote($PARAM['DIRECTOR']).",
				".dbQuote($PARAM['ACTORS']).",
				".dbQuote($PARAM['ADDITIONAL']).",
				".dbQuote($PARAM['INFO']).",
				".dbQuote($PARAM['GENRE']).",
				".dbQuote($PARAM['URL']).",
				".dbQuote(time()).")";
			$result = runQuery($query,'insetFilm()','ADD_TEXT_FILM_INFO');
			if ($PARAM['ID'] == '')	//poslednoto id
				{
				$PARAM['ID'] = mysql_insert_id();

				//zaradi snimkite ot PHOTOs
				if (session_is_registered('PHOTOS') && is_array($PHOTOS))
					for ($i = 0; $i<count($PHOTOS[session_id()]); $i++)
						if ($INFO = checkPicture($PHOTOS[session_id()][$i]))
							fixPicture($PHOTOS[session_id()][$i], 'film', $PARAM['ID'], $INFO);
				unset ($PHOTOS[session_id()]);
				}

			//prezarejdane
			if ($PARAM['Photo'] != 1)
				{
				$SUBS['COMMAND'] = $PARAM['cmd']."&err=20018&ID=".$PARAM['ID'];
				printPage('_admin_done.htmlt');
				return;
				}
			} else $SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	if ($PARAM['Photo'] == 1)
		{	//dobawi snimka
		if (!session_is_registered('PHOTOS'))
			{	//izpolzwa se ako nyama oschte registrirano ID
			session_register('PHOTOS');
			$PHOTOS = array();
			}

		$SUBS['ERROR'] = '';
		if ($SNIMKA == 'none')
			$SUBS['ERROR'] = $MSG[20019];

		if (!$INFO = checkPicture($SNIMKA))	//pass INFO as parameter to fixPicture
			$SUBS['ERROR'] = $MSG[20020];
		
		if ($SUBS['ERROR'] != '')
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {	//do tha job
			if ($PARAM['ID'] == '')
				{
				$where = getAdmSetting('TEMPORARY_DIR').session_id().md5($INFO[0]+$INFO[1]).$SNIMKA_name;
				if (@copy ($SNIMKA, $where))
					{
					if (!in_array($where, @$PHOTOS[session_id()]))
						{
						if (!is_array($PHOTOS[session_id()]))
							$PHOTOS[session_id()] = array();
						$PHOTOS[session_id()][] = $where;
						}
					} else setLogAndStatus("Writing", $SNIMKA, 0, "insertFilm()", 'WRITE_SESSION_PICS');
				} else {
				fixPicture($SNIMKA, 'film', $PARAM['ID'], $INFO);
			
				//prezarejdane
				$SUBS['COMMAND'] = $PARAM['cmd']."&err=20022&ID=".$PARAM['ID'];
				printPage('_admin_done.htmlt');
				return;
				}
			}
		}

	if ($PARAM['Delete'] != '')	//iztrij snimka
		if ($PARAM['ID'] != '')
			{
			//get thumbnail
			$query = "SELECT	URL, ID
				FROM $tbl_1d_pictures
				WHERE RefID = ".dbQuote($PARAM['Delete'])."
					AND RefType= 'thumb' ";
			$result = runQuery($query,'insetFilm()','GET_THUMBS');
			if ($row = db_fetch_row($result))
				{
				if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[0]))
					setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[0], 0, "insertFilm()", 'DEL_THUMBS');
	
				//erase thumbnail
				$query = "DELETE FROM $tbl_1d_pictures
					WHERE ID = $row[1]";
				$result = runQuery($query,'insetFilm()','DEL_THUMBS_DB');
				}
			$query = "SELECT	URL, ID
				FROM $tbl_1d_pictures
				WHERE ID = ".dbQuote($PARAM['Delete']);
			$result = runQuery($query,'insetFilm()','GET_PIC');
			if ($row = db_fetch_row($result))
				{
				if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[0]))
					setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[0], 0, "insertFilm()", 'DEL_PICS');
				//erase pic
				$query = "DELETE FROM $tbl_1d_pictures
					WHERE ID = $row[1]";
				$result = runQuery($query,'insetFilm()','DEL_PICS');
				}
			} else {
			if (!@unlink(getAdmSetting('UPLOAD_DIR').$PHOTOS[session_id()][$PARAM['Delete']])) 	//from session
				setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$PHOTOS[session_id()][$PARAM['Delete']],
					0, "insertFilm()", 'DEL_SESSIONS');
			unset ($PHOTOS[session_id()][$PARAM['Delete']]);
			}

	if ($PARAM['Add'] != 1)
		{	//podgotowka za pokazwane
		$query = "SELECT	Title,
				Director,
				Actors,
				Additional,
				Description,
				OriginalTitle,
				Genre,
				URL
			FROM $tbl_1d_films
			WHERE ID = ".dbQuote($PARAM['ID']);
		$result = runQuery($query,'insetFilm()','GET_TEXT_FILM_INFO');
		if ($row = db_fetch_row($result))
			{
			$SUBS['TITLE'] = htmlEncode($row[0]);
			$SUBS['DIRECTOR'] = htmlEncode($row[1]);
			$SUBS['ACTORS'] = htmlEncode($row[2]);
			$SUBS['ADDITIONAL'] = htmlEncode($row[3]);
			$SUBS['INFO'] = htmlEncode($row[4]);
			$SUBS['ORIGINAL'] = htmlEncode($row[5]);
			$SUBS['GENRE'] = htmlEncode($row[6]);
			$SUBS['SITE'] = htmlEncode($row[7]);
			} else {
			if (($PARAM['ID'] != '') && ($SUBS['ERROR'] == ''))
				{
				$SUBS['ERROR'] = $MSG[20021];
				$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
				}
			$PARAM['ID'] = '';
			}
		}

	//pokaji snimkite
	if ($PARAM['ID'] == '')
		{
		for ($i=0; $i<count($PHOTOS[session_id()]); $i++)
			{
			$SUBS['URL'] = $PHOTOS[session_id()][$i];
			$SUBS['IND'] = $i;
			$SUBS['THUMB'] = " &nbsp; ".$MSG[20024]." ".($i+1);
			$SUBS['SNIMKAS'] .= fileParse('_admin_edit_film_snimka.htmlt');
			}
		} else {
		$query = "SELECT	URL,
				Width,
				Height,
				ID
			FROM $tbl_1d_pictures
			WHERE (RefID LIKE ".dbQuote($PARAM['ID']).")
				AND RefType = 'film' ";
		$result = runQuery($query,'insetFilm()','GET_PICS_FILM_INFO');
		$upload = getAdmSetting('UPLOAD_DIR');
		$SUBS['UPLOAD'] = $upload;
		while ($row = db_fetch_row($result))
			{
			$query = "SELECT	URL,
					Width,
					Height
				FROM $tbl_1d_pictures
				WHERE (RefID = $row[3]) AND RefType = 'thumb' ";
			$res = runQuery($query,'insetFilm()','GET_FILM_THUMB');
			$thumb = db_fetch_row($res);
			$SUBS['URL'] = $row[0];
			$SUBS['IND'] = $row[3];
			$SUBS['THUMB'] = "<img border=\"0\" width=\"$thumb[1]\" height=\"$thumb[2]\" src=\"$upload$thumb[0]\" align=\"absmiddle\">";
			$SUBS['SNIMKAS'] .= fileParse('_admin_edit_film_snimka.htmlt');
			}
		}

	////----[Mrasnika's] Edition 12.10.2002
	$SUBS['FILM_NAV'] = fileParse('_admin_edit_film2.htmlt');

	$SUBS['ID'] = $PARAM['ID'];
	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

 	printPage('_admin_edit_film.htmlt');
 	}

//function browseFilms()
function browseFilms() {
	global $tbl_1d_films, $tbl_1d_videodvd, $tbl_1d_agenda, $tbl_1d_pictures;
	global $SUBS, $MSG, $PARAM, $MONTHS, $MON, $MONTHS2;
	global $span;	//sedmici
	global $searchString, $searchCat, $searchGroup, $searchDate1, $searchDate2, $searchPrem, $searchPage, $searchCount, $searchWhere;	//session

	////---- Commands
	if ($PARAM['Unset'] == 1)
		{	//izgkluchi AGENDA

		reset ($PARAM);
		$Films = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^film_([0-9]+)$', $k, $R))
				$Films .= ",$R[1]";
		if ($Films == '0')
			{
			$error = '20008';
			} else {
			$query = "UPDATE $tbl_1d_films
				SET Agenda = 'no'
				WHERE ID IN ($Films)";
			$result = runQuery($query,'browseFilms()','UNSET_AGENDA');
			$error = '20064';
			}

		$SUBS['COMMAND'] = $PARAM['cmd']."&err=$error";
		printPage('_admin_done.htmlt');
		return;
		}

	if ($PARAM['Set'] == 1)
		{	//wkluchi AGENDA

		$query = "SELECT ID FROM $tbl_1d_films
			WHERE ID = ".dbQuote($PARAM['id']);
		$result = runQuery($query,'browseFilms()','CHECK_ID');
		if (db_num_rows($result) == 0)
			{
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20008";
			printPage('_admin_done.htmlt');
			return;
			} else {
			$query = "UPDATE $tbl_1d_films
				SET Agenda = 'yes'
				WHERE ID = ".dbQuote($PARAM['id']);
			$result = runQuery($query,'browseFilms()','SET_AGENDA');
			$PARAM['FILMS'] = $PARAM['id'];
			$PARAM['err'] = '20065';
			manageAgenda();
			return;
			}
		}

	if ($PARAM['Delete'] == 1)
		{	//istrij filmi
		reset ($PARAM);
		$Films = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^film_([0-9]+)$', $k, $R))
				$Films .= ",$R[1]";
		if ($Films == '0')
			{
			$error = '20008';
			} else {
			//iztrij kartinki;
			$query = "SELECT	$tbl_1d_pictures.ID,
					$tbl_1d_pictures.URL,
					$tbl_1d_pictures.RefType,
					
					a1.ID AS thumbID,
					a1.URL AS thumbURL

				FROM $tbl_1d_films
				LEFT JOIN $tbl_1d_videodvd
					ON $tbl_1d_videodvd.FilmID = $tbl_1d_films.ID
				LEFT JOIN $tbl_1d_pictures
					ON (	$tbl_1d_pictures.RefID = $tbl_1d_films.ID
						AND $tbl_1d_pictures.RefType = 'film'
						) OR (
						$tbl_1d_pictures.RefType IN ('video','dvd')
						AND $tbl_1d_pictures.RefID = $tbl_1d_videodvd.ID
						)
				LEFT JOIN $tbl_1d_pictures a1
					ON a1.RefType = 'thumb'
						AND a1.RefID = $tbl_1d_pictures.ID
				WHERE $tbl_1d_films.ID IN ($Films)";

			$result = runQuery($query,'browseFilms()','GET_PISTURES');
			$Pics ='0';
			while ($row = db_fetch_row($result))
				switch ($row[2]) {
					case 'film' :	//film
						//del pic
						if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[1]))
							setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[1], 0, "browseFilms()", 'DEL_FILM_PICS');
						$Pics .= ",$row[0]";
						//del thumb
						if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[4]))
							setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[4], 0, "browseFilms()", 'DEL_THUMB_PICS');
						$Pics .= ",$row[3]";
						break;

					case 'dvd' :	;//video & dvd
					case 'video' :	
						if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[1]))
							setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[1], 0, "browseFilms()", 'DEL_VIDEO_DVD_PICS');
						$Pics .= ",$row[0]";
						break;
					}
			//iztriwane na kartinkite ot db
			$query = "DELETE FROM $tbl_1d_pictures
				WHERE ID IN ($Pics) ";
			$result = runQuery($query,'browseFilms()','ERASE_PISTURES');

			//istriwane na video i dvd informaciyata
			$query = "DELETE FROM $tbl_1d_videodvd
				WHERE FilmID IN ($Films) ";
			$result = runQuery($query,'browseFilms()','ERASE_VIDEO_DVD');

			//iztriwane ot kino programata
			$query = "DELETE FROM $tbl_1d_agenda
				WHERE Film IN ($Films)
					AND Type = 'list' ";
			$result = runQuery($query,'browseFilms()','ERASE_AGENDA');

			//iztriwane na samiyat film
			$query = "DELETE FROM $tbl_1d_films
				WHERE ID IN ($Films) ";
			$result = runQuery($query,'browseFilms()','ERASE_FILMS');

			$error = '20066';
			}

		$SUBS['COMMAND'] = $PARAM['cmd']."&err=$error";
		printPage('_admin_done.htmlt');
		return;
		}

	//SESSION
	if (!session_is_registered('searchString')) session_register('searchString');
	if (!session_is_registered('searchCat')) session_register('searchCat');
	if (!session_is_registered('searchGroup')) session_register('searchGroup');
	if (!session_is_registered('searchDate1')) session_register('searchDate1');
	if (!session_is_registered('searchDate2')) session_register('searchDate2');
	if (!session_is_registered('searchPrem')) session_register('searchPrem');
	if (!session_is_registered('searchPage'))
		{
		session_register('searchPage');
		$searchPage = getAdmSetting('RESULT_PER_PAGE');
		}
	//set perpage
	if (($PARAM['SearchPage']) && ($PARAM['SearchPage']>0))
		$searchPage = $PARAM['SearchPage'];

	if (!session_is_registered('searchCount')) session_register('searchCount');
	if (!session_is_registered('searchWhere'))
		{
		session_register('searchWhere');
		$searchWhere = '1';
		}

	if ($PARAM['Search'] == 1)
		{
		$searchString = $PARAM['String'];
		$searchCat = $PARAM['Category'];
		$searchGroup = $PARAM['Group'];
		////---[Mrasnika's] Edition 01.10.2002
		// $searchDate1 = $PARAM['Date1'];
		// $searchDate2 = $PARAM['Date2'];
		$searchDate1 = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
		$searchDate2 = 1 + strToTime ($PARAM['Day2'].' '.$MONTHS2[$PARAM['Month2']].' '.$PARAM['Year2']);
		$searchPrem = $PARAM['Prem'];
		$searchWhere = '1';

		$string = dbQuote("%$searchString%");

		switch ($searchCat) {
			case 1 :	//Zaglavie
				$searchWhere .= " AND (($tbl_1d_films.Title LIKE $string) OR ($tbl_1d_films.OriginalTitle LIKE $string)) ";
				break;
			case 2 :	//Aktyori
				$searchWhere .= " AND $tbl_1d_films.Actors LIKE $string ";
				break;
			case 3 :	//rejisyor
				$searchWhere .= " AND $tbl_1d_films.Director LIKE $string ";
				break;

			default :	//wsichki poleta
				$searchWhere .= " AND (($tbl_1d_films.Actors LIKE $string)
						OR ($tbl_1d_films.Title LIKE $string)
						OR ($tbl_1d_films.OriginalTitle LIKE $string)
						OR ($tbl_1d_films.Director LIKE $string)
						OR ($tbl_1d_films.Description LIKE $string)
						OR ($tbl_1d_films.Additional LIKE $string)
						OR ($tbl_1d_films.Genre LIKE $string)
						OR ($tbl_1d_films.URL LIKE $string)
						) ";
			}

		switch ($searchGroup) {
			case 1 : 	//video
				$searchWhere .= "	AND ($tbl_1d_videodvd.FilmID IS NOT NULL)
						AND ($tbl_1d_videodvd.Type = 'video' ) ";
				break;

			case 2 : 	//dvd
				$searchWhere .= "	AND (a1.FilmID IS NOT NULL)
						AND (a1.Type = 'dvd' ) ";
				break;

			case 3 : 	//kino programa
				$searchWhere .= "	AND ($tbl_1d_films.Agenda = 'yes' )";
				break;
			default : 	;	//wsichki
			}

		if ($searchDate1>$searchDate2)
			{	//flip them
			$s = $searchDate1;
			$searchDate1 = $searchDate2;
			$searchDate2 = $s;
			}


		//ako se tarsi po premieri i nyama data za "predi",
		//izpolzwa se data na poslednata aktiwna sedmica
		if (($searchPrem > 0) && ($searchDate2 == 0))
			$searchDate21 = getLastWeek();
			else $searchDate21 = $searchDate2;

		switch ($searchPrem) {
			case 1 :	//kino
				$searchWhere .=  " AND $tbl_1d_films.tsPremiere >= $searchDate1
						AND $tbl_1d_films.tsPremiere <= ($searchDate21+604799)
						AND $tbl_1d_films.tsPremiere != 0 ";
				break;
			case 4 :	//usa
				$searchWhere .=  " AND $tbl_1d_films.tsUSAPremiere >= $searchDate1
						AND $tbl_1d_films.tsUSAPremiere <= ($searchDate21+604799)
						AND $tbl_1d_films.tsUSAPremiere != 0 ";
				break;
			case 2 :	//video
				$searchWhere .=  " AND $tbl_1d_videodvd.tsWhen >= $searchDate1
						AND $tbl_1d_videodvd.tsWhen <= ($searchDate21+604799)
						AND $tbl_1d_videodvd.Type = 'video' ";
				break;
			case 3 :	//dvd
				$searchWhere .=  " AND a1.tsWhen >= $searchDate1
						AND a1.tsWhen <= ($searchDate21+604799)
						AND a1.Type = 'dvd' ";
				break;
			default :	//wsichko
				$searchDate1 = 0;
				$searchDate2 = 0;
			}
		}

	//prepare sort
	switch ($PARAM['sort']) {
		case 1 :	$searchSort = ' ASC ';
			$SUBS['SORT'] = 0;
			break;
		case 0 :	$searchSort = ' DESC ';
			$SUBS['SORT'] = 1;
			break;
		default :	$searchSort = ' DESC ';
			$SUBS['SORT'] = 0;
		}

	//prepare order
	switch ($PARAM['orderby']) {
		case 1 :	$searchOrder = "$tbl_1d_films.Title $searchSort, $tbl_1d_films.OriginalTitle $searchSort";
			$searchSort = '';
			break;
		case 2 :	$searchOrder = "$tbl_1d_films.tsUSAPremiere";
			break;
		case 3 :	$searchOrder = "$tbl_1d_films.tsPremiere";
			break;
		case 4 :	$searchOrder = "$tbl_1d_videodvd.tsWhen";
			break;
		case 5 :	$searchOrder = "a1.tsWhen";
			break;
		case 6 :	$searchOrder = "$tbl_1d_films.Agenda";
			break;
		case 7 :	$searchOrder = "$tbl_1d_films.tsLast";
			break;
		default :	$searchOrder = "$tbl_1d_films.ID";	//case 0
		}

	$searchSelect =
		"SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.Title,
			$tbl_1d_films.tsLast,
			$tbl_1d_films.Agenda,
			$tbl_1d_films.tsPremiere,
			
			$tbl_1d_videodvd.ID,
			$tbl_1d_videodvd.tsWhen,
			$tbl_1d_videodvd.tsLast,
			
			a1.ID,
			a1.tsWhen,
			a1.tsLast,
			
			$tbl_1d_films.tsUSAPremiere,
			$tbl_1d_films.OriginalTitle";

	$searchFrom =
		"FROM $tbl_1d_films
		LEFT JOIN $tbl_1d_videodvd
			ON $tbl_1d_videodvd.FilmID = $tbl_1d_films.ID
				AND $tbl_1d_videodvd.Type = 'video'
		LEFT JOIN $tbl_1d_videodvd AS a1
			ON a1.FilmID = $tbl_1d_films.ID
				AND a1.Type = 'dvd' ";

	//get search count
	if ((!$searchCount) || ($PARAM['Search'] == 1))
		{
		$query = "SELECT COUNT($tbl_1d_films.ID) $searchFrom WHERE $searchWhere";
		$result = runQuery($query, 'browseFilms()', 'GET_FILMS_COUNT');
		if ($row = db_fetch_row($result))
			$searchCount = $row[0];
			else $searchCount = 0;
		}

	if (!$PARAM['offs'])
		$searchStart = 0;
		else $searchStart = $PARAM['offs'];

	if ($PARAM['offs']>= $searchCount)
		{
		$SUBS['ERROR'] = $MSG[20047];	//out of search limits
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		$searchRows = 0;
		}

	//run query
	if ($SUBS['ERROR'] == '')
		{
		$query = "$searchSelect $searchFrom WHERE $searchWhere $searchGroupBy
			ORDER BY $searchOrder $searchSort
			LIMIT $searchStart, $searchPage";
		$result = runQuery($query, 'browseFilms()', 'DO_FILMS_SEARDCH');
		$searchRows = db_num_rows ($result);
		}

	if ($PARAM['Search'] == 1)
		{
		$SUBS['COMMAND'] = $PARAM['cmd'];
		printPage('_admin_done.htmlt');
		return;
		}

////---- test only
//echo nl2br($query);

	$SUBS['SORTED'] = (1+$SUBS['SORT'])%2;
	$SUBS['ORDER'] = $PARAM['orderby'];
	$SUBS['PERPAGE'] = $searchPage;
	$SUBS['START'] = $searchStart;

	while ($row = db_fetch_row($result))
		{
		$SUBS['ID'] = sprintf('%04d', $row[0]);
		$SUBS['ID1'] = $row[0];
		if ($row[1] != '')
			$SUBS['TITLE'] = htmlEncode($row[1]);
			else $SUBS['TITLE'] = htmlEncode($row[12]);
		
		$SUBS['CINEMA'] = $MSG[20067];
		$SUBS['CW'] = getNextWeek();
		if ($row[4] > 0)
			{
			$SUBS['CINEMA'] = showWeek($row[4]);
			$SUBS['CW'] = $row[4];
			}

		$SUBS['USA'] = '';
		$SUBS['UW'] = '';
		if ($row[11] > 0)
			{
			$SUBS['USA'] = showWeek($row[11]);
			$SUBS['UW'] = $row[11];
			}

		$SUBS['VIDEO'] = $MSG[20067];
		$SUBS['VW'] = getNextWeek();
		$SUBS['VID'] = '';
		$SUBS['VLAST'] = '';
		if ($row[6] > 0)
			{
			$SUBS['VIDEO'] = showWeek($row[6]);
			$SUBS['VW'] = $row[6];
			$SUBS['VID'] = $row[5];
			$SUBS['VLAST'] = $MSG[20068].date('d ', $row[7]).$MONTHS[intval(date('m',$row[7]))] . date(' Y H:i:s', $row[7]);
			}

		$SUBS['DVD'] = $MSG[20067];
		$SUBS['DW'] = getNextWeek();
		$SUBS['DID'] = '';
		$SUBS['DLAST'] = date('d ', $row[10]).$MONTHS[intval(date('m',$row[10]))] . date(' Y H:i:s', $row[10]);
		if ($row[9] > 0)
			{
			$SUBS['DVD'] = showWeek($row[9]);
			$SUBS['DW'] = $row[9];
			$SUBS['DID'] = $row[8];
			$SUBS['DLAST'] = $MSG[20068].date('d ', $row[10]).$MONTHS[intval(date('m',$row[10]))] . date(' Y H:i:s', $row[10]);
			}
		if ($row[3] == 'yes')
			$SUBS['AGENDA'] =  $MSG[20058];
			else$SUBS['AGENDA'] = $MSG[20059];
		$SUBS['LAST'] = date('d.', $row[2]).$MON[intval(date('m',$row[2]))] . date('.Y H:i:s', $row[2]);

		$SUBS['FILMS'] .= fileParse('_admin_browse_film_row.htmlt');
		}

	//navigation
	$SUBS['TOTAL'] = $searchCount;
	$template = fileToString(getAdmSetting('TEMPLATES_DIR').'/_admin_browse_film_navigation.htmlt');
	if ($searchRows != 0)
		{
		$SUBS['PAGE'] = (1+$searchStart) .' - '. ($searchStart + $searchRows);
		} else $SUBS['PAGE'] = '0 - 0';

	if ($searchStart != 0)
		{
		$SUBS['BUTTON'] = $MSG[20060];	//first
		$SUBS['START'] = 0;
		$SUBS['FIRST'] = strParse($template);
		} else $SUBS['FIRST'] = $MSG[20060];

	if ($searchStart != 0)
		{
		$SUBS['BUTTON'] = $MSG[20063];	//previous
		if (($SUBS['START'] = $searchStart - $searchPage) < 0) $SUBS['START'] = 0;
		$SUBS['PREV'] = strParse($template);
		} else $SUBS['PREV'] = $MSG[20063];

	if (($SUBS['START'] = $searchStart + $searchPage) < $searchCount)
		{
		$SUBS['BUTTON'] = $MSG[20062];	//next
		$SUBS['NEXT'] = strParse($template);
		} else $SUBS['NEXT'] = $MSG[20062];

	if ($searchStart < ($SUBS['START'] = $searchCount - $searchPage))
		{
		$SUBS['BUTTON'] = $MSG[20061];	//last
		$SUBS['LAST'] = strParse($template);
		} else $SUBS['LAST'] = $MSG[20061];
	$SUBS['START'] = $searchStart;

//// - - - - dispay
	// get min and max dates
	$query = "SELECT	min($tbl_1d_videodvd.tsWhen),
			min($tbl_1d_films.tsPremiere)
		FROM $tbl_1d_films
		LEFT JOIN $tbl_1d_videodvd
			ON $tbl_1d_videodvd.FilmID  = $tbl_1d_films.ID
		WHERE $tbl_1d_films.tsPremiere > 0" ;
	$result = runQuery($query,'browseFilms()','GET_DATES');

	 if ($row = db_fetch_row($result))
		$span = min ($row[0], $row[1]);

	////---[Mrasnika's] Edition 01.10.2002
	//	 else $span = 946080000;	//??
	if (!$searchDate1)
		$searchDate1 = $span;

	if (!$searchDate2)
		$searchDate2 = time();

	//load dates
	$PARAM['Year1'] = date ('Y', $searchDate1);
	$PARAM['Month1'] = date ('m', $searchDate1);
	$PARAM['Day1'] = date ('d', $searchDate1);

	$PARAM['Year2'] = date ('Y', $searchDate2);
	$PARAM['Month2'] = date ('m', $searchDate2);
	$PARAM['Day2'] = date ('d', $searchDate2);

	////---[Mrasnika's] Edition 01.10.2002
	// $SUBS['DATE1'] = getWeeks($searchDate1);
	// $SUBS['DATE2'] = getWeeks($searchDate2);
	
	$Year2 = date ('Y');
	$Year1 = date ('Y', $span);
	
	//date 1
	for ($i=$Year1; $i<=$Year2;$i++)
		if ($i==$PARAM['Year1'])
			$SUBS['YEAR1'] .= "<OPTION value=\"$i\" selected>$i";
			else $SUBS['YEAR1'] .= "<OPTION value=\"$i\">$i";
	for ($i=1; $i<=12; $i++)
		if ($i == $PARAM['Month1'])
			$SUBS['MONTH1'] .= "<OPTION value=\"$i\" selected>".$MONTHS[$i];
			else $SUBS['MONTH1'] .= "<OPTION value=\"$i\">".$MONTHS[$i];
	for ($i=1; $i<=31; $i++)
		if ($i == $PARAM['Day1'])
			$SUBS['DAY1'] .= "<OPTION value=\"$i\" selected>".sprintf('%02d',$i);
			else $SUBS['DAY1'] .= "<OPTION value=\"$i\">".sprintf('%02d',$i);

	//date 2
	for ($i=$Year1; $i<=$Year2;$i++)
		if ($i==$PARAM['Year2'])
			$SUBS['YEAR2'] .= "<OPTION value=\"$i\" selected>$i";
			else $SUBS['YEAR2'] .= "<OPTION value=\"$i\">$i";
	for ($i=1; $i<=12; $i++)
		if ($i == $PARAM['Month2'])
			$SUBS['MONTH2'] .= "<OPTION value=\"$i\" selected>".$MONTHS[$i];
			else $SUBS['MONTH2'] .= "<OPTION value=\"$i\">".$MONTHS[$i];
	for ($i=1; $i<=31; $i++)
		if ($i == $PARAM['Day2'])
			$SUBS['DAY2'] .= "<OPTION value=\"$i\" selected>".sprintf('%02d',$i);
			else $SUBS['DAY2'] .= "<OPTION value=\"$i\">".sprintf('%02d',$i);

	$SUBS['PREM'.$searchPrem] = ' SELECTED';
	$SUBS['GRO'.$searchGroup] = ' SELECTED';
	$SUBS['CAT'.$searchCat] = ' SELECTED';
	$SUBS['STRING'] = htmlEncode($searchString);

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}
	printPage ('_admin_browse_film.htmlt');
	}

// - - - - - - 

 //function manageCity()
 function manageCity() {
 	global $tbl_1d_cities, $tbl_1d_cinemas;
	global $SUBS, $PARAM, $MSG;
	global $adminID;	//from session

	//dobawyane na gradowe
	if ($PARAM['Add'] == 1)
		{
		 if ($PARAM['ADDCITY']=='')	//prazni whodni danni
			$SUBS['ERROR'] = $MSG[20075];

		//proweri dali weche ne e wawedena
		if ($PARAM['id'] != '')
			$query = "SELECT ID FROM $tbl_1d_cities
				WHERE City LIKE ".dbQuote($PARAM['ADDCITY'])."
					AND ID != ".dbQuote($PARAM['id']);
			else $query = "SELECT ID FROM $tbl_1d_cities
				WHERE City LIKE ".dbQuote($PARAM['ADDCITY']);
		if ($SUBS['ERROR'] == '')
			{
			$result = runQuery($query,'manageCity()','CHECK_ADD_CITY');
			if (db_num_rows($result) != 0)
				$SUBS['ERROR'] = $MSG[20076];
			}
		
		if ($SUBS['ERROR'] != '')
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {
			if ($PARAM['id'] == '')
				$query = "INSERT INTO $tbl_1d_cities
					(City, Priority, adminID, Active)
					VALUES
					(".dbQuote($PARAM['ADDCITY']).",
					".intval($PARAM['NO']).",
					$adminID,
					".dbQuote($PARAM['ACTIVE']).")";
				else $query =
					"UPDATE $tbl_1d_cities SET
						City = ".dbQuote($PARAM['ADDCITY']).", 
						Priority = ".intval($PARAM['NO']).",
						Active = ".dbQuote($PARAM['ACTIVE'])."
					WHERE ID = ".dbQuote($PARAM['id']);

			$result = runQuery($query,'manageCity()','ADD_CITY');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20077";
			printPage('_admin_done.htmlt');
			return;
			}
		}

	//iztriwane na gradowe
	if ($PARAM['Delete']==1)
		{
		reset ($PARAM);
		$Cities = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^city_([0-9]+)$', $k, $R))
				$Cities .= ",$R[1]";
		if ($Cities == '0')
			$SUBS['ERROR'] = $MSG[20008];

		//proweri stawa li za iztriwane
		$query = "SELECT ID
			FROM $tbl_1d_cinemas
			WHERE CityID IN ($Cities)";
		$result = runQuery($query,'manageCity()','CHECK_CITY');
		if (($SUBS['ERROR'] == '')&& (db_num_rows($result) != 0))
			$SUBS['ERROR'] = $MSG[20078];

		if ($SUBS['ERROR']=='')
			{
			//iztriwane na grada
			$query = "DELETE FROM $tbl_1d_cities
				WHERE ID IN ($Cities)";
			$result = runQuery($query,'manageCity()','DEL_CITIES');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20079";
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse ('_admin_error.htmlt');
		}
		
	//pokaji gradowete
	$query = "SELECT	$tbl_1d_cities.ID,
			$tbl_1d_cities.City,
			($tbl_1d_cinemas.CityID IS NULL),
			$tbl_1d_cities.Priority,
			$tbl_1d_cities.Active
		FROM $tbl_1d_cities
		LEFT JOIN $tbl_1d_cinemas
			ON $tbl_1d_cinemas.CityID = $tbl_1d_cities.ID
		GROUP BY $tbl_1d_cities.ID
		ORDER BY $tbl_1d_cities.Priority";
	$result = runQuery($query, 'manageCity()', 'GET_CITIES');

	if (db_num_rows($result) == 0)
		$SUBS['NONE'] = $MSG[20023];
		else while ($row = db_fetch_row($result))
			{
			$SUBS['CITYID'] = $row[0];
			$SUBS['CITY'] = htmlEncode($row[1]);
			$SUBS['ISDEL'] = $row[2];
			$SUBS['NOM'] = $row[3];
			
			//active or not
			if ($row[4] == 'yes')
				$SUBS['COLOR'] = 'white';
				else $SUBS['COLOR'] = '#cfcfcf';
			
			$SUBS['CITIES'] .= fileParse('_admin_manage_city_row.htmlt');
			
			if (($PARAM['Add'] != 1) && ($row[0] == $PARAM['id']))
				{
				$PARAM['ADDCITY'] = $row[1];
				$PARAM['NO'] = $row[3];
				$PARAM['ACTIVE'] = $row[4];
				}
			}

	$SUBS['ID'] = $PARAM['id'];
	$SUBS['ADDCITY'] = htmlEncode($PARAM['ADDCITY']);
	$SUBS['NO'] = $PARAM['NO'];
	$SUBS['ACTIVE'.strToUpper($PARAM['ACTIVE'])] = " SELECTED ";

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

 	printPage('_admin_manage_city.htmlt');
	}

 //function manageCinema()
 function manageCinema() {
 	global $tbl_1d_cities, $tbl_1d_cinemas, $tbl_1d_agenda;
 	global $PARAM, $SUBS, $MSG;
 	global $adminID;	//from session

	//dobawyane na gradowe
	if ($PARAM['Add']==1)
		{
		if ($PARAM['ADDCINEMA'] == '')	//da ne e prazen
			$SUBS['ERROR'] = $MSG[20075];


		if ($PARAM['id'] == '')	//proweri dali weche ne e wawedena
			$query = "SELECT ID FROM $tbl_1d_cinemas
				WHERE CityID LIKE ".dbQuote($PARAM['CITY'])."
					AND Cinema LIKE ".dbQuote($PARAM['ADDCINEMA']);
			else $query = "SELECT ID FROM $tbl_1d_cinemas
				WHERE CityID LIKE ".dbQuote($PARAM['CITY'])."
					AND Cinema LIKE ".dbQuote($PARAM['ADDCINEMA'])."
					AND ID != ".dbQuote($PARAM['id']);
		if ($SUBS['ERROR'] != '')
			{
			$result = runQuery($query,'manageCinema()','CHECK_ADD_CINEMA');
			if (db_num_rows($result) > 0)
				$SUBS['ERROR'] = $MSG[20009];
			}

		if ($SUBS['ERROR'] != '')
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {
			if ($PARAM['id'] == 0)
				$query = "INSERT INTO $tbl_1d_cinemas
					(CityID, Cinema, Priority, adminID) VALUES
					(".dbQuote($PARAM['CITY']).",
					".dbQuote($PARAM['ADDCINEMA']).",
					".intval($PARAM['NO']).",
					$adminID)";
				else $query = "UPDATE $tbl_1d_cinemas SET
						CityID = ".dbQuote($PARAM['CITY']).",
						Cinema = ".dbQuote($PARAM['ADDCINEMA']).",
						Priority = ".intval($PARAM['NO'])."
						WHERE $tbl_1d_cinemas.ID = ".dbQuote($PARAM['id']);
			$result = runQuery($query,'manageCinema()','ADD_CINEMA');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20010&CITY=".$PARAM['CITY'];
			printPage('_admin_done.htmlt');
			return;
			}
		}

	//iztriwane na kinata
	if ($PARAM['Delete']==1)
		{
		reset ($PARAM);
		$Cinemas = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^city_([0-9]+)$', $k, $R))
				$Cinemas .= ",$R[1]";
		if ($Cinemas == '0')	//nischto ne e izbrano
			$SUBS['ERROR'] = $MSG[20008];

		//dali e swyrzano kam programata
		$query = "SELECT ID FROM $tbl_1d_agenda
			WHERE CinemaID IN ($Cinemas)";
		$result = runQuery($query,'manageCinema()','CHECK_AGENDA_CINEMAS');
		if (($SUBS['ERROR'] == '') && (db_num_rows($result)>0))
			$SUBS['ERROR'] = $MSG[20011];

		if ($SUBS['ERROR'] != '')
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {	//iztriwane na kinata
			$query = "DELETE FROM $tbl_1d_cinemas
				WHERE ID IN ($Cinemas)";
			$result = runQuery($query,'manageCinema()','DEL_CINEMAS');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20013";
			printPage('_admin_done.htmlt');
			return;
			}
		}

	//pokaji kinata
	$query = "SELECT	$tbl_1d_cinemas.ID,
			Cinema,
			City,
			CityID,
			
			($tbl_1d_agenda.CinemaID IS NULL),
			
			$tbl_1d_cinemas.Priority
		FROM	$tbl_1d_cinemas,
			$tbl_1d_cities

		LEFT JOIN $tbl_1d_agenda
			ON $tbl_1d_agenda.CinemaID = $tbl_1d_cinemas.ID

		WHERE	$tbl_1d_cities.ID=$tbl_1d_cinemas.CityID
		
		GROUP BY $tbl_1d_cinemas.ID
		
		ORDER BY	$tbl_1d_cities.Priority,
			$tbl_1d_cinemas.Priority";
	!$result = runQuery($query,'manageCinema()','GET_CINEMAS');
	if (db_num_rows($result) == 0)
		$SUBS['NONE'] = $MSG[20007];
		else {
		$city = '0';
		while ($row = db_fetch_row($result))
			{
			if ($city != $row[2])
				{
				$city = $row[2];
				$SUBS['CITY'] = $city;
				$SUBS['CITYID'] = $row[3];
				$SUBS['CINEMAS'] .= fileParse ('_admin_manage_cinema_city.htmlt');
				}
			$SUBS['CINEMAID'] = $row[0];
			$SUBS['CINEMA'] = htmlEncode($row[1]);
			$SUBS['ISDEL'] = $row[4];
			$SUBS['NOM'] = $row[5];
			$SUBS['CINEMAS'] .= fileParse('_admin_manage_cinema_row.htmlt');
			if (($PARAM['Add'] != 1) && ($row[0] == $PARAM['id']))
				{
				$PARAM['ADDCINEMA'] = $row[1];
				$PARAM['NO'] = $row[5];
				$PARAM['CITY'] = $row[3];
				}
			}
		}

 	//podgotwyane na gradowete
 	$query = "SELECT ID, City
 		FROM $tbl_1d_cities";
	$result = runQuery($query,'manageCinema()','GET_CITIES');
	while ($row = db_fetch_row($result))
		if ($PARAM['CITY']==$row[0])
			$SUBS['CITIES'] .= "<OPTION VALUE=\"$row[0]\" SELECTED>$row[1]";
			else $SUBS['CITIES'] .= "<OPTION VALUE=\"$row[0]\">$row[1]";

	$SUBS['ID'] = $PARAM['id'];
	$SUBS['ADDCINEMA'] = htmlEncode($PARAM['ADDCINEMA']);
	$SUBS['NO'] = $PARAM['NO'];
	$SUBS['CITY2'] = $PARAM['CITY'];

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

 	printPage('_admin_manage_cinema.htmlt');
 	}

 //function manageAgenda()
 function manageAgenda() {
	return include 'includes/admin.agenda.php';
 	}

 //function cinemaPremiere()
 function cinemaPremiere() {
 	global $tbl_1d_films, $tbl_1d_videodvd;
 	global $SUBS, $PARAM, $MSG, $MONTHS, $MONTHS2;

	if ($PARAM['Delete'] == '1')
		{
		reset ($PARAM);
		$Prem = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^cinema_([0-9]+)$', $k, $R))
				$Prem .= ",$R[1]";
		if ($Prem == '0')
			{
			$SUBS['ERROR'] = $MSG[20008];
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			} else {
			$query = "UPDATE $tbl_1d_films
				SET tsPremiere = 0
				WHERE ID IN ($Prem)";
			$result = runQuery($query,'cinemaPremiere()','DEL_CINEMA_PREM');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20048&WEEK=".$PARAM['WEEK'];
			printPage('_admin_done.htmlt');
			return;
			}
		}

	if ($PARAM['Add'] == 1)
		{	//nowo zaglavie
		$SUBS['ERROR'] ='';
		if ($PARAM['FILM'] < 1)
			$SUBS['ERROR'] = $MSG[20032];

		if ($SUBS['ERROR'] == '')
			{
			$query =	"UPDATE $tbl_1d_films SET
					tsPremiere = ".dbQuote($PARAM['WEEK']).",
					tsLast = ".time()."
					WHERE ID = ".dbQuote($PARAM['FILM']);
			$result = runQuery($query,'cinemaPremiere()','SAVE_PREMIERE');

			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20033&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK'];
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	//pokaji premierite
	if ($PARAM['Show'] == 1)
		{
		////----[Mrasnika's] Edition 12.10.2002
		if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();

		$SUBS['COMMAND'] = $PARAM['cmd']."&WHEN=".$PARAM['WHEN']."&WEEK=".$PARAM['WHEN'];
		printPage('_admin_done.htmlt');
		return;
		}

	////----[Mrasnika's] Edition 12.10.2002
	// if ($PARAM['WHEN']=='')
	//	$PARAM['WHEN'] = getNextWeek();

	if ($PARAM['WHEN'])
		{
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		} else if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();

	//pokaji filmi
	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.Title,
			tsLast,
			tsPremiere,
			$tbl_1d_films.Actors
		FROM $tbl_1d_films
		WHERE 	$tbl_1d_films.tsPremiere >= ".week($PARAM['WHEN'])." AND
			$tbl_1d_films.tsPremiere <= (".week($PARAM['WHEN'])."+604799) AND
			$tbl_1d_films.Agenda = 'yes'
		ORDER BY $tbl_1d_films.Title";
	$result = runQuery($query,'cinemaPremiere()','GET_PREMS');

	if (db_num_rows($result) == 0)
		$SUBS['NONE'] = $MSG[10015];
		else while ($row = db_fetch_row($result))
			{
			$SUBS['CHECK'] = $row[0];
			$SUBS['TITLE'] = htmlEncode($row[1]);
			$SUBS['ACTORS'] = htmlEncode($row[4]);
			$SUBS['GO2'] = $row[3];

			$SUBS['LAST'] = $datata = date ('d ', $row[2]).$MONTHS[intval(date('m',$row[2]))].date(' Y H:i:s', $row[2]);
			$SUBS['PREMS'] .= fileParse('_admin_cinema_premiere_row.htmlt');

			if (($PARAM['Add'] != 1) && ($PARAM['FILM'] == $row[0]))
				{
				$PARAM['FILMS'] = $row[0];
				$PARAM['WEEK'] = $row[3];
				}
			}

	//pokaji zapisanite filmi
	$query = "SELECT $tbl_1d_films.ID, Title
		FROM $tbl_1d_films
		LEFT JOIN $tbl_1d_videodvd
			ON $tbl_1d_videodvd.FilmID = $tbl_1d_films.ID
		WHERE Agenda = 'yes'
			AND $tbl_1d_videodvd.FilmID IS NULL
			AND Title != ''
			AND (($tbl_1d_films.ID = ".dbQuote($PARAM['FILMS']).") OR (tsPremiere = 0) OR (tsPremiere IS NULL))
			";
	$result = runQuery($query,'cinemaPremiere()','GET_FILMS');
	while ($row = db_fetch_row($result))
		if ($PARAM['FILM'] == $row[0])
		$SUBS['FILM'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
		else $SUBS['FILM'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);

	////----[Mrasnika's] Edition 12.10.2002
	// $SUBS['WEEK'] = getWeeks($PARAM['WEEK']);
	// $SUBS['WHEN'] = getWeeks($PARAM['WHEN']);

	//get oldest week
	$query = "SELECT min(tsPremiere)
		FROM $tbl_1d_films
		WHERE tsPremiere != 0";
	$result = runQuery($query,'cinemaPremiere()','GET_OLDEST_WEEK');
	if ($row = db_fetch_row($result))
		$span = week($row[0]);

	//compatibility
	if (!$PARAM['WHEN'])
		//no date applied
		if (!$PARAM['Day1'] || !$PARAM['Month1'] || !$PARAM['Year1'])
			{
			$PARAM['WHEN'] = getNextWeek();
			$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
			$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
			$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
			} else $PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);

		else {	//load date form
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		}
	
	if ($span > $PARAM['WHEN'])
		{	//ima data, no e po-malka ot naj-starata programa
		$SUBS['COMMAND'] = $PARAM['cmd']."&WHERE=".$PARAM['WHERE']."&WHEN=$span&WEEK=$span";
		printPage('_admin_done.htmlt');
		return;
		}

	$Year2 = 1+date ('Y', max($PARAM['WHEN'], time()));
	$Year1 = date ('Y', $span);

	for ($i=$Year1; $i<=$Year2;$i++)
		if ($i==$PARAM['Year1'])
			$SUBS['YEAR1'] .= "<OPTION value=\"$i\" selected>$i";
			else $SUBS['YEAR1'] .= "<OPTION value=\"$i\">$i";
	for ($i=1; $i<=12; $i++)
		if ($i == $PARAM['Month1'])
			$SUBS['MONTH1'] .= "<OPTION value=\"$i\" selected>".$MONTHS[$i];
			else $SUBS['MONTH1'] .= "<OPTION value=\"$i\">".$MONTHS[$i];
	for ($i=1; $i<=31; $i++)
		if ($i == $PARAM['Day1'])
			$SUBS['DAY1'] .= "<OPTION value=\"$i\" selected>".sprintf('%02d',$i);
			else $SUBS['DAY1'] .= "<OPTION value=\"$i\">".sprintf('%02d',$i);

	if (!$SUBS['WEEK'] = $PARAM['WEEK'])
		$SUBS['WEEK'] = $PARAM['WHEN'];
	$SUBS['DISPLAYWEEK'] = displayWeek($SUBS['WEEK']);
	
	////----[Mrasnika's] Edition 12.10.2002
	$SUBS['PREV'] = week($PARAM['WHEN']- 518400) ;
	$SUBS['NEXT'] = week($PARAM['WHEN']+ 1026800) ;

	$SUBS['GO'] = $PARAM['WEEK'];

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	printPage('_admin_cinema_premiere.htmlt');
 	}

// - - - - -
//function videoDistributors()
 function videoDistributors() {
 	global $tbl_1d_distr, $tbl_1d_videodvd;
	global $SUBS, $PARAM, $MSG;
	global $adminID;	//from session

	//dobawyane na distributori
	if ($PARAM['Add'] == 1)
		{
		if ($PARAM['ADD']=='')	//prazni whodni danni
			$SUBS['ERROR'] = $MSG[20075];

		if ($PARAM['id'] == '')
			$query = "SELECT ID FROM $tbl_1d_distr
				WHERE Distributor LIKE ".dbQuote($PARAM['ADD'])."
					AND Type = 'video' ";
			else $query = "SELECT ID FROM $tbl_1d_distr
					WHERE Distributor LIKE ".dbQuote($PARAM['ADD'])."
						AND Type = 'video'
						AND ID != ".dbQuote($PARAM['id']);
		if ($SUBS['ERROR'] == '')
			{	//proweri dali weche ne e waweden
			$result = runQuery($query,'videoDistributors()','CHECK_ADD_VIDEO_DISTR');
			if (db_num_rows($result) > 0)
				$SUBS['ERROR'] = $MSG[20036];
			}

		if ($SUBS['ERROR'] != '')
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {
			if ($PARAM['id'] == '')
				$query = "INSERT INTO $tbl_1d_distr
					(Distributor, Type, Priority, adminID) VALUES
					(".dbQuote($PARAM['ADD']).", 'video', ".intval($PARAM['NO']).", $adminID)";
				else $query = "UPDATE $tbl_1d_distr SET
						Distributor = ".dbQuote($PARAM['ADD']).",
						Priority = ".intval($PARAM['NO'])."
						WHERE ID = ".dbQuote($PARAM['id']);
			$result = runQuery($query,'videoDistributors()','SAVE_VIDEO_DISTR');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20037";
			printPage('_admin_done.htmlt');
			return;
			}
		}

	//iztriwane na razportostraniteli
	if ($PARAM['Delete']==1)
		{
		reset ($PARAM);
		$Distr = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^vd_([0-9]+)$', $k, $R))
				$Distr .= ",$R[1]";
		if ($Distr == '0')
			$SUBS['ERROR'] = $MSG[20008];

		//proweri stawa li za iztriwane
		$query = "SELECT ID
			FROM $tbl_1d_videodvd
			WHERE DistributorID IN ($Distr)";
		$result = runQuery($query,'videoDistributors()','CHECK_DISTR');
		if (($SUBS['ERROR'] == '')&& (db_num_rows($result) != 0))
			$SUBS['ERROR'] = $MSG[20038];

		if ($SUBS['ERROR']=='')
			{
			//iztriwane na razprostranitelya
			$query = "DELETE FROM $tbl_1d_distr
				WHERE ID IN ($Distr)";	//no type, just id
			$result = runQuery($query,'videoDistributors()','DEL_VIDEO_DISTR');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20040";
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse ('_admin_error.htmlt');
		}

	//pokaji razprostranitelite
	$query = "SELECT	$tbl_1d_distr.ID,
			$tbl_1d_distr.Distributor,
			
			($tbl_1d_videodvd.DistributorID IS NULL),
			
			Priority
		FROM $tbl_1d_distr
		LEFT JOIN $tbl_1d_videodvd
			ON $tbl_1d_videodvd.DistributorID = $tbl_1d_distr.ID
				AND $tbl_1d_videodvd.Type = 'video'
		
		WHERE $tbl_1d_distr.Type = 'video'
		
		GROUP BY $tbl_1d_distr.ID
		
		ORDER BY Priority";
	$result = runQuery($query, 'videoDistributors()', 'GET_DISTR');

	if (db_num_rows($result) == 0)
		$SUBS['NONE'] = $MSG[20034];
		else while ($row = db_fetch_row($result))
			{
			$SUBS['DID'] = $row[0];
			$SUBS['TITLE'] = htmlEncode($row[1]);
			$SUBS['ISDEL'] = $row[2];
			$SUBS['NOM'] = $row[3];
			$SUBS['DISTR'] .= fileParse('_admin_distr_video_row.htmlt');
			if (($PARAM['Add'] != 1) && ($row[0] == $PARAM['id']))
				{
				$PARAM['ADD'] = $row[1];
				$PARAM['NO'] = $row[3];
				}
			}

	$SUBS['ID'] = $PARAM['id'];
	$SUBS['ADD'] =  htmlEncode ($PARAM['ADD']);
	$SUBS['NO'] = $PARAM['NO'];

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

 	printPage('_admin_distr_video.htmlt');
	}


 //function videoPremiere()
 function videoPremiere() {
 	global $tbl_1d_films, $tbl_1d_distr, $tbl_1d_videodvd, $tbl_1d_pictures;
 	global $SUBS, $PARAM, $MSG, $MONTHS, $MONTHS2;
 	global $SNIMKA;

	$SUBS['SNIMKA'] = getAdmSetting('TEMPLATES_DIR')."default.jpg";

	//pokaji programata
	if ($PARAM['Show'] == 1)
		{
		////----[Mrasnika's] Edition 11.10.2002
		if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();

		$SUBS['COMMAND'] = $PARAM['cmd']."&WHEN=".$PARAM['WHEN']."&WEEK=".$PARAM['WHEN'];
		printPage('_admin_done.htmlt');
		return;
		}

	//iztrij markitanite zaglawiya
	if ($PARAM['Delete'] == 1)
		{
		reset ($PARAM);
		$Video = '0';
		while (list($k,$v) = each($PARAM))
			if (ereg('^video_([0-9]+)$',$k,$R))
				$Video .= ",$R[1]";

		if ($Video == '0')
			{
			$SUBS['ERROR'] = $MSG[20008];
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			} else {
			//get pictures
			$query = "SELECT ID, URL
				FROM $tbl_1d_pictures
				WHERE RefType = 'video' 
					AND RefID IN ($Video)";
			$result = runQuery($query,'videoPremiere()','GET_VIDEO_PICS');
			$Pics = '0';
			while ($row = db_fetch_row($result))
				{
				if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[1]))
					setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[1], 0, "videoPremiere()", 'DEL_VIDEO_PICS');
				$Pics .= ",$row[0]";
				}
			//erase pic
			$query = "DELETE FROM $tbl_1d_pictures
				WHERE ID IN ($Pics)";
			$result = runQuery($query,'videoPremeire()','DEL_VIDEO_PICS_DB');

			//erase videos
			$query = "DELETE FROM $tbl_1d_videodvd
				WHERE ID IN ($Video)";	//no type, just id
			$result = runQuery($query,'videoPremeire()','DEL_VIDEO_PREMIERES');

			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20044&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK'];
			printPage('_admin_done.htmlt');
			return;
			}
		}

	////----[Mrasnika's] Edition 02.10.2002
	// if ($PARAM['WHEN']=='')
	//	$PARAM['WHEN'] = getNextWeek();
	if ($PARAM['WHEN'])
		{
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		} else if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();

	//pokaji wywedenite video premieri
	$query = "SELECT	$tbl_1d_videodvd.ID,
			FilmID,
			$tbl_1d_films.Title,
			$tbl_1d_videodvd.DistributorID,
			$tbl_1d_distr.Distributor,
			$tbl_1d_videodvd.tsWhen,
			$tbl_1d_videodvd.tsLast
		FROM $tbl_1d_videodvd
		LEFT JOIN $tbl_1d_films
			ON $tbl_1d_films.ID = $tbl_1d_videodvd.FilmID
		LEFT JOIN $tbl_1d_distr
			ON $tbl_1d_distr.ID = $tbl_1d_videodvd.DistributorID
		WHERE	 $tbl_1d_videodvd.tsWhen >= ".week($PARAM['WHEN'])."
			AND $tbl_1d_videodvd.tsWhen <= ".(week($PARAM['WHEN'])+604799)."
			AND $tbl_1d_videodvd.Type = 'video'
		ORDER BY $tbl_1d_videodvd.ID";

	$result = runQuery($query,'videoPremiere()','GET_VIDEO_PREMS');

	while ($row = db_fetch_row($result))
		{
		$SUBS['CHECK'] = $row[0];

		$SUBS['FILMID'] = $row[1];
		$SUBS['TITLE'] = htmlEncode($row[2]);

		$SUBS['DID'] = $row[3];
		$SUBS['DISTRI'] = htmlEncode($row[4]);
		
		$SUBS['PRATI'] = $row[5];
		$SUBS['LAST'] = $datata = date ('d ', $row[6]).$MONTHS[intval(date('m',$row[6]))].date(' Y H:i:s', $row[6]);
		$SUBS['PREMS'] .= fileParse('_admin_video_premiere_row.htmlt');

		if ($PARAM['id'] == $row[0])
			{
			if ($PARAM['Add'] != 1)
				{
				$PARAM['FILMS'] = $row[1];
				$PARAM['DISTR'] = $row[3];
				$PARAM['WEEK'] = $row[5];
				}

			//zarejdane na snimkata
			$query = "SELECT URL
				FROM $tbl_1d_pictures
				WHERE RefType = 'video'
					AND RefID = ".dbQuote($PARAM['id']);
			$res = runQuery($query,'videoPremiere()','GET_VIDEO_PREM_PHOTO');
			if ($r = db_fetch_row($res))
				$SUBS['SNIMKA'] = getAdmSetting ('UPLOAD_DIR').$r[0];
			}
		}

	if ($PARAM['Add'] == 1)
		{	//nowo zaglavie
		$SUBS['ERROR'] ='';
		if (($PARAM['id']=='') || ($SNIMKA != 'none'))	//proweri za snimka
			if (!$INFO = checkPicture($SNIMKA))	//pass INFO as parameter to fixPicture
				$SUBS['ERROR'] = $MSG[20042];

		if ($SUBS['ERROR'] == '')
			{
			if ($PARAM['id']=='')
				$query = "INSERT INTO $tbl_1d_videodvd
					(FilmID,
					Type,
					DistributorID,
					tsWhen,
					tsLast) VALUES
					(".dbQuote($PARAM['FILM']).",
					'video',
					".dbQuote($PARAM['DISTR']).",
					".dbQuote(week($PARAM['WEEK'])).",
					".time().")";
				else $query =
					"UPDATE $tbl_1d_videodvd SET
					FilmID = ".dbQuote($PARAM['FILM']).",
					Type = 'video',
					DistributorID = ".dbQuote($PARAM['DISTR']).",
					tsWhen = ".dbQuote(week($PARAM['WEEK'])).",
					tsLast = ".time()."
					WHERE ID = ".dbQuote($PARAM['id']);
			$result = runQuery($query,'videoPremiere()','SAVE_VIDEO');
			
			if (($SNIMKA != 'none') && ($PARAM['id'] != ''))
				{	//iztrij snimkata
				$query = "SELECT URL, ID
					FROM $tbl_1d_pictures
					WHERE RefType = 'video'
						AND RefID = ".dbQuote($PARAM['id']);
				$result = runQuery($query,'videoPremiere()','GET_VIDEO_PHOTO_URL');
				if ($row = db_fetch_row ($result))
					{
					if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[0]))
						setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[0], 0, "videoPremiere()", 'DEL_VIDEO_PIC');
					//erase pic
					$query = "DELETE FROM $tbl_1d_pictures
						WHERE ID = ".dbQuote($row[1]);
					$result = runQuery($query,'videoPremeire()','DEL_VIDEO_PIC');
					}
				}
			if ($PARAM['id'] == '')
				$PARAM['id'] = mysql_insert_id();

			if ($SNIMKA != 'none')
				fixPicture($SNIMKA, 'video', $PARAM['id'], $INFO);

			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20041&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK'];//."&id=".$PARAM['id'];
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	//pokaji zapisanite filmi
	if ($PARAM['id'] == '')
		$Where = 'FilmID IS NULL';
		else $Where = "$tbl_1d_videodvd.ID = ".dbQuote($PARAM['id']);
	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.Title
		FROM $tbl_1d_films
		LEFT JOIN $tbl_1d_videodvd
			ON $tbl_1d_videodvd.FilmID = $tbl_1d_films.ID
				AND $tbl_1d_videodvd.Type = 'video'
		WHERE	$tbl_1d_films.Title != ''
			AND $Where";
	$result = runQuery($query,'videoPremiere()','GET_FILMS');
	while ($row = db_fetch_row($result))
		{
		if ($PARAM['FILMS'] == $row[0])
			$SUBS['FILM'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
			else $SUBS['FILM'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);
		}

	//pokaji razprostranitelite
	$query = "SELECT	$tbl_1d_distr.ID,
			$tbl_1d_distr.Distributor
		FROM $tbl_1d_distr
		WHERE Type = 'video'
		ORDER BY $tbl_1d_distr.ID";
	$result = runQuery($query,'videoPremiere()','GET_VIDEO_DISTR');
	while ($row = db_fetch_row($result))
		if ($PARAM['DISTR'] == $row[0])
			$SUBS['DISTR'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
			else $SUBS['DISTR'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);

	$SUBS['ID'] = htmlEncode($PARAM['id']);
	
	////----[Mrasnika's] Edition 11.10.2002
	// $SUBS['WHEN'] = getWeeks($PARAM['WHEN']);
	// $SUBS['WEEK'] = getWeeks($PARAM['WEEK']);

	//get oldest week
	$query = "SELECT min(tsWhen)
		FROM $tbl_1d_videodvd
		WHERE Type='video' ";
	$result = runQuery($query,'VideoPremiere()','GET_OLDEST_WEEK');
	if ($row = db_fetch_row($result))
		$span = $row[0];
	
	//compatibility
	if (!$PARAM['WHEN'])
		//no date applied
		if (!$PARAM['Day1'] || !$PARAM['Month1'] || !$PARAM['Year1'])
			{
			$PARAM['WHEN'] = getNextWeek();
			$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
			$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
			$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
			} else $PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);

		else {	//load date form
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		}
	

	if ($span > $PARAM['WHEN'])
		{	//ima data, no e po-malka ot naj-starata videopremiera
		$SUBS['COMMAND'] = $PARAM['cmd']."&WHEN=$span&WEEK=$span&";
		printPage('_admin_done.htmlt');
		return;
		}

	$Year2 = 1+date ('Y', max($PARAM['WHEN'], time()));
	$Year1 = date ('Y', $span);

	for ($i=$Year1; $i<=$Year2;$i++)
		if ($i==$PARAM['Year1'])
			$SUBS['YEAR1'] .= "<OPTION value=\"$i\" selected>$i";
			else $SUBS['YEAR1'] .= "<OPTION value=\"$i\">$i";
	for ($i=1; $i<=12; $i++)
		if ($i == $PARAM['Month1'])
			$SUBS['MONTH1'] .= "<OPTION value=\"$i\" selected>".$MONTHS[$i];
			else $SUBS['MONTH1'] .= "<OPTION value=\"$i\">".$MONTHS[$i];
	for ($i=1; $i<=31; $i++)
		if ($i == $PARAM['Day1'])
			$SUBS['DAY1'] .= "<OPTION value=\"$i\" selected>".sprintf('%02d',$i);
			else $SUBS['DAY1'] .= "<OPTION value=\"$i\">".sprintf('%02d',$i);

	if (!$SUBS['WEEK'] = $PARAM['WEEK'])
		$SUBS['WEEK'] = $PARAM['WHEN'];
	$SUBS['DISPLAYWEEK'] = displayWeek($SUBS['WEEK']);
	
	////----[Mrasnika's] Edition 12.10.2002
	$SUBS['PREV'] = week($PARAM['WHEN']) - 518400;
	$SUBS['NEXT'] = week($PARAM['WHEN']) + 1026800; 
	
	$SUBS['GO'] = $PARAM['WHEN'];

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	printPage('_admin_video_premiere.htmlt');
 	}

// - - - - -
//function dvdDistributors()
 function dvdDistributors() {
 	global $tbl_1d_distr, $tbl_1d_videodvd;
	global $SUBS, $PARAM, $MSG;
	global $adminID;	//from session

	//dobawyane na distributori
	if ($PARAM['Add'] == 1)
		{
		if ($PARAM['ADD']=='')	//prazni whodni danni
			$SUBS['ERROR'] = $MSG[20075];

		if ($PARAM['id'] == '')
			$query = "SELECT ID FROM $tbl_1d_distr
				WHERE Distributor LIKE ".dbQuote($PARAM['ADD'])."
					AND Type = 'dvd' ";
			else $query = "SELECT ID FROM $tbl_1d_distr
					WHERE Distributor LIKE ".dbQuote($PARAM['ADD'])."
						AND Type = 'dvd'
						AND ID != ".dbQuote($PARAM['id']);
		if ($SUBS['ERROR'] == '')
			{	//proweri dali weche ne e waweden
			$result = runQuery($query,'dvdDistributors()','CHECK_ADD_DVD_DISTR');
			if (db_num_rows($result) > 0)
				$SUBS['ERROR'] = $MSG[20036];
			}

		if ($SUBS['ERROR'] != '')
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {
			if ($PARAM['id'] == '')
				$query = "INSERT INTO $tbl_1d_distr
					(Distributor, Type, Priority, adminID) VALUES
					(".dbQuote($PARAM['ADD']).", 'dvd', ".intval($PARAM['NO']).", $adminID)";
				else $query = "UPDATE $tbl_1d_distr SET
						Distributor = ".dbQuote($PARAM['ADD']).",
						Priority = ".intval($PARAM['NO'])."
						WHERE ID = ".dbQuote($PARAM['id']);
			$result = runQuery($query,'videoDistributors()','SAVE_DVD_DISTR');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20037";
			printPage('_admin_done.htmlt');
			return;
			}

		}

	//iztriwane na gradowe
	if ($PARAM['Delete']==1)
		{
		reset ($PARAM);
		$Distr = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^dd_([0-9]+)$', $k, $R))
				$Distr .= ",$R[1]";
		if ($Distr == '0')
			$SUBS['ERROR'] = $MSG[20008];

		//proweri stawa li za iztriwane
		$query = "SELECT ID
			FROM $tbl_1d_videodvd
			WHERE DistributorID IN ($Distr)";
		$result = runQuery($query,'dvdDistributors()','CHECK_DISTR');
		if (($SUBS['ERROR'] == '')&& (db_num_rows($result) != 0))
			$SUBS['ERROR'] = $MSG[20043];

		if ($SUBS['ERROR']=='')
			{
			//iztriwane na distributora
			$query = "DELETE FROM $tbl_1d_distr
				WHERE ID IN ($Distr)";	//no type, just id
			$result = runQuery($query,'dvdDistributors()','DEL_DVD_DISTR');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20040";
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse ('_admin_error.htmlt');
		}

	//pokaji razprostranitelite
	$query = "SELECT	$tbl_1d_distr.ID,
			$tbl_1d_distr.Distributor,
			
			($tbl_1d_videodvd.DistributorID IS NULL),
			
			Priority
		FROM $tbl_1d_distr
		LEFT JOIN $tbl_1d_videodvd
			ON $tbl_1d_videodvd.DistributorID = $tbl_1d_distr.ID
				AND $tbl_1d_videodvd.Type = 'dvd'
		
		WHERE $tbl_1d_distr.Type = 'dvd'
		
		GROUP BY $tbl_1d_distr.ID
		
		ORDER BY Priority";
	$result = runQuery($query, 'dvdDistributors()', 'GET_DISTR');

	if (db_num_rows($result) == 0)
		$SUBS['NONE'] = $MSG[20034];
		else while ($row = db_fetch_row($result))
			{
			$SUBS['DID'] = $row[0];
			$SUBS['TITLE'] = htmlEncode($row[1]);
			$SUBS['ISDEL'] = $row[2];
			$SUBS['NOM'] = $row[3];
			$SUBS['DISTR'] .= fileParse('_admin_distr_dvd_row.htmlt');

			if (($PARAM['Add'] != 1) && ($row[0] == $PARAM['id']))
				{
				$PARAM['ADD'] = $row[1];
				$PARAM['NO'] = $row[3];
				}
			}

	$SUBS['ID'] = $PARAM['id'];
	$SUBS['ADD'] =  htmlEncode ($PARAM['ADD']);
	$SUBS['NO'] = $PARAM['NO'];


	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

 	printPage('_admin_distr_dvd.htmlt');
	}


 //function dvdPremiere()
 function dvdPremiere() {
 	global $tbl_1d_films, $tbl_1d_distr, $tbl_1d_videodvd, $tbl_1d_pictures;
 	global $SUBS, $PARAM, $MSG, $MONTHS, $MONTHS2;
 	global $SNIMKA;

	$SUBS['SNIMKA'] = getAdmSetting('TEMPLATES_DIR')."default.jpg";

	//pokaji programata
	if ($PARAM['Show'] == 1)
		{
		////----[Mrasnika's] Edition 11.10.2002
		if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();
			
		$SUBS['COMMAND'] = $PARAM['cmd']."&WHEN=".$PARAM['WHEN']."&WEEK=".$PARAM['WHEN'];
		printPage('_admin_done.htmlt');
		return;
		}

	//iztrij markitanite zaglawiya
	if ($PARAM['Delete'] == 1)
		{
		reset ($PARAM);
		$DVD = '0';
		while (list($k,$v) = each($PARAM))
			if (ereg('^dvd_([0-9]+)$',$k,$R))
				$DVD .= ",$R[1]";

		if ($DVD == '0')
			{
			$SUBS['ERROR'] = $MSG[20008];
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			} else {
			//get pictures
			$query = "SELECT ID, URL
				FROM $tbl_1d_pictures
				WHERE RefType = 'dvd' 
					AND RefID IN ($DVD)";
			$result = runQuery($query,'dvdPremiere()','GET_DVD_PICS');
			$Pics = '0';
			while ($row = db_fetch_row($result))
				{
				if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[1]))
					setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[1], 0, "dvdPremiere()", 'DEL_DVD_PICS');
				$Pics .= ",$row[0]";
				}
			//erase pic
			$query = "DELETE FROM $tbl_1d_pictures
				WHERE ID IN ($Pics)";
			$result = runQuery($query,'dvdPremeire()','DEL_DVD_PICS_DB');

			//erase videos
			$query = "DELETE FROM $tbl_1d_videodvd
				WHERE ID IN ($DVD)";	//no type, just id
			$result = runQuery($query,'dvdPremeire()','DEL_DVD_PREMIERES');

			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20045&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK'];
			printPage('_admin_done.htmlt');
			return;
			}
		}

	////----[Mrasnika's] Edition 02.10.2002
	// if ($PARAM['WHEN']=='')
	//	$PARAM['WHEN'] = getNextWeek();

	if ($PARAM['WHEN'])
		{
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		} else if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();


	//pokaji wywedenite dvd premieri
	$query = "SELECT	$tbl_1d_videodvd.ID,
			FilmID,
			$tbl_1d_films.Title,
			$tbl_1d_videodvd.DistributorID,
			$tbl_1d_distr.Distributor,
			$tbl_1d_videodvd.tsWhen,
			$tbl_1d_videodvd.tsLast
		FROM $tbl_1d_videodvd
		LEFT JOIN $tbl_1d_films
			ON $tbl_1d_films.ID = $tbl_1d_videodvd.FilmID
		LEFT JOIN $tbl_1d_distr
			ON $tbl_1d_distr.ID = $tbl_1d_videodvd.DistributorID
		WHERE	 $tbl_1d_videodvd.tsWhen >= ".week($PARAM['WHEN'])."
			AND $tbl_1d_videodvd.tsWhen <= (".week($PARAM['WHEN'])."+604799)
			AND $tbl_1d_videodvd.Type = 'dvd'
		ORDER BY $tbl_1d_videodvd.ID";
	$result = runQuery($query,'dvdPremiere()','GET_DVD_PREMS');

	while ($row = db_fetch_row($result))
		{
		$SUBS['CHECK'] = $row[0];

		$SUBS['FILMID'] = $row[1];
		$SUBS['TITLE'] = htmlEncode($row[2]);

		$SUBS['DID'] = $row[3];
		$SUBS['DISTRI'] = htmlEncode($row[4]);
		
		$SUBS['PRATI'] = $row[5];
		$SUBS['LAST'] = $datata = date ('d ', $row[6]).$MONTHS[intval(date('m',$row[6]))].date(' Y H:i:s', $row[6]);
		$SUBS['PREMS'] .= fileParse('_admin_dvd_premiere_row.htmlt');

		if ($PARAM['id'] == $row[0])
			{
			if ($PARAM['Add'] != 1)
				{
				$PARAM['FILMS'] = $row[1];
				$PARAM['DISTR'] = $row[3];
				$PARAM['WEEK'] = $row[5];
				}

			//zarejdane na snimkata
			$query = "SELECT URL
				FROM $tbl_1d_pictures
				WHERE RefType = 'dvd'
					AND RefID = ".dbQuote($PARAM['id']);
			$res = runQuery($query,'dvdPremiere()','GET_DVD_PREM_PHOTO');
			if ($r = db_fetch_row($res))
				$SUBS['SNIMKA'] = getAdmSetting ('UPLOAD_DIR').$r[0];
			}
		}

	if ($PARAM['Add'] == 1)
		{	//nowo zaglavie
		$SUBS['ERROR'] ='';
		if (($PARAM['id']=='') || ($SNIMKA != 'none'))	//proweri za snimka
			if (!$INFO = checkPicture($SNIMKA))	//pass INFO as parameter to fixPicture
				$SUBS['ERROR'] = $MSG[20042];

		if ($SUBS['ERROR'] == '')
			{
			if ($PARAM['id']=='')
				$query = "INSERT INTO $tbl_1d_videodvd
					(FilmID,
					Type,
					DistributorID,
					tsWhen,
					tsLast) VALUES
					(".dbQuote($PARAM['FILM']).",
					'dvd',
					".dbQuote($PARAM['DISTR']).",
					".dbQuote($PARAM['WEEK']).",
					".time().")";
				else $query =
					"UPDATE $tbl_1d_videodvd SET
					FilmID = ".dbQuote($PARAM['FILM']).",
					Type = 'dvd',
					DistributorID = ".dbQuote($PARAM['DISTR']).",
					tsWhen = ".dbQuote($PARAM['WEEK']).",
					tsLast = ".time()."
					WHERE ID = ".dbQuote($PARAM['id']);
			$result = runQuery($query,'dvdPremiere()','SAVE_DVD');
			
			if (($SNIMKA != 'none') && ($PARAM['id'] != ''))
				{	//iztrij snimkata
				$query = "SELECT URL, ID
					FROM $tbl_1d_pictures
					WHERE RefType = 'dvd'
						AND RefID = ".dbQuote($PARAM['id']);
				$result = runQuery($query,'dvdPremiere()','GET_DVD_PHOTO_URL');
				if ($row = db_fetch_row ($result))
					{
					if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[0]))
						setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[0], 0, "dvdPremiere()", 'DEL_DVD_PIC');
					//erase pic
					$query = "DELETE FROM $tbl_1d_pictures
						WHERE ID = ".dbQuote($row[1]);
					$result = runQuery($query,'dvdPremeire()','DEL_DVD_PICS');
					}
				}
			if ($PARAM['id'] == '')
				$PARAM['id'] = mysql_insert_id();

			if ($SNIMKA != 'none')
				fixPicture($SNIMKA, 'dvd', $PARAM['id'], $INFO);

			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20046&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK'];//."&id=".$PARAM['id'];
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	//pokaji zapisanite filmi
	if ($PARAM['id'] == '')
		$Where = 'FilmID IS NULL';
		else $Where = "$tbl_1d_videodvd.ID = ".dbQuote($PARAM['id']);
	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.Title
		FROM $tbl_1d_films
		LEFT JOIN $tbl_1d_videodvd
			ON $tbl_1d_videodvd.FilmID = $tbl_1d_films.ID
				AND $tbl_1d_videodvd.Type = 'dvd'
		WHERE	$tbl_1d_films.Title != ''
			AND $Where";
	$result = runQuery($query,'dvdPremiere()','GET_FILMS');
	while ($row = db_fetch_row($result))
		{
		if ($PARAM['FILMS'] == $row[0])
			$SUBS['FILM'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
			else $SUBS['FILM'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);
		}

	//pokaji razprostranitelite
	$query = "SELECT	$tbl_1d_distr.ID,
			$tbl_1d_distr.Distributor
		FROM $tbl_1d_distr
		WHERE Type = 'dvd'
		ORDER BY $tbl_1d_distr.ID";
	$result = runQuery($query,'dvdPremiere()','GET_DVD_DISTR');
	while ($row = db_fetch_row($result))
		if ($PARAM['DISTR'] == $row[0])
			$SUBS['DISTR'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
			else $SUBS['DISTR'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);

	$SUBS['ID'] = htmlEncode($PARAM['id']);
	
	////----[Mrasnika's] Edition 11.10.2002
	// $SUBS['WEEK'] = getWeeks($PARAM['WEEK']);
	// $SUBS['WHEN'] = getWeeks($PARAM['WHEN']);
	
	//get oldest week
	$query = "SELECT min(tsWhen)
		FROM $tbl_1d_videodvd
		WHERE Type='dvd' ";
	$result = runQuery($query,'dvdPremiere()','GET_OLDEST_WEEK');
	if ($row = db_fetch_row($result))
		$span = $row[0];
	
	//compatibility
	if (!$PARAM['WHEN'])
		//no date applied
		if (!$PARAM['Day1'] || !$PARAM['Month1'] || !$PARAM['Year1'])
			{
			$PARAM['WHEN'] = getNextWeek();
			$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
			$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
			$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
			} else $PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);

		else {	//load date form
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		}

	if ($span > $PARAM['WHEN'])
		{	//ima data, no e po-malka ot naj-starata dvdpremiera
		$SUBS['COMMAND'] = $PARAM['cmd']."&WHEN=$span&WEEK=$span&";
		printPage('_admin_done.htmlt');
		return;
		}

	$Year2 = 1+date ('Y', max($PARAM['WHEN'], time()));
	$Year1 = date ('Y', $span);

	for ($i=$Year1; $i<=$Year2;$i++)
		if ($i==$PARAM['Year1'])
			$SUBS['YEAR1'] .= "<OPTION value=\"$i\" selected>$i";
			else $SUBS['YEAR1'] .= "<OPTION value=\"$i\">$i";
	for ($i=1; $i<=12; $i++)
		if ($i == $PARAM['Month1'])
			$SUBS['MONTH1'] .= "<OPTION value=\"$i\" selected>".$MONTHS[$i];
			else $SUBS['MONTH1'] .= "<OPTION value=\"$i\">".$MONTHS[$i];
	for ($i=1; $i<=31; $i++)
		if ($i == $PARAM['Day1'])
			$SUBS['DAY1'] .= "<OPTION value=\"$i\" selected>".sprintf('%02d',$i);
			else $SUBS['DAY1'] .= "<OPTION value=\"$i\">".sprintf('%02d',$i);

	if (!$SUBS['WEEK'] = $PARAM['WEEK'])
		$SUBS['WEEK'] = $PARAM['WHEN'];
	$SUBS['DISPLAYWEEK'] = displayWeek($SUBS['WEEK']);
	
	////----[Mrasnika's] Edition 12.10.2002
	$SUBS['PREV'] = week($PARAM['WHEN']) - 518400;
	$SUBS['NEXT'] = week($PARAM['WHEN']) + 1026800; 
	
	$SUBS['GO'] = $PARAM['WHEN'];

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}
	printPage('_admin_dvd_premiere.htmlt');
 	}
// - - - - -
//function usaPremiere()
 function usaPremiere() {
 	global $tbl_1d_films;
 	global $SUBS, $PARAM, $MSG, $MONTHS, $MONTHS2;

	if ($PARAM['Delete'] == '1')
		{
		reset ($PARAM);
		$Prem = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^usa_([0-9]+)$', $k, $R))
				$Prem .= ",$R[1]";
		if ($Prem == '0')
			{
			$SUBS['ERROR'] = $MSG[20008];
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			} else {
			$query = "UPDATE $tbl_1d_films
				SET tsUSAPremiere = 0
				WHERE ID IN ($Prem)";
			$result = runQuery($query,'usaPremiere()','DEL_USA_PREM');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20049";
			printPage('_admin_done.htmlt');
			return;
			}
		}

	//pokaji programata
	if ($PARAM['Show'] == 1)
		{
		////----[Mrasnika's] Edition 13.10.2002
		if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();
			
		$SUBS['COMMAND'] = $PARAM['cmd']."&WHEN=".$PARAM['WHEN']."&WEEK=".$PARAM['WHEN'];
		printPage('_admin_done.htmlt');
		return;
		}

	////----[Mrasnika's] Edition 12.10.2002
	// if ($PARAM['WHEN']=='')
	//	$PARAM['WHEN'] = getNextWeek();
		
	if ($PARAM['WHEN'])
		{
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		} else if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();

	//pokaji filmi
	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.OriginalTitle,
			tsLast,
			tsUSAPremiere,
			$tbl_1d_films.Actors
		FROM $tbl_1d_films
		WHERE 	$tbl_1d_films.tsUSAPremiere >= ".$PARAM['WHEN']." AND
			$tbl_1d_films.tsUSAPremiere <= (".$PARAM['WHEN']."+604799)
		ORDER BY $tbl_1d_films.OriginalTitle";
	$result = runQuery($query,'usaPremiere()','GET_PREMS');

	while ($row = db_fetch_row($result))
		{
		$SUBS['CHECK'] = $row[0];
		$SUBS['TITLE'] = htmlEncode($row[1]);
		$SUBS['ACTORS'] = htmlEncode($row[4]);
		$SUBS['KOGA'] = $row[3];

		$SUBS['LAST'] = $datata = date ('d ', $row[2]).$MONTHS[intval(date('m',$row[2]))].date(' Y H:i:s', $row[2]);
		$SUBS['PREMS'] .= fileParse('_admin_usa_premiere_row.htmlt');

		if (($PARAM['Add'] != 1) && ($PARAM['FILM'] == $row[0]))
			{
			$PARAM['FILMS'] = $row[0];
			$PARAM['WEEK'] = $row[3];
			}
		}

	if ($PARAM['Add'] == 1)
		{	//nowo zaglavie
		$SUBS['ERROR'] ='';
		if ($PARAM['FILM'] < 1)
			$SUBS['ERROR'] = $MSG[20032];

		if ($SUBS['ERROR'] == '')
			{
			$query =	"UPDATE $tbl_1d_films SET
					tsUSAPremiere = ".dbQuote($PARAM['WEEK']).",
					tsLast = ".time()."
					WHERE ID = ".dbQuote($PARAM['FILM']);
			$result = runQuery($query,'usaPremiere()','SAVE_USA_PREMIERE');

			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20057&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK'];
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	//pokaji zapisanite filmi
	$query = "SELECT ID, OriginalTitle, tsUSAPremiere
		FROM $tbl_1d_films
		WHERE OriginalTitle != ''
			AND ((ID = ".dbQuote($PARAM['FILM']).") OR (tsUSAPremiere = 0))";
	$result = runQuery($query,'usaPremiere()','GET_FILMS');
	while ($row = db_fetch_row($result))
		if ($PARAM['FILM'] == $row[0])
			{
			$SUBS['FILM'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
			$PARAM['WEEK'] = $row[2];
			$PARAM['WHEN'] = $row[2];
			} else $SUBS['FILM'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);

	////----[Mrasnika's] Edition 13.10.2002
	// $SUBS['WEEK'] = getWeeks($PARAM['WEEK']);
	// $SUBS['WHEN'] = getWeeks($PARAM['WHEN']);

	//get oldest week
	$query = "SELECT min(tsUSAPremiere)
		FROM $tbl_1d_films
		WHERE tsUSAPremiere != 0";
	$result = runQuery($query,'manageAgenda()','GET_OLDEST_WEEK');
	if ($row = db_fetch_row($result))
		$span = week($row[0]);

	//compatibility
	if (!$PARAM['WHEN'])
		//no date applied
		if (!$PARAM['Day1'] || !$PARAM['Month1'] || !$PARAM['Year1'])
			{
			$PARAM['WHEN'] = getNextWeek();
			$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
			$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
			$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
			} else $PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);

		else {	//load date form
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		}
	
	if ($span > $PARAM['WHEN'])
		{	//ima data, no e po-malka ot naj-starata premiera
		$SUBS['COMMAND'] = $PARAM['cmd']."&WHERE=".$PARAM['WHERE']."&WHEN=$span&WEEK=$span";
		printPage('_admin_done.htmlt');
		return;
		}

	$Year2 = 1+date ('Y', max($PARAM['WHEN'], time()));
	$Year1 = date ('Y', $span);

	for ($i=$Year1; $i<=$Year2;$i++)
		if ($i==$PARAM['Year1'])
			$SUBS['YEAR1'] .= "<OPTION value=\"$i\" selected>$i";
			else $SUBS['YEAR1'] .= "<OPTION value=\"$i\">$i";
	for ($i=1; $i<=12; $i++)
		if ($i == $PARAM['Month1'])
			$SUBS['MONTH1'] .= "<OPTION value=\"$i\" selected>".$MONTHS[$i];
			else $SUBS['MONTH1'] .= "<OPTION value=\"$i\">".$MONTHS[$i];
	for ($i=1; $i<=31; $i++)
		if ($i == $PARAM['Day1'])
			$SUBS['DAY1'] .= "<OPTION value=\"$i\" selected>".sprintf('%02d',$i);
			else $SUBS['DAY1'] .= "<OPTION value=\"$i\">".sprintf('%02d',$i);

	if (!$SUBS['WEEK'] = $PARAM['WEEK'])
		$SUBS['WEEK'] = $PARAM['WHEN'];
	$SUBS['DISPLAYWEEK'] = displayWeek($SUBS['WEEK']);
	
	////----[Mrasnika's] Edition 12.10.2002
	$SUBS['PREV'] = week($PARAM['WHEN']- 518400) ;
	$SUBS['NEXT'] = week($PARAM['WHEN']+ 1026800) ;

	$SUBS['GO'] = $PARAM['WEEK'];

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	printPage('_admin_usa_premiere.htmlt');
 	}

//function insertArticle()
 function insertArticle() {
 	global $tbl_1d_article, $tbl_1d_pictures; 
 	global $SUBS, $PARAM, $MSG;
 	global $SNIMKA, $SNIMKA_name;	//file upload
 	global $PHOTOS, $adminID;	//from session

	if ($PARAM['Add']==1)
		{
		$SUBS['ID'] = $PARAM['ID'];	//da ze zapische, ako e podadeno
		$SUBS['ERROR'] = '';

		if ($PARAM['SECTION'] == '0')
			 $SUBS['ERROR'] = $MSG[20070];
			else $SUBS['SECTION'.strToUpper($PARAM['SECTION'])] = ' SELECTED';

		if ($PARAM['TITLE'] != '')	//zaglavie
			$SUBS['TITLE'] = htmlEncode($PARAM['TITLE']);
			else $SUBS['ERROR'] = $MSG[20069];

		if  ($PARAM['INFO'] != '')
			$SUBS['INFO'] = htmlEncode($PARAM['INFO']);
			else if ($SUBS['ERROR']=='') $SUBS['ERROR'] = $MSG[20071];

		if ($SUBS['ERROR'] == '')
			{	//wsichko e ok, zapiswane w DB
			if ($PARAM['ID'] != '')
				$query = "UPDATE $tbl_1d_article SET
					Title = ".dbQuote($PARAM['TITLE']).",
					Caption = ".dbQuote($PARAM['INFO']).",
					tsLast = ".dbQuote(time())."
					WHERE ID=".$PARAM['ID'];
				else $query = "INSERT INTO $tbl_1d_article
				(Title, Caption, tsLast, tsWhen, adminID)
				VALUES
				(".dbQuote($PARAM['TITLE']).",
				".dbQuote($PARAM['INFO']).",
				".dbQuote(time()).", ".dbQuote(getNextWeek()).", $adminID )";
			$result = runQuery($query,'insertArticle()','ADD_TEXT_ARTILE_INFO');
			if ($PARAM['ID'] == '')	//poslednoto id
				{
				$PARAM['ID'] = mysql_insert_id();

				//zaradi snimkite ot PHOTOs
				if (session_is_registered('PHOTOS') && is_array($PHOTOS))
					for ($i = 0; $i<count($PHOTOS[session_id()]); $i++)
						if ($INFO = checkPicture($PHOTOS[session_id()][$i]))
							fixPicture($PHOTOS[session_id()][$i], 'article', $PARAM['ID'], $INFO);
				unset ($PHOTOS[session_id()]);
				}

			//prezarejdane
			if ($PARAM['Photo'] != 1)
				{
				$SUBS['COMMAND'] = $PARAM['cmd']."&err=20018&ID=".$PARAM['ID'];
				printPage('_admin_done.htmlt');
				return;
				}
			} else $SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	if ($PARAM['Photo'] == 1)
		{	//dobawi snimka
		if (!session_is_registered('PHOTOS'))
			{	//izpolzwa se ako nyama oschte registrirano ID
			session_register('PHOTOS');
			$PHOTOS = array();
			}

		$SUBS['ERROR'] = '';
		if ($SNIMKA == 'none')
			$SUBS['ERROR'] = $MSG[20019];

		if (!$INFO = checkPicture($SNIMKA))	//pass INFO as parameter to fixPicture
			$SUBS['ERROR'] = $MSG[20020];
		
		if ($SUBS['ERROR'] != '')
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {	//do tha job
			if ($PARAM['ID'] == '')
				{
				$where = getAdmSetting('TEMPORARY_DIR').session_id().md5($INFO[0]+$INFO[1]).$SNIMKA_name;
				if (@copy ($SNIMKA, $where))
					{
					if (!@in_array($where, $PHOTOS[session_id()]))
						{
						if (!is_array($PHOTOS[session_id()]))
							$PHOTOS[session_id()] = array();
						$PHOTOS[session_id()][] = $where;
						}
					} else setLogAndStatus("Writing", $SNIMKA, 0, "insertArticle()", 'WRITE_SESSION_PICS');
				} else {
				fixPicture($SNIMKA, 'article', $PARAM['ID'], $INFO);
			
				//prezarejdane
				$SUBS['COMMAND'] = $PARAM['cmd']."&err=20022&ID=".$PARAM['ID'];
				printPage('_admin_done.htmlt');
				return;
				}
			}
		}

	if ($PARAM['Delete'] != '')	//iztrij snimka
		if ($PARAM['ID'] != '')
			{
			//get thumbnail
			$query = "SELECT	URL, ID
				FROM $tbl_1d_pictures
				WHERE RefID = ".dbQuote($PARAM['Delete'])."
					AND RefType= 'thumb' ";
			$result = runQuery($query,'insetFilm()','GET_THUMBS');
			if ($row = db_fetch_row($result))
				{
				if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[0]))
					setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[0], 0, "insertArticle()", 'DEL_THUMBS');
	
				//erase thumbnail
				$query = "DELETE FROM $tbl_1d_pictures
					WHERE ID = $row[1]";
				$result = runQuery($query,'insertArticle()','DEL_THUMBS_DB');
				}
			$query = "SELECT	URL, ID
				FROM $tbl_1d_pictures
				WHERE ID = ".dbQuote($PARAM['Delete']);
			$result = runQuery($query,'insertArticle()','GET_PIC');
			if ($row = db_fetch_row($result))
				{
				if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[0]))
					setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[0], 0, "insertArticle()", 'DEL_PICS');
				//erase pic
				$query = "DELETE FROM $tbl_1d_pictures
					WHERE ID = $row[1]";
				$result = runQuery($query,'insertArticle()','DEL_PICS');
				}
			} else {
			if (!@unlink(getAdmSetting('UPLOAD_DIR').$PHOTOS[session_id()][$PARAM['Delete']])) 	//from session
				setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$PHOTOS[session_id()][$PARAM['Delete']],
					0, "insertArticle()", 'DEL_SESSIONS');
			unset ($PHOTOS[session_id()][$PARAM['Delete']]);
			}

	//display
	$SUBS['SECTION'.strToUpper($PARAM['w'])] = ' SELECTED';

	if ($PARAM['Add'] != 1)
		{	//podgotowka za pokazwane
		$query = "SELECT	Title,
				Type,
				Caption
			FROM $tbl_1d_article
			WHERE ID = ".dbQuote($PARAM['ID']);
		$result = runQuery($query,'insertArticle()','GET_TEXT_ARTICLE_INFO');
		if ($row = db_fetch_row($result))
			{
			$SUBS['TITLE'] = htmlEncode($row[0]);
			$SUBS['SECTION'.strToUpper($row[1])] = ' SELECTED';
			$SUBS['INFO'] = htmlEncode($row[2]);
			} else {
			if (($PARAM['ID'] != '') && ($SUBS['ERROR'] == ''))
				{
				$SUBS['ERROR'] = $MSG[20081];
				$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
				}
			$PARAM['ID'] = '';
			}
		}

	//pokaji snimkite
	if ($PARAM['ID'] == '')
		{
		for ($i=0; $i<count($PHOTOS[session_id()]); $i++)
			{
			$SUBS['URL'] = $PHOTOS[session_id()][$i];
			$SUBS['IND'] = $i;
			$SUBS['THUMB'] = " &nbsp; ".$MSG[20024]." ".($i+1);
			$SUBS['SNIMKAS'] .= fileParse('_admin_edit_film_snimka.htmlt');
			}
		} else {
		$query = "SELECT	URL,
				Width,
				Height,
				ID
			FROM $tbl_1d_pictures
			WHERE (RefID LIKE ".dbQuote($PARAM['ID']).")
				AND RefType = 'article' ";
		$result = runQuery($query,'insertArticle()','GET_PICS_ARTICLE_INFO');
		$upload = getAdmSetting('UPLOAD_DIR');
		$SUBS['UPLOAD'] = $upload;
		while ($row = db_fetch_row($result))
			{
			$query = "SELECT	URL,
					Width,
					Height
				FROM $tbl_1d_pictures
				WHERE (RefID = $row[3]) AND RefType = 'thumb' ";
			$res = runQuery($query,'insertArtcile()','GET_ARTICLE_THUMB');
			$thumb = db_fetch_row($res);
			$SUBS['URL'] = $row[0];
			$SUBS['IND'] = $row[3];
			$SUBS['THUMB'] = "<img border=\"0\" width=\"$thumb[1]\" height=\"$thumb[2]\" src=\"$upload$thumb[0]\">";
			$SUBS['SNIMKAS'] .= fileParse('_admin_edit_article_snimka.htmlt');
			}
		}

	////----[Mrasnika's] Edition 13.10.2002
	$SUBS['FILM_NAV'] = fileParse('_admin_edit_article2.htmlt');

	$SUBS['ID'] = $PARAM['ID'];
	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

 	printPage('_admin_edit_article.htmlt');
 	}

//function manageArticles()
 function manageArticles() {
 	global $tbl_1d_article, $tbl_1d_pictures;
 	global $SUBS, $PARAM, $MSG, $MONTHS, $MONTHS2;
 	global $articleString, $articlePage, $articleCount, $articleWhere;	//from session

	if ($PARAM['Delete'] == '1')
		{
		reset ($PARAM);
		$Ar = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^article_([0-9]+)$', $k, $R))
				$Ar .= ",$R[1]";
				
		if ($Ar == '0')
			{
			$SUBS['ERROR'] = $MSG[20008];
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			} else {
			//iztrij kartinki;
			$query = "SELECT	$tbl_1d_pictures.ID,
					$tbl_1d_pictures.URL,
					$tbl_1d_pictures.RefType,
					
					a1.ID AS thumbID,
					a1.URL AS thumbURL

				FROM $tbl_1d_article
				LEFT JOIN $tbl_1d_pictures
					ON (	$tbl_1d_pictures.RefID = $tbl_1d_article.ID
						AND $tbl_1d_pictures.RefType = 'article'
						)
				LEFT JOIN $tbl_1d_pictures a1
					ON a1.RefType = 'thumb'
						AND a1.RefID = $tbl_1d_pictures.ID
				WHERE $tbl_1d_article.ID IN ($Ar)";

			$result = runQuery($query,'manageArticles()','GET_PISTURES');
			$Pics ='0';
			while ($row = db_fetch_row($result))
				{
				//del pic
				if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[1]))
					setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[1], 0, "manageArticles()", 'DEL_ARTICLES_PICS');
				$Pics .= ",0$row[0]";
				//del thumb
				if (!@unlink(getAdmSetting('UPLOAD_DIR').$row[4]))
					setLogAndStatus("Erasing", getAdmSetting('UPLOAD_DIR').$row[4], 0, "manageArticles()", 'DEL_THUMB_PICS');
				$Pics .= ",0$row[3]";
				}

			//iztriwane na kartinkite ot db
			$query = "DELETE FROM $tbl_1d_pictures
				WHERE ID IN ($Pics) ";
			$result = runQuery($query,'manageArticles()','ERASE_PISTURES');

			//iztriwane na samiyat film
			$query = "DELETE FROM $tbl_1d_article
				WHERE ID IN ($Ar) ";
			$result = runQuery($query,'manageArticles()','ERASE_ARTICLES');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20093";
			printPage('_admin_done.htmlt');
			return;
			}
		}

	if ($PARAM['Add'] == 1)
		{	//nowo zaglavie
		$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
		$query =	"UPDATE $tbl_1d_article SET
				Priority = ".intval($PARAM['NO']).",
				tsWhen = ".dbQuote($PARAM['WHEN']).",
				tsLast = ".time()."
				WHERE ID = ".dbQuote($PARAM['id']);
		$result = runQuery($query,'manageArticles()','SAVE_WHEN_ARTICLE');

		$SUBS['COMMAND'] = $PARAM['cmd']."&err=20092&offs=".$PARAM['offs'];
		printPage('_admin_done.htmlt');
		return;
		}

	//SESSION
	if (!session_is_registered('articleString')) session_register('articleString');
	if (!session_is_registered('articlePage'))
		{
		session_register('articlePage');
		$articlePage = getAdmSetting('RESULT_PER_PAGE');
		}
	if (!$articlePage)
		$articlePage = getAdmSetting('RESULT_PER_PAGE');

	//set perpage
	if (($PARAM['SearchPage']) && ($PARAM['SearchPage']>0))
		$articlePage = $PARAM['SearchPage'];

	if (!session_is_registered('articleCount')) session_register('articleCount');
	if (!session_is_registered('articleWhere'))
		{
		session_register('articleWhere');
		$articleWhere = '1';
		}

	if ($PARAM['Show'] == 1)
		{
		$articleString = $PARAM['string'];
		$articleWhere = '1';

		$string = dbQuote("%$articleString%");
		$articleWhere .= " AND (($tbl_1d_article.Title LIKE $string) OR ($tbl_1d_article.Caption LIKE $string)) ";
		}

	//prepare sort
	switch ($PARAM['sort']) {
		case 1 :	$articleSort = ' ASC ';
			$SUBS['SORT'] = 0;
			break;
		case 0 :	$articleSort = ' DESC ';
			$SUBS['SORT'] = 1;
			break;
		default :	$articleSort = ' DESC ';
			$SUBS['SORT'] = 0;
		}

	//prepare order
	if (!$PARAM['orderby'])
		$PARAM['orderby'] = '2';
	switch ($PARAM['orderby']) {
		case 1 :	$articleOrder = "$tbl_1d_article.Title";
			break;
		case 2 :	$articleOrder = "$tbl_1d_article.tsWhen $articleSort, $tbl_1d_article.Priority ";
			$articleSort = ' ASC ';
			break;
		case 3 :	$articleOrder = "$tbl_1d_article.tsLast";
			break;
		default :	$articleOrder = "$tbl_1d_article.ID";	//case 0
		}

	//pokaji statii
	$articleSelect = "SELECT	$tbl_1d_article.ID,
			$tbl_1d_article.Title,
			$tbl_1d_article.tsWhen,
			$tbl_1d_article.tsLast,
			$tbl_1d_article.Priority";

	$articleFrom = "FROM $tbl_1d_article";

	//get search count
	if ((!$articleCount) || ($PARAM['Show'] == 1))
		{
		$query = "SELECT COUNT($tbl_1d_article.ID) $articleFrom WHERE $articleWhere";
		$result = runQuery($query, 'manageArticles()', 'GET_ARTIFLES_COUNT');
		if ($row = db_fetch_row($result))
			$articleCount = $row[0];
			else $articleCount = 0;
		}

	if (!$PARAM['offs'])
		$articleStart = 0;
		else $articleStart = $PARAM['offs'];

	if ($PARAM['offs']>= $articleCount)
		{
		$SUBS['ERROR'] = $MSG[20047];	//out of search limits
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		$articleRows = 0;
		
		////----[Mrasnika's] Edition 16.10.2002
		$no = 1;
		}

	//run query
	// if ($SUBS['ERROR'] == '')
	if ($no != 1)
		{
		$query = "$articleSelect $articleFrom WHERE $articleWhere $articleGroupBy
			ORDER BY $articleOrder $articleSort
			LIMIT $articleStart, $articlePage";
		$result = runQuery($query, 'manageArticles()', 'DO_ARTICLES_SEARCH');
		$articleRows = db_num_rows ($result);
		}

	if ($PARAM['Show'] == 1)
		{
		$SUBS['COMMAND'] = $PARAM['cmd'];
		printPage('_admin_done.htmlt');
		return;
		}

	$SUBS['SORTED'] = (1+$SUBS['SORT'])%2;
	$SUBS['ORDER'] = $PARAM['orderby'];
	$SUBS['PERPAGE'] = $articlePage;
	$SUBS['START'] = $articleStart;

	while ($row = db_fetch_row($result))
		{
		$SUBS['CHECK'] = $row[0];
		$SUBS['ID2'] = sprintf('%04d',$row[0]);
		$SUBS['TITLE'] = htmlEncode($row[1]);
		$SUBS['KOGA'] = $row[2];
		$SUBS['SEDMICA'] = date ('d ', $row[2]).$MONTHS[intval(date('m',$row[2]))].date(' Y', $row[2]);
		$SUBS['NOM'] = $row[4];
		$SUBS['LAST'] = $datata = date ('d ', $row[3]).$MONTHS[intval(date('m',$row[3]))].date(' Y H:i:s', $row[3]);
		$SUBS['ARTICLES'] .= fileParse('_admin_manage_articles_row.htmlt');

		if (($PARAM['Add'] != 1) && ($PARAM['id'] == $row[0]))
			{
			$PARAM['WHEN'] = $row[2];
			$PARAM['NO'] = $row[4];
			$SUBS['ARTICLE'] = $SUBS['TITLE'];
			}
		}

	//navigation
	$SUBS['TOTAL'] = $articleCount;
	$template = fileToString(getAdmSetting('TEMPLATES_DIR').'/_admin_manage_article_navigation.htmlt');
	if ($articleRows != 0)
		{
		$SUBS['PAGE'] = (1+$articleStart) .' - '. ($articleStart + $articleRows);
		} else $SUBS['PAGE'] = '0 - 0';

	if ($articleStart != 0)
		{
		$SUBS['BUTTON'] = $MSG[20060];	//first
		$SUBS['START'] = 0;
		$SUBS['FIRST'] = strParse($template);
		} else $SUBS['FIRST'] = $MSG[20060];

	if ($articleStart != 0)
		{
		$SUBS['BUTTON'] = $MSG[20063];	//previous
		if (($SUBS['START'] = $articleStart - $articlePage) < 0) $SUBS['START'] = 0;
		$SUBS['PREV'] = strParse($template);
		} else $SUBS['PREV'] = $MSG[20063];

	if (($SUBS['START'] = $articleStart + $articlePage) < $articleCount)
		{
		$SUBS['BUTTON'] = $MSG[20062];	//next
		$SUBS['NEXT'] = strParse($template);
		} else $SUBS['NEXT'] = $MSG[20062];

	if ($articleStart < ($SUBS['START'] = $articleCount - $articlePage))
		{
		$SUBS['BUTTON'] = $MSG[20061];	//last
		$SUBS['LAST'] = strParse($template);
		} else $SUBS['LAST'] = $MSG[20061];
	$SUBS['START'] = $articleStart;

	//nameri naj-starata statiya
	$query = "SELECT MIN(tsWhen) FROM $tbl_1d_article";
	$result = runQuery($query,'manageArticles()','GET_OLDEST_WEEK');
	if ($row = db_fetch_row($result))
		$span = $row[0];

	//compatibility
	if (!$PARAM['WHEN'])
		//no date applied
		if (!$PARAM['Day1'] || !$PARAM['Month1'] || !$PARAM['Year1'])
			{
			$PARAM['WHEN'] = getNextWeek();
			$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
			$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
			$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
			} else $PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);

		else {	//load date form
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		}
	
	if ($span > $PARAM['WHEN'])
		{	//ima data, no e po-malka ot naj-starata programa
		$SUBS['COMMAND'] = $PARAM['cmd']."&WHERE=".$PARAM['WHERE']."&WHEN=$span&WEEK=$span&PLACES=".$PARAM['PLACES'];
		printPage('_admin_done.htmlt');
		return;
		}

	$Year2 = 1+date ('Y', max($PARAM['WHEN'], time()));
	$Year1 = date ('Y', $span);

	for ($i=$Year1; $i<=$Year2;$i++)
		if ($i==$PARAM['Year1'])
			$SUBS['YEAR1'] .= "<OPTION value=\"$i\" selected>$i";
			else $SUBS['YEAR1'] .= "<OPTION value=\"$i\">$i";
	for ($i=1; $i<=12; $i++)
		if ($i == $PARAM['Month1'])
			$SUBS['MONTH1'] .= "<OPTION value=\"$i\" selected>".$MONTHS[$i];
			else $SUBS['MONTH1'] .= "<OPTION value=\"$i\">".$MONTHS[$i];
	for ($i=1; $i<=31; $i++)
		if ($i == $PARAM['Day1'])
			$SUBS['DAY1'] .= "<OPTION value=\"$i\" selected>".sprintf('%02d',$i);
			else $SUBS['DAY1'] .= "<OPTION value=\"$i\">".sprintf('%02d',$i);

	if (!$SUBS['WEEK'] = $PARAM['WEEK'])
		$SUBS['WEEK'] = $PARAM['WHEN'];
	$SUBS['DISPLAYWEEK'] = displayWeek($SUBS['WEEK']);

	$SUBS['GO'] = $PARAM['WEEK'];
	$SUBS['ID'] = $PARAM['id'];
	$SUBS['NO'] = htmlEncode($PARAM['NO']);
	
	$SUBS['STRING'] = htmlEncode($articleString);

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	printPage('_admin_manage_articles.htmlt');
 	}

// - - - - - 
 //function manageCharts()
 function manageCharts() {
 	global $tbl_1d_charts, $tbl_1d_kino_charts, $tbl_1d_videodvd_charts;
	global $SUBS, $PARAM, $MSG;

	//dobawyane na gradowe
	if ($PARAM['Add'] == 1)
		{
		 if ($PARAM['ADDCHART']=='')	//prazni whodni danni
			$SUBS['ERROR'] = $MSG[20075];

		if (($SUBS['ERROR']=='') && (!$PARAM['NO']))	//prazni pozicii
			$SUBS['ERROR'] = $MSG[20012];

		if (($SUBS['ERROR']=='') && (!is_numeric($PARAM['NO'])))	//prazni pozicii
			$SUBS['ERROR'] = $MSG[20039];

		//proweri dali weche ne e wawedena
		if ($PARAM['id'] != '')
			$query = "SELECT ID FROM $tbl_1d_charts
				WHERE Title LIKE ".dbQuote($PARAM['ADDCHART'])."
					AND ID != ".dbQuote($PARAM['id']);
			else $query = "SELECT ID FROM $tbl_1d_charts
				WHERE Title LIKE ".dbQuote($PARAM['ADDCHART']);

		if ($SUBS['ERROR'] == '')
			{
			$result = runQuery($query,'manageCharts()','CHECK_ADD_CHART');
			if (db_num_rows($result) != 0)	//weche e wyweden
				$SUBS['ERROR'] = $MSG[20072];
			}
		
		if ($SUBS['ERROR'] != '')
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {
			if ($PARAM['id'] == '')
				$query = "INSERT INTO $tbl_1d_charts
					(Type, Title, Length) VALUES
					(".dbQuote($PARAM['TYPE']).", ".dbQuote($PARAM['ADDCHART']).", ".intval($PARAM['NO']).")";
				else $query =
					"UPDATE $tbl_1d_charts SET
						Type = ".dbQuote($PARAM['TYPE']).", 
						Title = ".dbQuote($PARAM['ADDCHART']).", 
						Length = ".intval($PARAM['NO'])."
					WHERE ID = ".dbQuote($PARAM['id']);

			$result = runQuery($query,'manageCharts()','ADD_CHART');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20073";
			printPage('_admin_done.htmlt');
			return;
			}
		}

	//iztriwane na gradowe
	if ($PARAM['Delete']==1)
		{
		reset ($PARAM);
		$Charts = '0';
		while (list($k,$v)=each($PARAM))
			if (ereg('^chart_([0-9]+)$', $k, $R))
				$Charts .= ",$R[1]";
		if ($Charts == '0')
			$SUBS['ERROR'] = $MSG[20008];

		//proweri stawa li za iztriwane
		$query = "SELECT	$tbl_1d_kino_charts.ID,
				$tbl_1d_videodvd_charts.ID
			FROM $tbl_1d_kino_charts, $tbl_1d_videodvd_charts
			WHERE ($tbl_1d_kino_charts.ChartID IN ($Charts))
					OR ($tbl_1d_videodvd_charts.ChartID IN ($Charts))";
		$result = runQuery($query,'manageCharts()','CHECK_CHARTS');

		if (($SUBS['ERROR'] == '')&& (db_num_rows($result) != 0))
			$SUBS['ERROR'] = $MSG[20095];

		if ($SUBS['ERROR']=='')
			{
			//iztriwane na grada
			$query = "DELETE FROM $tbl_1d_charts
				WHERE ID IN ($Charts)";
			$result = runQuery($query,'manageCharts()','DEL_CHARTS');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20096";
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse ('_admin_error.htmlt');
		}

	//pokaji klasacii
	$query = "SELECT	$tbl_1d_charts.ID,
			$tbl_1d_charts.Title,
			($tbl_1d_kino_charts.ChartID IS NULL) AND ($tbl_1d_videodvd_charts.ChartID IS NULL),
			$tbl_1d_charts.Length,
			$tbl_1d_charts.Type
		FROM $tbl_1d_charts
		LEFT JOIN $tbl_1d_kino_charts
			ON $tbl_1d_kino_charts.ChartID = $tbl_1d_charts.ID
		LEFT JOIN $tbl_1d_videodvd_charts
			ON $tbl_1d_videodvd_charts.ChartID = $tbl_1d_charts.ID
		
		GROUP BY $tbl_1d_charts.ID ";
	$result = runQuery($query, 'manageCharts()', 'GET_CHARTS');

	if (db_num_rows($result) == 0)
		$SUBS['NONE'] = $MSG[20094];
		else while ($row = db_fetch_row($result))
			{
			$SUBS['CHARTID'] = $row[0];
			$SUBS['CHART'] = htmlEncode($row[1]);
			$SUBS['ISDEL'] = $row[2];
			$SUBS['NOM'] = $row[3];
			if ('kino' == $row[4])
				$SUBS['TIP'] = $MSG[20074];
				else $SUBS['TIP'] = $MSG[20080];
			$SUBS['CHARTS'] .= fileParse('_admin_manage_charts_row.htmlt');
			if (($PARAM['Add'] != 1) && ($row[0] == $PARAM['id']))
				{
				$PARAM['ADDCHART'] = $row[1];
				$PARAM['NO'] = $row[3];
				$PARAM['TYPE'] = $row[4];
				}
			}

	//display
	$SUBS['ID'] = $PARAM['id'];
	$SUBS['ADDCHART'] = htmlEncode($PARAM['ADDCHART']);
	$SUBS['NO'] = $PARAM['NO'];
	$SUBS['TYPE'.strToUpper($PARAM['TYPE'])] = ' SELECTED ';

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

 	printPage('_admin_manage_charts.htmlt');
	}

//function setCharts()
 function setCharts() {
 	global $SUBS, $PARAM, $MSG;
 	global $tbl_1d_charts, $tbl_1d_kino_charts, $tbl_1d_videodvd_charts, $tbl_1d_films, $tbl_1d_videodvd;
 	global $MONTHS, $MONTHS2;

	// check chart length
	$query = "SELECT Length, Type
		FROM $tbl_1d_charts
		ORDER BY ID = ".dbQuote($PARAM['PLACES'])." DESC ";
	$result = runQuery($query,'setCharts()','GET_CHART_LENGTH_AND_TYPE');
	if ($row = db_fetch_row($result))
		{
		$max = $row[0];
		$type = $row[1];
		} else {
		adminMenu();
		return;
		}

	//iztriwane
	if ($PARAM['Delete'] == 1)
		{
		reset ($PARAM);
		$Films = '0';
		while (list($k,$v) = each($PARAM))
			if (ereg('^c_([0-9]+)$',$k,$R))
				$Films .= ",$R[1]";

		//get chart type
		$query = "SELECT Type
			FROM $tbl_1d_charts
			WHERE ID = ".dbQuote($PARAM['PLACES']);
		$result = runQuery($query,'setCharts()','GET_CHART_TYPE2');
		if ($row = db_fetch_row($result))
			$type = $row[0];
			else $SUBS['ERROR'] = $MSG[20008];

		switch ($type) {
			case 'videodvd' :
				$query = "DELETE FROM $tbl_1d_videodvd_charts WHERE ID IN ($Films)";
				break;
			case 'kino' :
				$query = "DELETE FROM $tbl_1d_kino_charts WHERE ID IN ($Films)";
				break;
			}

		if ($Films == '0')
			$SUBS['ERROR'] = $MSG[20008];

		if ($SUBS['ERROR'])			
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			else {
			runQuery($query,'setCharts()','DEL_CHART_RECORDS');
			
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20030&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK']."&WHERE=".$PARAM['WHERE']."&PLACES=".$PARAM['PLACES'];
			printPage('_admin_done.htmlt');
			return;
			}
		}

	//add chart record
	if ($PARAM['Add'] == 1)
		{
		$SUBS['ERROR'] ='';

		if ($PARAM['NO'] == '')	//poziciya w klasaciyata
			$SUBS['ERROR'] = $MSG[20097];
		
		if (($SUBS['ERROR'] =='' ) && (!is_numeric($PARAM['NO'])))
			$SUBS['ERROR'] = $MSG[20099];	//poziciyata dali e cefira
		
		if (($SUBS['ERROR'] =='' ) && ($PARAM['NO'] > $max))
			$SUBS['ERROR'] = $MSG[20099];	//poziciyata dali e po-golyama ot poziciite w klasaciyata
		if (($SUBS['ERROR'] =='' ) && ($PARAM['NO'] < 0))
			$SUBS['ERROR'] = $MSG[20099];	//poziciyata dali e po-malka ot nula

		//tipa na filma
		switch ($PARAM['TYPE']) {
			case 'list' :	//list
				if (($SUBS['ERROR']=='') && ($PARAM['FILMS']==0))
					$SUBS['ERROR'] = $MSG[20026];
				$film = $PARAM['FILMS'];
				break;
			case 'raw' :	//raw
				if (($SUBS['ERROR']=='') && ($PARAM['FILM']==''))
					$SUBS['ERROR'] = $MSG[20027];
				$film = $PARAM['FILM'];
				break;
			default :	if ($SUBS['ERROR'] == '') $SUBS['ERROR'] = $MSG[20028];
			}

		switch ($type) {
			case 'kino' :
				if (($SUBS['ERROR'] =='' ) && ($PARAM['WEEKS'] == ''))
					$SUBS['ERROR'] = $MSG[20098];	//sedmici w klasaciyata
				if (($SUBS['ERROR'] =='' ) && (!is_numeric($PARAM['WEEKS'])))
					$SUBS['ERROR'] = $MSG[20100];	//sedmicite dali sa cefira

				if (($SUBS['ERROR'] =='' ) && ($PARAM['SCREENS'] == ''))
					$SUBS['ERROR'] = $MSG[20101];	//ekrani na klasaciyata
				if (($SUBS['ERROR'] =='' ) && (!is_numeric($PARAM['SCREENS'])))
					$SUBS['ERROR'] = $MSG[20102];	//ekranite dali sa cefira
			
				if (($SUBS['ERROR'] =='' ) && ($PARAM['BO'] == ''))
					$SUBS['ERROR'] = $MSG[20103];	//sedmichen prihod na klasaciyata
				/*if (($SUBS['ERROR'] =='' ) && (!is_numeric($PARAM['BO'])))
					$SUBS['ERROR'] = $MSG[20104];	//sedmichen prihod dali sa cefira*/
			
				if (($SUBS['ERROR'] =='' ) && ($PARAM['CBO'] == ''))
					$SUBS['ERROR'] = $MSG[20105];	//obscht prihod na klasaciyata
				/*if (($SUBS['ERROR'] =='' ) && (!is_numeric($PARAM['CBO'])))
					$SUBS['ERROR'] = $MSG[20106];	//obscht prihod dali sa cefira*/
				break;

			case 'videodvd' :
				if (($SUBS['ERROR'] =='' ) && ($PARAM['WEEKS'] == ''))
					$SUBS['ERROR'] = $MSG[20098];	//sedmici w klasaciyata
				if (($SUBS['ERROR'] =='' ) && (!is_numeric($PARAM['WEEKS'])))
					$SUBS['ERROR'] = $MSG[20100];	//sedmicite dali sa cefira
				break;
			}

		if ($SUBS['ERROR'] == '')
			{
			if ($PARAM['id']=='')
				switch ($type) {
					case 'videodvd':
						$query = "INSERT INTO $tbl_1d_videodvd_charts (
								ChartID,
								No,
								Type,
								Film,
								Weeks,
								tsWhen
								) VALUES (
								".dbQuote($PARAM['PLACES']).",
								".dbQuote($PARAM['NO']).",
								".dbQuote($PARAM['TYPE']).",
								".dbQuote($film).",
								".dbQuote($PARAM['WEEKS']).",
								".dbQuote($PARAM['WEEK'])." )";
						break;

					case 'kino' :
						$query = "INSERT INTO $tbl_1d_kino_charts (
								ChartID,
								No,
								Type,
								Film,
								BoxOffice,
								cumBoxOffice,
								Weeks,
								Screens,
								tsWhen
								) VALUES (
								".dbQuote($PARAM['PLACES']).",
								".dbQuote($PARAM['NO']).",
								".dbQuote($PARAM['TYPE']).",
								".dbQuote($film).",
								".dbQuote($PARAM['BO']).",
								".dbQuote($PARAM['CBO']).",
								".dbQuote($PARAM['WEEKS']).",
								".dbQuote($PARAM['SCREENS']).",
								".dbQuote($PARAM['WEEK'])." )";
						break;
					}
				else switch ($type) {
					case 'videodvd' :
						$query = "UPDATE $tbl_1d_videodvd_charts SET
								ChartID = ".dbQuote($PARAM['PLACES']).",
								No = ".dbQuote($PARAM['NO']).",
								Type = ".dbQuote($PARAM['TYPE']).",
								Film = ".dbQuote($film).",
								Weeks = ".dbQuote($PARAM['WEEKS']).",
								tsWhen = ".dbQuote($PARAM['WEEK'])." 
							WHERE ID = " . dbQuote($PARAM['id']);
						break;

					case 'kino' :
						$query = "UPDATE $tbl_1d_kino_charts SET
								ChartID = ".dbQuote($PARAM['PLACES']).",
								No = ".dbQuote($PARAM['NO']).",
								Type = ".dbQuote($PARAM['TYPE']).",
								Film = ".dbQuote($film).",
								BoxOffice = ".dbQuote($PARAM['BO']).",
								cumBoxOffice = ".dbQuote($PARAM['CBO']).",
								Weeks = ".dbQuote($PARAM['WEEKS']).",
								Screens = ".dbQuote($PARAM['SCREENS']).",
								tsWhen = ".dbQuote($PARAM['WEEK'])." 
							WHERE ID = " . dbQuote($PARAM['id']);
						break;
					}
			$result = runQuery($query,'setCharts()','SAVE_CHART');
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20107&PLACES=".$PARAM['PLACES']."&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK'];
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	////----[Mrasnika's] Edition 12.10.2002
	if ($PARAM['WHEN'])
		{
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		} else if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();
			 
	$SUBS['PREV'] = week($PARAM['WHEN']) - 518400;
	$SUBS['NEXT'] = week($PARAM['WHEN']) + 1026800; 

	//show charts records
	switch ($type) {
		case 'kino' :
			$query = "SELECT	$tbl_1d_kino_charts.ID,
				ChartID,
				No,
				Type,
				Film,
				BoxOffice,
				cumBoxOffice,
				Weeks,
				Screens,
				tsWhen,
				
				$tbl_1d_films.Title,
				$tbl_1d_films.OriginalTitle

				FROM $tbl_1d_kino_charts
				LEFT JOIN $tbl_1d_films
					ON $tbl_1d_kino_charts.Type = 'list'
						AND $tbl_1d_films.ID = $tbl_1d_kino_charts.Film
				WHERE $tbl_1d_kino_charts.ChartID = ".dbQuote($PARAM['PLACES'])."
					AND $tbl_1d_kino_charts.tsWhen >= ".week($PARAM['WHEN'])."
					AND $tbl_1d_kino_charts.tsWhen <= (".week($PARAM['WHEN'])."+604799)
				ORDER BY $tbl_1d_kino_charts.No,
					$tbl_1d_kino_charts.BoxOffice";
			break;

		case 'videodvd' :
			$query = "SELECT	$tbl_1d_videodvd_charts.ID,
				ChartID,
				No,
				Type,
				Film,
				Weeks,
				Weeks,
				Weeks,
				Weeks,
				tsWhen,
				
				$tbl_1d_films.Title,
				$tbl_1d_films.OriginalTitle

				FROM $tbl_1d_videodvd_charts
				LEFT JOIN $tbl_1d_films
					ON $tbl_1d_videodvd_charts.Type = 'list'
						AND $tbl_1d_films.ID = $tbl_1d_videodvd_charts.Film
				WHERE $tbl_1d_videodvd_charts.ChartID = ".dbQuote($PARAM['PLACES'])."
					AND $tbl_1d_videodvd_charts.tsWhen >= ".week($PARAM['WHEN'])."
					AND $tbl_1d_videodvd_charts.tsWhen <= (".week($PARAM['WHEN'])."+604799)
				ORDER BY $tbl_1d_videodvd_charts.No ";
			break;
		}

	$result = runQuery($query,'setCharts()','GET_CHART_RECORDS');
	while ($row = db_fetch_row($result))
		{
		$SUBS['CHECK'] = $row[0];
		$SUBS['CHARTID'] = $row[1];
		$SUBS['NO2'] = sprintf("%02d",$row[2]);

		if ($row[3] == 'list')
			{
			if ($row[10])
				$SUBS['TITLE'] = htmlEncode($row[10]);
				else $SUBS['TITLE'] = htmlEncode($row[11]);
			$SUBS['MOVIE'] = $SUBS['ACTION']."?cmd=insertfilm&ID=$row[4]";
			} else {
			$SUBS['TITLE'] = htmlEncode($row[4]);
			$SUBS['MOVIE'] = "javascript:alert('$MSG[20031]')";
			}
		$SUBS['PRATI'] = $row[9];
		
		switch ($type) {
			case 'kino' :
				$SUBS['BO2'] = $row[5];
				$SUBS['CBO2'] = $row[6];
				$SUBS['SCREENS2'] = $row[8];
				$SUBS['WEEKS2'] = $row[7];

				$SUBS['SHOWCHARTS'] .= fileParse('_admin_charts_row.htmlt');
				break;

			case 'videodvd' :
				$SUBS['WEEKS2'] = $row[7];

				$SUBS['SHOWCHARTS'] .= fileParse('_admin_charts_row2.htmlt');
				break;
			}
		
		if (($PARAM['Add'] != 1) && ($PARAM['id'] == $row[0]))
			{	//load form
			$PARAM['PLACES'] = $row[1];
			$PARAM['TYPE'] = $row[3];
			if ($row[3]!='list')
				$PARAM['FILM'] = $row[4];
				else $PARAM['FILMS'] = htmlEncode($row[4]);

			$PARAM['WEEK'] = $row[9];
			$PARAM['NO'] = $row[2];

			switch ($type) {
				default :	//kino
				$PARAM['BO'] = $row[5];
				$PARAM['CBO'] = $row[6];
				$PARAM['WEEKS'] = $row[7];
				$PARAM['SCREENS'] = $row[8];
				}
			}
		}

 	//get charts
	$SUBS['CHARTS'] = $PARAM['PLACES'];

 	$query = "SELECT ID, Title
 		FROM $tbl_1d_charts";
 	$result = runQuery($query,'setCharts()','GET_CHARTS');
 	while ($row = db_fetch_row($result))
 		{
 		////----[Mrasnika's] Edition 12.10.2002
 		// if ($row[0] == $PARAM['chartid'])
 		//	$SUBS['CHARTS'] .= "<OPTION value=\"$row[0]\" SELECTED>".htmlEncode($row[1]);
 		//	else $SUBS['CHARTS'] .= "<OPTION value=\"$row[0]\">".htmlEncode($row[1]);

 		if (!$s1)	{	//store default chart
 			$s1 = $row[0];
 			$s2 = $row[1];
 			}
 		
 		if ($row[0] == $PARAM['PLACES'])
 			$SUBS['CHARTTITLE'] = htmlEncode($row[1]);
 		if ($row[0] == $PARAM['PLACES'])
 			$SUBS['PLACES'] .= "<OPTION value=\"$row[0]\" SELECTED>".htmlEncode($row[1]);
 			else $SUBS['PLACES'] .= "<OPTION value=\"$row[0]\">".htmlEncode($row[1]);
 		}

	if (!$SUBS['CHARTTITLE'])
		{	//no default chart
		$SUBS['CHARTS'] = $s1;
 		$SUBS['CHARTTITLE'] = htmlEncode($s2);
		}

	//get oldest week
	switch ($type) {
		default :	//kino
			$query = "SELECT	min($tbl_1d_kino_charts.tsWhen)
				FROM	$tbl_1d_kino_charts
				GROUP BY $tbl_1d_kino_charts.ChartID
				ORDER BY $tbl_1d_kino_charts.ChartID=".dbQuote($PARAM['PLACES'])." DESC";
			$result = runQuery($query,'setCharts()','GET_OLDEST_WEEK_KINO');
			if ($row = db_fetch_row($result))
				$span = $row[0];
			break;

		case 'videodvd' :	//videodvd
			$query = "SELECT	min($tbl_1d_videodvd_charts.tsWhen)
				FROM	$tbl_1d_videodvd_charts";
			$result = runQuery($query,'setCharts()','GET_OLDEST_WEEK_VIDEO');
			if ($row = db_fetch_row($result))
				$span = $row[0];
			break;
		}
	if (!$span) $span = getNextWeek();

	////----[Mrasnika's] Edition 12.10.2002
	// $SUBS['WEEK'] = getWeeks($PARAM['WEEK']);
	// $SUBS['WHEN'] = getWeeks($PARAM['WHEN']);

	//compatibility
	if (!$PARAM['WHEN'])
		//no date applied
		if (!$PARAM['Day1'] || !$PARAM['Month1'] || !$PARAM['Year1'])
			{
			$PARAM['WHEN'] = getNextWeek();
			$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
			$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
			$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
			} else $PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);

		else {	//load date form
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		}

	if ($span > $PARAM['WHEN'])
		{	//ima data, no e po-malka ot naj-starata klasaciya
		$SUBS['COMMAND'] = $PARAM['cmd']."&WHEN=$span&PLACES=".$PARAM['PLACES'];
		printPage('_admin_done.htmlt');
		return;
		}

	$Year2 = 1+date ('Y', max($PARAM['WHEN'], time()));
	$Year1 = date ('Y', $span);

	for ($i=$Year1; $i<=$Year2;$i++)
		if ($i==$PARAM['Year1'])
			$SUBS['YEAR1'] .= "<OPTION value=\"$i\" selected>$i";
			else $SUBS['YEAR1'] .= "<OPTION value=\"$i\">$i";
	for ($i=1; $i<=12; $i++)
		if ($i == $PARAM['Month1'])
			$SUBS['MONTH1'] .= "<OPTION value=\"$i\" selected>".$MONTHS[$i];
			else $SUBS['MONTH1'] .= "<OPTION value=\"$i\">".$MONTHS[$i];
	for ($i=1; $i<=31; $i++)
		if ($i == $PARAM['Day1'])
			$SUBS['DAY1'] .= "<OPTION value=\"$i\" selected>".sprintf('%02d',$i);
			else $SUBS['DAY1'] .= "<OPTION value=\"$i\">".sprintf('%02d',$i);

	$SUBS['WEEK'] = $PARAM['WHEN'];
	$SUBS['DISPLAYWEEK'] = displayWeek($SUBS['WEEK']);

	//get films
	switch ($type) {
		case 'kino' :
			$query = "SELECT	$tbl_1d_films.ID,
				Title,
				OriginalTitle
			FROM $tbl_1d_films
			LEFT JOIN $tbl_1d_videodvd
				ON $tbl_1d_videodvd.FilmID = $tbl_1d_films.ID
			WHERE	$tbl_1d_videodvd.ID IS NULL
			GROUP BY $tbl_1d_films.ID
			ORDER BY OriginalTitle !='' DESC, Title='' DESC";
			break;

		case 'videodvd' :
			$query = "SELECT	$tbl_1d_films.ID, Title
			FROM $tbl_1d_films
			LEFT JOIN $tbl_1d_videodvd
				ON $tbl_1d_videodvd.FilmID = $tbl_1d_films.ID
			WHERE	$tbl_1d_videodvd.ID IS NOT NULL
			GROUP BY $tbl_1d_films.ID
			ORDER BY Title DESC";
			break;
		}
	$result = runQuery($query,'setCharts()','GET_FILMS');
	while ($row = db_fetch_row($result))
		{
		//fix titles
		if (!$row[1])$row[1] = $row[2];

		if ($PARAM['FILMS'] == $row[0])
			////----[Mrasnika's] Edition 12.10.2002
			// $SUBS['FILM'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode("$row[1] $row[2] ");
			// else $SUBS['FILM'] .= "\n<option value=\"$row[0]\">".htmlEncode("$row[1] $row[2] ");
			 $SUBS['FILM'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
			 else $SUBS['FILM'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);
		}

	// display
	$SUBS['TYPE'.strToUpper($PARAM['TYPE'])] = ' checked ';
	$SUBS['FILM2'] = htmlEncode($PARAM['FILM']);
	$SUBS['NO'] = htmlEncode($PARAM['NO']);

	switch ($type) {
		default:	//kino
			$SUBS['WEEKS'] = htmlEncode($PARAM['WEEKS']);
			$SUBS['SCREENS'] = htmlEncode($PARAM['SCREENS']);
			$SUBS['BO'] = htmlEncode($PARAM['BO']);
			$SUBS['CBO'] = htmlEncode($PARAM['CBO']);

			$SUBS['DISPLAYCHART'] = fileParse("_admin_charts_kino.htmlt");
			break;

		case 'videodvd':	//video & dvd
			$SUBS['WEEKS'] = htmlEncode($PARAM['WEEKS']);
			
			$SUBS['DISPLAYCHART'] = fileParse("_admin_charts_videodvd.htmlt");
			break;
		}
	
	$SUBS['ID'] = htmlEncode($PARAM['id']);
	
	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}
 	
 	printPage('_admin_charts.htmlt');
 	}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
//compatibility
($SUBS['ACTION'] = $_SERVER['SCRIPT_NAME'])
	|| ($SUBS['ACTION'] = '/admin/admin.php');
if (init())	{
	if ($HTTP_POST_VARS['Login'])
		{
		if (!logAdmin())
			{
			$PARAM['cmd'] = 'login';	//login title
			$SUBS['ERROR'] = $MSG[20001];	//invalid password
			$SUBS['LOGIN_ERROR'] = fileParse('_admin_error.htmlt');
			printPage ('_admin_login.htmlt');

			} else adminMenu();	//default;
		}elseif	(!adminLogged())
				{
				if (($PARAM['cmd'] != '') && ($PARAM['cmd'] != 'logout'))
					{
					$SUBS['ERROR'] = $MSG[20002];	//access denies
					$SUBS['LOGIN_ERROR'] = fileParse('_admin_error.htmlt');
					}
				$PARAM['cmd'] = 'login';	//login title
				printPage ('_admin_login.htmlt');
				} else switch ($PARAM['cmd']) {

					case 'charts' :
						manageCharts();
						break;

					case 'manage_chart' :
						setCharts();
						break;

					
					case 'cinema_new' :
						cinemaPremiere();
						break;

					case 'agenda' :
						manageAgenda();
						break;

					case 'city' :
						manageCity();
						break;

					case 'cinema' :
						manageCinema();
						break;

					case 'insertfilm' :
						insertFilm();
						break;

					case 'film' :
						browseFilms();
						break;

					case 'videodistr' :
						videoDistributors();
						break;

					case 'video_new' :
						videoPremiere();
						break;


					case 'dvddistr' :
						dvdDistributors();
						break;

					case 'dvd_new' :
						dvdPremiere();
						break;


					case 'usa_new' :
						usaPremiere();
						break;
						
					case 'add_article' :
						insertArticle();
						break;
						
					case 'articles' :
						manageArticles();
						break;


					case 'logout' :
						adminLogout();
						break;

					case 'backup' :
						setBackup();
						break;

					case 'password' :
						adminPassword();
						break;
						
					case 'opt':
						break;

					default :	//default;
						adminMenu();
					}
	halt ();
	}
?>