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
use App\Services\SmsSender;
use CommonFunction;

class EmailNotification
{

    public static function sendUsershortlistedEmailNotificationToWorker($job,$action,$from_user,$id,$date=null)
    {
      $email_template='shortlisted_for_Job';
      if($action==2)//2 cancel
          $email_template='rejected_for_Job';
      if($action==3)//2 cancel
        $email_template='accepted_for_Job';
      if($action==4)//2 cancel
        $email_template='hired_the_worker';

      $to_data = User::where(['id'=>$id])->first();
      $message=self::getUsershortlistedNotificationMessage($action,$from_user,$job,$date);
      // dd($message);
      //James Holland has requested Full time job for Nanny.
      if(isset($to_data) && $to_data!=array())
      {
        if(isset($to_data->email) && $to_data->email!=null)
        {
          self::sendmail($to_data->email,$to_data->first_name,$message,$email_template);
        }
      }
    }
    //********************************************************************************
    //Title : send Job action Email Notification To Employee
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 22-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendJobactionEmailNotificationToEmployee($job,$action,$from_user)
    {
        $email_template='workeracceptjob';
        if($action==2)//2 cancel
            $email_template='workercanceljob';

        $message=self::getNotificationMessage($action,$from_user,$job);
        //James Holland has requested Full time job for Nanny.
        $to_data= User::where(['id'=>$job->user_id])->first();
        if(isset($to_data) && $to_data!=array())
        {
            if(isset($to_data->email) && $to_data->email!=null)
            {
                self::sendmail($to_data->email,$to_data->first_name,$message,$email_template);
            }
        }

    }
    //********************************************************************************
    //Title : send Job action Email Notification To Worker
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 22-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendJobactionEmailNotificationToWorker($job,$action,$from_user)
    {

        $email_template='employeecompletejob';
        if($action==3)//3 terminate
            $email_template='employeeterminatejob';

        $message=self::getWorkerJobNotificationMessage($action,$from_user,$job);
        //Employer Holland has completed Full time job for Nanny.

        $to_data= User::where(['id'=>$job->selected_worker_id])->first();
        if(isset($to_data) && $to_data!=array())
        {
            if(isset($to_data->email) && $to_data->email!=null)
            {
                self::sendmail($to_data->email,$to_data->first_name,$message,$email_template);
            }
        }

    }
    //********************************************************************************
    //Title : Send mail
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 11-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendmail($to,$fullname=null,$msg=null,$key){
      try{
        $appname=config('params.appTitle');
        $emailtemplates=Emailtemplate::where(['key'=>$key])->first();
        $subject=$emailtemplates->title;

        if($emailtemplates != array()){
            $content=$emailtemplates->content;

            $image=CommonFunction::getemailtemplatelogo($appname);
            if($content != ""){
                $content=str_replace('{logo}',$image,$content);
                $content=str_replace('{appname}',$appname,$content);
                $content=str_replace('{name}',$fullname,$content);
                $content=str_replace('{msg}',$msg,$content);
                Mail::send( ['html' => null], ['text' => null],
                    function ($message) use ($to,$appname,$subject,$content)
                    {
                        $message->to($to);
                        $message->from(config('params.adminmailemail')); //not sure why I have to add this
                        $message->setBody($content, 'text/html');
                        $message->subject($appname.' : '.$subject);
                    }
                );

            }
        }

      }
      catch(\Swift_SwiftException $e)
      {
         $result=Yii::$app->apifunction->setResponse($e->getMessage(),false,200,400);
         Yii::$app->mycomponent->setHeader(400);
		 echo json_encode($result,true);die;
      }
    }
    //********************************************************************************
    //Title : get Notification Message to employer
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

    public static function getUsershortlistedNotificationMessage($type=null,$from_user=array(),$job=array(),$date=null)
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
          // dd($tmp_str);
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
            if($job==array() && $action==5)
            {
                $tmp_str=config('params.employer_final_sortlisted_notification_to_worker_without_job');
                $worker_name=(isset($from_user->first_name) && $from_user->first_name!=null)?$from_user->first_name.' '.$from_user->second_name:'';
                $tmp_str = str_replace('{user_name}',$worker_name,$tmp_str);
                $fun_result=$tmp_str;
            }
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
    //Title : send Contact expire Email Notification To employer
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 01-05-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendContactexpireEmailNotificationToemployer($action,$id)
    {
        $email_template='employer_contact_expire_lapse';

        //$message=self::getNotificationMessage($action,$from_user,$job);
        $message=config('params.employer_contact_expire_lapse');
        
        $to_data= User::where(['id'=>$id])->first();
        if(isset($to_data) && $to_data!=array())
        {
            if(isset($to_data->email) && $to_data->email!=null)
            {
                self::sendmail($to_data->email,$to_data->first_name,$message,$email_template);
            }
        }

    }
    //********************************************************************************
    //Title : send hire worker form shortlist Email Notification To employer
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 17-05-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function sendhireworkerfromshortlistEmailNotificationToemployer($action,$id)
    {
        $email_template='employer_hire_from_shortlist_message';

        //$message=self::getNotificationMessage($action,$from_user,$job);
        $message=config('params.employer_hire_from_shortlist_message');
        
        $to_data= User::where(['id'=>$id])->first();
        if(isset($to_data) && $to_data!=array())
        {
            if(isset($to_data->email) && $to_data->email!=null)
            {
                self::sendmail($to_data->email,$to_data->first_name,$message,$email_template);
            }
        }

    }

}
