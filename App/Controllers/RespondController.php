<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Response\NotFound;
use App\Plugins\Http\Response\BadRequest;
use App\Plugins\Http\Response\InternalServerError;

class RespondController
{
    /**
     * Send a 200 OK response.
     *
     * @param array $data
     * @return void
     */
    protected function respondOk(array $data): void
    {
        (new Ok($data))->send();
    }

    /**
     * Send a 201 Created response.
     *
     * @param array $data
     * @return void
     */
    protected function respondCreated(array $data): void
    {
        (new Created($data))->send();
    }

    /**
     * Send a 204 No Content response.
     *
     * @return void
     */
    protected function respondNoContent(): void
    {
        (new NoContent())->send();
    }

    /**
     * Send a 404 Not Found response.
     *
     * @param array $data
     * @return void
     */
    protected function respondNotFound(array $data): void
    {
        (new NotFound($data))->send();
    }

    /**
     * Send a 400 Bad Request response.
     *
     * @param array $data
     * @return void
     */
    protected function respondBadRequest(array $data): void
    {
        (new BadRequest($data))->send();
    }

    /**
     * Send a 500 Internal Server Error response.
     *
     * @param array $data
     * @return void
     */
    protected function respondInternalServerError(array $data): void
    {
        (new InternalServerError($data))->send();
    }
}
