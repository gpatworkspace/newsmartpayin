<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Validation\Rule;
use Carbon\Carbon;

class FundController extends Controller
{
    #public $fundapi,$cashfree, $admin;

    #public function __construct()
    #{
    #    $this->fundapi = Api::where('code', 'fund')->first();
         

    #}

    public function index($type, $action="none")
    {
       $data['type'] = $type;

        return view('fund.'.$type)->with($data);
    }
    public function getToken(){
       
        $url = $this->cashfree->url."payout/v1/verifyToken";

        $header = array(
            'Authorization: Bearer '.$this->cashfree->optional1
        );

        $result = \Myhelper::curl($url, "POST", "", $header, "no");
        //dd($result,$url); 
        $response = json_decode($result['response']);
        if(isset($response->subCode) && $response->subCode == "403"){

            $url = $this->cashfree->url."payout/v1/authorize";

            $header = array(
                "X-Client-Id: ".$this->cashfree->username,
                "X-Client-Secret: ".$this->cashfree->password
            );

            $result = \Myhelper::curl($url, "POST", "", $header, "no");
            
            //dd($result,$header,$url);
            $response = json_decode($result['response']);
            if(isset($response->subCode) && $response->subCode == "200"){
                Api::where('id', $this->cashfree->id)->update(['optional1' => $response->data->token]);
                return $response->data->token;
            }
        }
        return $this->cashfree->optional1;
    }

    public function transaction(Request $post)
    {
        //dd($post->all());
        if ($this->cashfree->status == "0") {
            return response()->json(['status' => "This function is down."],400);
        }
        
        $provide = Provider::where('recharge1', 'fund')->first();
        $post['provider_id'] = $provide->id;

        switch ($post->type) {
             case 'onlinepaytamchecksum':
              
                $privatekey = "p0cZE9MpB6Knw6tj";
                $paramList["MID"] = $post['MID'];
                $paramList["ORDER_ID"] = $post['ORDER_ID'];
                $paramList["CUST_ID"] = $post['CUST_ID'];
                $paramList["INDUSTRY_TYPE_ID"] =$post['INDUSTRY_TYPE_ID'];
                $paramList["CHANNEL_ID"] = $post['CHANNEL_ID'];
                $paramList["TXN_AMOUNT"] = $post['TXN_AMOUNT'];
                $paramList["WEBSITE"] = $post['WEBSITE'];
                $paramList["CALLBACK_URL"] = $post['CALLBACK_URL'];
                
                
                $checkSum = \Paytm::getChecksumFromArray($paramList,$privatekey);
                //dd($checkSum);
                 return response()->json(['checkSum' => $checkSum],200);


                 exit();
                break;
            
            case 'transfer':
            case 'return':
                if($post->type == "transfer" && !\Myhelper::can('fund_transfer')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }

                if($post->type == "return" && !\Myhelper::can('fund_return')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }

                $rules = array(
                    'amount'    => 'required|numeric|min:1',
                );
        
                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors'=>$validator->errors()], 422);
                }

                if($post->type == "transfer"){
                    if(\Auth::user()->mainwallet - $this->mainlocked() < $post->amount){
                        return response()->json(['status' => "Insufficient wallet balance."],400);
                    }
                }else{
                    $user = User::where('id', $post->user_id)->first();
                    if($user->mainwallet - $this->mainlocked() < $post->amount){
                        return response()->json(['status' => "Insufficient balance in user wallet."],400);
                    }
                }
                $post['txnid'] = 0;
                $post['option1'] = 0;
                $post['option2'] = 0;
                $post['option3'] = 0;
                $post['refno'] = date('ymdhis');
                return $this->paymentAction($post);

                break;
                
            case 'addrelationshipinfo':
                 $rules = array(
                    'relationship_m_no'    => 'required',
                    'relationship_m_email'=>'required'
                );
        
                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors'=>$validator->errors()], 422);
                }
                $action=User::where('id',$post->user_id)->update(['relationship_m_no'=>$post->relationship_m_no,'relationship_m_email'=>$post->relationship_m_email]);
                if($action){
                        return response()->json(['status' => "success"],200);
                }else{
                        return response()->json(['status' => "Something went wrong, please try again."],200);
                }
                break;
           
            case 'requestview':
                if(!\Myhelper::can('setup_bank')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }

                $fundreport = Fundreport::where('id', $post->id)->first();
                
                if($fundreport->status != "pending"){
                    return response()->json(['status' => "Request already approved"],400);
                }

                $post['amount'] = $fundreport->amount;
                $post['type'] = "request";
                $post['user_id'] = $fundreport->user_id;
                if ($post->status == "approved") {
                    if(\Auth::user()->mainwallet < $post->amount){
                        return response()->json(['status' => "Insufficient wallet balance."],200);
                    }
                    $action = Fundreport::where('id', $post->id)->update([
                        "status" => $post->status,
                        "remark" => $post->remark
                    ]);

                    $post['txnid'] = $fundreport->id;
                    $post['option1'] = $fundreport->fundbank_id;
                    $post['option2'] = $fundreport->paymode;
                    $post['option3'] = $fundreport->paydate;
                    $post['refno'] = $fundreport->ref_no;
                    return $this->paymentAction($post);
                }else{
                    $action = Fundreport::where('id', $post->id)->update([
                        "status" => $post->status,
                        "remark" => $post->remark
                    ]);

                    if($action){
                        return response()->json(['status' => "success"],200);
                    }else{
                        return response()->json(['status' => "Something went wrong, please try again."],200);
                    }
                }
                
                return $this->paymentAction($post);
                break;

            case 'request':
                if(!\Myhelper::can('fund_request')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }

