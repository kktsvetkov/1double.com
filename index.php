<?php /**/ ?><?
// ------------------------------------------------------------------
// index.php
// ------------------------------------------------------------------


define ('INDEX', 1);
include('includes/shared.inc.php');
//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

//fucntion getArticle()
function getArticle($type, $tswhen) {
	global $tbl_1d_pictures, $tbl_1d_article;
	global $SUBS, $MSG, $MONTHS;

	$Type = dbQuote($type);
	$today = 1 + strToTime (date('d F Y'));

	switch ($type)
		{
		case 'front' :	//pyrwa stranica
			$SUBS['PIC_2'] = '_front.gif';
			$SUBS['ALT_2'] = $MSG[30013];
			break;
		
		default :	//pokazwane na statii
			$SUBS['PIC_2'] = '_akt.gif';
			$SUBS['ALT_2'] = $MSG[30012];
			$Where = " AND $tbl_1d_article.ID=".dbQuote($type);
			break;
		}

	$query = "SELECT	Title,
		Caption,
		$tbl_1d_article.ID,
		$tbl_1d_article.tsWhen
	FROM	$tbl_1d_article
	WHERE	$tbl_1d_article.tsWhen <= $today
		$Where
	ORDER BY ($tbl_1d_article.tsWhen >= $today) DESC,
		$tbl_1d_article.tsWhen DESC,
		$tbl_1d_article.Priority
	LIMIT 0, 10";

	$result = runQuery($query,'getArtcile()','GET_ARTICLES');
	
	////----[Mrasnika's] Edition 20.10.2002
	if (!db_num_rows($result) && ($type != 'front'))
		return 0;
	
	$Articles = '';
	$SUBS['ALIGN'] = 'right';
	$path = getAdmSetting('UPLOAD_DIR');

	$mark = 0;
	while ($row = db_fetch_row($result))
		{
		if ($mark == 0) $mark = $row[3];
		
		if ($type == 'front')
			{	//pyrwa stranica
			if ($mark > ($row[3]+604799)) continue;
			} else {	//statia
			$SUBS['WHEN'] = "[ ".$MSG[30020].date(' d ', $row[3]).$MONTHS[intval(date('m', $row[3]))].date(' Y', $row[3])." ]";
			}

		$SUBS['ZAGLAVIE'] = htmlEncode($row[0]);
		$SUBS['ID'] = $row[2];

		////----[Mrasnika's] Edition 26.10.2002
		switch ($type) {
			case 'front' :	//cut tha caption
				$length = 450;
				$pics = " LIMIT 0, 2";
				if ((strLen($row[1])/$length)>2)
					{
					$SUBS['CAPTION'] = nl2br(htmlEncode(subStr(rtrim($row[1]), 0, $length)));
					$SUBS['CAPTION'] .= fileParse('_index_article_show.htmlt');
					} else $SUBS['CAPTION'] = nl2br(htmlEncode(rtrim($row[1])));
				break;
			default :	//show the whole caption
				$SUBS['CAPTION'] = nl2br(htmlEncode($row[1]));
				break;
			}

		$query = "SELECT	$tbl_1d_pictures.URL,
				$tbl_1d_pictures.Width,
				$tbl_1d_pictures.Height,
				
				p1.URL,
				p1.Width,
				p1.Height
				
			FROM	$tbl_1d_pictures
			LEFT JOIN	$tbl_1d_pictures AS p1
				ON p1.RefID = $tbl_1d_pictures.ID
					AND p1.RefType = 'thumb'
			WHERE  $tbl_1d_pictures.RefID = $row[2]
				AND $tbl_1d_pictures.RefType = 'article'
			ORDER BY Rand()
			$pics ";
		$pics = '';
		
		////----[Masnika's] Edition 16.10.2002
		$pics2 = '';
		$up = 0;
		
		$res = runQuery($query,'getArtcile()','GET_ARTICLE_PHOTOS');
		while ($r = db_fetch_row($res))
			{
			$SUBS['URL'] = $path.$r[0];
			$SUBS['WIDTH'] = $r[1];
			$SUBS['HEIGHT'] = $r[2];
			
			$SUBS['TURL'] = $path.$r[3];
			$SUBS['TWIDTH'] = $r[4];
			$SUBS['THEIGHT'] = $r[5];

			////----[Masnika's] Edition 16.10.2002
			if ($up<2)
				{
				$up++;
				$SUBS['ALIGN'] = 'right';
				$pics .= fileParse('_index_thumb.htmlt');
				} else {
				$SUBS['ALIGN'] = '';
				$pics2 .= fileParse('_index_thumb.htmlt');
				}
			}
		$SUBS['KARTINKI'] = $pics;
		$SUBS['KARTINKI2'] = $pics2;
		$Articles .= fileParse('_index_article.htmlt');
		}

	//return $Articles;

	$SUBS['VALUE_2'] = $Articles;
	return fileParse('_index_section2.htmlt');
	}

