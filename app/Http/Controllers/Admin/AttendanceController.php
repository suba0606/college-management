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
use Carbon;

use App\Models\Admin\Student;
use App\Models\Admin\Department;
use App\Models\Admin\AttendanceMain;
use App\Models\Admin\Attendance;




class AttendanceController extends Controller
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

                    $data = AttendanceMain::select([
                        'id',
                        'department',
                        'current_year',
                        'section',
                        'date',
                    ]);


                    if ($request->has('date') && $request->date != '') {
                        $data = $data->where('date', DBdateformat($request->date));
                    }

                    if ($request->has('department') && $request->department != '') {
                        $department = decryptId($request->department);
                        $data = $data->where('department', $department);
                    }

                    if ($request->has('current_year') && $request->current_year != '') {
                        $current_year = $request->current_year;
                        $data = $data->where('current_year', $current_year);
                    }

                    if ($request->has('section') && $request->section != '') {
                        $section = $request->section;
                        $data = $data->where('section', $section);
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
                            if ($row->department == 0) {
                                return "All";
                            } else {
                                $department =   getCustomValue('admin_department', 'department_name', $row->department);
                                return $department;
                            }
                        })
                        ->addColumn('date', function ($row) {

                            return Displaydateformat($row->date);
                        })
                        ->addColumn('sectionname', function ($row) {

                            return  $row->section;
                        })
                        ->addColumn('currentyear', function ($row) {

                            return  $row->current_year;
                        })
                        ->addColumn('action', function ($row) {
                            $btn = '';
                            $btn .= '<a href="' . admin_url('Attendance/view/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-eye" ></i></a> ';
                            $btn .= '<a href="' . admin_url('Attendance/edit/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-edit" ></i></a> ';

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


        return view('admin.Attendance_details_list', $data);
    }

    public function MyAttendance(Request $request)
    {

        $data = Attendance::select([
            'admin_student.id',
            'admin_student.date',
            'admin_student.attendance_status',
        ]);
        $data = $data->leftJoin('admin_student', 'admin_student.id', 'admin_attendance_details.student_id');
        $data = $data->where('admin_student.department', Auth::user()->department);
        $data = $data->where('admin_student.user_id', Auth::id());

        if ($request->has('month_year') && $request->month_year != '') {

            $dateString = $request->month_year;

            $date = \DateTime::createFromFormat('M-Y', $dateString);
            $carbon = Carbon::instance($date);

            $month = $carbon->format('m');
            $year =  $carbon->format('Y');

            $data = $data->whereRaw("MONTH(date) = $month");
            $data = $data->whereRaw("YEAR(date) = $year");
        }else{
          
            $month = date('M');
            $year =  date('Y');

            $data = $data->whereRaw("MONTH(date) = $month");
            $data = $data->whereRaw("YEAR(date) = $year");
        }

        $attentancelist = $data->get();

        $Department = Department::get();

        $datas = array(
            'attentancelist' => $attentancelist,
        );


        return view('admin.Attendance_details_My_list', $datas);
    }

    public function AttendanceAdd(Request $request)
    {
        $id = Auth::id();

        $department = Department::get();

        $data = array(
            'Department' => $department
        );
        return view('admin.Attendance_details_add', $data);
    }

    public function AttendancedetailsAdd(Request $request)
    {

        $date = $request->date;
        $department = decryptId($request->department);
        $year = $request->year;
        $section = $request->section;

        $where_array = array(
            'department' => $department,
            'current_year' =>  $year,
            'section' =>  $section
        );
        $student = Student::where($where_array)->get();
        $department_details = Department::get();

        $data = array(
            'date' => $date,
            'department_details' =>  $department_details,
            'department' => $department,
            'year' => $year,
            'section' => $section,
            'studentdetails' => $student
        );


        return view('admin.Attendance_details_enter', $data);
    }


    public function AttendanceAddSubmit(Request $request)
    {
        try {

            $insert_attendance = array(
                'date' => DBdateformat($request->date),
                'department' => $request->form_department,
                'current_year' => $request->form_year,
                'section' => $request->form_section,
                'created_by' => Auth::id(),
            );

            $attendance =  AttendanceMain::create($insert_attendance);
            $attendance_id =  $attendance->id;

            $where_array = array(
                'department' => $request->form_department,
                'current_year' => $request->form_year,
                'section' =>  $request->form_section,
            );

            $studentDetails = Student::where($where_array)->get();

            foreach ($studentDetails as $student) {
                $name = 'student_' . $student->id;
                if ($request->has($name)) {
                    $attendance_status = 1;
                } else {
                    $attendance_status = 0;
                }

                $attandence_array = array(
                    'attendance_id' =>  $attendance_id,
                    'department' =>  $student->department,
                    'current_year' =>  $student->current_year,
                    'section' =>  $student->section,
                    'student_id' =>  $student->id,
                    'date' =>   DBdateformat($request->date),
                    'attendance_status' =>  $attendance_status,
                    'submitted_user' =>   Auth::id(),
                    'created_by' =>   Auth::id(),

                );
                Attendance::create($attandence_array);
            }


            Session::flash('success', 'Attendance added successfully!');

            return redirect(admin_url('Attendance'));
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('Attendance'));
        }
    }

    public function AttendanceView(Request $request)
    {

        try {
            $id = decryptId($request->id);

            $attendance =  AttendanceMain::where('id', $id)->first();
            $attendancedetails =  Attendance::select('admin_attendance_details.*', 'admin_student.rollno', 'admin_student.name')
                ->join('admin_student', 'admin_student.id', 'student_id')
                ->where('attendance_id', $id)
                ->get();

            $data = array(
                'attendance' => $attendance,
                'attendancedetails' => $attendancedetails,
            );



            return view('admin.Attendance_details_view', $data);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
        }
    }

    public function AttendanceEdit(Request $request)
    {
        try {

            $id = decryptId($request->id);

            $attendance =  AttendanceMain::where('id', $id)->first();
            $attendancedetails =  Attendance::select('admin_attendance_details.*', 'admin_student.rollno', 'admin_student.name')
                ->join('admin_student', 'admin_student.id', 'student_id')
                ->where('attendance_id', $id)
                ->get();

            $data = array(
                'attendance' => $attendance,
                'attendancedetails' => $attendancedetails,
            );

            return view('admin.Attendance_details_edit', $data);
        } catch (Exception $error) {
            report($error->getMessage());
            dd($error);
        }
    }

    public function AttendanceUpdate(Request $request)
    {
        try {

            $id = decryptId($request->id);



            $attendancedetails =  Attendance::where('attendance_id', $id)->get();

            // $attendancedetails =  Attendance::where('attendance_id', $id,)->update('attendance_status' ,  0);

            foreach ($attendancedetails as $student) {

                $name = 'student_' . $student->id;
                if ($request->has($name)) {
                    $attendance_status = 1;
                } else {
                    $attendance_status = 0;
                }

                $attandence_array = array(
                    'attendance_status' =>  $attendance_status,
                    'updated_by' =>   Auth::id(),

                );
                Attendance::where('id', $student->id)->update($attandence_array);
            }



            Session::flash('success', 'Attendance updated successfully!');

            return redirect(admin_url('Attendance'));
        } catch (Exception $ex) {
            dd($ex);
            return "Error";
        }
    }

    public function Eventstatus(Request $request)
    {

        try {
            $Events_id = decryptId($request->Events_id);
            $type = $request->types;

            if ($type == 1) {
                $update_data = array(
                    'status' => 0,
                );

                $message = 'Event was In-Activated';
                $successMsg = 'Successfully Event  was In-Activated';

                $where_data = array(
                    'id' => $Events_id,
                );
                Events::where($where_data)->update($update_data);
            } else {
                $update_data = array(
                    'status' => 1,

                );
                $message = 'Event was Activated';
                $successMsg = 'Event successfully activated';

                $update_Eventsdata = array(
                    'status' => 1,
                    'is_active' => 1,
                    'active_tokan' => Str::random(60),
                );

                $where_data = array(
                    'id' => $Events_id,
                );

                Events::where($where_data)->update($update_Eventsdata);

                $practice_details = Events::where($where_data)->first();
            }

            return response()->json(['status' => 'success', 'msg' => $successMsg], 200);
        } catch (Exception $ex) {
            dd($ex);
            return response()->json(['error' => $ex, 'status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function EventsDelete(Request $request)
    {

        try {
            $Eventsid = decryptId($request->Events_id);

            $update_data = array(
                'status' => 0,
                'trash' => "YES",
            );
            $where_data = array(
                'id' => $Eventsid
            );

            Events::where($where_data)->update($update_data);

            $message = 'Your Account has been Deleted';

            try {

                $Events_details = Events::where($where_data)->first();

                if ($Events_details != null) {

                    $details = array(
                        "event_date" => $Event_details['event_date'],
                        "event_name" => $Event_details['event_name'],
                        'message' => $message,
                    );

                    dispatch((new AccountStatusJob($details))->onQueue('high'));
                }
            } catch (Exception $ex) {

                report($ex);
                return response()->json(['error' => '1', 'status' => 'error', 'msg' => 'Please try after some time'], 406);
            }



            return response()->json(['status' => 'success', 'msg' => 'Event deleted successfully'], 200);
        } catch (Exception $ex) {
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }
}
