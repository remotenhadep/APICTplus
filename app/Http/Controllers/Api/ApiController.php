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
use Auth;
use Validator;

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
    	$ngay = date("Y-m-d", strtotime($req->date));
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

        $user = User::create($user_param);

        return response()->json([
            'message' => 'User successfully created',
            'user' => $user->only(['username', 'name', 'phone', 'email'])
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
     *             @OA\Property(property="name", type="string", format="text"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="id", type="string", format="text"),
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
            'username' => 'required|string|between:2,100',
            'email' => 'required|string|max:100|email|unique:users,email,' . $request->id,
            'phone' => 'nullable|string|max:100|unique:users,phone,' . $request->id,
            'password' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::find($request->id);
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = bcrypt($request->password);
        $user->update();

        return response()->json([
            'message' => 'User successfully updated',
            'user' => $user->only(['username', 'name', 'phone', 'email'])
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
}
