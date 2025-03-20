<?php

namespace App\Plugins\Http;

abstract class JsonStatus extends Status {
    /** @var string */
    const CONTENT_TYPE = 'application/json';

    /**
     * Constructor of this class
     * @param int $code
     * @param string $message
     * @param mixed $body
     */
    public function __construct(int $code, string $message, $body = []) {
        // Construct the parent with the default content type of json
        parent::__construct($code, $message, self::CONTENT_TYPE, $body);
    }

    /**
     * Function to send this response
     * @return string
     */
    public function getBody(): string {
        return json_encode($this->body);
    }
}
