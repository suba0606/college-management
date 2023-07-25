<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

use Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();

        Validator::extend('recaptcha', 'App\\Validators\\CustomValidation@recaptcha');
        Validator::extend('PasswordPolicy', 'App\\Validators\\CustomValidation@PasswordPolicy');

        defined('MENU') or define('MENU', 'template_left_menu');
        defined('NOTIFICATION') or define('NOTIFICATION', 'admin_notification');

        defined('ROLE_ADMIN') or define('ROLE_ADMIN', 1);
        defined('ROLE_STAFF') or define('ROLE_STAFF', 2);
        defined('ROLE_STUDENT') or define('ROLE_STUDENT', 3);

        View::composer('*', function ($view) {

            $mymenu = [];
            if (Auth::check()) {

                if (Auth::user()->role == ROLE_ADMIN) {

                    $mymenu = [
                        '1', '2', '3', '4', '5', '8', '9', '15', '16', '17', '18', '14', '24', '25', '26', '27', '28', '29', '31', '34',
                        '35', '36', '37', '38', '39',
                    ];
                }
            }

            $menu = DB::table(MENU)
                ->select('id', 'name', 'link', 'icon', 'parent_id', 'is_parent', 'is_module', 'sort_order')
                ->where('status', 1)
                ->where('trash', 'NO')
                ->whereIn('id', $mymenu)
                ->orderBy('parent_id', 'asc')
                ->orderBy('sort_order', 'asc')
                ->get();

            $menu_lsit = get_admin_menu($menu);

            View::share('left_menu', $menu_lsit);

            $user_id = Auth::id();

            $patient_notifications = DB::table(NOTIFICATION)
                ->select('*')
                ->where('delivery_to',  $user_id)
                ->orderBy('created_at', 'DESC')
                ->get();
            View::share('notication_menu', $patient_notifications);

            $count_notifications = DB::table(NOTIFICATION)
                ->select('*')
                ->where('delivery_to',  $user_id)
                ->where('read_status', 0)
                ->orderBy('created_at', 'DESC')
                ->get();

            View::share('notification_count', $count_notifications);
        });
    }
}
