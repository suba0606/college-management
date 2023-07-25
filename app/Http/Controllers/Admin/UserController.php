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

use App\Imports\UserImport;

use App\Models\User;
use App\Models\Admin\UserRole;
use App\Models\Admin\MasterDepartment;
use App\Models\Admin\MasterJobTitle;

use App\Jobs\SetpasswordJob;
use App\Jobs\AccountStatusJob;

use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
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

                    $data = User::select([
                        'id',
                        'name',
                        'first_name',
                        'last_name',
                        'email',
                        'status',
                        'role',
                        'created_by',
                        'created_date',
                    ]);

                    // $data = $data->where('role', ROLE_ADMIN);
                    $name = $request->name;
                    if ($request->has('name') && $request->name != '') {
                        $data = $data->where('name', "LIKE", "%" . $request->name . "%");
                    }

                    if ($request->has('email') && $request->email != '') {

                        $data = $data->where('email', "LIKE", "%" . $request->email . "%");
                    }

                    if ($request->has('mobile') && $request->mobile != '') {
                        $data = $data->where('mobile', "LIKE", "%" . $request->mobile . "%");
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

                        ->addColumn('role_name', function ($row) {

                            $role_name =   getCustomValue('template_user_role', 'role_name', $row->role);
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
                            $btn = '<a href="' . admin_url('User_Management/' . encryptId($row['id'])) . '"   class="" title="View"><i class="fa  fa-eye" ></i></a> ';
                            $btn .= '<a href="' . admin_url('User_Management/edit/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-edit" ></i></a> ';

                            return $btn;
                        })
                        ->rawColumns(['action', 'status'])
                        ->setFilteredRecords($total_records)
                        ->setTotalRecords($total_records)
                        ->make(true);
                    return $datatables;
                } catch (Exception $ex) {
                    report($ex);

                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } else {
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 401);
            }
        }


        $user = User::get();
        $role_details = UserRole::get();
        $data = array(
            'role_details' => $role_details,
            'user_details' => $user
        );


        return view('admin.User_details_list', $data);
    }

    public function UserAdd(Request $request)
    {
        $id = Auth::id();
        $user = User::find($id);
        $role_details = UserRole::get();

        //$role_details = UserRole::find($user['role']);

        $data = array(
            'role_details' => $role_details
        );

        return view('admin.User_details_add', $data);
    }

    public function Useremailcheck(Request $request)
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

    public function UserAddSubmit(Request $request)
    {

        try {

            $login_id = Auth::user()->role;

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


            $userdetails = User::create($insert_data);

            $link = admin_url() . 'Account_Activate/' . $userdetails['active_tokan'] . '?email=' . urlencode($userdetails['email']);

            try {

                $details = [
                    "email" => $request->email,
                    "name" => $request->name,
                    "link" => $link,
                    "expire" => get_constant('RESET_PASSWORD_EXPIRE')
                ];

                dispatch((new SetpasswordJob($details))->onQueue('high'));

                Session::flash('success', 'User added successfully!');

                return redirect(admin_url('User_Management'));
            } catch (\Exception $e) {
                report($e);
                return redirect(admin_url('User_Management'));
            }
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('User_Management'));
        }
    }

    public function UserImport(Request $request)
    {

        $data = array();
        return view('admin.User_details_Import', $data);
    }

    public function UserImportSubmit(Request $request)
    {

        try {


            $file = $request->file('user_file');

            if ($file != null) {
                $uploadpath = 'public/uploads/userdata';

                $filenewname = time() . Str::random('10') . '.' . $file->getClientOriginalExtension();
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $fileMimetype = $file->getMimeType();
                $fileExt = $file->getClientOriginalExtension();

                $file->move($uploadpath, $filenewname);

                $path = $uploadpath . "/" . $filenewname;
                $user_id = auth()->user()->id;

                $insert_data = array(
                    'file_path' => $path,
                    'source_path' => $path,
                    'dest-path' => $path,
                    'file_name' => $filenewname,
                    'file_orgname' => $fileName,
                    'extract_status' => 0,
                    'upload_type' => '2',
                    'created_by' => $user_id
                );
                $insert_id = DB::table('admin_upload_log')->insertGetId($insert_data);
            }

            Session::flash('success', 'Successfully User upload !');

            return redirect(admin_url('User_Management'));
        } catch (Exception $ex) {

            Session::flash('error', 'User upload failed!');

            return redirect(admin_url('User_Management'));
        }
    }

    public function UserView(Request $request, $userid = '')
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

    public function UserEdit(Request $request)
    {
        try {

            $id = decryptId($request->userid);

            $user_details = User::where('id', $id)->first();


            $role_details = UserRole::get();

            $data = array(
                'role_details' => $role_details,
                'user_details' => $user_details,

            );

            return view('admin.User_details_edit', $data);
        } catch (Exception $error) {
            report($error->getMessage());
            dd($error);
        }
    }

    public function UserUpdate(Request $request)
    {
        try {

            $id = decryptId($request->userid);

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

            $userdetails = User::where('id', $id)->update($update_data);

            Session::flash('success', 'User updated successfully!');

            return redirect(admin_url('User_Management'));
        } catch (Exception $ex) {
            dd($ex);
            return "Error";
        }
    }

    public function UserStatus(Request $request)
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

    public function UserDelete(Request $request)
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

    public function Role_index(Request $request)
    {

        if (Auth::check()) {

            if ($request->ajax()) {

                try {



                    $data = UserRole::select([
                        'id',
                        'role_name',
                        'status',

                        'created_at',
                    ]);
                    $data = $data->orderBy('created_at', 'Desc');
                    $data = $data->get();



                    $datatables = Datatables::of($data)
                        ->addIndexColumn()
                        ->filter(function ($instance) use ($request) {
                            if ($request->has('role_name') && $request->role_name != '') {
                                $instance->collection = $instance->collection->filter(function ($row) use ($request) {
                                    return Str::contains(strtolower($row['role_name']), strtolower($request->get('role_name'))) ? true : false;
                                });
                            }
                        })
                        ->addColumn('created_date', function ($row) {

                            $d = strtotime($row->created_at);
                            $btn = date("d/m/Y", $d);

                            return $btn;
                        })
                        ->addColumn('status', function ($row) {

                            if ($row->status == 1) {
                                $btn = '<span style="color:green;">Active</span>';
                            } else if ($row->status == 0) {
                                $btn = '<span style="color:red;">In-Active</span>';
                            }

                            return $btn;
                        })
                        ->addColumn('created_by', function ($row) {

                            $btn = "Admin";

                            return $btn;
                        })
                        ->addColumn('action', function ($row) {
                            $btn = '';
                            $btn = '<a href="' . admin_url('user_role_management/' . encryptId($row->id)) . '"   class="" title="View"><i class="fa  fa-eye" style="color:#0277bd;"></i></a> ';
                            $btn .= '<a  href="' . admin_url('user_role_management/edit/' . encryptId($row->id)) . '" class=" " title="Edit"><i class="fa fa-edit" style="color:#43a047;"></i></a> ';
                            $btn .= '<a  href="javascript:void(0);"  data-id="' . encryptId($row->id) . '"  class="UserRoleDelete" title="Delete"><i class="fa fa-trash-alt" style="color:#d81821;"></i></a> ';

                            return $btn;
                        })
                        ->rawColumns(['action', 'created_date', 'created_by', 'status'])
                        ->make(true);

                    return $datatables;
                } catch (Exception $ex) {
                    dd($ex);
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            }
        }

        return view('admin.User_role_list');
    }

    public function UserRoleAdd(Request $request)
    {

        $data = [];
        return view('admin.User_role_add', $data);
    }

    public function UserRolecheck(Request $request)
    {

        if ($request->ajax()) {

            if ($request->roleid == '') {
                $user = UserRole::where('role_name', $request->role_name)->get();
            } else {
                $user = UserRole::where('role_name', $request->role_name)
                    ->where('id', '!=', decryptId($request->roleid))
                    ->get();
            }

            if ($user->count()) {
                return Response::json(array('msg' => 'true'));
            }
            return Response::json(array('msg' => 'false'));
        }
    }

    public function UserRoleAddSubmit(Request $request)
    {

        try {

            $insert_data = array(
                'role_name' => $request->role_name,
                'role_permission' => null,
                'created_by' => Auth::id(),
                'updated_by' => null,
            );

            $roledetails = UserRole::create($insert_data);
            Session::flash('success', 'User Role added successfully!');
            return redirect(admin_url('user_role_management'));
        } catch (Exception $ex) {
            report($ex);

            return redirect(admin_url('User_Management'));
        }
    }

    public function UserRoleView(Request $request, $userid = '')
    {

        $id = decryptId($request->roleid);

        try {

            if (Auth::check()) {
                $data['role_details'] = UserRole::find($id);
            }

            return view('admin.User_role_view', $data);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
        }
    }

    public function UserRoleEdit(Request $request, $userid = '')
    {


        try {
            $id = decryptId($request->roleid);

            $data['role_details'] = UserRole::find($id);

            return view('admin.User_role_edit', $data);
        } catch (Exception $error) {

            report($error->getMessage());
            dd($error->getMessage());
        }
    }

    public function UserRoleUpdate(Request $request)
    {

        $id = decryptId($request->roleid);

        $update_data = array(
            'role_name' => $request->role_name,

        );

        $userdetails = UserRole::where('id', $id)->update($update_data);

        Session::flash('message', 'Role updated successfully!');

        Session::flash('success', 'User Role Updated successfully!');
        return redirect(admin_url('user_role_management'));
    }

    public function UserRoleDelete(Request $request)
    {

        try {
            $roleid = decryptId($request->roleid);

            $role_mapped = User::where('role', $roleid)->get();

            if (count($role_mapped) > 0) {
                return response()->json(['status' => 'error', 'msg' => 'Could not delete,Current Role Mapped with users'], 406);
            }

            $update_data = array(
                'status' => 0,
                'trash' => "YES",
            );
            $where_data = array(
                'id' => $roleid
            );

            UserRole::where($where_data)->update($update_data);

            return response()->json(['status' => 'success', 'msg' => 'Role deleted successfully'], 200);
        } catch (Exception $ex) {
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }
    public function checkUserAccess($type = '')
    {

        if (in_array(Auth::user()->role, ['customer', 'user'])) {
            $permission = Permission::where('user_id', Auth::user()->id)->first();

            if ($permission == '') {
                return false;
            }

            $name = 'user_' . $type;
            if ($permission->$name == 0) {
                return false;
            }
        }

        return true;
    }

    public function test()
    {


        $first = DB::table('ard_school_insp_initial')->select('ins_unique_id')->pluck('ins_unique_id')->toArray();

        $second = DB::table('ard_school_insp_initial_main')->select('ins_unique_id')->pluck('ins_unique_id')->toArray();

        $total = array_merge($first, $second);

        $counts = array_count_values($total);
        $result = array();

        foreach ($counts as $value => $count) {

            if ($count > 1) {
                $result[] = $value;
            }
        }



        print_r($result);

        dd('test');
    }
}
