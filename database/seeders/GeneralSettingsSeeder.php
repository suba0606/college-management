<?php

namespace Database\Seeders;

use App\Models\Admin\SettingGeneral;
use Illuminate\Database\Seeder;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $insertdata = [
            [
                "key" => 'favicoin',
                "value" => '',
                "display_name" => 'favicon',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'logo_mini',
                "value" => '',
                "display_name" => 'logo mini',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'logo',
                "value" => '',
                "display_name" => 'logo',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'logo_email',
                "value" => '',
                "display_name" => 'logo email',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'user_image',
                "value" => '',
                "display_name" => 'default user image',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'reset_password_expiry',
                "value" => '',
                "display_name" => 'reset password expiry',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'block_invalid_login_count',
                "value" => '',
                "display_name" => 'block invalid login count',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'block_invalid_login_mins',
                "value" => '',
                "display_name" => 'block invalid login mins',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'login_username_type_list',
                "value" => '',
                "display_name" => 'login username type list',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'login_username_type',
                "value" => '',
                "display_name" => 'login username type',
                "status" => 1,
                "trash" => "NO",
            ],

            [
                "key" => 'otp_notification_type_list',
                "value" => '',
                "display_name" => 'otp notification type list',
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "key" => 'otp_notification_type',
                "value" => '',
                "display_name" => 'otp notification type',
                "status" => 1,
                "trash" => "NO",
            ],
            



        ];

        SettingGeneral::truncate();
        SettingGeneral::insert($insertdata);
    }
}
