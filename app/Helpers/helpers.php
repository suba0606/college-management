<?php

use Illuminate\Support\Str;


use Twilio\Rest\Client;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

/*
 * Encrypt & Decrypt start
 */

if (!function_exists('get_encryptVal')) {

    function get_encryptVal($id)
    {

        return strtr(base64_encode($id), '+/=', '-_,');
    }
}

if (!function_exists('get_decryptVal')) {

    function get_decryptVal($id)
    {
        return base64_decode(strtr($id, '-_,', '+/='));
    }
}
/*
 * Encrypt & Decrypt End
 */

if (!function_exists('DBdateformat')) {

    function DBdateformat($date)
    {

        return date('Y-m-d', strtotime($date));
    }
}

if (!function_exists('DBdatetimeformat')) {

    function DBdatetimeformat($date)
    {

        return date('Y-m-d H:i:s', strtotime($date));
    }
}

if (!function_exists('Displaydateformat')) {

    function Displaydateformat($date)
    {

        return date('d-m-Y', strtotime($date));
    }
}

if (!function_exists('datastringreplace')) {

    function datastringreplace($date)
    {

        return str_replace("/", "-", $date);
    }
}

if (!function_exists('Displaydatetimeformat')) {

    function Displaydatetimeformat($date)
    {

        return date('d-m-Y H:i:s', strtotime($date));
    }
}

if (!function_exists('todaydate')) {

    function todaydate()
    {

        return date('d-m-Y');
    }
}

if (!function_exists('todayDbdate')) {

    function todayDbdate()
    {

        return date('Y-m-d');
    }
}

if (!function_exists('todaydatetime')) {

    function todaydatetime()
    {

        return date('d-m-Y H:i:s');
    }
}

