<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDO Informix Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the query builder
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/database/
 */
class CI_DB_pdo_informix_driver extends CI_DB_pdo_driver
{
    /**
     * Sub-driver
     *
     * @var	string
     */
    public $subdriver = 'informix';

    // --------------------------------------------------------------------

    /**
     * ORDER BY random keyword
     *
     * @var	array
     */
    protected $_random_keyword = array('ASC', 'ASC');  // Currently not supported

    // --------------------------------------------------------------------

    /**
     * Class constructor
     *
     * Builds the DSN if not already set.
     *
     * @param	array	$params
     * @return	void
     */
    public function __construct($params)
    {
        parent::__construct($params);

        if (empty($this->dsn)) {
            $this->dsn = 'informix:';

            // Pre-defined DSN
            if (empty($this->hostname) && empty($this->host) && empty($this->port) && empty($this->service)) {
                if (isset($this->DSN)) {
                    $this->dsn .= 'DSN=' . $this->DSN;
                } elseif (!empty($this->database)) {
                    $this->dsn .= 'DSN=' . $this->database;
                }

                return;
            }

            if (isset($this->host)) {
                $this->dsn .= 'host=' . $this->host;
            } else {
                $this->dsn .= 'host=' . (empty($this->hostname) ? '127.0.0.1' : $this->hostname);
            }

            if (isset($this->service)) {
                $this->dsn .= '; service=' . $this->service;
            } elseif (!empty($this->port)) {
                $this->dsn .= '; service=' . $this->port;
            }

            empty($this->database) OR $this->dsn .= '; database=' . $this->database;
            empty($this->server) OR $this->dsn .= '; server=' . $this->server;

            $this->dsn .= '; protocol=' . (isset($this->protocol) ? $this->protocol : 'onsoctcp')
                . '; EnableScrollableCursors=1';
        }
    }

    // --------------------------------------------------------------------

