<?php

namespace App\Plugins\Http;

interface IStatus {
    /**
     * Function to retrieve the status code
     * @return int
     */
    public function getCode(): int;

    /**
     * Function to retrieve the message for the code
     * @return string
     */
    public function getMessage(): string;

    /**
     * Function to retrieve the content type
     * @return string
     */
    public function getContentType(): string;

    /**
     * Function to set the body
     * @param $body
     */
    public function body($body);

    /**
     * Function to retrieve the body
     * @return string
     */
    public function getBody(): string;

    /**
     * Function to send this response
     */
    public function send();
}
