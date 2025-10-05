<?php

namespace App\Plugins\Http;

abstract class Status implements IStatus {
    /**
     * The status code
     * @var int
     */
    protected $code;

    /**
     * The content-type
     * @var string
     */
    protected $contentType;

    /**
     * The response body
     * @var mixed
     */
    protected $body;

    /**
     * The message related to the Status
     * @var mixed
     */
    protected $message;

    /**
     * Status constructor.
     * @param int $code
     * @param string $message
     * @param string $contentType
     * @param mixed $body
     */
    public function __construct(int $code, string $message, string $contentType, $body = []) {
        $this->code = $code;
        $this->contentType = $contentType;
        $this->body = $body;
        $this->message = $message;
    }

    /**
     * Function to retrieve the status code
     * @return int
     */
    public function getCode(): int {
        return $this->code;
    }

    /**
     * Function to retrieve the message of the status code
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * Function to retrieve the content-type
     * @return string
     */
    public function getContentType(): string {
        return $this->contentType;
    }

    /**
     * Function to set the body
     * @param $body
     * @return $this
     */
    public function body($body) {
        $this->body = $body;
        return $this;
    }

    /**
     * Function to output this response
     */
    public function send() {
        $statusHeader = 'HTTP/1.1 ' . $this->getCode() . ' ' . $this->getMessage();
        $contentTypeHeader = 'Content-type: ' . $this->getContentType();
        header($statusHeader);
        header($contentTypeHeader);
        echo $this->getBody();
    }
}