//fucntion getBlock()
function getBlock($code, $nohead=0) {
	global $tbl_1d_pictures, $tbl_1d_films, $tbl_1d_videodvd, $tbl_1d_distr, $tbl_1d_article;
	global $tbl_1d_charts, $tbl_1d_videodvd_charts, $tbl_1d_kino_charts;
	global $SUBS, $MSG, $PARAM;

	switch ($code) {
		case 1 :	//bg premieri
			$SUBS['CMD'] = 'film';
			$ref = dbQuote('film');
			$size = 2;
			
			$SUBS['PIC_2'] = '_kino.gif';
			$SUBS['CMD_2'] = 'kinoprem';
			$SUBS['ALT_2'] = $MSG[30008];
			
			$query = "SELECT	$tbl_1d_films.ID,
					
					$tbl_1d_films.Title,
					$tbl_1d_films.Actors,
					
					$tbl_1d_films.tsPremiere

				FROM $tbl_1d_films

				WHERE 	$tbl_1d_films.tsPremiere <= (".week()."+604799)
						AND $tbl_1d_films.tsPremiere != ''
						AND $tbl_1d_films.Title != ''

				GROUP BY $tbl_1d_films.ID
				
				ORDER BY	($tbl_1d_films.tsPremiere >= ".week().") DESC,
					$tbl_1d_films.tsPremiere DESC,
					$tbl_1d_films.Actors DESC,
					$tbl_1d_films.Title ";
			break;

		case 2 :	//video premieri
			$SUBS['CMD'] = 'video';
			$ref = dbQuote('video');
			$size = 2;
			
			$SUBS['PIC_2'] = '_video.gif';
			$SUBS['CMD_2'] = 'videoprem';
			$SUBS['ALT_2'] = $MSG[30007];
			
			$query = "SELECT	$tbl_1d_videodvd.ID,
					
					$tbl_1d_films.Title,
					$tbl_1d_films.Actors,
					
					$tbl_1d_videodvd.tsWhen

				FROM $tbl_1d_videodvd
				LEFT JOIN $tbl_1d_films
					ON $tbl_1d_films.ID = $tbl_1d_videodvd.FilmID
				LEFT JOIN $tbl_1d_distr
					ON $tbl_1d_videodvd.DistributorID = $tbl_1d_distr.ID

				WHERE 	$tbl_1d_videodvd.tsWhen <= (".week()."+604799)
						AND $tbl_1d_videodvd.Type = 'video'
						
				GROUP BY $tbl_1d_films.ID
				
				ORDER BY	($tbl_1d_videodvd.tsWhen >= ".week().") DESC,
					$tbl_1d_videodvd.tsWhen DESC,
					$tbl_1d_distr.Priority,
					$tbl_1d_films.Actors DESC,
					$tbl_1d_films.Title";
			break;

		case 3 :	//dvd premieri
			$SUBS['CMD'] = 'dvd';
			$ref = dbQuote('dvd');
			$size = 2;
			
			$SUBS['PIC_2'] = '_dvd.gif';
			$SUBS['CMD_2'] = 'dvdprem';
			$SUBS['ALT_2'] = $MSG[30009];
			
			$query = "SELECT	$tbl_1d_videodvd.ID,
					
					$tbl_1d_films.Title,
					$tbl_1d_films.Actors,
					
					$tbl_1d_videodvd.tsWhen

				FROM $tbl_1d_videodvd
				LEFT JOIN $tbl_1d_films
					ON $tbl_1d_films.ID = $tbl_1d_videodvd.FilmID
				LEFT JOIN $tbl_1d_distr
					ON $tbl_1d_videodvd.DistributorID = $tbl_1d_distr.ID

				WHERE 	$tbl_1d_videodvd.tsWhen <= (".week()."+604799)
						AND $tbl_1d_videodvd.Type = 'dvd'

				GROUP BY $tbl_1d_films.ID
				
				ORDER BY	($tbl_1d_videodvd.tsWhen >= ".week().") DESC,
					$tbl_1d_videodvd.tsWhen DESC,
					$tbl_1d_distr.Priority,
					$tbl_1d_films.Actors DESC,
					$tbl_1d_films.Title";
			break;

		case 4 :	//usa premieri
			$SUBS['CMD'] = 'film';
			$ref = dbQuote('film');
			$size = 2;
			
			$SUBS['PIC_2'] = '_usa.gif';
			$SUBS['CMD_2'] = 'usaprem';
			$SUBS['ALT_2'] = $MSG[30010];
			
			$query = "SELECT	$tbl_1d_films.ID,
					
					$tbl_1d_films.OriginalTitle,
					$tbl_1d_films.Actors,
					
					$tbl_1d_films.tsUsaPremiere

				FROM $tbl_1d_films

				WHERE 	$tbl_1d_films.tsUsaPremiere <= (".week()."+604799)
						AND $tbl_1d_films.tsUsaPremiere != ''
						AND $tbl_1d_films.OriginalTitle != ''

				GROUP BY $tbl_1d_films.ID
				
				ORDER BY	($tbl_1d_films.tsUsaPremiere >= ".week().") DESC,
					$tbl_1d_films.tsUsaPremiere DESC,
					$tbl_1d_films.Actors DESC,
					$tbl_1d_films.OriginalTitle ";
			break;

		default :
		case 5 :	//statii za parwa stranica
			$today = 1 + strToTime (date('d F Y'));
			
			$SUBS['CMD'] = '2double';
			$ref = dbQuote('article');
			$size = 1;
			
			$SUBS['PIC_2'] = '_akt.gif';
			$SUBS['CMD_2'] = 'statia';
			$SUBS['ALT_2'] = $MSG[30012];
			
			$query = "SELECT	$tbl_1d_article.ID,
					
					$tbl_1d_article.Title,
					'',
					
					''

				FROM $tbl_1d_article

				WHERE 	$tbl_1d_article.tsWhen < $today

				ORDER BY	($tbl_1d_article.tsWhen >= ".week().") DESC,
					$tbl_1d_article.tsWhen DESC,
					$tbl_1d_article.Priority
				LIMIT 0, 20";

			break;

		case 7 :	//klasacii
			$SUBS['CMD'] = 'klasacia';
			$ref = dbQuote('');
			$size = 1;
			
			$SUBS['PIC_2'] = '_kino_chart.gif';
			$SUBS['CMD_2'] = 'charts';
			$SUBS['ALT_2'] = $MSG[30012];
			
			$query = "SELECT	$tbl_1d_article.ID,
					
					$tbl_1d_article.Title,
					'',
					
					''

				FROM $tbl_1d_article

				WHERE 	$tbl_1d_article.tsWhen < (".week()."+604799)

				ORDER BY	($tbl_1d_article.tsWhen >= ".week().") DESC,
					$tbl_1d_article.tsWhen DESC,
					$tbl_1d_article.Priority
				LIMIT 0, 5";

		 	$query = "SELECT	$tbl_1d_charts.id,
		 			$tbl_1d_charts.Title,
		 			'',
		 			''
		 		FROM $tbl_1d_charts
		 		LEFT JOIN $tbl_1d_kino_charts
					ON $tbl_1d_kino_charts.ChartID = $tbl_1d_charts.ID
						AND $tbl_1d_charts.Type = 'kino'
		 		LEFT JOIN $tbl_1d_videodvd_charts
					ON $tbl_1d_videodvd_charts.ChartID = $tbl_1d_charts.ID
						AND $tbl_1d_charts.Type = 'videodvd'
				WHERE $tbl_1d_charts.id != ".dbQuote($PARAM['id'])."
				GROUP BY $tbl_1d_charts.id";

			break;
		}

	$result = runQuery($query,'getBlock()','GET_BLOCK');
	$mark = 0;
	$SUBS['BLOCKS'] = '';
	while ($row = db_fetch_row($result))
		{
		if ($mark == 0) $mark = $row[3];
		if (week($mark) != week($row[3])) break;

		$SUBS['ID'] = htmlEncode($row[0]);
		$SUBS['PRE'] = htmlEncode($row[1]);
		$IDS[$row[0]] = $SUBS['PRE'];
		$SUBS['MORE'] = htmlEncode($row[2]);

		if ($row[2])
			$SUBS['BLOCKS'] .= fileParse('_index_block_row.htmlt');
			else $SUBS['BLOCKS'] .= fileParse('_index_block_row2.htmlt');
		}

	//get pictures
	if (is_array($IDS))
		$ids = join (',' , array_keys($IDS));
		else $ids ='';
	if ($ids == '') $ids = '0';
	
	$query = "SELECT	$tbl_1d_pictures.URL,
			$tbl_1d_pictures.Width,
			$tbl_1d_pictures.Height,

			p1.URL,
			p1.Width,
			p1.Height,
			
			$tbl_1d_pictures.RefID

		FROM	$tbl_1d_pictures
		LEFT JOIN $tbl_1d_pictures AS p1
			ON $tbl_1d_pictures.ID = p1.RefID
				AND p1.RefType = 'thumb'
		WHERE	$tbl_1d_pictures.RefID IN ($ids)
				AND $tbl_1d_pictures.RefType = $ref
		ORDER BY RAND()";

	$result = runQuery($query,'getBlock()','GET_BLOCK_PICS');

	$SUBS['KARTINKI'] = '';
	$SUBS['ALIGN'] = 'center';
	$path = getAdmSetting('UPLOAD_DIR');
	$mark = 0;
	while ($row = db_fetch_row($result))
		{
		if ($mark == 0)
			$mark = $row[6];
			else if ($mark == $row[6])
				continue;
		if ($size == 0)
			break;
			else $size--;
		
		$SUBS['ALT'] = $IDS[$row[6]];
		
		if ($row[4] == '')
			{
			$SUBS['TURL'] = $path.$row[0];
			$SUBS['TWIDTH'] = $row[1];
			$SUBS['THEIGHT'] = $row[2];

			$SUBS['KARTINKI'] .= fileParse('_index_kaseta.htmlt');
			} else {
			$SUBS['URL'] = $path.$row[0];
			$SUBS['WIDTH'] = $row[1];
			$SUBS['HEIGHT'] = $row[2];
		
			$SUBS['TURL'] = $path.$row[3];
			$SUBS['TWIDTH'] = $row[4];
			$SUBS['THEIGHT'] = $row[5];
			$SUBS['KARTINKI'] .= " " . fileParse('_index_thumb.htmlt');
			}
		}
	//return fileParse('_index_block.htmlt');
	if (!$nohead)
		{
		$SUBS['VALUE_2'] = fileParse('_index_block.htmlt');
		return fileParse('_index_section.htmlt');
		} else return fileParse('_index_block.htmlt');
	}

//fucntion getWeek()
function getWeek($code) {
	global $tbl_1d_pictures, $tbl_1d_films, $tbl_1d_videodvd, $tbl_1d_distr, $tbl_1d_article;
	global $MONTHS, $MSG, $SUBS;

	switch ($code) {
		case 1 :	//bg premieri
			$pre = $MSG[30002];
			$query = "SELECT	$tbl_1d_films.tsPremiere, $tbl_1d_films.ID
				FROM $tbl_1d_films
				WHERE 	$tbl_1d_films.tsPremiere <= (".week()."+604799)
				ORDER BY	($tbl_1d_films.tsPremiere >= ".week().") DESC,
					$tbl_1d_films.tsPremiere DESC ";
			break;

		case 2 :	//video premieri
			$pre = $MSG[30004];
			$query = "SELECT	$tbl_1d_videodvd.tsWhen
				FROM $tbl_1d_videodvd
				WHERE 	$tbl_1d_videodvd.tsWhen <= (".week()."+604799)
						AND $tbl_1d_videodvd.Type = 'video'
				ORDER BY	($tbl_1d_videodvd.tsWhen >= ".week().") DESC,
					$tbl_1d_videodvd.tsWhen DESC ";
			break;

		case 3 :	//dvd premieri
			$pre = $MSG[30005];
			$query = "SELECT	$tbl_1d_videodvd.tsWhen
				FROM $tbl_1d_videodvd
				WHERE 	$tbl_1d_videodvd.tsWhen <= (".week()."+604799)
						AND $tbl_1d_videodvd.Type = 'dvd'
				ORDER BY	($tbl_1d_videodvd.tsWhen >= ".week().") DESC,
					$tbl_1d_videodvd.tsWhen DESC ";
			break;

		case 4 :	//usa premieri
			$pre = $MSG[30003];
			$query = "SELECT	$tbl_1d_films.tsUsaPremiere
				FROM $tbl_1d_films
				WHERE 	$tbl_1d_films.tsUsaPremiere <= (".week()."+604799)
				ORDER BY	($tbl_1d_films.tsUsaPremiere >= ".week().") DESC,
					$tbl_1d_films.tsUsaPremiere DESC ";
			break;
		}

	$result = runQuery($query,'getWeek()','GET_WEEK');
	$mark = 0;

	////----[Mrasnika's] Edition 31.10.2002
	/* while ($row = db_fetch_row($result))
		{
		if ($mark == 0) $mark = week($row[0]);
		if ($mark != week($row[0])) break;
		}
	*/
	$row = @db_fetch_row($result);
	$mark = week($row[0]);

	if ($mark == week())
		return '';

	$dokoga = $mark + 604799;
	$godina1 = date('Y',$mark);
	$godina2 = date('Y',$dokoga);
	if ($godina1 == $godina2)
		$godina1 = '';
	
	$datata = date ('d ', $mark).$MONTHS[intval(date('m',$mark))]." $godina1";
	$datata .= date (' - d ', $dokoga).$MONTHS[intval(date('m',$dokoga))]." $godina2";
	$SUBS['WR'] = "$pre $datata";
	return fileParse ('_index_week.htmlt');
	}


