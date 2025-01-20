<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Account;
use Illuminate\Support\Facades\Crypt;
use App\Models\Config;
use App\Models\Lichphatsong;
use App\Models\Category;
use App\Models\Video;
use App\Models\Playlist;
use App\Models\Plus;
use Auth;
use Validator;
use DateTime;

class ApiController extends Controller
{    
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     operationId="register",
     *     tags={"Authentication"},
     *     summary="User Register",
     *     description="User register and JWT token generation",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","username","email","password"},
     *             @OA\Property(property="name", type="string", format="text"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="username", type="string", format="text"),
     *             @OA\Property(property="phone", type="string", format="text"),
     *             @OA\Property(property="email", type="string", format="text"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful register with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="array", @OA\Items(
     *                 @OA\Property(property="name", type="string"),      
     *                      @OA\Property(property="username", type="string"),  
     *                      @OA\Property(property="phone", type="string"),  
     *                      @OA\Property(property="email", type="string")
     *              ))
     *         )
     *     )
     * )
     */
    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'username' => 'required|string|between:2,100',
            'email' => 'required|string|max:100|email|unique:users',
            'phone' => 'required|string|max:100|unique:users',
            'password' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user_param = array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        );

        $user_param['status'] = 1;

        $user = User::create($user_param);

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user->only(['username', 'name', 'phone', 'email'])
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     operationId="login",
     *     tags={"Authentication"},
     *     summary="User login",
     *     description="User login and JWT token generation",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", format="text"),
     *             @OA\Property(property="password", type="string", format="password")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful register with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string"),
     *             @OA\Property(property="expires_in", type="string"),
     *             @OA\Property(property="user", type="array", @OA\Items(
     *                 @OA\Property(property="name", type="string"),      
     *                      @OA\Property(property="phone", type="string"),  
     *                      @OA\Property(property="email", type="string"),  
     *                      @OA\Property(property="username", type="string")
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()->only(['username', 'name', 'phone', 'email'])
        ]);
    }    

    /**
     * @OA\GET(
     *     path="/api/users/list",
     *     operationId="UserList",
     *     tags={"Users"},
     *     summary="List of users",
     *     description="List of users",
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="user_list", type="array", @OA\Items(
        *                  @OA\Property(property="username", type="string"),
        *                  @OA\Property(property="name", type="string"),
        *                  @OA\Property(property="phone", type="string"),
        *                  @OA\Property(property="email", type="string")
        *              ))
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function userlist(Request $request) {
    	$data = User::where([['status','=', 1]])->orderBy('name')
            ->select('id', 'username', 'name', 'phone', 'email')
            ->get();
        return [
            'code' => "200",
            'error' => "",
            'data' => $data
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/users/create",
     *     operationId="createUser",
     *     tags={"Users"},
     *     summary="User Create",
     *     description="Create New User",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","username","email","password"},
     *             @OA\Property(property="name", type="string", format="text"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="username", type="string", format="text"),
     *             @OA\Property(property="phone", type="string", format="text"),
     *             @OA\Property(property="email", type="string", format="text"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful register with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),   
     *                      @OA\Property(property="name", type="string"),      
     *                      @OA\Property(property="username", type="string"),  
     *                      @OA\Property(property="phone", type="string"),  
     *                      @OA\Property(property="email", type="string")
     *              ))
     *         )
     *     )
     * )
     */
    public function usercreate (Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'username' => 'required|string|between:2,100',
            'email' => 'required|string|max:100|email|unique:users',
            'phone' => 'nullable|string|max:100|unique:users',
            'password' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user_param = array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        );
        $user_param['status'] = 1;

        $user = User::create($user_param);

        return response()->json([
            'message' => 'User successfully created',
            'user' => $user->only(['id', 'username', 'name', 'phone', 'email'])
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/users/update",
     *     operationId="updateUser",
     *     tags={"Users"},
     *     summary="User Update",
     *     description="Update User",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id","name","email","password","phone"},
     *             @OA\Property(property="id", type="string", format="text"),
     *             @OA\Property(property="name", type="string", format="text"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="phone", type="string", format="text"),
     *             @OA\Property(property="email", type="string", format="text"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful update user with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),   
     *                      @OA\Property(property="name", type="string"),      
     *                      @OA\Property(property="username", type="string"),  
     *                      @OA\Property(property="phone", type="string"),  
     *                      @OA\Property(property="email", type="string")
     *              ))
     *         )
     *     )
     * )
     */
    public function userupdate (Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required|string|between:2,100',
            // 'username' => 'required|string|between:2,100',
            'email' => 'required|string|max:100|email|unique:users,email,' . $request->id,
            'phone' => 'nullable|string|max:100|unique:users,phone,' . $request->id,
            'password' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::find($request->id);
        $user->name = $request->name;
        // $user->username = $request->username;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = bcrypt($request->password);
        $user->update();

        return response()->json([
            'message' => 'User successfully updated',
            'user' => $user->only(['id', 'username', 'name', 'phone', 'email'])
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/users/disabled",
     *     operationId="disabledUser",
     *     tags={"Users"},
     *     summary="Disabled Update",
     *     description="Disabled User",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id"},
     *             @OA\Property(property="id", type="string", format="text")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful disabled with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),   
     *                      @OA\Property(property="name", type="string"),      
     *                      @OA\Property(property="username", type="string"),  
     *                      @OA\Property(property="phone", type="string"),  
     *                      @OA\Property(property="email", type="string")
     *              ))
     *         )
     *     )
     * )
     */
    public function userdisabled (Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::find($request->id);
        $user->status = 0;
        $user->update();

        return response()->json([
            'message' => 'User successfully disabled',
            'user' => $user->only(['username', 'name', 'phone', 'email', 'status'])
        ], 201);
    }

    /**
     * @OA\GET(
     *     path="/api/live/tv",
     *     operationId="LiveTV",
     *     tags={"Others"},
     *     summary="TV Broadcast Schedule",
     *     description="TV Broadcast Schedule",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Day",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="live_url", type="string"),
     *                  @OA\Property(property="playback_url", type="string"),
     *                  @OA\Property(property="list", type="string"),
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function livetv(Request $req) {
    	$ngay = DateTime::createFromFormat('d/m/Y', $req->date)->format('Y-m-d');
    	$lps = Lichphatsong::where([['ngay','=', $ngay],['type','=',0]])->get();
		$conf = Config::where('name','liveTv')->first();
		$conf1 = Config::where('name','playbackTv')->first();
    	$rs = array();
        $i = 1;
        foreach ($lps as $k) {
            # code...
			$duration = 0;
            $milisecon = 0;
            $times = explode(":", $k->time);
            if (count($times)==2) {
            	# code...
            	$milisecon = $times[0]*60*60 + $times[1]*60;
            }
            $timecode = str_replace("-", "", $k->timecode);
            $timecode = str_replace(":", "", $timecode);
            $timecode = str_replace(" ", "", $timecode);
			$duration = $k->duration * 1000;
            $l = ['id'=>$k->id,'time'=>$k->time,'title'=>$k->title, 'playliststart'=>$timecode, 'duration'=>$duration,'milisecon'=>$milisecon, 'timecode'=>$k->timecode, 'youtubeid'=>$k->youtubeid, 'url_mp4'=>$k->url_mp4];
            array_push($rs, $l);
            $i++;
        }
        return [
            'code' => "200",
            'error'=>"",
            'data'=>[
                'live_url' => $conf->value,
				'playback_url' => $conf1->value,
                'list'=>$rs
            ]
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/live/tv/create",
     *     operationId="createLiveTV",
     *     tags={"Others"},
     *     summary="Create Live TV (api mới, cần check lại nghiệp vụ)",
     *     description="Create Live TV",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ngay","time","title","youtubeid","url_mp4","duration","creator_id"},
     *             @OA\Property(property="ngay", type="string", format="string", example="17/01/2025"),
     *             @OA\Property(property="time", type="string", format="text", example="15:00"),
     *             @OA\Property(property="title", type="string", format="text"),
     *             @OA\Property(property="youtubeid", type="string", format="text"),
     *             @OA\Property(property="timecode", type="string", format="text", example="17/01/2025 15:00:24"),
     *             @OA\Property(property="url_mp4", type="string", format="text"),
     *             @OA\Property(property="duration", type="string", format="text"),
     *             @OA\Property(property="creator_id", type="string", format="text")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful create live tv",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),   
     *                      @OA\Property(property="time", type="string"),      
     *                      @OA\Property(property="title", type="string"),  
     *                      @OA\Property(property="playliststart", type="string"),  
     *                      @OA\Property(property="duration", type="string"),  
     *                      @OA\Property(property="milisecon", type="string"),  
     *                      @OA\Property(property="timecode", type="string"),  
     *                      @OA\Property(property="youtubeid", type="string"),  
     *                      @OA\Property(property="url_mp4", type="string"),  
     *                      @OA\Property(property="creator_id", type="string")
     *              ))
     *         )
     *     )
     * )
     */
    public function createlivetv (Request $request){
        $validator = Validator::make($request->all(), [
            'ngay' => 'required|date_format:d/m/Y',
            'time' => 'required',
            'title' => 'required',
            'youtubeid' => 'required',
            'url_mp4' => 'required',
            'timecode' => 'nullable|date_format:d/m/Y H:i:s',
            'duration' => 'numeric|min:0|required',
            'creator_id' => 'required|exists:users,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $param = array_merge(
            $validator->validated(),
            ['ngay' => DateTime::createFromFormat('d/m/Y', $request->ngay)->format('Y-m-d'),
            'timecode' => $request->timecode != '' ? DateTime::createFromFormat('d/m/Y H:i:s', $request->timecode)->format('Y-m-d H:i:s') : null]
        );

        $param['type'] = 0;
        $param['creator_id'] = $request->creator_id;

        $object = LichPhatSong::create($param);
        
        $duration = 0;
        $milisecon = 0;
        $times = explode(":", $object->time);
        if (count($times)==2) {
            # code...
            $milisecon = $times[0]*60*60 + $times[1]*60;
        }
        $timecode = str_replace("-", "", $object->timecode);
        $timecode = str_replace(":", "", $timecode);
        $timecode = str_replace(" ", "", $timecode);
        $duration = $object->duration * 1000;
        $result_data = ['id' => $object->id, 'time' => $object->time, 'title'=>$object->title, 
            'playliststart' => $timecode, 'duration' => $duration,'milisecon' => $milisecon, 
            'timecode'=>$object->timecode, 'youtubeid'=>$object->youtubeid, 'url_mp4'=>$object->url_mp4];
            

        return response()->json([
            'message' => 'Create Live TV successful',
            'data' => $result_data
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/live/tv/update",
     *     operationId="updateLiveTV",
     *     tags={"Others"},
     *     summary="Update Live TV (api mới, cần check lại nghiệp vụ)",
     *     description="Update Live TV",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id","ngay","time","title","youtubeid","url_mp4","duration", "updater_id"},
     *             @OA\Property(property="id", type="string", format="text"),
     *             @OA\Property(property="ngay", type="string", format="text", example="17/01/2025"),
     *             @OA\Property(property="time", type="string", format="text"),
     *             @OA\Property(property="title", type="string", format="text"),
     *             @OA\Property(property="youtubeid", type="string", format="text"),
     *             @OA\Property(property="url_mp4", type="string", format="text"),
     *             @OA\Property(property="timecode", type="string", format="text", example="17/01/2025 15:00:24"),
     *             @OA\Property(property="duration", type="string", format="text"),
     *             @OA\Property(property="updater_id", type="string", format="text")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful updated live tv",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),   
     *                      @OA\Property(property="time", type="string"),      
     *                      @OA\Property(property="title", type="string"),  
     *                      @OA\Property(property="playliststart", type="string"),  
     *                      @OA\Property(property="duration", type="string"),  
     *                      @OA\Property(property="milisecon", type="string"),  
     *                      @OA\Property(property="timecode", type="string"),  
     *                      @OA\Property(property="youtubeid", type="string"),  
     *                      @OA\Property(property="url_mp4", type="string"),  
     *                      @OA\Property(property="updater_id", type="string")
     *              ))
     *         )
     *     )
     * )
     */
    public function updatelivetv (Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:lichphatsongs,id',
            'ngay' => 'required|date_format:d/m/Y',
            'time' => 'required',
            'title' => 'required',
            'youtubeid' => 'required',
            'url_mp4' => 'required',
            'timecode' => 'nullable|date_format:d/m/Y H:i:s',
            'duration' => 'numeric|min:0|required',
            'updater_id' => 'required|exists:users,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $object = LichPhatSong::find($request->id);
        $object->ngay = DateTime::createFromFormat('d/m/Y', $request->ngay)->format('Y-m-d');
        $object->time = $request->time;
        $object->title = $request->title;
        $object->youtubeid = $request->youtubeid;
        $object->url_mp4 = $request->url_mp4;
        $object->timecode = $request->timecode != '' ? DateTime::createFromFormat('d/m/Y H:i:s', $request->timecode)->format('Y-m-d H:i:s') : null;
        $object->duration = $request->duration;
        $object->updater_id = $request->updater_id;

        $object->update();
        
        $duration = 0;
        $milisecon = 0;
        $times = explode(":", $object->time);
        if (count($times)==2) {
            # code...
            $milisecon = $times[0]*60*60 + $times[1]*60;
        }
        $timecode = str_replace("-", "", $object->timecode);
        $timecode = str_replace(":", "", $timecode);
        $timecode = str_replace(" ", "", $timecode);
        $duration = $object->duration * 1000;
        $result_data = ['id' => $object->id, 'time' => $object->time, 'title'=>$object->title, 
            'playliststart' => $timecode, 'duration' => $duration,'milisecon' => $milisecon, 
            'timecode'=>$object->timecode, 'youtubeid'=>$object->youtubeid, 'url_mp4'=>$object->url_mp4, 
            'creator_id' => $object->creator_id, 'updater_id' => $object->updater_id];            

        return response()->json([
            'message' => 'Update Live TV successful',
            'data' => $result_data
        ], 201);
    }

    /**
     * @OA\DELETE(
     *     path="/api/live/tv/delete",
     *     operationId="deleteLiveTV",
     *     tags={"Others"},
     *     summary="Delete Live TV (api mới, cần check lại nghiệp vụ)",
     *     description="Delete Live TV",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function deletelivetv(Request $request) {        
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:lichphatsongs,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
    	$object = LichPhatSong::find($request->id);
        $object_name = $object->title;
        $object->delete();
        return [
            'code' => "Xóa thành công " . $object_name,
            'error' => ""
        ];
    }

    /**
     * @OA\GET(
     *     path="/api/live/radio",
     *     operationId="LiveRadio",
     *     tags={"Others"},
     *     summary="Radio Broadcast Schedule",
     *     description="Radio Broadcast Schedule",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Day",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="live_url", type="string"),
     *                  @OA\Property(property="playback_url", type="string"),
     *                  @OA\Property(property="list", type="string"),
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function liveradio(Request $req) {
    	$ngay = DateTime::createFromFormat('d/m/Y', $req->date)->format('Y-m-d');
    	$lps = Lichphatsong::where([['ngay','=', $ngay],['type','=',1]])->get();
		$conf = Config::where('name','liveTv')->first();
		$conf1 = Config::where('name','playbackTv')->first();
    	$rs = array();
        $i = 1;
        foreach ($lps as $k) {
            # code...
			$duration = 0;
            $milisecon = 0;
            $times = explode(":", $k->time);
            if (count($times)==2) {
            	# code...
            	$milisecon = $times[0]*60*60 + $times[1]*60;
            }
            $timecode = str_replace("-", "", $k->timecode);
            $timecode = str_replace(":", "", $timecode);
            $timecode = str_replace(" ", "", $timecode);
			$duration = $k->duration * 1000;
            $l = ['id'=>$k->id,'time'=>$k->time,'title'=>$k->title, 'playliststart'=>$timecode, 'duration'=>$duration,'milisecon'=>$milisecon, 'timecode'=>$k->timecode, 'youtubeid'=>$k->youtubeid, 'url_mp4'=>$k->url_mp4];
            array_push($rs, $l);
            $i++;
        }
        return [
            'code' => "200",
            'error'=>"",
            'data'=>[
                'live_url' => $conf->value,
				'playback_url' => $conf1->value,
                'list'=>$rs
            ]
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/live/radio/create",
     *     operationId="createLiveRadio",
     *     tags={"Others"},
     *     summary="Create Live Radio (api mới, cần check lại nghiệp vụ)",
     *     description="Create Live Radio",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ngay","time","title","youtubeid","url_mp4","duration","creator_id"},
     *             @OA\Property(property="ngay", type="string", format="string", example="17/01/2025"),
     *             @OA\Property(property="time", type="string", format="text", example="15:00"),
     *             @OA\Property(property="title", type="string", format="text"),
     *             @OA\Property(property="youtubeid", type="string", format="text"),
     *             @OA\Property(property="timecode", type="string", format="text", example="17/01/2025 15:00:24"),
     *             @OA\Property(property="url_mp4", type="string", format="text"),
     *             @OA\Property(property="duration", type="string", format="text"),
     *             @OA\Property(property="creator_id", type="string", format="text")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful create live radio",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),   
     *                      @OA\Property(property="time", type="string"),      
     *                      @OA\Property(property="title", type="string"),  
     *                      @OA\Property(property="playliststart", type="string"),  
     *                      @OA\Property(property="duration", type="string"),  
     *                      @OA\Property(property="milisecon", type="string"),  
     *                      @OA\Property(property="timecode", type="string"),  
     *                      @OA\Property(property="youtubeid", type="string"),  
     *                      @OA\Property(property="url_mp4", type="string"),
     *                      @OA\Property(property="creator_id", type="string"),
     *              ))
     *         )
     *     )
     * )
     */
    public function createliveradio (Request $request){
        $validator = Validator::make($request->all(), [
            'ngay' => 'required|date_format:d/m/Y',
            'time' => 'required',
            'title' => 'required',
            'youtubeid' => 'required',
            'url_mp4' => 'required',
            'timecode' => 'nullable|date_format:d/m/Y H:i:s',
            'duration' => 'numeric|min:0|required',
            'creator_id' => 'required|exists:users,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $param = array_merge(
            $validator->validated(),
            ['ngay' => DateTime::createFromFormat('d/m/Y', $request->ngay)->format('Y-m-d'),
            'timecode' => $request->timecode != '' ? DateTime::createFromFormat('d/m/Y H:i:s', $request->timecode)->format('Y-m-d H:i:s') : null]
        );

        $param['type'] = 1;
        $param['creator_id'] = $request->creator_id;

        $object = LichPhatSong::create($param);
        
        $duration = 0;
        $milisecon = 0;
        $times = explode(":", $object->time);
        if (count($times)==2) {
            # code...
            $milisecon = $times[0]*60*60 + $times[1]*60;
        }
        $timecode = str_replace("-", "", $object->timecode);
        $timecode = str_replace(":", "", $timecode);
        $timecode = str_replace(" ", "", $timecode);
        $duration = $object->duration * 1000;
        $result_data = ['id' => $object->id, 'time' => $object->time, 'title'=>$object->title, 
            'playliststart' => $timecode, 'duration' => $duration,'milisecon' => $milisecon, 
            'timecode'=>$object->timecode, 'youtubeid'=>$object->youtubeid, 'url_mp4'=>$object->url_mp4];
            

        return response()->json([
            'message' => 'Create Live Radio successful',
            'data' => $result_data
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/live/radio/update",
     *     operationId="updateLiveRadio",
     *     tags={"Others"},
     *     summary="Update Live Radio (api mới, cần check lại nghiệp vụ)",
     *     description="Update Live Radio",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id","ngay","time","title","youtubeid","url_mp4","duration", "updater_id"},
     *             @OA\Property(property="id", type="string", format="text"),
     *             @OA\Property(property="ngay", type="string", format="text", example="17/01/2025"),
     *             @OA\Property(property="time", type="string", format="text"),
     *             @OA\Property(property="title", type="string", format="text"),
     *             @OA\Property(property="youtubeid", type="string", format="text"),
     *             @OA\Property(property="url_mp4", type="string", format="text"),
     *             @OA\Property(property="timecode", type="string", format="text", example="17/01/2025 15:00:24"),
     *             @OA\Property(property="duration", type="string", format="text"),
     *             @OA\Property(property="updater_id", type="string", format="text")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful updated live radio",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),   
     *                      @OA\Property(property="time", type="string"),      
     *                      @OA\Property(property="title", type="string"),  
     *                      @OA\Property(property="playliststart", type="string"),  
     *                      @OA\Property(property="duration", type="string"),  
     *                      @OA\Property(property="milisecon", type="string"),  
     *                      @OA\Property(property="timecode", type="string"),  
     *                      @OA\Property(property="youtubeid", type="string"),  
     *                      @OA\Property(property="url_mp4", type="string")
     *              ))
     *         )
     *     )
     * )
     */
    public function updateliveradio (Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:lichphatsongs,id',
            'ngay' => 'required|date_format:d/m/Y',
            'time' => 'required',
            'title' => 'required',
            'youtubeid' => 'required',
            'url_mp4' => 'required',
            'timecode' => 'nullable|date_format:d/m/Y H:i:s',
            'duration' => 'numeric|min:0|required',
            'updater_id' => 'required|exists:users,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $object = LichPhatSong::find($request->id);
        $object->ngay = DateTime::createFromFormat('d/m/Y', $request->ngay)->format('Y-m-d');
        $object->time = $request->time;
        $object->title = $request->title;
        $object->youtubeid = $request->youtubeid;
        $object->url_mp4 = $request->url_mp4;
        $object->timecode = $request->timecode != '' ? DateTime::createFromFormat('d/m/Y H:i:s', $request->timecode)->format('Y-m-d H:i:s') : null;
        $object->duration = $request->duration;
        $object->updater_id = $request->updater_id;

        $object->update();
        
        $duration = 0;
        $milisecon = 0;
        $times = explode(":", $object->time);
        if (count($times)==2) {
            # code...
            $milisecon = $times[0]*60*60 + $times[1]*60;
        }
        $timecode = str_replace("-", "", $object->timecode);
        $timecode = str_replace(":", "", $timecode);
        $timecode = str_replace(" ", "", $timecode);
        $duration = $object->duration * 1000;
        $result_data = ['id' => $object->id, 'time' => $object->time, 'title'=>$object->title, 
            'playliststart' => $timecode, 'duration' => $duration,'milisecon' => $milisecon, 
            'timecode'=>$object->timecode, 'youtubeid'=>$object->youtubeid, 'url_mp4'=>$object->url_mp4,
            'creator_id' => $object->creator_id, 'updater_id' => $object->updater_id];            

        return response()->json([
            'message' => 'Update Live TV successful',
            'data' => $result_data
        ], 201);
    }

    /**
     * @OA\DELETE(
     *     path="/api/live/radio/delete",
     *     operationId="deleteLiveRadio",
     *     tags={"Others"},
     *     summary="Delete Live Radio (api mới, cần check lại nghiệp vụ)",
     *     description="Delete Live Radio",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function deleteliveradio(Request $request) {        
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:lichphatsongs,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
    	$object = LichPhatSong::find($request->id);
        $object_name = $object->title;
        $object->delete();
        return [
            'code' => "Xóa thành công " . $object_name,
            'error' => ""
        ];
    }

    /**
     * @OA\GET(
     *     path="/api/categories/list",
     *     operationId="CategoriesList",
     *     tags={"Categories"},
     *     summary="Categories List",
     *     description="Categories List",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="parentid",
     *         in="query",
     *         description="Parent category id",
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),
     *                      @OA\Property(property="parent_id", type="string"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="order", type="string")
     *                  ))
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function categories(Request $request) {
        $parentid = 0;
        if ($request->parentid != '') {
            $parentid = $request->parentid;
        }
    	$cates = Category::where([['parentid','=',$parentid],['status','=', 1]])->orderby('order')->get();
        $rs = array();
        foreach ($cates as $cate) {
            $c = ['id'=>$cate->id,'parent_id'=>$cate->parentid,'title'=>$cate->title, 'order'=>$cate->order];
            array_push($rs, $c);
        }
        return [
            'code' => "200",
            'error'=>"",
            'data'=>[
                'list'=>$rs
            ]
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/categories/create",
     *     operationId="createCategory",
     *     tags={"Categories"},
     *     summary="Create Categories (api mới, cần check lại nghiệp vụ)",
     *     description="Create Categories",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","parentid","creator_id"},
     *             @OA\Property(property="title", type="string", format="string"),
     *             @OA\Property(property="parentid", type="string", format="string"),
     *             @OA\Property(property="order", type="string", format="string"),
     *             @OA\Property(property="status", type="string", format="string"),
     *             @OA\Property(property="creator_id", type="string", format="string")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful create category",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),
     *                      @OA\Property(property="parent_id", type="string"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="order", type="string"),     * 
     *                      @OA\Property(property="status", type="string"),     * 
     *                      @OA\Property(property="creator_id", type="string")
     *                  ))
     *              ))
     *         )
     *     )
     * )
     */
    public function createcategories (Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'parentid' => 'required',
            'creator_id' => 'required|exists:users,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        
        $param =  Array(
            'title' => $request->title,
            'parentid' => $request->parentid,
            'order' => $request->order != '' ? $request->order : 100,
            'status' => $request->status != '' ? $request->status : 1,
            'creator_id' => $request->creator_id
        );

        $object = Category::create($param);            

        return response()->json([
            'message' => 'Create category successful',
            'data' => ['id'=>$object->id,'parent_id'=>$object->parentid,'title'=>$object->title, 
                'order'=>$object->order, 'status' => $object->status, 'creator_id' => $object->creator_id]
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/categories/update",
     *     operationId="updateCategory",
     *     tags={"Categories"},
     *     summary="Update Categories (api mới, cần check lại nghiệp vụ)",
     *     description="Update Categories",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id","title","parentid","order","status", "creator_id"},
     *             @OA\Property(property="id", type="string", format="string"),
     *             @OA\Property(property="title", type="string", format="string"),
     *             @OA\Property(property="parentid", type="string", format="string"),
     *             @OA\Property(property="order", type="string", format="string"),
     *             @OA\Property(property="status", type="string", format="string"),
     *             @OA\Property(property="updater_id", type="string", format="string")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful update category",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),
     *                      @OA\Property(property="parent_id", type="string"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="order", type="string"),
     *                      @OA\Property(property="status", type="string"),
     *                      @OA\Property(property="creator_id", type="string"),
     *                      @OA\Property(property="updater_id", type="string")
     *                  ))
     *              ))
     *         )
     *     )
     * )
     */
    public function updatecategories (Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:categories,id',
            'title' => 'required|max:255',
            'parentid' => 'required',
            'order' => 'nullable|numeric|min:0',
            'status' => 'nullable|numeric|min:0|max:1',
            'updater_id' => 'required|exists:users,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $object = Category::find($request->id);
        $object->title  = $request->title;  
        $object->parentid  = $request->parentid;
        if ($request->order != '') {
            $object->order  = $request->order;
        }
        if ($request->status != '') {
            $object->status  = $request->status;
        }
        $object->updater_id = $request->updater_id;

        $object->update();

        return response()->json([
            'message' => 'Update category successful',
            'data' => ['id'=>$object->id,'parent_id'=>$object->parentid,'title'=>$object->title, 
                'order'=>$object->order, 'status' => $object->status, 'creator_id' => $object->creator_id, 'updater_id' => $object->updater_id]
        ], 201);
    }

    /**
     * @OA\DELETE(
     *     path="/api/categories/delete",
     *     operationId="deleteCategories",
     *     tags={"Categories"},
     *     summary="Delete Category (api mới, cần check lại nghiệp vụ)",
     *     description="Delete Category",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function deletecategories (Request $request) {        
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:categories,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        if (Video::where('category_id', $request->id)->count() > 0) {
            return [
                'code' => "Cannot delete [Video]",
                'error' => ""
            ];
        }
        if (Playlist::where('category_id', $request->id)->count() > 0) {
            return [
                'code' => "Cannot delete [Playlist]",
                'error' => ""
            ];
        }
        if (Plus::where('category_id', $request->id)->count() > 0) {
            return [
                'code' => "Cannot delete [Plus]",
                'error' => ""
            ];
        }
    	$object = Category::find($request->id);
        $object_name = $object->title;
        $object->delete();
        return [
            'code' => "Xóa thành công " . $object_name,
            'error' => ""
        ];
    }

    /**
     * @OA\GET(
     *     path="/api/playlists/list",
     *     operationId="PlaylistsList",
     *     tags={"Playlists"},
     *     summary="Playlists List",
     *     description="Playlists List",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Category id",
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string"),
     *                      @OA\Property(property="playlistid", type="string"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="category_id", type="string"),
     *                      @OA\Property(property="publishedat", type="string"),
     *                      @OA\Property(property="thumbnail", type="string"),
     *                      @OA\Property(property="nextpagetoken", type="string")
     *                  ))
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function playlists(Request $request) {
        if ($request->category_id == '') {
            $data = Playlist::orderby('created_at', 'desc')->orderby('title', 'asc')->get();
        } else {
            $data = Playlist::where('category_id', $request->category_id)->orderby('created_at', 'desc')->orderby('title', 'asc')->get();
        }

        $rs = array();
        foreach ($data as $item) {
            $c = ['id' => $item->id, 'playlistid' => $item->playlistid, 'title'=>$item->title, 'category_id'=>$item->category_id,
                'publishedat'=>$item->publishedat, 'thumbnail'=>$item->thumbnail, 'nextpagetoken'=>$item->nextpagetoken];
            array_push($rs, $c);
        }
        return [
            'code' => "200",
            'error'=>"",
            'data'=>[
                'list'=>$rs
            ]
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/playlists/create",
     *     operationId="createPlaylist",
     *     tags={"Playlists"},
     *     summary="Create Playlist (api mới, cần check lại nghiệp vụ)",
     *     description="Create Playlist",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"playlistid","title", "category_id", "publishedat", "thumbnail", "nextpagetoken"},
     *             @OA\Property(property="playlistid", type="string", format="string"),
     *             @OA\Property(property="title", type="string", format="string"),
     *             @OA\Property(property="category_id", type="string", format="string"),
     *             @OA\Property(property="publishedat", type="string", format="string"),
     *             @OA\Property(property="thumbnail", type="string", format="string"),
     *             @OA\Property(property="nextpagetoken", type="string", format="string")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful create category",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string", format="string"),
     *                      @OA\Property(property="playlistid", type="string", format="string"),
     *                      @OA\Property(property="title", type="string", format="string"),
     *                      @OA\Property(property="category_id", type="string", format="string"),
     *                      @OA\Property(property="publishedat", type="string", format="string"),
     *                      @OA\Property(property="thumbnail", type="string", format="string"),
     *                      @OA\Property(property="nextpagetoken", type="string", format="string")
     *                  ))
     *              ))
     *         )
     *     )
     * )
     */
    public function createplaylists (Request $request){
        $validator = Validator::make($request->all(), [
            'playlistid' => 'required|max:255',
            'title' => 'required|max:255',
            'category_id' => 'required|exists:categories,id',
            'publishedat' => 'required|date_format:d/m/Y H:i:s',
            'thumbnail' => 'required|max:255',
            'nextpagetoken' => 'required|max:255'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        
        $param =  Array(
            'playlistid' => $request->playlistid,
            'title' => $request->title,
            'category_id' => $request->category_id,
            'publishedat' => DateTime::createFromFormat('d/m/Y H:i:s', $request->publishedat)->format('Y-m-d H:i:s'),
            'thumbnail' => $request->thumbnail,
            'nextpagetoken' => $request->nextpagetoken,
        );

        $object = Playlist::create($param);            

        return response()->json([
            'message' => 'Create playlist successful',
            'data' => ['id'=>$object->id,'playlistid'=>$object->playlistid,'title'=>$object->title, 
                'category_id'=>$object->category_id, 'publishedat' => $object->publishedat,
                'thumbnail'=>$object->thumbnail, 'nextpagetoken' => $object->nextpagetoken]
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/playlists/update",
     *     operationId="updatePlaylist",
     *     tags={"Playlists"},
     *     summary="Update Playlists (api mới, cần check lại nghiệp vụ)",
     *     description="Update Playlists",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id","playlistid","title","category_id","publishedat","thumbnail","nextpagetoken"},
     *             @OA\Property(property="id", type="string", format="string"),
     *             @OA\Property(property="playlistid", type="string", format="string"),
     *             @OA\Property(property="title", type="string", format="string"),
     *             @OA\Property(property="category_id", type="string", format="string"),
     *             @OA\Property(property="publishedat", type="string", format="string"),
     *             @OA\Property(property="thumbnail", type="string", format="string"),
     *             @OA\Property(property="nextpagetoken", type="string", format="string")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful update category",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string", format="string"),
     *                      @OA\Property(property="playlistid", type="string", format="string"),
     *                      @OA\Property(property="title", type="string", format="string"),
     *                      @OA\Property(property="category_id", type="string", format="string"),
     *                      @OA\Property(property="publishedat", type="string", format="string"),
     *                      @OA\Property(property="thumbnail", type="string", format="string"),
     *                      @OA\Property(property="nextpagetoken", type="string", format="string")
     *                  ))
     *              ))
     *         )
     *     )
     * )
     */
    public function updateplaylists (Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:playlists,id',
            'playlistid' => 'required|max:255',
            'title' => 'required|max:255',
            'category_id' => 'required|exists:categories,id',
            'publishedat' => 'required|date_format:d/m/Y H:i:s',
            'thumbnail' => 'required|max:255',
            'nextpagetoken' => 'required|max:255'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $object = Playlist::find($request->id);
        $object->playlistid  = $request->playlistid; 
        $object->title  = $request->title;  
        $object->category_id  = $request->category_id;  
        $object->publishedat  = DateTime::createFromFormat('d/m/Y H:i:s', $request->publishedat)->format('Y-m-d H:i:s');  
        $object->thumbnail  = $request->thumbnail;    
        $object->nextpagetoken  = $request->nextpagetoken;

        $object->update();

        return response()->json([
            'message' => 'Update playlist successful',
            'data' => ['id'=>$object->id,'playlistid'=>$object->playlistid,'title'=>$object->title, 
                'category_id'=>$object->category_id, 'publishedat' => $object->publishedat,
                'thumbnail'=>$object->thumbnail, 'nextpagetoken' => $object->nextpagetoken]
        ], 201);
    }

    /**
     * @OA\DELETE(
     *     path="/api/playlists/delete",
     *     operationId="deletePlaylists",
     *     tags={"Playlists"},
     *     summary="Delete Playlist (api mới, cần check lại nghiệp vụ)",
     *     description="Delete Playlist",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function deleteplaylists (Request $request) {        
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:playlists,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        if (Video::where('playlist_id', $request->id)->count() > 0) {
            return [
                'code' => "Cannot delete [Video]",
                'error' => ""
            ];
        }
    	$object = Playlist::find($request->id);
        $object_name = $object->title;
        $object->delete();
        return [
            'code' => "Xóa thành công " . $object_name,
            'error' => ""
        ];
    }

    /**
     * @OA\GET(
     *     path="/api/videos/list",
     *     operationId="VideoList",
     *     tags={"Videos"},
     *     summary="Video List",
     *     description="Video List",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Category id",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="video_id",
     *         in="query",
     *         description="Video id",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="keyword",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit row selected",
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="user_id", type="string"),
     *                      @OA\Property(property="aprove_userid", type="string"),
     *                      @OA\Property(property="category_id", type="string"),
     *                      @OA\Property(property="playlist_id", type="string"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="description", type="string"),
     *                      @OA\Property(property="shortdescription", type="string"),
     *                      @OA\Property(property="thumbnails", type="string"),
     *                      @OA\Property(property="youtubeid", type="string"),
     *                      @OA\Property(property="mp4_link", type="string"),
     *                      @OA\Property(property="viewcount", type="string"),
     *                      @OA\Property(property="likeCount", type="string"),
     *                      @OA\Property(property="shareCount", type="string"),
     *                      @OA\Property(property="commentCount", type="string"),
     *                      @OA\Property(property="publishedAt", type="string"),
     *                      @OA\Property(property="order", type="string"),
     *                      @OA\Property(property="hot", type="string"),
     *                      @OA\Property(property="plus", type="string"),
     *                      @OA\Property(property="state", type="string"),
     *                      @OA\Property(property="status", type="string")
     *                  ))
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function videolists(Request $request) {
        $validator = Validator::make($request->all(), [
            'video_id' => 'nullable|exists:videos,id',
            'category_id' => 'nullable|exists:categories,id',
            'limit' => 'nullable|numeric|min:0'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $object = Video::where('status', 1);
        if ($request->category_id != '') {
            $object = $object->where('category_id', $request->category_id);
        } else {
            $object = $object->where('hot', '1');
        }

        if ($request->video_id != '') {
            $object = $object->where('id', $request->video_id);
        }

        if ($request->keyword != '') {
            $object = $object->where('title', 'like', '%'. $request->keyword . '%');
        }

        if ($request->limit != '') {
            $object = $object->limit($request->limit);
        } else {
            $object = $object->limit(5);
        }

        $object = $object->orderby('publishedAt','desc')->get();
        $rs = array();
        foreach ($object as $video) {
            $category_title='';
            if($video->category != null){
                $category_title = $video->category->title;
            }
            $c = ['id' => $video->id, 'category_id' => $video->category_id, 'category_title' => $category_title,
                'title' => $video->title, 'description' => $video->description, 'thumbnails' => $video->thumbnails, 
                'youtubeid'=>$video->youtubeid, 'mp4_link' => $video->mp4_link, 'viewcount' => $video->viewcount, 
                'likecount' => $video->likeCount, 'sharecount' => $video->shareCount, 'commentcount' => $video->commentCount, 
                'publishedat' => $video->publishedAt];
            array_push($rs, $c);
        }
        return [
            'code' => "200",
            'error'=>"",
            'data'=>[
                'list'=>$rs
            ]
        ];
    }

    /**
     * @OA\GET(
     *     path="/api/videos/relatelist",
     *     operationId="VideoRealteList",
     *     tags={"Videos"},
     *     summary="Video Relate List",
     *     description="Video Relate List",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="video_id",
     *         in="query",
     *         description="Video id",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit row selected",
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="user_id", type="string"),
     *                      @OA\Property(property="aprove_userid", type="string"),
     *                      @OA\Property(property="category_id", type="string"),
     *                      @OA\Property(property="playlist_id", type="string"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="description", type="string"),
     *                      @OA\Property(property="shortdescription", type="string"),
     *                      @OA\Property(property="thumbnails", type="string"),
     *                      @OA\Property(property="youtubeid", type="string"),
     *                      @OA\Property(property="mp4_link", type="string"),
     *                      @OA\Property(property="viewcount", type="string"),
     *                      @OA\Property(property="likeCount", type="string"),
     *                      @OA\Property(property="shareCount", type="string"),
     *                      @OA\Property(property="commentCount", type="string"),
     *                      @OA\Property(property="publishedAt", type="string"),
     *                      @OA\Property(property="order", type="string"),
     *                      @OA\Property(property="hot", type="string"),
     *                      @OA\Property(property="plus", type="string"),
     *                      @OA\Property(property="state", type="string"),
     *                      @OA\Property(property="status", type="string")
     *                  ))
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function videorelatelists(Request $request) {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|exists:videos,id',
            'limit' => 'nullable|numeric|min:0'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $video_object = Video::find($request->video_id);
        $category_id = $video_object->category_id;
        
        $object = Video::where('category_id', $category_id)->where('status', 1)->inRandomOrder();

        if ($request->limit != '') {
            $object = $object->limit($request->limit);
        } else {
            $object = $object->limit(5);
        }

        $object = $object->orderby('publishedAt','desc')->get();
        $rs = array();
        foreach ($object as $video) {
            $category_title='';
            if($video->category != null){
                $category_title = $video->category->title;
            }
            $c = ['id' => $video->id, 'category_id' => $video->category_id, 'category_title' => $category_title,
                'title' => $video->title, 'description' => $video->description, 'thumbnails' => $video->thumbnails, 
                'youtubeid'=>$video->youtubeid, 'mp4_link' => $video->mp4_link, 'viewcount' => $video->viewcount, 
                'likecount' => $video->likeCount, 'sharecount' => $video->shareCount, 'commentcount' => $video->commentCount, 
                'publishedat' => $video->publishedAt];
            array_push($rs, $c);
        }
        return [
            'code' => "200",
            'error'=>"",
            'data'=>[
                'list'=>$rs
            ]
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/videos/create",
     *     operationId="createVideo",
     *     tags={"Videos"},
     *     summary="Create Video (api mới, cần check lại nghiệp vụ)",
     *     description="Create Video",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","aprove_userid", "category_id", "playlist_id", "title", "description",
     *              "shortdescription", "thumbnails", "youtubeid", "mp4_link", "publishedAt", "state"},
     *             @OA\Property(property="user_id", type="string", format="string"),
     *             @OA\Property(property="aprove_userid", type="string", format="string"),
     *             @OA\Property(property="category_id", type="string", format="string"),
     *             @OA\Property(property="playlist_id", type="string", format="string"),
     *             @OA\Property(property="title", type="string", format="string"),
     *             @OA\Property(property="description", type="string", format="string"),
     *             @OA\Property(property="shortdescription", type="string", format="string"),
     *             @OA\Property(property="thumbnails", type="string", format="string"),
     *             @OA\Property(property="youtubeid", type="string", format="string"),
     *             @OA\Property(property="mp4_link", type="string", format="string"),
     *             @OA\Property(property="viewcount", type="string", format="string"),
     *             @OA\Property(property="likeCount", type="string", format="string"),
     *             @OA\Property(property="shareCount", type="string", format="string"),
     *             @OA\Property(property="commentCount", type="string", format="string"),
     *             @OA\Property(property="publishedAt", type="string", format="string"),
     *             @OA\Property(property="order", type="string", format="string"),
     *             @OA\Property(property="hot", type="string", format="string"),
     *             @OA\Property(property="plus", type="string", format="string"),
     *             @OA\Property(property="state", type="string", format="string"),
     *             @OA\Property(property="status", type="string", format="string")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful create category",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string", format="string"),
     *                      @OA\Property(property="user_id", type="string", format="string"),
     *                      @OA\Property(property="aprove_userid", type="string", format="string"),
     *                      @OA\Property(property="category_id", type="string", format="string"),
     *                      @OA\Property(property="playlist_id", type="string", format="string"),
     *                      @OA\Property(property="title", type="string", format="string"),
     *                      @OA\Property(property="description", type="string", format="string"),
     *                      @OA\Property(property="shortdescription", type="string", format="string"),
     *                      @OA\Property(property="thumbnails", type="string", format="string"),
     *                      @OA\Property(property="youtubeid", type="string", format="string"),
     *                      @OA\Property(property="mp4_link", type="string", format="string"),
     *                      @OA\Property(property="viewcount", type="string", format="string"),
     *                      @OA\Property(property="likeCount", type="string", format="string"),
     *                      @OA\Property(property="shareCount", type="string", format="string"),
     *                      @OA\Property(property="commentCount", type="string", format="string"),
     *                      @OA\Property(property="publishedAt", type="string", format="string"),
     *                      @OA\Property(property="order", type="string", format="string"),
     *                      @OA\Property(property="hot", type="string", format="string"),
     *                      @OA\Property(property="plus", type="string", format="string"),
     *                      @OA\Property(property="state", type="string", format="string"),
     *                      @OA\Property(property="status", type="string", format="string")
     *                  ))
     *              ))
     *         )
     *     )
     * )
     */
    public function createvideos (Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'aprove_userid' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'playlist_id' => 'required|exists:playlists,id',
            'title' => 'required|max:255',
            'description' => 'required',
            'shortdescription' => 'required|max:255',
            'thumbnails' => 'required|max:255',
            'youtubeid' => 'required|max:255',
            'mp4_link' => 'required|max:255',
            'viewcount' => 'nullable|numeric|min:0',
            'likeCount' => 'nullable|numeric|min:0',
            'shareCount' => 'nullable|numeric|min:0',
            'commentCount' => 'nullable|numeric|min:0',
            'publishedAt' => 'required|date_format:d/m/Y H:i:s',
            'order' => 'nullable|numeric|min:0',
            'hot' => 'nullable|numeric|min:0',
            'plus' => 'nullable|numeric|min:0',
            'state' => 'required|numeric',
            'status' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        
        $param =  Array(
            'user_id' => $request->user_id,
            'aprove_userid' => $request->aprove_userid,
            'category_id' => $request->category_id,
            'playlist_id' => $request->playlist_id,
            'title' => $request->title,
            'description' => $request->description,
            'shortdescription' => $request->shortdescription,
            'thumbnails' => $request->thumbnails,
            'youtubeid' => $request->youtubeid,
            'mp4_link' => $request->mp4_link,
            'viewcount' => $request->viewcount != '' ? $request->viewcount : 0,
            'likeCount' => $request->likeCount != '' ? $request->likeCount : 0,
            'shareCount' => $request->shareCount != '' ? $request->shareCount : 0,
            'commentCount' => $request->commentCount != '' ? $request->commentCount : 0,
            'publishedAt' => DateTime::createFromFormat('d/m/Y H:i:s', $request->publishedAt)->format('Y-m-d H:i:s'),
            'order' => $request->order != '' ? $request->order : 100,
            'hot' => $request->hot != '' ? $request->hot : 0,
            'plus' => $request->plus != '' ? $request->plus : 0,
            'state' => $request->state,
            'status' => $request->status
        );

        $object = Video::create($param);            

        $category_title='';
        if($object->category != null){
            $category_title = $object->category->title;
        }
        return response()->json([
            'message' => 'Create category successful',
            'data' => ['id' => $object->id, 'category_id' => $object->category_id, 'category_title' => $category_title,
                'title' => $object->title, 'description' => $object->description, 'thumbnails' => $object->thumbnails, 
                'youtubeid'=>$object->youtubeid, 'mp4_link' => $object->mp4_link, 'viewcount' => $object->viewcount, 
                'likecount' => $object->likeCount, 'sharecount' => $object->shareCount, 'commentcount' => $object->commentCount, 
                'publishedat' => $object->publishedAt]
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/videos/update",
     *     operationId="updateVideo",
     *     tags={"Videos"},
     *     summary="Update Video (api mới, cần check lại nghiệp vụ)",
     *     description="Update Video",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "user_id","aprove_userid", "category_id", "playlist_id", "title", "description",
     *              "shortdescription", "thumbnails", "youtubeid", "mp4_link", "publishedAt", "state"},
     *             @OA\Property(property="id", type="string", format="string"),
     *             @OA\Property(property="user_id", type="string", format="string"),
     *             @OA\Property(property="aprove_userid", type="string", format="string"),
     *             @OA\Property(property="category_id", type="string", format="string"),
     *             @OA\Property(property="playlist_id", type="string", format="string"),
     *             @OA\Property(property="title", type="string", format="string"),
     *             @OA\Property(property="description", type="string", format="string"),
     *             @OA\Property(property="shortdescription", type="string", format="string"),
     *             @OA\Property(property="thumbnails", type="string", format="string"),
     *             @OA\Property(property="youtubeid", type="string", format="string"),
     *             @OA\Property(property="mp4_link", type="string", format="string"),
     *             @OA\Property(property="viewcount", type="string", format="string"),
     *             @OA\Property(property="likeCount", type="string", format="string"),
     *             @OA\Property(property="shareCount", type="string", format="string"),
     *             @OA\Property(property="commentCount", type="string", format="string"),
     *             @OA\Property(property="publishedAt", type="string", format="string"),
     *             @OA\Property(property="order", type="string", format="string"),
     *             @OA\Property(property="hot", type="string", format="string"),
     *             @OA\Property(property="plus", type="string", format="string"),
     *             @OA\Property(property="state", type="string", format="string"),
     *             @OA\Property(property="status", type="string", format="string")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful create category",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="list", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="string", format="string"),
     *                      @OA\Property(property="user_id", type="string", format="string"),
     *                      @OA\Property(property="aprove_userid", type="string", format="string"),
     *                      @OA\Property(property="category_id", type="string", format="string"),
     *                      @OA\Property(property="playlist_id", type="string", format="string"),
     *                      @OA\Property(property="title", type="string", format="string"),
     *                      @OA\Property(property="description", type="string", format="string"),
     *                      @OA\Property(property="shortdescription", type="string", format="string"),
     *                      @OA\Property(property="thumbnails", type="string", format="string"),
     *                      @OA\Property(property="youtubeid", type="string", format="string"),
     *                      @OA\Property(property="mp4_link", type="string", format="string"),
     *                      @OA\Property(property="viewcount", type="string", format="string"),
     *                      @OA\Property(property="likeCount", type="string", format="string"),
     *                      @OA\Property(property="shareCount", type="string", format="string"),
     *                      @OA\Property(property="commentCount", type="string", format="string"),
     *                      @OA\Property(property="publishedAt", type="string", format="string"),
     *                      @OA\Property(property="order", type="string", format="string"),
     *                      @OA\Property(property="hot", type="string", format="string"),
     *                      @OA\Property(property="plus", type="string", format="string"),
     *                      @OA\Property(property="state", type="string", format="string"),
     *                      @OA\Property(property="status", type="string", format="string")
     *                  ))
     *              ))
     *         )
     *     )
     * )
     */
    public function updatevideos (Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:videos,id',
            'user_id' => 'required|exists:users,id',
            'aprove_userid' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'playlist_id' => 'required|exists:playlists,id',
            'title' => 'required|max:255',
            'description' => 'required',
            'shortdescription' => 'required|max:255',
            'thumbnails' => 'required|max:255',
            'youtubeid' => 'required|max:255',
            'mp4_link' => 'required|max:255',
            'viewcount' => 'nullable|numeric|min:0',
            'likeCount' => 'nullable|numeric|min:0',
            'shareCount' => 'nullable|numeric|min:0',
            'commentCount' => 'nullable|numeric|min:0',
            'publishedAt' => 'required|date_format:d/m/Y H:i:s',
            'order' => 'nullable|numeric|min:0',
            'hot' => 'nullable|numeric|min:0',
            'plus' => 'nullable|numeric|min:0',
            'state' => 'required|numeric',
            'status' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        $object = Video::find($request->id);
        $object->user_id = $request->user_id;
        $object->aprove_userid = $request->aprove_userid;
        $object->category_id = $request->category_id;
        $object->playlist_id = $request->playlist_id;
        $object->title = $request->title;
        $object->description = $request->description;
        $object->shortdescription = $request->shortdescription;
        $object->thumbnails = $request->thumbnails;
        $object->youtubeid = $request->youtubeid;
        $object->mp4_link = $request->mp4_link;
        $object->viewcount = $request->viewcount != '' ? $request->viewcount : $object->viewcount;
        $object->likeCount = $request->likeCount != '' ? $request->likeCount : $object->likeCount;
        $object->shareCount = $request->shareCount != '' ? $request->shareCount : $object->shareCount;
        $object->commentCount = $request->commentCount != '' ? $request->commentCount : $object->commentCount;
        $object->publishedAt = DateTime::createFromFormat('d/m/Y H:i:s', $request->publishedAt)->format('Y-m-d H:i:s');
        $object->order = $request->order != '' ? $request->order : $object->order;
        $object->hot = $request->hot != '' ? $request->hot : $object->hot;
        $object->plus = $request->plus != '' ? $request->plus : $object->plus;
        $object->state = $request->state;
        $object->status = $request->status;

        $object->update();            
        $category_title='';
        if($object->category != null){
            $category_title = $object->category->title;
        }
        return response()->json([
            'message' => 'Update category successful',
            'data' => ['id' => $object->id, 'category_id' => $object->category_id, 'category_title' => $category_title,
                'title' => $object->title, 'description' => $object->description, 'thumbnails' => $object->thumbnails, 
                'youtubeid'=>$object->youtubeid, 'mp4_link' => $object->mp4_link, 'viewcount' => $object->viewcount, 
                'likecount' => $object->likeCount, 'sharecount' => $object->shareCount, 'commentcount' => $object->commentCount, 
                'publishedat' => $object->publishedAt]
        ], 201);
    }

    /**
     * @OA\DELETE(
     *     path="/api/videos/delete",
     *     operationId="deleteVideo",
     *     tags={"Videos"},
     *     summary="Delete Video (api mới, cần check lại nghiệp vụ)",
     *     description="Delete Video",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function deletevideos (Request $request) {        
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:videos,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
    	$object = Video::find($request->id);
        $object_name = $object->title;
        $object->delete();
        return [
            'code' => "Xóa thành công " . $object_name,
            'error' => ""
        ];
    }
}
