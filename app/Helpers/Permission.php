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

    public static function mail($view, $data, $mailto, $name, $mailvia, $namevia, $subject)
    {
        \Mail::send($view, $data, function($message) use($mailto, $name, $mailvia, $namevia, $subject) {
            $message->to($mailto, $name)->subject($subject);
            $message->from($mailvia, $namevia);
        });

        if (\Mail::failures()) {
            return "fail";
        }
        return "success";
    }

    public static function sms($mobile, $content)
    {
        $smsdata = \App\Model\Company::where('website', $_SERVER['HTTP_HOST'])->first();
        if(isset($smsdata->senderid)){
            $url = "http://securesms.co.in/vendorsms/pushsms.aspx?user=".$smsdata->smsuser."&password=".$smsdata->smspwd."&msisdn=".$mobile."&sid=".$smsdata->senderid."&msg=".urlencode($content)."&fl=0&gwid=2";

            $result = \Myhelper::curl($url, "GET", "", [], "no", "", "");
            if($result['response'] != ''){
                $response = json_decode($result['response']);
                if ($response->ErrorCode == "000") {
                    return "success";
                }
            }
        }
        return "fail";
    }

    public static function commission($report)
    {
        if(in_array($report->apicode, ['aeps', 'kaeps'])){
            $insert = [
                'number'  => $report->aadhar,
                'mobile'  => $report->mobile,
                'provider_id' => $report->provider_id,
                'api_id'  => $report->api_id,
                'txnid'   => $report->id,
                'payid'   => $report->payid,
                'refno'   => $report->refno,
                'status'  => 'success',
                'rtype'   => 'commission',
                'trans_type' => "credit",
                'via'     => "portal",
                'product' => "aeps",
                'provider_id'=> $report->provider_id
            ];
            
            $provider = $report->provider_id;
            $precommission = $report->charge;
        }else{
            $myreport = Report::where('id', $report->id)->first(['profit', 'gst']);
            $insert = [
                'number' => $report->number,
                'mobile' => $report->mobile,
                'provider_id' => $report->provider_id,
                'api_id' => $report->api_id,
                'txnid'  => $report->id,
                'payid'  => $report->payid,
                'refno'  => $report->refno,
                'status' => 'success',
                'rtype'  => 'commission',
                'via'    => $report->via,
                'trans_type' => "credit",
                'product' => $report->product
            ];
            if($report->product == "dmt"){
                $precommission = $report->charge - $myreport->profit - $myreport->gst;
            }elseif($report->product == "nsdlpan"){
                $precommission = $report->amount;
            }else{
                $precommission = $report->profit;
            }
            $provider = $report->provider_id;
        }

        $parent = User::where('id', $report->user->parent_id)->first(['id', 'mainwallet', 'scheme_id', 'role_id', 'parent_id']);

        if($parent->role->slug == "distributor"){
            $insert['balance'] = $parent->mainwallet;
            $insert['user_id'] = $parent->id;
            $insert['credit_by'] = $report->user_id;
            $parentcommission = \Myhelper::getCommission($report->amount, $parent->scheme_id, $provider, 'distributor');

            if(in_array($report->product, ['recharge','billpay','aeps'])){
                $insert['amount'] = $parentcommission - $precommission;
            }elseif($report->product == "utipancard"){
                $insert['amount'] = $report->option1 * $parentcommission - $precommission;
            }elseif($report->product == "dmt"){
                $insert['amount'] = $precommission - $parentcommission;
            }

            User::where('id', $parent->id)->increment('mainwallet', $insert['amount']);
            Report::create($insert);
            if(in_array($report->apicode, ['aeps', 'kaeps'])){
                Aepsreport::where('id', $report->id)->update(['disid' => $parent->id, "disprofit" => $insert['amount']]);
            }else{
                Report::where('id', $report->id)->update(['disid' => $parent->id, "disprofit" => $insert['amount']]);
            }

            if(in_array($report->product, ['recharge','billpay','dmt','aeps'])){
                $precommission = $parentcommission;
            }elseif($report->product == "utipancard"){
                $precommission = $report->option1 * $parentcommission;
            }

            $parent = User::where('id', $parent->parent_id)->first(['id', 'mainwallet', 'scheme_id', 'role_id', 'parent_id']);
        }

        if($parent->role->slug == "md"){
            $insert['balance'] = $parent->mainwallet;
            $insert['user_id'] = $parent->id;
            $insert['credit_by'] = $report->user_id;
            $parentcommission = \Myhelper::getCommission($report->amount, $parent->scheme_id, $provider, 'md');

            if(in_array($report->product, ['recharge','billpay','aeps'])){
                $insert['amount'] = $parentcommission - $precommission;
            }elseif($report->product == "utipancard"){
                $insert['amount'] = $report->option1 * $parentcommission - $precommission;
            }elseif($report->product == "dmt"){
                $insert['amount'] = $precommission - $parentcommission;
            }

            User::where('id', $parent->id)->increment('mainwallet', $insert['amount']);
            Report::create($insert);
            if(in_array($report->apicode, ['aeps', 'kaeps'])){
                Aepsreport::where('id', $report->id)->update(['mdid' => $parent->id, "mdprofit" => $insert['amount']]);
            }else{
                Report::where('id', $report->id)->update(['mdid' => $parent->id, "mdprofit" => $insert['amount']]);
            }

            if(in_array($report->product, ['recharge','billpay','dmt','aeps'])){
                $precommission = $parentcommission;
            }elseif($report->product == "utipancard"){
                $precommission = $report->option1 * $parentcommission;
            }
            $parent = User::where('id', $parent->parent_id)->first(['id', 'mainwallet', 'scheme_id', 'role_id', 'parent_id']);
        }

        if($parent->role->slug == "whitelable"){
            $insert['balance'] = $parent->mainwallet;
            $insert['user_id'] = $parent->id;
            $insert['credit_by'] = $report->user_id;

            $parentcommission = \Myhelper::getCommission($report->amount, $parent->scheme_id, $provider, 'whitelable');

            if(in_array($report->product, ['recharge','billpay','aeps'])){
                $insert['amount'] = $parentcommission - $precommission;
            }elseif($report->product == "utipancard"){
                $insert['amount'] = $report->option1 * $parentcommission - $precommission;
            }elseif($report->product == "dmt"){
                $insert['amount'] = $precommission - $parentcommission;
            }
            
            User::where('id', $parent->id)->increment('mainwallet', $insert['amount']);
            Report::create($insert);
            if(in_array($report->apicode, ['aeps', 'kaeps'])){
                Aepsreport::where('id', $report->id)->update(['wid' => $parent->id, "wprofit" => $insert['amount']]);
            }else{
                Report::where('id', $report->id)->update(['wid' => $parent->id, "wprofit" => $insert['amount']]);
            }
        }
    }

    public static function getCommission($amount, $scheme, $slab, $role)
    {
        $schememanager = \DB::table('portal_settings')->where('code', 'schememanager')->first(['value']);
        if($schememanager->value != "all"){
            $myscheme = Scheme::where('id', $scheme)->first(['status']);
            if($myscheme && $myscheme->status == "1"){
                $comdata = Commission::where('scheme_id', $scheme)->where('slab', $slab)->first();
                if ($comdata) {
                        switch ($role) {
                            case 'whitelable':
                                if ($comdata->type == "percent") {
                                    $commission = $amount * $comdata->whitelable / 100;
                                }else{
                                    $commission = $comdata->whitelable;
                                }
                                break;

                            case 'md':
                                if ($comdata->type == "percent") {
                                    $commission = $amount * $comdata->md / 100;
                                }else{
                                    $commission = $comdata->md;
                                }
                                break;

                            case 'distributor':
                                if ($comdata->type == "percent") {
                                    $commission = $amount * $comdata->distributor / 100;
                                }else{
                                    $commission = $comdata->distributor;
                                }
                                break;

                            case 'retailer':
                                if ($comdata->type == "percent") {
                                    $commission = $amount * $comdata->retailer / 100;
                                }else{
                                    $commission = $comdata->retailer;
                                }
                                break;
                            
                            default:
                                $commission = 0;
                                break;
                        }
                    if($commission == null){
                        $commission = 0;
                    }
                }else{
                    $commission = 0;
                }
            }else{
                $commission = 0;
            }
        }else{
            $myscheme = Package::where('id', $scheme)->first(['status']);
            if($myscheme && $myscheme->status == "1"){
                $comdata = Packagecommission::where('scheme_id', $scheme)->where('slab', $slab)->first();
                if ($comdata) {
                    if ($comdata->type == "percent") {
                        $commission = $amount * $comdata->value / 100;
                    }else{
                        $commission = $comdata->value;
                    }
                }else{
                    $commission = 0;
                }
            }else{
                $commission = 0;
            }
        }
        return $commission;
    }

    public static function curl($url , $method='GET', $parameters, $header, $log="no", $modal="none", $txnid="none")
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_TIMEOUT, 180);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        if($parameters != ""){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        }

        if(sizeof($header) > 0){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if($log != "no"){
            Apilog::create([
                "url" => $url,
                "modal" => $modal,
                "txnid" => $txnid,
                "header" => $header,
                "request" => $parameters,
                "response" => $response
            ]);
        }

        return ["response" => $response, "error" => $err, 'code' => $code];
    }

    public static function getParents($id)
    {
        $data = [];
        $user = User::where('id', $id)->first(['id', 'role_id']);
        if($user){
            $data[] = $id;
            switch ($user->role->slug) {
                case 'superadmin':
                    $whitelabels = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'whitelable');
                    })->get(['id']);

                    if(sizeOf($whitelabels) > 0){
                        foreach ($whitelabels as $value) {
                            $data[] = $value->id;
                        }
                    }

                    $mds = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'md');
                    })->get(['id']);

                    if(sizeOf($mds) > 0){
                        foreach ($mds as $value) {
                            $data[] = $value->id;
                        }
                    }
                    
                    $distributors = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'distributor');
                    })->get(['id']);

                    if(sizeOf($distributors) > 0){
                        foreach ($distributors as $value) {
                            $data[] = $value->id;
                        }
                    }
                    
                    $retailers = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->whereIn('slug', ['retailer', 'apiuser']);
                    })->get(['id']);

                    if(sizeOf($retailers) > 0){
                        foreach ($retailers as $value) {
                            $data[] = $value->id;
                        }
                    }
                    break;
                    
                case 'whitelable':
                    $mds = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'md');
                    })->get(['id']);

                    if(sizeOf($mds) > 0){
                        foreach ($mds as $value) {
                            $data[] = $value->id;
                        }
                    }
                    
                    $distributors = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'distributor');
                    })->get(['id']);

                    if(sizeOf($distributors) > 0){
                        foreach ($distributors as $value) {
                            $data[] = $value->id;
                        }
                    }
                    
                    $retailers = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'retailer');
                    })->get(['id']);

                    if(sizeOf($retailers) > 0){
                        foreach ($retailers as $value) {
                            $data[] = $value->id;
                        }
                    }
                    break;
                
                case 'md':                
                    $distributors = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'distributor');
                    })->get(['id']);

                    if(sizeOf($distributors) > 0){
                        foreach ($distributors as $value) {
                            $data[] = $value->id;
                        }
                    }
                    
                    $retailers = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'retailer');
                    })->get(['id']);

                    if(sizeOf($retailers) > 0){
                        foreach ($retailers as $value) {
                            $data[] = $value->id;
                        }
                    }
                    break;
                
                case 'distributor':                
                    $retailers = \App\User::whereIn('parent_id', $data)->whereHas('role', function($q){
                        $q->where('slug', 'retailer');
                    })->get(['id']);

                    if(sizeOf($retailers) > 0){
                        foreach ($retailers as $value) {
                            $data[] = $value->id;
                        }
                    }
                    break;
            }
        }
        return $data;
    }
    
    public static function transactionRefund($id)
    {
        $report = Report::where('id', $id)->first();
        $count  = Report::where('user_id', $report->user_id)->where('status', 'refunded')->where('txnid', $report->id)->count();
        if($count == 0){
            $user = User::where('id', $report->user_id)->first(['id', 'mainwallet']);
            if($report->trans_type == "debit"){
                User::where('id', $report->user_id)->increment('mainwallet',$report->amount + $report->charge - $report->profit);
            }else{
                User::where('id', $report->user_id)->decrement('mainwallet',$report->amount + $report->charge - $report->profit);
            }
            $insert = [
                'number' => $report->number,
                'mobile' => $report->mobile,
                'provider_id' => $report->provider_id,
                'api_id' => $report->api_id,
                'apitxnid' => $report->apitxnid,
                'txnid' => $report->id,
                'payid' => $report->payid,
                'refno' => $report->refno,
                'description' => "Transaction Reversed, amount refunded",
                'remark' => $report->remark,
                'option1' => $report->option1,
                'option2' => $report->option2,
                'option3' => $report->option3,
                'option4' => $report->option3,
                'status' => 'refunded',
                'rtype' => $report->rtype,
                'via' => $report->via,
                'trans_type' => ($report->trans_type == "credit") ? "debit" : "credit",
                'product' => $report->product,
                'amount' => $report->amount,
                'profit' => $report->profit,
                'charge' => $report->charge,
                'gst' => $report->gst,
                'tds' => $report->tds,
                'balance' => $user->mainwallet,
                'user_id' => $report->user_id,
                'credit_by' => $report->credit_by,
                'adminprofit' => $report->adminprofit
            ];
            Report::create($insert);

            $commissionReports = Report::where('rtype', 'commission')->where('txnid', $report->id)->get();
            foreach ($commissionReports as $report) {
                $user = User::where('id', $report->user_id)->first(['id', 'mainwallet']);

                if($report->trans_type == "debit"){
                    User::where('id', $report->user_id)->increment('mainwallet', $report->amount - $report->profit);
                }else{
                    User::where('id', $report->user_id)->decrement('mainwallet', $report->amount - $report->profit);
                }
                
                $insert = [
                    'number' => $report->number,
                    'mobile' => $report->mobile,
                    'provider_id' => $report->provider_id,
                    'api_id' => $report->api_id,
                    'apitxnid' => $report->apitxnid,
                    'txnid' => $report->id,
                    'payid' => $report->payid,
                    'refno' => $report->refno,
                    'description' => "Transaction Reversed, amount refunded",
                    'remark' => $report->remark,
                    'option1' => $report->option1,
                    'option2' => $report->option2,
                    'option3' => $report->option3,
                    'option4' => $report->option3,
                    'status' => 'refunded',
                    'rtype' => $report->rtype,
                    'via' => $report->via,
                    'trans_type' => ($report->trans_type == "credit") ? "debit" : "credit",
                    'product' => $report->product,
                    'amount' => $report->amount,
                    'profit' => $report->profit,
                    'charge' => $report->charge,
                    'gst' => $report->gst,
                    'tds' => $report->tds,
                    'balance' => $user->mainwallet,
                    'user_id' => $report->user_id,
                    'credit_by' => $report->credit_by,
                    'adminprofit' => $report->adminprofit
                ];
                Report::create($insert);
            }
        }
    }

    public static function getTds($amount)
    {
        return $amount*5/100;
    }

    public static function callback($report, $product)
    {
        switch ($product) {
            case 'utipancard':
            case 'recharge':
                $report = Report::where('id', $report->id)->first();
                $apitxnid = $report->apitxnid;
                $refno = $report->refno;
                break;

            case 'utiid':
                $report = Utiid::where('id', $report->id)->first();
                $apitxnid = $report->vleid;
                $refno = $report->remark;
                break;
        }

        if($report->status == "success"){
            $status = "success";
        }elseif($report->status == "reversed"){
            $status = "failed";
        }else{
            $status = "unknown";
        }
        

        if($status != "unknown"){
            $url = $report->user->callbackurl."?txnid=".$apitxnid."&status=".$report->status."&refno=".$refno;
            $result = \Myhelper::curl($url, "GET", "", [], "no", "", "");
            Callbackresponse::create([
                'url' => $url,
                'response' => ($result['response'] != '') ? $result['response'] : $result['error'],
                'status' => $result['code'],
                'product' => $product,
                'user_id' => $report->user_id,
                'transaction_id' => $report->id
            ]);
        }
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