<?php

namespace Database\Seeders;

use App\Models\Admin\UserRole;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
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
                "id" => 1,
                "role_name" => "Admin",
                "role_permission" => '',
                "status" => 1,
                "trash" => "NO",
                
            ]
            

        ];

        UserRole::truncate();
        UserRole::insert($insertdata);
    }
}