//fucntion cinemaWeek()
function cinemaWeek($code) {
	global $tbl_1d_agenda;
	global $MONTHS, $MSG, $SUBS;

////----[Mrasnika's] Edition 12.11.2002
	return '';

	$pre = $MSG[30016];
	$code = dbQuote($code);

	$query = "SELECT	$tbl_1d_agenda.tsWhen
		FROM $tbl_1d_agenda
		WHERE 	$tbl_1d_agenda.tsWhen <= (".week()."+604799)
				AND $tbl_1d_agenda.CinemaID = $code
		
		ORDER BY	($tbl_1d_agenda.tsWhen >= ".week().") DESC,
			$tbl_1d_agenda.tsWhen DESC ";

	$result = runQuery($query,'cinemaWeek()','GET_WEEK');
	$mark = 0;

	////----[Mrasnika's] Edition 12.11.2002
	/*
	while ($row = db_fetch_row($result))
		{
		if ($mark == 0) $mark = week($row[0]);
		if ($mark != week($row[0])) break;
		}
	if ($mark == week())
		return '';
	*/

	$row = @db_fetch_row($result);
	$mark = week($row[0]);

//echo "$mark ".week();
//echo "<br>".date('Y-m-d H:i:s - ',$row[0]).date('Y-m-d H:i:s ',week());

	if ($mark == week())
		return '';
	
	$dokoga = $mark + 604799;
	$godina1 = date('Y',$mark);
	$godina2 = date('Y',$dokoga);
	if ($godina1 == $godina2)
		$godina1 = '';
	
	$datata = date ('d ', $mark).$MONTHS[intval(date('m',$mark))]." $godina1";
	$datata .= date (' - d ', $dokoga).$MONTHS[intval(date('m',$dokoga))]." $godina2";
	$SUBS['WR'] = "$pre $datata";
	return fileParse ('_index_week.htmlt');
	}

//fucntion getAgenda()
function getAgenda($exclude=0) {
	global $tbl_1d_cities, $tbl_1d_agenda, $tbl_1d_cinemas;
	global $SUBS;

	////---- [Mrasnika's] Edition 2003-08-03
	// sazdaj wremennata tablica
	$name = "tbl2_".md5(microtime());
	$query = "CREATE TEMPORARY TABLE $name (
			ID INT NOT NULL auto_increment,
			
			CityID INT,
			City TEXT,
			CityPriority INT, 

			CinemaID INT,
			Cinema TEXT,
			CinemaPriority INT,
			
			Primary Key (ID))";
	runQuery($query,'getAgenda()','CREATE_TEMPORARY');

	//nameri kinata
	$query = "SELECT DISTINCT $tbl_1d_agenda.CinemaID
		FROM	$tbl_1d_agenda";
	$result = runQuery($query,'getAgenda()','FIND_CINEMAS');
	$cinemas = '0';
	while ($row = db_fetch_row($result)) {
		$cinemas .= ",$row[0]";
		}

	//zapischi kinata
	$query = "SELECT	$tbl_1d_cinemas.ID,
			$tbl_1d_cinemas.Cinema,
			$tbl_1d_cinemas.CityID,
			$tbl_1d_cinemas.Priority
		FROM	$tbl_1d_cinemas
		WHERE $tbl_1d_cinemas.ID IN ($cinemas) ";
	$result = runQuery($query,'getAgenda()','GET_CINEMAS');
	$cities = '0';
	$query = "INSERT INTO $name (CinemaID, Cinema, CityID, CinemaPriority) VALUES ";
	for ($i=0; $i<db_num_rows($result); $i++) {
		$row = db_fetch_row($result);
		$cell = "($row[0], ".dbQuote($row[1]).", $row[2], $row[3])";
		if ($i == 0 ) {
			$query .= $cell;
			} else {
			$query .= ", $cell";
			}
		}
	runQuery($query,'getAgenda()','SET_CINEMAS');

	//zapischi gradowete
	$query = "SELECT	$tbl_1d_cities.ID,
			$tbl_1d_cities.City,
			$tbl_1d_cities.Active = 'yes',
			$tbl_1d_cities.Priority
		FROM	$tbl_1d_cities";
	$result = runQuery($query,'getAgenda()','GET_CITIES');
	while ($row = db_fetch_row($result)) {
		if ($row[2]) {
			$query = "UPDATE $name SET
					City = ".dbQuote($row[1]).",
					CityPriority = $row[3]
				WHERE CityID = $row[0]";
			} else {
			$query = "DELETE FROM $name WHERE CityID = $row[0]";
			}
		runQuery($query,'getAgenda()','FIX_CITIES');
		}
/*
$result = runQuery("select *  from $name",'getAgenda()','FIX_CITIES');
while ($row = mysql_fetch_array($result, 1)){
	echo "<pre>";
	print_r($row);
	echo "</pre><hr>";
	}
die();
*/

	if ($exclude > 0)
		$Where = "  AND $name.cityID != ".dbQuote($exclude);

	$CITY = array();

	//wzemi razpredelanieto
	$query = "SELECT	Count(DISTINCT cinemaID)+1,
			CityID

		FROM	$name
		WHERE	1 $Where
		GROUP BY CityID
		ORDER BY CityPriority";


	$result = runQuery($query,'getAgenda()','GET_COLUMNS');
	while ($row = db_fetch_row($result))
		{
		$Sum += $row[0];
		$CITY[$row[1]] = $row[0];
		
		$Sum += 3;
		$CITY[$row[1]] += 3;
		}

	$first = '0';
	$Sum = intval($Sum/2);
	while (list($k,$v)=each($CITY))
		{
		if ($Cut>= $Sum) break;
		$Cut += $v;
		$first .= ",$k";
		}

	//pokaji kinata
	$query = "SELECT	CinemaID,
			Cinema,
			City,
			CityID,
			CityID IN ($first)

		FROM	$name
		WHERE	1 $Where
		GROUP BY CinemaID
		ORDER BY	
			CityPriority,
			CinemaPriority";

	$result = runQuery($query,'getAgenda()','GET_CINEMAS');
	$city = '0';
	$SUBS['AGENDA1'] = '';
	$SUBS['AGENDA0'] = '';
	while ($row = db_fetch_row($result))
		{
		if ($city != $row[2])
			{
			$city = $row[2];
			$SUBS['CITY'] = $city;
			$SUBS['CITYID'] = $row[3];
			if ($row[4]==1)
				$SUBS['AGENDA1'] .= fileParse ('_index_cinema_city.htmlt');
				else $SUBS['AGENDA0'] .= fileParse ('_index_cinema_city.htmlt');
			}
		$SUBS['CINEMAID'] = $row[0];
		$SUBS['CINEMA'] = htmlEncode($row[1]);
		if ($row[4]==1)
			$SUBS['AGENDA1'] .= fileParse('_index_cinema_row.htmlt');
			else $SUBS['AGENDA0'] .= fileParse('_index_cinema_row.htmlt');
		}
	return fileParse('_index_cinema.htmlt');
	}


