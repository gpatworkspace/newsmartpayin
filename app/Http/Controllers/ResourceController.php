<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class ResourceController extends Controller
{
   
    public function index($type)
    {
        switch ($type) {
            case 'scheme':
                $permission = "scheme_manager";
                
                break;

            case 'company':
                $permission = "company_manager";
                break;

            case 'companyprofile':
                $permission = "change_company_profile";
                
                break;
            
            case 'commission':
                $permission = "view_commission";
                
                
                break;
            
            default:
                # code...
                break;
        }

        if ($type != "package" && !\Myhelper::can($permission)) {
            abort(403);
        }
        $data['type'] = $type;

        return view("resource.".$type)->with($data);
    }
    }

