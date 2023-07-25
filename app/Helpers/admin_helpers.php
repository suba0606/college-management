<?php

use Illuminate\Support\Facades\DB;

/*
 * Menu bar start
 */

if (!function_exists('get_admin_menu')) {

    function get_admin_menu($menu, $is_home = false)
    {

        $menu_array = array();
        $i = 0;

        foreach ($menu as $key => $value) {
            $value = (object) $value;

            $menu_array[$value->parent_id][$i]['id'] = $value->id;
            $menu_array[$value->parent_id][$i]['name'] = $value->name;
            $menu_array[$value->parent_id][$i]['link'] = $value->link;
            $menu_array[$value->parent_id][$i]['icon'] = $value->icon;
            $menu_array[$value->parent_id][$i]['is_parent'] = $value->is_parent;
            $menu_array[$value->parent_id][$i]['parent_id'] = $value->parent_id;
            $menu_array[$value->parent_id][$i]['sort_order'] = $value->sort_order;
            $i++;
        }
        $html = "";

        $html .= ' <ul id="sidebarnav">';

        if (count($menu_array) > 0) {
            foreach ($menu_array[0] as $key => $value) {

                $target = "_self";
                $href = "#";

                if ($value['is_parent'] != 0) {

                    $href = admin_url($value['link']);
                    $link_name = $value['name'];
                    $link_icon = $value['icon'];

                    $html .= '<li class="sidebar-item">
                    <a id="link_' . encryptId($value['id']) . '" class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)"
                        aria-expanded="false"><i data-feather="' . $link_icon . '"></i><span
                            class="hide-menu">' . $value['name'] . '</span></a>';

                    if ($value['is_parent'] == '1' && isset($menu_array[$value['id']])) {
                        $parentdetails = array(
                            'id' => $value['id'],
                            'name' => $value['name'],
                        );
                        $html .= get_admin_menuchild($menu_array[$value['id']], $menu_array, $parentdetails);
                    }

                    $html .= '</li>';
                } else {

                    $href = admin_url($value['link']);
                    $link_name = $value['name'];
                    $link_icon = $value['icon'];

                    $html .= ' <li class="sidebar-item">
                    <a id="link_' . encryptId($value['id']) . '" class="sidebar-link waves-effect waves-dark sidebar-link" href="' . $href . '" aria-expanded="false">
                        <i data-feather="' . $link_icon . '"></i><span class="hide-menu">' . $link_name . '</span>
                    </a>
                </li>';
                }
            }
        }
        $html .= '</ul>';
        return $html;
    }
}

if (!function_exists('get_admin_menuchild')) {

    function get_admin_menuchild($menu, $menu_array, $parent)
    {

        $id = $parent['id'];

        $string = "";

        $string .= '<ul aria-expanded="false" class="collapse first-level">';

        foreach ($menu as $key => $value) {

            $target = "_self";
            $href = "#";
            $string .= '<li class="sidebar-item">';
            if ($value['is_parent'] == '1') {

                $string .= '<a href="timeline-center.html" class="sidebar-link"><i class="ri-clockwise-line"></i>
                <span class="hide-menu"> Center Timeline </span></a>';

                $parentdetails = array(
                    'id' => $value['id'],
                    'name' => $value['name'],
                );
                $string .= get_admin_menuchild($menu_array[$value['id']], $menu_array, $parentdetails);
            } else {

                $string .= ' <a  id="link_' . encryptId($value['id']) . '" href="' . admin_url($value["link"]) . '" class="sidebar-link">
                <span class="hide-menu"> ' . $value["name"] . ' </span></a>';
            }
            $string .= '</li>';
        }
        $string .= '</ul>';

        return $string;
    }
}


/*
 * Menu bar End
 */



if (!function_exists('getSettingValue')) {
    function getSettingValue($type)
    {

        $defaultVal =  DB::table('template_general_settings')->where('key', $type)->first();

        if ($defaultVal != '' ||  $defaultVal != null) {
            return $defaultVal->value;
        } else {
            return "";
        }
    }
}


if (!function_exists('getStudentId')) {
    function getStudentId($userId)
    {

        $defaultVal =  DB::table('admin_student')->select('id')->where('user_id', $userId)->first();

        if ($defaultVal != '' ||  $defaultVal != null) {
            return $defaultVal->id;
        } else {
            return "";
        }
    }
}


if (!function_exists('getCountValue')) {
    function getCountValue($type)
    {

        $tableName = 'users';
        $where = [];

        switch ($type) {
            case "user":
                $tableName = 'users';
                $where = array(
                    'status' => 1,
                    'trash' => 'NO',
                    'role' => 1
                );
                break;
            case "staff":
                $tableName = 'admin_staff';
                $where = array(
                    'status' => 1,
                    'trash' => 'NO',
                );
                break;
            case "student":
                $tableName = 'admin_student';
                $where = array(
                    'status' => 1,
                    'trash' => 'NO',
                );
                break;
            case "holiday":
                $tableName = 'admin_holiday_events';
                $where = array(
                    'status' => 1,
                    'trash' => 'NO',
                    'event_type' => 1,
                );
                break;
            case "event":
                $tableName = 'admin_holiday_events';
                $where = array(
                    'status' => 1,
                    'trash' => 'NO',
                    'event_type' => 2,
                );
                break;
            case "department":
                $tableName = 'admin_department';
                $where = array(
                    'status' => 1,
                    'trash' => 'NO',
                );
                break;
            default:
                $tableName = 'users';
                $where = array(
                    'status' => 1,
                    'trash' => 'NO',
                    'role' => 1
                );
                break;
        }


        $count =  DB::table($tableName);

        if (count($where) > 0)
            $count = $count->where($where);

        $count = $count->count();

        return $count;
    }
}
