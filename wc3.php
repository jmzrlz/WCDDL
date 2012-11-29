<?php
// WarezCoders DDL Script 3
// Coded by JmZ
// Thanks to my partner & friend since 2004, Sickness, for coding with me all these years
// Thanks to the following people for being around:
// Whoo, Costa, Phaze, Kurupt, Corrup, Katz, Ultra, Wau, Ultima, Tippie
// the list goes on.
// I coded this in widescreen so expect long lines

define('WCDDL_GUTS', 1337);
require "./wcfg.php";
foreach(Core::load()->getModules() as $module)
	include $module['path'];
Core::load()->executeHook('init');

class DatabaseRaw {
	protected $val;
	public function __construct($val) {
		$this->val = $val;
	}
	public function __toString() {
		return $this->val;
	}
}

class Database {
	private static $db;
	private static $log = array();
	private static $logTime = 0;

	public static function load() {
		if(empty(self::$db))
			try {
				self::$db = new PDO(WCDDL_DB_DSN, WCDDL_DB_USER, WCDDL_DB_PASS);
			} catch(PDOException $e) {
				die('Database problems, try again in a few minutes.');
			}
		return self::$db;
	}

	public static function quickColumn($query, $args=array()) {
		Core::load()->executeHook('DatabaseColumn', array(&$query, &$args));
		if(!$c = self::query($query, $args))
			return false;
		return $c->fetchColumn();
	}

	public static function quickRowObject($class, $query, $args=array()) {
		Core::load()->executeHook('DatabaseRowObject', array(&$class, &$query, &$args));
		if(!$c = self::query($query, $args))
			return false;
		return $c->fetchObject($class);
	}

	public static function quickRowObjects($class, $query, $args=array()) {
		Core::load()->executeHook('DatabaseRowObjects', array(&$class, &$query, &$args));
		if(!$c = self::query($query, $args))
			return false;
		return $c->fetchAll(PDO::FETCH_CLASS, $class);
	}

	public static function quickExecute($query, $args=array(), $id = false) {
		Core::load()->executeHook('DatabaseExecute', array(&$query, &$args));
		if(!$c = self::query($query, $args))
			return false;
		return $id ? self::load()->lastInsertId() : $c->rowCount();
	}

	public static function quickRow($query, $args=array()) {
		Core::load()->executeHook('DatabaseRow', array(&$query, &$args));
		if(!$c = self::query($query, $args))
			return false;
		return $c->fetch();
	}

	public static function quickRows($query, $args=array()) {
		Core::load()->executeHook('DatabaseRows', array(&$query, &$args));
		if(!$c = self::query($query, $args))
			return false;
		return $c->fetchAll();
	}

	public static function query($query, $args=array()) {
		$d = self::load();
		$start = microtime(true);
		if(!$q = $d->prepare($query))
			return false;
		if(!$q->execute($args))
			return false;
		$end = microtime(true)-$start;
		self::$log[] = array($query, $args, $end);
		self::$logTime += $end;
		return $q;
	}

	public static function queryLog() {
		return self::$log;
	}

	public static function queryLogCount() {
		return count(self::$log);
	}

	public static function queryLogTime() {
		return self::$logTime;
	}

	public static function insert($table, $map, $id = false) {
		// Paranoia mode again
		$table = preg_replace('#[^\w\-]+#', '', $table);
		Core::load()->executeHook('DatabaseInsert', array(&$table, &$map));
		$query = 'INSERT INTO ' . $table . ' (' . implode(', ', array_keys($map)) . ') VALUES (';
		$params = array();
		foreach($map as $field => $value) {
			if($value instanceof DatabaseRaw) {
				$params[] = (string) $value;
				unset($map[$field]);
				continue;
			}
			$params[] = ':' . $field;
		}
		$query .= implode(', ', $params) . ')';
		if(!$c = self::query($query, $map))
			return false;
		return $id ? self::load()->lastInsertId() : $c->rowCount();
	}

	public static function delete($table, $map = array()) {
		$table = preg_replace('#[^\w\-]+#', '', $table);
		Core::load()->executeHook('DatabaseDelete', array(&$table, &$map));
		$query = 'DELETE FROM ' . $table;
		if(!empty($map)) {
			$query .= ' WHERE ';
			$where = array();
			foreach($map as $field => $value) {
				if($value instanceof DatabaseRaw) {
					$where[] = $field . ' = ' . ((string) $value);
					unset($map[$field]);
					continue;
				}
				$where[] = $field . ' = :' . $field;
			}
			$query .= implode(' AND ', $where);
		}
		if(!$c = self::query($query, $map))
			return false;
		return $c->rowCount();
	}

	public static function update($table, $map, $criteria = array()) {
		$table = preg_replace('#[^\w\-]+#', '', $table);
		Core::load()->executeHook('DatabaseUpdate', array(&$table, &$map));
		$query = 'UPDATE ' . $table . ' SET ';
		$params = array();
		foreach($map as $field => $value) {
			if($value instanceof DatabaseRaw) {
				$params[] = $field . ' = ' . ((string) $value);
				unset($map[$field]);
				continue;
			}
			$params[] = $field . ' = :' . $field;
		}
		$query .= implode(', ', $params);
		if(!empty($criteria)) {
			$query .= ' WHERE ';
			$params = array();
			foreach($criteria as $field => $value) {
				if($value instanceof DatabaseRaw) {
					$params[] = $field . ' = ' . ((string) $value);
					unset($criteria[$field]);
					continue;
				}
				$params[] = $field . ' = :' . $field;
			}
			$query .= implode(' AND ', $params);
		}
		$map = array_merge($map, $criteria);
		if(!$c = self::query($query, $map))
			return false;
		return $c->rowCount();
	}
}

class Core {
	private static $instance;
	private $templateVariables = array();
	private $configCache = array();
	protected $hookList = array();

