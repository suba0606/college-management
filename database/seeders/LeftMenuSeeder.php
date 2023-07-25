<?php

namespace Database\Seeders;

use App\Models\Admin\LeftMenu;
use Illuminate\Database\Seeder;

class LeftMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $insert_array = [
            [
                "id" => 1,
                "name" => "Dashboard",
                "link" => "dashboard",
                "icon" => "pie-chart",
                "parent_id" => "0",
                "is_parent" => "0",
                "is_module" => "1",
                "sort_order" => "1",
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "id" => "2",
                "name" => "User Management",
                "link" => "user_management_",
                "icon" => "message-circle",
                "parent_id" => "0",
                "is_parent" => "1",
                "is_module" => "1",
                "sort_order" => "2",
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "id" => "3",
                "name" => "User Role",
                "link" => "User_Role",
                "icon" => "file-text",
                "parent_id" => "2",
                "is_parent" => "0",
                "is_module" => "1",
                "sort_order" => "2",
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "id" => "4",
                "name" => "User Management",
                "link" => "User_Management",
                "icon" => "trello",
                "parent_id" => "2",
                "is_parent" => "0",
                "is_module" => "1",
                "sort_order" => "3",
                "status" => 1,
                "trash" => "NO",
            ],
            [
                "id" => 5,
                "name" => "General Settings",
                "link" => "General_Settings",
                "icon" => "pie-chart",
                "parent_id" => "0",
                "is_parent" => "0",
                "is_module" => "1",
                "sort_order" => "3",
                "status" => 1,
                "trash" => "NO",
            ],
        ];

        LeftMenu::truncate();
        LeftMenu::insert($insert_array);
    }
}