//fucntion getCity()
function getCity() {
	global $tbl_1d_cities, $tbl_1d_agenda, $tbl_1d_cinemas, $tbl_1d_films, $tbl_1d_pictures;
	global $SUBS, $MSG, $PARAM;

	////----[Mrasnika's] Edition 2003-08-03
	// imeto na grada
	$query = "SELECT $tbl_1d_cities.City
		FROM $tbl_1d_cities
		WHERE $tbl_1d_cities.ID = ".dbQuote($PARAM['id']);
	$result = runQuery($query,'getCity()','GET_TOWN');
	$row = db_fetch_row($result);
	$town = $row[0];

	//nameri kinata
	$query = "SELECT $tbl_1d_cinemas.ID
		FROM $tbl_1d_cinemas
		WHERE $tbl_1d_cinemas.CityID = ".dbQuote($PARAM['id']);
	$result = runQuery($query,'getCity()','GET_CINEMAS');
	$cinemas = '0';
	while ($row = db_fetch_row($result)) {
		$cinemas .= ",$row[0]";
		}

	//nameri sedmicata
	$query = "SELECT $tbl_1d_agenda.tsWhen
		FROM $tbl_1d_agenda
		WHERE	$tbl_1d_agenda.ID IS NOT NULL
				AND $tbl_1d_agenda.CinemaID IN ($cinemas)
				AND $tbl_1d_agenda.tsWhen <= ".(week()+604799)."

		ORDER BY ($tbl_1d_agenda.tsWhen >= ".week().") DESC,
			$tbl_1d_agenda.tsWhen DESC
		LIMIT 0,1";
	$result = runQuery($query,'getCity()','GET_WEEK');
	$row = db_fetch_row($result);
	$__week__ = $row[0];

	
	// sazdaj wremenna tablica
	$name = "tbl_".md5(microtime());
	$query = "CREATE TEMPORARY TABLE $name (
			ID INT NOT NULL auto_increment,
			Film TEXT,
			Title TEXT,
			Actors TEXT,
			
			CinemaID INT,
			Cinema TEXT,
			Agenda TEXT,
			Type TEXT,
			
			pictureWidth INT,
			pictureHeight INT,
			pictureURL TEXT,
			pictureID INT,

			thumbWidth INT,
			thumbHeight INT,
			thumbURL TEXT,
			
			tsWhen INT,
			
			cinemaPriority INT,
			agendaPriority INT,
			
		Primary Key (ID))";
	runQuery($query,'getCity()','CREATE_TEMPORARY');

	//zapischi programata
	$query = "SELECT	$tbl_1d_agenda.CinemaID,
			$tbl_1d_cinemas.Cinema,
			$tbl_1d_agenda.Agenda,
			$tbl_1d_agenda.Type,
			$tbl_1d_agenda.Film,
			$tbl_1d_agenda.tsWhen,
			
			$tbl_1d_cinemas.Priority,
			$tbl_1d_agenda.Priority

		FROM $tbl_1d_agenda
		LEFT JOIN	$tbl_1d_cinemas
			ON $tbl_1d_agenda.CinemaID = $tbl_1d_cinemas.ID

		WHERE	$tbl_1d_agenda.ID IS NOT NULL
				AND $tbl_1d_cinemas.CityID = ".dbQuote($PARAM['id'])."
				AND $tbl_1d_agenda.tsWhen <= ".($__week__+604799)."
				AND $tbl_1d_agenda.tsWhen >= $__week__";

	$result = runQuery($query,'getCity()','GET_AGENDA');
	
	$query = "INSERT INTO $name (CinemaID, Cinema, Agenda, Type, Film, tsWhen, cinemaPriority, agendaPriority) VALUES ";
	$films = '0';
	
	for ($i=0; $i<db_num_rows($result); $i++) {
		$row = db_fetch_row($result);
		$cell = "($row[0], ".dbQuote($row[1]).", ".dbQuote($row[2]).", ".dbQuote($row[3]).", ".dbQuote($row[4]).", $row[5], $row[6], $row[7])";
		if ($i == 0) {
			$query .= $cell;
			} else {
			$query .= ",$cell";
			}
		if ($row[3] == 'list') $films .= ",$row[4]";
		}
	runQuery($query,'getCity()','SET_AGENDA');

	//zapischi filmite
	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.Title,
			$tbl_1d_films.Actors

		FROM	$tbl_1d_films
		WHERE	$tbl_1d_films.ID IN ($films)";
	$result = runQuery($query,'getCity()','GET_FILMS');
	while ($row = db_fetch_row($result)) {
		$query = "UPDATE $name SET
				Title = ".dbQuote($row[1]).",
				Actors = ".dbQuote($row[2])."
			WHERE Film = $row[0]";

		runQuery($query,'getCity()','UPDATE_FILM');
		}

	//pokaji kinata
	$query = "SELECT	$name.CinemaID,
			$name.Cinema,
		
			$name.Film,
			$name.Type = 'list',
			$name.Agenda,
			$name.Title,
			
			$name.tsWhen

		FROM	$name

		ORDER BY
			## $name.tsWhen DESC,
			$name.cinemaPriority,
			$name.agendaPriority";

	$result = runQuery($query,'getCity()','GET_AGENDA_RECORDS');

	$SUBS['AGENDA1'] = '';
	$SUBS['AGENDA0'] = '';

	$SUBS['CMD'] = 'film';
	
	$cinema = '0';
	//$week = '0';
	
	while ($row = db_fetch_row($result))
		{
		//if ($week == '0') $week = $row[7];
		//if ($week != $row[7]) break;
		
		if ($cinema == '0')
			$cinema = $row[0];
			else if ($cinema!= $row[0])
				{
				$SUBS['AGENDA0'] .= fileParse('_index_kino_row.htmlt');
				$cinema = $row[0];
				$SUBS['PROGRAMATA'] = '';
				}

		$SUBS['ID'] = $row[2];
		$SUBS['MORE'] = htmlEncode($row[4]);
		
		$SUBS['CINEMAID'] = $cinema;
		$SUBS['CINEMA'] = htmlEncode($row[1]);
		$SUBS['TOWN'] = htmlEncode($town);
		
		if ($row[3])
			{
			$SUBS['PRE'] = htmlEncode($row[5]);
			$SUBS['PROGRAMATA'] .= fileParse('_index_kino_row2.htmlt');
			} else {
			$SUBS['PRE'] = htmlEncode($row[2]);
			$SUBS['PROGRAMATA'] .= fileParse('_index_kino_row3.htmlt');
			}
		}

	$SUBS['OLD'] = cinemaWeek($cinema);
	$SUBS['AGENDA0'] .= fileParse('_index_kino_row.htmlt');	//posledniyat

	//zapischi snimkite
	$query = "SELECT	$tbl_1d_pictures.Width,
			$tbl_1d_pictures.Height,
			$tbl_1d_pictures.Url,
			$tbl_1d_pictures.ID,
			$tbl_1d_pictures.RefID
		FROM $tbl_1d_pictures

		WHERE	$tbl_1d_pictures.RefType = 'film'
				AND $tbl_1d_pictures.RefID IN ($films)
		ORDER BY RAND()";

	$result = runQuery($query,'getCity()','GET_PICTURES');
	$pics = '0';
	while ($row = db_fetch_row($result)) {
		$query = "UPDATE $name SET
				pictureWidth = $row[0],
				pictureHeight = $row[1],
				pictureURL = ".dbQuote($row[2]).",
				pictureID = $row[3]
			WHERE Film = $row[4]";
		runQuery($query,'getCity()','SET_PICTURES');
		$pics .= ",$row[3]";
		}

	//zapischi thumb-ovete
	$query = "SELECT	$tbl_1d_pictures.Width,
			$tbl_1d_pictures.Height,
			$tbl_1d_pictures.Url,
			$tbl_1d_pictures.RefID
		FROM $tbl_1d_pictures

		WHERE	$tbl_1d_pictures.RefType = 'thumb'
				AND $tbl_1d_pictures.RefID IN ($pics)";
	$result = runQuery($query,'getCity()','GET_THUMBS');
	while ($row = db_fetch_row($result)) {
		$query = "UPDATE $name SET
				thumbWidth = $row[0],
				thumbHeight = $row[1],
				thumbURL = ".dbQuote($row[2])."
			WHERE pictureID = $row[3]";
		runQuery($query,'getCity()','SET_THUMBS');
		}

	//pokaji filmite
	$query = "SELECT	$name.Film,
			$name.Title,
			$name.Actors,
			
			$name.CinemaID,
			$name.Cinema,
			$name.Agenda,
			
			$name.pictureWidth,
			$name.pictureHeight,
			$name.pictureUrl,
			
			$name.thumbWidth,
			$name.thumbHeight,
			$name.thumbUrl,
			
			$name.tsWhen

		FROM	$name
		WHERE	$name.type = 'list'

		ORDER BY $name.Film ASC,
			$name.cinemaID";

	$result = runQuery($query,'getCity()','GET_FILM_RECORDS');
	
	$path = getAdmSetting('UPLOAD_DIR');
	$SUBS['ALIGN'] = 'left';
	
	$film = '0';
	$cinema = '0';
	$week = '0';
	while ($row = db_fetch_row($result))
		{
		if ($week == '0') $week = $row[12];
		if ($week != $row[12]) break;

		if ($cinema == '0')
			$cinema = $row[3];
			else if (($cinema == $row[3]) && ($film == $row[0]))
				continue;
				else $cinema = $row[3];
		
		if ($film == '0')
			$film = $row[0];
			else  if ($film!= $row[0])
				{
				$film = $row[0];
				$SUBS['AGENDA1'] .= fileParse('_index_title_row.htmlt');
				$SUBS['TITLES'] = '';
				}

		$IDS[$row[0]] = htmlEncode($row[1]);
		$SUBS['CINEMAID'] = $row[3];
		$SUBS['CINEMA'] = htmlEncode($row[4]);
		$SUBS['KOGA'] = htmlEncode($row[5]);
		$SUBS['TITLES'] .= fileParse('_index_title_row2.htmlt');

		$SUBS['TITLEID'] = $row[0];
		$SUBS['FILM_TITLE'] = htmlEncode($row[1]);
		$SUBS['ACTORS_TITLE'] = htmlEncode($row[2]);

		//kartinkata
		$SUBS['ALT'] = $SUBS['FILM_TITLE'];
		$SUBS['URL'] = $path.$row[8];
		$SUBS['WIDTH'] = $row[6];
		$SUBS['HEIGHT'] = $row[7];
		$SUBS['TURL'] = $path.$row[11];
		$SUBS['TWIDTH'] = $row[9];
		$SUBS['THEIGHT'] = $row[10];
		$SUBS['POKAJI'] = fileParse('_index_thumb.htmlt');
		}
	$SUBS['AGENDA1'] .= fileParse('_index_title_row.htmlt');

	return fileParse('_index_town.htmlt');
	}

