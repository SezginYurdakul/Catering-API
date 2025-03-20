<?php

namespace App\Plugins\Http\Exceptions;

use App\Plugins\Http;

class BadRequest extends Http\ApiException {
    /**
     * Constructor of this class
     * @param mixed $body
     */
    public function __construct($body = '') {
        parent::__construct(new Http\Response\BadRequest($body));
    }
}
