<?php

namespace App\Jobs;

use App\Models\Admin\Campaign_Type;
use App\Models\Admin\CampaignBusinessType;
use App\Models\Admin\CategoryDept;
use App\Models\Admin\CategoryTLC;
use App\Models\Admin\ManufacturerMaster;
use App\Models\Admin\UserRole;
use App\Models\User;
use DB;
use Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;

use SimpleXLSX;
use Str;

class ImportUserJob implements ShouldQueue
{

    use
        Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    protected $details;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


        $i = 1;
        $error_data = [];

        $error_data = [];
        $update_array = array(
            'extract_status' => 1,
        );

        DB::table('admin_upload_log')
            ->where('id', $this->details['log_id'])
            ->update($update_array);

        $xlsx = SimpleXLSX::parse($this->details['path']);

        foreach ($xlsx->rows() as $row) {

            /*
             * Header column validation
             */
            if ($i == 1) {

                if (count($row) >= 13) {

                    if (
                        $row['0'] != 'First Name' ||
                        $row['1'] != 'Last Name' ||
                        $row['2'] != 'Email Id' ||
                        $row['3'] != 'User Role' ||
                        /*   $row['4'] != 'Phone' ||
                        $row['5'] != 'Ext' || */
                        $row['4'] != 'Mobile' ||
                        $row['5'] != 'Campaign Booking Type' ||
                        $row['6'] != 'Department' ||
                        $row['7'] != 'Categories' ||
                        $row['8'] != 'Business Type' ||
                        $row['9'] != 'Reporting Managers' ||
                        $row['10'] != 'Manufacturer' ||
                        $row['11'] != 'Profile Status' ||
                        $row['12'] != 'Website Access'
                    ) {

                        $error_data_1 = array(
                            'log_id' => $this->details['log_id'],
                            'file_name' => "Error in the Line Number " . $i,
                            'error' => 'Header Column Not Match',
                        );

                        $insert_id = DB::table('admin_upload_error_log')->insert($error_data_1);
                        $i++;
                        break;
                    }
                    $i++;
                    continue;
                } else {
                    $error_data_1 = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'Header Column Not Match',
                    );

                    $insert_id = DB::table('admin_upload_error_log')->insert($error_data_1);
                    $i++;
                    break;
                }
            }

            /* Column data validation */

            $cond_error_data = [];
            $manufacturer_id =  $row['10'];

            if ($row['0'] == '') {

                $cond_error_data = array(
                    'log_id' => $this->details['log_id'],
                    'file_name' => "Error in the Line Number " . $i,
                    'error' => 'User First Name Code is missing',
                );
                $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                $i++;
                continue;
            }

            if ($row['1'] == '') {
                $cond_error_data = array(
                    'log_id' => $this->details['log_id'],
                    'file_name' => "Error in the Line Number " . $i,
                    'error' => 'User Last Name is missing',
                );
                $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                $i++;
                continue;
            }

            if ($row['2'] == '') {
                $cond_error_data = array(
                    'log_id' => $this->details['log_id'],
                    'file_name' => "Error in the Line Number " . $i,
                    'error' => 'User Email Id is missing',
                );
                $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                $i++;
                continue;
            } else {

                $user_details = User::where('email', $row['2'])->first();

                // if ($user_details != null) {

                //     $cond_error_data = array(
                //         'log_id' => $details['log_id'],
                //         'file_name' => "Error in the Line Number " . $i,
                //         'error' => 'User Already Exist ',
                //     );
                //     $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                //     $i++;
                //     continue;
                // }
            }