//fucntion getCinema()
function getCinema() {
	global $tbl_1d_cities, $tbl_1d_agenda, $tbl_1d_cinemas, $tbl_1d_films, $tbl_1d_pictures;
	global $SUBS, $MSG, $PARAM;

	//pokaji kinata
	$query = "SELECT	$tbl_1d_cinemas.Cinema,
			$tbl_1d_cities.City,
			$tbl_1d_cities.ID,
			
			$tbl_1d_agenda.Film,
			$tbl_1d_agenda.Type = 'list',
			$tbl_1d_agenda.Agenda,
			$tbl_1d_films.Title,
			
			$tbl_1d_pictures.Width,
			$tbl_1d_pictures.Height,
			$tbl_1d_pictures.Url,
			
			p1.Width,
			p1.Height,
			p1.Url,
			
			$tbl_1d_films.Actors,
			$tbl_1d_films.Director,
			
			$tbl_1d_agenda.tsWhen

		-- FROM	$tbl_1d_cinemas, $tbl_1d_cities
		
		FROM $tbl_1d_cinemas

		INNER JOIN $tbl_1d_cities ON $tbl_1d_cities.ID = $tbl_1d_cinemas.CityID
		
		--

		LEFT JOIN	$tbl_1d_agenda
			ON $tbl_1d_agenda.CinemaID = $tbl_1d_cinemas.ID
		LEFT JOIN $tbl_1d_films
			ON $tbl_1d_films.ID = $tbl_1d_agenda.Film
				AND $tbl_1d_agenda.Type = 'list'
		LEFT JOIN $tbl_1d_pictures
			ON $tbl_1d_pictures.RefID = $tbl_1d_films.ID
				AND $tbl_1d_pictures.RefType = 'film'
		LEFT JOIN $tbl_1d_pictures AS p1
			ON $tbl_1d_pictures.ID = p1.RefID
				AND p1.RefType = 'thumb'

		WHERE	$tbl_1d_agenda.tsWhen <= (".week()."+604799)
				AND ($tbl_1d_agenda.ID IS NOT NULL)
				AND $tbl_1d_cinemas.ID = ".dbQuote($PARAM['id'])."

		GROUP BY $tbl_1d_agenda.tsWhen, $tbl_1d_agenda.Film
		
		ORDER BY ($tbl_1d_agenda.tsWhen >= ".week().") DESC,
			$tbl_1d_agenda.tsWhen DESC,
			$tbl_1d_cinemas.Priority,
			$tbl_1d_agenda.Priority ";

	$result = runQuery($query,'getCinema()','GET_CINEMAS');

	$path = getAdmSetting('UPLOAD_DIR');
	$SUBS['ALIGN'] = 'left';

	$week = '0';
	while ($row = db_fetch_row($result))
		{
		if ($week == '0') $week = $row[15];
		if ($week != $row[15]) continue;

//echo "$row[3] $row[15] <br>";

		$SUBS['CITYID'] = $row[2];
		$SUBS['CINEMA'] = htmlEncode($row[0]);
		$SUBS['TOWN'] = htmlEncode($row[1]);

		$SUBS['KOGA'] = htmlEncode($row[5]);

		if ($row[4])
			{
			$SUBS['FILM_TITLE'] = htmlEncode($row[6]);
			$SUBS['MOVIE'] = $SUBS['ACTION']."?cmd=film&id=$row[3]";
			} else {
			$SUBS['FILM_TITLE'] = htmlEncode($row[3]);
			$SUBS['MOVIE'] = "javascript:alert('$MSG[20031]')";
			}

		if ($row[13])
			$SUBS['ACTORS_TITLE'] = " $MSG[30014] " . htmlEncode($row[13])."<br>";
			else $SUBS['ACTORS_TITLE'] = '';
		if ($row[14])
			$SUBS['DIRECTOR_TITLE'] = " $MSG[30015] " . htmlEncode($row[14])."<br>";
			else $SUBS['DIRECTOR_TITLE'] = '';

		//kartinkata
		$SUBS['ALT'] = $SUBS['FILM_TITLE'];
		$SUBS['URL'] = $path.$row[9];
		$SUBS['WIDTH'] = $row[7];
		$SUBS['HEIGHT'] = $row[8];
		$SUBS['TURL'] = $path.$row[12];
		$SUBS['TWIDTH'] = $row[10];
		$SUBS['THEIGHT'] = $row[11];
		$SUBS['POKAJI'] = fileParse('_index_thumb.htmlt');
		
		$SUBS['AGENDA'] .= fileParse('_index_zala_row.htmlt');
		}
	
	$SUBS ['OLD'] = cinemaWeek($PARAM['id']);
	return fileParse('_index_zala.htmlt');
	}

// function getFilm()
function getFilm($id) {
	global $tbl_1d_pictures, $tbl_1d_films, $tbl_1d_videodvd;
	global $SUBS, $PARAM, $MSG;

	$SUBS['KARTINKI'] = '';
	
	////----[Mrasnika's] Edition 17.10.2002
	$SUBS['KARTINKI2'] = '';
	$top = 2;

	$query = "SELECT	$tbl_1d_films.ID,
			Title,
			OriginalTitle,
			Director,
			Actors,
			Additional,
			Description,
			Genre,
			URL

		FROM	$tbl_1d_films

		WHERE	$tbl_1d_films.ID = ".dbQuote($id);

	$result = runQuery($query,'getFilm()','GET_FILM_DETAILS');

	if (!$row = db_fetch_array($result))
		{
		frontPage();
		return;
		} else {
		if (!$row[1])
			$SUBS['ZAGLAVIE'] = htmlEncode($row[2]);
			else {
			$SUBS['ZAGLAVIE'] = htmlEncode($row[1]);
			$SUBS['ORIGINALNO'] = htmlEncode($row[2])."<br>";
			}
		
		$SUBS['ALT'] = $SUBS['ZAGLAVIE'];
		
		if ($row[3])
			$SUBS['DIRECTOR'] = $MSG[30018].htmlEncode($row[3]).'<br>';
			else $SUBS['DIRECTOR'] = '';
		
		if ($row[4])
			$SUBS['ACTORS'] = $MSG[30019].htmlEncode($row[4]).'<br>';
			else $SUBS['ACTORS'] = '';
		
		if ($row[5])
			$SUBS['ADDITIONAL'] = htmlEncode($row[5]) . '<br>';
			else $SUBS['ADDITIONAL'] ='';

		$SUBS['CAPTION'] = htmlEncode($row[6]);

		if ($row[7])
			$SUBS['GENRE'] = htmlEncode($row[7]) . '<br>';
			else $SUBS['GENRE'] = '';

		if ($row[8])
			{
			////----[Mrasnika's] Edition 2003-06-13
			if (eregi('^http://', $row[8])) {
				$SUBS['SITE'] = $row[8];
				} else {
				$SUBS['SITE'] = "http://$row[8]";
				}
			
			$SUBS['SITE'] = "$MSG[30001] " . fileParse('_index_url.htmlt') . "<br>";
			} else $SUBS['SITE'] = '';

		$query = "SELECT	$tbl_1d_pictures.URL,
				$tbl_1d_pictures.Width,
				$tbl_1d_pictures.Height,
				
				
				p1.URL,
				p1.Width,
				p1.Height
				
			FROM	$tbl_1d_pictures
			LEFT JOIN	$tbl_1d_pictures AS p1
				ON p1.RefID = $tbl_1d_pictures.ID
					AND p1.RefType = 'thumb'
			WHERE  $tbl_1d_pictures.RefID = $row[0]
				AND $tbl_1d_pictures.RefType = 'film'
			ORDER BY RAND()";

		$path = getAdmSetting('UPLOAD_DIR');
		$i = 0;
		$res = runQuery($query,'getFilms()','GET_FILM_PHOTOS');
		while ($r = db_fetch_row($res))
			{
			$SUBS['URL'] = $path.$r[0];
			$SUBS['WIDTH'] = $r[1];
			$SUBS['HEIGHT'] = $r[2];
			
			$SUBS['TURL'] = $path.$r[3];
			$SUBS['TWIDTH'] = $r[4];
			$SUBS['THEIGHT'] = $r[5];

			////----[Mrasnika's] Edition 17.10.2002
			if ($i<$top)
				{
				$i++;
				$SUBS['ALIGN'] = 'right';
				$SUBS['KARTINKI2'] .= fileParse('_index_thumb.htmlt');
				} else {
				$SUBS['ALIGN'] = '';
				$SUBS['KARTINKI'] .= " " . fileParse('_index_thumb.htmlt');
				}
			}
		} 
	
	return fileParse('_index_get_film.htmlt');
	}


//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
//function frontPage()
 function frontPage() {
	global $SUBS;
	$SUBS['FIRST'] = '';
	
	$SUBS['STATIQ'] .= getArticle('front', week());	//frontpage
	$SUBS['PREMIERI'] = getBlock (1);
	$SUBS['VIDEOPREM'] = getBlock (2);
	$SUBS['DVDPREM'] = getBlock (3);
	$SUBS['USAPREM'] = getBlock (4);

	$SUBS['ARTICLES'] = getBlock (5);
	
	index('_index_front.htmlt');
	}

//function Programa()
 function Programa() {
	global $SUBS;
	$SUBS['PROGRAMA'] = getAgenda();
	$SUBS['USAPREM'] = getBlock (4);
	$SUBS['PREMIERI'] = getBlock (1);
	
	$SUBS['ARTICLES'] = getBlock (5);
	
	index('_index_programa.htmlt');
	}


