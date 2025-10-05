<?php

namespace App\Plugins\Http;

abstract class ApiException extends \Exception {
    /** @var IStatus */
    private $status;

    /**
     * Constructor of this class
     * @param IStatus $status The status for this exception
     */
    public function __construct(IStatus $status) {
        // Construct parent:
        parent::__construct($status->getMessage(), $status->getCode());
        // Assign the status:
        $this->status = $status;
    }

    /**
     * Function to send the exception
     */
    public function send() {
        $this->status->send();
    }
}
