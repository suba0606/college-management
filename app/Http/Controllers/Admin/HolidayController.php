<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

use DB;
use Exception;
use Auth;
use Str;
use DataTables;
use Session;


use App\Models\Holiday;
use App\Models\Admin\HolidayRole;

use App\Models\Admin\Events;
use App\Models\Admin\Department;




class HolidayController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::check()) {
                try {

                    $data = Events::select([
                        'id',
                        'event_type',
                        'event_date',
                        'event_name',
                        'department',
                    ]);

                    $data = $data->where('event_type', '1');

                    if ($request->has('event_date') && $request->event_date != '') {
                        $data = $data->where('event_date', DBdateformat($request->event_date));
                    }

                    if ($request->has('event_name') && $request->event_name != '') {

                        $data = $data->where('event_name', "LIKE", "%" . $request->event_name . "%");
                    }

                    if ($request->has('department') && $request->department != '') {
                        $department = decryptId($request->department);
                        $data = $data->where('department', $department);
                    }
                    if ($request->has('status') && $request->status != '') {
                        $status = decryptId($request->status);
                        $data = $data->where('status', $status);
                    }
                    $data_count = $data = $data->orderBy('created_at', 'Desc');
                    $total_records = $data_count->count();


                    if ($request->length >= 0) {
                        $data = $data->offset($request->start)->limit($request->length);
                    }
                    $data = $data->get();

                    $datatables = Datatables::of($data)
                        ->addIndexColumn()

                        ->addColumn('department_name', function ($row) {
                            if($row->department == 0){
                                return "All";
                            }else{
                                $department =   getCustomValue('admin_department','department_name', $row->department);
                                return $department;
                            }
                            
                        })
                        ->addColumn('event_date', function ($row) {
                           
                                return Displaydateformat($row->event_date);
                            
                            
                        })
                        ->addColumn('action', function ($row) {
                            $btn = '';
                            // $btn = '<a href="' . admin_url('Holiday/' . encryptId($row['id'])) . '"   class="" title="View"><i class="fa  fa-eye" ></i></a> ';
                            $btn .= '<a href="' . admin_url('Holiday/edit/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-edit" ></i></a> ';

                            return $btn;
                        })
                        ->rawColumns(['action', 'status'])
                        ->setFilteredRecords($total_records)
                        ->setTotalRecords($total_records)
                        ->make(true);
                    return $datatables;
                } catch (Exception $ex) {
                    report($ex);
                    DD($ex);
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } else {
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 401);
            }
        }


        $Department = Department::get();

        $data = array(
            'Department' => $Department,
        );


        return view('admin.Holiday_details_list', $data);
    }

    public function HolidayAdd(Request $request)
    {
        $id = Auth::id();

        $department = Department::get();

        $data = array(
            'Department' => $department
        );
        return view('admin.Holiday_details_add', $data);
    }

    public function eventdatecheck(Request $request)
    {

        if ($request->ajax()) {

            if ($request->Holidayid == '') {
                $Holiday = holiday::where('event_date', $request->event_date)->get();
            } else {
                $Holiday = holiday::where('event_date', $request->event_date)
                    ->where('id', '!=', decryptId($request->event_date))
                    ->get();
            }

            if ($Holiday->count()) {
                return Response::json(array('msg' => 'true'));
            }
            return Response::json(array('msg' => 'false'));
        }
    }

    public function HolidayAddSubmit(Request $request)
    {

        try {

            $login_id = Auth::Holiday()->role;

            $rules = [
                'fname' => 'required',
                'lname' => 'required',
                'email' => 'required|email',

            ];
            $messages = [
                'fname.required' => 'Please enter first name',
                'lname.required' => 'Please enter last name',
                'email.required' => 'Please enter email address',
                'email.email' => 'Please enter a valid email address',

            ];
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {

                return redirect()->back()->withErrors($validator)->withInput();
            }

            $password = Str::random(12);
            $password = 'asdF@1234567';

            $insert_data = array(
                'first_name' => $request->fname,
                'last_name' => $request->lname,
                'name' => $request->fname . " " . $request->lname,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'remember_token' => '',
                'role' => decryptID($request->role),
                'password' => Hash::make($password),
                'active_tokan' => Str::random(60),

                'status' =>  1,
                'password_reset' => 0,
                'trash' => 'NO',
                'created_by' => 1,
                'created_date' => date('Y-m-d'),
            );

            $file = $request->file('profile_image');
            if ($file != null) {
                $uploadpath = 'public/uploads/profile';

                $filenewname = time() . Str::random('10') . '.' . $file->getClientOriginalExtension();
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $fileMimetype = $file->getMimeType();
                $fileExt = $file->getClientOriginalExtension();
                $file->move($uploadpath, $filenewname);
                $insert_data['profile_image'] = $filenewname;
            } else {
                $insert_data['profile_image'] = '';
            }


            $Holidaydetails = Holiday::create($insert_data);

            $link = admin_url() . 'Account_Activate/' . $Holidaydetails['active_tokan'] . '?email=' . urlencode($Holidaydetails['email']);

            try {

                $details = [
                    "email" => $request->email,
                    "name" => $request->name,
                    "link" => $link,
                    "expire" => get_constant('RESET_PASSWORD_EXPIRE')
                ];

                dispatch((new SetpasswordJob($details))->onQueue('high'));

                Session::flash('success', 'Holiday added successfully!');

                return redirect(admin_url('Holiday'));
            } catch (\Exception $e) {
                report($e);
                return redirect(admin_url('Holiday'));
            }
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('Holiday'));
        }
    }

    public function HolidayView(Request $request)
    {

        try {
            $id = decryptId($request->Holidayid);

            if (Auth::check()) {
                $data['Holiday_details'] = Holiday::find($id);
            }
            return view('admin.Holiday_details_view', $data);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
        }
    }

    public function HolidayEdit(Request $request)
    {
        try {

            $id = decryptId($request->Holidayid);

            $Holiday_details = Holiday::where('id', $id)->first();


            $role_details = HolidayRole::get();

            $data = array(
                'role_details' => $role_details,
                'Holiday_details' => $Holiday_details,

            );

            return view('admin.Holiday_details_edit', $data);
        } catch (Exception $error) {
            report($error->getMessage());
            dd($error);
        }
    }

    public function HolidayUpdate(Request $request)
    {
        try {

            $id = decryptId($request->Holidayid);

            $update_data = array(
                'first_name' => $request->fname,
                'last_name' => $request->lname,
                'name' => $request->fname . " " . $request->lname,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'role' => decryptID($request->role),

            );

            $file = $request->file('profile_image');
            if ($file != null) {
                $uploadpath = 'public/uploads/profile';

                $filenewname = time() . Str::random('10') . '.' . $file->getClientOriginalExtension();
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $fileMimetype = $file->getMimeType();
                $fileExt = $file->getClientOriginalExtension();
                $file->move($uploadpath, $filenewname);
                $update_data['profile_image'] = $filenewname;
            }

            $Holidaydetails = Holiday::where('id', $id)->update($update_data);

            Session::flash('success', 'Holiday updated successfully!');

            return redirect(admin_url('Holiday'));
        } catch (Exception $ex) {
            dd($ex);
            return "Error";
        }
    }

    public function HolidayStatus(Request $request)
    {

        try {
            $Holiday_id = decryptId($request->Holiday_id);
            $type = $request->types;

            if ($type == 1) {
                $update_data = array(
                    'status' => 0,
                );

                $message = 'Holiday was In-Activated';
                $successMsg = 'Successfully Holiday  was In-Activated';

                $where_data = array(
                    'id' => $Holiday_id,
                );
                Holiday::where($where_data)->update($update_data);
            } else {
                $update_data = array(
                    'status' => 1,

                );
                $message = 'Holiday was Activated';
                $successMsg = 'Holiday successfully activated';

                $update_Holidaydata = array(
                    'status' => 1,
                    'is_active' => 1,
                    'active_tokan' => Str::random(60),
                );

                $where_data = array(
                    'id' => $Holiday_id,
                );

                Holiday::where($where_data)->update($update_Holidaydata);

                $practice_details = Holiday::where($where_data)->first();
            }

            return response()->json(['status' => 'success', 'msg' => $successMsg], 200);
        } catch (Exception $ex) {
            dd($ex);
            return response()->json(['error' => $ex, 'status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function HolidayDelete(Request $request)
    {

        try {
            $Holidayid = decryptId($request->Holiday_id);

            $update_data = array(
                'status' => 0,
                'trash' => "YES",
            );
            $where_data = array(
                'id' => $Holidayid
            );

            Holiday::where($where_data)->update($update_data);

            $message = 'Your Account has been Deleted';

            try {

                $Holiday_details = Holiday::where($where_data)->first();

                if ($Holiday_details != null) {

                    $details = array(
                        "event_date" => $Holiday_details['event_date'],
                        "event_name" => $Holiday_details['event_name'],
                        'message' => $message,
                    );

                    dispatch((new AccountStatusJob($details))->onQueue('high'));
                }
            } catch (Exception $ex) {

                report($ex);
                return response()->json(['error' => '1', 'status' => 'error', 'msg' => 'Please try after some time'], 406);
            }



            return response()->json(['status' => 'success', 'msg' => 'Holiday deleted successfully'], 200);
        } catch (Exception $ex) {
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }
}