                $rules = array(
                    'fundbank_id'    => 'required|numeric',
                    'paymode'    => 'required',
                    'amount'    => 'required|numeric|min:100',
                    'ref_no'    => 'required|unique:fundreports,ref_no',
                    'paydate'    => 'required'
                );
        
                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                     foreach ($validator->errors()->messages() as $key => $value) {
                    $error = $value[0];
                }
                    return response()->json(['status'=>'failed','message'=>$error]);
                }

                $post['user_id'] = \Auth::id();
                $post['credited_by'] = \Auth::user()->parent_id;
                
                // if(\Auth::user()->parent_id=="7"){
                //     if($post->amount<50000){
                //   return response()->json(['status'=>  "amount should be greater than 50000"], 400);  
                //  }
                // }
                
                if(!\Myhelper::can('setup_bank', \Auth::user()->parent_id)){
                    $admin = User::whereHas('role', function ($q){
                        $q->where('slug', 'whitelable');
                    })->where('company_id', \Auth::user()->company_id)->first(['id']);

                    if($admin && \Myhelper::can('setup_bank', $admin->id)){
                        $post['credited_by'] = $admin->id;
                    }else{
                        $admin = User::whereHas('role', function ($q){
                            $q->where('slug', 'admin');
                        })->first(['id']);
                        $post['credited_by'] = $admin->id;
                    }
                }
                
                $post['status'] = "pending";
                if($post->hasFile('payslips')){
                    $filename ='payslip'.\Auth::id().date('ymdhis').".".$post->file('payslips')->guessExtension();
                    $post->file('payslips')->move(public_path('deposit_slip/'), $filename);
                    $post['payslip'] = $filename;
                }
                $action = Fundreport::create($post->all());
                if($action){
                    return response()->json(['status' => "success"],200);
                }else{
                    return response()->json(['status'=>'failed','message' => "Something went wrong, please try again."],200);
                }
                break;
                
            case 'updateprepaid':
               

                $rules = array(
                   
                    'prepaidrefno'    => 'required',
                   
                );
        
                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors'=>$validator->errors()], 422);
                }
              
              $action = \DB::table('reports')->where('id',$post->id)->update(['remark'=>$post->prepaidrefno]);
            
               
                    
               
                
                if($action){
                    return response()->json(['status' => "success"],200);
                }else{
                    return response()->json(['status' => "Something went wrong, please try again."],200);
                }
                break;     
                
            case 'prepaidcardload':
               

                $rules = array(
                   
                    'amount'    => 'required|numeric|min:1',
                    'option1'    => 'required',
                   
                );
        
                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors'=>$validator->errors()], 422);
                }

                $post['user_id'] = \Auth::id();
                
                if(\Auth::user()->mainwallet - $this->mainlocked() < $post->amount){
                        return response()->json(['status' => "Insufficient wallet balance."],400);
                    }
               
               $userdata= Report::where('id',$post->id)->where('user_id',$post->user_id)->first();
            
               User::where('id', \Auth::user()->id)->decrement('mainwallet', $post->amount);
                $insert = [
                        'number'  => $userdata->number,
                        'bank'  => $userdata->bank,
                        'amount'=>$post->amount,
                        'provider_id'=>'0',
                        'api_id'=>'0',
                        'apitxnid' => $userdata->apitxnid,
                        'txnid'  => $userdata->txnid,
                        'payid'  => $userdata->payid,
                        'mobile'  => $userdata->mobile,
                        'refno'   => $userdata->refno,
                        'description'   => $userdata->description,
                        'option1' => $userdata->option1,
                        'option2' => $userdata->option2,
                        'status'  => 'success',
                        'user_id'    => $post->user_id,
                        'product'=> 'cardload'
                        
                    ];
                    
                $action = \DB::table('reports')->insert([$insert]);
                
                if($action){
                    return response()->json(['status' => "success"],200);
                }else{
                    return response()->json(['status' => "Something went wrong, please try again."],200);
                }
                break;    

            case 'bank':
                if ($this->txnpinCheck($post) == "fail") {
                    return response()->json(['status' => "Transaction otp is incorrect"],400);
                }
                $payouttype = $this->instanttypemode();
                $impschargeupto25 =  $this->impschargeupto25();
                $impschargeabove25 = $this->impschargeabove25();
                $payoutapi = $this->payoutapi();
                //dd($payoutapi);
                
                
                $api = Api::where('code', 'cashpayout')->first();
                
                $user = User::where('id',\Auth::user()->id)->first();
                $post['user_id'] = \Auth::id();
                 
                if($user->account == '' && $user->bank == '' && $user->ifsc == ''){
                    $rules = array(
                        'amount'    => 'required|numeric|min:1',
                        'account'   => 'sometimes|required',
                        'bank'   => 'sometimes|required',
                        'ifsc'   => 'sometimes|required'
                    );
                }else{
                    $rules = array(
                        'amount'    => 'required|numeric|min:1'
                    );

                    $post['account'] = $user->account;
                    $post['bank']    = $user->bank;
                    $post['ifsc']    = $user->ifsc;
                }

                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }
                

                if($user->account == '' && $user->bank == '' && $user->ifsc == ''){
                    User::where('id',\Auth::user()->id)->update(['account' => $post->account, 'bank' => $post->bank, 'ifsc'=>$post->ifsc]);
                }

                $settlerequest = Aepsfundrequest::where('user_id', \Auth::user()->id)->where('status', 'pending')->count();
                if($settlerequest > 0){
                    return response()->json(['status'=> "One request is already submitted"], 400);
                }

               $post['charge'] = 0;
                
               if($post->amount <= 25000){
                    $post['charge'] = $impschargeupto25;
                }

                if($post->amount > 25000){
                    $post['charge'] = $impschargeabove25;
                }
                
                if($post->mode == "NEFT" ){
                    $post['charge'] = $this->neftcharge();
                }
                
                //$post['provider_id'] = $provider->id;
                
                // $post['charge'] = \Myhelper::getCommission($post->amount, $user->scheme_id, $post->provider_id, $user->role->slug);
                
                // if($post->amount <= 25000){
                //     $post['charge'] = $impschargeupto25;
                // }

                // if($post->amount > 25000){
                //     $post['charge'] = $impschargeabove25;
                // }
                
                // if($post->mode == "NEFT" ){
                //     $post['charge'] = $this->neftcharge();
                // }
                 $post['gst'] = $this->getGst($post->charge);
                
                
                if($user->aepsbalance < $post->amount + $post->charge-$post->gst){
                    return response()->json(['status'=>  "Low aeps balance to make this request."], 400);
                }
                
                $previousrecharge = Aepsfundrequest::where('account', $post->account)->where('amount', $post->amount)->where('user_id', $post->user_id)->whereBetween('created_at', [Carbon::now()->subSeconds(30)->format('Y-m-d H:i:s'), Carbon::now()->addSeconds(30)->format('Y-m-d H:i:s')])->count();
                    if($previousrecharge){
                        return response()->json(['status'=> "Transaction Allowed After 1 Min."]);
                    }
                    
                    do {
                        $post['txnid'] = $this->transcode().rand(111111111111, 999999999999);
                    } while (Aepsfundrequest::where("payoutid", "=", $post->txnid)->first() instanceof Aepsfundrequest);

                    $post['status']   = "pending";
                    $post['mode']   = $post->mode;
                    $post['pay_type'] = "payout";
                    $post['payoutid'] = $post->txnid;
                    $post['payoutref']= $post->txnid;
                    $post['create_time']= Carbon::now()->toDateTimeString();
                    try {
                        $aepsrequest = Aepsfundrequest::create($post->all());
                    } catch (\Exception $e) {
                        return response()->json(['status'=> "Duplicate Transaction Not Allowed, Please Check Transaction History"]);
                    }
                    
                    $inserts['api_id'] = $api->id;
                    $inserts['payid']  = $aepsrequest->id;
                    $inserts['provider_id']  = "0";
                    $inserts['mobile'] = $user->mobile;
                    $inserts['charge'] = $post->charge;
                    $inserts['gst'] = $post->gst;
                    $inserts['number'] = $post->account;
                    $inserts['amount'] = $post->amount;
                    $inserts['txnid']  = $post->txnid;
                    $inserts['option1']= $post->bank."(".$post->ifsc.")";
                    $inserts['user_id']= $user->id;
                    $inserts['credit_by']= $this->admin->id;
                    $inserts['balance']    = $user->aepsbalance;
                    $inserts['trans_type'] = "debit";
                    $inserts['product']  = 'payout';
                    $inserts['aepstype']  = 'PAYOUT';
                    $inserts['status'] = 'success';
                    $inserts['remark'] = "Bank Settlement";
                   
                    User::where('id', \Auth::user()->id)->decrement('aepsbalance', $post->amount + $inserts['charge']+$post->gst);
                    $myaepsreport= Aepsreport::create($inserts);
                    
                    $authToken = $this->getToken();
                      
                    $header = array(
                            'Authorization: Bearer '.$authToken,
                            'Content-Type: application/json'
                        );
                    $url = $this->cashfree->url."payout/v1/directTransfer";
                        $request=[
                            "amount"         => $post->amount,
                            "transferId"     => $post->txnid,
                            "transferMode"   => strtolower($post->mode),
                            "remarks"        => 'test',
                            "beneDetails"    => [
                                            "bankAccount"     => $post->account,
                                            "ifsc"            => $post->ifsc,
                                            "name"            => $user->name,
                                            "email"           => $user->email,
                                            "phone"           => $user->mobile,
                                            "address1"        => $user->address,
                                ],
                            ];
                        
                         
                        
                        $result = \Myhelper::curl($url, "POST", json_encode($request), $header, 'yes', 'Payout', $post->payoutid);
                       //dd($result['response'],json_encode($request),$url,$header);
                        
                        if($result['response'] != ""){
                            $response = json_decode($result['response']);
                            //dd($response);
                            if(isset($response->status) && ($response->status=="ERROR"&& $response->subCode=="400")){
                                   User::where('id', $aepsrequest->user_id)->increment('aepsbalance', $aepsrequest->amount+$aepsrequest->charge+$post->gst);
                                   Aepsreport::where('id', $myaepsreport->id)->update(['status' => "failed", "refno" => isset($response->message) ? $response->message : "Failed"]);
                                   Aepsfundrequest::where('id', $aepsrequest->id)->update(['status' => "rejected", "remark" => isset($response->message) ? $response->message : "Failed"]);
                                   return response()->json(['status'=>'failed','message' => isset($response->message) ? $response->message : "Failed"], 400);
                                
                               
                            }
                            if($response->status=="ERROR"&& $response->subCode=="422"){
                                   User::where('id', $aepsrequest->user_id)->increment('aepsbalance', $aepsrequest->amount+$aepsrequest->charge+$post->gst);
                                   Aepsreport::where('id', $myaepsreport->id)->update(['status' => "failed", "refno" => isset($response->message) ? $response->message : "Failed"]);
                                   Aepsfundrequest::where('id', $aepsrequest->id)->update(['status' => "rejected", "remark" => isset($response->message) ? $response->message : "Failed"]);
                                   return response()->json(['status'=>'failed','message' => isset($response->message) ? $response->message : "Failed"], 400);
                                
                               
                            }
                            elseif($response->status=="SUCCESS"&& $response->subCode=="200"){
                                Aepsfundrequest::where('id', $aepsrequest->id)->update(['status' => "approved", "payoutref" => $response->data->referenceId,'apitxnid'=>$response->data->utr]);
                                Aepsreport::where('id', $myaepsreport->id)->update(['status' => "success", "refno" => isset($response->data->referenceId) ? $response->data->referenceId : "Failed","apitxnid" => isset($response->data->utr) ? $response->data->utr : "Failed"]);
                                return response()->json(['status'=>"success"], 200);  
                            }
                            
                            else{
                                Aepsfundrequest::where('id', $aepsrequest->id)->update(['status' => "pending", "payoutref" => $response->data->referenceId,'apitxnid'=>$response->data->utr]);
                                Aepsreport::where('id', $myaepsreport->id)->update(['status' => "pending", "refno" => isset($response->data->referenceId) ? $response->data->referenceId : "Pending","apitxnid" => isset($response->data->utr) ? $response->data->utr : "Pending"]);
                                return response()->json(['status'=>"pending"], 200);   
                            }
                           
                        }else{
                            return response()->json(['status'=>"failed"], 200);
                        }
                    
                break;

            case 'wallet':
                if(!\Myhelper::can('aeps_fund_request')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }
                
                $settlementtype = $this->settlementtype();

                if($settlementtype == "down"){
                    return response()->json(['status' => "Aeps Settlement Down For Sometime"],400);
                }

                $rules = array(
                    'amount'    => 'required|numeric|min:1',
                );
        
                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors'=>$validator->errors()], 422);
                }

                $user = User::where('id',\Auth::user()->id)->first();

                $request = Aepsfundrequest::where('user_id', \Auth::user()->id)->where('status', 'pending')->count();
                if($request > 0){
                    return response()->json(['status'=> "One request is already submitted"], 400);
                }

                if(\Auth::user()->aepsbalance < $post->amount){
                    return response()->json(['status'=>  "Low aeps balance to make this request"], 400);
                }

                $post['user_id'] = \Auth::id();

                if($settlementtype == "auto"){
                    $previousrecharge = Aepsfundrequest::where('type', $post->type)->where('amount', $post->amount)->where('user_id', $post->user_id)->whereBetween('created_at', [Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')])->count();
                    if($previousrecharge > 0){
                        return response()->json(['status'=> "Transaction Allowed After 5 Min."]);
                    }
                    $post['mode']="IMPS"; 
                    $post['status'] = "approved";
                    $load = Aepsfundrequest::create($post->all());
                    $payee = User::where('id', \Auth::id())->first();
                    User::where('id', $payee->id)->decrement('aepsbalance', $post->amount);
                    $inserts = [
                        "mobile"  => $payee->mobile,
                        "amount"  => $post->amount,
                        'provider_id' => '0',
                        'api_id' => $this->fundapi->id,
                        "bank"    => $payee->bank,
                        'txnid'   => date('ymdhis'),
                        'refno'   => $post->refno,
                        "user_id" => $payee->id,
                        "credited_by" => $user->id,
                        "balance"     => $payee->aepsbalance,
                        'trans_type'        => "debit",
                        'transtype'   => 'fund',
                        'aepstype'   => 'other',
                        'product'   => 'fund request',
                        'status'      => 'success',
                        'remark'      => "Move To Wallet Request",
                        'payid'       => "Wallet Transfer Request",
                        'number'      => $payee->account
                    ];

                    Aepsreport::create($inserts);

                    if($post->type == "wallet"){
                        $provide = Provider::where('recharge1', 'aepsfund')->first();
                        User::where('id', $payee->id)->increment('mainwallet', $post->amount);
                        $insert = [
                            'number' => $payee->mobile,
                            'mobile' => $payee->mobile,
                            'provider_id' => '0',
                            'api_id' => $this->fundapi->id,
                            'amount' => $post->amount,
                            'charge' => '0.00',
                            'profit' => '0.00',
                            'gst' => '0.00',
                            'tds' => '0.00',
                            'txnid' => $load->id,
                            'payid' => $load->id,
                            'refno' => $post->refno,
                            'description' =>  "Aeps Fund Recieved",
                            'remark' => $post->remark,
                            'option1' => $payee->name,
                            'status' => 'success',
                            'user_id' => $payee->id,
                            'credit_by' => $payee->id,
                            'rtype' => 'main',
                            'via' => 'portal',
                            'balance' => $payee->mainwallet,
                            'trans_type' => 'credit',
                            'product' => "fund request"
                        ];

                        Report::create($insert);
                    }
                }else{
                    $load = Aepsfundrequest::create($post->all());
                }

                if($load){
                    return response()->json(['status' => "success"],200);
                }else{
                    return response()->json(['status' => "fail"],200);
                }
                break;
                
                case 'aepsbank': 
               if ($this->txnpinCheck($post) == "fail") {
                    return response()->json(['status' => "Transaction otp is incorrect"]);
                }
                $settlementservice= $this->settlementservice();
                $banksettlementtype = $this->banksettlementtype();
                if($banksettlementtype == "off"){
                    return response()->json(['status' => "Aeps Settlement Down For Sometime"],400);
                }
                if($banksettlementtype == "down"){
                    return response()->json(['status' => "Aeps Settlement Down For Sometime"],400);
                }

                $user = User::where('id',\Auth::user()->id)->first();
                $post['user_id'] = \Auth::id();

                if($user->account == '' && $user->bank == '' && $user->ifsc == ''){
                    $rules = array(
                        'amount'    => 'required|numeric|min:1',
                        'account'   => 'sometimes|required',
                        'bank'   => 'sometimes|required',
                        'ifsc'   => 'sometimes|required'
                    );
                }else{
                    $rules = array(
                        'amount'    => 'required|numeric|min:10'
                    );

                    $post['account'] = $user->account;
                    $post['bank']    = $user->bank;
                    $post['ifsc']    = $user->ifsc;
                }

                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }

                if($user->account == '' && $user->bank == '' && $user->ifsc == ''){
                    User::where('id',\Auth::user()->id)->update(['account' => $post->account, 'bank' => $post->bank, 'ifsc'=>$post->ifsc]);
                }

                if($post->amount <= 1000){
                    $post['charge'] = $this->settlementcharge1k();
                }elseif($post->amount > 1000 && $post->amount <= 25000){
                    $post['charge'] = $this->settlementcharge25k();
                }else{
                    $post['charge'] = $this->settlementcharge2l();
                }

                if($user->aepsbalance < $post->amount + $post->charge){
                    return response()->json(['status'=>  "Low aeps balance to make this request."], 400);
                }
                $previousrecharge = Qrreport::where('number', $post->account)->where('amount', $post->amount)->where('user_id', $post->user_id)->whereBetween('created_at', [Carbon::now()->subSeconds(30)->format('Y-m-d H:i:s'), Carbon::now()->addSeconds(30)->format('Y-m-d H:i:s')])->count();
                if($previousrecharge){
                    return response()->json(['status'=> "Transaction Allowed After 1 Min."]);
                } 

                

                do {
                    $post['txnid'] = $this->transcode().rand(111111111111, 999999999999);
                } while (Qrreport::where("txnid", "=", $post->txnid)->first() instanceof Qrreport);

                $insert = [
                    'number' => $post->account,
                    'mobile' => $user->mobile,
                    'provider_id' => $provide->id,
                    'api_id' => '22',
                    'amount' => $post->amount,
                    'charge' => $post->charge,
                    'txnid'  => $post->txnid,
                    'option1'  => $post->bank,
                    'option2'  => $post->ifsc,
                    'remark'   => "Bank Settlement",
                    'status'   => 'success',
                    'user_id'  => $user->id,
                    'credit_by'=> $this->admin->id,
                    'rtype'    => 'main',
                    'via'      => 'portal',
                    'balance'  => $user->aepsbalance,
                    'trans_type'  => 'debit',
                    'product'     => 'payout',
                    'create_time' => Carbon::now()->format('Y-m-d H:i:s')
                ];

                User::where('id', $insert['user_id'])->decrement('aepsbalance',$insert['amount']+$insert['charge']);
                $myaepsreport = Qrreport::create($insert);
                
               $authToken = $this->getToken();
                      
                        $header = array(
                            'Authorization: Bearer '.$authToken,
                            'Content-Type: application/json'
                        );
                        $url = $this->cashfree->url."payout/v1/directTransfer";
                        $request=[
                            "amount"         => $post->amount,
                            "transferId"     => $post->txnid,
                            "transferMode"   => strtolower($post->mode),
                            "remarks"        => 'test',
                            "beneDetails"    => [
                                            "bankAccount"     => $post->account,
                                            "ifsc"            => $post->ifsc,
                                            "name"            => $user->name,
                                            "email"           => $user->email,
                                            "phone"           => $user->mobile,
                                            "address1"        => $user->address,
                                ],
                            ];
                        
                         
                        
                        $result = \Myhelper::curl($url, "POST", json_encode($request), $header, 'yes', 'Payout', $post->payoutid);
                       //dd($result['response'],json_encode($request),$url,$header);
                        
                        if($result['response'] != ""){
                            $response = json_decode($result['response']);
                            //dd($response);
                            if(isset($response->status) && $response->status=="ERROR"){
                                  User::where('id', $insert['user_id'])->increment('aepsbalance', $insert['amount'] + $insert['charge']);
                                 Qrreport::where('id', $myaepsreport->id)->update(['status' => "failed", "refno" =>  isset($response->message) ? $response->message : "Failed"]);
                               
                                return response()->json(['status' => isset($response->message) ? $response->message : "Failed"], 400);
                                
                               
                            }
                            
                            else{
                                   Qrreport::where('id', $myaepsreport->id)->update(['status' => "success", "refno" => isset($response->data->referenceId) ? $response->data->referenceId : "Failed"]);
                                return response()->json(['status'=>"success"], 200);
                            }
                           
                        }else{
                            return response()->json(['status'=>"success"], 200);
                        }

                
                break;

            case 'aepswallet':
                if(!\Myhelper::can('aeps_fund_request')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }
                $rules = array(
                    'amount'    => 'required|numeric|min:1',
                );
        
                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors'=>$validator->errors()], 422);
                }

                $user = User::where('id',\Auth::user()->id)->first();

                if(\Auth::user()->aepsbalance >= $post->amount){
                    $post['user_id'] = \Auth::id();
                    $post['charge']  = 0;
                    $payee = User::where('id', \Auth::id())->first();
                    User::where('id', $payee->id)->decrement('aepsbalance', $post->amount);
                    $insert = [
                        'number' => $user->mobile,
                        'mobile' => $user->mobile,
                        'provider_id' => $provide->id,
                        'api_id' => '22',
                        'amount' => $post->amount,
                        'charge' => $post->charge,
                        'txnid'  => date("ymdhis"),
                        'remark'   => "Wallet Settlement",
                        'status'   => 'success',
                        'user_id'  => $user->id,
                        'credit_by'=> $this->admin->id,
                        'rtype'    => 'main',
                        'via'      => 'portal',
                        'balance'  => $user->aepsbalance,
                        'trans_type'  => 'debit',
                        'product'     => 'payout',
                        'create_time' => Carbon::now()->format('Y-m-d H:i:s')
                    ];

                    $load = Qrreport::create($insert);

                    if($post->type == "aepswallet"){
                            $provide = Provider::where('recharge1', 'fund')->first();
                            User::where('id', $payee->id)->increment('mainwallet', $post->amount);
                            $insert = [
                                'number' => $payee->mobile,
                                'mobile' => $payee->mobile,
                                'provider_id' => $provide->id,
                                'api_id' => $this->fundapi->id,
                                'amount' => $post->amount,
                                'charge' => '0.00',
                                'profit' => '0.00',
                                'gst' => '0.00',
                                'tds' => '0.00',
                                'txnid' => $load->id,
                                'payid' => $load->id,
                                'refno' => $post->refno,
                                'description' =>  "Aeps Fund Recieved",
                                'remark' => $post->remark,
                                'option1' => $payee->name,
                                'status' => 'success',
                                'user_id' => $payee->id,
                                'credit_by' => $payee->id,
                                'rtype' => 'main',
                                'via' => 'portal',
                                'balance' => $payee->mainwallet,
                                'trans_type' => 'credit',
                                'product' => "fund request"
                            ];

                            Report::create($insert);
                        }
                
                    if($load){
                        return response()->json(['status' => "success"],200);
                    }else{
                        return response()->json(['status' => "fail"],200);
                    }
                }else{
                    return response()->json(["errors"=>['amount'=>["Low aeps balance to make this request."]]], 422);
                }
                break;

               //cash deposit offline

                case 'cashdeposittransfer':
             
                 if(\Myhelper::hasNotRole('admin')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }
                $user = User::where('id',\Auth::user()->id)->first();

                if($user->mainwallet < $post->amount){
                    return response()->json(['status' => "Insufficient Aeps Wallet Balance"],400);
                }
                
                $request = Aepsfundrequest::find($post->id);
                $payee = User::where('id', $request->user_id)->first();

                if($post->status == "rejected"){
                    User::where('id', $payee->id)->update(['mainwallet'=> $payee->mainwallet + $request->amount]);
                     $userbal = User::where('id', $request->user_id)->first();
                     $insert1 = [
                            "mobile"     => $request->mobile,
                            "number"     => $request->account,
                            "txnid"      => $request->apitxnid,
                            "amount"     => $request->amount,
                            "bank"       => $request->bankName,
                            "user_id"    => $user->id,
                            'status'     => 'failed',
                            'aepstype'   => 'CD',
                            'remark'     => $post->remark,
                            'balance'    => $userbal->mainwallet 
                        ];
                        
                    Aepsfundrequest::where('id', $post->id)->update(['status'=>$post->status, 'remark'=> $post->remark]);
                        
                      Report::create($insert1);
                    return response()->json(['status'=> "success"], 200);
                 }
                     else{
                           Aepsfundrequest::where('id', $post->id)->update(['status'=>$post->status, 'remark'=> $post->remark]);
                           $insert1 = [
                                "mobile"     => $request->mobile,
                                "number"     => $request->account,
                                "txnid"      => $request->apitxnid,
                                "amount"     => $request->amount,
                                "bank"       => $request->bankName,
                                "user_id"    => $user->id,
                                'status'     => 'success',
                                'aepstype'   => 'CD',
                                'remark'     => $post->remark,
                                'balance'    => $payee->mainwallet 
                            ];
                            Report::create($insert1);
                           return response()->json(['status'=> "success"], 200);
                     }
                 
              break;


            case 'matmbank':
                $banksettlementtype = $this->banksettlementtype();
                $impschargeupto25 = $this->impschargeupto25();
                $impschargeabove25 = $this->impschargeabove25();
                $neftcharge = $this->neftcharge(); 
                $payoutapi = $this->payoutapi(); 
                
                if($banksettlementtype == "down"){
                    return response()->json(['status' => "Aeps Settlement Down For Sometime"],400);
                }

                $user = User::where('id',\Auth::user()->id)->first();

                $post['user_id'] = \Auth::id();

                if($user->account == '' && $user->bank == '' && $user->ifsc == ''){
                    $rules = array(
                        'amount'    => 'required|numeric|min:10',
                        'account'   => 'sometimes|required',
                        'bank'   => 'sometimes|required',
                        'ifsc'   => 'sometimes|required'
                    );
                }else{
                    $rules = array(
                        'amount'    => 'required|numeric|min:10'
                    );

                    $post['account'] = $user->account;
                    $post['bank']    = $user->bank;
                    $post['ifsc']    = $user->ifsc;
                }

                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 422);
                }

                if($user->account == '' && $user->bank == '' && $user->ifsc == ''){
                    User::where('id',\Auth::user()->id)->update(['account' => $post->account, 'bank' => $post->bank, 'ifsc'=>$post->ifsc]);
                }

                $settlerequest = Microatmfundrequest::where('user_id', \Auth::user()->id)->where('status', 'pending')->count();
                if($settlerequest > 0){
                    return response()->json(['status'=> "One request is already submitted"], 400);
                }

                if($post->mode == "IMPS" && $post->amount <= 25000 && ($user->mainwallet < $post->amount + $impschargeupto25)){
                    return response()->json(['status'=>  "Low aeps balance to make this request."], 400);
                }elseif($post->mode == "IMPS" && $post->amount > 25000 && ($user->mainwallet > $post->amount + $impschargeabove25)){
                    return response()->json(['status'=>  "Low aeps balance to make this request."], 400);
                }elseif($post->mode == "NEFT" && ($user->mainwallet < $post->amount + $neftcharge)){
                    return response()->json(['status'=>  "Low aeps balance to make this request."], 400);
                }

                $post['charge'] = 0;
                if($post->mode == "IMPS" && $post->amount <= 25000){
                    $post['charge'] = $impschargeupto25;
                }

                if($post->mode == "IMPS" && $post->amount > 25000){
                    $post['charge'] = $impschargeabove25;
                }

                if($post->mode == "NEFT"){
                    $post['charge'] = $neftcharge;
                }

                if($banksettlementtype == "auto" && in_array($post->mode, ['IMPS', 'NEFT'])){

                    $previousrecharge = Microatmfundrequest::where('account', $post->account)->where('amount', $post->amount)->where('user_id', $post->user_id)->whereBetween('created_at', [Carbon::now()->subSeconds(30)->format('Y-m-d H:i:s'), Carbon::now()->addSeconds(30)->format('Y-m-d H:i:s')])->count();
                    if($previousrecharge){
                        return response()->json(['status'=> "Transaction Allowed After 1 Min."]);
                    } 
                    
                    $api = Api::where('code', 'ppayout')->first();

                    do {
                        $post['payoutid'] = $this->transcode().rand(111111111111, 999999999999);
                    } while (Microatmfundrequest::where("payoutid", "=", $post->payoutid)->first() instanceof Microatmfundrequest);

                    $post['status']   = "pending";
                    $post['pay_type'] = "payout";
                    $post['payoutid'] = $post->payoutid;
                    $post['payoutref']= $post->payoutid;
                    $post['create_time']= Carbon::now()->toDateTimeString();
                    try {
                        $aepsrequest = Microatmfundrequest::create($post->all());
                    } catch (\Exception $e) {
                        return response()->json(['status'=> "Duplicate Transaction Not Allowed, Please Check Transaction History"]);
                    }

                    $aepsreports['api_id'] = $api->id;
                    $aepsreports['payid']  = $aepsrequest->id;
                    $aepsreports['mobile'] = $user->mobile;
                    $aepsreports['refno']  = "success";
                    $aepsreports['number'] = $post->account;
                    $aepsreports['amount'] = $post->amount;
                    $aepsreports['charge'] = $post->charge;
                    $aepsreports['bank']   = $post->bank."(".$post->ifsc.")";
                    $aepsreports['txnid']  = $post->payoutid;
                    $aepsreports['user_id']= $user->id;
                    $aepsreports['credited_by'] = $this->admin->id;
                    $aepsreports['balance']     = $user->mainwallet;
                    $aepsreports['type']        = "debit";
                    $aepsreports['transtype']   = 'fund';
                    $aepsreports['status'] = 'success';
                    $aepsreports['remark'] = "Bank Settlement";

                    User::where('id', $aepsreports['user_id'])->decrement('mainwallet',$aepsreports['amount']+$aepsreports['charge']);
                    $myaepsreport = Report::create($aepsreports);

                    $url = $api->url."/bpay/api/v1/disburse/order/bank";
                    $parameter = [
                        "orderId" => $request->payoutid,
                        "subwalletGuid" => $api->optional1,
                        "amount" => $request->amount, 
                        "beneficiaryAccount" => $request->account,
                        "beneficiaryIFSC" => $request->ifsc,
                        'transferMode' => $request->mode,
                        "purpose" => "OTHERS",
                        "callbackUrl" => "https://nikatby.co.in/api/callback/update/mpayout" ,
                        "comments" => "comment",
                        "date" => date('Y-m-d')
                    ];

                    $body= json_encode($parameter, true);
                    $checksum = \Paytm::getChecksumFromString($body, $api->password);
                    $header = array("Content-Type: application/json", "x-mid: ".$api->username, "x-checksum: ".$checksum);

                    if(env('APP_ENV') != "local"){
                        $result = \Myhelper::curl($url, 'POST', $body, $header, 'yes', 'App\Model\Aepsfundrequest',$request->payoutid);
                    }else{
                        $result = [
                            'error'    => true,
                            'response' => ''
                        ];
                    }

                    if($result['response'] == ''){
                        return response()->json(['status'=> "success"]);
                    }

                    $response = json_decode($result['response']);
                    if(isset($response->status) && in_array(strtolower($response->status), ['success', 'pending', 'accepted'])){
                        Microatmfundrequest::where('id', $aepsrequest->id)->update(['status' => "accepted"]);
                        return response()->json(['status' => "success", "message" => "Aeps fund request submitted successfully", "txnid" => $aepsrequest->id],200);
                    }else{
                        User::where('id', $aepsreports['user_id'])->increment('mainwallet',$aepsreports['amount']+$aepsreports['charge']);
                        Report::where('id', $aepsreportstxn->id)->update(['status' => "failed"]);
                    
                        Microatmfundrequest::where('id', $aepsrequest->id)->update(['status' => "rejected"]);
                        return response()->json(['status' =>  $response->message], 400);
                    }
                }else{
                    $post['pay_type'] = "manual";
                    $request = Microatmfundrequest::create($post->all());
                }

                if($request){
                    return response()->json(['status'=>"success", 'message' => "Fund request successfully submitted"], 200);
                }else{
                    return response()->json(['status'=>"ERR", 'message' => "Something went wrong."], 400);
                }
                break;

            case 'matmwallet':
                if(!\Myhelper::can('aeps_fund_request')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }
                $settlementtype = $this->settlementtype();

                if($settlementtype == "down"){
                    return response()->json(['status' => "Aeps Settlement Down For Sometime"],400);
                }

                $rules = array(
                    'amount'    => 'required|numeric|min:1',
                );
        
                $validator = \Validator::make($post->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors'=>$validator->errors()], 422);
                }

                $user = User::where('id',\Auth::user()->id)->first();

                $request = Microatmfundrequest::where('user_id', \Auth::user()->id)->where('status', 'pending')->count();
                if($request > 0){
                    return response()->json(['status'=> "One request is already submitted"], 400);
                }

                if(\Auth::user()->mainwallet < $post->amount){
                    return response()->json(['status'=>  "Low aeps balance to make this request"], 400);
                }

                $post['user_id'] = \Auth::id();

                if($settlementtype == "auto"){
                    $previousrecharge = Microatmfundrequest::where('type', $post->type)->where('amount', $post->amount)->where('user_id', $post->user_id)->whereBetween('created_at', [Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')])->count();
                    if($previousrecharge > 0){
                        return response()->json(['status'=> "Transaction Allowed After 5 Min."]);
                    }

                    $post['status'] = "approved";
                    $load  = Microatmfundrequest::create($post->all());
                    $payee = User::where('id', \Auth::id())->first();
                    User::where('id', $payee->id)->decrement('mainwallet', $post->amount);
                    $inserts = [
                        "mobile"  => $payee->mobile,
                        "amount"  => $post->amount,
                        "bank"    => $payee->bank,
                        'txnid'   => date('ymdhis'),
                        'refno'   => $post->refno,
                        "user_id" => $payee->id,
                        "credited_by" => $user->id,
                        "balance"     => $payee->mainwallet,
                        'trans_type'        => "debit",
                        'aepstype'   => 'MICROATMFUND',
                        'status'      => 'success',
                        'remark'      => "Move To Wallet Request",
                        'payid'       => "Wallet Transfer Request",
                        'number'      => $payee->account
                    ];

                    Report::create($inserts);

                    if($post->type == "matmwallet"){
                        $provide = Provider::where('recharge1', 'aepsfund')->first();
                        User::where('id', $payee->id)->increment('mainwallet', $post->amount);
                        $insert = [
                            'number' => $payee->account,
                            'mobile' => $payee->mobile,
                            'provider_id' => $provide->id,
                            'api_id' => $this->fundapi->id,
                            'amount' => $post->amount,
                            'charge' => '0.00',
                            'profit' => '0.00',
                            'gst' => '0.00',
                            'tds' => '0.00',
                            'txnid' => $load->id,
                            'payid' => $load->id,
                            'refno' => $post->refno,
                            'description' =>  "MicroAtm Fund Recieved",
                            'remark' => $post->remark,
                            'option1' => $payee->name,
                            'status' => 'success',
                            'user_id' => $payee->id,
                            'credit_by' => $payee->id,
                            'rtype' => 'main',
                            'via' => 'portal',
                            'balance' => $payee->mainwallet,
                            'trans_type' => 'credit',
                            'product' => "fund request"
                        ];

                        Report::create($insert);
                    }
                }else{
                    $load = Microatmfundrequest::create($post->all());
                }

                if($load){
                    return response()->json(['status' => "success"],200);
                }else{
                    return response()->json(['status' => "fail"],200);
                }
                break;
                
            case 'aepstransfer':
                if(\Myhelper::hasNotRole('admin')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }

                $user = User::where('id',\Auth::user()->id)->first();
                
                $request = Aepsfundrequest::find($post->id);
                $payee   = User::where('id', $request->user_id)->first();
                $post['charge'] = 0;
                if($request->type == "bank"){
                    $post['charge'] = $this->neftcharge();
                }
                
                if($payee->mainwallet < $request->amount + $post->charge){
                    return response()->json(['status' => "Insufficient Aeps Wallet Balance"],400);
                }
                
                $action  = Aepsfundrequest::where('id', $post->id)->update(['status'=>$post->status, 'remark'=> $post->remark]);
                if($action){
                    if($post->status == "approved" && $request->status == "pending"){
                        User::where('id', $payee->id)->decrement('mainwallet', $request->amount + $post->charge);

                        $inserts = [
                            "mobile"  => $payee->mobile,
                            "amount"  => $request->amount,
                            "charge"  => $post->charge,
                            "bank"    => $payee->bank,
                            'txnid'   => $request->id,
                            'refno'   => $post->refno,
                            "user_id" => $payee->id,
                            "credited_by" => $user->id,
                            "balance"     => $payee->mainwallet,
                            'type'        => "debit",
                            'transtype'   => 'fund',
                            'status'      => 'success',
                            'remark'      => "Move To ".ucfirst($request->type)." Request",
                        ];

                        if($request->type == "wallet"){
                            $inserts['payid'] = "Wallet Transfer Request";
                            $inserts["number"]= $payee->number;
                        }else{
                            $inserts['payid'] = $payee->bank." ( ".$payee->ifsc." )";
                            $inserts['number'] = $payee->account;
                        }

                        Report::create($inserts);

                        if($request->type == "wallet"){
                            $provide = Provider::where('recharge1', 'aepsfund')->first();
                            User::where('id', $payee->id)->increment('mainwallet', $request->amount);
                            $insert = [
                                'number' => $payee->mobile,
                                'mobile' => $payee->mobile,
                                'provider_id' => $provide->id,
                                'api_id' => $this->fundapi->id,
                                'amount' => $request->amount,
                                'charge' => '0.00',
                                'profit' => '0.00',
                                'gst' => '0.00',
                                'tds' => '0.00',
                                'txnid' => $request->id,
                                'payid' => $request->id,
                                'refno' => $post->refno,
                                'description' =>  "Aeps Fund Recieved",
                                'remark' => $post->remark,
                                'option1' => $payee->name,
                                'status' => 'success',
                                'user_id' => $payee->id,
                                'credit_by' => $user->id,
                                'rtype' => 'main',
                                'via' => 'portal',
                                'balance' => $payee->mainwallet,
                                'trans_type' => 'credit',
                                'product' => "fund request"
                            ];

                            Report::create($insert);
                        }
                    }
                    return response()->json(['status'=> "success"], 200);
                }else{
                    return response()->json(['status'=> "fail"], 400);
                }

                break;

            case 'microatmtransfer':
                if(\Myhelper::hasNotRole('admin')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }

                $user = User::where('id',\Auth::user()->id)->first();
                if($request->type == "matmbank"){
                    $post['charge'] = $this->neftcharge();
                }

                $request = Microatmfundrequest::find($post->id);
                $action  = Microatmfundrequest::where('id', $post->id)->update(['status'=>$post->status, 'remark'=> $post->remark]);
                $payee   = User::where('id', $request->user_id)->first();
                
                if($payee->mainwallet < $request->amount + $post->charge){
                    return response()->json(['status' => "Insufficient Aeps Wallet Balance"],400);
                }
                
                if($action){
                    if($post->status == "approved" && $request->status == "pending"){
                        
                        User::where('id', $payee->id)->decrement('mainwallet', $request->amount + $post->charge);

                        $inserts = [
                            "mobile"  => $payee->mobile,
                            "amount"  => $request->amount,
                            "charge"  => $post->charge,
                            "bank"    => $payee->bank,
                            'txnid'   => $request->id,
                            'refno'   => $post->refno,
                            "user_id" => $payee->id,
                            "credited_by" => $user->id,
                            "balance"     => $payee->mainwallet,
                            'type'        => "debit",
                            'transtype'   => 'fund',
                            'status'      => 'success',
                            'remark'      => "Move To ".ucfirst($request->type)." Request",
                        ];

                        if($request->type == "matmwallet"){
                            $inserts['payid'] = "Wallet Transfer Request";
                            $inserts["number"]= $payee->number;
                        }else{
                            $inserts['payid'] = $payee->bank." ( ".$payee->ifsc." )";
                            $inserts['number'] = $payee->account;
                        }

                        Report::create($inserts);

                        if($request->type == "matmwallet"){
                            $provide = Provider::where('recharge1', 'aepsfund')->first();
                            User::where('id', $payee->id)->increment('mainwallet', $request->amount);
                            $insert = [
                                'number' => $payee->mobile,
                                'mobile' => $payee->mobile,
                                'provider_id' => $provide->id,
                                'api_id' => $this->fundapi->id,
                                'amount' => $request->amount,
                                'charge' => '0.00',
                                'profit' => '0.00',
                                'gst' => '0.00',
                                'tds' => '0.00',
                                'txnid' => $request->id,
                                'payid' => $request->id,
                                'refno' => $post->refno,
                                'description' =>  "MicroAtm Fund Recieved",
                                'remark' => $post->remark,
                                'option1' => $payee->name,
                                'status' => 'success',
                                'user_id' => $payee->id,
                                'credit_by' => $user->id,
                                'rtype' => 'main',
                                'via' => 'portal',
                                'balance' => $payee->mainwallet,
                                'trans_type' => 'credit',
                                'product' => "fund request"
                            ];

                            Report::create($insert);
                        }
                    }
                    return response()->json(['status'=> "success"], 200);
                }else{
                    return response()->json(['status'=> "fail"], 400);
                }

                break;
            
            case 'loadwallet':
                if(\Myhelper::hasNotRole('admin')){
                    return response()->json(['status' => "Permission not allowed"],400);
                }
                $action = User::where('id', \Auth::id())->increment('mainwallet', $post->amount);
                if($action){
                    $insert = [
                        'number' => \Auth::user()->mobile,
                        'mobile' => \Auth::user()->mobile,
                        'provider_id' => $post->provider_id,
                        'api_id' => $this->fundapi->id,
                        'amount' => $post->amount,
                        'charge' => '0.00',
                        'profit' => '0.00',
                        'gst' => '0.00',
                        'tds' => '0.00',
                        'apitxnid' => NULL,
                        'txnid' => date('ymdhis'),
                        'payid' => NULL,
                        'refno' => NULL,
                        'description' => NULL,
                        'remark' => $post->remark,
                        'option1' => NULL,
                        'option2' => NULL,
                        'option3' => NULL,
                        'option4' => NULL,
                        'status' => 'success',
                        'user_id' => \Auth::id(),
                        'credit_by' => \Auth::id(),
                        'rtype' => 'main',
                        'via' => 'portal',
                        'adminprofit' => '0.00',
                        'balance' => \Auth::user()->mainwallet,
                        'trans_type' => 'credit',
                        'product' => "fund ".$post->type
                    ];
                    $action = Report::create($insert);
                    if($action){
                        return response()->json(['status' => "success"], 200);
                    }else{
                        return response()->json(['status' => "Technical error, please contact your service provider before doing transaction."],400);
                    }
                }else{
                    return response()->json(['status' => "Fund transfer failed, please try again."],400);
                }
                break;
            
            default:
                # code...
                break;
        }
    }

    public function paymentAction($post)
    {
        $user = User::where('id', $post->user_id)->first();

        if($post->type == "transfer" || $post->type == "request"){
            $action = User::where('id', $post->user_id)->increment('mainwallet', $post->amount);
        }else{
            $action = User::where('id', $post->user_id)->decrement('mainwallet', $post->amount);
        }

        if($action){
            if($post->type == "transfer" || $post->type == "request"){
                $post['trans_type'] = "credit";
            }else{
                $post['trans_type'] = "debit";
            }

            $insert = [
                'number' => $user->mobile,
                'mobile' => $user->mobile,
                'provider_id' => $post->provider_id,
                'api_id' => $this->fundapi->id,
                'amount' => $post->amount,
                'charge' => '0.00',
                'profit' => '0.00',
                'gst' => '0.00',
                'tds' => '0.00',
                'apitxnid' => NULL,
                'txnid' => $post->txnid,
                'payid' => NULL,
                'refno' => $post->refno,
                'description' => NULL,
                'remark' => $post->remark,
                'option1' => $post->option1,
                'option2' => $post->option2,
                'option3' => $post->option3,
                'option4' => NULL,
                'status' => 'success',
                'user_id' => $user->id,
                'credit_by' => \Auth::id(),
                'rtype' => 'main',
                'via' => 'portal',
                'adminprofit' => '0.00',
                'balance' => $user->mainwallet,
                'trans_type' => $post->trans_type,
                'product' => "fund ".$post->type
            ];
            $action = Report::create($insert);
            if($action){
                return $this->paymentActionCreditor($post);
            }else{
                return response()->json(['status' => "Technical error, please contact your service provider before doing transaction."],400);
            }
        }else{
            return response()->json(['status' => "Fund transfer failed, please try again."],400);
        }
    }

    public function paymentActionCreditor($post)
    {
        $payee = $post->user_id;
        $user = User::where('id', \Auth::id())->first();
        if($post->type == "transfer" || $post->type == "request"){
            $action = User::where('id', $user->id)->decrement('mainwallet', $post->amount);
        }else{
            $action = User::where('id', $user->id)->increment('mainwallet', $post->amount);
        }

        if($action){
            if($post->type == "transfer" || $post->type == "request"){
                $post['trans_type'] = "debit";
            }else{
                $post['trans_type'] = "credit";
            }

            $insert = [
                'number' => $user->mobile,
                'mobile' => $user->mobile,
                'provider_id' => $post->provider_id,
                'api_id' => $this->fundapi->id,
                'amount' => $post->amount,
                'charge' => '0.00',
                'profit' => '0.00',
                'gst' => '0.00',
                'tds' => '0.00',
                'apitxnid' => NULL,
                'txnid' => $post->txnid,
                'payid' => NULL,
                'refno' => $post->refno,
                'description' => NULL,
                'remark' => $post->remark,
                'option1' => $post->option1,
                'option2' => $post->option2,
                'option3' => $post->option3,
                'option4' => NULL,
                'status' => 'success',
                'user_id' => $user->id,
                'credit_by' => $payee,
                'rtype' => 'main',
                'via' => 'portal',
                'adminprofit' => '0.00',
                'balance' => $user->mainwallet,
                'trans_type' => $post->trans_type,
                'product' => "fund ".$post->type
            ];

            $action = Report::create($insert);
            if($action){
                return response()->json(['status' => "success"], 200);
            }else{
                return response()->json(['status' => "Technical error, please contact your service provider before doing transaction."],400);
            }
        }else{
            return response()->json(['status' => "Technical error, please contact your service provider before doing transaction."],400);
        }
    }
     public function getGst($amount)
    {
        return $amount*18/100;
    }
}
