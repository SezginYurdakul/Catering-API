<?php

namespace App\Plugins\Http\Response;

use App\Plugins\Http\JsonStatus;

class Created extends JsonStatus {
    /** @var int */
    const STATUS_CODE = 201;
    /** @var string */
    const STATUS_MESSAGE = 'Created';

    /**
     * Constructor of this class
     * @param $body
     */
    public function __construct($body = '') {
        parent::__construct(self::STATUS_CODE, self::STATUS_MESSAGE, $body);
    }
}
