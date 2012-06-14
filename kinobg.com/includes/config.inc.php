<?php /**/ ?><?php
// ------------------------------------------------------------------
// config.inc.php
// ------------------------------------------------------------------
// Copyright (c) 2001 Dreamware Technologies
// http://www.dreamwaretech.com
// Definitions of script configuration variables and constants.
//

// Debug version?
 define ("DEBUG", "1");
 define ("AUDIT", "1");
 
// admin settings
$ADMIN_SETTINGS['MIN_USERNAME_LEN'] = 4;
$ADMIN_SETTINGS['MAX_USERNAME_LEN'] = 12;
$ADMIN_SETTINGS['MIN_PASSWORD_LEN'] = 6;
$ADMIN_SETTINGS['RESULT_PER_PAGE'] = 10;

$ADMIN_SETTINGS["IMAGES_DIR"] = "images";
$ADMIN_SETTINGS["UPLOAD_DIR"] = "upload/";
$ADMIN_SETTINGS["TEMPLATES_DIR"] = "templates/";
$ADMIN_SETTINGS["EMAIL_TEMPLATES_DIR"] = "emails/";
$ADMIN_SETTINGS["TEMPORARY_DIR"] = "temp/";
$ADMIN_SETTINGS["BACKUP_DIR"] = "backup/";

 $ADMIN_SETTINGS ['IMG_WIDTH'] = 641;
 $ADMIN_SETTINGS ['IMG_HEIGHT'] = 481;

 $ADMIN_SETTINGS ['THUMB_WIDTH'] = 83;
 $ADMIN_SETTINGS ['THUMB_HEIGHT'] = 61;
 $ADMIN_SETTINGS ['THUMB_LEFT'] = 7;
 $ADMIN_SETTINGS ['THUMB_TOP'] = 7;

 $ADMIN_SETTINGS ['VIDEO_WIDTH'] = 70;
 $ADMIN_SETTINGS ['VIDEO_HEIGHT'] = 120;

 $ADMIN_SETTINGS ['DVD_WIDTH'] = 70;
 $ADMIN_SETTINGS ['DVD_HEIGHT'] = 120;

 $ADMIN_SETTINGS ['ARTICLE_WIDTH'] = 720;
 $ADMIN_SETTINGS ['ARTICLE_HEIGHT'] = 540;

 $ADMIN_SETTINGS ['COLUMN_LEN'] = 100;
 $ADMIN_SETTINGS ['PERMANENT_LIMIT'] = 10;



// Database info
$dbhost = 'localhost';
$dbport = '3306';
$dbname = '1double_new';
$dbuser = 'root';
$dbpass = '';

// Table names
$tbl_1d_admins = 'tbl_admin';
$tbl_1d_cities = 'tbl_city';
$tbl_1d_cinemas = 'tbl_cinema';
$tbl_1d_agenda = 'tbl_agenda';
$tbl_1d_films = 'tbl_film_info';
$tbl_1d_pictures = 'tbl_pictures';
$tbl_1d_agenda = 'tbl_agenda';

$tbl_1d_distr = 'tbl_video_dvd_distr';
$tbl_1d_videodvd = 'tbl_video_dvd_info';

$tbl_1d_article = 'tbl_articles';

$tbl_1d_charts = 'tbl_charts';
$tbl_1d_kino_charts = 'tbl_kino_charts';
$tbl_1d_videodvd_charts = 'tbl_videodvd_charts';

// Log file path:
$debugLogFile = "logs/debug.log";
$adminLogFile = "logs/unauthorized.access";
?>