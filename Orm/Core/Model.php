<?php
namespace Orm\Core;

use Orm\Helper\OrmAbstract;

/**
 * Class Model.
 * It contains methods and properties who help to work with database and are common for all of model.
 */
class Model extends OrmAbstract
{
    /**
     * @var null|string|int Record id.
     */
    protected $_id;
    /**
     * @var null|string Table name.
     */
    protected $_tableName;
    
    /**
     * @var null|object Connection to db.
     */
    protected $_conn = NULL;

    /**
     * Set connection property.
     */
    public function __construct($connect_db, $tableName, $id)
    {
        $this->_conn = $connect_db;
        $this->_tableName = $tableName;
        $this->_id = $id;
    }

    /**
     * Close connection to db.
     */
    public function __destruct()
    {
        $this->_conn = null;
    }

    /**
     * Get table name of a model.
     *
     * @return string
     */
    protected function _getTableName()
    {
        return $this->_tableName;
    }

    /**
     * Get properties of object.
     *
     * @return array|null
     */
    protected function _getPropetries()
    {
        $properties = get_object_vars($this);
        $public_prop = [];
        foreach ($properties as $key => $prop) {
            if ($prop != NULL && !is_object($prop) && $key != '_tableName') {
                $public_prop[substr($key, 1)] = '"' . $prop . '"';
            }
        }
        return $public_prop;
    }

    /**
     * Fetch record by id
     *
     * @param int|string $id Record Id.
     *
     * @return mixed
     */
    protected function _getById($id)
    {
        $sql = 'SELECT * FROM' . ' ' . $this->_getTableName() . ' '
             . 'WHERE id = ' . $id;
        try {
            $sth = $this->_conn->prepare($sql);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Update existing record in database.
     *
     * @return bool|object
     */
    protected function _update()
    {
        $update_array = [];
        foreach ($this->_getPropetries() as $key => $prop) {
            $update_array[] = $key . '=' . $prop . ' ';
        }
        try {
            $sql = 'UPDATE ' . $this->_getTableName() . ' '
                 . 'SET ' . implode(', ',$update_array) . ' '
                 . 'WHERE id = ' . $this->getId();
            if($this->_execute($sql)) {
                return $this->load($this->getId());
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Create new record in database.
     *
     * @return bool|object
     */
    protected function _create()
    {
        if(!empty($this->_getPropetries())) {
            try {
                $sql = 'INSERT INTO' . ' ' . $this->_getTableName() . '(' . implode(', ', array_keys($this->_getPropetries())) . ') '
                     . 'VALUES(' . implode(', ', array_values($this->_getPropetries())) . ')';
                if($this->_execute($sql)) {
                    return $this->load($this->_conn->lastInsertId());
                } else {
                    return false;
                }
            } catch (\PDOException $e) {
                die($e->getMessage());
            }
        } else {
            return false;
        }
    }

    /**
     * Delete record in database.
     *
     * @return bool
     */
    public function delete()
    {
        try {
            $sql = 'DELETE FROM' . ' ' . $this->_getTableName() . ' '
                 . 'WHERE id = ' . $this->getId();
            return $this->_execute($sql);
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Is new record or existing ?.
     *
     * @return bool
     */
    protected function _isNewRecord()
    {
        return $this->getId() == NULL ? true : false;
    }

    /**
     * Get record id.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Helper function who execute sql query
     *
     * @param string $sql Query into database.
     * @param array|null $params Params for execute method.
     *
     * @return bool
     */
    protected function _execute($sql, $params = array())
    {
        $sth = $this->_conn->prepare($sql);
        $result = $sth->execute($params);
        return $result;
    }
}