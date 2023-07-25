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




class CGPAController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }


   

    public function CGPA_Regulation_2018(Request $request)
    {   

        $grade = array(
            '' => '',
            'O' => 'O',
            'A+' => 'A+',
            'A' => 'A',
            'B+' => 'B+',
            'B' => 'B',
            'RA-F' => 'RA-F',
        );

        $data = [
            'grades' => $grade
        ];
       
        return view('admin.CGPA_2018', $data);
    }

    public function CGPA_Regulation_2020(Request $request)
    {

        $grade = array(
            '' => '',
            'O' => 'O',
            'A+' => 'A+',
            'A' => 'A',
            'B+' => 'B+',
            'B' => 'B',
            'RA-F' => 'RA-F',
        );

        $data = [
            'grades' => $grade
        ];

        return view('admin.CGPA_2020', $data);
    }



}