	public static function load() {
		if(empty(self::$instance))
			self::$instance = new Core;
		return self::$instance;
	}

	// If this function causes a vuln, it's your fault
	// never pass properties which shouldn't be
	// altered by the user
	public function mapRequest($object, $props) {
		if(!class_exists($object))
			return false;
		if(!is_array($props))
			return false;
		$return = new $object;
		foreach($props as $prop) {
			if(!empty($_GET[$prop]))
				$return->$prop = $_GET[$prop];
			elseif(!empty($_POST[$prop]))
				$return->$prop = $_POST[$prop];
		}
		return $return;
	}

	public function templateVar($name, $val=null) {
		if(strpos($name, ' ') !== false)
			return false;
		if(!is_null($val))
			$this->templateVariables[$name] = $val;
		return array_key_exists($name, $this->templateVariables) ? $this->templateVariables[$name] : false;
	}

	public function config($name, $val=null, $group=null) {
		if(empty($val)) {
			if(in_array($name, $this->configCache))
				$d = $this->configCache[$name];
			elseif(!$d = Database::quickColumn('SELECT config_val FROM ' . WCDDL_DB_PREFIX . 'config WHERE config_name = ?', array($name)))
				return false;
			return $d;
		}
		if(!is_string($val)) $val = serialize($val);
		if(!$d = Database::quickColumn('SELECT config_name FROM ' . WCDDL_DB_PREFIX . 'config WHERE config_name = ?', array($name)))
			$save = Database::quickExecute('INSERT INTO ' . WCDDL_DB_PREFIX . 'config (config_name, config_val, config_group) VALUES (?, ?, ?)', array(
				$name, $val, empty($group) ? 'misc' : $group));
		else {
			if(empty($group))
				$save = Database::quickExecute('UPDATE ' . WCDDL_DB_PREFIX . 'config SET config_val = ? WHERE config_name = ?', array($val, $d));
			else
				$save = Database::quickExecute('UPDATE ' . WCDDL_DB_PREFIX . 'config SET config_val = ?, config_group = ? WHERE config_name = ?', array($val, $group, $d));
		}
		$this->configCache[$name] = $val;
		return $save;
	}

	public function parseConfig($name) {
		if(!$c = $this->config($name))
			return false;
		return unserialize($c);
	}

	public function configGroup($group) {
		return Database::quickRow('SELECT config_name, config_val FROM ' . WCDDL_DB_PREFIX . 'config WHERE config_group = ?', array($group));
	}

	public function configDelete($name) {
		unset($this->configCache[$name]);
		return Database::quickExecute('DELETE FROM ' . WCDDL_DB_PREFIX . 'config WHERE config_name = ?', array($name));
	}

	public function executeHook($name, $data=null) {
		if(empty($this->hookList[$name]))
			return is_null($data) ? true : $data;
		foreach($this->hookList[$name] as $h) {
			if(!is_array($h) && is_callable($h))
				call_user_func_array($h, !is_array($data) ? array() : $data);
			elseif(is_array($h) && (is_object($h[0]) || class_exists((string) $h[0]))) {
				$c = is_object($h[0]) ? $h[0] : new $h[0];
				if(method_exists($c, $h[1]))
					call_user_func_array(array($c, $h[1]), !is_array($data) ? array() : $data);
			}
		}
		return is_null($data) ? true : $data;
	}

	public function hook($name, $func) {
		if(!is_array($func) && !is_callable($func) && str_word_count($func) != 1)
			return false;
		if(!array_key_exists($name, $this->hookList))
			$this->hookList[$name] = array();
		if(!is_array($func))
			$this->hookList[$name][] = $func;
		elseif(is_array($func) && count($func) == 2)
			$this->hookList[$name][] = $func;
		else
			return false;
		return true;
	}

	public function getModules() {
		$return = array();
		if(!defined('WCDDL_PATH_MODULES'))
			return $return;
		$path = WCDDL_PATH_MODULES;
		if(!file_exists($path) || !is_dir($path))
			return $return;
		if($dir = opendir($path)) {
			while(($file = readdir($dir)) !== false) {
				if(substr($file, 0, 6) == "wcddl_" && strrchr($file, ".") == ".php")
					$return[] = array(
						'file' => basename($file),
						'path' => $path . basename($file)
						);
			}
			closedir($dir);
		}
		Core::load()->executeHook('CoreGetModules', array(&$return));
		return $return;
	}
}

class Downloads {
	public $page = 1;
	public $perPage = 30;
	public $query;
	public $type;
	public $numRows = 0;
	public $maxPages = 0;
	public $order = 'id DESC';
	public $siteInfo = false;
	public $optimisePagination = 0;
	public $queue = false;

