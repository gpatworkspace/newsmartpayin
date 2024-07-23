<?php

namespace App\Http\Middleware;

use Closure;

class ApiCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($post, Closure $next)
    {
        if(!\Request::is('api/getip') && !\Request::is('api/getbal/*') && !\Request::is('api/callback/*') && !\Request::is('api/checkaeps/*') && !\Request::is('api/android/*')){
            if(!$post->has('token')){
                return response()->json(['statuscode'=>'ERR','status'=>'ERR','message'=> 'Invalid api token']);
            }
            
            $user = \App\Model\Apitoken::where('ip', $post->ip())->where('domain', $_SERVER['HTTP_HOST'])->where('token', $post->token)->first();
            if(!$user){
                return response()->json(['statuscode'=>'ERR','status'=>'ERR','message'=> 'Invalid Domain or Ip Address or Api Token']);
            }
        }

        // if(\Request::is('api/android/*')){
        //     if (strpos($_SERVER['HTTP_USER_AGENT'], 'Dalvik') === false && strpos($_SERVER['HTTP_USER_AGENT'], 'Android') === false) {
        //         return response()->json(['statuscode'=>'ERR', 'status'=>'ERR', 'message' => "Unauthorize Access" ]);
        //     }

        //     if ($post->has('apptoken')) {
        //         $apptoken = \App\Model\Securedata::where('apptoken', $post->apptoken)->where('user_id', $post->user_id)->first();
        //         if(!$apptoken){
        //             return response()->json(['statuscode'=>'UA', 'status'=>'UA', 'message' => "Unauthorize Access Ip"]);
        //         }else{
        //             \App\Model\Securedata::where('apptoken', $post->apptoken)->update(['last_activity' => time()]);
        //         }

        //         $user = \App\User::where('id', $apptoken->user_id)->first();

        //         if($user->status == "blocked"){
        //             return response()->json(['statuscode'=>'ERR', 'status'=>'ERR', 'message' => "Account Blocked"]);
        //         }

        //         if($user->company->status == "0"){
        //             return response()->json(['statuscode'=>'ERR', 'status'=>'ERR', 'message' => "Service Down"]);
        //         }
        //     }
        // }
        
        return $next($post);
    }
}