//function Grad()
 function Grad() {
	global $SUBS, $PARAM;

	$SUBS['PROGRAMA'] = getAgenda($PARAM['id']);

	if (!$SUBS['GRADA'] = getCity())
		{
		Programa();
		return;
		}
	
	//$SUBS['USAPREM'] = getBlock (4);
	$SUBS['PREMIERI'] = getBlock (1);
	
	//$SUBS['ARTICLES'] = getBlock (6);
	
	index('_index_grad.htmlt');
	}

//function Kino()
 function Kino() {
	global $SUBS, $PARAM;

	$SUBS['PROGRAMA'] = getAgenda();

	if (!$SUBS['KINOTO'] = getCinema())
		{
		Programa();
		return;
		}

	$SUBS['USAPREM'] = getBlock (4);
	$SUBS['PREMIERI'] = getBlock (1);
	
	$SUBS['ARTICLES'] = getBlock (6);
	
	index('_index_salon.htmlt');
	}

//function kinoPrem()
 function kinoPrem() {
	global $tbl_1d_films;
	global $SUBS;

	//$SUBS['PREMIERI'] = getBlock (1);
	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.tsPremiere

		FROM $tbl_1d_films

		WHERE 	$tbl_1d_films.tsPremiere <= (".week()."+604799)
				AND $tbl_1d_films.tsPremiere != ''
				AND $tbl_1d_films.Title != ''

		GROUP BY $tbl_1d_films.ID
				
		ORDER BY	($tbl_1d_films.tsPremiere >= ".week().") DESC,
			$tbl_1d_films.tsPremiere DESC ";
	$result = runQuery($query,'kinoPrem()','GET_WEEK');
	$mark = 0;
	$SUBS['PREMIERI'] = '';
	while ($row = db_fetch_row($result))
		{
		if ($mark == 0) $mark = $row[1];
		if ($mark != $row[1]) break;
		$SUBS['PREMIERI'] .= getFilm($row[0]);
		}
	
	$SUBS['WEEK'] = getWeek (1);
	
	$SUBS['PROGRAMA'] = getAgenda();
	$SUBS['USAPREM'] = getBlock (4);

	////----[Mrasnika's] Edition 17.10.2002
	//$SUBS['ARTICLES'] = getBlock (6);
	$SUBS['ARTICLES'] = getBlock (5);

	index('_index_kinoprem.htmlt');
	}


//function usaPrem()
 function usaPrem() {
	global $tbl_1d_films;
	global $SUBS;

	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.tsUsaPremiere

		FROM $tbl_1d_films

		WHERE 	$tbl_1d_films.tsUsaPremiere <= (".week()."+604799)
				AND $tbl_1d_films.tsUsaPremiere != ''
				AND $tbl_1d_films.OriginalTitle != ''

		GROUP BY $tbl_1d_films.ID
				
		ORDER BY	($tbl_1d_films.tsUsaPremiere >= ".week().") DESC,
			$tbl_1d_films.tsUsaPremiere DESC ";

	$result = runQuery($query,'usaPrem()','GET_WEEK');
	$mark = 0;
	$SUBS['PREMIERI'] = '';
	while ($row = db_fetch_row($result))
		{
		if ($mark == 0) $mark = $row[1];
		if ($mark != $row[1]) break;
		$SUBS['PREMIERI'] .= getFilm($row[0]);
		}

	//$SUBS['PREMIERI'] = getBlock (4);
	$SUBS['WEEK'] = getWeek (4);

	$SUBS['BGPREM'] = getBlock(1);

	$SUBS['ARTICLES'] = getBlock (5);
	
	index('_index_usaprem.htmlt');
	}

//function film()
 function film() {
	global $tbl_1d_pictures, $tbl_1d_films, $tbl_1d_videodvd;
	global $SUBS, $PARAM, $MSG;

	$SUBS['PREMIERI'] = getBlock (1);
	$SUBS['PROGRAMA'] = getAgenda();
	$SUBS['USAPREM'] = getBlock (4);
	$SUBS['ARTICLES'] = getBlock (6);

	////----[Mrasnika's] Edition 17.10.2002
	/*
	if (!$SUBS['FILM'] = getFilm($PARAM['id']))
		{
		frontPage();
		return;
		} 
	*/
	if (!$SUBS['FILM'] = getFilm($PARAM['id']))
		return;
		//samata funkciya schte otpechata pyrwata stranica
	
	index('_index_film.htmlt');
	}

//function video_dvd_Prem()
 function video_dvd_Prem($type='') {
	global $SUBS, $MSG;
	global $tbl_1d_pictures, $tbl_1d_videodvd, $tbl_1d_distr, $tbl_1d_films;
	
	////----[Mrasnika's] Edition 16.10.2002
	// prowerka na nowite paramentri
	if ($type=='') $type = 'video';

	$query = "SELECT	$tbl_1d_films.ID,
			
			$tbl_1d_films.Title,
			$tbl_1d_films.Director,
			$tbl_1d_films.Actors,
			$tbl_1d_films.Description,
			$tbl_1d_distr.Distributor,
			
			$tbl_1d_pictures.URL,
			$tbl_1d_pictures.Width,
			$tbl_1d_pictures.Height,
			
			$tbl_1d_films.Additional,
			$tbl_1d_films.Genre,
			
			$tbl_1d_videodvd.tsWhen,
			$tbl_1d_films.OriginalTitle,
			
			$tbl_1d_videodvd.ID

		FROM $tbl_1d_videodvd
		LEFT JOIN $tbl_1d_films
			ON $tbl_1d_films.ID = $tbl_1d_videodvd.FilmID
		LEFT JOIN $tbl_1d_distr
			ON $tbl_1d_videodvd.DistributorID = $tbl_1d_distr.ID
		LEFT JOIN $tbl_1d_pictures
			ON $tbl_1d_videodvd.ID = $tbl_1d_pictures.RefID
				AND $tbl_1d_pictures.RefType = '$type'

		WHERE 	$tbl_1d_videodvd.tsWhen <= (".week()."+604799)
				AND $tbl_1d_videodvd.Type = '$type'

		GROUP BY $tbl_1d_films.ID
		
		ORDER BY	($tbl_1d_videodvd.tsWhen >= ".week().") DESC,
			$tbl_1d_videodvd.tsWhen DESC,
			$tbl_1d_distr.Priority,
			$tbl_1d_films.Title";

	$result = runQuery($query,'video_dvd_Prem()','GET_VIDEO_PREMS');
	$path = getAdmSetting('UPLOAD_DIR');
	$mark = 0;
	while ($row = db_fetch_row($result))
		{
		if ($mark == 0) $mark = $row[11];
		if (week($mark) != week($row[11])) break;

		$SUBS['FILMID'] = $row[0];
		$SUBS['TITLE'] = htmlEncode($row[1]);
		
		////----[Mrasnika's] Edition 31.10.2002
		if ($row[2])
			$SUBS['DIRECTOR'] = htmlEncode($MSG[30018].$row[2])."<br>";
			else $SUBS['DIRECTOR'] = '';
		if ($row[3])
			$SUBS['ACTORS'] = htmlEncode($MSG[30019].$row[3])."<br>";
			else $SUBS['ACTORS'] = '';

		$SUBS['CAPTION'] = htmlEncode($row[4]);
		$SUBS['DISTR'] = htmlEncode($row[5]);
		
		$SUBS['THUMB'] = $path.$row[6];
		$SUBS['VWIDTH'] = $row[7];
		$SUBS['VHEIGHT'] = $row[8];
		
		if ($row[9])
			$SUBS['ADDITIONAL'] = htmlEncode($row[9]) . "<br>";
			else $SUBS['ADDITIONAL'] = '';
		if ($row[10])
			$SUBS['GENRE'] = htmlEncode($row[10]) . "<br>";
			else $SUBS['GENRE'] = '';
		if ($row[12])
			$SUBS['ORIGINAL'] = htmlEncode($row[12]) . "<br>";
			else $SUBS['ORIGINAL'] = '';
		$SUBS['ID'] = $row[13];

		//prowerka za oschte kartinki
		$query = "SELECT	$tbl_1d_pictures.URL,
				$tbl_1d_pictures.Width,
				$tbl_1d_pictures.Height,
				
				p1.URL,
				p1.Width,
				p1.Height
				
			FROM	$tbl_1d_pictures
			LEFT JOIN	$tbl_1d_pictures AS p1
				ON p1.RefID = $tbl_1d_pictures.ID
					AND p1.RefType = 'thumb'
			WHERE  $tbl_1d_pictures.RefID = $row[0]
				AND $tbl_1d_pictures.RefType = 'film'
			ORDER BY RAND()";

		$res = runQuery($query,'video_dvd_Prem()','GET_VIDEO_PICS');

		$SUBS['ALIGN'] = 'right';
		$SUBS['KARTINKI'] = '';
		
		////----[Mrasnika's] Edition 16.10.2002
		$SUBS['KARTINKI2'] = '';
		$top = 0;
		
		while ($r = db_fetch_row($res))
			{
			$SUBS['URL'] = $path.$r[0];
			$SUBS['WIDTH'] = $r[1];
			$SUBS['HEIGHT'] = $r[2];
			
			$SUBS['TURL'] = $path.$r[3];
			$SUBS['TWIDTH'] = $r[4];
			$SUBS['THEIGHT'] = $r[5];

			if ($top<2)
				{
				$top++;
				$SUBS['KARTINKI'] .= fileParse('_index_thumb.htmlt');
				} else {
				$SUBS['ALIGN'] = '';
				$SUBS['KARTINKI2'] .= ' '.fileParse('_index_thumb.htmlt');
				}
			}
		$SUBS['PREMIERI'] .= fileParse('_index_videodvd.htmlt');
		}
	//dispaly
	if ($type == 'video')
		$SUBS['WEEK'] = getWeek (2);
		else $SUBS['WEEK'] = getWeek (3);
	
	index("_index_".$type."prem.htmlt");
	}