	public function get() {
		// Im paranoid like this
		// OTT security but whatever
		if(!empty($this->order)) $this->order = preg_replace('#[^\w ]+#', '', $this->order);
		$this->perPage = intval($this->perPage);
		$this->page = intval($this->page);
		if(!empty($this->type) && defined('WCDDL_TYPES') && $allowableTypes = explode(',', WCDDL_TYPES))
			if(is_array($allowableTypes) && !in_array($this->type, $allowableTypes))
				$this->type = null;
		Core::load()->executeHook('DownloadsPreGet', array(&$this));
		// End paranoia
		$return = array();
		if($this->queue) {
			if(!$this->siteInfo)
				$sql = 'SELECT d.id, d.title, d.type, d.url FROM ' . WCDDL_DB_PREFIX . 'queue d';
			else
				$sql = 'SELECT d.id, d.title, d.type, d.url, s.url AS site_url, s.name AS site_name
				FROM ' . WCDDL_DB_PREFIX . 'queue d LEFT JOIN ' . WCDDL_DB_PREFIX . 'sites s ON (s.id = d.sid)';
		} else {
			if(!$this->siteInfo)
				$sql = 'SELECT d.id, d.title, d.type, d.url, d.time_added, d.views FROM ' . WCDDL_DB_PREFIX . 'downloads d';
			else
				$sql = 'SELECT d.id, d.title, d.type, d.url, d.time_added, d.views, s.url as site_url, s.name as site_name
				FROM ' . WCDDL_DB_PREFIX . 'downloads d LEFT JOIN ' . WCDDL_DB_PREFIX . 'sites s ON (s.id = d.sid)';
		}
		Core::load()->executeHook('DownloadsGetQuery', array(&$sql));
		$where = '';
		$whereParams = array();
		if(!empty($this->query) && !$this->queue) {
			$this->logQuery();
			$where .= (empty($where) ? ' WHERE' : ' AND') . ' MATCH(d.title) AGAINST(:query)';
			$whereParams['query'] = $this->query;
		}
		if(!empty($this->type)) {
			$where .= (empty($where) ? ' WHERE' : ' AND') . ' d.type = :type';
			$whereParams['type'] = $this->type;
		}
		Core::load()->executeHook('DownloadsGetWhere', array(&$where, &$whereParams));
		if(!$this->optimisePagination) {
			$this->numRows = Database::quickColumn('SELECT COUNT(*) FROM ' . WCDDL_DB_PREFIX . ($this->queue ? 'queue' : 'downloads') . ' d' . $where, $whereParams);
			$this->maxPages = ceil($this->numRows/$this->perPage);
		}
		$rows = $sql . $where . (!empty($this->order) ? ' ORDER BY d.' . $this->order : '');
		$rows .= ' LIMIT ' . (($this->page-1)*$this->perPage) . ', ' . ($this->optimisePagination ? $this->perPage+1 : $this->perPage);
		Core::load()->executeHook('DownloadsGetFullQuery', array(&$rows));
		if(!$rows = Database::quickRowObjects('Download', $rows, $whereParams))
			return $return;
		if($this->optimisePagination) {
			$this->numRows = count($rows);
			$this->maxPages = count($rows) == ($this->perPage+1) ? $this->page+1 : $this->page;
			array_pop($rows);
		}
		$return = array_merge($return, $rows);
		Core::load()->executeHook('DownloadsGetRows', array(&$rows));
		Core::load()->executeHook('DownloadsPostGet', array(&$this));
		return $rows;
	}

	public function logQuery() {
		if(empty($this->query))
			return false;
		Core::load()->executeHook('LogQueryPre', array(&$this->query));
		if($i = Database::quickColumn('SELECT id FROM ' . WCDDL_DB_PREFIX . 'recents WHERE query = ?', array($this->query)))
			return Database::quickExecute('UPDATE ' . WCDDL_DB_PREFIX . 'recents SET searches=searches+1 WHERE id = ?', array($i));
		return Database::quickExecute('INSERT INTO ' . WCDDL_DB_PREFIX . 'recents (query) VALUES (?)', array($this->query));
	}

	public function pages($map, $html=true) {
		return Common::pages($this, $map, $html);
	}

	// Lets make this one static
	public static function showQueries($i=50) {
		$q = Database::quickRowObjects('DownloadQuery', 'SELECT query, searches FROM ' . WCDDL_DB_PREFIX . 'recents ORDER BY id DESC LIMIT ' . intval($i));
		Core::load()->executeHook('ShowQueriesPost', array(&$q));
		return is_array($q) ? $q : array();
	}
}

class DownloadQuery {
	public $query;
	public $searches = 1;
	public static $outputPattern = 'index.php?q=#queryurl#';

	public function url() {
		return Common::formatURL($this->query);
	}

	public function increment() {
		return Database::quickExecute('UPDATE ' . WCDDL_DB_PREFIX . 'recents SET searches=searches+1 WHERE query = ?', array($this->query));
	}

	public function __toString() {
		if(empty(self::$outputPattern))
			return htmlspecialchars($this->query);
		return str_replace(array('#query#', '#queryurl#'), array(htmlspecialchars($this->query), $this->url()), self::$outputPattern);
	}
}

class Common {
	public static function formatURL($s, $sep='-') {
		$s = preg_replace('#[\W]+#', $sep, $s);
		$s = trim($s, $sep);
		$s = strtolower($s);
		Core::load()->executeHook('CommonFormatUrl', array(&$s));
		return $s;
	}

	public static function isEmail($e) {
		Core::load()->executeHook('CommonIsEmail', array(&$e));
		if(empty($e)) return false;
		return filter_var($e, FILTER_VALIDATE_EMAIL);
	}

	public static function isUrl($u) {
		Core::load()->executeHook('CommonIsUrl', array(&$u));
		if(empty($u)) return false;
		return filter_var($u, FILTER_VALIDATE_URL);
	}

	public static function urlHost($u) {
		if(!self::isUrl($u))
			return false;
		$p = strtolower(parse_url($u, PHP_URL_HOST));
		if(substr($p, 0, 4) == 'www.')
			$p = substr($p, 4);
		Core::load()->executeHook('CommonUrlHost', array(&$p));
		return $p;
	}

	public static function displayStr($s) {
		Core::load()->executeHook('CommonDisplayStr', array(&$s));
		return htmlspecialchars($s);
	}

	public static function isAjaxRequest() {
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			return true;
		return false;
	}

	public static function arrayMergeUnique($a, $b) {
		foreach($b as $entry)
			if(!in_array($entry, $a))
				$a[] = $entry;
		return $a;
	}

