<?php

namespace App\Plugins\Db\Adapters;

use App\Plugins\Db\IDb;

class MySql implements IAdapter {
    /** @var IDb */
    private $db;

    /**
     * Function to get the db instance
     * @return mixed
     */
    function getDb() {
        return $this->db;
    }

    /**
     * Function to set the db instance
     * @param IDb $db
     */
    function setDb(IDb $db) {
        $this->db = $db;
    }
}
