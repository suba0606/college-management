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
use App\Models\Admin\Student;
use App\Models\Admin\Department;
use App\Models\Admin\MasterJobTitle;

use App\Jobs\SetpasswordJob;
use App\Jobs\AccountStatusJob;



class StudentController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function index(Request $request)
    {

        if (Auth::user()->role == ROLE_STUDENT){

            return redirect(admin_url('dashboard'));

        }


        if ($request->ajax()) {
            if (Auth::check()) {
                try {

                  

                    $data = Student::select([
                        'id',
                        'name',
                        'gender',
                        'dob',
                        'mobile',
                        'rollno',
                        'department',

                    ]);

                    if ($request->has('name') && $request->name != '') {
                        $data = $data->where('name', "LIKE", "%" . $request->name . "%");
                    }

                    if ($request->has('department') && $request->department != '') {

                        $data = $data->where('department',  decryptId($request->department));
                    }

                    if ($request->has('rollno') && $request->rollno != '') {
                        $data = $data->where('rollno', "LIKE", "%" . $request->rollno . "%");
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

                            $dept_name =   getCustomValue('admin_department', 'department_name', $row->department);
                            return $dept_name;
                        })


                        ->addColumn('action', function ($row) {
                            $btn = '';
                            $btn = '<a href="' . admin_url('StudentManagement/' . encryptId($row['id'])) . '"   class="" title="View"><i class="fa  fa-eye" ></i></a> ';
                            $btn .= '<a href="' . admin_url('StudentManagement/edit/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-edit" ></i></a> ';
                            $btn .= '<a href="' . admin_url('Student_Certificate/' . encryptId($row['id'])) . '" class=" " title="Upload Certificate"><i class="fa fa-file" ></i></a> ';

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


        return view('admin.Student_details_list', $data);
    }

    public function StudentAdd(Request $request)
    {

        $Department = Department::get();


        $data = array(
            'Department' => $Department
        );

        return view('admin.Student_details_add', $data);
    }

    public function Studentemailcheck(Request $request)
    {

        if ($request->ajax()) {

            if ($request->userid == '') {
                $user = Student::where('email', $request->email)->get();
            } else {
                $user = Student::where('email', $request->email)
                    ->where('id', '!=', decryptId($request->userid))
                    ->get();
            }

            if ($user->count()) {
                return Response::json(array('msg' => 'true'));
            }
            return Response::json(array('msg' => 'false'));
        }
    }

    public function StudentAddSubmit(Request $request)
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
                'role' => 3,
                'password' => Hash::make($password),
                'active_tokan' => Str::random(60),
                'status' =>  1,
                'trash' => 'NO',
                'created_by' => Auth::id(),
                'created_date' => date('Y-m-d'),
            );

            $user =  User::insertGetId($insert_data);


            $Student_insert = array(

                'name' => $request->name,
                'gender' => $request->gender,
                'dob' => DBdateformat($request->dob),
                'dob' => DBdateformat($request->doj),
                'rollno' => $request->student_id,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'department' => decryptId($request->department),
                'joining_year' => date("Y"),
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'section' => $request->section,
                'country' => $request->country,
                'sslc_passing_year' => $request->sslc_passing_year,
                'sslc_mark' => $request->sslc_mark,
                'sslc_certificate' => '',
                'hsc_passing_year' => $request->hsc_passing_year,
                'hsc_mark' => $request->hsc_mark,
                'hsc_certificate' => '',
                'current_year' => $request->current_year,
                'created_by' => Auth::id(),
                'user_id' => $user,

            );


            Student::create($Student_insert);



            Session::flash('success', 'Student added successfully!');

            return redirect(admin_url('StudentManagement'));
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('StudentManagement'));
        }
    }

    public function StudentView(Request $request)
    {

        try {

            $id = decryptId($request->userid);

            $Student_details = Student::where('id', $id)->first();
            $Department = Department::get();

            $data = array(
                'Department' => $Department,
                'Student' => $Student_details,

            );

         

            return view('admin.Student_details_view', $data);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
        }
    }

    public function StudentEdit(Request $request)
    {
        try {

            $id = decryptId($request->userid);

            $Student_details = Student::where('id', $id)->first();
            $Department = Department::get();

            $data = array(
                'Department' => $Department,
                'Student' => $Student_details,

            );

            return view('admin.Student_details_edit', $data);
        } catch (Exception $error) {
            report($error->getMessage());
            dd($error);
        }
    }

    public function StudentUpdate(Request $request)
    {
        try {

            $id = decryptId($request->userid);
            $Student = Student::find($id);

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

            User::where('id', $Student->user_id)->update($user_update_data);


            $Student_update_date = array(

                'name' => $request->name,
                'gender' => $request->gender,
                'dob' => DBdateformat($request->dob),
                'rollno' => $request->student_id,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'department' => decryptId($request->department),
                'joining_year' => date("Y"),
                'section' => $request->section,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'country' => $request->country,
                'sslc_passing_year' => $request->sslc_passing_year,
                'sslc_mark' => $request->sslc_mark,
                'sslc_certificate' => '',
                'hsc_passing_year' => $request->hsc_passing_year,
                'hsc_mark' => $request->hsc_mark,
                'hsc_certificate' => '',
                'placement_willing' => $request->placement_willing,
                'current_year' => $request->current_year,
                'updated_by' => Auth::id(),


            );


            Student::where('id', $id)->update($Student_update_date);


            Session::flash('success', 'Student Updated successfully!');

            return redirect(admin_url('StudentManagement'));
        } catch (Exception $ex) {
            dd($ex);
            return "Error";
        }
    }

    public function StudentStatus(Request $request)
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

    public function StudentDelete(Request $request)
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

    public function Student_Certificate(Request $request)
    {

        $student_id = decryptId($request->id);

        $certificate =  DB::table('admin_student_certificate')->where('student_id', $student_id)->get()->keyBy('certificate_type');

        $data = array(
            'student_id' => $student_id,
            'certificate' => $certificate->toArray()
        );


        return view('admin.Student_certificate_add', $data);
    }

    public function Student_Certificate_Submit(Request $request)
    {


        $student_id = decryptId($request->student_id);

        $sslc = $request->file('sslc');
        if ($sslc != null) {
            $uploadpath = 'public/uploads/certificate';

            $sslcnewname = time() . Str::random('10') . '.' . $sslc->getClientOriginalExtension();
            $sslcName = $sslc->getClientOriginalName();
            $sslcSize = $sslc->getSize();
            $sslcMimetype = $sslc->getMimeType();
            $sslcExt = $sslc->getClientOriginalExtension();
            $sslc->move($uploadpath, $sslcnewname);


            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sslc')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sslc',
                    'file_path' =>  $uploadpath . "/" . $sslcnewname,
                    'org_name' => $sslcName,
                    'name' => $sslcnewname,
                    'file_type' =>  $sslcExt,
                    'file_size' =>  $sslcSize,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sslc',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sslcnewname,
                    'org_name' => $sslcName,
                    'name' => $sslcnewname,
                    'file_type' =>  $sslcExt,
                    'file_size' =>  $sslcSize,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }

        $hsc = $request->file('hsc');
        if ($hsc != null) {
            $uploadpath = 'public/uploads/certificate';

            $hscnewname = time() . Str::random('10') . '.' . $hsc->getClientOriginalExtension();
            $hscName = $hsc->getClientOriginalName();
            $hscSize = $hsc->getSize();
            $hscMimetype = $hsc->getMimeType();
            $hscExt = $hsc->getClientOriginalExtension();
            $hsc->move($uploadpath, $hscnewname);



            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'hsc')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'hsc',
                    'file_path' =>  $uploadpath . "/" . $hscnewname,
                    'org_name' => $hscName,
                    'name' => $hscnewname,
                    'file_type' =>  $hscExt,
                    'file_size' =>  $hscSize,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'hsc',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $hscnewname,
                    'org_name' => $hscName,
                    'name' => $hscnewname,
                    'file_type' =>  $hscExt,
                    'file_size' =>  $hscSize,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }


        $sem1 = $request->file('sem1');
        if ($sem1 != null) {
            $uploadpath = 'public/uploads/certificate';

            $sem1newname = time() . Str::random('10') . '.' . $sem1->getClientOriginalExtension();
            $sem1Name = $sem1->getClientOriginalName();
            $sem1Size = $sem1->getSize();
            $sem1Mimetype = $sem1->getMimeType();
            $sem1Ext = $sem1->getClientOriginalExtension();
            $sem1->move($uploadpath, $sem1newname);
            $update_data['profile_image'] = $sem1newname;

            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sem1')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem1',
                    'file_path' =>  $uploadpath . "/" . $sem1newname,
                    'org_name' => $sem1Name,
                    'name' => $sem1newname,
                    'file_type' =>  $sem1Ext,
                    'file_size' =>  $sem1Size,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem1',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sem1newname,
                    'org_name' => $sem1Name,
                    'name' => $sem1newname,
                    'file_type' =>  $sem1Ext,
                    'file_size' =>  $sem1Size,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }

        $sem2 = $request->file('sem2');
        if ($sem2 != null) {
            $uploadpath = 'public/uploads/certificate';

            $sem2newname = time() . Str::random('10') . '.' . $sem2->getClientOriginalExtension();
            $sem2Name = $sem2->getClientOriginalName();
            $sem2Size = $sem2->getSize();
            $sem2Mimetype = $sem2->getMimeType();
            $sem2Ext = $sem2->getClientOriginalExtension();
            $sem2->move($uploadpath, $sem2newname);
            $update_data['profile_image'] = $sem2newname;


            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sem2')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem2',
                    'file_path' =>  $uploadpath . "/" . $sem2newname,
                    'org_name' => $sem2Name,
                    'name' => $sem2newname,
                    'file_type' =>  $sem2Ext,
                    'file_size' =>  $sem2Size,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem2',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sem2newname,
                    'org_name' => $sem2Name,
                    'name' => $sem2newname,
                    'file_type' =>  $sem2Ext,
                    'file_size' =>  $sem2Size,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }

        $sem3 = $request->file('sem3');
        if ($sem3 != null) {
            $uploadpath = 'public/uploads/certificate';

            $sem3newname = time() . Str::random('10') . '.' . $sem3->getClientOriginalExtension();
            $sem3Name = $sem3->getClientOriginalName();
            $sem3Size = $sem3->getSize();
            $sem3Mimetype = $sem3->getMimeType();
            $sem3Ext = $sem3->getClientOriginalExtension();
            $sem3->move($uploadpath, $sem3newname);



            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sem3')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem3',
                    'file_path' =>  $uploadpath . "/" . $sem3newname,
                    'org_name' => $sem3Name,
                    'name' => $sem3newname,
                    'file_type' =>  $sem3Ext,
                    'file_size' =>  $sem3Size,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem3',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sem3newname,
                    'org_name' => $sem3Name,
                    'name' => $sem3newname,
                    'file_type' =>  $sem3Ext,
                    'file_size' =>  $sem3Size,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }


        $sem4 = $request->file('sem4');
        if ($sem4 != null) {
            $uploadpath = 'public/uploads/certificate';

            $sem4newname = time() . Str::random('10') . '.' . $sem4->getClientOriginalExtension();
            $sem4Name = $sem4->getClientOriginalName();
            $sem4Size = $sem4->getSize();
            $sem4Mimetype = $sem4->getMimeType();
            $sem4Ext = $sem4->getClientOriginalExtension();
            $sem4->move($uploadpath, $sem4newname);
            $update_data['profile_image'] = $sem4newname;


            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sem4')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem4',
                    'file_path' =>  $uploadpath . "/" . $sem4newname,
                    'org_name' => $sem4Name,
                    'name' => $sem4newname,
                    'file_type' =>  $sem4Ext,
                    'file_size' =>  $sem4Size,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem4',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sem4newname,
                    'org_name' => $sem4Name,
                    'name' => $sem4newname,
                    'file_type' =>  $sem4Ext,
                    'file_size' =>  $sem4Size,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }


        $sem5 = $request->file('sem5');
        if ($sem5 != null) {
            $uploadpath = 'public/uploads/certificate';

            $sem5newname = time() . Str::random('10') . '.' . $sem5->getClientOriginalExtension();
            $sem5Name = $sem5->getClientOriginalName();
            $sem5Size = $sem5->getSize();
            $sem5Mimetype = $sem5->getMimeType();
            $sem5Ext = $sem5->getClientOriginalExtension();
            $sem5->move($uploadpath, $sem5newname);
            $update_data['profile_image'] = $sem5newname;


            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sem5')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem5',
                    'file_path' =>  $uploadpath . "/" . $sem5newname,
                    'org_name' => $sem5Name,
                    'name' => $sem5newname,
                    'file_type' =>  $sem5Ext,
                    'file_size' =>  $sem5Size,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem5',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sem5newname,
                    'org_name' => $sem4Name,
                    'name' => $sem5newname,
                    'file_type' =>  $sem5Ext,
                    'file_size' =>  $sem5Size,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }

        $sem6 = $request->file('sem6');
        if ($sem6 != null) {
            $uploadpath = 'public/uploads/certificate';

            $sem6newname = time() . Str::random('10') . '.' . $sem6->getClientOriginalExtension();
            $sem6Name = $sem6->getClientOriginalName();
            $sem6Size = $sem6->getSize();
            $sem6Mimetype = $sem6->getMimeType();
            $sem6Ext = $sem6->getClientOriginalExtension();
            $sem6->move($uploadpath, $sem6newname);
           


            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sem6')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem6',
                    'file_path' =>  $uploadpath . "/" . $sem6newname,
                    'org_name' => $sem6Name,
                    'name' => $sem6newname,
                    'file_type' =>  $sem6Ext,
                    'file_size' =>  $sem6Size,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem6',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sem6newname,
                    'org_name' => $sem6Name,
                    'name' => $sem6newname,
                    'file_type' =>  $sem6Ext,
                    'file_size' =>  $sem6Size,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }

        $sem7 = $request->file('sem7');
        if ($sem7 != null) {
            $uploadpath = 'public/uploads/certificate';

            $sem7newname = time() . Str::random('10') . '.' . $sem7->getClientOriginalExtension();
            $sem7Name = $sem7->getClientOriginalName();
            $sem7Size = $sem7->getSize();
            $sem7Mimetype = $sem7->getMimeType();
            $sem7Ext = $sem7->getClientOriginalExtension();
            $sem7->move($uploadpath, $sem7newname);
          


            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sem7')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem7',
                    'file_path' =>  $uploadpath . "/" . $sem7newname,
                    'org_name' => $sem7Name,
                    'name' => $sem7newname,
                    'file_type' =>  $sem7Ext,
                    'file_size' =>  $sem7Size,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem7',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sem7newname,
                    'org_name' => $sem7Name,
                    'name' => $sem7newname,
                    'file_type' =>  $sem7Ext,
                    'file_size' =>  $sem7Size,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }

        $sem8 = $request->file('sem8');
        if ($sem8 != null) {
            $uploadpath = 'public/uploads/certificate';

            $sem8newname = time() . Str::random('10') . '.' . $sem8->getClientOriginalExtension();
            $sem8Name = $sem8->getClientOriginalName();
            $sem8Size = $sem8->getSize();
            $sem8Mimetype = $sem8->getMimeType();
            $sem8Ext = $sem8->getClientOriginalExtension();
            $sem8->move($uploadpath, $sem8newname);
           


            $exist_check =  DB::table('admin_student_certificate')
                ->where('student_id', $student_id)
                ->where('certificate_type', 'sem8')
                ->first();

            if ($exist_check  == null  || $exist_check  == '') {

                $insert_array = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem8',
                    'file_path' =>  $uploadpath . "/" . $sem8newname,
                    'org_name' => $sem8Name,
                    'name' => $sem8newname,
                    'file_type' =>  $sem8Ext,
                    'file_size' =>  $sem8Size,
                    'created_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->insert($insert_array);
            } else {

                $update_where  = array(
                    'student_id' => $student_id,
                    'certificate_type' => 'sem8',

                );

                $update_array  = array(
                    'file_path' =>  $uploadpath . "/" . $sem8newname,
                    'org_name' => $sem8Name,
                    'name' => $sem8newname,
                    'file_type' =>  $sem8Ext,
                    'file_size' =>  $sem8Size,
                    'update_by' => Auth::id()
                );

                DB::table('admin_student_certificate')->where($update_where)->update($update_array);
            }
        }


        Session::flash('success', 'Student Certificate Updated successfully!');

        return redirect(admin_url('StudentManagement'));
    }

}
