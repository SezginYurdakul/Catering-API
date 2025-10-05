<?php


namespace App\Plugins\Db\Connection;

class Mysql extends Connection {
    /**
     * Function to get the DSN for the connection
     * @return string
     */
    public function getDsn(): string {
        return 'mysql:host=' . $this->getHost() . ';dbname=' . $this->getDbName();
    }
}
