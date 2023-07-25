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
use App\Models\Admin\Trip;
use App\Models\Admin\TripDetails;


class TripController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }


    public function TripStart(Request $request)
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

            if ($existcount != null ||  $existcount != '') {

                $where_array = array(
                    'id' => $id,
                    "api_token" => $token
                );

                $busDetails =  Bus::where('assigned_driver', $id)->where('id', $request->bus_id)->first();

                if ($busDetails == '' ||  $busDetails == null) {
                    $data = array(
                        'message' => "No Bus Found",
                    );

                    return response()->json($data, 400);
                } else {
                    $busid =  $busDetails->id;

                    $update_array = array(
                        'live_status' => 1
                    );

                    Bus::where('id', $request->bus_id)->update($update_array);

                    if ($request->type == "COLLEGE") {
                        $destination['lat'] = $busDetails->from_loc_lat;
                        $destination['lang'] = $busDetails->from_loc_lang;

                        $from =  'COLLEGE';
                        $to =  'HOME';
                    } else {
                        $destination['lat'] = $busDetails->end_loc_lat;
                        $destination['lang'] = $busDetails->end_loc_lang;

                        $from =  'HOME';
                        $to =  'COLLEGE';
                    }

                    $insertData = array(
                        'bus_id' => $busid,
                        'driver_id' => $id,
                        'date' => todayDBdatetime(),
                        'start_time' => todayDBdatetime(),
                        'trip_status' => 1,
                        'trip_from' => $from,
                        'trip_to' => $to,
                    );

                    $insertData =  Trip::create($insertData);

                    $data = array(
                        'message' => "Trip started successfully",
                        'destination' => $destination,
                        'data_send_freq' => getSettingValue('API_DATA_FREQ_SEC'),
                        "trip_id" => $insertData->id
                    );

                    return response()->json($data, 200);
                }
            } else {
                return response()->json(['message' => "No Bus mapped with this driver"], 400);
            }
        } catch (\Exception $ex) {
            dd($ex);
            return response()->json(['message' => "invalid token"], 400);
        }
    }

    public function TripUpdate(Request $request)
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

            if ($existcount != null ||  $existcount != '') {

                $where_array = array(
                    'id' => $id,
                    "api_token" => $token
                );

                $busDetails =  Bus::where('assigned_driver', $id)->where('id', $request->bus_id)->first();

                if ($busDetails == '' ||  $busDetails == null) {
                    $data = array(
                        'message' => "No Bus Found",
                    );

                    return response()->json($data, 400);
                } else {
                    $busid =  $busDetails->id;

                    $insert_array = array(
                        'bus_id' => $request->bus_id,
                        'trip_id' => $request->trip_id,
                        'date' => todayDBdatetime(),
                        'lat' => $request->lat,
                        'lang' => $request->lang,
                    );

                    TripDetails::create($insert_array);

                    $data = array(
                        'message' => "Updated",
                    );

                    return response()->json($data, 200);
                }
            } else {
                return response()->json(['message' => "No Bus mapped with this driver"], 400);
            }
        } catch (\Exception $ex) {
            dd($ex);
            return response()->json(['message' => "invalid token"], 400);
        }
    }


    public function TripEnd(Request $request)
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

            if ($existcount != null ||  $existcount != '') {

                $where_array = array(
                    'id' => $id,
                    "api_token" => $token
                );

                $busDetails =  Bus::where('assigned_driver', $id)->where('id', $request->bus_id)->first();

                if ($busDetails == '' ||  $busDetails == null) {
                    $data = array(
                        'message' => "No Bus Found",
                    );

                    return response()->json($data, 400);
                } else {


                    $insertData = array(

                        'end_time' => todayDBdatetime(),
                        'trip_status' => 2,

                    );

                    $insertData =  Trip::where('id', $request->trip_id )->update($insertData);

                    $data = array(
                        'message' => "Trip ended successfully",
                       
                    );

                    return response()->json($data, 200);
                }
            } else {
                return response()->json(['message' => "No Bus mapped with this driver"], 400);
            }
        } catch (\Exception $ex) {
            dd($ex);
            return response()->json(['message' => "invalid token"], 400);
        }
    }
}
