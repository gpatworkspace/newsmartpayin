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

class MemberController extends Controller
{
   
    public function index($type , $action="view"){
            
            if($action != 'view' && $action != 'create'){
                abort(404);
            }
    
            $data['role'] = Role::where('slug', $type)->first();
            $data['roles'] = [];
            if(!$data['role'] && !in_array($type, ['other', 'active', 'inactive', 'kycpending', 'kycsubmitted', 'kycrejected'])){
                abort(404);
            }
            
            if($action == "view" && !\Myhelper::can('view_'.$type)){
                abort(401);
            }elseif($action == "create" && !\Myhelper::can('create_'.$type) && !in_array($type, ['kycpending', 'kycsubmitted', 'kycrejected'])){
                abort(401);
            }
    
            if(!in_array($type, ["active", 'inactive']) && !$data['role']){
                $roles = Role::whereIn('slug', ["superadmin", "admin",  "apiuser"])->get();
    
                foreach ($roles as $role) {
                    if(\Myhelper::can('create_'.$type)){
                        $data['roles'][] = $role;
                    }
                }
    
                $roless = Role::whereNotIn('slug', ['admin', "superadmin",'apiuser'])->get();
    
                foreach ($roless as $role) {
                    if(\Myhelper::can('create_other')){
                        $data['roles'][] = $role;
                    }
                }
            }
            
            if ($action == "create" && (!$data['role'] && sizeOf($data['roles']) == 0)){
                abort(404);
            }
            
            $data['type'] = $type;
            
            
    
            $types = array(
                'Resource' => 'resource',
                'Setup Tools' => 'setup',
                'Member'   => 'member',
                'Member Setting'   => 'memberaction',
                'Member Report'    => 'memberreport',
    
                'Wallet Fund'   => 'fund',
                'Wallet Fund Report'   => 'fundreport',
    
                'Aeps Fund'   => 'aepsfund',
                'Aeps Fund Report'   => 'aepsfundreport',
    
                'Agents List'   => 'idreport',
    
                'Portal Services'   => 'service',
                'Transactions'   => 'report',
    
                'Transactions Editing'   => 'reportedit',
                'Transactions Status'   => 'reportstatus',
    
                'User Setting' => 'setting'
            );
            foreach ($types as $key => $value) {
                $data['permissions'][$key] = Permission::where('type', $value)->orderBy('id', 'ASC')->get();
            }
    
            if($action == "view"){
                return view('members.index')->with($data);
            }else{
                return view('members.create')->with($data);
            }
        }
    }

