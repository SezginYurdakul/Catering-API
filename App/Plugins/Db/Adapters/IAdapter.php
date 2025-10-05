<?php

namespace App\Plugins\Db\Adapters;

use App\Plugins\Db\IDb;

interface IAdapter {
    function getDb();

    function setDb(IDb $db);
}
