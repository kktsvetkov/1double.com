<?php /**/ ?><?php
// ------------------------------------------------------------------
// cache.inc.php
// ------------------------------------------------------------------
// keshirane na stranicite
//

class Cache {

	var $prefix = 'cache_';
	var $dir = './cache2';
	var $expire = 3600;

	var $OK = 0;
	var $name = '';
	var $force = '';

//constructor
function Cache() {

	clearstatcache();

	if (func_num_args()>0) {
		$this->prefix = func_get_arg(0);
		}
	if (func_num_args()>1) {
		$this->dir = func_get_arg(1);
		}
	if (func_num_args()>2) {
		$this->expire = func_get_arg(2);
		}

	//check dir
	$this->OK = 1;
	if (!file_exists($this->dir)) {
		$this->OK = mkdir($this->dir, 0777);
		} else {
		$this->OK = is_writable($this->dir) && is_readable($this->dir);
		}

	$this->OK &= !preg_match('~google|bot~', $_SERVER['HTTP_USER_AGENT']);

	//start caching
	if ($this->OK) {
		$this->Start();
		}
	}

//funstion start
function Start() {

	//no cache for post
	if (strToUpper($_SERVER['REQUEST_METHOD'])=='POST') {
		return;
		}

	//no cache for sessions
	if (count($_SESSION)) {
		return;
		} else {
		@session_destroy();
		}

	//construct cache name
	$this->name = $this->prefix
		.rawUrlEncode($_SERVER['SCRIPT_FILENAME'])
		.rawUrlEncode($_SERVER['QUERY_STRING'])
		.'.hTm';

	//check for cache
	if (!file_exists($this->dir . '/' . $this->name)) {
		ob_start();
		} else {
		$STAT = @stat($this->dir . '/' . $this->name);

		if ((($STAT[9] + $this->expire) > time()) && ($STAT[7]>0)) {
			echo "<!--start of cached version (",
				date('D, M-d-Y H:i:s', $STAT[9]), ")-->\n";
			readfile($this->dir . '/' . $this->name);
			die("\n<!--end of cached version-->");
			} else {
			$this->force = $this->dir . '/' . $this->name;
			echo "<!--detected expired cached version (" .date('D, M-d-Y H:i:s', $STAT[9]). ")-->\n";
			}
		}
	}

//funstion end
function End() {

	//purge forced version
	if ($this->force) {
		@unlink($this->force);
		}

	$length = ob_get_length();
	$cache = ob_get_contents();

	if (!($this->OK && $this->name && $length)) {
		return;
		}

	//write cache
	if ($fp = @fopen($this->dir . '/' . $this->name,'w+')) {
		fwrite($fp, $cache);
		fclose($fp);
		}

	//validate cache
	if ($length != filesize($this->dir . '/' . $this->name)) {
		//@unlink($this->dir . '/' . $this->name);
		}
	}

//funstion force output
function Force() {
	if (!file_exists($this->dir . '/' . $this->name)) {

		if ($cache_dir = opendir($this->dir)) {
			while (false !== ($file = readdir($cache_dir))) {
				if (!ereg('^'.$this->prefix, $file)) continue;
				break;
				}
			closedir($cache_dir);
			}
		$forced_cache = $file;
		} else {
		$forced_cache = $this->name;
		}

	echo "<!--force cached version-->\n";
	readfile($this->dir . '/' . $forced_cache);
	die("\n<!--end of cached version-->");
	}
 }
