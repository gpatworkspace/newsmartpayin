<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return view('login');
    }

    public function signupshow()
    {
        return view('register');
    }

    public function showdashboard()
    {
        return view('home');
    }
   
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'mobile' => 'required|numeric|digits:10',
            'password' => 'required|string'
        ]);

        $user = User::where('mobile', $validatedData['mobile'])->first();

        if($user && Hash::check($validatedData['password'], $user->password)){
            Auth::login($user);
            return redirect()->route('dashboard')->with('success','Logged In successfully.');
        }
        else{
            return back()->withErrors(['mobile'=>'Invalid Credentials.!'])->withInput();
        }

        // if(!$user){
        //     return response()->json(['status' => "Your aren't registred with us." ], 400);
        // }

        // if(!\Auth::validate(['mobile' => $post->mobile, 'password' => $post->password])){
        //     return response()->json(['status'=> $post->password], 400);
        // }

        // if (!\Auth::validate(['mobile' => $post->mobile, 'password' => $post->password,'status'=> "active"])) {
        //     return response()->json(['status' => 'Your account currently de-activated, please contact administrator'], 400);
        // }

        
        // if (\Auth::attempt(['mobile' =>$post->mobile, 'password' =>$post->password, 'status'=> "active"])) {
        //     return response()->json(['status' => 'Login'], 200);
        // }else{
        //     return response()->json(['status' => 'Something went wrong, please contact administrator'], 400);
        // }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('mylogin');
    }

    public function passwordReset(Request $post)
    {
        $rules = array(
            'type' => 'required',
            'mobile'  =>'required|numeric',
        );

        $validate = \Myhelper::FormValidator($rules, $post);
        if($validate != "no"){
            return $validate;
        }

        if($post->type == "request" ){
            $user = \App\User::where('mobile', $post->mobile)->first();
            if($user){
                $company = \App\Model\Company::where('id', $user->company_id)->first();
                
                $otp     = rand(111111, 999999);
                if($company->senderid){
                   
                
                  $tempid="1207162425636819834";
                  $content = "Dear partner, your TPIN reset otp is ".$otp." Don't share with anyone Regards ".$otp." Pyrapay ";
                  $sms     = \Myhelper::whatsapp($post->mobile, $content);
                
                }else{
                    $sms = false;
                }
                

                if($sms == "success" || $mail == "success"){
                    \App\User::where('mobile', $post->mobile)->update(['remember_token'=> $otp]);
                    return response()->json(['status' => 'TXN', 'message' => "Password reset token sent successfully"], 200);
                }else{
                    return response()->json(['status' => 'ERR', 'message' => "Something went wrong"], 400);
                }
            }else{
                return response()->json(['status' => 'ERR', 'message' => "You aren't registered with us"], 400);
            }
        }else{
            $user = \App\User::where('mobile', $post->mobile)->where('remember_token' , $post->token)->get();
            if($user->count() == 1){
                $update = \App\User::where('mobile', $post->mobile)->update(['password' => bcrypt($post->password), 'passwordold' => $post->password]);
                if($update){
                    return response()->json(['status' => "TXN", 'message' => "Password reset successfully"], 200);
                }else{
                    return response()->json(['status' => 'ERR', 'message' => "Something went wrong"], 400);
                }
            }else{
                return response()->json(['status' => 'ERR', 'message' => "Please enter valid token"], 400);
            }
        }  
    }
    
    public function registration(Request $post)
    {
        $this->validate($post, [
            'name'       => 'required',
            'mobile'     => 'required|numeric|digits:10|unique:users,mobile',
            'email'      => 'required|email|unique:users,email',
        ]);
        
        $post['password']   = bcrypt($post->mobile);
        $post['passwordold']   = ($post->mobile);
        $post['company_id'] = 1;
        $post['status']     = "block";
        $post['role_id']     = 4;
        $post['parent_id']     = 1;
        $post['kyc']        = "verified";

        $response = User::updateOrCreate(['id'=> $post->id], $post->all());
        if($response){
            $content="Dear ".$post->mobile.", Your profile is now created on our system. Username : ".$post->mobile.", password : ".$post->mobile.", Pyrapay";
            \Myhelper::whatsapp($post->mobile, $content);
           
            return response()->json(['status' => "TXN", 'message' => "Success"], 200);
        }
        else{
            return response()->json(['status' => 'ERR', 'message' => "Something went wrong, please try again"], 400);
        }
    }

    public function txnotp(Request $post)
    {
       $post['user_id']=\Auth::id();
       $user = \App\User::where('id', $post->user_id)->first();
       $post['mobile']=$user->mobile;
       
            if($user){
                $company = \App\Model\Company::where('id', $user->company_id)->first();
                
                $otp     = rand(111111, 999999);
                if($company->senderid){
                   
                  $content = "Your OTP for payout is ".$otp.". OTP is confidential, Please Do Not Share this with anyone. Thanks Pyrapay";
                  $sms     = \Myhelper::whatsapp($post->mobile, $content);
                
                
                }else{
                    $sms = false;
                }
                
                if($sms == "success"){
                    $user = \DB::table('txnotp')->insert([
                    'mobile' => $post->mobile,
                    'user_id'=>\Auth::id(),
                    'token' => \Myhelper::encrypt($otp, "pyrapay@@##2025500"),
                    'last_activity' => time()
                ]);
                    return response()->json(['status' => 'success', 'message' => "Otp sent successfully"], 200);
                }else{
                    return response()->json(['status' => 'failed', 'message' => "Something went wrong"], 400);
                }
            }else{
                return response()->json(['status' => 'failed', 'message' => "You aren't registered with us"], 400);
            }
    }
    
    public function getotp(Request $post)
    {
        
        $rules = array(
            'mobile'  =>'required|numeric',
        );

        $validate = \Myhelper::FormValidator($rules, $post);
        if($validate != "no"){
            return $validate;
        }

        $user = \App\User::where('mobile', $post->mobile)->first();
        if($user){
            $company = \App\Model\Company::where('id', $user->company_id)->first();
            $companyname=$company->companyname;
            $otp = rand(111111, 999999);
            $msg="Dear Sir, Your OTP for login is ".$otp." and valid for 5 min. Nikatby.in";
            $tempid="";
            $sms = \Myhelper::sms($post->mobile, $msg, $tempid,$company);
            

            try {
                \Myhelper::whatsapp($post->mobile, $msg);
            } catch (\Exception $e) {}

            if($sms == "success"){
                $user = \DB::table('password_resets')->insert([
                    'mobile' => $post->mobile,
                    'token' => \Myhelper::encrypt($otp, "nikatby@@##2025500"),
                    'last_activity' => time()
                ]);
            
                return response()->json(['status' => 'TXN', 'message' => "Pin generate token sent successfully"], 200);
            }else{
                return response()->json(['status' => 'ERR', 'message' => "Something went wrong"], 400);
            }
        }else{
            return response()->json(['status' => 'ERR', 'message' => "You aren't registered with us"], 400);
        }  
    }
    
    public function setpin(Request $post)
    {
        $checkPin=Pindata::where('user_id',$post->id)->first();
        if($checkPin)
        {
            Pindata::destroy($checkPin->id);
            $setPin=Pindata::create([
                'pin' => md5($post->pin),
                'user_id'  => $post->id
            ]);
            
            return response()->json(['status' => "success"], 200);
        }
        else{
            $setPin=Pindata::create([
                'pin' => md5($post->pin),
                'user_id'  => $post->id
            ]);
            if($setPin)
            {
                
                return response()->json(['status' => "success"], 200);
            }
            else{
                
                return response()->json(['status' => 'ERR', 'message' => 'Try Again']);
            }
        }
        // $rules = array(
        //     'id'  =>'required|numeric',
        //     'otp'  =>'required|numeric',
        //     'pin'  =>'required|numeric|confirmed',
        // );

        // $validate = \Myhelper::FormValidator($rules, $post);
        // if($validate != "no"){
        //     return $validate;
        // }

        // $user = \DB::table('password_resets')->where('mobile', $post->mobile)->where('token' , \Myhelper::encrypt($post->otp, "nikatby@@##2025500"))->first();
        // if($user){
        //     try {
        //         Pindata::where('user_id', $post->id)->delete();
        //         $apptoken = Pindata::create([
        //             'pin' => \Myhelper::encrypt($post->pin, "nikatby@@##2025500"),
        //             'user_id'  => $post->id
        //         ]);
        //     } catch (\Exception $e) {
        //         return response()->json(['status' => 'ERR', 'message' => 'Try Again']);
        //     }
            
        //     if($apptoken){
        //         \DB::table('password_resets')->where('mobile', $post->mobile)->where('token' , \Myhelper::encrypt($post->otp, "nikatby@@##2025500"))->delete();
        //         return response()->json(['status' => "success"], 200);
        //     }else{
        //         return response()->json(['status' => "Something went wrong"], 400);
        //     }
        // }else{
        //     return response()->json(['status' => "Please enter valid otp"], 400);
        // }  
    }

    public function setpinwithoutotp(Request $request)
    {
        echo $request->mobile;
        echo $request->pin;
    }
}
