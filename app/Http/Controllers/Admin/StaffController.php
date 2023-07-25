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


use App\Models\User;
use App\Models\Admin\UserRole;
use App\Models\Admin\Department;
use App\Models\Admin\Staff;

use App\Jobs\SetpasswordJob;
use App\Jobs\AccountStatusJob;



class StaffController extends Controller
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

                    DB::enableQueryLog();

                    $data = Staff::select([
                        'id',
                        'staff_id',
                        'name',
                        'gender',
                        'mobile',
                        'department',
                        'status',
                        'created_by',
                    ]);

                    if ($request->has('name') && $request->name != '') {
                        $data = $data->where('name', "LIKE", "%" . $request->name . "%");
                    }

                    if ($request->has('department') && $request->department != '') {

                        $data = $data->where('department',  decryptId($request->department));
                    }

                    if ($request->has('mobile') && $request->mobile != '') {
                        $data = $data->where('mobile', "LIKE", "%" . $request->mobile . "%");
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

                            $role_name =   getCustomValue('admin_department', 'department_name', $row->department);
                            return $role_name;
                        })

                        ->addColumn('status', function ($row) {

                            if ($row->status == 1) {
                                $text = "<span style='color:green;cursor:pointer' class= 'StatusChange' data-id='" . encryptId($row['id']) . "' data-type = '1' >Active<span>";
                            } else if ($row->status == 0) {
                                $text = "<span style='color:red;cursor:pointer' class= 'StatusChange' data-id='" . encryptId($row['id']) . "' data-type = '0' >In-Active<span>";
                            }

                            return $text;
                        })
                        ->addColumn('action', function ($row) {
                            $btn = '';
                            $btn = '<a href="' . admin_url('StaffManagement/' . encryptId($row['id'])) . '"   class="" title="View"><i class="fa  fa-eye" ></i></a> ';
                            $btn .= '<a href="' . admin_url('StaffManagement/edit/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-edit" ></i></a> ';

                            return $btn;
                        })
                        ->rawColumns(['action', 'status'])
                        ->setFilteredRecords($total_records)
                        ->setTotalRecords($total_records)
                        ->make(true);
                    return $datatables;
                } catch (Exception $ex) {
                    dd($ex);

                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } else {

                dd($ex);
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 401);
            }
        }

        $Department = Department::get();

        $data = array(
            'Department' => $Department,

        );


        return view('admin.Staff_details_list', $data);
    }

    public function StaffAdd(Request $request)
    {

        $Department = Department::get();


        $data = array(
            'Department' => $Department
        );

        return view('admin.Staff_details_add', $data);
    }

    public function Staffemailcheck(Request $request)
    {

        if ($request->ajax()) {

            if ($request->userid == '') {
                $user = Staff::where('email', $request->email)->get();
            } else {
                $user = Staff::where('email', $request->email)
                    ->where('id', '!=', decryptId($request->userid))
                    ->get();
            }

            if ($user->count()) {
                return Response::json(array('msg' => 'true'));
            }
            return Response::json(array('msg' => 'false'));
        }
    }

    public function StaffAddSubmit(Request $request)
    {

        try {


            $password = 'asdF@1234567';

            $insert_data = array(
                'first_name' => '',
                'last_name' => '',
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'remember_token' => '',
                'role' => 2,
                'password' => Hash::make($password),
                'active_tokan' => Str::random(60),
                'status' =>  1,
                'trash' => 'NO',
                'created_by' => Auth::id(),
                'created_date' => date('Y-m-d'),
            );

            $user =  User::insertGetId($insert_data);


            $staff_insert = array(

                'staff_id' => $request->staff_id,
                'name' => $request->name,
                'gender' => $request->gender,
                'dob' => DBdateformat($request->dob),
                'mobile' => $request->mobile,
                'doj' => DBdateformat($request->doj),
                'department' => decryptId($request->department),
                'qualification' => $request->qualification,
                'experience' => $request->experience,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'country' => $request->country,
                'created_by' => Auth::id(),
                'user_id' => $user,

            );


            Staff::create($staff_insert);


            Session::flash('success', 'Staff added successfully!');

            return redirect(admin_url('StaffManagement'));
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('StaffManagement'));
        }
    }

    public function StaffView(Request $request)
    {

        try {
            $id = decryptId($request->userid);

            $Staff_details = Staff::where('id', $id)->first();
            $Department = Department::get();

            $data = array(
                'Department' => $Department,
                'staff' => $Staff_details,

            );
            return view('admin.Staff_details_view', $data);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
        }
    }

    public function StaffEdit(Request $request)
    {
        try {

            $id = decryptId($request->userid);

            $Staff_details = Staff::where('id', $id)->first();
            $Department = Department::get();

            $data = array(
                'Department' => $Department,
                'staff' => $Staff_details,

            );

            return view('admin.Staff_details_edit', $data);
        } catch (Exception $error) {
            report($error->getMessage());
            dd($error);
        }
    }

    public function StaffUpdate(Request $request)
    {
        try {

            $id = decryptId($request->userid);
            $staff = Staff::find($id);

            $user_update_data = array(
                'first_name' => '',
                'last_name' => '',
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'role' => 2,
                'status' =>  1,
                'trash' => 'NO',
            );

            User::where('id', $staff->user_id)->update($user_update_data);


            $staff_update_date = array(
                'staff_id' => $request->staff_id,
                'name' => $request->name,
                'gender' => $request->gender,
                'dob' => DBdateformat($request->dob),
                'mobile' => $request->mobile,
                'doj' => DBdateformat($request->doj),
                'department' => decryptId($request->department),
                'qualification' => $request->qualification,
                'experience' => $request->experience,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'country' => $request->country,
                'updated_by' => Auth::id(),
            );


            Staff::where('id', $id)->update($staff_update_date);


            Session::flash('success', 'Staff Updated successfully!');

            return redirect(admin_url('StaffManagement'));
        } catch (Exception $ex) {
            dd($ex);
            return "Error";
        }
    }

    public function StaffStatus(Request $request)
    {

        try {
            $user_id = decryptId($request->user_id);
            $type = $request->types;

            if ($type == 1) {
                $update_data = array(
                    'status' => 0,
                );

                $message = 'User was In-Activated';
                $successMsg = 'Successfully User  was In-Activated';

                $where_data = array(
                    'id' => $user_id,
                );
                User::where($where_data)->update($update_data);
            } else {
                $update_data = array(
                    'status' => 1,

                );
                $message = 'User was Activated';
                $successMsg = 'User successfully activated';

                $update_userdata = array(
                    'status' => 1,
                    'is_active' => 1,
                    'active_tokan' => Str::random(60),
                );

                $where_data = array(
                    'id' => $user_id,
                );

                User::where($where_data)->update($update_userdata);

                $practice_details = User::where($where_data)->first();
            }

            return response()->json(['status' => 'success', 'msg' => $successMsg], 200);
        } catch (Exception $ex) {
            dd($ex);
            return response()->json(['error' => $ex, 'status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function StaffDelete(Request $request)
    {

        try {
            $userid = decryptId($request->user_id);

            $update_data = array(
                'status' => 0,
                'trash' => "YES",
            );
            $where_data = array(
                'id' => $userid
            );

            User::where($where_data)->update($update_data);

            $message = 'Your Account has been Deleted';

            try {

                $user_details = User::where($where_data)->first();

                if ($user_details != null) {

                    $details = array(
                        "email" => $user_details['email'],
                        "name" => $user_details['name'],
                        'message' => $message,
                    );

                    dispatch((new AccountStatusJob($details))->onQueue('high'));
                }
            } catch (Exception $ex) {

                report($ex);
                return response()->json(['error' => '1', 'status' => 'error', 'msg' => 'Please try after some time'], 406);
            }



            return response()->json(['status' => 'success', 'msg' => 'User deleted successfully'], 200);
        } catch (Exception $ex) {
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }
}
