<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Auth;
use Session;
use Str;
use DB;
use Response;
use DataTables;
use Log;
use SimpleXLSX;

use App\Jobs\DeliverycityImportJob;
use App\Jobs\BiddingkeywordImportJob;

use App\Models\Admin\SettingDeliveryCity;
use App\Models\Admin\SettingGeneral;
use App\Models\Admin\CampaignKeywordType;
use App\Models\Admin\CategoryBLC;
use App\Models\Admin\CategorySLC;
use App\Models\Admin\CategoryTLC;

use App\Models\Admin\CampaignDeliveryCity;
use App\Exports\DeliveryCityExport;
use App\Exports\BiddingKeywordExport;
use App\Models\Admin\CampaignBusinessType;
use App\Models\Admin\SettingLocation;
use App\Models\Admin\SettingDc;
use App\Models\Admin\SettingTier;
use App\Models\Admin\UserRole;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Settings;

class SettingsController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Delivery City Master
     */

    public function Delivery_City(Request $request)
    {

        try {

            if (Auth::check()) {

                if ($request->ajax()) {
                    try {


                        $data = SettingDeliveryCity::select([
                            'admin_setting_delivery_city.*',
                            // 'dcs.dc_name',
                            'locations.location_name',
                            'tiers.tier_name',
                            'users.name as createdby'

                        ]);
                        if ($request->has('city_name') && $request->city_name != '') {
                            $data = $data->where('city_name', "LIKE", "%" . $request->city_name . "%");
                        }
                        
                        $data = $data->join('admin_dc_master_location as locations', 'admin_setting_delivery_city.location',  'locations.id')
                            // ->join('admin_dc_master_dc as dcs', 'admin_setting_delivery_city.dc', 'dcs.id')
                            ->join('admin_dc_master_tier as tiers', 'admin_setting_delivery_city.tier',  'tiers.id')
                            ->join('users as users', 'admin_setting_delivery_city.created_by', 'users.id');

                        $data = $data->orderBy('admin_setting_delivery_city.id', 'Desc');
                        $data = $data->get();
                        
                        $datatables = Datatables::of($data)
                            ->addIndexColumn()

                            ->addColumn('created_date', function ($row) {

                                $d = strtotime($row->created_at);
                                $btn = date("d/m/Y", $d);

                                return $btn;
                            })
                            ->addColumn('business_type', function ($row) {
                                return getMultipleValue('admin_campaign_business_type', $row->business_type, 'id', 'business_type_name');
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

                                $btn = $row->createdby;

                                return $btn;
                            })
                            ->addColumn('action', function ($row) {
                                $btn = '';
                                $btn = '<a href="' . admin_url('Delivery_City_View/' . encryptId($row->id)) . '"   class="" title="View"><i class="fa  fa-eye" style="color:#0277bd;"></i></a> ';
                                // $btn .= '<a  href="' . admin_url('Delivery_City_Edit/' . encryptId($row->id)) . '" class=" " title="Edit"><i class="fa fa-edit" style="color:#43a047;"></i></a> ';
                                // $btn .= '<a href="javascript:void(0);"  data-id="' . encryptId($row->id) . '"  class="DeleteRecord" title="Delete"><i class="fa fa-trash-alt" style="color:#d81821;"></i></a> ';

                                return $btn;
                            })
                            ->rawColumns(['action', 'created_date', 'created_by', 'status'])
                            ->make(true);

                        return $datatables;
                    } catch (Exception $ex) {

                        report($ex);
                        dd($ex);
                        return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                    }
                }
            }
            $checkData = SettingDeliveryCity::count();
            $count_array = ['count' => $checkData];;

            return view('admin.Settings_Delivery_City_list', $count_array);
        } catch (Exception $ex) {
            report($ex);
            dd($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Delivery_City_Upload(Request $request)
    {
        try {
            if (Auth::check()) {

                $data = [];
                return view('admin.Campaign_Delivery_City_Upload', $data);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);

            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Delivery_City_UploadSubmit(Request $request)
    {

        try {

            $file = $request->file('product_file');

            if ($file != null) {
                $uploadpath = 'public/uploads/productdata';

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
                    'upload_type' => '5',
                    'created_by' => $user_id
                );
                $insert_id = DB::table('admin_upload_log')->insertGetId($insert_data);

                $details = [
                    "user_id" => $user_id,
                    "log_id" => $insert_id,
                    "path" => $path,

                ];
                dispatch((new DeliverycityImportJob($details))->onQueue('default'));

                //$this->Keyword_upload_Add($user_id, $insert_id, $path);

                //  \Excel::import(new KeywordsImport($user_id, $insert_id), $path);


            }
            $insert_data['log_id'] = $insert_id;
            $insert_data['uploded_by'] = Auth::user();
            Log::channel('deliveryCity-info')->info("Delivery City Successfully Uploaded", $insert_data);

            Session::flash('success', 'Successfully Delivery City upload !');
            return redirect(admin_url('Delivery_City'));
        } catch (Exception $ex) {
            dd($ex);
            Log::channel('deliveryCity-info')->error($ex);

            Session::flash('error', 'DeliveryCity upload failed!');
            return redirect(admin_url('Delivery_City'));
        }
    }


    public function Download_DeliveryCity(Request $request)
    {

        $data = SettingDeliveryCity::withoutGlobalScopes()
            // ->join('admin_category_tlc as tlc', 'admin_setting_delivery_city.tlc_id', '=', 'tlc.id')
            // ->join('admin_category_slc as slc', 'admin_setting_delivery_city.slc_id', '=', 'slc.id')
            // ->join('admin_category_blc as blc', 'admin_setting_delivery_city.blc_id', '=', 'blc.id')
            // ->join('admin_category_brand as brand', 'admin_setting_delivery_city.brand_id', '=', 'brand.id')
            // ->join('admin_category_department as dept', 'admin_setting_delivery_city.department_id', '=', 'dept.id')
            ->select(
                'admin_setting_delivery_city.*',
                'city_name',
                'dc',
                'tier',
                'location',
                'bb_daily',
            );

        $data = $data->where('admin_setting_delivery_city.trash', 'NO');

        // if ($request->tlc_id != null) {
        //     $data = $data->where('tlc_id', decryptId($request->tlc_id));
        // }
        // if ($request->slc_id != null) {
        //     $data = $data->where('slc_id', decryptId($request->slc_id));
        // }
        // if ($request->blc_id != null) {
        //     $data = $data->where('blc_id', decryptId($request->blc_id));
        // }
        // if ($request->brand_id != null) {
        //     $data = $data->where('brand_id', decryptId($request->brand_id));
        // }
        // if ($request->department_id != null) {
        //     $data = $data->where('department_id', decryptId($request->department_id));
        // }
        if ($request->city_name != null) {
            $data = $data->where('city_name', 'like', '%' . $request->city_name . '%');
        }

        // if ($request->bbcode != null) {
        //     $data = $data->where('bb_code', 'like', '%' . $request->bbcode . '%');
        // }

        // if ($request->check_ids != null) {
        //     $check_ids = string_to_array($request->check_ids, ',');
        //     $data = $data->whereIn('admin_setting_delivery_city.id', $check_ids);
        // }

        if ($request->dates != null) {
            $dates = explode('-', $request->get('dates'));

            $fromdate = date("Y-m-d", strtotime($dates['0']));
            $todate = date("Y-m-d", strtotime($dates['1']));

            $data = $data->whereDate('admin_setting_delivery_city.created_at', '>=', $fromdate);
            $data = $data->whereDate('admin_setting_delivery_city.created_at', '<=', $todate);
        }


        $data = $data->get();

        return Excel::download(new DeliveryCityExport($data), 'DeliveryCity_Report.xlsx');
    }

    public function Delivery_City_View(Request $request)
    {

        try {

            try {
                if (Auth::check()) {

                    $data = SettingDeliveryCity::withoutGlobalScopes()
                        ->join('admin_dc_master_dc as dcs', 'admin_setting_delivery_city.dc', 'dcs.id')
                        ->join('admin_dc_master_location as locations', 'admin_setting_delivery_city.location',  'locations.id')
                        ->join('admin_dc_master_tier as tiers', 'admin_setting_delivery_city.tier',  'tiers.id')

                        ->select(
                            'admin_setting_delivery_city.*',
                            'dcs.dc_name',
                            'locations.location_name',
                            'tiers.tier_name',

                        );

                    $id = decryptId($request->id);

                    $where_data = array(
                        'id' => $id
                    );

                    $Delivery_City = SettingDeliveryCity::where($where_data)->first();


                    $data = array(
                        'Delivery_City' => $Delivery_City,
                    );

                    return view('admin.Settings_Delivery_City_view', $data);
                } else {
                    report('Invalid User');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } catch (Exception $ex) {
                dd($ex);
                report($ex);
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
        }
    }

    public function Delivery_City_Add(Request $request)
    {

        try {
            if (Auth::check()) {


                $dc = SettingDc::get();
                $location = SettingLocation::get();
                $tier = SettingTier::get();
                $business_type = CampaignBusinessType::get();


                $data = array(
                    'dc' => $dc,
                    'location' => $location,
                    'tier' => $tier,
                    'business_type' => $business_type,
                );

                return view('admin.Settings_Delivery_City_add', $data);
            } else {
                report('Invalid User');

                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);

            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Delivery_City_AddSubmit(Request $request)
    {

        try {

            if (Auth::check()) {

                $business_type = array_to_string(array_map("decryptId", $request->business_type));

                $data = array(
                    'city_name' => $request->city_name,
                    // 'dc' => decryptId($request->dc),
                    'tier' => decryptId($request->tier),
                    'location' => decryptId($request->location),
                    // 'bb_daily' => $request->bb_daily,
                    'business_type' => $business_type,
                    'created_by' => Auth::user()->id,
                    'created_date' => date('Y-m-d'),
                    'updated_by' => null,
                );
                $delivert_city = SettingDeliveryCity::create($data);

                Log::channel('deliveryCity-info')->info("Delivery City Successfully Added", $delivert_city->toArray());


                Session::flash('success', 'Delivery City added successfully!');
                return redirect(admin_url('Delivery_City'));
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
            Log::channel('deliveryCity-info')->error($ex);
            report($ex);


            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Delivery_City_Edit(Request $request)
    {

        try {
            if (Auth::check()) {

                $id = decryptId($request->id);
                $dc = SettingDc::get();
                $location = SettingLocation::get();
                $tier = SettingTier::get();
                $business_type = CampaignBusinessType::get();

                $Delivery_City = SettingDeliveryCity::where('id', decryptId($request->id))->first();


                $data = array(
                    'dcs' => $dc,
                    'locations' => $location,
                    'tiers' => $tier,
                    'business_type' => $business_type,
                    'Delivery_City' => $Delivery_City
                );




                return view('admin.Settings_Delivery_City_edit', $data);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Delivery_City_EditSubmit(Request $request)
    {
        // $deliverycity_id = decryptId($request->deliverycity_id);

        try {
            if (Auth::check()) {



                $business_type = array_to_string(array_map("decryptId", $request->business_type));

                $id = decryptId($request->id);
                $data = array(
                    'city_name' => $request->city_name,
                    // 'dc' => decryptId($request->dc),
                    'tier' => decryptId($request->tier),
                    'location' => decryptId($request->location),
                    // 'bb_daily' => $request->bb_daily,
                    'business_type' => $business_type,
                    'updated_by' => Auth::user()->id,


                );
                $delivert_city =  SettingDeliveryCity::where('id', $id)->update($data);

                $data['deliveryCity_id'] = $id;
                $data['updated_by'] = Auth::user();
                if ($delivert_city) {
                    Log::channel('deliveryCity-info')->info("Delivery City Successfully Updated", $data);
                } else {
                    Log::channel('deliveryCity-info')->info("Delivery City Updated Failed", $data);
                }
                Session::flash('success', 'Delivery City Updated successfully!');

                return redirect(admin_url('Delivery_City'));
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            Log::channel('deliveryCity-info')->error($ex);
            report($ex);

            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Delivery_City_CheckExist(Request $request)
    {

        try {

            try {
                if (Auth::check()) {

                    if ($request->ajax()) {

                        if ($request->id == '') {
                            $user = SettingDeliveryCity::where('city_name', $request->city_name)->get();
                        } else {
                            $user = SettingDeliveryCity::where('city_name', $request->city_name)
                                ->where('id', '!=', decryptId($request->id))
                                ->get();
                        }

                        if ($user->count()) {
                            return Response::json(array('msg' => 'true'));
                        }
                        return Response::json(array('msg' => 'false'));
                    }
                } else {
                    report('Invalid User');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } catch (Exception $ex) {

                report($ex);

                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
        }
    }


    public function Delivery_City_Delete(Request $request)
    {

        try {

            try {
                if (Auth::check()) {


                    if ($request->ajax()) {

                        $id = decryptId($request->id);

                        $update_data = array(
                            'status' => 0,
                            'trash' => "YES",
                            'updated_by' => Auth::id()
                        );
                        $where_data = array(
                            'id' => $id
                        );

                        SettingDeliveryCity::where($where_data)->update($update_data);

                        $update_data['deliveryCity_id'] = $id;
                        $update_data['deleted_by'] = Auth::user();
                        Log::channel('deliveryCity-info')->info("Delivery City Successfully Deleted", $update_data);

                        return response()->json(['status' => 'success', 'msg' => 'Delivery City deleted successfully'], 200);
                    } else {
                        report('Invalid Request');
                        return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                    }
                } else {
                    report('Invalid User');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } catch (Exception $ex) {

                Log::channel('deliveryCity-info')->error($ex);
                report($ex);
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
            report('Invalid Request');
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Delivery_City_Download(Request $request)
    {

        try {
            if (Auth::check()) {

                $product_id = decryptId($request->product_id);

                $update_data = array(
                    'status' => 0,
                    'trash' => "YES",
                    'updated_by' => Auth::id()
                );
                $where_data = array(
                    'id' => $product_id
                );

                SettingDeliveryCity::where($where_data)->update($update_data);

                return response()->json(['status' => 'success', 'msg' => 'Product deleted successfully'], 200);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Delivery_City_Ratecard()
    {
        try {
            if (Auth::check()) {
                $General_Settings = SettingDeliveryCity::all();
                $data = array(
                    'General_Settings' => $General_Settings,

                );

                return view('admin.Settings_Delivery_City_ratecard', $data);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
        }
    }

    public function Delivery_City_Ratecard_Edit()
    {
        try {
            if (Auth::check()) {
                $General_Settings = SettingDeliveryCity::select([
                    'admin_setting_delivery_city.*',
                    // 'dcs.dc_name',
                    'locations.location_name',
                    'tiers.tier_name',
                    'users.name as createdby'
                ]);
                $General_Settings = $General_Settings->join('admin_dc_master_location as locations', 'admin_setting_delivery_city.location',  'locations.id')
                    // ->join('admin_dc_master_dc as dcs', 'admin_setting_delivery_city.dc', 'dcs.id')                    
                    ->join('admin_dc_master_tier as tiers', 'admin_setting_delivery_city.tier',  'tiers.id')
                    ->join('users as users', 'admin_setting_delivery_city.created_by', 'users.id');

                $General_Settings = $General_Settings->orderBy('id', 'DESC')->get();
                $data = array(
                    'General_Settings' => $General_Settings,

                );

                return view('admin.Settings_Delivery_City_ratecard_edit', $data);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
        }
    }

    public function Delivery_City_Ratecard_EditSubmit(Request $request)
    {


        try {
            if (Auth::check()) {
                $i = 0;
                foreach ($request->dcIndexId as $dcDetails) {
                    $id = decryptId($dcDetails);
                    $update_data = array('percentage' => $request->percentage[$i]);
                    $updateDetails = SettingDeliveryCity::where('id', $id)->update($update_data);
                    $i++;
                }

                $update_data['city_ratecard_id'] = $id;
                $update_data['Updated_by'] = Auth::user();
                if ($updateDetails) {
                    Log::channel('cityRatecard-info')->info("Delivery City Ratecard Successfully Updated", $update_data);
                } else {
                    Log::channel('cityRatecard-info')->info("Delivery City Ratecard Updated Failed", $update_data);
                }

                Session::flash('success', 'Deliverycity Ratecard Updated successfully!');
                return redirect(admin_url('Delivery_City_Ratecard_Edit'));
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            Log::channel('cityRatecard-info')->error($ex);

            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }


    /**
     * General Settings Master
     */



    public function General_Settings_View(Request $request)
    {


        try {

            if (Auth::check()) {

                $General_Settings = SettingGeneral::first();
                $data = array(
                    'General_Settings' => $General_Settings,

                );

                return view('admin.Settings_General_view', $data);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);
            dd($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function usermanual_submit(Request $request)
    {

        try {

            $file = $request->file('usermanual');

            if ($file != null) {
                $uploadpath = 'public/uploads/sampleFile';

                $filenewname = time() . Str::random('10') . '.' . $file->getClientOriginalExtension();
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $fileMimetype = $file->getMimeType();
                $fileExt = $file->getClientOriginalExtension();

                if ($fileExt != 'pdf') {
                    Session::flash('error', 'Invalid file format, please upload pdf file ');
                    return redirect(admin_url('General_Settings_View'));
                }
                $file->move($uploadpath, $fileName);
                $path = $uploadpath . "/" . $fileName;

                $update_array = array(
                    'user_manual' => $path
                );

                $fileupdate =    DB::table('admin_setting_general_settings')->update($update_array);
                $update_array['uploaded_by'] = Auth::user();
                Log::channel('userManual-info')->info("user maual Successfully Uploaded", $update_array);

                Session::flash('success', 'Successfully user maual uploaded!');
                return redirect(admin_url('General_Settings_View'));
            }

            Session::flash('success', 'file not upload, please try after sometime');
            return redirect(admin_url('General_Settings_View'));
        } catch (Exception $ex) {
            Log::channel('userManual-info')->error($ex);

            Session::flash('error', 'User manual upload  failed!');
            return redirect(admin_url('General_Settings_View'));
        }
    }

    public function generate_otp_submit(Request $request)
    {
        $General_Settings = SettingGeneral::first();

        try {

            $insert_data = array(
                'otp_date' => $request->generate_otp,
            );
            $general_details = SettingGeneral::where('id', $General_Settings->id)->update($insert_data);
            Session::flash('success', 'OTP generating date added successfully');
            return redirect(admin_url('General_Settings_View'));
        } catch (Exception $ex) {
            Log::channel('userManual-info')->error($ex);

            Session::flash('error', 'OTP is not added');
            return redirect(admin_url('General_Settings_View'));
        }
    }

    public function General_Settings_Edit(Request $request)
    {

        try {

            try {
                if (Auth::check()) {


                    $General_Settings = SettingGeneral::first();


                    $data = array(
                        'General_Settings' => $General_Settings,

                    );

                    return view('admin.Settings_General_edit', $data);
                } else {
                    report('Invalid User');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } catch (Exception $ex) {

                report($ex);
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
        }
    }

    public function General_Settings_EditSubmit(Request $request)
    {

        try {
            if (Auth::check()) {
                $id = decryptId($request->id);
                $product_data = array(
                    'minimum_rate_card_amt' => $request->minimum_rate_card_amt,
                    'number_of_slot' => $request->number_of_slot,
                    'slot_block_time' => $request->slot_block_time,
                    'threshold_days' => $request->threshold_days,
                    'dc_days' => $request->dc_days,
                    'approve_limit' => $request->approve_limit,
                    'post_approval_threshold' => $request->post_approval_threshold,
                    'campaign_box_enable' => $request->campaign_box_enable,
                    'default_discount_percent' => $request->default_discount_percent,
                    'default_category_head_discount' => $request->category_head,
                    'default_category_manager_discount' => $request->category_manager,
                    'default_gst' => $request->default_gst,
                    'bidding_start_date' => $request->bidding_start_date,
                    'bidding_end_date' => $request->bidding_end_date,
                    'everymonth_campaignbox_start_in' => $request->everymonth_campaignbox_start_in,
                    'OTP_date' => $request->otp_generate,
                    'updated_by' => Auth::user()->id,
                );
                $purity_details = SettingGeneral::where('id', '1')->update($product_data);
                

                $product_data['settings_id'] = $id;
                $product_data['updated_by'] = Auth::user();
                if ($purity_details) {
                    Log::channel('generalSettings-info')->info("General Settings Successfully Updated", $product_data);
                } else {
                    Log::channel('generalSettings-info')->info("General Settings Updated Failed", $product_data);
                }
                Session::flash('success', 'General Settings Updated successfully!');
                return redirect(admin_url('General_Settings_View'));
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            Log::channel('generalSettings-info')->error($ex);
            report($ex);
            dd($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Free_Campaign_User_Edit(Request $request)
    {

        try {

            try {
                if (Auth::check()) {


                    $Free_Campaign_User = SettingGeneral::first();
                    $id = decryptId($request->id);
                    $users = User::where('role', '!=', '1')->get();
                    $General_Settings = SettingGeneral::first();


                    $data = array(
                        'Free_Campaign_User' => $Free_Campaign_User,
                        'users' => $users,
                        'General_Settings' => $General_Settings,
                    );



                    return view('admin.Free_Campaign_User_Edit', $data);
                } else {
                    report('Invalid User');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } catch (Exception $ex) {

                report($ex);
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
        }
    }

    public function Free_Campaign_User_EditSubmit(Request $request)
    {

        try {

            try {
                if (Auth::check()) {




                    $users = array_to_string(arrayDecrypt($request->user));


                    $product_data = array(
                        'free_campaign_users' =>  $users,
                        'updated_by' => Auth::user()->id,
                    );

                    $purity_details = SettingGeneral::where('id', '1')->update($product_data);

                    $product_data['updated_by'] = Auth::user();
                    if ($purity_details) {
                        Log::channel('freeCampaign-info')->info("Free Campaign Successfully Updated", $product_data);
                    } else {
                        Log::channel('freeCampaign-info')->info("Free Campaign Updated Failed", $product_data);
                    }
                    Session::flash('success', 'Free Campaign Updated successfully!');
                    return redirect(admin_url('General_Settings_View'));
                } else {
                    report('Invalid User');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } catch (Exception $ex) {

                Log::channel('freeCampaign-info')->error($ex);
                report($ex);
                dd($ex);
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
        }
    }



    public function General_Settings_Download(Request $request)
    {

        try {
            if (Auth::check()) {

                $product_id = decryptId($request->product_id);

                $update_data = array(
                    'status' => 0,
                    'trash' => "YES",
                    'updated_by' => Auth::id()
                );
                $where_data = array(
                    'id' => $product_id
                );

                Product::where($where_data)->update($update_data);

                return response()->json(['status' => 'success', 'msg' => 'Product deleted successfully'], 200);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }


    public function Bidding_Keywords_Upload(Request $request)
    {
        try {
            if (Auth::check()) {

                $data = [];

                return view('admin.Bidding_Keywords_Upload', $data);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);


            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function Bidding_Keywords_UploadSubmit(Request $request)
    {

        try {

            $file = $request->file('product_file');

            if ($file != null) {
                $uploadpath = 'public/uploads/productdata';

                $filenewname = time() . Str::random('10') . '.' . $file->getClientOriginalExtension();
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $fileMimetype = $file->getMimeType();
                $fileExt = $file->getClientOriginalExtension();

                $file->move($uploadpath, $filenewname);

                $path = $uploadpath . "/" . $filenewname;
                // chmod($path, 0777);

                $user_id = auth()->user()->id;

                $insert_data = array(
                    'file_path' => $path,
                    'source_path' => $path,
                    'dest-path' => $path,
                    'file_name' => $filenewname,
                    'file_orgname' => $fileName,
                    'extract_status' => 0,
                    'upload_type' => '12',
                    'created_by' => $user_id
                );
                $insert_id = DB::table('admin_upload_log')->insertGetId($insert_data);

                $details = [
                    "user_id" => $user_id,
                    "log_id" => $insert_id,
                    "path" => $path,

                ];


                // dispatch((new BiddingkeywordImportJob($details))->onQueue('high'));
                dispatch(new BiddingkeywordImportJob($details));

                // dd('test');
                //\Excel::import(new RateCardImport($user_id, $insert_id), $path);
            }

            $insert_data['log_id'] = $insert_id;
            $insert_data['Uploded_by'] = Auth::user();
            Log::channel('BiddingKeyword-info')->info("BiddingKeyword Successfully Uploaded", $insert_data);
            Session::flash('success', 'Successfully BiddingKeyword upload  !');
            return redirect(admin_url('General_Settings_View'));
        } catch (Exception $ex) {
            Log::channel('BiddingKeyword-info')->error($ex);

            Session::flash('error', 'BiddingKeyword upload  failed!');
            return redirect(admin_url('General_Settings_View'));
        }
    }



    public function Bidding_Keywords(Request $request)
    {

        try {

            if (Auth::check()) {
                $id = decryptId($request->id);
                //  dd($request->id);
                if ($request->ajax()) {

                    try {

                        DB::enableQueryLog();

                        $first = DB::table('admin_campaign_keywords_type')
                            ->select(DB::raw('admin_campaign_keywords_type.id,admin_campaign_keywords_type.keyword_name as item_name,"Keyword" as item_type, "0" as cat_type, users.name as username, DATE_FORMAT(admin_campaign_keywords_type.bidding_date, "%d/%m/%Y") as booking_date'))

                            ->join('users', 'admin_campaign_keywords_type.bidding_by', 'users.id')
                            ->where('admin_campaign_keywords_type.is_bidding',  '1')
                            ->where('admin_campaign_keywords_type.trash',  'NO');

                        if ($request->has('keyword_name') && $request->keyword_name != '') {
                            $first = $first->where('admin_campaign_keywords_type.keyword_name', 'like', '%' . $request->keyword_name . '%');
                        }

                        $second = DB::table('admin_category_blc')->select(DB::raw('admin_category_blc.id,admin_category_blc.blc_name as item_name,"Category" as item_type,"l1" as cat_type, users.name as username, DATE_FORMAT(admin_category_blc.bidding_date, "%d/%m/%Y") as booking_date'))
                            ->join('users', 'admin_category_blc.bidding_by', 'users.id')
                            ->where('admin_category_blc.is_bidding',  '1')->where('admin_category_blc.trash',  'NO');
                        if ($request->has('keyword_name') && $request->keyword_name != '') {
                            $second = $second->where('blc_name', 'like', '%' . $request->keyword_name . '%');
                        }

                        $third = DB::table('admin_category_slc')->select(DB::raw('admin_category_slc.id,admin_category_slc.slc_name as item_name,"Category" as item_type,"l2" as cat_type, users.name as username, DATE_FORMAT(admin_category_slc.bidding_date, "%d/%m/%Y") as booking_date'))
                            ->join('users', 'admin_category_slc.bidding_by', 'users.id')
                            ->where('admin_category_slc.is_bidding',  '1')->where('admin_category_slc.trash',  'NO');
                        if ($request->has('keyword_name') && $request->keyword_name != '') {
                            $third = $third->where('slc_name', 'like', '%' . $request->keyword_name . '%');
                        }
                        $fourth = DB::table('admin_category_tlc')->select(DB::raw('admin_category_tlc.id,admin_category_tlc.tlc_name as item_name,"Category" as item_type,"l3" as cat_type, users.name as username, DATE_FORMAT(admin_category_tlc.bidding_date, "%d/%m/%Y") as booking_date'))
                            ->join('users', 'admin_category_tlc.bidding_by', 'users.id')
                            ->where('admin_category_tlc.is_bidding',  '1')->where('admin_category_tlc.trash',  'NO')

                            ->unionAll($first)
                            ->unionAll($second)
                            ->unionAll($third)
                            ->orderBy('item_type', 'ASC');
                        if ($request->has('keyword_name') && $request->keyword_name != '') {
                            $fourth = $fourth->where('tlc_name', 'like', '%' . $request->keyword_name . '%');
                        }
                        $data  = $fourth;


                        $data_count = $data;

                        $total_records = $data_count->count();

                        $data = $data->offset($request->start)->limit($request->length)->get();

                        // dd(DB::getQueryLog());

                        // dd($data);
                        $datatables = Datatables::of($data)
                            ->addIndexColumn()


                            ->addColumn('action', function ($row) {
                                $btn = '';

                                $btn .= '<a href="javascript:void(0);"   data-id="' .   encryptId($row->cat_type . "_" . $row->id) . '"  class="DeleteRecord" title="Delete"><i class="fa  fa-trash-alt" style="color:#d81821;"></i></a> ';

                                return $btn;
                            })
                            ->rawColumns(['action', 'created_date', 'created_by', 'status'])
                            ->setFilteredRecords($total_records)
                            ->setTotalRecords($total_records)
                            ->skipPaging()
                            ->make(true);


                        return $datatables;
                    } catch (Exception $ex) {

                        report($ex);
                        dd($ex->getMessage());

                        return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                    }
                }
            }
        } catch (Exception $ex) {

            report($ex);
            // dd($ex);

            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }
    public function BiddingKeywordsReportDownload(Request $request)
    {

        try {


            $details = array(
                'item_type' => $request->item_type,
                'item_name' => $request->item_name,
            );

            return Excel::download(new BiddingKeywordExport($details), 'Bidding Keywords Details.xlsx');
        } catch (Exception $ex) {

            report($ex);
        }
    }


    public function Bidding_Delete(Request $request)
    {



        try {

            if (Auth::check()) {

                if ($request->ajax()) {

                    $id = decryptId($request->id);

                    $array_value = string_to_array($id, '_');

                    $update_data = array(
                        'is_bidding' => 0,
                        'bidding_by' => null,
                        'bidding_date' => null
                    );
                    $where_data = array(
                        'id' => $array_value[1]

                    );

                    switch ($array_value[0]) {
                        case "0":
                            CampaignKeywordType::where($where_data)->update($update_data);
                            break;
                        case "l1":
                            CategoryTLC::where($where_data)->update($update_data);
                            break;
                        case "l2":
                            CategorySLC::where($where_data)->update($update_data);
                            break;
                        case "l3":
                            CategoryBLC::where($where_data)->update($update_data);
                            break;
                        default:
                            echo "Error!";
                    }
                    $insert_data['deleted_by'] = Auth::user();
                    $insert_data['bidding_id'] = $id;
                    Log::channel('BiddingKeyword-info')->info("Bidding Category deleted successfully", $insert_data);
                    return response()->json(['status' => 'success', 'msg' => 'Bidding Category deleted successfully'], 200);
                } else {
                    report('Invalid Request');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            Log::channel('BiddingKeyword-info')->error($ex);
            report($ex);
            dd($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function master_dc_list(Request $request)
    {
        try {

            if (Auth::check()) {

                if ($request->ajax()) {
                    try {

                        $data = DB::table('admin_dc_master_dc')->select('*');


                        if ($request->has('dc_name') && $request->dc_name != '') {
                            $data = $data->where('dc_name', "LIKE", "%" . $request->dc_name . "%");
                        }

                        $data = $data->where('trash', 'NO');
                        $data = $data->orderBy('id', 'Desc');
                        $data = $data->get();

                        $datatables = Datatables::of($data)
                            ->addIndexColumn()

                            ->addColumn('dc_name', function ($row) {

                                return $row->dc_name;
                            })

                            ->addColumn('action', function ($row) {
                                $btn = '';
                                // $btn = '<a href="' . admin_url('Delivery_City_View/' . encryptId($row->id)) . '"   class="" title="View"><i class="fa  fa-eye" style="color:#0277bd;"></i></a> ';
                                $btn .= '<a  href="' . admin_url('master_dc_edit/' . encryptId($row->id)) . '" class=" " title="Edit"><i class="fa fa-edit" style="color:#43a047;"></i></a> ';
                                //   $btn .= '<a href="javascript:void(0);"  data-id="' . encryptId($row->id) . '"  class="DeleteRecord" title="Delete"><i class="fa fa-trash-alt" style="color:#d81821;"></i></a> ';

                                return $btn;
                            })
                            ->rawColumns(['action'])
                            ->make(true);

                        return $datatables;
                    } catch (Exception $ex) {

                        report($ex);
                        dd($ex);
                        return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                    }
                }
            }
            $checkData = SettingDeliveryCity::count();
            $count_array = ['count' => $checkData];;

            return view('admin.Settings_master_dc_list', $count_array);
        } catch (Exception $ex) {

            report($ex);
            dd($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function master_dc_add(Request $request)
    {

        try {
            if (Auth::check()) {


                $master_dc_list = DB::table('admin_dc_master_dc')->get();

                $data = array(
                    'master_dc_list' => $master_dc_list,

                );

                return view('admin.Settings_master_dc_add', $data);
            } else {
                report('Invalid User');

                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);

            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function master_dc_add_submit(Request $request)
    {
        try {

            if (Auth::check()) {

                $data = array(
                    'dc_name' => $request->dc_name,
                    'created_by' => Auth::user()->id,
                    'created_at' => date('Y-m-d'),
                    'updated_by' => null,
                );

                DB::table('admin_dc_master_dc')->insert($data);

                Log::channel('masteDc-info')->info("Master Dc Successfully Added", $data);

                Session::flash('success', 'Master Dc added successfully!');
                return redirect(admin_url('master_dc_list'));
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
            Log::channel('masteDc-info')->error($ex);
            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function master_dc_CheckExist(Request $request)
    {

        try {

            try {
                if (Auth::check()) {

                    if ($request->ajax()) {

                        if ($request->id == '') {
                            $user = DB::table('admin_dc_master_dc')->where('dc_name', $request->dc_name)->get();
                        } else {
                            $user = DB::table('admin_dc_master_dc')->where('dc_name', $request->dc_name)->where('id', '!=', decryptId($request->id))
                                ->get();
                        }

                        if ($user->count()) {
                            return Response::json(array('msg' => 'true'));
                        }
                        return Response::json(array('msg' => 'false'));
                    }
                } else {
                    report('Invalid User');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } catch (Exception $ex) {

                report($ex);

                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
        }
    }

    public function master_dc_edit(Request $request)
    {

        try {
            if (Auth::check()) {

                $id = decryptId($request->id);
                $master_dc = DB::table('admin_dc_master_dc')->where('id', decryptId($request->id))->first();


                $data = array(
                    'master_dc' => $master_dc,

                );
                return view('admin.Settings_master_dc_edit', $data);
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {

            report($ex);
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function master_dc_editsubmit(Request $request)
    {

        try {
            if (Auth::check()) {
                $id = decryptId($request->id);
                $data = array(
                    'dc_name' => $request->dc_name,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->id,


                );
                $update_Dc =  DB::table('admin_dc_master_dc')->where('id', $id)->update($data);

                $data['masterDc_id'] = $id;
                $data['Updated_by'] = Auth::user();
                if ($update_Dc) {
                    Log::channel('masteDc-info')->info("Master DC Successfully Updated", $data);
                } else {
                    Log::channel('masteDc-info')->info("Master DC Updated Failed", $data);
                }
                Session::flash('success', 'Details Updated successfully!');

                return redirect(admin_url('master_dc_list'));
            } else {
                report('Invalid User');
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
            Log::channel('masteDc-info')->error($ex);
            report($ex);

            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }

    public function master_dc_delete(Request $request)
    {
        try {

            try {
                if (Auth::check()) {


                    if ($request->ajax()) {

                        $id = decryptId($request->id);

                        $update_data = array(
                            'status' => 0,
                            'trash' => "YES",
                            'updated_by' => Auth::id()
                        );
                        $where_data = array(
                            'id' => $id
                        );

                        DB::table('admin_dc_master_dc')->where($where_data)->update($update_data);

                        $update_data['masterDc_id'] = $id;
                        $update_data['Deleted_by'] = Auth::user();
                        Log::channel('masteDc-info')->info("Master Dc deleted successfully", $update_data);

                        return response()->json(['status' => 'success', 'msg' => 'Master Dc deleted successfully'], 200);
                    } else {
                        report('Invalid Request');
                        return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                    }
                } else {
                    report('Invalid User');
                    return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
                }
            } catch (Exception $ex) {

                report($ex);
                return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
            }
        } catch (Exception $ex) {
            Log::channel('masteDc-info')->error($ex);
            report('Invalid Request');
            return response()->json(['status' => 'error', 'msg' => 'Please try after some time'], 406);
        }
    }
}
