<?php
namespace App\Helpers;

use Cookie;
use Illuminate\Support\Facades\Storage;
use App\Models\Emailtemplate;
use App\Models\User;
use App\Models\Job;
use App\Models\Skill;
use App\Models\Location;
use App\Models\Userworklocationtown;
use App\Models\Userworklocationsuburb;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactAdmin;
use App\Models\Userrating;
use App\Models\Notification;
use App\Models\Token;
use App\Models\Usernotificationsetting;
use App\Services\SmsSender;

class PushNotification
{

    public static function sendUseracceptedPushNotificationToWorker($job,$action,$from_user,$id)
    {
        $device_token_android=$device_token_iphone=array();
        $ij=$mn=0;
        $body_a=array();
        $message=self::getUsershortlistedNotificationMessage($action,$from_user,$job,time());


        $body_a['data']['message'] = $message;
        $body_a['data']['job_id'] = $job->id ?? 0;
        $body_a['data']['worker_id'] = $from_user->id;


        $body_a['data']['type'] = 1;
        $body_a['data']['badge'] = 1;
        $body_a['data']['sound'] = 'default';
        $body_a['data']['message'] = $message;
        $body_a['data']['alert'] = $message;
        $body_a['data']['text'] = $message;
        $body_a['data']['title'] = $message;


        $notification = new Notification();
        $notification->to_type =3;//2= employee
        $notification->to_id =$id;
        $notification->message =$message;
        $notification->type =1;//1= job
        $notification->job_id =$job->id ?? 0;
        $notification->worker_id =$id;
        $notification->i_by =$from_user->id;
        $notification->i_date =date('Y-m-d H:i:s',time());
        $notification->u_by =$from_user->id;
        $notification->u_date =date('Y-m-d H:i:s',time());
        $notification->save();


        $uDeviceDetail_data = Token::where(['user_id'=>$id])->orderby("last_login",'desc')->get();
        if(isset($uDeviceDetail_data) && $uDeviceDetail_data!=array())
        {
            foreach($uDeviceDetail_data as $uDeviceDetail)
            {
                if(isset($uDeviceDetail->device_id) && $uDeviceDetail->device_id!=null && isset($uDeviceDetail->device_type) && $uDeviceDetail->device_type!=null)
                {
                    if($uDeviceDetail->device_type=='a'|| $uDeviceDetail->device_type=='A')
                    {
                        if(isset($device_token_android[$ij]) && count($device_token_android[$ij]) == 500)
                            $ij++;
                        $device_token_android[$ij][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                    if($uDeviceDetail->device_type=='i'|| $uDeviceDetail->device_type=='I')
                    {
                        if(isset($device_token_iphone[$mn]) && count($device_token_iphone[$mn]) == 500)
                            $mn++;
                        $device_token_iphone[$mn][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                }
            }
        }


        if(isset($device_token_android) && $device_token_android != array())
            self::pushnotification_android_array($device_token_android,$body_a);

        if(isset($device_token_iphone) && $device_token_iphone != array())
            self::pushnotification_iphone_array($device_token_iphone,$body_a);
    }

    //********************************************************************************
    //Title : send Job action Push Notification To Employee
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 21-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendUsershortlistedPushNotificationToWorker($job,$action,$from_user,$id,$date=null)
    {
        $device_token_android=$device_token_iphone=array();
        $ij=$mn=0;
        $body_a=array();
        $message=self::getUsershortlistedNotificationMessage($action,$from_user,$job,$date);
        

        $type = 9;
        if($action == 1)
          $type = 7;
        if($action == 2)
          $type = 8;
        if($action == 3)
          $type = 7;
        if($action == 4)
          $type = 10;

        $body_a['data']['message'] = $message;
        $body_a['data']['job_id'] = $job->id ?? 0;
        $body_a['data']['type'] = $type;
        $body_a['data']['worker_id'] = $id;
        $body_a['data']['badge'] = 1;
        $body_a['data']['sound'] = 'default';
        $body_a['data']['message'] = $message;
        $body_a['data']['alert'] = $message;
        $body_a['data']['text'] = $message;
        $body_a['data']['title'] = $message;

        $notification = new Notification();
        $notification->to_type =3;
        $notification->to_id =$id;
        $notification->message =$message;
        $notification->type =$type;
        $notification->job_id =$job->id ?? 0;
        $notification->worker_id =$id;
        $notification->i_by =$from_user->id;
        $notification->i_date =date('Y-m-d H:i:s',time());
        $notification->u_by =$from_user->id;
        $notification->u_date =date('Y-m-d H:i:s',time());
        $notification->save();


        $uDeviceDetail_data = Token::where(['user_id'=>$id])->orderby("last_login",'desc')->get();
        if(isset($uDeviceDetail_data) && $uDeviceDetail_data!=array())
        {
            foreach($uDeviceDetail_data as $uDeviceDetail)
            {
                if(isset($uDeviceDetail->device_id) && $uDeviceDetail->device_id!=null && isset($uDeviceDetail->device_type) && $uDeviceDetail->device_type!=null)
                {
                    if($uDeviceDetail->device_type=='a'|| $uDeviceDetail->device_type=='A')
                    {
                        if(isset($device_token_android[$ij]) && count($device_token_android[$ij]) == 500)
                            $ij++;
                        $device_token_android[$ij][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                    if($uDeviceDetail->device_type=='i'|| $uDeviceDetail->device_type=='I')
                    {
                        if(isset($device_token_iphone[$mn]) && count($device_token_iphone[$mn]) == 500)
                            $mn++;
                        $device_token_iphone[$mn][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                }
            }
        }


        if(isset($device_token_android) && $device_token_android != array())
            self::pushnotification_android_array($device_token_android,$body_a);

        if(isset($device_token_iphone) && $device_token_iphone != array())
            self::pushnotification_iphone_array($device_token_iphone,$body_a);
    }
    
    
    //********************************************************************************
    //Title : send Job action Push Notification To Employee
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 21-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendotherUsershortlistedPushNotificationToemployeer($job,$action,$from_user,$id,$date=null,$worker_id=null)
    {
        $device_token_android=$device_token_iphone=array();
        $ij=$mn=0;
        $body_a=array();
        $worker_data = User::find($worker_id);
        //dd($job);
        $message=self::getUsershortlistedNotificationMessage($action,$worker_data,$job,$date);
        

        $type = 5;

        $body_a['data']['message'] = $message;
        $body_a['data']['job_id'] = $job->id ?? 0;
        $body_a['data']['type'] = $type;
        $body_a['data']['worker_id'] = $worker_id;
        $body_a['data']['badge'] = 1;
        $body_a['data']['sound'] = 'default';
        $body_a['data']['message'] = $message;
        $body_a['data']['alert'] = $message;
        $body_a['data']['text'] = $message;
        $body_a['data']['title'] = $message;

        $notification = new Notification();
        $notification->to_type =2;
        $notification->to_id =$id;
        $notification->message =$message;
        $notification->type =$type;
        $notification->job_id =$job->id ?? 0;
        $notification->worker_id =$worker_id;
        $notification->i_by =$from_user->id;
        $notification->i_date =date('Y-m-d H:i:s',time());
        $notification->u_by =$from_user->id;
        $notification->u_date =date('Y-m-d H:i:s',time());
        $notification->save();


        $uDeviceDetail_data = Token::where(['user_id'=>$id])->orderby("last_login",'desc')->get();
        if(isset($uDeviceDetail_data) && $uDeviceDetail_data!=array())
        {
            foreach($uDeviceDetail_data as $uDeviceDetail)
            {
                if(isset($uDeviceDetail->device_id) && $uDeviceDetail->device_id!=null && isset($uDeviceDetail->device_type) && $uDeviceDetail->device_type!=null)
                {
                    if($uDeviceDetail->device_type=='a'|| $uDeviceDetail->device_type=='A')
                    {
                        if(isset($device_token_android[$ij]) && count($device_token_android[$ij]) == 500)
                            $ij++;
                        $device_token_android[$ij][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                    if($uDeviceDetail->device_type=='i'|| $uDeviceDetail->device_type=='I')
                    {
                        if(isset($device_token_iphone[$mn]) && count($device_token_iphone[$mn]) == 500)
                            $mn++;
                        $device_token_iphone[$mn][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                }
            }
        }


        if(isset($device_token_android) && $device_token_android != array())
            self::pushnotification_android_array($device_token_android,$body_a);

        if(isset($device_token_iphone) && $device_token_iphone != array())
            self::pushnotification_iphone_array($device_token_iphone,$body_a);
    }

    public static function getUsershortlistedNotificationMessage($type=null,$from_user=array(),$job=array(),$date)
    {
        
        $fun_result='';
        if(isset($type) && $type != null && $from_user != array() && $job!=array())
        {

            $tmp_str=config('params.employer_final_sortlisted_notification_to_worker');
            if($type==2)
              $tmp_str=config('params.employer_sortlisted_notification_to_worker');
            if($type==3)
              $tmp_str=config('params.employer_accepted_notification_to_worker');
            if($type==4)
                $tmp_str=config('params.employer_hired_notification_to_worker');
            if($type==6)
                $tmp_str=config('params.employer_hired_notification_to_other_employer');
                
                
            //dd(config('params.employer_hired_notification_to_other_employer'));

            $skill_name=$contract_name='';
            $worker_name=(isset($from_user->first_name) && $from_user->first_name!=null)?$from_user->first_name.' '.$from_user->second_name:'';

            $job_data = Job::
            leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
            ->selectRaw("jobs.id,jobs.contract_type,skills.name as skill_name")
            ->where('jobs.is_deleted',0)
            ->where('skills.is_deleted',0)
            ->where('jobs.id',$job->id)
            ->first();
            if(isset($job_data) && $job_data!=array())
            {
                $skill_name=(isset($job_data->skill_name) && $job_data->skill_name!='')?$job_data->skill_name:'';
                $contract_name=(isset($job_data->contract_type) && $job_data->contract_type!=null)?config('params.contract_type')[$job_data->contract_type]:'';
            }
            $tmp_str = str_replace('{user_name}',$worker_name,$tmp_str);
            $tmp_str = str_replace('{contract_type}',$contract_name,$tmp_str);
            $tmp_str = str_replace('{job_name}',$skill_name,$tmp_str);
            $tmp_str = str_replace('{date}',$date,$tmp_str);
            $fun_result=$tmp_str;
        }
        else
        {
            if($job==array() && $type==5)
            {
                $tmp_str=config('params.employer_final_sortlisted_notification_to_worker_without_job');
                $worker_name=(isset($from_user->first_name) && $from_user->first_name!=null)?$from_user->first_name.' '.$from_user->second_name:'';
                $tmp_str = str_replace('{user_name}',$worker_name,$tmp_str);
                $fun_result=$tmp_str;
            }
        }
        return $fun_result;
    }


    public static function sendJobactionPushNotificationToEmployee($job,$action,$from_user)
    {
        $device_token_android=$device_token_iphone=array();
        $ij=$mn=0;
        $body_a=array();
        $message=self::getNotificationMessage($action,$from_user,$job);
        //James Holland has requested Full time job for Nanny.

        // echo "<pre>";
        // print_r($message);
        // die;
        $body_a['data']['type'] = 2;
        $body_a['data']['message'] = $message;
        $body_a['data']['job_id'] = $job->id;
        $body_a['data']['worker_id'] = $from_user->id;


        $body_a['data']['badge'] = 1;
        $body_a['data']['sound'] = 'default';
        $body_a['data']['message'] = $message;
        $body_a['data']['alert'] = $message;
        $body_a['data']['text'] = $message;
        $body_a['data']['title'] = $message;


        $notification = new Notification();
        $notification->to_type =2;//2= employee
        $notification->to_id =$job->user_id;
        $notification->message =$message;
        $notification->type =2;//When worker has requested for a job  2
        $notification->job_id =$job->id;
        $notification->worker_id =$from_user->id;
        $notification->i_by =$from_user->id;
        $notification->i_date =date('Y-m-d H:i:s',time());
        $notification->u_by =$from_user->id;
        $notification->u_date =date('Y-m-d H:i:s',time());
        $notification->save();


        $uDeviceDetail_data = Token::where(['user_id'=>$job->user_id])->orderby("last_login",'desc')->get();
        if(isset($uDeviceDetail_data) && $uDeviceDetail_data!=array())
        {
            foreach($uDeviceDetail_data as $uDeviceDetail)
            {
                if(isset($uDeviceDetail->device_id) && $uDeviceDetail->device_id!=null && isset($uDeviceDetail->device_type) && $uDeviceDetail->device_type!=null)
                {
                    if($uDeviceDetail->device_type=='a'|| $uDeviceDetail->device_type=='A')
                    {
                        if(isset($device_token_android[$ij]) && count($device_token_android[$ij]) == 500)
                            $ij++;
                        $device_token_android[$ij][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                    if($uDeviceDetail->device_type=='i'|| $uDeviceDetail->device_type=='I')
                    {
                        if(isset($device_token_iphone[$mn]) && count($device_token_iphone[$mn]) == 500)
                            $mn++;
                        $device_token_iphone[$mn][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                }
            }
        }


        if(isset($device_token_android) && $device_token_android != array())
            self::pushnotification_android_array($device_token_android,$body_a);

        if(isset($device_token_iphone) && $device_token_iphone != array())
            self::pushnotification_iphone_array($device_token_iphone,$body_a);
    }


    //********************************************************************************
    //Title : send Job action Push Notification To Worker
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 22-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendJobactionPushNotificationToWorker($job,$action,$from_user,$id)
    {
        $device_token_android=$device_token_iphone=array();
        $ij=$mn=0;
        $body_a=array();
        $message=self::getWorkerJobNotificationMessage($action,$from_user,$job);
        //Employer Holland has completed Full time job for Nanny.
        
        if($action==1)//break
        $type=11;
        elseif($action==2)//completed
        $type=12;
        elseif($action==3)//terminated
        $type=13;

        // echo "<pre>";
        // print_r($message);
        // die;
        //$body_a['data']['type'] = $type;
        $body_a['data']['message'] = $message;
        $body_a['data']['job_id'] = $job->id;
        $body_a['data']['employer_id'] = $from_user->id;
        $body_a['data']['type'] = $type;


        $body_a['data']['badge'] = 1;
        $body_a['data']['sound'] = 'default';
        $body_a['data']['message'] = $message;
        $body_a['data']['alert'] = $message;
        $body_a['data']['text'] = $message;
        $body_a['data']['title'] = $message;


        $notification = new Notification();
        $notification->to_type =3;//3= Worker
        $notification->to_id =$id;
        $notification->message =$message;
        $notification->type =$type;//1= job
        $notification->job_id = $job->id;
        $notification->worker_id = $id;
        $notification->i_by =$from_user->id;
        $notification->i_date =date('Y-m-d H:i:s',time());
        $notification->u_by =$from_user->id;
        $notification->u_date =date('Y-m-d H:i:s',time());
        $notification->save();


        $uDeviceDetail_data = Token::where(['user_id'=>$job->selected_worker_id])->orderby("last_login",'desc')->get();
        if(isset($uDeviceDetail_data) && $uDeviceDetail_data!=array())
        {
            foreach($uDeviceDetail_data as $uDeviceDetail)
            {
                if(isset($uDeviceDetail->device_id) && $uDeviceDetail->device_id!=null && isset($uDeviceDetail->device_type) && $uDeviceDetail->device_type!=null)
                {
                    if($uDeviceDetail->device_type=='a'|| $uDeviceDetail->device_type=='A')
                    {
                        if(isset($device_token_android[$ij]) && count($device_token_android[$ij]) == 500)
                            $ij++;
                        $device_token_android[$ij][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                    if($uDeviceDetail->device_type=='i'|| $uDeviceDetail->device_type=='I')
                    {
                        if(isset($device_token_iphone[$mn]) && count($device_token_iphone[$mn]) == 500)
                            $mn++;
                        $device_token_iphone[$mn][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                }
            }
        }


        if(isset($device_token_android) && $device_token_android != array())
            self::pushnotification_android_array($device_token_android,$body_a);

        if(isset($device_token_iphone) && $device_token_iphone != array())
            self::pushnotification_iphone_array($device_token_iphone,$body_a);
    }
    //********************************************************************************
    //Title : push notification android array
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 21-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function pushnotification_android_array($deviceToken,$body)
    {
      //print_r($deviceToken); die;
        if(isset($deviceToken) && $deviceToken != null && $deviceToken != array())
        {
            $url = 'https://fcm.googleapis.com/fcm/send';
            $serverApiKey = config('params.android_server_api_key');

            $headers = array(
            'Content-Type:application/json',
            'Authorization:key=' . $serverApiKey
            );

            foreach($deviceToken as $key=>$value)
            {

              $value = array_values($value);
              //dd($value);
              //$token_block[$ij] =

              $data = array(
                'registration_ids' => $value,
                'data' => $body['data']
              );
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, $url);
              if ($headers)
              curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
              curl_setopt($ch, CURLOPT_POST, true);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
              $response = curl_exec($ch);
              curl_close($ch);
            }
        }
    }
    //********************************************************************************
    //Title : push notification iphone array
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 21-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function pushnotification_iphone_array($deviceToken, $body)
    {
        if(isset($deviceToken) && $deviceToken != null && $deviceToken != array())
        {
            $url = 'https://fcm.googleapis.com/fcm/send';

            $serverApiKey = config('params.ios_server_api_key');

            $headers = array(
              'Content-Type:application/json',
              'Authorization:key=' . $serverApiKey
            );

            $body['data']['text'] = $body['data']['alert'];

            foreach($deviceToken as $key=>$value)
            {

                $value = array_values($value);
                //echo "<pre>";print_r($value);die;
                $data = array(
                  'registration_ids' => $value,
                  'notification' => $body['data'],
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                if ($headers)
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $response = curl_exec($ch);
                curl_close($ch);
            }
        }
    }
    //********************************************************************************
    //Title : get Notification Message for employer
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 21-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function getNotificationMessage($type=null,$from_user=array(),$job=array())
    {
        $fun_result='';
        if(isset($type) && $type != null && $from_user != array() && $job!=array())
        {

            $tmp_str=config('params.worker_apply_notification_to_employer');
            if($type==2)
            $tmp_str=config('params.worker_cancel_notification_to_employer');

            $skill_name=$contract_name='';
            $worker_name=(isset($from_user->first_name) && $from_user->first_name!=null)?$from_user->first_name.' '.$from_user->second_name:'';

            $job_data = Job::
            leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
            ->selectRaw("jobs.id,jobs.contract_type,skills.name as skill_name")
            ->where('jobs.is_deleted',0)
            ->where('skills.is_deleted',0)
            ->where('jobs.id',$job->id)
            ->first();
            if(isset($job_data) && $job_data!=array())
            {
                $skill_name=(isset($job_data->skill_name) && $job_data->skill_name!='')?$job_data->skill_name:'';
                $contract_name=(isset($job_data->contract_type) && $job_data->contract_type!=null)?config('params.contract_type')[$job_data->contract_type]:'';
            }
            $tmp_str = str_replace('{user_name}',$worker_name,$tmp_str);
            $tmp_str = str_replace('{contract_type}',$contract_name,$tmp_str);
            $tmp_str = str_replace('{job_name}',$skill_name,$tmp_str);
            $fun_result=$tmp_str;
        }
        return $fun_result;
    }

    public static function getNotificationMessageToWorker($type=null,$from_user=array(),$job=array())
    {
        $fun_result='';
        if(isset($type) && $type != null && $from_user != array() && $job!=array())
        {
          $tmp_str=config('params.employer_apply_notification_to_worker');
            if($type==2)
            $tmp_str=config('params.employer_cancel_notification_to_worker');
            //James Holland has shortlisted you for Nanny as Full time

            $skill_name=$contract_name='';
            $worker_name=(isset($from_user->first_name) && $from_user->first_name!=null)?$from_user->first_name.' '.$from_user->second_name:'';

            $job_data = Job::
            leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
            ->selectRaw("jobs.id,jobs.contract_type,skills.name as skill_name")
            ->where('jobs.is_deleted',0)
            ->where('skills.is_deleted',0)
            ->where('jobs.id',$job->id)
            ->first();
            if(isset($job_data) && $job_data!=array())
            {
                $skill_name=(isset($job_data->skill_name) && $job_data->skill_name!='')?$job_data->skill_name:'';
                $contract_name=(isset($job_data->contract_type) && $job_data->contract_type!=null)?config('params.contract_type')[$job_data->contract_type]:'';
            }
            $tmp_str = str_replace('{user_name}',$worker_name,$tmp_str);
            $tmp_str = str_replace('{contract_type}',$contract_name,$tmp_str);
            $tmp_str = str_replace('{job_name}',$skill_name,$tmp_str);
            $fun_result=$tmp_str;
        }
        return $fun_result;
    }

    //********************************************************************************
    //Title : get Worker Job Notification Message
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 22-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function getWorkerJobNotificationMessage($type=null,$from_user=array(),$job=array())
    {
        $fun_result='';
        if(isset($type) && $type != null && $from_user != array() && $job!=array())
        {
            $tmp_str='';
            if($type==2)//complete
            $tmp_str=config('params.employer_complete_notification_to_worker');
            elseif($type==3)//terminate
            $tmp_str=config('params.employer_terminate_notification_to_worker');

            $skill_name=$contract_name='';
            $employer_name=(isset($from_user->first_name) && $from_user->first_name!=null)?$from_user->first_name.' '.$from_user->second_name:'';

            $job_data = Job::
            leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
            ->selectRaw("jobs.id,jobs.contract_type,skills.name as skill_name")
            ->where('jobs.is_deleted',0)
            ->where('skills.is_deleted',0)
            ->where('jobs.id',$job->id)
            ->first();
            if(isset($job_data) && $job_data!=array())
            {
                $skill_name=(isset($job_data->skill_name) && $job_data->skill_name!='')?$job_data->skill_name:'';
                $contract_name=(isset($job_data->contract_type) && $job_data->contract_type!=null)?config('params.contract_type')[$job_data->contract_type]:'';
            }
            $tmp_str = str_replace('{user_name}',$employer_name,$tmp_str);
            $tmp_str = str_replace('{contract_type}',$contract_name,$tmp_str);
            $tmp_str = str_replace('{job_name}',$skill_name,$tmp_str);
            $fun_result=$tmp_str;
        }
        return $fun_result;
    }
    //********************************************************************************
    //Title : push notification iphone array
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 21-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function getUserNotificationsetting($worker_id=null,$type=null,$type_id=null)
    {
        $fun_result=1;
        if(isset($worker_id) && $worker_id!=null && isset($type) && $type!=null && isset($type_id) && $type_id!=null)
        {
            //get user setting from master
            $noti_flag=Usernotificationsetting::where('is_deleted',0)->where('user_id',$worker_id)
                       ->where('type',$type)->where('type_id',$type_id)->first();
            if(isset($noti_flag) && $noti_flag!=array())
                $fun_result=$noti_flag->value;
        }
        return $fun_result;
    }
    
    //********************************************************************************
    //Title : send Contact expire Push Notification To employer
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 01-05-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendContactexpirePushNotificationToemployer($action,$id)
    {
        $device_token_android=$device_token_iphone=array();
        $ij=$mn=0;
        $body_a=array();
        //$message=self::getWorkerJobNotificationMessage($action,$from_user,$job);
        $message=config('params.employer_contact_expire_lapse');
        //Employer Holland has completed Full time job for Nanny.
        $type=$action;
        

        
        //$body_a['data']['type'] = $type;
        $body_a['data']['message'] = $message;
        //$body_a['data']['job_id'] = $job->id;
        //$body_a['data']['employer_id'] = $from_user->id;
        $body_a['data']['type'] = $action;


        $body_a['data']['badge'] = 1;
        $body_a['data']['sound'] = 'default';
        $body_a['data']['message'] = $message;
        $body_a['data']['alert'] = $message;
        $body_a['data']['text'] = $message;
        $body_a['data']['title'] = $message;


        $notification = new Notification();
        $notification->to_type =2;//3= Worker
        $notification->to_id =$id;
        $notification->message =$message;
        $notification->type =$type;//1= job
        //$notification->job_id = $job->id;
        //$notification->worker_id = $id;
        //$notification->i_by =$from_user->id;
        $notification->i_date =date('Y-m-d H:i:s',time());
        //$notification->u_by =$from_user->id;
        $notification->u_date =date('Y-m-d H:i:s',time());
        $notification->save();


        $uDeviceDetail_data = Token::where(['user_id'=>$id])->orderby("last_login",'desc')->get();
        if(isset($uDeviceDetail_data) && $uDeviceDetail_data!=array())
        {
            foreach($uDeviceDetail_data as $uDeviceDetail)
            {
                if(isset($uDeviceDetail->device_id) && $uDeviceDetail->device_id!=null && isset($uDeviceDetail->device_type) && $uDeviceDetail->device_type!=null)
                {
                    if($uDeviceDetail->device_type=='a'|| $uDeviceDetail->device_type=='A')
                    {
                        if(isset($device_token_android[$ij]) && count($device_token_android[$ij]) == 500)
                            $ij++;
                        $device_token_android[$ij][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                    if($uDeviceDetail->device_type=='i'|| $uDeviceDetail->device_type=='I')
                    {
                        if(isset($device_token_iphone[$mn]) && count($device_token_iphone[$mn]) == 500)
                            $mn++;
                        $device_token_iphone[$mn][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                }
            }
        }


        if(isset($device_token_android) && $device_token_android != array())
            self::pushnotification_android_array($device_token_android,$body_a);

        if(isset($device_token_iphone) && $device_token_iphone != array())
            self::pushnotification_iphone_array($device_token_iphone,$body_a);
    }
    
    //********************************************************************************
    //Title : send hire worker from shortlist Push Notification To employer
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 17-05-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendhireworkerfromshortlistPushNotificationToemployer($action,$id)
    {
        $device_token_android=$device_token_iphone=array();
        $ij=$mn=0;
        $body_a=array();
        //$message=self::getWorkerJobNotificationMessage($action,$from_user,$job);
        $message=config('params.employer_hire_from_shortlist_message');
        //Employer Holland has completed Full time job for Nanny.
        $type=$action;
        

        
        //$body_a['data']['type'] = $type;
        $body_a['data']['message'] = $message;
        //$body_a['data']['job_id'] = $job->id;
        //$body_a['data']['employer_id'] = $from_user->id;
        $body_a['data']['type'] = $action;


        $body_a['data']['badge'] = 1;
        $body_a['data']['sound'] = 'default';
        $body_a['data']['message'] = $message;
        $body_a['data']['alert'] = $message;
        $body_a['data']['text'] = $message;
        $body_a['data']['title'] = $message;


        $notification = new Notification();
        $notification->to_type =2;//3= Worker
        $notification->to_id =$id;
        $notification->message =$message;
        $notification->type =$type;//1= job
        //$notification->job_id = $job->id;
        //$notification->worker_id = $id;
        //$notification->i_by =$from_user->id;
        $notification->i_date =date('Y-m-d H:i:s',time());
        //$notification->u_by =$from_user->id;
        $notification->u_date =date('Y-m-d H:i:s',time());
        $notification->save();


        $uDeviceDetail_data = Token::where(['user_id'=>$id])->orderby("last_login",'desc')->get();
        if(isset($uDeviceDetail_data) && $uDeviceDetail_data!=array())
        {
            foreach($uDeviceDetail_data as $uDeviceDetail)
            {
                if(isset($uDeviceDetail->device_id) && $uDeviceDetail->device_id!=null && isset($uDeviceDetail->device_type) && $uDeviceDetail->device_type!=null)
                {
                    if($uDeviceDetail->device_type=='a'|| $uDeviceDetail->device_type=='A')
                    {
                        if(isset($device_token_android[$ij]) && count($device_token_android[$ij]) == 500)
                            $ij++;
                        $device_token_android[$ij][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                    if($uDeviceDetail->device_type=='i'|| $uDeviceDetail->device_type=='I')
                    {
                        if(isset($device_token_iphone[$mn]) && count($device_token_iphone[$mn]) == 500)
                            $mn++;
                        $device_token_iphone[$mn][$uDeviceDetail->id] = $uDeviceDetail->device_id;
                    }
                }
            }
        }


        if(isset($device_token_android) && $device_token_android != array())
            self::pushnotification_android_array($device_token_android,$body_a);

        if(isset($device_token_iphone) && $device_token_iphone != array())
            self::pushnotification_iphone_array($device_token_iphone,$body_a);
    }
}
