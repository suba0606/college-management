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
use App\Models\Admin\Department;




class DepartmentController extends Controller
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

                    $data = Department::select([
                        'id',
                        'department_name',
                    ]);


                    if ($request->has('department') && $request->department != '') {
                        $data = $data->where('department_name', "LIKE", "%" . $request->department . "%");
                    }

                    $data_count = $data = $data->orderBy('created_at', 'Desc');
                    $total_records = $data_count->count();


                    if ($request->length >= 0) {
                        $data = $data->offset($request->start)->limit($request->length);
                    }
                    $data = $data->get();

                    $datatables = Datatables::of($data)
                        ->addIndexColumn()

                        ->addColumn('role_name', function ($row) {

                            $role_name =   getCustomValue('template_user_role', 'role_name', $row->role);
                            return $role_name;
                        })


                        ->addColumn('action', function ($row) {
                            $btn = '';

                            $btn .= '<a href="' . admin_url('Department/edit/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-edit" ></i></a> ';

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



        $data = array();


        return view('admin.Department_details_list', $data);
    }

    public function DepartmentAdd(Request $request)
    {

        $data = array();

        return view('admin.Department_details_add', $data);
    }

    public function Departmentcheck(Request $request)
    {

        if ($request->ajax()) {

            if ($request->deptid == '') {
                $Department = Department::where('department_name', $request->department_name)->get();
            } else {
                $Department = Department::where('department_name', $request->department_name)
                    ->where('id', '!=', decryptId($request->deptid))
                    ->get();
            }

            if ($Department->count()) {
                return Response::json(array('msg' => 'true'));
            }
            return Response::json(array('msg' => 'false'));
        }
    }

    public function DepartmentAddSubmit(Request $request)
    {

        try {

            $insert_data = array(
                'department_name' => $request->department_name,
                'status' =>  1,
                'trash' => 'NO',
                'created_by' => 1,
            );

            Department::create($insert_data);

            Session::flash('success', 'Department added successfully!');

            return redirect(admin_url('Department'));
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('Department'));
        }
    }



    public function DepartmentEdit(Request $request)
    {
        try {

            $id = decryptId($request->deptid);
            $Department = Department::where('id', $id)->first();

            $data = array(
                'Department' => $Department,
            );

            return view('admin.Department_details_edit', $data);
        } catch (Exception $error) {
            report($error->getMessage());
            dd($error);
        }
    }

    public function DepartmentUpdate(Request $request)
    {
        try {

            $id = decryptId($request->deptid);

           

            Department::where('id', $id)->update(['department_name' => $request->department_name]);

            Session::flash('success', 'User updated successfully!');

            return redirect(admin_url('Department'));
        } catch (Exception $ex) {
            dd($ex);
            return "Error";
        }
    }

    public function DepartmentStatus(Request $request)
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

    public function DepartmentDelete(Request $request)
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
