<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Base\ApiController;
use Illuminate\Support\Facades\DB;
use App\Models\Token;
use App\Models\User;
use App\Models\Usercontract;
use App\Models\Userworklocationtown;
use App\Models\Job;
use App\Models\Userrating;
use App\Models\Jobterminatehistory;
use App\Models\Userworklocationsuburb;
use App\Models\Usershortlisted;
use App\Models\Notification;
use App\Models\Searchhistory;
use App\Models\Usernotificationsetting;
use Hash;
use URL;
use ApiFunction;
use PushNotification,EmailNotification;
use CommonFunction;


class WorkerController extends ApiController
{

    //********************************************************************************
    //Title : Worker Home Data
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 15-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getWorkerhome(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required',
          'latitude'=>'required',
          'longitude'=>'required',
        ]);

        //if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
        extract($_POST);
        $user_id='';
        if(isset($request->user['id']) && $request->user['id']!=null)
        {
          $user_id = $request->user['id'];
          $user = User::find($user_id);
        }
        $getalljobWorkerapplyjobvalue=commonFunction::getalljobWorkerapplyjobvalue($user_id,1);
        

        //if(isset($user) && $user!=array())
        //{
                $start=(isset($start) && $start!='')?$start:0;
                $limit=(isset($limit) && $limit!='')?$limit+1:11;
                
                $selected_urs = array();
                $selected_urs = Usershortlisted::where(['is_deleted'=>0,'worker_id'=>$user_id,'type'=>2,'type_value'=>1])->pluck('job_id');

                //start filter
                $andwhere='';
                if(isset($contract_type) && $contract_type!=null)
                {
                    if($contract_type!=3)
                    $andwhere='jobs.contract_type='.$contract_type;
                }

                $andwhere1='';
                if(isset($residency) && $residency!=null)
                {
                    if($residency!=3)
                    $andwhere1='jobs.preferred_residency='.$residency;
                }

                $andwhere2='';
                if(isset($suburb_id) && $suburb_id!=null)
                {
                  $andwhere2='user_details.suburb_id IN ('.$suburb_id.')';
                  if($user_id != "")
                  {
                    $ids = explode(",",$suburb_id);
                    foreach ($ids as $key => $id)
                    {
                      // $sh = New Searchhistory;
                      $sh = Searchhistory::where(['is_deleted'=>0,'user_id'=>$user_id,'type_id'=>$id,'type'=>1])->first();
                      if($sh == array())
                        $sh = New Searchhistory;
                      $sh->user_id = $user_id;
                      $sh->type = 2;
                      $sh->type_id = $id;
                      $sh->i_by = $user_id;
                      $sh->i_date = date('Y-m-d H:i:s',time());
                      $sh->u_by = $user_id;
                      $sh->u_date = date('Y-m-d H:i:s',time());
                      $sh->save();
                    }
                  }
                }

                //end filter

                //get worker desired town and suburb
                $desired_town_data=CommonFunction::GetWorkerdesiredTownids($user_id);
                $desired_suburb_data=CommonFunction::GetWorkerdesiredSuburbids($user_id);

                $display_job_interval_worker=config('params.display_job_interval_worker');
                //get location
                //$latitude = $user->latitude ;
                //$longitude = $user->longitude;
                $radius = 100;
                $radius_query = "(6371 * acos(cos(radians(" . $latitude . "))
                                   * cos(radians(jobs.latitude))
                                   * cos(radians(jobs.longitude)
                                   - radians(" . $longitude . "))
                                   + sin(radians(" . $latitude . "))
                                   * sin(radians(jobs.latitude))))";
                // dd($radius_query);
                $result['is_last'] = 1;
                $result['jobs_list'] =array();
                $skill_data = Job::
                selectRaw("DATE_ADD(jobs.i_date, INTERVAL {$display_job_interval_worker} DAY) AS nextweek,{$radius_query} AS distance,jobs.*,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id,locations.name as town_name,l.name as suburb_name,users.profile_image,concat(users.first_name,' ',users.second_name) as emp_name")
                ->leftjoin('user_details', 'user_details.user_id', '=', 'jobs.user_id')
                ->leftjoin('users', 'users.id', '=', 'jobs.user_id')
                ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
                ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
                ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
                //->leftjoin('user_shortlisted as us', 'us.job_id', '=', 'jobs.id')
                //->select(['{$radius_query} AS distance','jobs.*','user_details.town_id','user_details.suburb_id','skills.name as skill_name','user_details.town_id','user_details.suburb_id','locations.name as town_name','l.name as suburb_name'])
                ->where('jobs.is_deleted',0)
                ->where('jobs.is_active',1)
                ->where('skills.is_deleted',0)
                ->where('skills.is_active',1)
                ->whereNotIn('jobs.id',$selected_urs)
                ->whereNotIn('jobs.status', [1,2,3])//1 =hire,2=running,3 =Completed
                ->whereNotIn('jobs.id', $getalljobWorkerapplyjobvalue);
                if(isset($request->user['id']) && $request->user['id']!=null)
                {
                    $skill_data->where('jobs.skill_id',$user->detail->skill_id);
                }
                //$skill_data=$skill_data->whereRaw('(user_details.town_id IN ('.implode(',',$desired_town_data).') OR user_details.suburb_id IN ('.implode(',',$desired_suburb_data).'))');
                $home_where='';
                if($desired_town_data!=array())
                    $home_where.='user_details.town_id IN ('.implode(',',$desired_town_data).')';

                if($desired_suburb_data!=array())
                {
                    if($home_where!='')
                    $home_where.=' OR ';

                    $home_where.='user_details.suburb_id IN ('.implode(',',$desired_suburb_data).')';
                }
                if($home_where!='')
                {
                    $home_where='('.$home_where.')';
                    $skill_data=$skill_data->whereRaw($home_where);
                }

                $skill_data=$skill_data->whereRaw("{$radius_query} < ?", [$radius]);

                if($andwhere!='')
                  $skill_data=$skill_data->whereRaw($andwhere);

                if($andwhere1!='')
                  $skill_data=$skill_data->whereRaw($andwhere1);

                if( $andwhere2!='')
                  $skill_data=$skill_data->whereRaw($andwhere2);

                $skill_data=$skill_data->skip($start)->take($limit)
                ->havingRaw('nextweek >= now()')
                ->orderby('id','DESC')
                ->orderby('distance','ASC')
                ->get();

                // dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $value)
                    {
                      //$result['jobs_list'][$i]['week'] = $value->nextweek;
                      //$result['jobs_list'][$i]['i_date'] = $value->i_date;
                      $result['jobs_list'][$i]['id'] = $value->id;
                      $result['jobs_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                      $result['jobs_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';
                      $result['jobs_list'][$i]['contract_type'] = (isset($value->contract_type) && $value->contract_type!=null)?config('params.contract_type')[$value->contract_type]:'';

                      $result['jobs_list'][$i]['town_suburb']='';
                      if(isset($value->town_name) && $value->town_name!=null)
                      $result['jobs_list'][$i]['town_suburb'] .= (isset($value->town_name) && $value->town_name!=null)?$value->town_name:'';
                      if(isset($value->suburb_name) && $value->suburb_name!=null)
                      {
                          $result['jobs_list'][$i]['town_suburb'] .=', ';
                          $result['jobs_list'][$i]['town_suburb'] .=(isset($value->suburb_name) && $value->suburb_name!=null)?$value->suburb_name:'';
                      }
                      $result['jobs_list'][$i]['posted_timestamp'] = (isset($value->i_date) && $value->i_date!=null)?strtotime($value->i_date):'';
                      $result['jobs_list'][$i]['distance_in_km'] = $value->distance ?? 0;
                      $result['jobs_list'][$i]['employer_image'] = (isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                      $result['jobs_list'][$i]['employer_fullname'] = (isset($value->emp_name) && $value->emp_name!=null)?$value->emp_name:'';
                      //$result['jobs_list'][$i]['is_applied']=CommonFunction::getWorkerapplyjobvalue($user_id,$value->id,$value->type);//1=shortlisted
                      //$result['jobs_list'][$i]['status'] = $value->status;
                      //get shortlisted value
                      
                      $result['jobs_list'][$i]['proposed_pay_from']='';
                        if(isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)
                            $result['jobs_list'][$i]['proposed_pay_from'] = (isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)?$value->proposed_pay_from:'';
        
                        $result['jobs_list'][$i]['proposed_pay_to'] ='';
                        if(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)
                            $result['jobs_list'][$i]['proposed_pay_to'] =(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)?$value->proposed_pay_to:'';



                      $i++;
                    }

                    if($i == $limit){
                        unset($result['jobs_list'][$i-1]);
                        $result['is_last'] =0;
                    }
                }
                return $this->SuccessResponse($result);
        //}
        //else {
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
    }

    //********************************************************************************
    //Title : Change job action
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 15-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************


    public function changeJobaction(Request $request)
    {
      $request->validate([
        'job_id' => 'required',
        'action'=>'required',
      ]);

      if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        return $this->ErrorResponse('error_user_not_found',200);
      }

      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::find($user_id);

      if(isset($user) && $user!=array())
      {

            //check job is exist or not
            $job= Job::
            selectRaw('jobs.*')
            ->where('jobs.id',$job_id)
            ->where('jobs.is_deleted',0)
            ->where('jobs.is_active',1)
            ->first();
            if(isset($job) && $job!=array())
            {
                if($job->status!=0)
                    return $this->ErrorResponse('error_request_job_apply_only',200);
                
                if($action==1)//apply
                {
                    $already_data = Usershortlisted::where(['is_deleted'=>0,'job_id'=>$job_id,'worker_id'=>$user_id,'type'=>1,'type_value'=>1])->first();
                    if(isset($already_data) && $already_data!=array())
                    {
                        return $this->ErrorResponse('worker_already_interested_workerside',200);
                    }
                }
                //check record is exist or not
                $job_tracking = Usershortlisted::where(['is_deleted'=>0,'worker_id'=>$user_id,'job_id'=>$job_id,'type'=>1])->first();
                if(isset($job_tracking) && $job_tracking!=array())
                {

                }
                else
                {
                    $job_tracking=new Usershortlisted;
                    $job_tracking->i_date = date('Y-m-d H:i:s',time());
                    $job_tracking->i_by = $user->id;
                    $job_tracking->worker_id = $user_id;
                    $job_tracking->job_id = $job_id;
                }
                $status='';
                $api_msg='success';
                if($action==1)//apply
                {
                    $status=1;
                    $api_msg='success_apply_job_worker';
                }
                else if($action==2)
                {
                    $status=0;
                    $api_msg='success_cancel_job_worker';
                }
                $job_tracking->type = 1;
                $job_tracking->type_value = $status;
                $job_tracking->user_id = $job->user_id;
                //$job_tracking->date = date('Y-m-d',time());;
                $job_tracking->u_date = date('Y-m-d H:i:s',time());
                $job_tracking->u_by = $user->id;
                $job_tracking->save();


                //send push and email notification to employer
                PushNotification::sendJobactionPushNotificationToEmployee($job,$action,$user); //action:1=apply,2=cancel
                EmailNotification::sendJobactionEmailNotificationToEmployee($job,$action,$user); //action:1=apply,2=cancel
                //end
                return $this->SuccessResponse([],$api_msg);
            }
            else{
              return $this->ErrorResponse('job_not_found',200);
            }
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }

    //********************************************************************************
    //Title : Job Detail
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 15-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getWorkerjobdetail(Request $request)
    {
         $request->validate([
          'job_id'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {

          $latitude = (isset($user->latitude) && $user->latitude!='')?$user->latitude:0;
          $longitude = (isset($user->longitude) && $user->longitude!='')?$user->longitude:0;
            $radius = 100;
            $radius_query = "(6371 * acos(cos(radians(" . $latitude . "))
                               * cos(radians(users.latitude))
                               * cos(radians(users.longitude)
                               - radians(" . $longitude . "))
                               + sin(radians(" . $latitude . "))
                               * sin(radians(users.latitude))))";


            $job = Job::
            leftjoin('user_details', 'user_details.user_id', '=', 'jobs.user_id')
            ->leftjoin('users', 'users.id', '=', 'jobs.user_id')
            ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
            ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
            ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
            //->select(['{$radius_query} AS distance','jobs.*','user_details.town_id','user_details.suburb_id','skills.name as skill_name','user_details.town_id','user_details.suburb_id','locations.name as town_name','l.name as suburb_name'])
            ->selectRaw("{$radius_query} AS distance,jobs.*,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id,locations.name as town_name,l.name as suburb_name,users.profile_image,concat(users.first_name,' ',users.second_name) as emp_name")
            ->where('jobs.is_deleted',0)
            ->where('jobs.is_active',1)
            ->where('skills.is_deleted',0)
            ->where('skills.is_active',1)
            ->where('jobs.id',$job_id)
            //->where('jobs.skill_id',$user->detail->skill_id)
            //->whereRaw('(user_details.town_id IN ('.implode(',',$desired_town_data).') OR user_details.suburb_id IN ('.implode(',',$desired_suburb_data).'))')
            //->whereRaw("{$radius_query} < ?", [$radius])
            //->skip($start)->take($limit)
            //->orderby('distance')
            ->first();
            if(isset($job) && $job!=array())
            {

                $result['id'] = $job->id;
                $result['skill_id'] = (isset($job->skill_id) && $job->skill_id!=null)?$job->skill_id:'';
                $result['skill_name'] = (isset($job->skill_name) && $job->skill_name!=null)?$job->skill_name:'';
                $result['contract_type'] = (isset($job->contract_type) && $job->contract_type!=null)?config('params.contract_type')[$job->contract_type]:'';
                $result['contract_type_id'] = (isset($job->contract_type) && $job->contract_type!=null)?(int)$job->contract_type:0;

                $result['job_location']='';
                if(isset($job->town_name) && $job->town_name!=null)
                $result['job_location'] .= (isset($job->town_name) && $job->town_name!=null)?$job->town_name:'';
                if(isset($job->suburb_name) && $job->suburb_name!=null)
                {
                    $result['job_location'] .=', ';
                    $result['job_location'] .=(isset($job->suburb_name) && $job->suburb_name!=null)?$job->suburb_name:'';
                }
                $result['job_posted_timestamp'] = (isset($job->i_date) && $job->i_date!=null)?strtotime($job->i_date):'';
                $result['distance_in_km'] = $job->distance ?? 0;
                $result['employer_profile_image'] = (isset($job->profile_image) && $job->profile_image!=null)?$job->profile_image:'';
                $result['employer_fullname'] = (isset($job->emp_name) && $job->emp_name!=null)?$job->emp_name:'';
                //$result['is_applied']=1;
                //if($job->status!=null || $job->status==0)
                //$result['is_applied']=0;
                $result['is_applied']=CommonFunction::getWorkerapplyjobvalue($user_id,$job->id,1);//1=shortlisted

                $result['experience'] = (isset($job->experience) && $job->experience!=null)?config('params.total_experiance_array')[$job->experience]:'';
                $result['experience_id'] = (isset($job->experience) && $job->experience!=null)?(int)$job->experience:0;

                $result['proposed_desired_pay']='';
                if(isset($job->proposed_pay_from) && $job->proposed_pay_from!=null)
                $result['proposed_desired_pay'] .= (isset($job->proposed_pay_from) && $job->proposed_pay_from!=null)?$job->proposed_pay_from:'';
                if(isset($job->proposed_pay_to) && $job->proposed_pay_to!=null)
                {
                    $result['proposed_desired_pay'] .=' - ';
                    $result['proposed_desired_pay'] .=(isset($job->proposed_pay_to) && $job->proposed_pay_to!=null)?$job->proposed_pay_to:'';
                }

                $result['preferred_residency'] = (isset($job->preferred_residency) && $job->preferred_residency!=null)?config('params.preferred_residency')[$job->preferred_residency]:'';
                $result['service_needed_type'] = (isset($job->service_needed_type) && $job->service_needed_type!=null)?config('params.service_needed_type')[$job->service_needed_type]:'';
                $result['description'] = (isset($job->description) && $job->description!=null)?$job->description:'';
                $result['job_status'] = (isset($job->status) && $job->status!=null)?(int)$job->status:'';

                $result['start_timestamp'] = (isset($job->worker_started_work_on) && $job->worker_started_work_on!=null)?strtotime($job->worker_started_work_on):0;
                $result['end_timestamp'] = (isset($job->end_date) && $job->end_date!=null)?strtotime($job->end_date):0;
                $result['terminated_reason']=$result['terminated_comment']='';

                //job is terminated
                if($job->status==4)
                {
                    $job_track= Jobterminatehistory::
                    leftjoin('terminate_reasons', 'terminate_reasons.id', '=', 'job_terminate_history.terminate_id')
                    ->selectRaw("job_terminate_history.*,terminate_reasons.name as terminate_reasons")
                    ->where(['job_terminate_history.is_deleted'=>0,'job_terminate_history.job_id'=>$job_id,'job_terminate_history.worker_id'=>$job->selected_worker_id,'job_terminate_history.job_status'=>4])->first();
                    if(isset($job_track) && $job_track!=array())
                    {
                        $result['terminated_reason']=$job_track->terminate_reasons;
                        $result['terminated_comment']=(isset($job_track->terminate_description) && $job_track->terminate_description!=null)?$job_track->terminate_description:'';
                        $result['end_timestamp'] = (isset($job_track->i_date) && $job_track->i_date!=null)?strtotime($job_track->i_date):0;
                    }

                }
                //end job terminated

                //start rating
                $result['rating_attitude']=$result['rating_competency']=$result['rating_communication_skills']=0;
                $result['rating_shows_initiative']=$result['rating_recommend']=$result['average_rating']=0;
                //end rating
                $getWokerjobrating=CommonFunction::getWokerjobrating($job_id,$job->selected_worker_id);
                if(isset($getWokerjobrating) && $getWokerjobrating!=array())
                {
                    $result['average_rating']=(isset($getWokerjobrating['total']) && $getWokerjobrating['total']!=null)?$getWokerjobrating['total']:0;
                    foreach($getWokerjobrating['rating'] as $key => $r)
                    {
                        switch($key)
                        {
                            case 1:
                            $result['rating_attitude']=$r;
                            break;

                            case 2:
                            $result['rating_competency']=$r;
                            break;

                            case 3:
                            $result['rating_communication_skills']=$r;
                            break;

                            case 4:
                            $result['rating_shows_initiative']=$r;
                            break;

                            case 5:
                            $result['rating_recommend']=$r;
                            break;

                        }
                    }
                }



                return $this->SuccessResponse($result);
            }
            else
            {
                return $this->ErrorResponse('job_not_found',200);
            }
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }

    //********************************************************************************
    //Title : Shortlisted List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function shortlistedList(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required'
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {
                $start=(isset($start) && $start!='')?$start:0;
                $limit=(isset($limit) && $limit!='')?$limit+1:11;
                


                //get worker desired town and suburb
                $desired_town_data=CommonFunction::GetWorkerdesiredTownids($user_id);
                $desired_suburb_data=CommonFunction::GetWorkerdesiredSuburbids($user_id);

                //get location
                $latitude = (isset($user->latitude) && $user->latitude!='')?$user->latitude:0;
                $longitude = (isset($user->longitude) && $user->longitude!='')?$user->longitude:0;
                $radius = 1000000;
                $radius_query = "(6371 * acos(cos(radians(" . $latitude . "))
                                   * cos(radians(users.latitude))
                                   * cos(radians(users.longitude)
                                   - radians(" . $longitude . "))
                                   + sin(radians(" . $latitude . "))
                                   * sin(radians(users.latitude))))";

              $result['is_last'] = 1;
              $result['jobs_list'] =array();
              $skill_data = Job::
              selectRaw("{$radius_query} AS distance,jobs.*,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id,locations.name as town_name,l.name as suburb_name,users.profile_image,concat(users.first_name,' ',users.second_name) as emp_name,us.type as type,us.id as uid")
              ->leftjoin('user_details', 'user_details.user_id', '=', 'jobs.user_id')
              ->leftjoin('users', 'users.id', '=', 'jobs.user_id')
              ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
              ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
              ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
              //->leftjoin('user_shortlisted as us', 'us.job_id', '=', 'jobs.id')
             ->leftjoin('user_shortlisted as us',function($join) use ($user_id)
                 {
                     $join->on('us.job_id', '=', 'jobs.id');
                     $join->where('us.worker_id', '=', $user_id);
                     $join->where('us.is_deleted', '=', 0);
                 })
              //->select(['{$radius_query} AS distance','jobs.*','user_details.town_id','user_details.suburb_id','skills.name as skill_name','user_details.town_id','user_details.suburb_id','locations.name as town_name','l.name as suburb_name'])
              ->where('jobs.is_deleted',0)
              ->where('jobs.is_active',1)
              ->where('skills.is_deleted',0)
              ->where('skills.is_active',1)
              ->where('us.worker_id',$user_id)
              //->where('us.type',2) //only final shortilted
              ->whereRaw('us.type=2 or (us.type=1 and us.is_from_worker=1)')
              ->where('us.type_value',1)
              ->where('us.is_deleted',0);
              //->where('jobs.skill_id',$user->detail->skill_id)
              //->whereRaw('(user_details.town_id IN ('.implode(',',$desired_town_data).') OR user_details.suburb_id IN ('.implode(',',$desired_suburb_data).'))')
              //->whereRaw("{$radius_query} < ?", [$radius]);

              $skill_data=$skill_data->skip($start)->take($limit)
              ->orderby('jobs.id','desc')
              ->get();

              //dd($skill_data);
              if(isset($skill_data) && $skill_data!=array())
              {
                  $i=0;
                  foreach ($skill_data as $key => $value)
                  {
                    $result['jobs_list'][$i]['id'] = $value->id;
                    $result['jobs_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                    $result['jobs_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';
                    //$result['jobs_list'][$i]['contract_type'] = (isset($value->contract_type) && $value->contract_type!=null)?config('params.contract_type')[$value->contract_type]:'';
                    $result['jobs_list'][$i]['contract_type'] = (isset($value->contract_type) && $value->contract_type!=null)?(int)$value->contract_type:0;

                    $result['jobs_list'][$i]['town_suburb']='';
                    if(isset($value->town_name) && $value->town_name!=null)
                    $result['jobs_list'][$i]['town_suburb'] .= (isset($value->town_name) && $value->town_name!=null)?$value->town_name:'';
                    if(isset($value->suburb_name) && $value->suburb_name!=null)
                    {
                        $result['jobs_list'][$i]['town_suburb'] .=', ';
                        $result['jobs_list'][$i]['town_suburb'] .=(isset($value->suburb_name) && $value->suburb_name!=null)?$value->suburb_name:'';
                    }
                    $result['jobs_list'][$i]['posted_timestamp'] = (isset($value->i_date) && $value->i_date!=null)?strtotime($value->i_date):0;
                    $result['jobs_list'][$i]['distance_in_km'] = $value->distance ?? 0;
                    $result['jobs_list'][$i]['employer_image'] = (isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                    $result['jobs_list'][$i]['employer_fullname'] = (isset($value->emp_name) && $value->emp_name!=null)?$value->emp_name:'';
                    $result['jobs_list'][$i]['status_id'] = (isset($value->status) && $value->status!=null)?(int)$value->status:0;
                    $result['jobs_list'][$i]['is_applied']=CommonFunction::getWorkerapplyjobvalue($user_id,$value->id,$value->type,$value->uid);//1=shortlisted
                    $result['jobs_list'][$i]['type'] = (isset($value->type) && $value->type!=null)?(int)$value->type:0;
                    $result['jobs_list'][$i]['us_id'] = (isset($value->uid) && $value->uid!=null)?(int)$value->uid:0;
                    //$result['jobs_list'][$i]['is_applied']=1;
                    //if($value->status!=null || $value->status==0)
                    //$result['jobs_list'][$i]['is_applied']=0;



                    $i++;
                  }

                  if($i == $limit){
                      unset($result['jobs_list'][$i-1]);
                      $result['is_last'] =0;
                  }
              }
              return $this->SuccessResponse($result);
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }

    //********************************************************************************
    //Title : Get Rating
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getRating(Request $request)
    {
         $request->validate([
          'worker_id'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {

            $userdata = User::
            where('users.is_deleted',0)
            ->where('users.is_active',1)
            ->where('users.id',$worker_id)
            ->where('actor',3)
            //->where('jobs.skill_id',$user->detail->skill_id)
            //->whereRaw('(user_details.town_id IN ('.implode(',',$desired_town_data).') OR user_details.suburb_id IN ('.implode(',',$desired_suburb_data).'))')
            //->whereRaw("{$radius_query} < ?", [$radius])
            //->skip($start)->take($limit)
            //->orderby('distance')
            ->first();
            if(isset($userdata) && $userdata!=array())
            {

                $result['worker_id'] = $userdata->id;
                $result['profile_image'] = (isset($userdata->profile_image) && $userdata->profile_image!=null)?$userdata->profile_image:'';
                $result['is_worker_premium'] = (isset($userdata->is_employer_premium) && $userdata->is_employer_premium!=null)?(int)$userdata->is_employer_premium:0;
                $result['worker_firstname'] = (isset($userdata->first_name) && $userdata->first_name!=null)?$userdata->first_name:'';
                $result['worker_secondname'] = (isset($userdata->second_name) && $userdata->second_name!=null)?$userdata->second_name:'';

                //get Employer rating count
                $result['total_employers_rating'] =CommonFunction::getEmployerratingcount($userdata->id);

                //start rating
                $result['rating_attitude']=$result['rating_competency']=$result['rating_communication_skills']=0;
                $result['rating_shows_initiative']=$result['rating_recommend']=$result['average_rating']=0;
                //end rating
                $getWokerjobrating=CommonFunction::getWokeralljobavgrating($userdata->id);
                if(isset($getWokerjobrating) && $getWokerjobrating!=array())
                {
                    $result['average_rating']=(isset($getWokerjobrating['total']) && $getWokerjobrating['total']!=null)?$getWokerjobrating['total']:0;
                    foreach($getWokerjobrating['rating'] as $key => $r)
                    {
                        switch($key)
                        {
                            case 1:
                            $result['rating_attitude']=$r;
                            break;

                            case 2:
                            $result['rating_competency']=$r;
                            break;

                            case 3:
                            $result['rating_communication_skills']=$r;
                            break;

                            case 4:
                            $result['rating_shows_initiative']=$r;
                            break;

                            case 5:
                            $result['rating_recommend']=$r;
                            break;

                        }
                    }
                }

                //job rating list
                $start=(isset($start) && $start!='')?$start:0;
                $limit=(isset($limit) && $limit!='')?$limit+1:3;
                $result['jobs_rating_list'] = array();
                $result['is_last'] = 1;
                $selected_job = Userrating::where(['is_deleted'=>0,'worker_id'=>$userdata->id])->pluck('job_id');

                $skill_data = Job::
                selectRaw("jobs.*,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id,locations.name as town_name,l.name as suburb_name,users.profile_image,concat(users.first_name,' ',users.second_name) as emp_name,
                          (select id from user_rating where is_deleted=0 and job_id=jobs.id and worker_id=".$user_id." order by id DESC limit 1) as `rid`")
                ->leftjoin('user_details', 'user_details.user_id', '=', 'jobs.user_id')
                ->leftjoin('users', 'users.id', '=', 'jobs.user_id')
                ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
                ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
                ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
                //->leftjoin('user_rating as r', 'r.job_id', '=', 'jobs.id')
                ->where('jobs.is_deleted',0)
                ->where('jobs.is_active',1)
                ->where('skills.is_deleted',0)
                ->where('skills.is_active',1)
                ->whereIn('jobs.id',$selected_job)
                ->where('jobs.selected_worker_id',$userdata->id);
                $skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('rid','desc')
                ->get();

                //dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $key => $value)
                    {
                        //$result['jobs_rating_list'][$i]['rating_id'] = $value->rid;
                        $result['jobs_rating_list'][$i]['job_id'] = $value->id;
                        $result['jobs_rating_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                        $result['jobs_rating_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';

                        $result['jobs_rating_list'][$i]['town_suburb']='';
                        if(isset($value->town_name) && $value->town_name!=null)
                        $result['jobs_rating_list'][$i]['town_suburb'] .= (isset($value->town_name) && $value->town_name!=null)?$value->town_name:'';
                        if(isset($value->suburb_name) && $value->suburb_name!=null)
                        {
                            $result['jobs_rating_list'][$i]['town_suburb'] .=', ';
                            $result['jobs_rating_list'][$i]['town_suburb'] .=(isset($value->suburb_name) && $value->suburb_name!=null)?$value->suburb_name:'';
                        }

                        $result['jobs_rating_list'][$i]['employer_profile_image'] = (isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                        $result['jobs_rating_list'][$i]['employer_fullname'] = (isset($value->emp_name) && $value->emp_name!=null)?$value->emp_name:'';
                        $result['jobs_rating_list'][$i]['job_status'] = (isset($value->status) && $value->status!=null)?(int)$value->status:0;
                        $result['jobs_rating_list'][$i]['end_date'] = (isset($value->end_date) && $value->end_date!=null)?strtotime($value->end_date):0;
                        if($value->status==4)
                        {
                            //job is terminated

                            $job_track= Jobterminatehistory::
                            leftjoin('terminate_reasons', 'terminate_reasons.id', '=', 'job_terminate_history.terminate_id')
                            ->selectRaw("job_terminate_history.*,terminate_reasons.name as terminate_reasons")
                            ->where(['job_terminate_history.is_deleted'=>0,'job_terminate_history.job_id'=>$value->id,'job_terminate_history.worker_id'=>$value->selected_worker_id,'job_terminate_history.job_status'=>4])
                            ->orderby('job_terminate_history.id','desc')
                            ->first();
                            //dd($job_track->terminate_description);
                            if(isset($job_track) && $job_track!=array())
                            {
                                //$result['terminated_reason']=$job_track->terminate_reasons;
                                //$result['terminated_comment']=(isset($job_track->terminate_description) && $job_track->terminate_description!=null)?$job_track->terminate_description:'';
                                //$result['end_timestamp'] = (isset($job_track->i_date) && $job_track->i_date!=null)?strtotime($job_track->i_date):0;
                                $result['jobs_rating_list'][$i]['end_date'] = (isset($job_track->i_date) && $job_track->i_date!=null)?strtotime($job_track->i_date):0;
                            }

                            
                        }

                        $result['jobs_rating_list'][$i]['total_percentage']=0;
                        $getWokerjobrating=CommonFunction::getWokerjobrating($value->id,$userdata->id);
                        if(isset($getWokerjobrating) && $getWokerjobrating!=array())
                            $result['jobs_rating_list'][$i]['total_percentage']=(isset($getWokerjobrating['total']) && $getWokerjobrating['total']!=null)?$getWokerjobrating['total']:0;




                      $i++;
                    }

                    if($i == $limit){
                        unset($result['jobs_rating_list'][$i-1]);
                        $result['is_last'] =0;
                    }
                }
                //end job rating


                return $this->SuccessResponse($result);
            }
            else
            {
                return $this->ErrorResponse('worker_not_found',200);
            }
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }
    //********************************************************************************
    //Title : Worker job History List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function workerjobHistorylist(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required',
          'type'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {
                $start=(isset($start) && $start!='')?$start:0;
                $limit=(isset($limit) && $limit!='')?$limit+1:11;

                //start filter
                $andwhere='';
                if(isset($type) && $type!=null && $type!=0)
                    $andwhere='jobs.status='.$type;


                //end filter

                $result['is_last'] = 1;
                $result['job_history_list'] =array();
                $skill_data = Job::
                selectRaw("jobs.*,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id,locations.name as town_name,l.name as suburb_name,users.profile_image,concat(users.first_name,' ',users.second_name) as emp_name")
                ->leftjoin('user_details', 'user_details.user_id', '=', 'jobs.selected_worker_id')
                ->leftjoin('users', 'users.id', '=', 'jobs.user_id')
                ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
                ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
                ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
                //->select(['{$radius_query} AS distance','jobs.*','user_details.town_id','user_details.suburb_id','skills.name as skill_name','user_details.town_id','user_details.suburb_id','locations.name as town_name','l.name as suburb_name'])
                ->where('jobs.selected_worker_id',$user_id)
                ->where('jobs.status', '<=', 4)//upto terminated
                ->where('jobs.is_deleted',0)
                ->where('jobs.is_active',1);

                if( $andwhere!='')
                $skill_data=$skill_data->whereRaw($andwhere);

                $skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('jobs.id','desc')
                ->get();

                //dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $value)
                    {
                      $result['job_history_list'][$i]['job_id'] = $value->id;
                      $result['job_history_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                      $result['job_history_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';
                      $result['job_history_list'][$i]['contract_type'] = (isset($value->contract_type) && $value->contract_type!=null)?config('params.contract_type')[$value->contract_type]:'';
                      $result['job_history_list'][$i]['job_status']=$value->status;

                      $result['job_history_list'][$i]['town_suburb']='';
                      if(isset($value->town_name) && $value->town_name!=null)
                      $result['job_history_list'][$i]['town_suburb'] .= (isset($value->town_name) && $value->town_name!=null)?$value->town_name:'';
                      if(isset($value->suburb_name) && $value->suburb_name!=null)
                      {
                          $result['job_history_list'][$i]['town_suburb'] .=', ';
                          $result['job_history_list'][$i]['town_suburb'] .=(isset($value->suburb_name) && $value->suburb_name!=null)?$value->suburb_name:'';
                      }
                      //$result['job_history_list'][$i]['posted_timestamp'] = (isset($value->i_date) && $value->i_date!=null)?strtotime($value->i_date):'';
                      //$result['job_history_list'][$i]['distance_in_km'] = (isset($value->distance) && $value->distance!=null)?$value->distance:'';
                      //$result['job_history_list'][$i]['worker_id'] = (isset($value->selected_worker_id) && $value->selected_worker_id!=null)?(int)$value->selected_worker_id:0;
                      $result['job_history_list'][$i]['employer_profile_image'] = (isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                      $result['job_history_list'][$i]['employer_fullname'] = (isset($value->emp_name) && $value->emp_name!=null)?$value->emp_name:'';

                      //if($value->status==1)//hired
                      //$result['job_history_list'][$i]['job_status']=1;
                      //if($value->status==2)//running
                      //$result['job_history_list'][$i]['job_status']=2;
                      //if($value->status==3)//completed
                      //$result['job_history_list'][$i]['job_status']=3;
                      //if($value->status==4)//terminated
                      //$result['job_history_list'][$i]['job_status']=4;
                      $i++;
                    }

                    if($i == $limit){
                        unset($result['job_history_list'][$i-1]);
                        $result['is_last'] =0;
                    }
                }
                return $this->SuccessResponse($result);
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }

    //********************************************************************************
    //Title : Worker job History List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function notificationList(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required',
          'user_type'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {
                $start=(isset($start) && $start!='')?$start:0;
                $limit=(isset($limit) && $limit!='')?$limit+1:11;


                $result['is_last'] = 1;
                $result['notification_list'] =array();
                $skill_data = Notification::

                where('notifications.is_deleted',0)
                ->whereRaw('FIND_IN_SET('.$user_id.',notifications.to_id)')
                ->whereRaw('(NOT FIND_IN_SET('.$user_id.',notifications.deleted_by) OR notifications.deleted_by is NULL)');


                $skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('notifications.i_date','desc')
                ->get();
                if(isset($skill_data) && $skill_data!=array())
                {
                  $i=0;
                  foreach ($skill_data as $value)
                  {
                    $result['notification_list'][$i]['notification_id'] = $value->id;
                    $result['notification_list'][$i]['title'] = (isset($value->title) && $value->title!=null)?$value->title:'';
                    $result['notification_list'][$i]['message'] = (isset($value->message) && $value->message!=null)?$value->message:'';
                    $result['notification_list'][$i]['date'] = (isset($value->i_date) && $value->i_date!=null)?strtotime($value->i_date):0;
                    $result['notification_list'][$i]['notification_status'] = $value->type ?? 1;
                    $result['notification_list'][$i]['job_id'] = $value->job_id ?? 0;
                    $result['notification_list'][$i]['worker_id'] = $value->worker_id ?? 0;
                    $i++;
                  }
                  if($i == $limit){
                      unset($result['notification_list'][$i-1]);
                      $result['is_last'] =0;
                  }
                }
                return $this->SuccessResponse($result);
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }

    //********************************************************************************
    //Title : Clear Notification
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function clearNotification(Request $request)
    {
        // $request->validate([
        //  'start'=>'required',
        //  'limit'=>'required',
        //  'user_type'=>'required',
        //]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {
            DB::update('update notifications set deleted_by = concat(ifnull(deleted_by,""),",'.$user_id.'") where FIND_IN_SET('.$user_id.',to_id) AND (NOT FIND_IN_SET('.$user_id.',deleted_by) OR deleted_by is NULL)');
            return $this->SuccessResponse([],'success_clear_all_notification');
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }

    //********************************************************************************
    //Title : Worker job History List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function notificationSetting(Request $request)
    {
        // $request->validate([
        //  'start'=>'required',
        //  'limit'=>'required',
        //  'user_type'=>'required',
        //]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {

            //set notification value
            if(isset($type) && $type!=null && isset($type_id) && $type_id!=null)
            {
                $user_noti=Usernotificationsetting::where('is_deleted',0)
                ->where('user_id',$user_id)
                ->where('type',$type)
                ->where('type_id',$type_id)
                ->first();
                if(isset($user_noti) && $user_noti!=array())
                {

                }
                else
                {
                    $user_noti=new Usernotificationsetting();
                    $user_noti->user_id=$user_id;
                    $user_noti->type=$type;
                    $user_noti->type_id=$type_id;
                    $user_noti->i_date = date('Y-m-d H:i:s',time());
                    $user_noti->i_by = $user->id;
                }
                $user_noti->value=(isset($type_value) && $type_value!='')?$type_value:0;
                $user_noti->u_date = date('Y-m-d H:i:s',time());
                $user_noti->u_by = $user->id;
                $user_noti->save();

            }
            //get user email notification setting
            $result['email_accepted']=$result['email_rejected']=$result['email_completed']=$result['email_terminated']=$result['email_shortlisted']=0;

            $email_noti=Usernotificationsetting::where('is_deleted',0)
            ->where('user_id',$user_id)
            ->where('type',1)//email notification
            ->orderby('id')
            ->get();

            if(isset($email_noti) && $email_noti!=array())
            {
                foreach($email_noti as $v)
                {
                    switch($v['type_id'])
                    {
                        case 1:
                        $result['email_accepted']=$v['value'];
                        break;

                        case 2:
                        $result['email_rejected']=$v['value'];
                        break;

                        case 3:
                        $result['email_completed']=$v['value'];
                        break;

                        case 4:
                        $result['email_terminated']=$v['value'];
                        break;

                        case 5:
                        $result['email_shortlisted']=$v['value'];
                        break;

                        default:
                    }
                }
            }
            //end user email notification setting

            //get user app notification setting
            $result['app_accepted']=$result['app_rejected']=$result['app_completed']=$result['app_terminated']=0;

            $email_noti=Usernotificationsetting::where('is_deleted',0)
            ->where('user_id',$user_id)
            ->where('type',2)//app notification
            ->orderby('id')
            ->get();

            if(isset($email_noti) && $email_noti!=array())
            {
                foreach($email_noti as $v)
                {
                    switch($v['type_id'])
                    {
                        case 1:
                        $result['app_accepted']=$v['value'];
                        break;

                        case 2:
                        $result['app_rejected']=$v['value'];
                        break;

                        case 3:
                        $result['app_completed']=$v['value'];
                        break;

                        case 4:
                        $result['app_terminated']=$v['value'];
                        break;

                        default:
                    }
                }
            }
            //end user app notification setting


            return $this->SuccessResponse($result);
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }

}