	public static function pages($instance, $map, $html=true) {
		if(!is_array($map))
			return false;
		Core::load()->executeHook('PagesPre', array(&$map));
		foreach($map as $m) {
			if(count($m) !== 2)
				continue;
			if(!is_array($m[0]))
				$m[0] = array($m[0]);
			if($m[0] == array('default'))
				$defaultURL = $m;
			$found = 0;
			foreach($m[0] as $prop) {
				if(empty($instance->$prop))
					continue;
				$found++;
			}
			if($found !== count($m[0]))
				continue;
			$finalURL = $m;
			break;
		}
		if(empty($finalURL) && !empty($defaultURL))
			$finalURL = $defaultURL;
		if(!empty($finalURL)) {
			foreach($finalURL[0] as $prop)
				if($prop != 'default')
					$finalURL[1] = str_replace('#' . $prop . '#', $instance->$prop, $finalURL[1]);
			$result = array();
			for($i = ($instance->page-10); $i <= ($instance->page+10); $i++) {
				if($i > 0 && $i <= $instance->maxPages)
					$result[] = str_replace('#page#', $i, $finalURL[1]);
			}
			Core::load()->executeHook('PagesPost', array(&$result));
			return $html ? implode(", ", $result) : $result;
		}
		return false;
	}
}

class Download {
	public $id;
	// Updateable ones
	public $title, $type, $url, $sid;
	// Static ones
	public $time_added, $views;
	// Site stuff
	public $site_name, $site_url;

	public function __construct() {
	}

	public function queue() {
		Core::load()->executeHook('DownloadQueuePre', array(&$this));
		$id = array(
			'sid' => $this->sid,
			'title' => $this->title,
			'type' => $this->type,
			'url' => $this->url,
		);
		Core::load()->executeHook('DownloadQueueInsert', array(&$id));
		$this->id = Database::insert(WCDDL_DB_PREFIX . 'queue', $id, true);
		return $this->id;
	}

	public function deQueue() {
		if(empty($this->id))
			return false;
		Core::load()->executeHook('DownloadDeQueuePre', array(&$this));
		$dq = array(
			'id' => $this->id,
		);
		Core::load()->executeHook('DownloadDeQueueDelete', array(&$id));
		$dq = Database::delete(WCDDL_DB_PREFIX . 'queue', $dq);
		$this->id = null;
		return $dq;
	}

	public function delete() {
		if(empty($this->id))
			return false;
		Core::load()->executeHook('DownloadDeletePre', array(&$this));
		$d = array(
			'id' => $this->id,
		);
		Core::load()->executeHook('DownloadDeleteDelete', array(&$d));
		$d = Database::delete(WCDDL_DB_PREFIX . 'downloads', $d);
		$this->id = null;
		return $d;
	}

	public function save() {
		$params = array(
			'sid' => $this->sid,
			'title' => $this->title,
			'type' => $this->type,
			'url' => $this->url,
			'time_added' => new DatabaseRaw('NOW()'),
		);
		if(!empty($this->id)) {
			unset($params['time_added']);
			$criteria = array(
				'id' => $this->id,
			);
			Core::load()->executeHook('DownloadSaveUpdate', array(&$params, &$criteria));
			Database::update(WCDDL_DB_PREFIX . 'downloads', $params, $criteria);
		} else {
			Core::load()->executeHook('DownloadSaveInsert', array(&$params));
			$this->id = Database::insert(WCDDL_DB_PREFIX . 'downloads', $params, true);
		}
		return $this->id;
	}

	public function addView() {
		if(empty($this->id))
			return false;
		Core::load()->executeHook('DownloadAddView', array(&$this));
		return Database::quickExecute('UPDATE ' . WCDDL_DB_PREFIX . 'downloads SET views=views+1 WHERE id = ?', array($this->id));
	}

	public function showTitle() {
		if(empty($this->title))
			return '';
		$t = $this->title;
		Core::load()->executeHook('DownloadShowTitle', array(&$t));
		$t = Common::displayStr($t);
		Core::load()->executeHook('DownloadShowTitlePost', array(&$t));
		return $t;
	}

	// Static methods
	public static function getQueue($i, $siteInfo=false) {
		if(!self::existsByID($i, true))
			return false;
		if(!$siteInfo)
			$query = 'SELECT * FROM ' . WCDDL_DB_PREFIX . 'queue WHERE id = ?';
		else
			$query = 'SELECT q.*, s.name AS site_name, s.url AS site_url FROM ' . WCDDL_DB_PREFIX . 'queue q
			LEFT JOIN ' . WCDDL_DB_PREFIX . 'sites s ON (s.id = q.sid) WHERE q.id = ?';
		return Database::quickRowObject('Download', $query, array($i));
	}

	public static function get($i, $siteInfo=false) {
		if(!self::existsByID($i))
			return false;
		if(!$siteInfo)
			$query = 'SELECT * FROM ' . WCDDL_DB_PREFIX . 'downloads WHERE id = ?';
		else
			$query = 'SELECT d.*, s.name AS site_name, s.url AS site_url FROM ' . WCDDL_DB_PREFIX . 'downloads d
			LEFT JOIN ' . WCDDL_DB_PREFIX . 'sites s ON (s.id = d.sid) WHERE d.id = ?';
		return Database::quickRowObject('Download', $query, array($i));
	}

	public static function existsByID($i, $queue=false) {
		if(Database::quickColumn('SELECT id FROM ' . WCDDL_DB_PREFIX . ($queue ? 'queue' : 'downloads') . ' WHERE id = ?', array($i)))
			return true;
		return false;
	}
}

class Submit {
	public $title = array();
	public $url = array();
	public $type = array();
	public $sname, $surl, $email;

	public $error = '';
	public $whitelist = false;
	public $blacklist = true;
	public $sid;

	public function __construct() {
		# The constants should really be kept apart from
		# this class but ill use them here to reduce
		# code in the submit page header and for
		# simplicity
		if(defined('WCDDL_WHITELIST') && WCDDL_WHITELIST == 1)
			$this->whitelist = true;
		if(defined('WCDDL_BLACKLIST') && WCDDL_BLACKLIST == 1)
			$this->blacklist = true;
		Core::load()->executeHook('SubmitConstruct', array(&$this));
	}