    /**
     * Show table query
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param	bool	$prefix_limit
     * @return	string
     */
    protected function _list_tables($prefix_limit = FALSE)
    {
        $sql = "SELECT \"tabname\" FROM \"systables\"
\t\t\tWHERE \"tabid\" > 99 AND \"tabtype\" = 'T' AND LOWER(\"owner\") = " . $this->escape(strtolower($this->username));

        if ($prefix_limit === TRUE && $this->dbprefix !== '') {
            $sql .= ' AND "tabname" LIKE \'' . $this->escape_like_str($this->dbprefix) . "%' "
                . sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Show column query
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @param	string	$table
     * @return	string
     */
    protected function _list_columns($table = '')
    {
        if (strpos($table, '.') !== FALSE) {
            sscanf($table, '%[^.].%s', $owner, $table);
        } else {
            $owner = $this->username;
        }

        return "SELECT \"colname\" FROM \"systables\", \"syscolumns\"
\t\t\tWHERE \"systables\".\"tabid\" = \"syscolumns\".\"tabid\"
\t\t\t\tAND \"systables\".\"tabtype\" = 'T'
\t\t\t\tAND LOWER(\"systables\".\"owner\") = " . $this->escape(strtolower($owner)) . "
\t\t\t\tAND LOWER(\"systables\".\"tabname\") = " . $this->escape(strtolower($table));
    }

    // --------------------------------------------------------------------

    /**
     * Returns an object with field data
     *
     * @param	string	$table
     * @return	array
     */
    public function field_data($table)
    {
        $sql = "SELECT \"syscolumns\".\"colname\" AS \"name\",
\t\t\t\tCASE \"syscolumns\".\"coltype\"
\t\t\t\t\tWHEN 0 THEN 'CHAR'
\t\t\t\t\tWHEN 1 THEN 'SMALLINT'
\t\t\t\t\tWHEN 2 THEN 'INTEGER'
\t\t\t\t\tWHEN 3 THEN 'FLOAT'
\t\t\t\t\tWHEN 4 THEN 'SMALLFLOAT'
\t\t\t\t\tWHEN 5 THEN 'DECIMAL'
\t\t\t\t\tWHEN 6 THEN 'SERIAL'
\t\t\t\t\tWHEN 7 THEN 'DATE'
\t\t\t\t\tWHEN 8 THEN 'MONEY'
\t\t\t\t\tWHEN 9 THEN 'NULL'
\t\t\t\t\tWHEN 10 THEN 'DATETIME'
\t\t\t\t\tWHEN 11 THEN 'BYTE'
\t\t\t\t\tWHEN 12 THEN 'TEXT'
\t\t\t\t\tWHEN 13 THEN 'VARCHAR'
\t\t\t\t\tWHEN 14 THEN 'INTERVAL'
\t\t\t\t\tWHEN 15 THEN 'NCHAR'
\t\t\t\t\tWHEN 16 THEN 'NVARCHAR'
\t\t\t\t\tWHEN 17 THEN 'INT8'
\t\t\t\t\tWHEN 18 THEN 'SERIAL8'
\t\t\t\t\tWHEN 19 THEN 'SET'
\t\t\t\t\tWHEN 20 THEN 'MULTISET'
\t\t\t\t\tWHEN 21 THEN 'LIST'
\t\t\t\t\tWHEN 22 THEN 'Unnamed ROW'
\t\t\t\t\tWHEN 40 THEN 'LVARCHAR'
\t\t\t\t\tWHEN 41 THEN 'BLOB/CLOB/BOOLEAN'
\t\t\t\t\tWHEN 4118 THEN 'Named ROW'
\t\t\t\t\tELSE \"syscolumns\".\"coltype\"
\t\t\t\tEND AS \"type\",
\t\t\t\t\"syscolumns\".\"collength\" as \"max_length\",
\t\t\t\tCASE \"sysdefaults\".\"type\"
\t\t\t\t\tWHEN 'L' THEN \"sysdefaults\".\"default\"
\t\t\t\t\tELSE NULL
\t\t\t\tEND AS \"default\"
\t\t\tFROM \"syscolumns\", \"systables\", \"sysdefaults\"
\t\t\tWHERE \"syscolumns\".\"tabid\" = \"systables\".\"tabid\"
\t\t\t\tAND \"systables\".\"tabid\" = \"sysdefaults\".\"tabid\"
\t\t\t\tAND \"syscolumns\".\"colno\" = \"sysdefaults\".\"colno\"
\t\t\t\tAND \"systables\".\"tabtype\" = 'T'
\t\t\t\tAND LOWER(\"systables\".\"owner\") = " . $this->escape(strtolower($this->username)) . "
\t\t\t\tAND LOWER(\"systables\".\"tabname\") = " . $this->escape(strtolower($table)) . "
\t\t\tORDER BY \"syscolumns\".\"colno\"";

        return (($query = $this->query($sql)) !== FALSE)
            ? $query->result_object()
            : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @param	string	$table
     * @param	array	$values
     * @return	string
     */
    protected function _update($table, $values)
    {
        $this->qb_limit = FALSE;
        $this->qb_orderby = array();
        return parent::_update($table, $values);
    }

    // --------------------------------------------------------------------

    /**
     * Truncate statement
     *
     * Generates a platform-specific truncate string from the supplied data
     *
     * If the database does not support the TRUNCATE statement,
     * then this method maps to 'DELETE FROM table'
     *
     * @param	string	$table
     * @return	string
     */
    protected function _truncate($table)
    {
        return 'TRUNCATE TABLE ONLY ' . $table;
    }

    // --------------------------------------------------------------------

    /**
     * Delete statement
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @param	string	$table
     * @return	string
     */
    protected function _delete($table)
    {
        $this->qb_limit = FALSE;
        return parent::_delete($table);
    }

    // --------------------------------------------------------------------

    /**
     * LIMIT
     *
     * Generates a platform-specific LIMIT clause
     *
     * @param	string	$sql	$SQL Query
     * @return	string
     */
    protected function _limit($sql)
    {
        $select = 'SELECT ' . ($this->qb_offset ? 'SKIP ' . $this->qb_offset : '') . 'FIRST ' . $this->qb_limit . ' ';
        return preg_replace('/^(SELECT\s)/i', $select, $sql, 1);
    }
}
