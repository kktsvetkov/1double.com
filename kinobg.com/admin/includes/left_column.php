<?php /**/ ?><?
	//kinopremiery
	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.Title,
			$tbl_1d_films.tsPremiere

		FROM $tbl_1d_films

		WHERE 	$tbl_1d_films.tsPremiere <= (".week()."+604799)
				AND $tbl_1d_films.Title != ''
				AND $tbl_1d_films.tsPremiere != ''

		ORDER BY	($tbl_1d_films.tsPremiere >= ".week().") DESC,
			$tbl_1d_films.tsPremiere DESC,
			$tbl_1d_films.Title";

	$result = runQuery($query,'index.php()','GET_KINO_PREMS');
	$SUBS['CMD'] = 'film';
	$mark = 0;
	while ($row = db_fetch_row($result))
		{	//premieri
		if ($mark == 0) $mark = $row[2];
		if (week($mark) != week($row[2])) break;

		$SUBS['ID'] = $row[0];
		$SUBS['TITLE'] = column($row[1]);
		$SUBS['KINO'] .= fileParse('_index_kino.htmlt');
		}

	//programa
	$query = "SELECT	$tbl_1d_cities.ID,
			$tbl_1d_cities.City

		FROM $tbl_1d_cities
		LEFT JOIN $tbl_1d_cinemas
			ON $tbl_1d_cities.ID = $tbl_1d_cinemas.CityID
		LEFT JOIN $tbl_1d_agenda
			ON $tbl_1d_cinemas.ID = $tbl_1d_agenda.CinemaID
			
		WHERE	$tbl_1d_agenda.CinemaID IS NOT NULL
				AND ($tbl_1d_agenda.tsWhen <= (".week()."+604799))
				AND $tbl_1d_cities.Active = 'yes'

		GROUP BY $tbl_1d_cities.ID

		ORDER BY $tbl_1d_cities.Priority ";

	$result = runQuery($query,'index.php()','GET_KINO_CITIES');
	$SUBS['CMD'] = 'city';
	while ($row = db_fetch_row($result))
		{	//gradowe
		$SUBS['ID'] = $row[0];
		$SUBS['TITLE'] = column($row[1]);
		$SUBS['GRAD'] .= fileParse('_index_kino.htmlt');
		}

	//video
	$query = "SELECT	$tbl_1d_videodvd.ID,
			$tbl_1d_films.Title,
			$tbl_1d_videodvd.tsWhen

		FROM $tbl_1d_videodvd
		LEFT JOIN $tbl_1d_distr
			ON $tbl_1d_videodvd.DistributorID = $tbl_1d_distr.ID
		LEFT JOIN $tbl_1d_films
			ON $tbl_1d_films.ID = $tbl_1d_videodvd.FilmID

		WHERE 	$tbl_1d_videodvd.Type = 'video'
				AND ($tbl_1d_videodvd.tsWhen <= (".week()."+604799))

		ORDER BY	($tbl_1d_videodvd.tsWhen >= ".week().") DESC,
			$tbl_1d_videodvd.tsWhen DESC,
			$tbl_1d_distr.Priority,
			$tbl_1d_videodvd.ID";

	$result = runQuery($query,'index.php()','GET_VIDEOS');
	$SUBS['CMD'] = 'videoprem';
	$mark = 0;
	while ($row = db_fetch_row($result))
		{	//video zaglawiya
		if ($mark == 0) $mark = $row[2];
		if (week($row[2]) != week($mark)) break;

		$SUBS['ID'] = $row[0];
		$SUBS['TITLE'] = column($row[1]);
		$SUBS['VIDEO'] .= fileParse('_index_first_row.htmlt');
		}

	//dvd
	$query = "SELECT	$tbl_1d_videodvd.ID,
			$tbl_1d_films.Title,
			$tbl_1d_videodvd.tsWhen

		FROM $tbl_1d_videodvd
		LEFT JOIN $tbl_1d_distr
			ON $tbl_1d_videodvd.DistributorID = $tbl_1d_distr.ID
		LEFT JOIN $tbl_1d_films
			ON $tbl_1d_films.ID = $tbl_1d_videodvd.FilmID

		WHERE 	$tbl_1d_videodvd.Type = 'dvd'
				AND ($tbl_1d_videodvd.tsWhen <= (".week()."+604799))

		ORDER BY	($tbl_1d_videodvd.tsWhen >= ".week().") DESC,
			$tbl_1d_videodvd.tsWhen DESC,
			$tbl_1d_distr.Priority,
			$tbl_1d_videodvd.ID";

	$result = runQuery($query,'index.php()','GET_DVDs');
	$SUBS['CMD'] = 'dvd';
	$mark = 0;
	while ($row = db_fetch_row($result))
		{	//dvd zaglawiya
		if ($mark == 0) $mark = $row[2];
		if (week($row[2]) != week($mark)) break;

		$SUBS['ID'] = $row[0];
		$SUBS['TITLE'] = column($row[1]);
		$SUBS['DVD'] .= fileParse('_index_first_row.htmlt');
		}

	//parwa stranica
	//$today = 1 + strToTime (date('d').' '.$MONTHS2[date('m')].' '.date('Y'));
	$today = 1 + strToTime (date('d F Y'));
	$query = "SELECT	$tbl_1d_article.ID,
			$tbl_1d_article.Title,
			$tbl_1d_article.tsWhen
		FROM $tbl_1d_article
		WHERE	$tbl_1d_article.tsWhen <= $today

		ORDER BY	($tbl_1d_article.tsWhen >= $today) DESC,
			$tbl_1d_article.tsWhen DESC,
			$tbl_1d_article.Priority
		LIMIT 0, 5";

	$result = runQuery($query,'index.php()','GET_SOUND');
	$SUBS['CMD'] = '#';
	$mark = 0;
	$First = '0';
	while ($row = db_fetch_row($result))
		{	//statii ot parwa stranica
		if ($mark == 0) $mark = $row[2];
		if (week($row[2]) != week($mark)) break;

		$SUBS['ID'] = $row[0];
		$First .= ",$row[0]";
		$SUBS['TITLE'] = column($row[1]);
		$SUBS['FIRST1'] .= fileParse('_index_first_row.htmlt');
		}
	$SUBS['FIRST'] .= fileParse('_index_first.htmlt');

	//statii
	$query = "SELECT	$tbl_1d_article.ID,
			$tbl_1d_article.Title,
			$tbl_1d_article.tsWhen
		FROM $tbl_1d_article
		WHERE $tbl_1d_article.tsWhen <= $today
			AND $tbl_1d_article.ID NOT IN ($First)

		ORDER BY	($tbl_1d_article.tsWhen >= ".week().") DESC,
			$tbl_1d_article.tsWhen DESC,
			$tbl_1d_article.Priority
		LIMIT 0, ".getAdmSetting('PERMANENT_LIMIT');

	$result = runQuery($query,'index.php()','GET_STATII');
	$SUBS['CMD'] = 'namm';
	$mark = 0;
	while ($row = db_fetch_row($result))
		{	//novini
		if ($mark == 0) $mark = $row[2];
		if (week($row[2]) != week($mark)) continue;

		$SUBS['ID'] = $row[0];
		$SUBS['TITLE'] = column($row[1]);
		$SUBS['AKTUALNO'] .= fileParse('_index_kino.htmlt');
		}


	//usa
	$query = "SELECT	$tbl_1d_films.ID,
			$tbl_1d_films.OriginalTitle,
			$tbl_1d_films.tsUsaPremiere
			
		FROM $tbl_1d_films
		
		WHERE 	$tbl_1d_films.tsUsaPremiere <= (".week()."+604799)
				AND $tbl_1d_films.OriginalTitle != ''
				AND $tbl_1d_films.tsUSaPremiere != ''
		
		ORDER BY	($tbl_1d_films.tsUsaPremiere >= ".week().") DESC,
			$tbl_1d_films.tsUsaPremiere DESC,
			$tbl_1d_films.OriginalTitle ";

	$result = runQuery($query,'index.php()','GET_USA');
	$SUBS['CMD'] = 'film';
	$mark = 0;
	while ($row = db_fetch_row($result))
		{	//usa zaglaviya
		if ($mark == 0) $mark = $row[2];
		if (week($mark) != week($row[2])) break;

		$SUBS['ID'] = $row[0];
		$SUBS['TITLE'] = column($row[1]);
		$SUBS['USA'] .= fileParse('_index_kino.htmlt');
		}

	//charts
	$query = "SELECT	$tbl_1d_charts.ID,
			$tbl_1d_charts.Title
			
		FROM $tbl_1d_charts
		LEFT JOIN $tbl_1d_kino_charts
			ON $tbl_1d_kino_charts.ChartID = $tbl_1d_charts.ID
				AND $tbl_1d_charts.Type = 'kino'
		LEFT JOIN $tbl_1d_videodvd_charts
			ON $tbl_1d_videodvd_charts.ChartID = $tbl_1d_charts.ID
				AND $tbl_1d_charts.Type = 'videodvd'
		
		WHERE ($tbl_1d_kino_charts.ID IS NOT NULL)
			OR ($tbl_1d_videodvd_charts.ID IS NOT NULL)
		
		GROUP BY $tbl_1d_charts.ID
		ORDER BY	$tbl_1d_charts.Type ";

	$result = runQuery($query,'index.php()','GET_CHARTS');
	$SUBS['CMD'] = 'klasacia';
	while ($row = db_fetch_row($result))
		{	//imenata na klasaciite
		$SUBS['ID'] = $row[0];
		$SUBS['TITLE'] = column($row[1]);
		$SUBS['CHARTS'] .= fileParse('_index_kino.htmlt');
		}

?>