//function getStatia()
 function getStatia($head=0) {
	global $tbl_1d_pictures,  $tbl_1d_article;
	global $SUBS, $MSG, $MONTHS, $PARAM;

	////----[Mrasnika's] Edition 21.10.2002
	$today = 1 + strToTime (date('d F Y'));
	$page = intval($PARAM['page']);

	$query = "SELECT	$tbl_1d_article.ID,
			$tbl_1d_article.Title,
			$tbl_1d_article.tsWhen
			
		FROM $tbl_1d_article
		
		WHERE	$tbl_1d_article.tsWhen <= $today

		ORDER BY ($tbl_1d_article.tsWhen >= ".week().") DESC,
			$tbl_1d_article.tsWhen DESC,
			$tbl_1d_article.Priority
		LIMIT $page, 10";
	$result = runQuery($query,'getStatia()','GET_2DOUBLE');
	
	////----[Mrasnika's] Edition 21.10.2002
	// wrong page
	if (!db_num_rows($result) && $page)
		{
		$PARAM['page'] = 0;
		if ($head==0)
			{
			getStatia();
			return;
			} else return getStatia($head);
		}
	
	$SUBS['CMD'] = "2double&page=$page";
	$days = 0;
	while ($row = db_fetch_row($result))
		{
		$SUBS['PRE'] = htmlEncode($row[1]);
		$SUBS['ID'] = htmlEncode($row[0]);
		$IDS[$row[0]] = $SUBS['PRE'];

		//$SUBS['ARTICLES'] .= fileParse('_index_block.htmlt');
		//$SUBS['BLOCKS'] = '';
		$SUBS['WHEN'] = date(' d ', $row[2]).$MONTHS[intval(date('m', $row[2]))].date(' Y', $row[2]);
		if ($when == $SUBS['WHEN'])
			{
			$SUBS['WHEN'] = '';
			$SUBS['BR'] = '';
			} else {
			if (!$when)
				$SUBS['BR'] = '';
				else $SUBS['BR'] = '<br>';
			
			$when = $SUBS['WHEN'];
			$days++;
			}

		$SUBS['BLOCKS'] .= fileParse('_index_block_row2.htmlt');
		}

	//get pictures
	if (is_array($IDS))
		$ids = join (',' , array_keys($IDS));
		else $ids = '0';

	$query = "SELECT	$tbl_1d_pictures.URL,
			$tbl_1d_pictures.Width,
			$tbl_1d_pictures.Height,
	
			p1.URL,
			p1.Width,
			p1.Height,
				
			$tbl_1d_pictures.RefID
		FROM	$tbl_1d_pictures
		LEFT JOIN $tbl_1d_pictures AS p1
			ON $tbl_1d_pictures.ID = p1.RefID
				AND p1.RefType = 'thumb'
		WHERE	$tbl_1d_pictures.RefID IN ($ids)
				AND $tbl_1d_pictures.RefType = 'article'
		ORDER BY RAND()";
	$res = runQuery($query,'getStatia()','GET_2DOUBLE_PICS');
	$SUBS['KARTINKI'] = '';
	$SUBS['ALIGN'] = 'center';
	
	$path = getAdmSetting('UPLOAD_DIR');
	$mark = 0;
	
	//$size = 2;
	$size = intval((count($IDS)+$days*2)/5);
		
	while ($r = db_fetch_row($res))
		{
		if ($mark == 0)
			$mark = $r[6];
			else if ($mark == $r[6])
				continue;
		if ($size == 0)
			break;
			else $size--;

		$SUBS['ALT'] = $IDS[$r[6]];

		$SUBS['URL'] = $path.$r[0];
		$SUBS['WIDTH'] = $r[1];
		$SUBS['HEIGHT'] = $r[2];

		$SUBS['TURL'] = $path.$r[3];
		$SUBS['TWIDTH'] = $r[4];
		$SUBS['THEIGHT'] = $r[5];
		$SUBS['KARTINKI'] .= " " . fileParse('_index_thumb.htmlt');
		}
	$SUBS['ARTICLES'] = fileParse('_index_block.htmlt');
	
	////----[Mrasnika's] Edition 21.10.2002
	// navigacia  
	$query = "SELECT Count(id)
		FROM $tbl_1d_article
		WHERE	$tbl_1d_article.tsWhen <= $today";
	$result = runQuery($query,'getStatia()','GET_ARTICLE_COUNT');
	$count = db_fetch_row($result);
	$SUBS['CMD'] = 'statia';

	//prev
	if (($page-10)>=0)
		{
		$SUBS['WHERE1'] = $page - 10;
		$SUBS['IMG1'] = '';
		} else {
		$SUBS['WHERE1'] = 0;
		$SUBS['IMG1'] = '2';
		}

	//next
	if (($page + 10) < $count[0])
		{
		$SUBS['WHERE2'] = $page + 10;
		$SUBS['IMG2'] = '';
		} else {
		$SUBS['WHERE2'] = 0;
		$SUBS['IMG2'] = '2';
		}

	$SUBS['ARTICLES'] .= fileParse('_index_statia_nav.htmlt');

	////----[Mrasnika's] Edition 20.10.2002
	if ($head==0)
		{
		$SUBS['PREMIERI'] = getBlock (1);
		$SUBS['VIDEOPREM'] = getBlock (2);
		$SUBS['DVDPREM'] = getBlock (3);
		$SUBS['USAPREM'] = getBlock (4);
		index('_index_statia.htmlt');
		} else return $SUBS['ARTICLES'];

	}

//function showArticle()
 function showArticle() {
	global $SUBS, $PARAM;

	////----[Mrasnika's] Edition 20.10.2002
	if (!$SUBS['MATERIAL'] = getArticle($PARAM['id'], week()))
		{
		frontPage();
		return;
		}

	$SUBS['MORE'] = getStatia(1);
	
	index('_index_show.htmlt');
	}