            if ($row['3'] == '') {
                $cond_error_data = array(
                    'log_id' => $this->details['log_id'],
                    'file_name' => "Error in the Line Number " . $i,
                    'error' => 'User Role is missing',
                );
                $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                $i++;
                continue;
            } else {

                $role_details = UserRole::where('role_name', $row['3'])->first();
                if ($role_details != null) {
                    $role_id = $role_details->id;
                } else {
                    $cond_error_data = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'In correct user Role ',
                    );
                    $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                    $i++;
                    continue;
                }
            }

            if ($row['5'] != '') {

                $campaign_array = string_to_array($row['5']);

                $campaign_details = Campaign_Type::whereIn('campaign_type', $campaign_array)->pluck('id');

                if (count($campaign_details) > 0) {
                    $campaign_id = array_to_string($campaign_details->toArray());
                } else {
                    $cond_error_data = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'In correct Campaign Booking Type ',
                    );
                    $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                    $i++;
                    continue;
                }
            } else{
                $campaign_id = null;
            }

            if ($row['6'] != '') {

                $department_array = string_to_array($row['6']);

                $department_details = CategoryDept::whereIn('department_name', $department_array)->pluck('id');

                if (count($department_details) > 0) {
                    $department_id = array_to_string($department_details->toArray());
                } else {
                    $cond_error_data = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'In correct user Department ',
                    );
                    $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                    $i++;
                    continue;
                }
            } else {
                $department_id = '';
            }

            if ($row['7'] != '') {

                $category_array = string_to_array($row['7']);
                $category_details = CategoryTLC::whereIn('tlc_name', $category_array)->pluck('id');
                if (count($category_details) > 0) {
                    $category_id = array_to_string($category_details->toArray());
                } else {
                    $cond_error_data = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'In correct user Category ',
                    );
                    $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                    $i++;
                    continue;
                }
            } else {
                $category_id = '';
            }

            if ($row['8'] != '') {

                $business_type = string_to_array($row['8']);
                $business_details = CampaignBusinessType::whereIn('business_type_name', $business_type)->pluck('id');
                if (count($business_details) > 0) {
                    $business_id = array_to_string($business_details->toArray());
                } else {
                    $cond_error_data = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'In Business Type ',
                    );
                    $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                    $i++;
                    continue;
                }
            } else {
                $business_id = '';
            }

            if ($row['9'] != '') {

                $report_manager_array = string_to_array($row['9']);
                $report_manager_details = User::whereIn('email', $report_manager_array)->pluck('id');

                if (count($report_manager_details) > 0) {
                    $report_manager_id = array_to_string($report_manager_details->toArray());
                } else {
                    $cond_error_data = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'In correct Reporting Manager ',
                    );
                    $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                    $i++;
                    continue;
                }
            } else {
                $report_manager_id = '';
            }

            if ($manufacturer_id != '') {

                $manufacturer_details = ManufacturerMaster::where('manufacturer_name', $row['10'])->first();

                if ($manufacturer_details != null) {                   

                    $manufacturer = $manufacturer_details->id;
                } else {                    
                    $cond_error_data = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'In correct Manufacturer Name ',
                    );
                    $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                    $i++;
                    continue;
                }
            } else {
                $manufacturer = '';
            }


            if ($row['11'] == '') {
                $cond_error_data = array(
                    'log_id' => $this->details['log_id'],
                    'file_name' => "Error in the Line Number " . $i,
                    'error' => ' User Profile Status is missing',
                );
                $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                $i++;
                continue;
            } else {
                if ($row['11'] == 'Active') {
                    $profile_status = 1;
                } else if ($row['11'] != 'In-Active') {
                    $profile_status = 0;
                } else {
                    $profile_status = 0;
                }
            }

            if ($row['12'] == '') {
                $cond_error_data = array(
                    'log_id' => $this->details['log_id'],
                    'file_name' => "Error in the Line Number " . $i,
                    'error' => 'Website Access is missing',
                );
                $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                $i++;
                continue;
            } else {
                $website_array = array(
                    'Product Ads',
                    'Banner Ads',
                );


                $arr = string_to_array($row['12']);
                $r = array_map('trim', $arr);
                

                if ($row['12'] == 'Product Ads') {
                    $website_access = '1';
                } else if ($row['12'] == 'Banner Ads') {
                    $website_access = '2';
                } else if ($r == $website_array) {
                    $website_access = '1,2';
                } else {
                    $website_access = '2';
                }
            }

            $user_details = User::where('email', $row['2'])->first();

            if ($user_details != null) {

                $update_data = array(
                    'first_name' => $row['0'],
                    'last_name' => $row['1'],
                    'name' => $row['0'] . " " . $row['1'],
                    'mobile' => $row['4'],
                    'department' => $department_id,
                    'category' => $category_id,
                    'business_type' => $business_id,
                    'campaign_booking_type' => $campaign_id,
                    'manufacture_name' => $manufacturer,
                    'report_manager' => $report_manager_id,
                    'website_access' => $website_access,
                    'status' => $profile_status,
                    'role' => $role_id,
                );

                $de = User::where('id', $user_details->id)->update($update_data);
            } else {

                /* User Insert */
                $password = Str::random(12);
                $insert_data = array(
                    'first_name' => $row['0'],
                    'last_name' => $row['1'],
                    'name' => $row['0'] . " " . $row['1'],
                    'email' => $row['2'],
                    // 'phone' => $row['4'],
                    // 'ext' => $row['5'],
                    'mobile' => $row['4'],
                    'department' => $department_id,
                    'category' => $category_id,
                    'website_access' => $website_access,
                    'business_type' => $business_id,
                    'campaign_booking_type' => $campaign_id,
                    'manufacture_name' => $manufacturer,
                    'report_manager' => $report_manager_id,
                    'status' => $profile_status,
                    'role' => $role_id,
                    'password' => Hash::make($password),
                    'is_active' => '0',
                    'active_tokan' => Str::random(60),
                    'created_by' => $this->details['user_id'],
                    'created_date' => date('Y-m-d'),
                );

                $userdetails = User::create($insert_data);

                $link = getHost() . 'Account_Activate/' . $userdetails->active_tokan . '?email=' . urlencode($userdetails->email);

                try {

                    $email_details = [
                        "email" => $row['2'],
                        "name" => $row['0'] . " " . $row['1'],
                        "link" => $link,
                        "expire" => get_constant('RESET_PASSWORD_EXPIRE'),
                    ];
                } catch (\Exception $e) {
                    $cond_error_data = array(
                        'log_id' => $this->details['log_id'],
                        'file_name' => "Error in the Line Number " . $i,
                        'error' => 'Email Not send',
                    );
                    $insert_id = DB::table('admin_upload_error_log')->insert($cond_error_data);
                    $i++;
                    continue;
                }
            }
            $i++;
        }

        $final_update_array = array(
            'extract_status' => 2,
        );

        DB::table('admin_upload_log')
            ->where('id', $this->details['log_id'])
            ->update($final_update_array);
        
    }
}
