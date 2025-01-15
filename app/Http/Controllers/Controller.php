<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Info(title="LÝ GIA THẠNH", version="0.1")
 *  @OA\SecurityScheme(
*     type="http",
*     securityScheme="BearerAuth",
*     scheme="bearer",
*     bearerFormat="JWT"
* )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}