if (!function_exists('todayDBdatetime')) {

    function todayDBdatetime()
    {

        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('monthyear')) {

    function monthyear()
    {

        return date('F,Y');
    }
}

if (!function_exists('currentyear')) {

    function currentyear()
    {

        return date('Y');
    }
}

if (!function_exists('print_array')) {

    function print_array($data, $exit = true)
    {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        if ($exit)
            exit;
    }
}

if (!function_exists('parseData')) {

    function parseData($results = array(), $postval = '', $retunval = '')
    {
        $results = (is_array($results) && $results != FALSE) ? (object) $results : $results;
        return ($results != FALSE && isset($results->$postval) && ($results->$postval != '')) ? $results->$postval : $retunval;
    }
}

if (!function_exists('userDetails')) {

    function userDetails()
    {

        $request = request();
        $input['useragent'] = $request->server('HTTP_USER_AGENT');
        $input['ip'] = $request->ip();
        return ($input);
    }
}

if (!function_exists('insertUserLog')) {

    function insertUserLog($event = '', $custom_msg = '')
    {
        $request = request();

        if (\Auth::check()) {
            $input['user_id'] = \Auth::id();
        }

        $input['params'] = json_encode($request->all());
        $input['user_agent'] = $request->server('HTTP_USER_AGENT');
        $input['user_ip'] = $request->ip();
        $input['request_type'] = $request->method();
        $input['page_url'] = $request->fullUrl();
        $input['user_event'] = $event;
        $input['custom_msg'] = $custom_msg;

        if ($request->ajax()) {
            $input['is_ajax'] = 'YES';
        }

        DB::table('template_user_page_visit')->insert($input);

        return true;
    }
}

if (!function_exists('string_to_array')) {

    function string_to_array($string, $separate = ',')
    {

        return explode($separate, $string);
    }
}

if (!function_exists('array_to_string')) {

    function array_to_string($array, $separate = ',')
    {

        return implode($separate, $array);
    }
}

if (!function_exists('merge_two_array')) {

    function merge_two_array($array1, $array2)
    {

        return $array = array_values(array_unique(array_merge($array1, $array2)));
    }
}

if (!function_exists('arrayEncrypt')) {

    function arrayEncrypt($arrayVal)
    {
        return array_map("encryptId", $arrayVal);
    }
}

if (!function_exists('arrayDecrypt')) {

    function arrayDecrypt($arrayVal)
    {
        if (count($arrayVal) > 0) {
            return array_map("decryptId", $arrayVal);
        } else {
            return [];
        }
    }
    if (!function_exists('datestringreplace')) {

        function datestringreplace($date)
        {

            return str_replace("-", "/", $date);
        }
    }
}

if (!function_exists('DateTimeFormat')) {

    function DateTimeFormat($date)
    {
        $date = str_replace("/", "-", $date);
        return date('Y-m-d H:i:s', strtotime($date));
    }
}

if (!function_exists('EndDayTime')) {

    function EndDayTime($date)
    {

        return date('Y-m-d 23:59:59', strtotime($date));
    }
}

if (!function_exists('YearFormat')) {

    function YearFormat($date)
    {
        $date = str_replace("/", "-", $date);
        return date('Y-m-d', strtotime($date));
    }
}

if (!function_exists('dateDiffInDays')) {

    function dateDiffInDays($date1, $date2)
    {
        $diff = strtotime($date2) - strtotime($date1);

        $dates = abs(round($diff / 86400));
        return $dates < 0 ? 0 : $dates + 1;
    }
}

if (!function_exists('DateMonthYearformat')) {

    function DateMonthYearformat($date)
    {

        return date('d/m/Y', strtotime($date));
    }
}

if (!function_exists('getHost')) {

    function getHost()
    {

        return  env('APP_URL');

        /*
            if (array_key_exists('HTTP_HOST', $_SERVER)) {
                $host = $_SERVER['HTTP_HOST'];
            } else {
                $host = '';
            }

            if ($host == '44.199.48.121') {
                $return = 'http://44.199.48.121/Bigbasket/Sponsored_Ads/V1/';
            } elseif ($host == 'bbmonetisation.com') {
                $return = 'http://bbmonetisation.com/Bigbasket/Sponsored_Ads/V1/';
            } elseif ($host == 'bbmonetisation.in') {
                $return = 'http://bbmonetisation.in/Bigbasket/Sponsored_Ads/V1/';
            } elseif ($host == 'localhost') {
                $return = env('APP_URL', 'http://localhost/Projects/BigBasket/visibility-tracker/');
            }elseif ($host == 'bbmonetisation.co.in') {
                $return = 'http://bbmonetisation.co.in/UAT/visibility-tracker/';
            } else {
                $return = 'http://bbmonetisation.co.in/UAT/visibility-tracker/';
            }

            return $return;
        */
    }
}

if (!function_exists('admin_url')) {

    function admin_url($value = "")
    {
        return config('constants.ADMIN_URL') . $value;
    }
}

if (!function_exists('get_constant')) {

    function get_constant($value)
    {

        return config('constants.' . $value);
    }
}

if (!function_exists('encryptId')) {

    function encryptId($value)
    {

        $action = 'encrypt';
        $string = $value;
        $output = false;
        $encrypt_method = "AES-256-CBC";

        $secret_key = 'P(0p!e@e$k';
        $secret_iv = 'Peop!eDe$k';

        // hash
        $key = hash('sha256', $secret_key);

        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {

            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;

        // return Crypt::encryptString($value);
    }
}

if (!function_exists('decryptId')) {

    function decryptId($encrypted)
    {

        $action = 'decrypt';
        $string = $encrypted;
        $output = false;
        $encrypt_method = "AES-256-CBC";

        $secret_key = 'P(0p!e@e$k';
        $secret_iv = 'Peop!eDe$k';

        // hash
        $key = hash('sha256', $secret_key);

        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {

            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;

        // return Crypt::decryptString($encrypted);
    }
}

if (!function_exists('getUsername')) {

    function getUsername($userid)
    {

        $user = DB::table('users')->select('name')->where('id', $userid)->where('trash', 'NO')->first();

        if ($user == null) {
            return 'User';
        } else {
            return $user->name;
        }
    }
}

if (!function_exists('getUseremail')) {

    function getUseremail($userid)
    {

        $user = DB::table('users')->select('email')->where('id', $userid)->where('trash', 'NO')->first();

        if ($user == null) {
            return 'User';
        } else {
            return $user->email;
        }
    }
}

if (!function_exists('getYesNoStatus')) {

    function getYesNoStatus($value)
    {
        $status = "";
        if ($value == 0) {
            $status = "NO";
        } elseif ($value == 1) {
            $status = "YES";
        } else {
            $status = "YES";
        }

        return $status;
    }
}

if (!function_exists('getChecked')) {

    function getChecked($value)
    {
        $status = "";
        if ($value == 0) {
            $status = "";
        } elseif ($value == 1) {
            $status = "checked";
        } else {
            $status = "";
        }

        return $status;
    }
}

if (!function_exists('getCheckedVal')) {

    function getCheckedVal($value, $check)
    {
        $status = "";
        if ($value == $check) {
            $status = "checked";
        } else {
            $status = "";
        }

        return $status;
    }
}

if (!function_exists('getSelected')) {

    function getSelected($value, $check)
    {
        $status = "";
        if ($value != $check) {
            $status = "";
        } elseif ($value == $check) {
            $status = "selected";
        } else {
            $status = "";
        }

        return $status;
    }
}

if (!function_exists('getActive')) {

    function getActive($value)
    {
        $status = "";
        if ($value == 0) {
            $status = "";
        } elseif ($value == 1) {
            $status = "active";
        } else {
            $status = "";
        }

        return $status;
    }
}

if (!function_exists('getCustomValue')) {

    function getCustomValue($table, $column, $value)
    {

        $returnval = DB::table($table)->where('id', $value)->where('trash', 'NO')->first();

        if ($returnval != null) {
            return $returnval->$column;
        } else {
            return '';
        }
    }
}

if (!function_exists('getSingleArray')) {

    function getSingleArray($array_val = array(), $key = "")
    {
        $return_array = array();
        foreach ($array_val as $array) {

            $return_array[] = $array->$key;
        }

        return $return_array;
    }
}

if (!function_exists('getMultipleValue')) {

    function getMultipleValue($table, $commaVal, $condCol, $dataCol)
    {
        $arrayCond = string_to_array($commaVal);

        $returnVal = DB::table($table)->whereIn($condCol, $arrayCond)->pluck($dataCol);

        $returnVal = array_to_string($returnVal->toArray(), ', ');

        return $returnVal;
    }
}

if (!function_exists('selectIfInString')) {

    function selectIfInString($value, $commaVal)
    {
        $arrayVal = string_to_array($commaVal);

        if (in_array($value, $arrayVal)) {
            $returnVal = "Selected";
        } else {
            $returnVal = "";
        }

        return $returnVal;
    }
}

if (!function_exists('selectIfInArray')) {

    function selectIfInArray($value, $arrayVal)
    {

        if (in_array($value, $arrayVal)) {
            $returnVal = "Selected";
        } else {
            $returnVal = "";
        }

        return $returnVal;
    }
}

if (!function_exists('getMimetype')) {
    function getMimetype($type)
    {

        switch ($type) {
            case "pdf":
                $mime = "application/pdf";
                break;
            case "csv":
                $mime = "text/csv";
                break;
            case "doc":
                $mime = "application/msword";
                break;
            case "docx":
                $mime = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
                break;
            case "jpeg":
                $mime = "image/jpeg";
                break;
            case "jpg":
                $mime = "image/jpeg";
                break;
            case "tif":
                $mime = "image/tiff";
                break;
            case "tiff":
                $mime = "image/tiff";
                break;
            case "txt":
                $mime = "text/plain";
                break;
            case "xls":
                $mime = "application/vnd.ms-excel";
                break;
            case "xlsx":
                $mime = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                break;
            case "zip":
                $mime = "application/zip";
                break;
            default:
                $mime = "";
                break;
        }

        return $mime;
    }
}

if (!function_exists('getProfileImage')) {

    function getProfileImage($image)
    {
        if ($image == null || $image == '') {
            return asset('public/asset/admin/images/avatars/default_profile.png');
        } else {
            $file = asset('public/uploads/profile/' . $image);
            if (does_url_exists($file)) {
                return asset('public/uploads/profile/' . $image);
            }
            return asset('public/asset/admin/images/avatars/default_profile.png');
        }
    }
}

if (!function_exists('does_url_exists')) {
    function does_url_exists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            $status = true;
        } else {
            $status = false;
        }
        curl_close($ch);
        return $status;
    }
}

if (!function_exists('getSequenceno')) {
    function getSequenceno($keyword, $id)
    {

        $zero = '';
        if ($id < 10000) {
            $zero = $zero . "0";
        }
        if ($id < 1000) {
            $zero = $zero . "0";
        }
        if ($id < 100) {
            $zero = $zero . "0";
        }
        if ($id < 10) {
            $zero = $zero . "0";
        }

        $seqno = $keyword  . $zero . $id;

        return $seqno;
    }
}

if (!function_exists('SanitizeInput')) {
    function SanitizeInput($string = '')
    {
        $returnString = preg_replace('/[^a-zA-Z0-9&-_! ]/s', '', $string);
        return $returnString;
    }
}

if (!function_exists('SanitizeInputArray')) {
    function SanitizeInputArray($input = [])
    {
        $returnArray = [];
        for ($i = 0; $i < count($input); $i++) {
            $returnArray[$i] = preg_replace('/[^a-zA-Z0-9&-_! ]/s', '', $input[$i]);
        }

        return $returnArray;
    }
}

if (!function_exists('getDefaultValue')) {
    function getDefaultValue($column)
    {

        $default_Details = DB::table('admin_setting_general_settings')->select($column)->first();
        $default_value = $default_Details->$column;

        return $default_value;
    }
}

if (!function_exists('priceRound')) {
    function priceRound($amount, $decimalPoint = 0)
    {
        if ($decimalPoint == 0) {
            return round($amount);
        } else {
            return round($amount, $decimalPoint);
        }
    }
}

if (!function_exists('generateotp')) {
    function generateotp($length)
    {
        $generator = "1357902468";
        $result = "";
        for ($i = 1; $i <= $length; $i++) {
            $result .= substr($generator, (rand() % (strlen($generator))), 1);
        }
        return $result;
    }
}

if (!function_exists('ListDateFormatChange')) {
    function ListDateFormatChange($dateString)
    {

        if ($dateString != '') {
            $dateArray = string_to_array($dateString);

            $newDates = [];
            foreach ($dateArray as $datevalue) {
                $newDates[] = Displaydateformat($datevalue);
            }

            return array_to_string($newDates, ', ');
        }
    }
}

if (!function_exists('getStartDateEnddate')) {
    function getStartDateEnddate($dateArray = [])
    {
        $ArrayCount = count($dateArray);

        if ($ArrayCount > 0) {
            $data['startdate'] = $dateArray[0];
            $data['enddate'] = $dateArray[$ArrayCount - 1];
            $data['daterange'] = Displaydateformat($data['startdate']) . " - " . Displaydateformat($data['enddate']);
            $data['status'] = 1;

            return $data;
        } else {
            $data['startdate'] = '';
            $data['enddate'] = '';
            $data['daterange'] = '';
            $data['status'] = 0;
            return $data;
        }
    }
}

if (!function_exists('sort_date')) {
    function sort_date($a, $b)
    {
        return \DateTime::createFromFormat('m-d-Y', $a) <=> \DateTime::createFromFormat('m-d-Y', $b);
    }
}

if (!function_exists('Arrayencode')) {
    function Arrayencode($data)
    {
        return base64_encode(serialize($data->toArray()));
    }
}

if (!function_exists('Arraydecode')) {
    function Arraydecode($data)
    {
        return unserialize(base64_decode($data))->toArray();
    }
}

if (!function_exists('get_time_ago')) {
    function get_time_ago($time)
    {
        $current_time = time() * 1000;

        $time_difference = $current_time - $time;

        if ($time_difference < 1) {
            return 'less than 1 second ago';
        }
        $condition = array(
            12 * 30 * 24 * 60 * 60 * 1000 => 'year',
            30 * 24 * 60 * 60 * 1000 => 'month',
            24 * 60 * 60 * 1000 => 'day',
            60 * 60 * 1000 => 'hour',
            60 * 1000 => 'minute',
            1000 => 'second',
        );

        foreach ($condition as $secs => $str) {
            $d = $time_difference / $secs;

            if ($d >= 1) {
                $t = round($d);
                return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
            }
        }
    }
}

if (!function_exists('sendMessage')) {
    function sendMessage($message, $recipients)
    {

        try {

            $account_sid = env("TWILIO_SID");
            $auth_token = env("TWILIO_AUTH_TOKEN");
            $twilio_number = env("TWILIO_NUMBER");
            $ext = "+91";
            $recipients = $ext . $recipients;
            $client = new Client($account_sid, $auth_token);
            $client->messages->create($recipients, ['from' => $twilio_number, 'body' => $message]);


            $insert_data = array(
                'message_send_by' => $twilio_number,
                'message_send_to' => $recipients,
                'message_content' => $message,
            );

            $message_details = twilio_msg::create($insert_data);

            return true;
        } catch (\Exception $ex) {
            report($ex);
            return $ex->getMessage();
        }
    }
}

if (!function_exists('timestamp_to_time')) {
    function timestamp_to_time($timestamp_to_time)
    {
        return date('H:i:s', $timestamp_to_time);
    }
}

if (!function_exists('timestamp_to_date')) {
    function timestamp_to_date($timestamp_to_time)
    {
        return date('d-m-Y', $timestamp_to_time);
    }
}

if (!function_exists('timestamp_to_dateTime')) {
    function timestamp_to_dateTime($timestamp_to_time)
    {
        return date('d-m-Y H:i:s', $timestamp_to_time);
    }
}

if (!function_exists('EmailValidation')) {
    function EmailValidation($emailid)
    {
        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation()

        ]);
        
        return $validator->isValid($emailid, $multipleValidations); //true
    }
}

if (!function_exists('EmailValidationWithDNS')) {
    function EmailValidationWithDNS($emailid)
    {
        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation()
        ]);
        //ietf.org has MX records signaling a server with email capabilities
        return $validator->isValid($emailid, $multipleValidations); //true
    }
}
