<?php

namespace App\Plugins\Http\Response;

use App\Plugins\Http\JsonStatus;

class Ok extends JsonStatus {
    /** @var int */
    const STATUS_CODE = 200;
    /** @var string */
    const STATUS_MESSAGE = 'OK';

    /**
     * Constructor of this class
     * @param mixed $body
     */
    public function __construct($body = '') {
        parent::__construct(self::STATUS_CODE, self::STATUS_MESSAGE, $body);
    }
}
