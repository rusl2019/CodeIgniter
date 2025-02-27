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
 * PDO Firebird Database Adapter Class
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
class CI_DB_pdo_firebird_driver extends CI_DB_pdo_driver
{
    /**
     * Sub-driver
     *
     * @var	string
     */
    public $subdriver = 'firebird';

    // --------------------------------------------------------------------

    /**
     * ORDER BY random keyword
     *
     * @var	array
     */
    protected $_random_keyword = array('RAND()', 'RAND()');

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
            $this->dsn = 'firebird:';

            if (!empty($this->database)) {
                $this->dsn .= 'dbname=' . $this->database;
            } elseif (!empty($this->hostname)) {
                $this->dsn .= 'dbname=' . $this->hostname;
            }

            empty($this->char_set) OR $this->dsn .= ';charset=' . $this->char_set;
            empty($this->role) OR $this->dsn .= ';role=' . $this->role;
        } elseif (!empty($this->char_set) && strpos($this->dsn, 'charset=', 9) === FALSE) {
            $this->dsn .= ';charset=' . $this->char_set;
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
        $sql = 'SELECT "RDB$RELATION_NAME" FROM "RDB$RELATIONS" WHERE "RDB$RELATION_NAME" NOT LIKE \'RDB$%\' AND "RDB$RELATION_NAME" NOT LIKE \'MON$%\'';

        if ($prefix_limit === TRUE && $this->dbprefix !== '') {
            return $sql . ' AND "RDB$RELATION_NAME" LIKE \'' . $this->escape_like_str($this->dbprefix) . "%' "
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
        return 'SELECT "RDB$FIELD_NAME" FROM "RDB$RELATION_FIELDS" WHERE "RDB$RELATION_NAME" = ' . $this->escape($table);
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
        $sql = "SELECT \"rfields\".\"RDB\$FIELD_NAME\" AS \"name\",
\t\t\t\tCASE \"fields\".\"RDB\$FIELD_TYPE\"
\t\t\t\t\tWHEN 7 THEN 'SMALLINT'
\t\t\t\t\tWHEN 8 THEN 'INTEGER'
\t\t\t\t\tWHEN 9 THEN 'QUAD'
\t\t\t\t\tWHEN 10 THEN 'FLOAT'
\t\t\t\t\tWHEN 11 THEN 'DFLOAT'
\t\t\t\t\tWHEN 12 THEN 'DATE'
\t\t\t\t\tWHEN 13 THEN 'TIME'
\t\t\t\t\tWHEN 14 THEN 'CHAR'
\t\t\t\t\tWHEN 16 THEN 'INT64'
\t\t\t\t\tWHEN 27 THEN 'DOUBLE'
\t\t\t\t\tWHEN 35 THEN 'TIMESTAMP'
\t\t\t\t\tWHEN 37 THEN 'VARCHAR'
\t\t\t\t\tWHEN 40 THEN 'CSTRING'
\t\t\t\t\tWHEN 261 THEN 'BLOB'
\t\t\t\t\tELSE NULL
\t\t\t\tEND AS \"type\",
\t\t\t\t\"fields\".\"RDB\$FIELD_LENGTH\" AS \"max_length\",
\t\t\t\t\"rfields\".\"RDB\$DEFAULT_VALUE\" AS \"default\"
\t\t\tFROM \"RDB\$RELATION_FIELDS\" \"rfields\"
\t\t\t\tJOIN \"RDB\$FIELDS\" \"fields\" ON \"rfields\".\"RDB\$FIELD_SOURCE\" = \"fields\".\"RDB\$FIELD_NAME\"
\t\t\tWHERE \"rfields\".\"RDB\$RELATION_NAME\" = " . $this->escape($table) . "
\t\t\tORDER BY \"rfields\".\"RDB\$FIELD_POSITION\"";

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
        return 'DELETE FROM ' . $table;
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
     * @param	string	$sql	SQL Query
     * @return	string
     */
    protected function _limit($sql)
    {
        // Limit clause depends on if Interbase or Firebird
        if (stripos($this->version(), 'firebird') !== FALSE) {
            $select = 'FIRST ' . $this->qb_limit
                . ($this->qb_offset > 0 ? ' SKIP ' . $this->qb_offset : '');
        } else {
            $select = 'ROWS '
                . ($this->qb_offset > 0 ? $this->qb_offset . ' TO ' . ($this->qb_limit + $this->qb_offset) : $this->qb_limit);
        }

        return preg_replace('`SELECT`i', 'SELECT ' . $select, $sql);
    }

    // --------------------------------------------------------------------

    /**
     * Insert batch statement
     *
     * Generates a platform-specific insert string from the supplied data.
     *
     * @param	string	$table	Table name
     * @param	array	$keys	INSERT keys
     * @param	array	$values	INSERT values
     * @return	string|bool
     */
    protected function _insert_batch($table, $keys, $values)
    {
        return ($this->db_debug) ? $this->display_error('db_unsupported_feature') : FALSE;
    }
}
