<?php

declare(strict_types=1);

namespace App\Controllers;


class IndexController extends RespondController {
    /**
     * Controller function used to test whether the project was set up properly.
     * @return void
     */
    public function test() {
    $this->respondOk(['message' => 'Welcome to Catering API!']);
    }
}
