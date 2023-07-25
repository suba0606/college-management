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
use App\Models\Admin\Subject;





class SubjectController extends Controller
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

                    $data = Subject::select([
                        'id',
                        'subject_name',
                        'department',
                        'semester',
                    ]);

                    if ($request->has('subject_name') && $request->subject_name != '') {
                        $data = $data->where('subject_name', "LIKE", "%" . $request->subject_name . "%");
                    }

                    if ($request->has('semester') && $request->semester != '') {
                        $data = $data->where('semester',  $request->semester);
                    }

                    if ($request->has('department') && $request->department != '') {
                        $department = decryptId($request->department);
                        $data = $data->where('department', $department);
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


                        ->addColumn('action', function ($row) {
                            $btn = '';
                            // $btn = '<a href="' . admin_url('Subject/' . encryptId($row['id'])) . '"   class="" title="View"><i class="fa  fa-eye" ></i></a> ';
                            $btn .= '<a href="' . admin_url('Subject/edit/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-edit" ></i></a> ';

                            return $btn;
                        })
                        ->rawColumns(['action', 'status'])
                        ->setFilteredRecords($total_records)
                        ->setTotalRecords($total_records)
                        ->make(true);
                    return $datatables;
                } catch (Exception $ex) {
                    report($ex);
                    dd($ex);
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



        return view('admin.Subject_details_list', $data);
    }

    public function SubjectAdd(Request $request)
    {
        $id = Auth::id();

        $department = Department::get();

        $data = array(
            'Department' => $department
        );

        return view('admin.Subject_details_add', $data);
    }

    public function Staffemailcheck(Request $request)
    {

        if ($request->ajax()) {

            if ($request->userid == '') {
                $user = User::where('email', $request->email)->get();
            } else {
                $user = User::where('email', $request->email)
                    ->where('id', '!=', decryptId($request->userid))
                    ->get();
            }

            if ($user->count()) {
                return Response::json(array('msg' => 'true'));
            }
            return Response::json(array('msg' => 'false'));
        }
    }

    public function SubjectAddSubmit(Request $request)
    {

        try {

            $insert_data = array(
                'subject_name' => $request->subject_name,
                'department' => decryptId($request->department),
                'semester' => $request->semester,
                'created_by' => Auth::id(),
            );

            Subject::create($insert_data);



            Session::flash('success', 'Subject added successfully!');

            return redirect(admin_url('Subject'));
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('Subject'));
        }
    }

    public function StaffView(Request $request)
    {

        try {
            $id = decryptId($request->userid);

            if (Auth::check()) {
                $data['user_details'] = User::find($id);
            }
            return view('admin.User_details_view', $data);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
        }
    }

    public function SubjectEdit(Request $request)
    {
        try {

            $id = decryptId($request->id);

            $subject = Subject::where('id', $id)->first();


            $department = Department::get();

            $data = array(
                'Department' => $department,
                'subject' => $subject,
            );

            return view('admin.Subject_details_edit', $data);
        } catch (Exception $error) {
            report($error->getMessage());
            dd($error);
        }
    }

    public function SubjectUpdate(Request $request)
    {
        try {

            $id = decryptId($request->id);

            $update_data = array(
                'subject_name' => $request->subject_name,
                'department' => decryptId($request->department),
                'semester' => $request->semester,
                'updated_by' => Auth::id(),
            );

            Subject::where('id', $id)->update($update_data);

            Session::flash('success', 'Subject updated successfully!');

            return redirect(admin_url('Subject'));
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
