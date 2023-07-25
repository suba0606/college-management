<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Auth;
use DB;
use Exception;
use Session;
use Str;

use App\Models\User;

class AdminController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function index()
    {

        try {
            $admin_count = User::where('role',1)->count();
             $student_count = User::where('role',2)->count();
              $driver_count = User::where('role',3)->count();
            $data = [
                'admin_count' =>  $admin_count,
                 'student_count' =>  $student_count,
                  'driver_count' =>  $driver_count,
                ];
            return view('admin.index', $data);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function profileView()
    {
        
        $id = Auth::user()->id;
        $users = User::find($id);

        
        $page_data['userDetails'] =$users;
        $page_data['company_name'] = '';

        return view('admin.user_profile', $page_data);
    }

    public function change_profile_password(Request $request)
    {

        $user = Auth::user();

        if (Auth::attempt(array('email' => $user->email, 'password' => $request->old_password))) {
            if ($request->password != null && $request->confirm_password != null) {
                if ($request->password == $request->confirm_password) {
                    $password = $request->password;
                    $user->password = Hash::make($password);

                    $user->save();

                    Session::flash('message', 'Password updated successfully!');
                }
            }
        } else {
            Session::flash('message', 'Wrong old password');
        }

        return redirect(admin_url('profile'));
    }

    public function profileUpdate(Request $request)
    {

        $user = Auth::user();

        $department = '';
        $category = '';
        $reportManager = '';

        if ($request->department) {
            $department = array_to_string(array_map("decryptId", $request->department));
        }
        if ($request->category) {
            $category = array_to_string(array_map("decryptId", $request->category));
        }
        if ($request->report_manager_l1) {
            $reportManager = array_to_string(array_map("decryptId", $request->report_manager_l1));
        }

        $user->first_name = $request->fname;
        $user->last_name = $request->lname;
        $user->name = $request->fname . " " . $request->lname;
        $user->phone = $request->phone;
        $user->ext = $request->ext;
        $user->mobile = $request->mobile;
        $user->department = $department;
        $user->category = $category;
        $user->report_manager = $reportManager;
        $user->status = decryptId($request->profile_status);
        /* $user->profile_image = $request->profile_image; */

        $file = $request->file('profile_image');
        if ($file != null) {
            $uploadpath = 'public/uploads/profile';

            $filenewname = time() . Str::random('10') . '.' . $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $fileMimetype = $file->getMimeType();
            $fileExt = $file->getClientOriginalExtension();
            $file->move($uploadpath, $filenewname);
            $user->profile_image = $filenewname;
        }

        /* if ($request->password != null && $request->confirm_password != null) {
        if ($request->password == $request->confirm_password ) {
        $password = $request->password;
        $user->password = Hash::make($password);
        }
        } */

        $user->save();
        // }

        Session::flash('message', 'Profile updated successfully!');

        return redirect(admin_url('profile'));
    }

    public function campaign_assets(Request $request)
    {
        try {
            /**Asset based on Business Type **/
            $business_type_data = DB::table('admin_campaign_business_type')

                ->select(DB::Raw('count(admin_campaign_slot_booking.child_asset) as BusinessTypeAsset'), 'admin_campaign_business_type.business_type_name')
                ->leftjoin('admin_campaign_slot_booking', 'admin_campaign_business_type.id', 'admin_campaign_slot_booking.business_type')
                ->where('admin_campaign_business_type.trash', 'NO')
                ->where('admin_campaign_business_type.status', '1')
                ->groupBy('admin_campaign_business_type.id')
                ->get();

            $campaign_asset['lable'] = [];
            $campaign_asset['asset_count'] = [];

            foreach ($business_type_data as $business_type_asset) {
                $campaign_asset['lable'][] = $business_type_asset->business_type_name;
                $campaign_asset['asset_count'][] = $business_type_asset->BusinessTypeAsset;
            }


            $data = [

                'campaign_asset' => $campaign_asset,
            ];
            return view('admin.dashboard_asset_campaign', $data);
        } catch (Exception $ex) {
            dd($ex);
            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function pending_approval(Request $request)
    {
        try {

            /**Asset based on Pending  Approval */

            $pending_approval_data = DB::table('admin_campaign_slot_booking')

                ->select(DB::Raw('count(child_asset) as BusinessTypeAsset'), 'booking_status')

                ->groupBy('booking_status')
                ->get('');
            $pending_asset['lable'] = [];
            $pending_asset['asset_count'] = [];

            foreach ($pending_approval_data as $pending_assets) {
                if ($pending_assets->booking_status == 1) {
                    $status = "Pending";
                } elseif ($pending_assets->booking_status == 3) {
                    $status = "Approved";
                } else {
                    $status = "Rejected";
                }

                $pending_asset['lable'][] = $status;

                $pending_asset['asset_count'][] = $pending_assets->BusinessTypeAsset;
            }

            $data = [

                'pending_asset' => $pending_asset,
            ];
            return view('admin.dashboard_asset_pending_approval', $data);
        } catch (Exception $ex) {
            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function asset_blocking(Request $request)
    {
        try {
            /**Asset based on Asset Blocking Details**/
            $Year = (int) date('Y');
            $qtr = array(
                "$Year-01-01/$Year-03-31",
                "$Year-04-01/$Year-06-30",
                "$Year-07-01/$Year-09-30",
                "$Year-10-01/$Year-12-31"
            );
            $qtr_month_counts = [];
            foreach ($qtr as $month_count => $months) {
                $qtr_dates = explode('/', $months);

                $qtr_start_date = $qtr_dates[0];
                $qtr_end_date = $qtr_dates[1];

                $asset_counts = DB::table('admin_campaign_slot_booking_details')
                    ->select(
                        'admin_campaign_slot_booking_details.id',
                        'admin_campaign_slot_booking_details.child_asset',
                        'admin_campaign_slot_booking_details.booked_date'
                    )
                    ->whereBetween('admin_campaign_slot_booking_details.booked_date', [$qtr_start_date, $qtr_end_date])
                    ->where('admin_campaign_slot_booking_details.booking_status', 3)
                    ->where('admin_campaign_slot_booking_details.status', 1)
                    ->count();

                $qtr_month_counts['lable'][] = $months;
                $qtr_month_counts['asset_count'][] = $asset_counts;
            }




            $data = [
                'asset_blocks' => $qtr_month_counts,
            ];
            return view('admin.dashboard_asset_blocking', $data);
        } catch (Exception $ex) {
            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function manualBookingAjax(Request $request)
    {
        try {
            if (Auth::check() && $request->ajax()) {
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
            dd($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }
    public function Manual_Asset_Campaign(Request $request)
    {
        try {
            if (Auth::check()) {
                $campaign_details = AssetCampaign::find(decryptId($request->campaignbookid));
                $campaign_box = $campaign_details->Campaginbox;
                $parent_assets = AssetParent::where('business_type', $campaign_details->business_type)->get();
                $child_assets = AssetChild::where('business_type_id', $campaign_details->business_type)->get();
                $delivery_cities = SettingDeliveryCity::whereRaw("FIND_IN_SET('$campaign_details->business_type',business_type)")->get();
                $getdc_days = SettingGeneral::pluck('dc_days')->first();

                $start_date = date('Y-m-d', strtotime($campaign_box->start_date));

                $dc_enable_date = date('Y-m-d', strtotime($start_date . ' - ' . $getdc_days . ' days'));
                $data = array(
                    'campaignbookid' => $request->campaignbookid,
                    'campaign_details' => $campaign_details,
                    'parent_assets' => $parent_assets,
                    'child_assets' => $child_assets,
                    'delivery_cities' => $delivery_cities,
                    'dc_enable_date' => $dc_enable_date,
                    'campaign_start_date' => $campaign_box->start_date,
                    'campaign_end_date' => $campaign_box->end_date,

                );
                return view('admin.Campaign_Book_Asset_manual', $data);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
            dd($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function manufacture_asset(Request $request)
    {
        try {
            /**Asset based on Manufacture Type --Starts--*/

            $manufacture_data = DB::table('admin_manufacturer_master')

                ->select((DB::raw('COUNT(admin_campaign_slot_booking.child_asset) AS ChildCount')), 'admin_manufacturer_master.manufacturer_name', 'admin_manufacturer_master.id', 'admin_campaign_slot_booking.campaign_id', 'admin_campaign_slot_booking.campaign_box_id')

                ->where('admin_manufacturer_master.trash', 'NO')

                ->leftjoin('admin_campaign_slot', 'admin_campaign_slot.manufacture_name', 'admin_manufacturer_master.id')

                ->leftjoin('admin_campaign_slot_booking', 'admin_campaign_slot_booking.campaign_id', 'admin_campaign_slot.id')

                ->groupBy('admin_manufacturer_master.id')

                ->get();

            $manufactures_asset['lable'] = [];
            $manufactures_asset['asset_count'] = [];

            foreach ($manufacture_data as $manufacture_asset) {
                $manufactures_asset['lable'][] = $manufacture_asset->manufacturer_name;
                $manufactures_asset['asset_count'][] = $manufacture_asset->ChildCount;
            }

            $data = [
                'manufactures_asset' => $manufactures_asset,
            ];

            return view('admin.dashboard_manufacture_asset', $data);
        } catch (Exception $ex) {
            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }
}
