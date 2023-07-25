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
use App\Models\Admin\Student;
use App\Models\Admin\Subject;





class PlacementController extends Controller
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

                    $data = DB::table('admin_placement_company')->select([
                        'id',
                        'company_name',
                        'interview_date',
                        'total_attend',
                        'total_select',
                    ])
                        ->where('status', 1)
                        ->where('trash', 'NO');


                    if ($request->has('company') && $request->company != '') {
                        $data = $data->where('company_name', "LIKE", "%" . $request->company . "%");
                    }


                    $data_count = $data = $data->orderBy('created_at', 'Desc');
                    $total_records = $data_count->count();


                    if ($request->length >= 0) {
                        $data = $data->offset($request->start)->limit($request->length);
                    }
                    $data = $data->get();

                    $datatables = Datatables::of($data)
                        ->addIndexColumn()
                        ->addColumn('date', function ($row) {
                            return Displaydateformat($row->interview_date);
                        })
                        ->addColumn('action', function ($row) {
                            $btn = '';

                            $btn .= '<a href="' . admin_url('Company_student_view/' . encryptId($row->id)) . '" class=" " title="View"><i class="fa fa-eye" ></i></a> ';

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



        return view('admin.Company_details_list', $data);
    }

    public function Add_Company(Request $request)
    {
        $id = Auth::id();

        $student = Student::where('placement_willing', 1)
            ->get();

        $data = array(
            'student_list' => $student
        );

        return view('admin.company_details_add', $data);
    }

    public function companyAddSubmit(Request $request)
    {

        try {


            $insert_data = array(
                'company_name' => $request->company,
                'interview_date' => DBdateformat($request->company),
                'total_attend' => $request->total_student,
                'total_select' => $request->selected_student,

            );

            $insert_id =    DB::table('admin_placement_company')->insertGetId($insert_data);



            $student_list =  arrayDecrypt($request->student);

            foreach ($student_list as $student) {

                $stud =   Student::find($student);

                $insert_data1 = array(
                    'company_id' => $insert_id,
                    'company_name' => $request->company,
                    'depart_id' => $stud->department,
                    'student_id' => $stud->id,
                    'student_name' => $stud->name,
                );

                $insert_id =    DB::table('admin_placement_company_student')->insertGetId($insert_data1);

                Student::where('id', $stud->id)->update(['placement_status' => 1]);
            }



            Session::flash('success', 'Company added successfully!');

            return redirect(admin_url('Company_List'));
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('Company_List'));
        }
    }


    public function Placement_Pending_List(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::check()) {
                try {

                    DB::enableQueryLog();

                    $data = Student::select([
                        'id',
                        'name',
                        'gender',
                        'dob',
                        'mobile',
                        'rollno',
                        'department',

                    ]);

                    $data = $data->where('placement_willing',  1);
                    $data = $data->where('placement_status',  0);


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


        return view('admin.Company_Pending_Student_details_list', $data);
    }


    public function Company_student_view(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::check()) {
                try {

                    $data = DB::table('admin_placement_company_student')->select([
                        'admin_placement_company_student.id',
                        'company_id',
                        'company_name',
                        'depart_id',
                        'student_id',
                        'student_name',
                        'admin_department.department_name',
                    ])
                        ->leftJoin('admin_department', 'admin_department.id', 'admin_placement_company_student.depart_id')
                        ->where('admin_placement_company_student.status', 1)
                        ->where('admin_placement_company_student.trash', 'NO');

                    $data = $data->where('company_id',  decryptId($request->companyid));

                    if ($request->has('department') && $request->department != '') {
                        $data = $data->where('depart_id',  decryptId($request->department));
                    }
                    if ($request->has('student') && $request->student != '') {
                        $data = $data->where('student_name', "LIKE", "%" . $request->student . "%");
                    }


                    $data_count = $data = $data->orderBy('admin_placement_company_student.created_at', 'Desc');
                    $total_records = $data_count->count();


                    if ($request->length >= 0) {
                        $data = $data->offset($request->start)->limit($request->length);
                    }
                    $data = $data->get();

                    $datatables = Datatables::of($data)
                        ->addIndexColumn()
                        
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
            'companyid' => $request->companyid,
        );



        return view('admin.Company_studennt_details_list', $data);
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
