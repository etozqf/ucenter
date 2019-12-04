<?php

/*
    [UCenter] (C)2001-2099 Comsenz Inc.
    This is NOT a freeware, use is subject to license terms

    $Id: db.class.php 1059 2011-03-01 07:25:09Z monkey $
*/

class ucclient_db
{
    public $querynum = 0;
    public $link;
    public $histories;

    public $dbhost;
    public $dbuser;
    public $dbpw;
    public $dbcharset;
    public $pconnect;
    public $tablepre;
    public $time;

    public $goneaway = 5;

    public function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset = '', $pconnect = 0, $tablepre = '', $time = 0)
    {
        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpw = $dbpw;
        $this->dbname = $dbname;
        $this->dbcharset = $dbcharset;
        $this->pconnect = $pconnect;
        $this->tablepre = $tablepre;
        $this->time = $time;

        if ($pconnect) {
            if (!$this->link = mysqli_pconnect($dbhost, $dbuser, $dbpw)) {
                $this->halt('Can not connect to MySQL server');
            }
        } else {
            if (!$this->link = mysqli_connect($dbhost, $dbuser, $dbpw)) {
                $this->halt('Can not connect to MySQL server');
            }
        }

        if ($this->version() > '4.1') {
            if ($dbcharset) {
                mysqli_query('SET character_set_connection='.$dbcharset.', character_set_results='.$dbcharset.', character_set_client=binary', $this->link);
            }

            if ($this->version() > '5.0.1') {
                mysqli_query("SET sql_mode=''", $this->link);
            }
        }

        if ($dbname) {
            mysqli_select_db($dbname, $this->link);
        }
    }

    public function fetch_array($query, $result_type = mysqli_ASSOC)
    {
        return mysqli_fetch_array($query, $result_type);
    }

    public function result_first($sql)
    {
        $query = $this->query($sql);

        return $this->result($query, 0);
    }

    public function fetch_first($sql)
    {
        $query = $this->query($sql);

        return $this->fetch_array($query);
    }

    public function fetch_all($sql, $id = '')
    {
        $arr = array();
        $query = $this->query($sql);
        while ($data = $this->fetch_array($query)) {
            $id ? $arr[$data[$id]] = $data : $arr[] = $data;
        }

        return $arr;
    }

    public function cache_gc()
    {
        $this->query("DELETE FROM {$this->tablepre}sqlcaches WHERE expiry<$this->time");
    }

    public function query($sql, $type = '', $cachetime = false)
    {
        $query = mysqli_query($this->link, $sql);
        if (!$query) {
            $this->halt('MySQL Query Error', $sql);
        }
        ++$this->querynum;
        $this->histories[] = $sql;

        return $query;
    }

    public function affected_rows()
    {
        return mysqli_affected_rows($this->link);
    }

    public function error()
    {
        return ($this->link) ? mysqli_error($this->link) : mysqli_error();
    }

    public function errno()
    {
        return intval(($this->link) ? mysqli_errno($this->link) : mysqli_errno());
    }

    public function result($query, $row)
    {
        $query = @mysqli_result($query, $row);

        return $query;
    }

    public function num_rows($query)
    {
        $query = mysqli_num_rows($query);

        return $query;
    }

    public function num_fields($query)
    {
        return mysqli_num_fields($query);
    }

    public function free_result($query)
    {
        return mysqli_free_result($query);
    }

    public function insert_id()
    {
        return ($id = mysqli_insert_id($this->link)) >= 0 ? $id : $this->result($this->query('SELECT last_insert_id()'), 0);
    }

    public function fetch_row($query)
    {
        $query = mysqli_fetch_row($query);

        return $query;
    }

    public function fetch_fields($query)
    {
        return mysqli_fetch_field($query);
    }

    public function version()
    {
        return mysqli_get_server_info($this->link);
    }

    public function close()
    {
        return mysqli_close($this->link);
    }

    public function halt($message = '', $sql = '')
    {
        $error = mysqli_error();
        $errorno = mysqli_errno();
        if ($errorno == 2006 && $this->goneaway-- > 0) {
            $this->connect($this->dbhost, $this->dbuser, $this->dbpw, $this->dbname, $this->dbcharset, $this->pconnect, $this->tablepre, $this->time);
            $this->query($sql);
        } else {
            $s = '';
            if ($message) {
                $s = "<b>UCenter info:</b> $message<br />";
            }
            if ($sql) {
                $s .= '<b>SQL:</b>'.htmlspecialchars($sql).'<br />';
            }
            $s .= '<b>Error:</b>'.$error.'<br />';
            $s .= '<b>Errno:</b>'.$errorno.'<br />';
            $s = str_replace(UC_DBTABLEPRE, '[Table]', $s);
            exit($s);
        }
    }
}