	public function submit() {
		// Better to code this to allow multiple errors
		// but im in a rush
		Core::load()->executeHook('SubmitPre', array(&$this));
		if(empty($this->title))
			$this->error = 'No titles were set.';
		elseif(empty($this->url))
			$this->error = 'No URLs were provided.';
		elseif(empty($this->type))
			$this->error = 'No types were chosen.';
		elseif(empty($this->sname))
			$this->error = 'Site name was empty.';
		elseif(empty($this->surl))
			$this->error = 'Site URL was empty.';
		elseif(empty($this->email))
			$this->error = 'Email was empty.';
		elseif(!common::isEmail($this->email))
			$this->error = 'Invalid email provided.';
		elseif(!common::isUrl($this->surl))
			$this->error = 'Invalid site URL.';
		else {
			$down = $this->filterDownloads();
			if(empty($down))
				$this->error = 'No valid downloads were entered.';
		}
		Core::load()->executeHook('SubmitValidation', array(&$this));
		if(!empty($this->error)) return false;
		$shost = common::urlHost($this->surl);
		if($this->whitelist && !Site::isWhitelisted($shost)) {
			$this->error = 'Site URL is not whitelisted.';
			return false;
		}
		if($this->blacklist && Site::isBlacklisted($shost)) {
			$this->error = 'Site URL is blacklisted.';
			return false;
		}
		if(!$siteID = Site::existsByURL($shost)) {
			$siteID = new Site;
			$siteID->name = $this->sname;
			$siteID->url = $shost;
			$siteID->email = $this->email;
			$siteID = $siteID->save();
		}
		$this->sid = $siteID;
		foreach($down as $d) {
			Core::load()->executeHook('SubmitDownload', array(&$d));
			$d->sid = $this->sid;
			$d->queue();
		}
		Core::load()->executeHook('SubmitDownloadPost', array(&$this));
		return true;
	}

	public function filterDownloads() {
		$pass = array();
		Core::load()->executeHook('SubmitFilterPre', array(&$this));
		if(empty($this->title) || empty($this->url) || empty($this->type))
			return $pass;
		foreach($this->title as $i => $t) {
			if(empty($this->url[$i]) || !common::isUrl($this->url[$i]))
				continue;
			if(common::urlHost($this->url[$i]) != common::urlHost($this->surl))
				continue;
			if(empty($this->type[$i]))
				continue;
			if(defined('WCDDL_TYPES') && $allowableTypes = explode(',', WCDDL_TYPES))
				if(is_array($allowableTypes) && !in_array($this->type[$i], $allowableTypes))
					continue;
			$valid = true;
			Core::load()->executeHook('SubmitFilterValidate', array(&$valid));
			if(!$valid) continue;
			$d = new Download;
			$d->sid = $this->sid;
			$d->title = $this->title[$i];
			$d->url = $this->url[$i];
			$d->type = $this->type[$i];
			$pass[] = $d;
		}
		return $pass;
	}
}

class Site {
	public $url, $name, $email;
	public $id;
	// List stuff
	public $page = 1;
	public $maxPages = 1;
	public $perPage = 20;

	public function save() {
		Core::load()->executeHook('SiteSavePre', array(&$this));
		$query = 'INSERT INTO ' . WCDDL_DB_PREFIX . 'sites (url, name, email) VALUES (:url, :name, :email)';
		$params = array(
			'url' => $this->url,
			'name' => $this->name,
			'email' => $this->email,
		);
		if(!empty($this->id)) {
			$criteria = array('id' => $this->id);
			Core::load()->executeHook('SiteSaveUpdate', array(&$params, &$criteria));
			Database::update(WCDDL_DB_PREFIX . 'sites', $params, $criteria);
		} else {
			Core::load()->executeHook('SiteSaveInsert', array(&$params));
			$this->id = Database::insert(WCDDL_DB_PREFIX . 'sites', $params, true);
		}
		return $this->id;
	}

	public function remove() {
		if(empty($this->id))
			return false;
		Database::quickExecute('DELETE FROM ' . WCDDL_DB_PREFIX . 'sites WHERE id = ?', array($this->id));
		Database::quickExecute('DELETE FROM ' . WCDDL_DB_PREFIX . 'downloads WHERE sid = ?', array($this->id));
		return true;
	}

	public function getMany() {
		if(empty($this->page))
			$this->page = 1;
		Core::load()->executeHook('SitesGetPre', array(&$this));
		$query = 'SELECT id, url, name, email FROM ' . WCDDL_DB_PREFIX . 'sites ORDER BY name ASC';
		$query .= ' LIMIT ' . (($this->page-1)*$this->perPage) . ', ' . ($this->perPage+1);
		Core::load()->executeHook('SitesGet', array(&$query));
		$rows = Database::quickRowObjects('Site', $query);
		$this->maxPages = $this->page;
		if(is_array($rows) && count($rows) == ($this->perPage+1)) {
			$this->maxPages++;
			array_pop($rows);
		}
		return $rows;
	}

	public function getList($wb='white') {
		if(empty($this->page))
			$this->page = 1;
		Core::load()->executeHook('SiteGetListPre', array(&$this));
		if($wb == 'white')
			$query = 'SELECT url FROM ' . WCDDL_DB_PREFIX . 'whitelist';
		else
			$query = 'SELECT url, reason FROM ' . WCDDL_DB_PREFIX . 'blacklist';
		$query .= ' LIMIT ' . (($this->page-1)*$this->perPage) . ', ' . ($this->perPage+1);
		Core::load()->executeHook('SiteGetList', array(&$query));
		$rows = Database::quickRows($query);
		$this->maxPages = $this->page;
		if(is_array($rows) && count($rows) == ($this->perPage+1)) {
			$this->maxPages++;
			array_pop($rows);
		}
		return $rows;
	}

