<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ChangepasswordJob;
use App\Jobs\ResetpasswordJob;
use App\Models\Admin\SettingGeneral;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Mail\Otpmail;
use Mail;
use Log;
use Session;
use Str;

class LoginController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }


    public function index()
    {
       
        return view('auth.landingpage');
    }

    public function student_login()
    {
        return view('auth.studentlogin');
    }

    public function staff_login()
    {
        return view('auth.stafflogin');
    }

    public function CGPA_Calculator()
    {
        return view('auth.cgpacalculator');
    }


    public function authenticate(Request $request)
    {

        // Auth::loginUsingId(1);
        // $request->session()->regenerate();
        // return redirect()->intended(admin_url('dashboard'));

        $rules = [
            'email' => 'required|email',
            'password' => 'required|email',
        ];
        $messages = [
            'email.required' => 'Please enter your email address!',
            'email.email' => 'Please enter a valid email address!',
            'password.required' => 'Please enter your password',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            Session::flash('error', 'Invalid Username/Password!');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $credentials['role'] = decryptId($request->loginas);
        $remember = $request->has('remember') ? true : false;

        if (Auth::attempt($credentials)) {

            $user = User::where('email', $request->email)->first();
            $request->session()->regenerate();
            return redirect()->intended(admin_url('dashboard'));
        }

        Session::flash('error', 'Invalid Username/Password!');

        return back()->withErrors([
            'email' => 'Username or Password is incorrect',
        ]);
    }





    public function showLoginForm()
    {

        header("Location:" . env('SITE_ONE_LINK')); /* Redirect browser */
        exit();
        session(['link' => url()->previous()]);
        return view('auth.login');
    }



    public function DirectLogin(Request $request)
    {

        $user_id = decryptId($request->id);

        if (Auth::loginUsingId($user_id)) {

            $request->session()->regenerate();
            return redirect()->intended(admin_url('dashboard'));
        }

        return redirect()->back();
    }

    public function logout(Request $request)
    {

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect(url('login'));
    }

    public function resetPassword(Request $request)
    {

        $rules = [
            'email' => 'required|email',
        ];
        $messages = [
            'email.required' => 'Enter official mail ID',
            'email.email' => 'Enter valid email ID',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = DB::table('users')->where('trash', 'NO')->where('email', '=', $request->email)
            ->first();

        if ($user == null) {

            $userdetails = userDetails();

            $logmessage = 'Invalid user try to login ' . $request->email . ', in user-agent : ' . $userdetails['useragent'] . ', User IP : ' . $userdetails['ip'];
            Log::channel('password-reset')->warning($logmessage);

            return redirect()->back()->with(['status' => trans('A reset password link has been sent to your Registered email address.')]);
        }

        DB::table('password_resets')->where('email', $user->email)
            ->delete();

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => Str::random(60),
            'created_at' => Carbon::now(),
        ]);

        $tokenData = DB::table('password_resets')
            ->where('email', $request->email)
            ->orderBy('created_at', 'Desc')
            ->first();
        $logmessage = 'User ' . $tokenData->email . ' request for Password Reset on ' . $tokenData->created_at . " User reset token is " . $tokenData->token;
        Log::channel('password-reset')->info($logmessage);

        //return redirect()->back()->with('status', trans('A reset password link has been sent to your Register email address.'));

        if ($this->sendResetEmail($request->email, $tokenData->token)) {
            return redirect()->back()->with('status', trans('A reset password link has been sent to your registered email address.'));
        } else {
            return redirect()->back()->withErrors(['error' => trans('A Network Error occurred. Please try again.')]);
        }
    }

    private function sendResetEmail($email, $token)
    {

        $user = DB::table('users')->where('trash', 'NO')->where('email', $email)->select('name', 'email')->first();

        $link = getHost() . 'password/reset/' . $token . '?email=' . urlencode($user->email);

        try {

            $details = [
                "email" => $user->email,
                "name" => $user->name,
                "link" => $link,
                "expire" => "",
            ];

            dispatch((new ResetpasswordJob($details))->onQueue('high'));

            return true;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    public function Passwordreset(Request $request)
    {

        //Validate input
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users,email',
                'password' => 'required|same:password-confirm',
                'token' => 'required'
            ]
        );

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator);
        }

        $password = $request->password;

        $newTime = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . " -" . get_constant('RESET_PASSWORD_EXPIRE') . " minutes"));

        $tokenData = DB::table('password_resets')
            ->where('token', $request->token)
            ->where('created_at', '>=', $newTime)
            ->first();

        if ($tokenData == null) {

            return redirect()->back()->withErrors(['email' => 'Forgot password link Expired']);
        }

        $user = User::where('email', $tokenData->email)->first();

        if (!$user) {

            return redirect()->back()->withErrors(['email' => 'Email not found']);
        }

        $user->password = Hash::make($password);
        $user->update();

        //Auth::login($user);

        DB::table('password_resets')
            ->where('email', $user->email)
            ->delete();

        if ($this->sendSuccessEmail($tokenData->email)) {
            return redirect(url('login'));
        } else {

            return redirect()->back()->withErrors(['email' => trans('A Network Error occurred. Please try again.')]);
        }
    }

    private function sendSuccessEmail($email, $token = '')
    {

        $user = DB::table('users')->where('trash', 'NO')->where('email', $email)->select('name', 'email')->first();

        try {

            $details = [
                "email" => $user->email,
                "name" => $user->name,
                "link" => '',
            ];
            dispatch((new ChangepasswordJob($details))->onQueue('high'));

            return true;
        } catch (\Exception $e) {

            return false;
        }
    }

    protected function credentials(Request $request)
    {

        if (is_numeric($request->get('email'))) {
            return ['phone' => $request->get('email'), 'password' => $request->get('password')];
        } elseif (filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {

            return ['email' => $request->get('email'), 'password' => $request->get('password')];
        }
        return ['loginid' => $request->get('email'), 'password' => $request->get('password')];
    }

    protected function loginTry($credentials, $request)
    {

        $userDetails = USER::withoutGlobalScopes()
            ->select('*')
            ->where($credentials)
            ->first();

        return $userDetails;
    }


    public function validate_email(Request $request)
    {

        if ($request->input('email') !== '') {
            if ($request->input('email')) {
                $rule = array('email' => 'Required|email|unique:users');
                $validator = Validator::make($request->all(), $rule);
            }
            if (!$validator->fails()) {
                die('true');
            }
        }
        die('false');
    }

    public function Account_Activate(Request $request)
    {

        if (Auth::check()) {
            if (Auth::user()->email == $request->email) {
                return redirect('login');
            }
        }

        $data = array(
            'token' => $request->token,
            'email' => $request->email,
            'status' => 'not_activate',
        );
        $whrere_array = array(
            'active_tokan' => $request->token,
            'email' => $request->email,
            'status' => '0',
        );

        $userdetails = User::where($whrere_array)->first();

        if ($userdetails == null) {
            $data['status'] = 'already_active';
        }

        return view('admin.account_activate', $data);
    }

    public function SubmitAccountActivate(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:12|same:password-confirm',
                'token' => 'required'
            ]
        );

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator);
        }

        $password = $request->password;

        $whrere_array = array(
            'active_tokan' => $request->token,
            'email' => $request->email,
            'is_active' => '0',
        );

        $user = User::where($whrere_array)->first();

        if ($user == null) {
            return redirect()->back()->withErrors(['email' => 'Your account has already been activated.']);
        }

        $user = User::where('email', $user->email)->first();

        if (!$user) {
            return redirect()->back()->withErrors(['email' => 'Email not found']);
        }

        $user->password = Hash::make($password);
        $user->active_tokan = '';
        $user->is_active = '1';
        $user->status = '1';
        $user->update();

        //Auth::login($user);

        return redirect(admin_url('dashboard'));
    }

    public function Otp(Request $request)
    {

        $sure = Auth::user();
        $user_email = $sure->email;
        $user_name = $sure->name;
        $user_otp = $sure->reset_otp;

        /**Email hashed  */

        $parts = explode('@', $user_email);
        $email_hash = '************' . "@" . $parts[1];

        /**Email sending part for OTP --STARTS--*/
        try {
            $link = '';
            $details = [
                "email" => $user_email,
                "name" => $user_name,
                "reset_otp" => $user_otp,
                "link" => $link,
                "expire" => get_constant('RESET_PASSWORD_EXPIRE'),
            ];

            $email = new Otpmail($details);
            Mail::to($details['email'])->send($email);
        } catch (\Exception $e) {
            dd($e);
            report($e);
        }
        /***Email sending part for OTP --ENDS--*/

        $data = array(
            'email' => $sure->email,
            'sure' => $sure,
            'email_hash' => $email_hash,
        );

        return view('auth.passwords.otp', $data);
    }

    public function OtpSubmit(Request $request)
    {
        $general_settings = SettingGeneral::first('otp_date');
        $otp_value = ($general_settings['otp_date']);

        $mil = strtotime(date("Y-m-d", strtotime("+" . $otp_value . "days")));

        $date_otp = (date("Y-m-d", $mil));

        $email = $request->email;
        $otp = $request->otp_check;
        $password = $request->password;
        $password = $request->password_confirm;

        $whrere_array = array(
            'email' => $email,
        );

        $user = User::where($whrere_array)->first();
        $user_otp = $user->reset_otp;

        if ($user_otp == $otp) {

            $update_password = array(
                'password' => Hash::make($password),
                'password_date' => $date_otp,
                'forcepassword' => 0,
            );

            $update = User::where($whrere_array)->update($update_password);
            return redirect(admin_url('dashboard'));
        } else {

            return redirect()->back()->withErrors(['otp' => 'Otp is not match']);
        }
    }

    public function forcepassword_change(Request $request)
    {

        $user_details = User::where('status', 1)->where('trash', 'NO')->get();

        $password_date['id'] = $password_date['date'] = $password_date['created_date'] = [];

        $curret_date = date('Y-m-d');

        foreach ($user_details as $user) {
            $whrere_array = array(
                'email' => $user->email,
            );

            if ($curret_date == $user->password_date) {
                $force_password_update = array(
                    'forcepassword' => 1,
                );
                $update = User::where($whrere_array)->update($force_password_update);
            }
        }
    }

   
}
