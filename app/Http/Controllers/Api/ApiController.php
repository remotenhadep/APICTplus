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

class ApiController extends Controller
{    
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
     *             required={"phone","password","name"},
     *             @OA\Property(property="phone", type="string", format="text"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="name", type="string", format="text"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful register with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="account", type="array", 
     *                  @OA\Items(
     *                      @OA\Property(property="name", type="string"),      
     *                      @OA\Property(property="token", type="string")
    *                    ))
    *              ))
     *         )
     *     )
     * )
     */
    public function register(Request $req){
        $phone = $req->input('phone');
        $pass = $req->input('password');
        $name = $req->input('name');
        if ($name == null) {
            $name = "";
        }
        if ($phone == null || $pass == null) {
            return [
                'code' => "401",
                'error'=>"Null parameter",
                'data'=>[
                    'list'=>[]
                ]
            ];
        }
        $accexits = Account::where('email',$phone)->first();
        if ($accexits != null) {
            # code...
            return [
                'code' => "402",
                'error'=>"Account is exits",
                'data'=>[
                    'list'=>[]
                ]
            ];
        }
        $token = Crypt::encryptString($phone.'-'.$pass);
        $acc = new Account();
        $acc->phone = '';
        $acc->password = Crypt::encryptString($pass);
        $acc->email=$phone;
        $acc->name=$name;
        $acc->token=$token;
        $acc->google='';
        $acc->facebook='';
        $acc->status=1;
        $acc->save();
        return [
            'code' => "200",
            'error'=>"",
            'data'=>[
                'account'=>['name'=>$name,'token'=>$token]
            ]
        ];
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
     *             required={"email","password"},
     *             @OA\Property(property="phone", type="string", format="text"),
     *             @OA\Property(property="password", type="string", format="password")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful register with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="account", type="array", 
     *                  @OA\Items(
     *                      @OA\Property(property="name", type="string"),      
     *                      @OA\Property(property="token", type="string")
     *                    ))
     *              ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function login(Request $req){

        $phone = $req->input('phone');
        $pass = $req->input('password');
        if ($phone == null || $pass == null) {
            return [
                'code' => "401",
                'error'=>"Account not exists",
                'data'=>[
                    'list'=>[]
                ]
            ];
        }

        $acc = Account::where('email', $phone)->first();
        if ($acc == null) {
            # code...
            return [
                'code' => "401",
                'error'=>"Account not exists",
                'data'=>[
                    'list'=>[]
                ]
            ];
        }
        if ($acc->status == 0) {
            # code...
            return [
                'code' => "401",
                'error'=>"Account not exists",
                'data'=>[
                    'list'=>[]
                ]
            ];
        }
        $password = Crypt::decryptString($acc->password);
        if ($password == $pass) {
            # code...
            return [
            'code' => "200",
            'error'=>"",
            'data'=>[
                'account'=>['name'=>$acc->name,'token'=>$acc->token]
            ]
        ];
        }else{
            return [
                'code' => "401",
                'error'=>"Tài khoản hoặc mật khẩu không đúng",
                'data'=>[
                    'list'=>[]
                ]
            ];
        }
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
}
