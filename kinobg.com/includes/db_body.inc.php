<?php /**/ ?><?php
// ------------------------------------------------------------------
// mysql_body.inc.php
// ------------------------------------------------------------------
// Copyright (c) 2001 Dreamware Technologies
// http://www.dreamwaretech.com
// Definitions of PHP functions for mysql for use with the database
//  independent shell.
// Last Revision: 2001/07/04 20:27 by E. 
//
// Exported Functions:
// 
// db_connect($host, $username, $password);
// db_close();
// db_pconnect($host, $username, $password);
// db_data_seek($result, $rowno);
// db_fetch_array($result);
// db_fetch_object($result);
// db_fetch_field($result);
// db_fetch_row($result);
// db_field_seek($result, $fldnum);
// db_free_result($result);
// db_num_fields($result);
// db_num_rows($result);
// db_query($statement, $connection);
// db_result($result, $row,$fld);
// db_select_db($db, $connection);
// 
// functions act as the according functions for mysql access through PHP, i.e.
// db_connect($host, $username, $password); = mysql_connect($host, $username, $password);
// 


//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_connect($host, $username, $password) {
	if ($r = mysql_connect($host, $username, $password)) {
		mysql_set_charset('cp1251', $r);
		}
	return $r;
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_close() {
  return mysql_close();
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_pconnect($host, $username, $password) {
	return mysql_pconnect($host, $username, $password);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_data_seek($result, $rowno) {
	return mysql_data_seek($result, $rowno);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_fetch_array($result) {
	return mysql_fetch_array($result);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_fetch_object($result) {
	return mysql_fetch_object($result);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_fetch_field($result) {
	return mysql_fetch_field($result);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_fetch_row($result) {
	return mysql_fetch_row($result);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_field_seek($result, $fldnum) {
	return mysql_field_seek($result, $fldnum);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_free_result($result) {
	return mysql_free_result($result);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_num_fields($result) {
	return mysql_num_fields($result);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_num_rows($result) {
	return mysql_num_rows($result);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_query($statement, $connection) {
	return mysql_query($statement, $connection);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_result($result, $row, $fld) {
	return mysql_result($result, $row, $fld);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
function db_select_db($db, $connection) {
	return mysql_select_db($db, $connection);
}
//:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::


////---- [Mrasnika's] Edition
function db_errno($dbh) {
	return @mysql_errno($dbh);
	}
function db_error($dbh) {
	return @mysql_error($dbh);
	}

////---- database backup function
function db_list_tables($database, $db) {
	return mysql_list_tables($database, $db);
	}
function db_affected_rows($dbh) {
	return mysql_affected_rows($dbh);
	}
function db_tablename($result, $i) {
	return mysql_tablename($result, $i);
	}
function db_list_fields($database, $table, $dbh) {
	return mysql_list_fields($database, $table, $dbh);
	}
function db_field_name($result, $i) {
	return mysql_field_name($result, $i);
	}

////----from PHPmyAdmin

function split_sql_file(&$ret, $sql, $release = MYSQL_INT_VERSION) {

	$sql               = trim($sql);
	$sql_len           = strlen($sql);
	$char              = '';
	$string_start      = '';
	$in_string         = FALSE;

	for ($i = 0; $i < $sql_len; ++$i)
    		{
        		$char = $sql[$i];

		// We are in a string, check for not escaped end of strings except for
		// backquotes that can't be escaped
		if ($in_string)
			{
            			for (;;)	{
                				$i         = strpos($sql, $string_start, $i);
                				// No end of string found -> add the current substring to the
                				// returned array
                				if (!$i)	{
                    				$ret[] = $sql;
                    				return TRUE;
                					}
                					// Backquotes or no backslashes before quotes: it's indeed the
                					// end of the string -> exit the loop
                					else if ($string_start == '`' || $sql[$i-1] != '\\')
						{
						$string_start      = '';
                    					$in_string         = FALSE;
                    					break;
                						} else {	// one or more Backslashes before the presumed end of string...
                    					// ... first checks for escaped backslashes
                    					$j                     = 2;
						$escaped_backslash     = FALSE;
                    					while ($i-$j > 0 && $sql[$i-$j] == '\\')
	                    					{
                        						$escaped_backslash = !$escaped_backslash;
                        						$j++;
                    						}
                    					// ... if escaped backslashes: it's really the end of the
                    					// string -> exit the loop
                    					if ($escaped_backslash)
                    						{
							$string_start  = '';
							$in_string     = FALSE;
							break;
							} else $i++;	// ... else loop
						} // end if...elseif...else
				} // end for
			} // end if (in string)

		// We are not in a string, first check for delimiter...
		else if ($char == ';')
			{
            			// if delimiter found, add the parsed part to the returned array
			$ret[]      = substr($sql, 0, $i);
			$sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
			$sql_len    = strlen($sql);
			if ($sql_len)
				$i      = -1;
				else return TRUE;	// The submited statement(s) end(s) here
			} // end else if (is delimiter)

        			// ... then check for start of a string,...
        			else if (($char == '"') || ($char == '\'') || ($char == '`'))
        				{
            				$in_string    = TRUE;
            				$string_start = $char;
        				} // end else if (is start of string)

				// ... for start of a comment (and remove this comment if found)...
				else if ($char == '#' || ($char == ' ' && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--'))
					{
					// starting position of the comment depends on the comment type
					$start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
					// if no "\n" exits in the remaining string, checks for "\r"
					// (Mac eol style)
					$end_of_comment   = (strpos(' ' . $sql, "\012", $i+2))
						? strpos(' ' . $sql, "\012", $i+2)
						: strpos(' ' . $sql, "\015", $i+2);
					if (!$end_of_comment)
						{
						// no eol found after '#', add the parsed part to the returned
						// array and exit
						$ret[]   = trim(substr($sql, 0, $i-1));
						return TRUE;
						} else {
						$sql     = substr($sql, 0, $start_of_comment).ltrim(substr($sql, $end_of_comment));
						$sql_len = strlen($sql);
						$i--;
						} // end if...else
					} // end else if (is comment)
        					// ... and finally disactivate the "/*!...*/" syntax if MySQL < 3.22.07
        					else if ($release < 32270 && ($char == '!' && $i > 1  && $sql[$i-2] . $sql[$i-1] == '/*'))
        						{
						$sql[$i] = ' ';
						} // end else if
	} // end for

	// add any rest to the returned array
	if (!empty($sql) && ereg('[^[:space:]]+', $sql))
		{
		$ret[] = $sql;
		}

	return TRUE;
}


?>