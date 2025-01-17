<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

Route::post("register", [ApiController::class, "register"]);
Route::post("login", [ApiController::class, "login"]);

Route::group([
    "middleware" => ["auth:api"]
], function(){
    // users    
    Route::get("users/list", [ApiController::class, "userlist"]);
    Route::post("users/create", [ApiController::class, "usercreate"]);
    Route::post("users/update", [ApiController::class, "userupdate"]);
    Route::post("users/disabled", [ApiController::class, "userdisabled"]);

    // television and radio
    Route::get("live/tv", [ApiController::class, "livetv"]);
});
