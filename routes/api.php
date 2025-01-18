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
    Route::post("live/tv/create", [ApiController::class, "createlivetv"]);
    Route::post("live/tv/update", [ApiController::class, "updatelivetv"]);
    Route::delete("live/tv/delete", [ApiController::class, "deletelivetv"]);

    Route::get("live/radio", [ApiController::class, "liveradio"]);
    Route::post("live/radio/create", [ApiController::class, "createliveradio"]);
    Route::post("live/radio/update", [ApiController::class, "updateliveradio"]);
    Route::delete("live/radio/delete", [ApiController::class, "deleteliveradio"]);

    // categories
    Route::get("categories/list", [ApiController::class, "categories"]);
    Route::post("categories/create", [ApiController::class, "createcategories"]);
    Route::post("categories/update", [ApiController::class, "updatecategories"]);
    Route::delete("categories/delete", [ApiController::class, "deletecategories"]);
});
