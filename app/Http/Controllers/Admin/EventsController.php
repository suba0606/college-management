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



use App\Models\Admin\EventsRole;

use App\Models\Admin\Events;
use App\Models\Admin\Department;




class EventsController extends Controller
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

                    $data = $data->where('event_type', '2');

                    if ($request->has('event_date') && $request->event_date != '') {
                        $data = $data->where('event_date', DBdateformat($request->event_date));
                    }

                    if ($request->has('event_name') && $request->event_name != '') {

                        $data = $data->where('event_name', "LIKE", "%" . $request->event_name . "%");
                    }

                    if ($request->has('department') && $request->department != '') {

                        $department = decryptId($request->department);

                      
                        if(Auth::user()->role == 1 &&  $department == 0){

                        }else{
                            $data = $data->where('department', $department);
                        }
                      

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
                            if ($row->department == 0) {
                                return "All";
                            } else {
                                $department =   getCustomValue('admin_department', 'department_name', $row->department);
                                return $department;
                            }
                        })
                        ->addColumn('event_date', function ($row) {

                            return Displaydateformat($row->event_date);
                        })
                        ->addColumn('action', function ($row) {
                            $btn = '';
                            // $btn = '<a href="' . admin_url('Events/' . encryptId($row['id'])) . '"   class="" title="View"><i class="fa  fa-eye" ></i></a> ';
                            $btn .= '<a href="' . admin_url('Events/edit/' . encryptId($row['id'])) . '" class=" " title="Edit"><i class="fa fa-edit" ></i></a> ';

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


        return view('admin.Events_details_list', $data);
    }

    public function EventsAdd(Request $request)
    {
        $id = Auth::id();

        $department = Department::get();

        $data = array(
            'Department' => $department
        );
        return view('admin.Events_details_add', $data);
    }

    public function eventsdatecheck(Request $request)
    {

        if ($request->ajax()) {

            if ($request->Eventsid == '') {
                $Events = Events::where('event_date', $request->event_date)->get();
            } else {
                $Events = Events::where('event_date', $request->event_date)
                    ->where('id', '!=', decryptId($request->event_date))
                    ->get();
            }

            if ($Events->count()) {
                return Response::json(array('msg' => 'true'));
            }
            return Response::json(array('msg' => 'false'));
        }
    }

    public function EventsAddSubmit(Request $request)
    {

        try {

            $insert_data = array(
                'event_type' => '2',
                'event_date' => DBdateformat($request->event_date),
                'event_name' => $request->event_name,
                'department' => decryptId($request->department),
                'created_by' => Auth::id(),
            );

            Events::create($insert_data);
            Session::flash('success', 'Event added successfully!');

            return redirect(admin_url('Events'));
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return redirect(admin_url('Events'));
        }
    }

    public function EventsView(Request $request)
    {

        try {
            $id = decryptId($request->Eventsid);

            if (Auth::check()) {
                $data['Events_details'] = Events::find($id);
            }
            return view('admin.Events_details_view', $data);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
        }
    }

    public function EventsEdit(Request $request)
    {
        try {

            $id = decryptId($request->id);

            $Events_details = Events::where('id', $id)->first();
            $department = Department::get();

            $data = array(

                'Events_details' => $Events_details,
                'Department' => $department

            );

            return view('admin.Events_details_edit', $data);
        } catch (Exception $error) {
            report($error->getMessage());
            dd($error);
        }
    }

    public function EventsUpdate(Request $request)
    {
        try {

            $id = decryptId($request->id);

            $update_data = array(
               
                'event_date' => DBdateformat($request->event_date),
                'event_name' => $request->event_name,
                'department' => decryptId($request->department),
                'updated_by' => Auth::id(),
            );

            Events::where('id',$id)->update($update_data);
           
            Session::flash('success', 'Event updated successfully!');

            return redirect(admin_url('Events'));
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
