<?php

namespace App\Plugins\Http\Response;

use App\Plugins\Http\JsonStatus;

class NoContent extends JsonStatus {
    /** @var int */
    const STATUS_CODE = 204;
    /** @var string */
    const STATUS_MESSAGE = 'NoContent';

    /**
     * Constructor of this class
     */
    public function __construct() {
        parent::__construct(self::STATUS_CODE, self::STATUS_MESSAGE, '');
    }
}
