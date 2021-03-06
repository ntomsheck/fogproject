<?php
/**	Class Name: MySQL
	For mysql connections specifically.
*/
class MySQL
{
	private $host, $user, $pass, $db, $startTime, $result, $queryResult, $link, $query;
	// Cannot use constants as you cannot access constants from $this->db::ROW_ASSOC
	public $ROW_ASSOC = 1;	// MYSQL_ASSOC
	public $ROW_NUM = 2;	// MYSQL_NUM
	public $ROW_BOTH = 3;	// MYSQL_BOTH
	public $debug = false;
	public $info = false;
	/** __construct($host,$user,$pass,$db = '')
		Constructs the connections to the database.
	*/
	function __construct($host, $user, $pass, $db = '')
	{
		try
		{
			if (!function_exists('mysql_connect'))
				throw new Exception(sprintf('%s PHP extension not loaded', __CLASS__));
			$this->host = $host;
			$this->user = $user;
			$this->pass = $pass;
			$this->db = $db;
			if (!$this->connect())
				throw new Exception('Failed to connect');
			$this->startTime = $this->now();
		}
		catch (Exception $e)
		{
			$GLOBALS['FOGCore']->error(sprintf('Failed to %s: %s', __FUNCTION__, $e->getMessage()));
		}
	}
	/** __destruct()
		Disconnect the connection.
	*/
	function __destruct()
	{
		try
		{
			if (!$this->link)
				return;
			if ($this->link && !mysql_close($this->link))
				throw new Exception('Could not disconnect');
		}
		catch (Exception $e)
		{
			$GLOBALS['FOGCore']->debug(sprintf('Failed to %s: %s', __FUNCTION__, $e->getMessage()));
		}
	}
	/** close()
		Close the connection.
	*/
	public function close()
	{
		$this->__destruct();
	}
	/** connect()
		Connects the database.
	*/
	public function connect()
	{
		try
		{
			if ($this->link)
				$this->close();
			if (!$this->link = @mysql_connect($this->host, $this->user, $this->pass))
				throw new Exception(sprintf('Host: %s, Username: %s, Password: %s, Database: %s', $this->host, $this->user, $this->pass, $this->db));
			if ($this->db)
				$this->select_db($this->db);
		}
		catch (Exception $e)
		{
			$GLOBALS['FOGCore']->debug(sprintf('Failed to %s: %s', __FUNCTION__, $e->getMessage()));
		}
		return $this;
	}
	/** query($sql,$data = array())
		Allows the sending of specific sql settings.
	*/
	public function query($sql, $data = array())
	{
		try
		{
			// printf
			if (!is_array($data))
				$data = array($data);
			if (count($data))
				$sql = vsprintf($sql, $data);
			$this->link = @mysql_connect($this->host, $this->user, $this->pass);
			// Query
			$this->query = $sql;
			$this->queryResult = mysql_query($this->query, $this->link) or $GLOBALS['FOGCore']->debug($this->error(), $this->query);
			// INFO
			$GLOBALS['FOGCore']->info($this->query);
		}
		catch (Exception $e)
		{
			$GLOBALS['FOGCore']->debug(sprintf('Failed to %s: %s', __FUNCTION__, $e->getMessage()));
		}
		return $this;
	}
	/** fetch($type = MYSQL_ASSOC)
		fetches the information.
	*/
	public function fetch($type = MYSQL_ASSOC)
	{
		try
		{
			if (!$this->queryResult)
				throw new Exception('No query result present. Use query() first');
			if ($this->queryResult === false)
				$this->result = false;
			elseif ($this->queryResult === true)
				$this->result = true;
			else
				$this->result = mysql_fetch_array($this->queryResult, $type);
			//return $this->result;
		}
		catch (Exception $e)
		{
			$GLOBALS['FOGCore']->debug(sprintf('Failed to %s: %s', __FUNCTION__, $e->getMessage()));
		}
		return $this;
	}
	/** result()
		The result of the query.
	*/
	public function result()
	{
		return $this->result;
	}
	/** queryResult()
		The result of the sql query.
	*/
	public function queryResult()
	{
		return $this->queryResult;
	}
	/** get($field = '')
		Get the information.  Can specify the database field as well.
	*/
	public function get($field = '')
	{
		try
		{
			// Result finished
			if ($this->result === false)
				return false;
			// Query failed
			if ($this->queryResult === false)
				return false;
			// Return: 'field' if requested and field exists in results, otherwise the raw result
			return ($field && array_key_exists($field, $this->result) ? $this->result[$field] : $this->result);
		}
		catch (Exception $e)
		{
			$GLOBALS['FOGCore']->debug(sprintf('Failed to %s: %s', __FUNCTION__, $e->getMessage()));
		}
		return false;
	}
	/** select_db($db)
		Selects the sent database.
	*/
	public function select_db($db)
	{
		try
		{
			if (!mysql_select_db($db, $this->link))
				throw new Exception("$db");
			$this->db = $db;
		}
		catch (Exception $e)
		{
			$GLOBALS['FOGCore']->debug(sprintf('Failed to %s: %s', __FUNCTION__, $e->getMessage()));
		}
		return $this;
	}
	/** error()
		What was the error.
	*/
	public function error()
	{
		return mysql_error();
	}
	/** insert_id()
		Return the id of the inserted element.
	*/
	public function insert_id()
	{
		$id = mysql_insert_id($this->link);
		return ($id ? $id : 0);
	}
	/** affected_rows()
		Return the affected rows.
	*/
	public function affected_rows()
	{
		$count = mysql_affected_rows($this->link);
		return ($count ? $count : 0);
	}
	/** num_rows()
		Return the number of rows.
	*/
	public function num_rows()
	{
		return (is_resource($this->queryResult) ? mysql_num_rows($this->queryResult) : null);
	}
	/** age()
		Return the age of the information.
	*/
	public function age()
	{
		return ($this->now() - $this->startTime);
	}
	/** now()
		Return the current time.
	*/
	private function now()
	{
		return microtime(true);
	}
	/** escape($data)
		Make sure the data is clean to pass to the database.
	*/
	public function escape($data)
	{
		return $this->sanitize($data);
	}
	/** sanitize($data)
		Sanatizes the entry.
	*/
	public function sanitize($data)
	{
		if (!is_array($data))
			return $this->clean($data);
		foreach ($data AS $key => $val)
		{
			if (is_array($val))
				$data[$this->clean($key)] = $this->escape($val);
			else
				$data[$this->clean($key)] = $this->clean($val);
		}
		return $data;
	}
	/** clean($data)
		Clean the information.
	*/
	private function clean($data)
	{
		return (get_magic_quotes_gpc() ? mysql_real_escape_string(stripslashes($data)) : mysql_real_escape_string($data));;
	}
	// For legacy $conn connections
	/** getLink()
		Make sure the link works.
	*/
	public function getLink()
	{
		return $this->link;
	}
	/** dump($exit = false)
		Dumps the data.
	*/
	public function dump($exit = false)
	{
		printf('<p>Last Error: %s</p><p>Last Query: %s</p><p>Last Query Result: %s</p><p>Last Num Rows: %s</p><p>Last Affected Rows: %s</p><p>Last Result: %s</p>',
			$this->error(),
			$this->query,
			(is_bool($this->queryResult) === true ? ($this->queryResult == true ? 'true' : 'false') : $this->queryResult),
			$this->num_rows(),
			$this->affected_rows(),
			(is_array($this->result) ? '<pre>' . print_r($this->result, 1) . '</pre>' : (is_bool($this->result) === true ? ($this->result == true ? 'true' : 'false') : $this->result))
		);
		if ($exit)
			exit;
		return $this;
	}
}