	public static function whitelist($url) {
		if(Common::isUrl($url))
			$url = Common::urlHost($url);
		Core::load()->executeHook('SiteWhitelist', array(&$url));
		return Database::quickExecute('INSERT IGNORE INTO ' . WCDDL_DB_PREFIX . 'whitelist (url) VALUES (?)', array($url));
	}

	public static function whitelistRemove($url) {
		if(Common::isUrl($url))
			$url = Common::urlHost($url);
		Core::load()->executeHook('SiteWhitelistRemove', array(&$url));
		return Database::quickExecute('DELETE FROM ' . WCDDL_DB_PREFIX . 'whitelist WHERE url = ?', array($url));
	}

	public static function blacklistRemove($url) {
		if(Common::isUrl($url))
			$url = Common::urlHost($url);
		Core::load()->executeHook('SiteBlacklistRemove', array(&$url));
		return Database::quickExecute('DELETE FROM ' . WCDDL_DB_PREFIX . 'blacklist WHERE url = ?', array($url));
	}

	public static function blacklist($url, $reason=null, $removeDownloads=false) {
		if(Common::isUrl($url))
			$url = Common::urlHost($url);
		Core::load()->executeHook('SiteBlacklist', array(&$url));
		if($removeDownloads && $sid = self::existsByUrl($url))
			Database::quickExecute('DELETE FROM ' . WCDDL_DB_PREFIX . 'downloads WHERE sid = ?', array($sid));
		return Database::quickExecute('INSERT IGNORE INTO ' . WCDDL_DB_PREFIX . 'blacklist (url, reason) VALUES (?, ?)', array($url, $reason));
	}

	public static function isWhitelisted($url) {
		if(Common::isUrl($url))
			$url = Common::urlHost($url);
		Core::load()->executeHook('SiteIsWhitelisted', array(&$url));
		return Database::quickColumn('SELECT url FROM ' . WCDDL_DB_PREFIX . 'whitelist WHERE url = ?', array($url));
	}

	public static function isBlacklisted($url) {
		if(Common::isUrl($url))
			$url = Common::urlHost($url);
		Core::load()->executeHook('SiteIsBlacklisted', array(&$url));
		return Database::quickColumn('SELECT url FROM ' . WCDDL_DB_PREFIX . 'blacklist WHERE url = ?', array($url));
	}

	public static function existsByID($i) {
		return Database::quickColumn('SELECT id FROM ' . WCDDL_DB_PREFIX . 'sites WHERE id = ?', array($i));
	}

	public static function existsByURL($u) {
		if(Common::isUrl($u))
			$u = Common::urlHost($u);
		return Database::quickColumn('SELECT id FROM ' . WCDDL_DB_PREFIX . 'sites WHERE url = ?', array($u));
	}

	public static function get($i) {
		if(!self::existsByID($i))
			return false;
		return Database::quickRowObject('Site', 'SELECT * FROM ' . WCDDL_DB_PREFIX . 'sites WHERE id = ?', array($i));
	}
}

// This class outputs HTML in some methods
// Goes against my personal style of coding but
// was needed to keep the admin page as a single file
// while keeping it clean.
class Admin {
	private static $instance;
	public $goMethods = array(
		'queue',
		'downloads',
		'downloadsAdd',
		'downloadsEdit',
		'whitelist',
		'blacklist',
		'config',
		'configAdd',
		'configEdit',
		'modules',
		'sites',
		'sitesAdd'
	);

	public static function load() {
		if(empty(self::$instance))
			self::$instance = new Admin;
		return self::$instance;
	}

	public function init() {
		Core::load()->executeHook('AdminInit', array(&$this));
	}

	public function handleContent() {
		if(empty($_REQUEST['go']))
			return false;
		$go = $_REQUEST['go'];
		Core::load()->executeHook('AdminHandleContent', array(&$go));
		if(in_array($go, $this->goMethods))
			call_user_func(array($this, $go));
	}

	private function queue() {
		$downloads = Core::load()->mapRequest('Downloads', array('page', 'type'));
		$downloads->siteInfo = true;
		$downloads->queue = true;
		if(!empty($_POST['rows'])) {
			if(isset($_POST['decline']))
				$this->queueDecline($_POST['rows']);
			elseif(isset($_POST['accept']))
				$this->queueAccept($_POST['rows']);
		}
		echo '<form action="?go=queue" method="post">';
		echo '<table width="100%">
			<tr>
				<th>&nbsp;</th>
				<th>Download</th>
				<th>Type</th>
				<th>Provider</th>
			</tr>';
		foreach($downloads->get() as $d)
			echo '<tr>
				<td><input type="checkbox" name="rows[]" value="' . $d->id . '" /></td>
				<td><a href="' . $d->url . '" target="_blank">' . $d->showTitle() . '</a></td>
				<td>' . Common::displayStr($d->type) . '</td>
				<td><a href="http://' . urlencode($d->site_url) . '/" target="_blank">' . Common::displayStr($d->site_name) . '</a></td>
			</tr>';
		echo '</table>';
		echo $downloads->pages(array(
			array('default', '<a href="?go=queue&amp;page=#page#">#page#</a>')
		));
		echo '<br /><input type="submit" name="decline" value="Decline" />
			<input type="submit" name="accept" value="Accept" />';
		echo '</form>';
	}

	private function queueDecline($rows) {
		if(empty($rows))
			return false;
		elseif(!is_array($rows))
			return false;
		else {
			foreach($rows as $row) {
				$download = new Download;
				$download->id = $row;
				$download->deQueue();
			}
		}
		return true;
	}

	private function queueAccept($rows) {
		if(empty($rows) || !is_array($rows))
			return false;
		else {
			foreach($rows as $row) {
				if(!$download = Download::getQueue($row))
					continue;
				$download->deQueue();
				$download->save();
			}
		}
		return true;
	}

