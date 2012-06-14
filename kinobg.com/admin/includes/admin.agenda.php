<?php /**/ ?><?php


 	global $tbl_1d_films, $tbl_1d_agenda, $tbl_1d_cinemas, $tbl_1d_cities;
 	global $SUBS, $PARAM, $MSG, $MONTHS, $MONTHS2;

	//pokaji programata
	if ($PARAM['Show'] == 1)
		{
		if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();

		$SUBS['COMMAND'] = $PARAM['cmd']."&WHERE=".$PARAM['WHERE']."&WHEN=".$PARAM['WHEN']."&WEEK=".$PARAM['WHEN']."&PLACES=".$PARAM['PLACES'];
		printPage('_admin_done.htmlt');
		return;
		}

	//iztrij markitanite zaglawiya
	if ($PARAM['Delete'] == 1)
		{
		reset ($PARAM);
		$Films = '0';
		while (list($k,$v) = each($PARAM))
			if (ereg('^agenda_([0-9]+)$',$k,$R))
				$Films .= ",$R[1]";

		if ($Films == '0')
			{
			$SUBS['ERROR'] = $MSG[20008];
			$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
			} else {
			$query = "DELETE FROM $tbl_1d_agenda
				WHERE ID IN ($Films)";
			$result = runQuery($query,'manageAgenda()','DEL_AGENDA');
			
			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20030&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK']."&CINEMA=".$PARAM['PLACES']."&WHERE=".$PARAM['WHERE']."&PLACES=".$PARAM['PLACES'];
			printPage('_admin_done.htmlt');
			return;
			}
		}

	if ($PARAM['WHERE'])
		$Where = " AND $tbl_1d_cities.ID=".$PARAM['WHERE'];

	if (($PARAM['WHERE']) && ($PARAM['PLACES']))
		$Where .= " AND $tbl_1d_cinemas.ID=".$PARAM['PLACES'];

	////----[Mrasnika's] Edition 02.10.2002
	if ($PARAM['WHEN'])
		{
		$PARAM['Year1'] = date ('Y', $PARAM['WHEN']);
		$PARAM['Month1'] = date ('m', $PARAM['WHEN']);
		$PARAM['Day1'] = date ('d', $PARAM['WHEN']);
		} else if ($PARAM['Day1'] && $PARAM['Month1'] && $PARAM['Year1'])
			$PARAM['WHEN'] = 1 + strToTime ($PARAM['Day1'].' '.$MONTHS2[$PARAM['Month1']].' '.$PARAM['Year1']);
			 else $PARAM['WHEN'] = getNextWeek();

	//pokaji wywedenata programa
	$query = "SELECT	$tbl_1d_agenda.ID,
			CinemaID,
			Cinema,
			CityID,
			City,
			Type,
			Film,
			$tbl_1d_agenda.Agenda,
			tsWhen,
			$tbl_1d_films.Title,
			$tbl_1d_agenda.tsLast,
			$tbl_1d_agenda.Priority
		
		 -- FROM $tbl_1d_agenda, $tbl_1d_cinemas, $tbl_1d_cities
		 
		 FROM $tbl_1d_agenda
		 
		 INNER JOIN $tbl_1d_cinemas
		 	ON $tbl_1d_agenda.CinemaID = $tbl_1d_cinemas.ID
		 INNER JOIN $tbl_1d_cities
		 	ON $tbl_1d_cinemas.CityID = $tbl_1d_cities.ID
		 
		 --
		
		LEFT JOIN $tbl_1d_films
			ON $tbl_1d_films.ID = $tbl_1d_agenda.Film
		WHERE $tbl_1d_agenda.tsWhen >= ".week($PARAM['WHEN'])."
			AND $tbl_1d_agenda.tsWhen <= (".week($PARAM['WHEN'])."+604799)
			$Where
		ORDER BY $tbl_1d_cities.ID, $tbl_1d_agenda.CinemaID, $tbl_1d_agenda.Priority";
	$result = runQuery($query,'manageAgenda()','GET_AGENDA');

	if (db_num_rows($result) == 0)
		$SUBS['NONE'] = $MSG[10015];
		else while ($row = db_fetch_row($result))
			{
			$SUBS['CHECK'] = $row[0];

			$SUBS['CINEMAID'] = $row[1];
			$SUBS['KINO'] = htmlEncode($row[2]);

			$SUBS['CITYID'] = $row[3];
			$SUBS['CITY'] = htmlEncode($row[4]);
			
			if ($row[5] == 'list')
				{
				$SUBS['TITLE'] = htmlEncode($row[9]);
				$SUBS['MOVIE'] = $SUBS['ACTION']."?cmd=insertfilm&ID=$row[6]";
				} else {
				$SUBS['TITLE'] = htmlEncode($row[6]);
				$SUBS['MOVIE'] = "javascript:alert('$MSG[20031]')";
				}

			$SUBS['KOGA'] = htmlEncode($row[7]);
			$SUBS['PRATI'] = $row[8];
			$SUBS['NOM'] = $row[11];
			$SUBS['LAST'] = $datata = date ('d ', $row[10]).$MONTHS[intval(date('m',$row[10]))].date(' Y H:i:s', $row[10]);	
			$SUBS['AGENDA'] .= fileParse('_admin_manage_agenda_row.htmlt');

			if (($PARAM['Add'] != 1) && ($PARAM['id'] == $row[0]))
				{
				$PARAM['CINEMA'] = $row[1];
				$PARAM['TYPE'] = $row[5];
				if ($row[5]!='list')
					$PARAM['FILM'] = $row[6];
					else $PARAM['FILMS'] = htmlEncode($row[6]);
				$PARAM['DATE'] = htmlEncode($row[7]);
				$PARAM['WEEK'] = $row[8];
				$PARAM['NO'] = $row[11];
				}
			}

	if ($PARAM['Add'] == 1)
		{	//nowo zaglavie
		$SUBS['ERROR'] ='';

		if ($PARAM['DATE'] == '')	//data na projekciyata
			$SUBS['ERROR'] = $MSG[20025];

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

		if ($SUBS['ERROR'] == '')
			{	//proweri kopbinaciya mejdu kino i film
			$query = "SELECT ID FROM $tbl_1d_agenda
				WHERE CinemaID = ".dbQuote($PARAM['CINEMA'])."
					AND ID != ".dbQuote($PARAM['id'])."
					AND $tbl_1d_agenda.tsWhen >= ".$PARAM['WEEK']."
					AND $tbl_1d_agenda.tsWhen <= (".$PARAM['WEEK']."+604799)
					AND (((Type = 'list') AND (Film = ".dbQuote($PARAM['FILMS'])."))
						OR ((Type = 'raw') AND (Film = ".dbQuote($PARAM['FILM']).")))";
			$result = runQuery($query,'manageAgenda()','CHECK_CINEMA');
			if ($row = db_fetch_row($result))
				{
				$SUBS['COMMAND'] = $PARAM['cmd']."&err=20035&id=$row[0]";
				printPage('_admin_done.htmlt');
				return;
				}
			}

		if ($SUBS['ERROR'] == '')
			{
			if ($PARAM['id']=='')
				$query = "INSERT INTO $tbl_1d_agenda
					(CinemaID,
					Type,
					Film,
					Agenda,
					Priority,
					tsWhen,
					tsLast) VALUES
					(".dbQuote($PARAM['CINEMA']).",
					".dbQuote($PARAM['TYPE']).",
					".dbQuote($film).",
					".dbQuote($PARAM['DATE']).",
					".intval($PARAM['NO']).",
					".dbQuote(week($PARAM['WEEK'])).",
					".time().")";
				else $query =
					"UPDATE $tbl_1d_agenda SET
					CinemaID = ".dbQuote($PARAM['CINEMA']).",
					Type = ".dbQuote($PARAM['TYPE']).",
					Film = ".dbQuote($film).",
					Agenda = ".dbQuote($PARAM['DATE']).",
					Priority = ".intval($PARAM['NO']).",
					tsWhen = ".dbQuote(week($PARAM['WEEK'])).",
					tsLast = ".time()."
					WHERE ID = ".dbQuote($PARAM['id']);

			$result = runQuery($query,'manageAgenda()','SAVE_FILM');

			//get city
			$query = "SELECT CityID FROM $tbl_1d_cinemas WHERE ID=".$PARAM['CINEMA'];
			$result = runQuery($query,'manageAgenda()','GET_CITY_ID');
			$row = db_fetch_row($result);

			$SUBS['COMMAND'] = $PARAM['cmd']."&err=20029&WHEN=".$PARAM['WEEK']."&WEEK=".$PARAM['WEEK']."&CINEMA=".$PARAM['CINEMA']."&WHERE=$row[0]&PLACES=".$PARAM['CINEMA'];
			printPage('_admin_done.htmlt');
			return;
			} else $SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	//pokaji zapisanite filmi
	$query = "SELECT ID, Title
		FROM $tbl_1d_films
		WHERE Agenda = 'yes'
			AND Title != '' ";
	$result = runQuery($query,'manageAgenda()','GET_FILMS');
	while ($row = db_fetch_row($result))
		if ($PARAM['FILMS'] == $row[0])
		$SUBS['FILM'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
		else $SUBS['FILM'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);

	//pokaji wywedenite kina
	$query = "SELECT $tbl_1d_cinemas.ID,
			$tbl_1d_cinemas.Cinema,
			$tbl_1d_cities.City
		FROM $tbl_1d_cinemas, $tbl_1d_cities
		WHERE $tbl_1d_cinemas.CityID=$tbl_1d_cities.ID
			AND ($tbl_1d_cities.Active = 'yes'
				OR $tbl_1d_cinemas.ID=".dbQuote($PARAM['CINEMA']).")
		ORDER BY $tbl_1d_cities.ID";
	$result = runQuery($query,'manageAgenda()','GET_CINEMAS');
	while ($row = db_fetch_row($result))
		if ($PARAM['CINEMA'] == $row[0])
			$SUBS['CINEMA'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[2])." - ".htmlEncode($row[1]);
			else $SUBS['CINEMA'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[2])." - ".htmlEncode($row[1]);

	//pokaji gradowete
	$query = "SELECT	$tbl_1d_cities.ID,
			$tbl_1d_cities.City
		FROM $tbl_1d_cities
		WHERE ($tbl_1d_cities.Active='yes'
			OR $tbl_1d_cities.ID=".dbQuote($PARAM['WHERE']).")
		ORDER BY $tbl_1d_cities.ID";
	$result = runQuery($query,'manageAgenda()','GET_CITIES');
	while ($row = db_fetch_row($result))
		if ($PARAM['WHERE'] == $row[0])
			$SUBS['WHERE'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
			else $SUBS['WHERE'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);

	if ($PARAM['WHERE'])
		{	//pokaji kinata
		$query = "SELECT	$tbl_1d_cinemas.ID,
				$tbl_1d_cinemas.Cinema
			FROM $tbl_1d_cinemas
			WHERE $tbl_1d_cinemas.CityID = ".dbQuote($PARAM['WHERE'])."
			ORDER BY $tbl_1d_cinemas.ID";
		$result = runQuery($query,'manageAgenda()','GET_CINEMAS_FOR_CITY');
		while ($row = db_fetch_row($result))
			if ($PARAM['PLACES'] == $row[0])
				$SUBS['PLACES'] .= "\n<option value=\"$row[0]\" selected>".htmlEncode($row[1]);
				else $SUBS['PLACES'] .= "\n<option value=\"$row[0]\">".htmlEncode($row[1]);
		}

	$SUBS['FILM2'] = htmlEncode($PARAM['FILM']);
	$SUBS['DATE'] = htmlEncode($PARAM['DATE']);
	$SUBS['ID'] = htmlEncode($PARAM['id']);
	$SUBS['NO'] = $PARAM['NO'];
	$SUBS['TYPE'.strtoupper($PARAM['TYPE'])] = ' checked';

	//get oldest week
	$query = "SELECT min(tsWhen) FROM $tbl_1d_agenda";
	$result = runQuery($query,'manageAgenda()','GET_OLDEST_WEEK');
	if ($row = db_fetch_row($result))
		{
		global $span;
		$span = $row[0];
		}

	////----[Mrasnika's] Edition 02.10.2002
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
	
	////----[Mrasnika's] Edition 12.10.2002
	$SUBS['PREV'] = week($PARAM['WHEN']) - 518400;
	$SUBS['NEXT'] = week($PARAM['WHEN']) + 1026800; 

	$SUBS['GO'] = $PARAM['WHERE'];
	$SUBS['GO2'] = $PARAM['WHEN'];

	if (($PARAM['err'] != '') && ($SUBS['ERROR']==''))
		{
		$SUBS['ERROR'] = $MSG[$PARAM['err']];
		$SUBS['ERROR'] = fileParse('_admin_error.htmlt');
		}

	printPage('_admin_manage_agenda.htmlt');

?>