//function showCharts()
 function showCharts() {
 	global $SUBS, $PARAM, $MSG;
 	global $tbl_1d_charts, $tbl_1d_videodvd_charts, $tbl_1d_kino_charts, $tbl_1d_films, $tbl_1d_pictures;

	////----[Mrasnika's] Edition 26.10.2002
	// proweri tipa na klasaciyata
	$query = "SELECT Type
		FROM $tbl_1d_charts
		WHERE ID = ".dbQuote($PARAM['id']);
	$result = runQuery($query,'showCharts()','GET_CHART_TYPE');
	if ($row = db_fetch_row($result))
		$type = $row[0];

	switch ($type) {
		case 'videodvd' :
			$query = "SELECT	$tbl_1d_charts.ID,
					$tbl_1d_charts.Type,
		 			$tbl_1d_charts.Title AS a1,

		 			$tbl_1d_videodvd_charts.No,
		 			$tbl_1d_videodvd_charts.Type,
		 			$tbl_1d_videodvd_charts.Film,

		 			$tbl_1d_films.Title AS a2,
		 			$tbl_1d_films.OriginalTitle,
		 			$tbl_1d_films.Actors,

		 			'',
		 			'',
		 			$tbl_1d_videodvd_charts.Weeks,
		 			'',
		 			$tbl_1d_videodvd_charts.tsWhen,

		 			$tbl_1d_pictures.URL,
					$tbl_1d_pictures.Width,
					$tbl_1d_pictures.Height,

					p1.URL,
					p1.Width,
					p1.Height

		 		FROM $tbl_1d_charts
				LEFT JOIN $tbl_1d_videodvd_charts
					ON $tbl_1d_videodvd_charts.ChartID = $tbl_1d_charts.ID
						AND $tbl_1d_charts.Type = 'videodvd'

				LEFT JOIN $tbl_1d_films
					ON $tbl_1d_videodvd_charts.Film = $tbl_1d_films.ID
						AND $tbl_1d_videodvd_charts.Type = 'list'
						
				LEFT JOIN $tbl_1d_pictures
					ON $tbl_1d_pictures.RefID = $tbl_1d_films.ID
						AND $tbl_1d_pictures.RefType = 'film'
				LEFT JOIN $tbl_1d_pictures AS p1
					ON $tbl_1d_pictures.ID = p1.RefID
						AND p1.RefType = 'thumb'
				
				WHERE	($tbl_1d_videodvd_charts.ID IS NOT NULL)
						AND ($tbl_1d_videodvd_charts.tsWhen <= (".week()."+604800))
						AND $tbl_1d_charts.ID = ".dbQuote($PARAM['id'])."
				
				GROUP BY $tbl_1d_videodvd_charts.ID
				
				ORDER BY ($tbl_1d_videodvd_charts.tsWhen > ".week().") DESC,
					$tbl_1d_videodvd_charts.No,
					$tbl_1d_videodvd_charts.tsWhen DESC,
					RAND()";

			break;
		default :
		case 'kino' :
			$query = "SELECT	$tbl_1d_charts.ID,
					$tbl_1d_charts.Type,
		 			$tbl_1d_charts.Title AS a1,

		 			$tbl_1d_kino_charts.No,
		 			$tbl_1d_kino_charts.Type,
		 			$tbl_1d_kino_charts.Film,

		 			$tbl_1d_films.Title AS a2,
		 			$tbl_1d_films.OriginalTitle,
		 			$tbl_1d_films.Actors,

		 			$tbl_1d_kino_charts.BoxOffice,
		 			$tbl_1d_kino_charts.cumBoxOffice,
		 			$tbl_1d_kino_charts.Weeks,
		 			$tbl_1d_kino_charts.Screens,
		 			$tbl_1d_kino_charts.tsWhen,

		 			$tbl_1d_pictures.URL,
					$tbl_1d_pictures.Width,
					$tbl_1d_pictures.Height,

					p1.URL,
					p1.Width,
					p1.Height

		 		FROM $tbl_1d_charts
				LEFT JOIN $tbl_1d_kino_charts
					ON $tbl_1d_kino_charts.ChartID = $tbl_1d_charts.ID
						AND $tbl_1d_charts.Type = 'kino'

				LEFT JOIN $tbl_1d_films
					ON $tbl_1d_kino_charts.Film = $tbl_1d_films.ID
						AND $tbl_1d_kino_charts.Type = 'list'
						
				LEFT JOIN $tbl_1d_pictures
					ON $tbl_1d_pictures.RefID = $tbl_1d_films.ID
						AND $tbl_1d_pictures.RefType = 'film'
				LEFT JOIN $tbl_1d_pictures AS p1
					ON $tbl_1d_pictures.ID = p1.RefID
						AND p1.RefType = 'thumb'
				
				WHERE	($tbl_1d_kino_charts.ID IS NOT NULL)
						AND ($tbl_1d_kino_charts.tsWhen <= (".week()."+604800))
						AND $tbl_1d_charts.ID = ".dbQuote($PARAM['id'])."
				
				GROUP BY $tbl_1d_kino_charts.ID
				
				ORDER BY ($tbl_1d_kino_charts.tsWhen > ".week().") DESC,
					$tbl_1d_kino_charts.No,
					$tbl_1d_kino_charts.tsWhen DESC,
					RAND()";

			break;
		}

	$result = runQuery($query,'showCharts()','GET_CHARTS_INFO');

	$week = '0';
	$chart = '0';
	$path = getAdmSetting('UPLOAD_DIR');
	$SUBS['ALIGN'] = 'absmiddle';

	while ($row = db_fetch_row($result))
		{
		switch ($row[1]) {
			case 'videodvd' :
				$SUBS['CHART_TITLE'] = htmlEncode($row[2]);
				$SUBS['CHART_WEEK'] = showWeek($row[13]);
				
				if ($chart != $row[0])
					{	//pechatay zaglawie
					$SUBS['KLASACII'] .= fileParse('_index_charts_title.htmlt');

					$chart = $row[0];
					$week = $row[13];
					} else {
					if (week($week) != week($row[13]))
						continue;
					}

				$SUBS['NO'] = sprintf('%02d', $row[3]);
				$SUBS['ACTORS'] = '';
				if ($row['4'] != 'list')
					$SUBS['TITLE'] = htmlEncode($row[5]);
					else {
					$SUBS['FILMID'] = $row[5];
					if ($row[6])
						$SUBS['TITLE'] = htmlEncode($row[6]);
						else $SUBS['TITLE'] = htmlEncode($row[7]);
					$SUBS['ALT'] = $SUBS['TITLE'];
					$SUBS['TITLE'] = fileParse('_index_charts_link.htmlt');
					
					$SUBS['ACTORS'] = htmlEncode($row[8]);
					}
			
				$SUBS['WEEKS'] = $row[11];

				//kartinka, ako ima
				$SUBS['PIC'] = '';
				if ($row[14])
					{
					$SUBS['URL'] = $path.$row[14];
					$SUBS['WIDTH'] = $row[15];
					$SUBS['HEIGHT'] = $row[16];
		
					$SUBS['TURL'] = $path.$row[17];
					$SUBS['TWIDTH'] = $row[18];
					$SUBS['THEIGHT'] = $row[19];
					$SUBS['PIC'] =  fileParse('_index_thumb.htmlt');
					}

				$SUBS['KLASACII'] .= fileParse('_index_charts_videodvd.htmlt');

				break;

			default :
			case 'kino' :
				$SUBS['CHART_TITLE'] = htmlEncode($row[2]);
				$SUBS['CHART_WEEK'] = showWeek($row[13]);
				
				if ($chart != $row[0])
					{	//pechatay zaglawie
					$SUBS['KLASACII'] .= fileParse('_index_charts_title.htmlt');

					$chart = $row[0];
					$week = $row[13];
					} else {
					if (week($week) != week($row[13]))
						continue;
					}

				$SUBS['NO'] = sprintf('%02d', $row[3]);
				$SUBS['ACTORS'] = '';
				if ($row['4'] != 'list')
					$SUBS['TITLE'] = htmlEncode($row[5]);
					else {
					$SUBS['FILMID'] = $row[5];
					if ($row[6])
						$SUBS['TITLE'] = htmlEncode($row[6]);
						else $SUBS['TITLE'] = htmlEncode($row[7]);
					$SUBS['ALT'] = $SUBS['TITLE'];
					$SUBS['TITLE'] = fileParse('_index_charts_link.htmlt');
					
					$SUBS['ACTORS'] = htmlEncode($row[8]);
					}

				$SUBS['BO'] = '';
				while (ereg('([0-9]{1,3}$)', $row[9], $R))
					{
					if (strlen($R[1])<3)
						$SUBS['BO'] = "$R[1]" . $SUBS['BO'];
						else $SUBS['BO'] = ",$R[1]" . $SUBS['BO'];
					$row[9] = ereg_replace('([0-9]{0,3}$)', '', $row[9]);
					}
				$SUBS['BO'] = ereg_replace('^,','',$SUBS['BO']);

				$SUBS['CBO'] = '';
				while (ereg('([0-9]{1,3}$)', $row[10], $R))
					{
					if (strlen($R[1])<3)
						$SUBS['CBO'] = "$R[1]" . $SUBS['CBO'];
						else $SUBS['CBO'] = ",$R[1]" . $SUBS['CBO'];
					$row[10] = ereg_replace('([0-9]{0,3}$)', '', $row[10]);
					}
				$SUBS['CBO'] = ereg_replace('^,','',$SUBS['CBO']);
				
				$SUBS['WEEKS'] = $row[11];
				$SUBS['SCREENS'] = $row[12];

				//kartinka, ako ima
				$SUBS['PIC'] = '';
				if ($row[14])
					{
					$SUBS['URL'] = $path.$row[14];
					$SUBS['WIDTH'] = $row[15];
					$SUBS['HEIGHT'] = $row[16];
		
					$SUBS['TURL'] = $path.$row[17];
					$SUBS['TWIDTH'] = $row[18];
					$SUBS['THEIGHT'] = $row[19];
					$SUBS['PIC'] =  fileParse('_index_thumb.htmlt');
					}

				$SUBS['KLASACII'] .= fileParse('_index_charts_kino.htmlt');

				break;
			}
		}

	////----[Mrasnika's] Edition 24.10.2002
	// $SUBS['MORE'] = getBlock(1) . getBlock(2) . getBlock(3);
	
	if ($SUBS['KLASACII'])
		$SUBS['MORE'] = getBlock(7);
		else {
		$SUBS['CHARTS2'] = '<br>'.getBlock(7,1).'<br><br>';
		$SUBS['MORE'] = getBlock(1) . getBlock(2) . getBlock(3) . getBlock(4);
		}
 
 	index('_index_charts.htmlt');
 	}

//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
//compatibility
//if (!$SUBS['ACTION'] = $_SERVER['PATH_INFO']) $SUBS['ACTION'] = $_SERVER['SCRIPT_NAME'];
$SUBS['ACTION'] = '/index.php';

if (init())	{

	//podgotwi kolonite
	include ('includes/left_column.php');



	switch ($PARAM['cmd']) {
		case 'kinoprem' :
			kinoPrem();
			break;

		case 'usaprem' :
			usaPrem();
			break;

		case 'video' :
		case 'videoprem' :
			video_dvd_Prem('video');
			break;

		case 'dvd' :
		case 'dvdprem' :
			//dvdPrem();
			video_dvd_Prem('dvd');
			break;

		case 'statia' :
			getStatia();
			break;
			
		case 'ost' :
		case 'namm' :
		case 'sound' :
		case 'zad' :
		case 'star' :
		case '2double' :
			showArticle();
			break;

		case 'film' :
			film();
			break;
			
		case 'city':
			Grad();
			break;

		case 'cinema':
			Kino();
			break;

		case 'programa' :
			Programa();
			break;

		case 'news' :
			News();
			break;
			
		
		case 'charts' :
		case 'klasacia' :
			showCharts();	//klasacii
			break;

		default :	//default;
			frontPage();
		}

	halt ();
	}
?>