	private function downloads() {
		$downloads = Core::load()->mapRequest('Downloads', array('page', 'type'));
		$downloads->siteInfo = true;
		if(!empty($_POST['rows'])) {
			if(isset($_POST['delete']))
				$this->downloadsDelete($_POST['rows']);
		}
		echo '<form action="?go=downloads" method="post">';
		echo '<table width="100%">
			<tr>
				<th>&nbsp;</th>
				<th>Download</th>
				<th>Type</th>
				<th>Provider</th>
			</tr>';
		foreach($downloads->get() as $d)
			echo '<tr>
				<td>
					<input type="checkbox" name="rows[]" value="' . $d->id . '" />
					(<a href="?go=downloadsEdit&amp;id=' . $d->id . '">edit</a>)
				</td>
				<td><a href="' . $d->url . '" target="_blank">' . $d->showTitle() . '</a></td>
				<td>' . Common::displayStr($d->type) . '</td>
				<td><a href="http://' . urlencode($d->site_url) . '/" target="_blank">' . Common::displayStr($d->site_name) . '</a></td>
			</tr>';
		echo '</table>';
		echo $downloads->pages(array(
			array('default', '<a href="?go=downloads&amp;page=#page#">#page#</a>')
		));
		echo '<br /><input type="submit" name="delete" value="Delete" />';
		echo '</form>';
	}

	private function downloadsDelete($rows) {
		if(empty($rows) || !is_array($rows))
			return false;
		else {
			foreach($rows as $row) {
				$download = new Download;
				$download->id = $row;
				$download->delete();
			}
		}
		return true;
	}

	private function downloadsAdd() {
		$error = null;
		if(isset($_POST['add'])) {
			if(empty($_POST['surl']))
				$error = 'No site URL was entered.';
			elseif(empty($_POST['title']))
				$error = 'No title was set.';
			elseif(empty($_POST['type']))
				$error = 'No type was selected.';
			elseif(empty($_POST['url']))
				$error = 'No URL was entered.';
			elseif(!Common::isUrl($_POST['url']))
				$error = 'Invalid URL entered.';
			elseif(!$siteID = Site::existsByURL($_POST['surl']))
				$error = 'Site must have already submitted downloads.';
		}
		if(!isset($_POST['add']) || !empty($error)) {
			echo '<strong>' . $error . '</strong>';
			echo '<form action="?go=downloadsAdd" method="post">
				<table width="100%">
					<tr>
						<td>Domain (e.g. abc.com)</td>
						<td><input type="text" name="surl" /></td>
					</tr>
					<tr>
						<td>Title</td>
						<td><input type="text" name="title" /></td>
					</tr>
					<tr>
						<td>URL</td>
						<td><input type="text" name="url" /></td>
					</tr>
					<tr>
						<td>Type</td>
						<td>
							<select name="type">';
			$opts = !defined('WCDDL_TYPES') ? array() : explode(',', WCDDL_TYPES);
			if(is_array($opts)) {
				foreach($opts as $at) {
					echo '<option value="'.$at.'">'.$at.'</option>';
				}
			}
			echo '			</select>
						</td>
					</tr>
					<tr>
						<td colspan="2"><input type="submit" value="Add Download" name="add" /></td>
					</tr>
				</table>
			</form>';
			return true;
		}
		$download = new Download;
		$download->title = $_POST['title'];
		$download->type = $_POST['type'];
		$download->url = $_POST['url'];
		$download->sid = $siteID;
		$download->save();
		echo 'Download added.';
	}

	private function downloadsEdit() {
		if(empty($_GET['id']) || !Download::existsByID($_GET['id'])) {
			echo 'No download was selected.';
			return true;
		}
		$error = null;
		if(isset($_POST['edit'])) {
			if(empty($_POST['title']))
				$error = 'No title set.';
			elseif(empty($_POST['url']))
				$error = 'No URL set.';
			elseif(empty($_POST['type']))
				$error = 'No type set.';
		}
		$download = Download::get($_GET['id']);
		if(!isset($_POST['edit']) || !empty($error)) {
			echo '<strong>' . $error . '</strong><br />';
			echo '<form action="?go=downloadsEdit&amp;id=' . $_GET['id'] . '" method="post">
				<table width="100%">
					<tr>
						<td>Title</td>
						<td><input type="text" name="title" value="' . $download->showTitle() . '" /></td>
					</tr>
					<tr>
						<td>URL</td>
						<td><input type="text" name="url" value="' . Common::displayStr($download->url) . '" /></td>
					</tr>
					<tr>
						<td>Type</td>
						<td>
							<select name="type">';
			$opts = !defined('WCDDL_TYPES') ? array() : explode(',', WCDDL_TYPES);
			if(is_array($opts)) {
				foreach($opts as $at) {
					echo '<option value="'.$at.'"' . ($download->type == $at ? ' selected="selected"' : '') . '>'.$at.'</option>';
				}
			}
			echo '			</select>
						</td>
					</tr>
					<tr>
						<td colspan="2"><input type="submit" value="Edit Download" name="edit" /></td>
					</tr>
				</table>
			</form>';
			return true;
		}
		$download->title = $_POST['title'];
		$download->url = $_POST['url'];
		$download->type = $_POST['type'];
		$download->save();
		echo 'Download edited.';
	}

