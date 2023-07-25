<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use DB;
use Exception;
use Log;
use Session;
use Str;

use App\Models\User;
use App\Models\Admin\Bus;


class LoginController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function Login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);


        $version = $request->version;


        $credentials = ['email' => $request->username, 'password' => $request->password];
        //'status' => 1, 'trash' => 'NO'
        $remember = $request->has('remember') ? true : false;


        $message = [];

        if (Auth::attempt($credentials, $remember)) {

            $user_id = Auth::id();
            $user_get = Auth::user();
            $token = $user_get->api_token;

            if ($token  == '' || $token  == null) {

                $token_text = time() . "_" . $user_id;
                $token = encryptId($token_text);

                User::where('id', $user_id)->update(['api_token' => $token]);
            }

            $busDetails =  Bus::where('assigned_driver', $user_id)->first();

            if ($busDetails == '' ||  $busDetails == null) {
                $busDetails = [];
            } else {

                $busDetails =  $busDetails->toArray();
            }

            $message = array(
                'token' => $token,
                'id' => $user_get->id,
                'busDetails' => $busDetails,
                'user_name' => $user_get->name,
                'email' => $user_get->email,
                'message' => 'login success',

            );


            if ($version) {
                $updateVersion = User::where('id', $user->id)->update(['version' => $version]);
            }
        } else {
            $message['message'] = 'Login Failed ';
        }


        return response($message, 200);
    }

    public function logout(Request $request)
    {

        try {

            $token = $request->token;

            $decryptval = explode("_", decryptId($token));

            $id = $decryptval[1];

            /**
             * check user valid token
             */

            $where_array = array(
                'id' => $id,
                "api_token" => $token
            );
            $existcount = User::where($where_array)->count();


            if ($existcount == 1) {

                $where_array = array(
                    'id' => $id,
                    "api_token" => $token
                );

                User::where($where_array)->update(['api_token' => '']);

                return response()->json(['message' => "successfully logged out"], 200);
            } else {
                return response()->json(['message' => "invalid token"], 400);
            }
        } catch (\Exception $ex) {
            return response()->json(['message' => "invalid token"], 400);
        }
    }

    public function GetBusDetails(Request $request)
    {

        try {

            $token = $request->token;

            $decryptval = explode("_", decryptId($token));

            $id = $decryptval[1];

            /**
             * check user valid token
             */

            $where_array = array(
                'id' => $id,
                "api_token" => $token
            );
            $existcount = User::where($where_array)->first();

            if ( $existcount != null ||  $existcount != '') {

                $where_array = array(
                    'id' => $id,
                    "api_token" => $token
                );

                $busDetails =  Bus::where('assigned_driver', $id)->first();

                if ($busDetails == '' ||  $busDetails == null) {
                    $data = array(
                        'message' => "No Bus Found",
                    );

                    return response()->json($data, 400);
                } else {

                    $busDetails =  $busDetails->toArray();
                }

                $data = array(
                    'message' => "success",
                    'bus' => $busDetails
                );

                return response()->json($data, 200);
            } else {
                return response()->json(['message' => "No Bus mapped with this driver"], 400);
            }
        } catch (\Exception $ex) {
            return response()->json(['message' => "invalid token"], 400);
        }
    }
}
