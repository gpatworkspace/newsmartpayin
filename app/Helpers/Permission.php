<?php
namespace App\Helpers;
 
use Illuminate\Http\Request;
use App\Model\Aepsreport;
use App\Models\UserPermission;
use App\Model\Apilog;
use App\Model\Scheme;
use App\Model\Commission;
use App\Models\User;
use App\Model\Report;
use App\Model\Utiid;
use App\Model\Provider;
use App\Model\Packagecommission;
use App\Model\Package;

class Permission {
    /**
     * @param String $permissions
     * 
     * @return boolean
     */
    public static function can($permission , $id="none") {
        if($id == "none"){
            $id = \Auth::id();
        }
        $user = User::where('id', $id)->first();

        if(is_array($permission)){
            $mypermissions = \DB::table('permissions')->whereIn('slug' ,$permission)->get(['id'])->toArray();
            if($mypermissions){
                foreach ($mypermissions as $value) {
                    $mypermissionss[] = $value->id;
                }
            }else{
                $mypermissionss = [];
            }
            $output = UserPermission::where('user_id', $id)->whereIn('permission_id', $mypermissionss)->count();
        }else{
            $mypermission = \DB::table('permissions')->where('slug' ,$permission)->first(['id']);
            if($mypermission){
                $output = UserPermission::where('user_id', $id)->where('permission_id', $mypermission->id)->count();
            }else{
                $output = 0;
            }
        }

        if($output > 0 || $user->role->slug == "superadmin"){
            return true;
        }else{
            return false;
        }
    }

    public static function hasRole($roles) {
        if(\Auth::check()){
            if(is_array($roles)){
                if(in_array(\Auth::user()->role->slug, $roles)){
                    return true;
                }else{
                    return false;
                }
            }else{
                if(\Auth::user()->role->slug == $roles){
                    return true;
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
    }

    public static function hasNotRole($roles) {
        if(\Auth::check()){
            if(is_array($roles)){
                if(!in_array(\Auth::user()->role->slug, $roles)){
                    return true;
                }else{
                    return false;
                }
            }else{
                if(\Auth::user()->role->slug != $roles){
                    return true;
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
    }

    public static function apiLog($url, $modal, $txnid, $header, $request, $response)
    {
        try {
            $apiresponse = Apilog::create([
                "url" => $url,
                "modal" => $modal,
                "txnid" => $txnid,
                "header" => $header,
                "request" => $request,
                "response" => $response
            ]);
        } catch (\Exception $e) {
            $apiresponse = "error";
        }
        return $apiresponse;
    }

   

    public static function FormValidator($rules, $post)
    
    {
        
        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'status' => 'ERR',  
                'message' => $error
            ));
        }else{
            return "no";
        }
    }
}