	private function whitelist() {
		if(!empty($_POST['url'])) {
			Site::whitelist($_POST['url']);
			echo 'URL whitelisted.';
		}
		if(isset($_POST['delete']) && !empty($_POST['rows']) && is_array($_POST['rows'])) {
			foreach($_POST['rows'] as $row)
				Site::whitelistRemove($row);
		}
		echo '<form action="?go=whitelist" method="post">
			<table width="100%">
			<tr>
				<th>&nbsp;</th>
				<th>URL</th>
			</tr>';
		$site = Core::load()->mapRequest('Site', array('page'));
		foreach($site->getList('white') as $row)
			echo '<tr>
				<td><input type="checkbox" name="rows[]" value="' . $row['url'] . '" /></td>
				<td>' . $row['url'] . '</td>
			</tr>';
		echo '</table><input type="submit" value="Delete" name="delete" /></form>';
		echo Common::pages($site, array(
			array('default', '<a href="?go=whitelist&amp;page=#page#">#page#</a>')
		));
		echo '<form action="?go=whitelist" method="post">
			<table width="100%">
				<tr>
					<td>Domain (e.g. abc.com)</td>
					<td><input type="text" name="url" /></td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" value="Whitelist" /></td>
				</tr>
			</table>
		</form>';
		return true;
	}

	private function blacklist() {
		if(!empty($_POST['url'])) {
			Site::blacklist($_POST['url'], $_POST['reason'], isset($_POST['delete_downloads']));
			echo 'URL blacklisted.';
		}
		if(isset($_POST['delete']) && !empty($_POST['rows']) && is_array($_POST['rows'])) {
			foreach($_POST['rows'] as $row)
				Site::blacklistRemove($row);
		}
		echo '<form action="?go=blacklist" method="post">
			<table width="100%">
			<tr>
				<th>&nbsp;</th>
				<th>URL</th>
				<th>Reason</th>
			</tr>';
		$site = Core::load()->mapRequest('Site', array('page'));
		foreach($site->getList('black') as $row)
			echo '<tr>
				<td><input type="checkbox" name="rows[]" value="' . $row['url'] . '" /></td>
				<td>' . $row['url'] . '</td>
				<td>' . $row['reason'] . '</td>
			</tr>';
		echo '</table><input type="submit" value="Delete" name="delete" /></form>';
		echo Common::pages($site, array(
			array('default', '<a href="?go=blacklist&amp;page=#page#">#page#</a>')
		));
		echo '<form action="?go=blacklist" method="post">
			<table width="100%">
				<tr>
					<td>Domain (e.g. abc.com)</td>
					<td><input type="text" name="url" /></td>
				</tr>
				<tr>
					<td>Reason</td>
					<td><input type="text" name="reason" /></td>
				</tr>
				<tr>
					<td>Remove Downloads</td>
					<td><input type="checkbox" name="delete_downloads" /></td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" value="Blacklist" /></td>
				</tr>
			</table>
		</form>';
		return true;
	}

	private function modules() {
		echo '<table width="100%">
			<tr>
				<th>Module Name</th>
			</tr>';
		foreach(Core::load()->getModules() as $mod) {
			echo '<tr>
				<td>' . Common::displayStr($mod['file']) . '</td>
				</tr>';
		}
		echo '</table>';
		return true;
	}

	private function sites() {
		$error = null;
		if(isset($_POST['add'])) {
			if(empty($_POST['url']))
				$error = 'No URL set.';
			elseif(empty($_POST['name']))
				$error = 'No name set.';
			elseif(empty($_POST['email']))
				$error = 'No email set.';
			else {
				$site = new Site;
				$site->url = $_POST['url'];
				$site->name = $_POST['name'];
				$site->email = $_POST['email'];
				$site->save();
			}
		}
		if(isset($_POST['delete']) && !empty($_POST['rows']) && is_array($_POST['rows'])) {
			foreach($_POST['rows'] as $row) {
				$s = new Site;
				$s->id = $row;
				$s->remove();
			}
		}
		echo '<strong>' . $error . '</strong>
			<form action="?go=sites" method="post">
			<table width="100%">
			<tr>
				<th>&nbsp;</th>
				<th>Site</th>
				<th>Email</th>
			</tr>';
		$site = Core::load()->mapRequest('Site', array('page'));
		foreach($site->getMany() as $row)
			echo '<tr>
				<td><input type="checkbox" name="rows[]" value="' . $row->id . '" /></td>
				<td><a href="http://' . $row->url . '/" target="_blank">' . $row->name . '</a></td>
				<td>' . $row->email . '</td>
			</tr>';
		echo '</table><input type="submit" value="Delete" name="delete" /></form>';
		echo Common::pages($site, array(
			array('default', '<a href="?go=sites&amp;page=#page#">#page#</a>')
		));
		echo '<form action="?go=sites" method="post">
			<table width="100%">
				<tr>
					<td>Domain (e.g. abc.com)</td>
					<td><input type="text" name="url" /></td>
				</tr>
				<tr>
					<td>Name (e.g. ABC)</td>
					<td><input type="text" name="name" /></td>
				</tr>
				<tr>
					<td>Email</td>
					<td><input type="text" name="email" /></td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" value="Add Site" name="add" /></td>
				</tr>
			</table>
		</form>';
		return true;
	}

	public function authenticate() {
		Core::load()->executeHook('AdminAuthenticatePre');
		if(!empty($_SESSION['wc3a']) && $_SESSION['wc3a'] == sha1(WCDDL_ADMIN_PASS . 'TehJmZ'))
			return true;
		if(!isset($_POST['pass']))
			die('<html>
				<body>
					<form action="" method="post">
						<input type="password" name="pass" />
						<input type="submit" value="Login" />
					</form>
				</body>
			</html>');
		if($_POST['pass'] != WCDDL_ADMIN_PASS) {
			Core::load()->executeHook('AdminAuthenticateFailure');
			die('Password incorrect.');
		}
		$_SESSION['wc3a'] = sha1(WCDDL_ADMIN_PASS . 'TehJmZ');
		Core::load()->executeHook('AdminAuthenticateSuccess');
		header("Location: " . WCDDL_ADMIN_LOCATION);
		return true;
	}
}
