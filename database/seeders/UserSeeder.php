<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Hash;

use App\Models\User;

class UserSeeder extends Seeder
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
                "first_name" => "admin",
                "last_name" => "user",
                "name" => "admin user",
                "email" => "admin@gmail.com",
                "role" => 1,
                "mobile" => "3423423422",
                "profile_image" => "1641291529ib3bigQd7j.png",
                "remember_token" => "",
                "active_tokan" => "",
                "password" => Hash::make('asdF@1234567'),
                "created_by" => "1",
                "created_date" => "2021-12-03",
                "status" => 1,
                "trash" => "NO",
                
            ],

        ];

        User::truncate();
        User::insert($insertdata);
    }
}
