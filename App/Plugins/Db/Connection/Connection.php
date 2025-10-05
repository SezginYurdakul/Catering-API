<?php

namespace App\Plugins\Db\Connection;

abstract class Connection implements IConnection {
    /** @var string */
    private $host;
    /** @var string */
    private $dbName;
    /** @var string */
    private $username;
    /** @var string */
    private $password;

    /**
     * Connection constructor.
     * @param string $host
     * @param string $dbName
     * @param string $username
     * @param string $password
     */
    public function __construct(string $host, string $dbName, string $username, string $password) {
        $this->host = $host;
        $this->dbName = $dbName;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Function to get the DSN for the connection
     * @return string
     */
    public abstract function getDsn(): string;

    /**
     * Function to get the host
     * @return string
     */
    public function getHost(): string {
        return $this->host;
    }

    /**
     * Function to get the db name
     * @return string
     */
    public function getDbName(): string {
        return $this->dbName;
    }

    /**
     * Function to get the username
     * @return string
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * Function to get the password
     * @return string
     */
    public function getPassword(): string {
        return $this->password;
    }
}
