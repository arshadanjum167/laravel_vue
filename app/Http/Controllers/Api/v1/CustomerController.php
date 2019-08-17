<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Base\ApiController;
use App\Models\Token;
use App\Models\User;
use App\Models\Usercontract;
use App\Models\Contactpackage;
use App\Models\Userworklocationtown;
use App\Models\Userworklocationsuburb;
use App\Models\Searchhistory;
use App\Models\Jobtracking;
use App\Models\Jobterminatehistory;
use App\Models\Job;
use App\Models\Jobcoupon;
use App\Models\Usershortlisted;
use App\Models\Userrating;
use App\Models\Skill;
use App\Models\Setting;
use App\Models\Employerpremiumhistory;
use App\Models\Employerpremium;
use App\Models\Domestictraininginstitute;
use Hash;
use URL;
use ApiFunction,PushNotification,EmailNotification;
use CommonFunction;


class CustomerController extends ApiController
{

    //********************************************************************************
    //Title : Complete/Edit Profile
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 13-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function completeEditProfile(Request $request)
    {
      if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        return $this->ErrorResponse('error_user_not_found',200);
      }

      extract($_POST);
      if($profile_action_type==1)//complete profile
      {
        if($user_type==3)//worker
        {
            $request->validate([
              'user_type'=>'required','first_name'=>'required','second_name'=>'required','dob'=>'required','nationality_id'=>'required',
              'hometown_id'=>'required','hometown_suburb_id'=>'required','address'=>'required','latitude'=>'required','longitude'=>'required',
              'gender'=>'required','kin_first_name'=>'required','kin_last_name'=>'required','kin_contact_number'=>'required','skill_id'=>'required',
              'contract_type'=>'required','preferred_residency'=>'required','proficiency_level'=>'required','document_type'=>'required','document_number'=>'required',/*'document_image'=>'required',*/
              'marital_status_id'=>'required','is_with_kids'=>'required','religion_id'=>'required','desired_work_location'=>'required','language_proficiency_id'=>'required',
              'is_good_conduct_certificate'=>'required','is_experience_with_pets'=>'required','is_education_attained'=>'required',/*'education_id'=>'required','govt_institute_id'=>'required',*/
              'is_specialized_trained'=>'required',/*'specialized_training_id'=>'required','profile_image'=>'required'*/
            ]);
        }
        if($user_type==2)//employee
        {
            $request->validate([
              'user_type'=>'required','first_name'=>'required','second_name'=>'required','nationality_id'=>'required',
              'hometown_id'=>'required','hometown_suburb_id'=>'required','address'=>'required','latitude'=>'required','longitude'=>'required',
              /*'profile_image'=>'required'*/
            ]);
        }

      }
      else //edit profile
      {
        $request->validate([
          'user_type'=>'required',
        ]);
      }

      $user_id = $request->user['id'];
      $user = User::find($user_id);

      if(isset($user) && $user!=array())
      {

            $api_msg='profile_updated';
            if(!isset($device_id)){$device_id=null;}
            if(!isset($device_type)){$device_type=null;}

            if(isset($first_name) && $first_name!=null){
              $user->first_name = $first_name;
            }
            if(isset($second_name) && $second_name!=null){
              $user->second_name = $second_name;
            }

            $country_code=(isset($country_code) && $country_code!=null)?$country_code:'+254';
            if(isset($country_code) && $country_code!=null &&
               isset($contact_number) && $contact_number!=null)
            {
                if($profile_action_type==1)//complete profie
                {
                    $user->country_code = $country_code;
                    $user->contact_number = $contact_number;
                }
                else
                {
                    if($contact_number!=$user->contact_number)
                    {

                        $result_obj = User::where(['is_deleted'=>0,'country_code'=>$country_code,'contact_number'=>$contact_number,'actor'=>$user_type])
                        ->where('id','<>',$user_id)->first();

                        if($result_obj)
                        {
                          return $this->ErrorResponse('mobile_exist_already',200);
                        }
                         //send otp and after verification update the new mobile numer
                         $otp=CommonFunction::generateOtpCode();
                         $user->otp=$otp;
                         $otp_msg = str_replace('{number}',$otp,config('params.otp_text'));

                         CommonFunction::sendsms($country_code.$contact_number,$otp_msg);
                         $api_msg='otp_sent';
                         /*updated by arshad*/
                         if(config('params.environment') == 'dev')
                         {
                             if(isset($user->email) && $user->email!=null)
                             {
                                 $content = 'Welcome to '.config('params.appName').', One Time Password(OTP) to verify your phone number is :'.$otp;
                                 CommonFunction::otpemail($user->email,$user->first_name,$content);
                             }
                         }
                    }
                }
            }

            if(isset($nationality_id) && $nationality_id!=null)
            $user->nationality_id = $nationality_id;

            if(isset($address) && $address!=null)
                $user->address = $address;
            if(isset($latitude) && $latitude!=null)
                $user->latitude = $latitude;
            if(isset($longitude) && $longitude!=null)
                $user->longitude = $longitude;

            //user detail
            if(isset($hometown_id) && $hometown_id!=null)
            {
                $user->detail->town_id = $hometown_id;
            }
            if(isset($hometown_suburb_id) && $hometown_suburb_id!=null)
            {
                $user->detail->suburb_id = $hometown_suburb_id;
            }
            if(isset($dob) && $dob!=null)
            {
                $dob=str_replace('/','-',$dob);
                $user->detail->dob = date('Y-m-d',strtotime($dob));
            }

            if(isset($gender) && $gender!=null)
                $user->detail->gender = $gender;
            if(isset($kin_first_name) && $kin_first_name!=null)
                $user->detail->kin_first_name = $kin_first_name;
            if(isset($kin_last_name) && $kin_last_name!=null)
                $user->detail->kin_second_name = $kin_last_name;

            if(isset($kin_country_code) && $kin_country_code!=null)
                $user->detail->kin_country_code = $kin_country_code;
            if(isset($kin_contact_number) && $kin_contact_number!=null)
                $user->detail->kin_contact_number = $kin_contact_number;
            if(isset($skill_id) && $skill_id!=null)
                $user->detail->skill_id = $skill_id;
            if(isset($contract_type) && $contract_type!=null)
            {
                $user->detail->contract_type = $contract_type;
                if($contract_type==3)
                {
                    $usercontract=array('1','2');
                    //get user contract
                    foreach($usercontract as $v)
                    {
                        $usercontract_data=Usercontract::where(['is_deleted'=>0,'user_id'=>$user->id,'contract_type'=>$v])->first();

                        if(isset($usercontract_data) && $usercontract_data!=array())
                        {
                        }
                        else
                        {
                            $usercontract_data=new Usercontract;
                            $usercontract_data->i_date = date('Y-m-d H:i:s',time());
                            $usercontract_data->i_by = $user->id;
                            $usercontract_data->user_id = $user->id;
                        }
                        $usercontract_data->contract_type = $v;
                        if($v==2 && isset($part_time_desired_pay) && $part_time_desired_pay!=null)
                            $usercontract_data->desired_pay = $part_time_desired_pay;

                        if($v==2 && isset($part_time_not_available))
                            $usercontract_data->not_available_for_work_days = $part_time_not_available;

                        if($v==1 && isset($full_time_desired_pay) && $full_time_desired_pay!=null)
                            $usercontract_data->desired_pay = $full_time_desired_pay;
                        if($v==1 && isset($full_time_not_available) && $full_time_not_available!=null)
                            $usercontract_data->not_available_for_work_days = $full_time_not_available;
                        $usercontract_data->u_date = date('Y-m-d H:i:s',time());
                        $usercontract_data->u_by = $user->id;
                        $usercontract_data->save();

                    }
                }
                else
                {
                    if($contract_type==1 || $contract_type==2)// full time or part
                    {
                        $v=$contract_type;
                        $usercontract_data=Usercontract::where(['is_deleted'=>0,'user_id'=>$user->id,'contract_type'=>$v])->first();

                        if(isset($usercontract_data) && $usercontract_data!=array())
                        {
                        }
                        else
                        {
                            $usercontract_data=new Usercontract;
                            $usercontract_data->i_date = date('Y-m-d H:i:s',time());
                            $usercontract_data->i_by = $user->id;
                            $usercontract_data->user_id = $user->id;
                        }
                        $usercontract_data->contract_type = $v;
                        if($v==2 && isset($part_time_desired_pay) && $part_time_desired_pay!=null)
                            $usercontract_data->desired_pay = $part_time_desired_pay;
                        if($v==2 && isset($part_time_not_available) && $part_time_not_available!=null)
                            $usercontract_data->not_available_for_work_days = $part_time_not_available;
                        if($v==1 && isset($full_time_desired_pay) && $full_time_desired_pay!=null)
                            $usercontract_data->desired_pay = $full_time_desired_pay;
                        if($v==1 && isset($full_time_not_available) && $full_time_not_available!=null)
                            $usercontract_data->not_available_for_work_days = $full_time_not_available;
                        $usercontract_data->u_date = date('Y-m-d H:i:s',time());
                        $usercontract_data->u_by = $user->id;
                        $usercontract_data->save();

                        //delete other contract detail
                        $other_type=1;
                        if($v==1)
                        $other_type=2;

                        $other_usercontract_data=Usercontract::where(['is_deleted'=>0,'user_id'=>$user->id,'contract_type'=>$other_type])->first();
                        if(isset($other_usercontract_data) && $other_usercontract_data!=array())
                        {
                            $other_usercontract_data->is_deleted=1;
                            $other_usercontract_data->u_date = date('Y-m-d H:i:s',time());
                            $other_usercontract_data->u_by = $user->id;
                            $other_usercontract_data->save();
                        }

                    }
                }
            }
            if(isset($preferred_residency) && $preferred_residency!=null)
                $user->detail->preferred_residency = $preferred_residency;
            if(isset($total_experience) && $total_experience!=null)
                $user->detail->experience = $total_experience;
            if(isset($proficiency_level) && $proficiency_level!=null)
                $user->detail->proficiency_level_id = $proficiency_level;

            if(isset($document_type) )
                $user->detail->document_id = $document_type;
            if(isset($document_number) )
                $user->detail->document_value = $document_number;
            //file is remaining
            if(isset($document_image) )
                $user->detail->document_file = $document_image;

            if(isset($marital_status_id) && $marital_status_id!=null)
                $user->detail->marital_status = $marital_status_id;
            if(isset($is_with_kids) && $is_with_kids!=null)
                $user->detail->with_kids = $is_with_kids;
            if(isset($kids_count) && $kids_count!=null)
                $user->detail->kids_count = $kids_count;
            if(isset($religion_id) && $religion_id!=null)
                $user->detail->religion_id = $religion_id;
            if(isset($religion_other) && $religion_other!=null)
                $user->detail->other_religion= $religion_other;

            if(isset($desired_work_location) && $desired_work_location!=null)
            {
                $desired_work_location=json_decode($desired_work_location);

                if(isset($desired_work_location) && $desired_work_location!=array())
                {
                    $town_ids=[];
                    foreach($desired_work_location as $v)
                    {

                        //check home town id in master
                        $userworklocationtown_data=Userworklocationtown::where(['is_deleted'=>0,'user_id'=>$user->id,'town_id'=>$v->desired_hometown_id])->first();

                        if(isset($userworklocationtown_data) && $userworklocationtown_data!=array())
                        {
                        }
                        else
                        {
                            $userworklocationtown_data=new Userworklocationtown;
                            $userworklocationtown_data->i_date = date('Y-m-d H:i:s',time());
                            $userworklocationtown_data->i_by = $user->id;
                            $userworklocationtown_data->user_id = $user->id;
                        }
                        $userworklocationtown_data->town_id = $v->desired_hometown_id;

                        $userworklocationtown_data->u_date = date('Y-m-d H:i:s',time());
                        $userworklocationtown_data->u_by = $user->id;
                        $userworklocationtown_data->save();

                        if(isset($v->desired_subburb) && $v->desired_subburb!=array())
                        {
                            $suburb_ids=[];
                            foreach($v->desired_subburb as $ds)
                            {
                                //check home town id in master
                                $userworklocationsuburb_data=Userworklocationsuburb::where(['is_deleted'=>0,'user_id'=>$user->id,'suburb_id'=>$ds->subburb_id,'user_work_locaton_town_id'=>$userworklocationtown_data->id])->first();

                                if(isset($userworklocationsuburb_data) && $userworklocationsuburb_data!=array())
                                {
                                }
                                else
                                {
                                    $userworklocationsuburb_data=new Userworklocationsuburb;
                                    $userworklocationsuburb_data->i_date = date('Y-m-d H:i:s',time());
                                    $userworklocationsuburb_data->i_by = $user->id;
                                    $userworklocationsuburb_data->user_id = $user->id;
                                }
                                $userworklocationsuburb_data->suburb_id = $ds->subburb_id;
                                $userworklocationsuburb_data->user_work_locaton_town_id = $userworklocationtown_data->id;

                                $userworklocationsuburb_data->u_date = date('Y-m-d H:i:s',time());
                                $userworklocationsuburb_data->u_by = $user->id;
                                $userworklocationsuburb_data->save();
                                $suburb_ids[]=$userworklocationsuburb_data->id;
                            }
                            if(isset($suburb_ids) && $suburb_ids!=array())
                                Userworklocationsuburb::whereNotIn('id',$suburb_ids)->where('user_id',$user->id)->where('user_work_locaton_town_id',$userworklocationtown_data->id)->update(['is_deleted' => 1]);
                        }
                        $town_ids[]=$userworklocationtown_data->id;
                    }
                    //dd($suburb_ids);
                    //delete other user record
                    if(isset($town_ids) && $town_ids!=array())
                        Userworklocationtown::whereNotIn('id',$town_ids)->where('user_id',$user->id)->update(['is_deleted' => 1]);



                }
            }
            if(isset($language_proficiency_id) && $language_proficiency_id!=null)
                $user->detail->language_proficiency_id= $language_proficiency_id;
            if(isset($language_proficiency_other) && $language_proficiency_other!=null)
                $user->detail->language_proficiency_other= $language_proficiency_other;


            if(isset($is_good_conduct_certificate) && $is_good_conduct_certificate!=null)
                $user->detail->good_conduct_certificate= $is_good_conduct_certificate;


            if(isset($good_conduct_certificate_timestamp) && $good_conduct_certificate_timestamp!=null)
            {
                $user->detail->issue_date_good_conduct_certificate=null;
                $good_conduct_certificate_timestamp=str_replace('/','-',$good_conduct_certificate_timestamp);
                $user->detail->issue_date_good_conduct_certificate= date('Y-m-d H:i:s',strtotime($good_conduct_certificate_timestamp));
            }

            if(isset($is_experience_with_pets) && $is_experience_with_pets!=null)
                $user->detail->experience_with_pet= $is_experience_with_pets;
            if(isset($is_education_attained) && $is_education_attained!=null)
                $user->detail->is_education_attained= $is_education_attained;

            if(isset($education_id) && $education_id!=null)
                $user->detail->highest_education_id= $education_id;

            if(isset($education_other) && $education_other!=null)
                $user->detail->other_highest_education= $education_other;

            if(isset($govt_institute_id) && $govt_institute_id!=null)
                $user->detail->govt_institute_id= $govt_institute_id;

            if(isset($is_specialized_trained) && $is_specialized_trained!=null)
                $user->detail->is_specialized_trained= $is_specialized_trained;
            if(isset($specialized_training_id) && $specialized_training_id!=null)
                $user->detail->domestic_training_institute_id= $specialized_training_id;
            if(isset($specialized_training_other) && $specialized_training_other!=null)
            {
                if($specialized_training_id==0)
                {
                    //check name is exist or not
                    $model=Domestictraininginstitute::where('name', 'like', '' . $specialized_training_other. '')
                    ->where('is_deleted',0)
                    ->first();
                    if(isset($model) && $model!=array())
                    {

                    }
                    else
                    {
                        //$user->detail->other_domestic_training_institute= $specialized_training_other;
                        //inser record by this user
                        $model=new Domestictraininginstitute();
                        $model->name=$specialized_training_other;
                        //$model->approved_by_admin=0;
                        $model->approved_by_admin=1;
                        $model->i_by=$user_id;
                        $model->i_date=date('Y-m-d H:i:s');
                        $model->u_by=$user_id;
                        $model->u_date=date('Y-m-d H:i:s');
                        $model->save();
                    }

                    $user->detail->domestic_training_institute_id=$model->id;

                }
            }

            if(isset($is_part_time_desired_pay_negotiable) && $is_part_time_desired_pay_negotiable!='')
                $user->detail->is_part_time_desired_pay_negotiable= $is_part_time_desired_pay_negotiable;
            if(isset($is_full_time_desired_pay_negotiable) && $is_full_time_desired_pay_negotiable!='')
                $user->detail->is_full_time_desired_pay_negotiable= $is_full_time_desired_pay_negotiable;

            //end user detail
            //profile image is remaining
            if(isset($profile_image) && $profile_image!=null)
            $user->profile_image = $profile_image;

            if(isset($about_me) && $about_me!=null)
                $user->about_me = $about_me;

            if($profile_action_type==1)
            $user->is_profile_completed = 1;



            $user->u_by = $user_id;
            $user->u_date = date('Y-m-d H:i:s',time());

            $user->detail->u_by = $user_id;
            $user->detail->u_date = date('Y-m-d H:i:s',time());

            if($user->save() && $user->detail->save())
            {
                //insert worker skill history
                if($user_type==3)//worker
                    CommonFunction::insertworkerskilhistory($user);

                //end worker skill history
                //$result = ApiFunction::userResponse($user);
                //return $this->SuccessResponse($result,'profile_updated');
                $token_result = $this->manageToken($user->id, $device_id, $device_type,$request->user['access_token']);
                $result = ApiFunction::apiLogin($user,$token_result,'');
                return $this->SuccessResponse($result,$api_msg);
            }
            else {
              return $this->ErrorResponse('error_in_save',200);
            }
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }

    //********************************************************************************
    //Title : Get Profile
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 16-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getProfile(Request $request)
    {
        $request->validate([
          'user_id'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
            return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $uid = $request->user['id'];
        $user = User::where(['is_deleted'=>0,'id'=>$uid])->first();

        if(isset($user) && $user!=array())
        {
            $data = User::where(['is_deleted'=>0,'id'=>$user_id])->first();
            if(isset($data) && $data!=array())
            {
                if(!isset($device_id)){$device_id=null;}
                if(!isset($device_type)){$device_type=null;}

                //$token_result = $this->manageToken($data->id, $device_id, $device_type,$request->user['access_token']);
                $token_result['token'] = $request->user['access_token'];
                $token_result['type'] = 'Bearer';
                $result = ApiFunction::apiLogin($data,$token_result,'');
                return $this->SuccessResponse($result,'success');
            }
            else
            {
                return $this->ErrorResponse('error_data_not_found',200);
            }
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }

    }

    //********************************************************************************
    //Title : searched history list
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 16-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function searchHistoryList(Request $request)
    {

        $request->validate([
          'user_type'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id,'actor'=>$user_type])->first();

      if(isset($user) && $user!=array())
      {
            //start suburb data
            $result['recent_searched_suburb_list'] =array();
            $suburb_data = Searchhistory::
            selectRaw('search_histories.*,locations.name as suburbname')
            ->leftjoin('locations', 'locations.id', '=', 'search_histories.type_id')
            ->where('search_histories.is_deleted',0)
            ->where('search_histories.is_active',1)
            ->where('locations.is_deleted',0)
            ->where('locations.is_active',1)
            ->where('search_histories.user_id',$user_id)
            ->where('search_histories.type',2)//suburb
            ->orderby('search_histories.id','desc')
            ->get();
            //dd($suburb_data);
            if(isset($suburb_data) && $suburb_data!=array())
            {
                $i=0;
                foreach ($suburb_data as $key => $value)
                {
                  $result['recent_searched_suburb_list'][$i]['id'] = $value->id;
                  $result['recent_searched_suburb_list'][$i]['suburb_name'] = (isset($value->suburbname) && $value->suburbname!=null)?$value->suburbname:'';

                  $i++;
                }
            }
            //end suburb data

            //start skill data
            $result['recent_searched_skill_list'] =array();
            $suburb_data = Searchhistory::
            selectRaw('search_histories.*,skills.name as skillname')
            ->leftjoin('skills', 'skills.id', '=', 'search_histories.type_id')
            ->where('search_histories.is_deleted',0)
            ->where('search_histories.is_active',1)
            ->where('skills.is_deleted',0)
            ->where('skills.is_active',1)
            ->where('search_histories.user_id',$user_id)
            ->where('search_histories.type',1)//skill
            ->orderby('search_histories.id','desc')
            ->get();
            //dd($suburb_data);
            if(isset($suburb_data) && $suburb_data!=array())
            {
                $i=0;
                foreach ($suburb_data as $key => $value)
                {
                  $result['recent_searched_skill_list'][$i]['id'] = $value->id;
                  $result['recent_searched_skill_list'][$i]['skill_name'] = (isset($value->skillname) && $value->skillname!=null)?$value->skillname:'';

                  $i++;
                }
            }
            //end skill data
            return $this->SuccessResponse($result);
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }
    //********************************************************************************
    //Title : Get employer Home data
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getEmployerhomedata(Request $request)
    {

        $request->validate([
          'start'=>'required',
          'limit'=>'required',
          //'type'=>'required',
          'latitude'=>'required',
          'longitude'=>'required',
        ]);

        //if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
      extract($_POST);
      $user_id = '';
      if(isset($request->user['id']) && $request->user['id'] !=0)
        $user_id=$request->user['id'];

      // dd($user_id);
      //$user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();
      //
      //if(isset($user) && $user!=array())
      //{
            $start=(isset($start) && $start!='')?$start:0;
            $limit=(isset($limit) && $limit!='')?$limit+1:11;

            //start search employeer
            $andwhere1='';
            if(isset($contract_type) && $contract_type!=null)
            {
                if($contract_type!=3)
                {
                    $andwhere1='(user_details.contract_type='.$contract_type.' or user_details.contract_type=3)';
                }

            }

            $andwhere2='';
            if(isset($residency) && $residency!=null)
            {
                if($residency!=3)
                $andwhere2='user_details.preferred_residency='.$residency;
            }

            $andwhere3='';
            if(isset($suburb_id) && $suburb_id!=null)
            {
              $andwhere3='user_details.suburb_id IN ('.$suburb_id.')';
              if($user_id != "")
              {
                $ids = explode(",",$suburb_id);
                foreach ($ids as $key => $id)
                {
                  $sh = Searchhistory::where(['is_deleted'=>0,'user_id'=>$user_id,'type_id'=>$id,'type'=>2])->first();
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

            $andwhere4='';
            if(isset($skill_id) && $skill_id!=null)
            {
              $andwhere4='user_details.skill_id IN ('.$skill_id.')';
              if($user_id != "")
              {
                $ids = explode(",",$skill_id);
                foreach ($ids as $key => $id)
                {
                  $sh = Searchhistory::where(['is_deleted'=>0,'user_id'=>$user_id,'type_id'=>$id,'type'=>1])->first();
                  if($sh == array())
                    $sh = New Searchhistory;
                  $sh->user_id = $user_id;
                  $sh->type = 1;
                  $sh->type_id = $id;
                  $sh->i_by = $user_id;
                  $sh->i_date = date('Y-m-d H:i:s',time());
                  $sh->u_by = $user_id;
                  $sh->u_date = date('Y-m-d H:i:s',time());
                  $sh->save();
                }
              }
            }
            //end search employeer

            //get location
            //$latitude = (isset($user->latitude) && $user->latitude!='')?$user->latitude:0;
            //$longitude = (isset($user->longitude) && $user->longitude!='')?$user->longitude:0;

            $selected_urs = array();
            $selected_urs = Usershortlisted::where(['is_deleted'=>0,'user_id'=>$user_id,'type_value'=>1])->pluck('worker_id');

            // dd($selected_urs);
            // if($selected_urs != array()){
            //   foreach ($selected_urs as $key => $urs){
            //
            //   }
            // }

            $radius = 100;
            $radius_query = "(6371 * acos(cos(radians(" . $latitude . "))
                               * cos(radians(users.latitude))
                               * cos(radians(users.longitude)
                               - radians(" . $longitude . "))
                               + sin(radians(" . $latitude . "))
                               * sin(radians(users.latitude))))";

            $result['is_last'] = 1;

            $result['contact_count'] =CommonFunction::getEmployercontactcount($user_id);
            //start suburb data
            $result['worker_list'] =array();
            $worker_data = User::
            selectRaw("{$radius_query} AS distance,users.*,users.id as uid,locations.name as town_name,l.name as suburb_name,user_details.premium_worker_status")
            ->leftjoin('user_details', 'user_details.user_id', '=', 'users.id')
            ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
            ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
            // ->leftjoin('user_shortlisted','user_shortlisted.worker_id','!=','users.id')
            // ->where('user_shortlisted.user_id',$user_id)
            //->leftjoin('jobs as j', 'j.selected_worker_id', '=', 'users.id')

            ->where('users.is_deleted',0)
            ->whereNotIn('users.id',$selected_urs)
            // ->where('user_shortlisted.user_id',$user_id)
            //->where('user_shortlisted.is_deleted',0)
            //->where('user_shortlisted.is_active',1)


            ->where('users.actor',3)
            ->where('users.is_active',1);
            //if($andwhere!='')
            //$worker_data=$worker_data->whereRaw($andwhere);
            if($andwhere1!='')
            $worker_data=$worker_data->whereRaw($andwhere1);
            if($andwhere2!='')
            $worker_data=$worker_data->whereRaw($andwhere2);
            if($andwhere3!='')
            $worker_data=$worker_data->whereRaw($andwhere3);
            if($andwhere4!='')
            $worker_data=$worker_data->whereRaw($andwhere4);

            //if($type==1)//interested/shortlisted
            //    $worker_data=$worker_data->where('user_shortlisted.type',1);
            //else if($type==2) //2= final shorlisted
            //    $worker_data=$worker_data->where('user_shortlisted.type',2);

            $worker_data=$worker_data->whereRaw("{$radius_query} < ?", [$radius]);
            //->where('locations.is_deleted',0)
            //->where('locations.is_active',1)
            $worker_data=$worker_data->skip($start)->take($limit)
            ->orderByRaw("FIELD(premium_worker_status , '2', '1', '3') ASC,distance")
            // ->groupBy('uid')
            ->get();
            //dd($worker_data);
            if(isset($worker_data) && $worker_data!=array())
            {
                $i=0;
                foreach ($worker_data as $key => $value)
                {
                    $result['worker_list'][$i]['id'] = $value->id;
                    $result['worker_list'][$i]['worker_firstname']=(isset($value->first_name) && $value->first_name!=null)?$value->first_name:'';
                    $result['worker_list'][$i]['worker_secondname']=(isset($value->second_name) && $value->second_name!=null)?$value->second_name:'';
                    $result['worker_list'][$i]['profile_image']=(isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                    $result['worker_list'][$i]['skill_name']=(isset($value->detail->getskill->name) && $value->detail->getskill->name!=null)?$value->detail->getskill->name:'';

                    $result['worker_list'][$i]['proficiency_level_id']=(isset($value->detail->proficiency_level_id) && $value->detail->proficiency_level_id!=null)?(int)$value->detail->proficiency_level_id:0;
                    $result['worker_list'][$i]['is_worker_premium']=0;
                    if(isset($value->premium_worker_status) && $value->premium_worker_status!=null && $value->premium_worker_status==2)
                    $result['worker_list'][$i]['is_worker_premium']=1;

                    $result['worker_list'][$i]['preferred_residency']=(isset($value->detail->preferred_residency) && $value->detail->preferred_residency!=null)?(int)$value->detail->preferred_residency:0;

                    $result['worker_list'][$i]['town_suburb']='';
                    if(isset($value->town_name) && $value->town_name!=null)
                    $result['worker_list'][$i]['town_suburb'] .= (isset($value->town_name) && $value->town_name!=null)?$value->town_name:'';
                    if(isset($value->suburb_name) && $value->suburb_name!=null)
                    {
                        $result['worker_list'][$i]['town_suburb'] .=', ';
                        $result['worker_list'][$i]['town_suburb'] .=(isset($value->suburb_name) && $value->suburb_name!=null)?$value->suburb_name:'';
                    }

                    $result['worker_list'][$i]['skill_id']=(isset($value->detail->skill_id) && $value->detail->skill_id!=null)?(int)$value->detail->skill_id:0;
                    $result['worker_list'][$i]['experience']=(isset($value->detail->experience) && $value->detail->experience!=null)?(float)$value->detail->experience:0;
                    $result['worker_list'][$i]['contract_type']=(isset($value->detail->contract_type) && $value->detail->contract_type!=null)?(int)$value->detail->contract_type:0;
                    $result['worker_list'][$i]['part_time_desired_pay']=0;
                    $result['worker_list'][$i]['part_time_not_available']='';
                    $result['worker_list'][$i]['full_time_desired_pay']=0;
                    $result['worker_list'][$i]['full_time_not_available']='';
                    $usercontract= Usercontract::where(['is_deleted'=>0,'user_id'=>$value->id])->get();
                    if(isset($usercontract) && $usercontract!=array())
                    {
                        foreach($usercontract as $v)
                        {
                            if($v->contract_type==1)
                            {
                                $result['worker_list'][$i]['full_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['full_time_not_available']=$v->not_available_for_work_days;
                            }
                            else if($v->contract_type==2)
                            {
                                $result['worker_list'][$i]['part_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['part_time_not_available']=$v->not_available_for_work_days;
                            }
                        }
                    }

                    $result['worker_list'][$i]['distance_in_km']=(isset($value->distance) && $value->distance!=null)?$value->distance:'';

                    $result['worker_list'][$i]['language_id']=(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)?$value->detail->language_proficiency_id:'0';
                    //$result['worker_list'][$i]['language_name']=(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null && isset(config('params.language_proficiency')[$value->detail->language_proficiency_id]) && config('params.language_proficiency')[$value->detail->language_proficiency_id]!=null)?config('params.language_proficiency')[$value->detail->language_proficiency_id]:'';
                    $result['worker_list'][$i]['language_name']='';
                    if(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)
                    {
                        $langs=explode(',',$value->detail->language_proficiency_id);
                        if(isset($langs) && $langs!=array())
                        {
                            $l=0;
                            foreach($langs as $v)
                            {
                                $result['worker_list'][$i]['language_name'].=(isset(config('params.language_proficiency')[$v]) && config('params.language_proficiency')[$v]!=null)?config('params.language_proficiency')[$v]:'';
                                if(count($langs)-1>$l)
                                $result['worker_list'][$i]['language_name'].=',';
                                $l++;
                            }
                        }

                    }
                    $result['worker_list'][$i]['is_part_time_desired_pay_negotiable']=(isset($value->detail->is_part_time_desired_pay_negotiable) && $value->detail->is_part_time_desired_pay_negotiable!=null)?(int)$value->detail->is_part_time_desired_pay_negotiable:0;
                    $result['worker_list'][$i]['is_full_time_desired_pay_negotiable']=(isset($value->detail->is_full_time_desired_pay_negotiable) && $value->detail->is_full_time_desired_pay_negotiable!=null)?(int)$value->detail->is_full_time_desired_pay_negotiable:0;

                    if(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id==0)
                        $result['worker_list'][$i]['language_name']=(isset($value->detail->language_proficiency_other) && $value->detail->language_proficiency_other!=null)?$value->detail->language_proficiency_other:'';

                    $result['worker_list'][$i]['education_id']=(isset($value->detail->heighest_education_id) && $value->detail->heighest_education_id!=null)?(int)$value->detail->heighest_education_id:0;
                    $result['worker_list'][$i]['education_name']=(isset($value->detail->geteducation->name) && $value->detail->geteducation->name!=null)?$value->detail->geteducation->name:'';
                    if(isset($value->detail->heighest_education_id) && $value->detail->heighest_education_id==0)
                        $result['worker_list'][$i]['education_name']=(isset($value->detail->other_highest_education) && $value->detail->other_highest_education!=null)?$value->detail->other_highest_education:'';

                    $result['worker_list'][$i]['total_percentage']=0;
                    $getWokerjobrating=CommonFunction::getWokeralljobavgrating($value->id);
                    if(isset($getWokerjobrating) && $getWokerjobrating!=array())
                        $result['worker_list'][$i]['total_percentage']=(isset($getWokerjobrating['total']) && $getWokerjobrating['total']!=null)?$getWokerjobrating['total']:0;

                    //$result['worker_list'][$i]['worker_status']=0;
                    //if($value->type==1)
                    //{
                    //    $result['worker_list'][$i]['worker_status']=2;
                    //    if($value->job_id!=0)
                    //    {
                    //        //get job detail
                    //        $result['worker_list'][$i]['worker_status']=1;
                    //        $result['worker_list'][$i]['job_id']=$value->job_id;
                    //        $result['worker_list'][$i]['proposed_pay_from']=(isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)?(int)$value->proposed_pay_from:0;
                    //        $result['worker_list'][$i]['proposed_pay_to']=(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)?(int)$value->proposed_pay_to:0;
                    //        $result['worker_list'][$i]['service_needed_type']=(isset($value->service_needed_type) && $value->service_needed_type!=null)?(int)$value->service_needed_type:0;
                    //        $result['worker_list'][$i]['service_needed_description']=(isset($value->description) && $value->description!=null)?$value->description:'';
                    //
                    //    }
                    //
                    //}

                  $i++;
                }
            }
            if($i == $limit){
                unset($result['worker_list'][$i-1]);
                $result['is_last'] =0;
            }
            //end suburb data


            return $this->SuccessResponse($result);
      //}
      //else {
      //  return $this->ErrorResponse('error_user_not_found',200);
      //}
    }
    //********************************************************************************
    //Title : Get employer's interested and shortlisted
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 02-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getEmployershortlistdata(Request $request)
    {

        $request->validate([
          'start'=>'required',
          'limit'=>'required',
          'type'=>'required',
          'selection_type'=>'required',
          //'latitude'=>'required',
          //'longitude'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();

      if(isset($user) && $user!=array())
      {
            $start=(isset($start) && $start!='')?$start:0;
            $limit=(isset($limit) && $limit!='')?$limit+1:11;

            //start search employeer
            $andwhere1='';
            if(isset($contract_type) && $contract_type!=null)
            {
                if($contract_type!=3)
                {
                    $andwhere1='(user_details.contract_type='.$contract_type.' or user_details.contract_type=3)';
                }

            }
            $andwhere2='';
            if(isset($skill_id) && $skill_id!=null)
            {
              $andwhere2='user_details.skill_id IN ('.$skill_id.')';
            }


            //get location
            $latitude = (isset($user->latitude) && $user->latitude!='')?$user->latitude:0;
            $longitude = (isset($user->longitude) && $user->longitude!='')?$user->longitude:0;

            $radius = 100;
            $radius_query = "(6371 * acos(cos(radians(" . $latitude . "))
                              * cos(radians(users.latitude))
                              * cos(radians(users.longitude)
                              - radians(" . $longitude . "))
                              + sin(radians(" . $latitude . "))
                              * sin(radians(users.latitude))))";

            $result['is_last'] = 1;

            $result['contact_count'] =CommonFunction::getEmployercontactcount($user_id);
            //start suburb data
            $result['worker_list'] =array();
            $worker_data = Usershortlisted::
            selectRaw("{$radius_query} AS distance,user_shortlisted.id as master_id,user_shortlisted.worker_id as worker_id,locations.name as town_name,l.name as suburb_name,user_shortlisted.type,user_shortlisted.job_id,j.proposed_pay_from,j.proposed_pay_to,j.service_needed_type,j.description,user_shortlisted.is_from_worker,user_shortlisted.u_date,user_shortlisted.i_date,users.*")
            //selectRaw("{$radius_query} AS distance,users.*,locations.name as town_name,l.name as suburb_name,user_shortlisted.type,user_shortlisted.job_id,j.proposed_pay_from,j.proposed_pay_to,j.service_needed_type,j.description")
            ->leftjoin('users', 'users.id', '=', 'user_shortlisted.worker_id')
            ->leftjoin('user_details', 'user_details.user_id', '=', 'users.id')
            ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
            ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
            ->leftjoin('jobs as j', 'j.id', '=', 'user_shortlisted.job_id')

            ->where('users.is_deleted',0)
            ->where('user_shortlisted.is_deleted',0)
            ->where('user_shortlisted.is_active',1)
            ->where('users.actor',3)
            ->where('users.is_active',1)
            ->where('user_shortlisted.type_value',1)
            ->where('user_shortlisted.user_id',$user_id);
            //if($andwhere!='')
            //$worker_data=$worker_data->whereRaw($andwhere);


            if($type==1)//interested/shortlisted
                $worker_data=$worker_data->where('user_shortlisted.type',1);
            else if($type==2) //2= final shorlisted
                $worker_data=$worker_data->where('user_shortlisted.type',2);

            if($selection_type==1)//interested by employer
            {
                $worker_data=$worker_data->whereRaw('(user_shortlisted.is_from_worker=0 OR (user_shortlisted.is_from_worker=1 and user_shortlisted.from_worker_status=1))');
            }
            elseif($selection_type==2)//Apply by worker
            {
                $worker_data=$worker_data->where('user_shortlisted.is_from_worker',1);
                $worker_data=$worker_data->where('user_shortlisted.from_worker_status',0);
            }
            if($andwhere1!='')
            $worker_data=$worker_data->whereRaw($andwhere1);

            if($andwhere2!='')
            $worker_data=$worker_data->whereRaw($andwhere2);

            //$worker_data=$worker_data->whereRaw("{$radius_query} < ?", [$radius]);
            //->where('locations.is_deleted',0)
            //->where('locations.is_active',1)
            $worker_data=$worker_data->skip($start)->take($limit)
            ->orderby('user_shortlisted.id','desc')
            ->get();

             //dd($worker_data);
            if(isset($worker_data) && $worker_data!=array())
            {
                $i=0;
                foreach ($worker_data as $key => $value)
                {
                    // dd($value->detail);
                    $result['worker_list'][$i]['id'] = $value->master_id;
                    $result['worker_list'][$i]['worker_id'] = $value->id;
                    $result['worker_list'][$i]['worker_firstname']=(isset($value->first_name) && $value->first_name!=null)?$value->first_name:'';
                    $result['worker_list'][$i]['worker_secondname']=(isset($value->second_name) && $value->second_name!=null)?$value->second_name:'';
                    $result['worker_list'][$i]['profile_image']=(isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';

                    $result['worker_list'][$i]['skill_name']=(isset($value->detail->getskill->name) && $value->detail->getskill->name!=null)?$value->detail->getskill->name:'';
                    $result['worker_list'][$i]['proficiency_level_id']=(isset($value->detail->proficiency_level_id) && $value->detail->proficiency_level_id!=null)?(int)$value->detail->proficiency_level_id:0;
                    $result['worker_list'][$i]['is_worker_premium'] = (isset($value->is_employer_premium) && $value->is_employer_premium!=null)?(int)$value->is_employer_premium:0;
                    $result['worker_list'][$i]['preferred_residency']=(isset($value->detail->preferred_residency) && $value->detail->preferred_residency!=null)?(int)$value->detail->preferred_residency:0;

                    $result['worker_list'][$i]['town_suburb']='';
                    if(isset($value->town_name) && $value->town_name!=null)
                    $result['worker_list'][$i]['town_suburb'] .= (isset($value->town_name) && $value->town_name!=null)?$value->town_name:'';
                    if(isset($value->suburb_name) && $value->suburb_name!=null)
                    {
                        $result['worker_list'][$i]['town_suburb'] .=', ';
                        $result['worker_list'][$i]['town_suburb'] .=(isset($value->suburb_name) && $value->suburb_name!=null)?$value->suburb_name:'';
                    }
                    // dd($value->detail);

                    $result['worker_list'][$i]['skill_id']=(isset($value->detail->skill_id) && $value->detail->skill_id!=null)?(int)$value->detail->skill_id:0;
                    $result['worker_list'][$i]['experience']=(isset($value->detail->experience) && $value->detail->experience!=null)?(float)$value->detail->experience:0;
                    $result['worker_list'][$i]['contract_type']=(isset($value->detail->contract_type) && $value->detail->contract_type!=null)?(int)$value->detail->contract_type:0;

                    $result['worker_list'][$i]['part_time_desired_pay']=0;
                    $result['worker_list'][$i]['part_time_not_available']='';
                    $result['worker_list'][$i]['full_time_desired_pay']=0;
                    $result['worker_list'][$i]['full_time_not_available']='';
                    $usercontract= Usercontract::where(['is_deleted'=>0,'user_id'=>$value->id])->get();
                    if(isset($usercontract) && $usercontract!=array())
                    {
                        foreach($usercontract as $v)
                        {
                            if($v->contract_type==1)
                            {
                                $result['worker_list'][$i]['full_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['full_time_not_available']=$v->not_available_for_work_days;
                            }
                            else if($v->contract_type==2)
                            {
                                $result['worker_list'][$i]['part_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['part_time_not_available']=$v->not_available_for_work_days;
                            }
                        }
                    }

                    $result['worker_list'][$i]['dob']='';
                    $result['worker_list'][$i]['age']=0;
                    if(isset($value->detail->dob) && $value->detail->dob!=null)
                    {
                        $result['worker_list'][$i]['dob']= date(config('params.new_date_format'),strtotime($value->detail->dob));
                        $result['worker_list'][$i]['age']=commonFunction::calculateAgefromdob($value->detail->dob);
                    }


                    //get worker contracts
                    $result['worker_list'][$i]['contract_type']=(isset($value->detail->contract_type) && $value->detail->contract_type!=null)?(int)$value->detail->contract_type:0;
                    $result['worker_list'][$i]['part_time_desired_pay']=0;
                    $result['worker_list'][$i]['part_time_not_available']='';
                    $result['worker_list'][$i]['full_time_desired_pay']=0;
                    $result['worker_list'][$i]['full_time_not_available']='';
                    $usercontract= Usercontract::where(['is_deleted'=>0,'user_id'=>$value->id])->get();
                    if(isset($usercontract) && $usercontract!=array())
                    {
                        foreach($usercontract as $v)
                        {
                            if($v->contract_type==1)
                            {
                                $result['worker_list'][$i]['full_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['full_time_not_available']=$v->not_available_for_work_days;
                            }
                            else if($v->contract_type==2)
                            {
                                $result['worker_list'][$i]['part_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['part_time_not_available']=$v->not_available_for_work_days;
                            }
                        }
                    }

                    $result['worker_list'][$i]['total_percentage']=0;
                    $getWokerjobrating=CommonFunction::getWokeralljobavgrating($value->id);
                    if(isset($getWokerjobrating) && $getWokerjobrating!=array())
                    {
                        $result['worker_list'][$i]['total_percentage']=(isset($getWokerjobrating['total']) && $getWokerjobrating['total']!=null)?$getWokerjobrating['total']:0;
                    }

                    $result['worker_list'][$i]['distance_in_km']=(isset($value->distance) && $value->distance!=null)?$value->distance:'';

                    $result['worker_list'][$i]['language_id']=(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)?$value->detail->language_proficiency_id:'0';
                    $result['worker_list'][$i]['language_name']='';
                    if(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)
                    {
                        $langs=explode(',',$value->detail->language_proficiency_id);
                        if(isset($langs) && $langs!=array())
                        {
                            $l=0;
                            foreach($langs as $v)
                            {
                                $result['worker_list'][$i]['language_name'].=(isset(config('params.language_proficiency')[$v]) && config('params.language_proficiency')[$v]!=null)?config('params.language_proficiency')[$v]:'';
                                if(count($langs)-1>$l)
                                $result['worker_list'][$i]['language_name'].=',';
                                $l++;
                            }
                        }

                    }

                    $result['worker_list'][$i]['application_date']=0;
                    if($selection_type==2)
                    {
                        $result['worker_list'][$i]['application_date']=(isset($value->i_date) && $value->i_date!=null)?strtotime($value->i_date):0;
                    }

                    // $result['worker_list'][$i]['language_name']=(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)?config('params.language_proficiency')[$value->detail->language_proficiency_id]:'';
                    if(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id==0)
                        $result['worker_list'][$i]['language_name']=(isset($value->detail->language_proficiency_other) && $value->detail->language_proficiency_other!=null)?$value->detail->language_proficiency_other:'';

                    $result['worker_list'][$i]['education_id']=(isset($value->detail->heighest_education_id) && $value->detail->heighest_education_id!=null)?(int)$value->detail->heighest_education_id:0;
                    $result['worker_list'][$i]['education_name']=(isset($value->detail->geteducation->name) && $value->detail->geteducation->name!=null)?$value->detail->geteducation->name:'';
                    if(isset($value->detail->heighest_education_id) && $value->detail->heighest_education_id==0)
                        $result['worker_list'][$i]['education_name']=(isset($value->detail->other_highest_education) && $value->detail->other_highest_education!=null)?$value->detail->other_highest_education:'';

                    $result['worker_list'][$i]['total_percentage']=0;
                    $getWokerjobrating=CommonFunction::getWokeralljobavgrating($value->id);
                    if(isset($getWokerjobrating) && $getWokerjobrating!=array())
                    {
                        $result['worker_list'][$i]['total_percentage']=(isset($getWokerjobrating['total']) && $getWokerjobrating['total']!=null)?$getWokerjobrating['total']:0;
                    }
                    //$result['worker_list'][$i]['worker_status']=0;
                    $result['worker_list'][$i]['worker_status']=1;
                    if($value->is_from_worker==0)
                    {
                        $result['worker_list'][$i]['worker_status']=2;
                    }
                    // if($value->type==1)
                    // {
                        //$result['worker_list'][$i]['worker_status']= $value->type == 1?1:0;

                        if($value->job_id!=0)
                        {
                            //get job detail
                            //$result['worker_list'][$i]['worker_status']=1;
                            $result['worker_list'][$i]['job_id']=$value->job_id;
                            $result['worker_list'][$i]['proposed_pay_from']=(isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)?(int)$value->proposed_pay_from:0;
                            $result['worker_list'][$i]['proposed_pay_to']=(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)?(int)$value->proposed_pay_to:0;
                            $result['worker_list'][$i]['service_needed_type']=(isset($value->service_needed_type) && $value->service_needed_type!=null)?(int)$value->service_needed_type:0;
                            $result['worker_list'][$i]['service_needed_description']=(isset($value->description) && $value->description!=null)?$value->description:'';

                        }else {
                          // $result['worker_list'][$i]['worker_status']=1;
                          $result['worker_list'][$i]['job_id']=$value->job_id ?? 0;
                          $result['worker_list'][$i]['proposed_pay_from']=(isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)?(int)$value->proposed_pay_from:0;
                          $result['worker_list'][$i]['proposed_pay_to']=(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)?(int)$value->proposed_pay_to:0;
                          $result['worker_list'][$i]['service_needed_type']=(isset($value->service_needed_type) && $value->service_needed_type!=null)?(int)$value->service_needed_type:0;
                          $result['worker_list'][$i]['service_needed_description']=(isset($value->description) && $value->description!=null)?$value->description:'';
                        }

                    // }

                  $i++;
                }
            }
            if($i == $limit){
                unset($result['worker_list'][$i-1]);
                $result['is_last'] =0;
            }
            //end suburb data


            return $this->SuccessResponse($result);
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }
    public function getEmployerhomedataold(Request $request)
    {

        $request->validate([
          'start'=>'required',
          'limit'=>'required',
          'type'=>'required',
          'latitude'=>'required',
          'longitude'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();

      if(isset($user) && $user!=array())
      {
            $start=(isset($start) && $start!='')?$start:0;
            $limit=(isset($limit) && $limit!='')?$limit+1:11;

            //get intereted woker ids
            $andwhere='';
            switch($type)
            {
                case 1: //intrested=shortlisted
                //$andwhere='0';
                //$intrested_uids= Jobtracking::
                //selectRaw('job_tracking.id,job_tracking.worker_id,j.user_id')
                //->leftjoin('jobs as j', 'j.id', '=', 'job_tracking.job_id')
                //->where('job_tracking.is_deleted',0)
                //->where('job_tracking.is_active',1)
                //->where(['j.user_id'=>$user_id])
                ////->skip($start)->take($limit)
                //->orderby('job_tracking.id')
                //->get()
                //->toArray();
                //if(isset($intrested_uids) && $intrested_uids!=array())
                //{
                //    $intrested_uids=array_pluck($intrested_uids, 'worker_id','worker_id');
                //    $andwhere='users.id IN('.implode(",",$intrested_uids).')';
                //}
                //break;
                $andwhere='0';
                $shortlisted_uids= Usershortlisted::
                selectRaw('id,worker_id')
                ->where('is_deleted',0)
                ->where('is_active',1)
                ->where('type',1)//shortliested
                ->where('type_value',1)
                ->where(['user_id'=>$user_id])
                //->skip($start)->take($limit)
                ->orderby('id')
                ->get()
                ->toArray();
                if(isset($shortlisted_uids) && $shortlisted_uids!=array())
                {
                    $shortlisted_uids=array_pluck($shortlisted_uids, 'worker_id','worker_id');
                    $andwhere='users.id IN('.implode(",",$shortlisted_uids).')';
                }
                break;

                //case 2: //shorlisted
                //$andwhere='0';
                //$shortlisted_uids= Usershortlisted::
                //selectRaw('id,worker_id')
                //->where('is_deleted',0)
                //->where('is_active',1)
                //->where('type',1)//shortliested
                //->where('type_value',1)
                //->where(['user_id'=>$user_id])
                ////->skip($start)->take($limit)
                //->orderby('id')
                //->get()
                //->toArray();
                //if(isset($shortlisted_uids) && $shortlisted_uids!=array())
                //{
                //    $shortlisted_uids=array_pluck($shortlisted_uids, 'worker_id','worker_id');
                //    $andwhere='users.id IN('.implode(",",$shortlisted_uids).')';
                //}
                //break;

                case 2: //final shorlisted
                $andwhere='0';
                $shortlisted_uids= Usershortlisted::
                selectRaw('id,worker_id')
                ->where('is_deleted',0)
                ->where('is_active',1)
                ->where('type',2)//final shortliested
                ->where('type_value',1)
                ->where(['user_id'=>$user_id])
                //->skip($start)->take($limit)
                ->orderby('id')
                ->get()
                ->toArray();
                if(isset($shortlisted_uids) && $shortlisted_uids!=array())
                {
                    $shortlisted_uids=array_pluck($shortlisted_uids, 'worker_id','worker_id');
                    $andwhere='users.id IN('.implode(",",$shortlisted_uids).')';
                }
                break;
            }
            //end get intereted woker ids

            //start search employeer
            $andwhere1='';
            if(isset($contract_type) && $contract_type!=null)
            {
                if($contract_type!=3)
                $andwhere1='user_details.contract_type='.$contract_type;
            }

            $andwhere2='';
            if(isset($residency) && $residency!=null)
            {
                if($residency!=3)
                $andwhere2='user_details.preferred_residency='.$residency;
            }

            $andwhere3='';
            if(isset($suburb_id) && $suburb_id!=null)
                $andwhere3='user_details.suburb_id IN ('.$suburb_id.')';

            $andwhere4='';
            if(isset($skill_id) && $skill_id!=null)
                $andwhere4='user_details.skill_id IN ('.$skill_id.')';
            //end search employeer

            //get location
            //$latitude = (isset($user->latitude) && $user->latitude!='')?$user->latitude:0;
            //$longitude = (isset($user->longitude) && $user->longitude!='')?$user->longitude:0;

            $radius = 100;
            $radius_query = "(6371 * acos(cos(radians(" . $latitude . "))
                               * cos(radians(users.latitude))
                               * cos(radians(users.longitude)
                               - radians(" . $longitude . "))
                               + sin(radians(" . $latitude . "))
                               * sin(radians(users.latitude))))";

            $result['is_last'] = 1;

            $result['contact_count'] =0;
            //start suburb data
            $result['worker_list'] =array();
            $worker_data = User::
            selectRaw("{$radius_query} AS distance,users.*,locations.name as town_name,l.name as suburb_name")
            ->leftjoin('user_details', 'user_details.user_id', '=', 'users.id')
            ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
            ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
            ->where('users.is_deleted',0)
            ->where('users.actor',3)
            ->where('users.is_active',1);
            if($andwhere!='')
            $worker_data=$worker_data->whereRaw($andwhere);
            if($andwhere1!='')
            $worker_data=$worker_data->whereRaw($andwhere1);
            if($andwhere2!='')
            $worker_data=$worker_data->whereRaw($andwhere2);
            if($andwhere3!='')
            $worker_data=$worker_data->whereRaw($andwhere3);
            if($andwhere4!='')
            $worker_data=$worker_data->whereRaw($andwhere4);

            $worker_data=$worker_data->whereRaw("{$radius_query} < ?", [$radius]);
            //->where('locations.is_deleted',0)
            //->where('locations.is_active',1)
            $worker_data=$worker_data->skip($start)->take($limit)
            ->orderby('distance')
            ->get();
            //dd($worker_data);
            if(isset($worker_data) && $worker_data!=array())
            {
                $i=0;
                foreach ($worker_data as $key => $value)
                {
                    $result['worker_list'][$i]['id'] = $value->id;
                    $result['worker_list'][$i]['worker_firstname']=(isset($value->first_name) && $value->first_name!=null)?$value->first_name:'';
                    $result['worker_list'][$i]['worker_secondname']=(isset($value->second_name) && $value->second_name!=null)?$value->second_name:'';
                    $result['worker_list'][$i]['profile_image']=(isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                    $result['worker_list'][$i]['skill_name']=(isset($value->detail->getskill->name) && $value->detail->getskill->name!=null)?$value->detail->getskill->name:'';

                    $result['worker_list'][$i]['proficiency_level_id']=(isset($value->detail->proficiency_level_id) && $value->detail->proficiency_level_id!=null)?(int)$value->detail->proficiency_level_id:0;
                    $result['worker_list'][$i]['is_worker_premium'] = (isset($value->is_employer_premium) && $value->is_employer_premium!=null)?(int)$value->is_employer_premium:0;
                    $result['worker_list'][$i]['preferred_residency']=(isset($value->detail->preferred_residency) && $value->detail->preferred_residency!=null)?(int)$value->detail->preferred_residency:0;

                    $result['worker_list'][$i]['town_suburb']='';
                    if(isset($value->town_name) && $value->town_name!=null)
                    $result['worker_list'][$i]['town_suburb'] .= (isset($value->town_name) && $value->town_name!=null)?$value->town_name:'';
                    if(isset($value->suburb_name) && $value->suburb_name!=null)
                    {
                        $result['worker_list'][$i]['town_suburb'] .=', ';
                        $result['worker_list'][$i]['town_suburb'] .=(isset($value->suburb_name) && $value->suburb_name!=null)?$value->suburb_name:'';
                    }

                    $result['worker_list'][$i]['skill_id']=(isset($value->detail->skill_id) && $value->detail->skill_id!=null)?(int)$value->detail->skill_id:0;
                    $result['worker_list'][$i]['experience']=(isset($value->detail->experience) && $value->detail->experience!=null)?(float)$value->detail->experience:0;
                    $result['worker_list'][$i]['contract_type']=(isset($value->detail->contract_type) && $value->detail->contract_type!=null)?(int)$value->detail->contract_type:0;
                    $result['worker_list'][$i]['part_time_desired_pay']=0;
                    $result['worker_list'][$i]['part_time_not_available']='';
                    $result['worker_list'][$i]['full_time_desired_pay']=0;
                    $result['worker_list'][$i]['full_time_not_available']='';
                    $usercontract= Usercontract::where(['is_deleted'=>0,'user_id'=>$value->id])->get();
                    if(isset($usercontract) && $usercontract!=array())
                    {
                        foreach($usercontract as $v)
                        {
                            if($v->contract_type==1)
                            {
                                $result['worker_list'][$i]['full_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['full_time_not_available']=$v->not_available_for_work_days;
                            }
                            else if($v->contract_type==2)
                            {
                                $result['worker_list'][$i]['part_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['part_time_not_available']=$v->not_available_for_work_days;
                            }
                        }
                    }

                    $result['worker_list'][$i]['distance_in_km']=(isset($value->distance) && $value->distance!=null)?$value->distance:'';

                    $result['worker_list'][$i]['language_id']=(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)?$value->detail->language_proficiency_id:'0';
                    $result['worker_list'][$i]['language_name']='';
                    if(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)
                    {
                        $langs=explode(',',$value->detail->language_proficiency_id);
                        if(isset($langs) && $langs!=array())
                        {
                            $l=0;
                            foreach($langs as $v)
                            {
                                $result['worker_list'][$i]['language_name'].=(isset(config('params.language_proficiency')[$v]) && config('params.language_proficiency')[$v]!=null)?config('params.language_proficiency')[$v]:'';
                                if(count($langs)-1>$l)
                                $result['worker_list'][$i]['language_name'].=',';
                                $l++;
                            }
                        }

                    }
                    // $result['worker_list'][$i]['language_name']=(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)?config('params.language_proficiency')[$value->detail->language_proficiency_id]:'';
                    if(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id==0)
                        $result['worker_list'][$i]['language_name']=(isset($value->detail->language_proficiency_other) && $value->detail->language_proficiency_other!=null)?$value->detail->language_proficiency_other:'';

                    $result['worker_list'][$i]['education_id']=(isset($value->detail->heighest_education_id) && $value->detail->heighest_education_id!=null)?(int)$value->detail->heighest_education_id:0;
                    $result['worker_list'][$i]['education_name']=(isset($value->detail->geteducation->name) && $value->detail->geteducation->name!=null)?$value->detail->geteducation->name:'';
                    if(isset($value->detail->heighest_education_id) && $value->detail->heighest_education_id==0)
                        $result['worker_list'][$i]['education_name']=(isset($value->detail->other_highest_education) && $value->detail->other_highest_education!=null)?$value->detail->other_highest_education:'';

                    $result['worker_list'][$i]['total_percentage']=0;
                    $getWokerjobrating=CommonFunction::getWokeralljobavgrating($value->id);
                    if(isset($getWokerjobrating) && $getWokerjobrating!=array())
                        $result['worker_list'][$i]['total_percentage']=(isset($getWokerjobrating['total']) && $getWokerjobrating['total']!=null)?$getWokerjobrating['total']:0;

                  $i++;
                }
            }
            if($i == $limit){
                unset($result['worker_list'][$i-1]);
                $result['is_last'] =0;
            }
            //end suburb data


            return $this->SuccessResponse($result);
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }

    //********************************************************************************
    //Title : Change job action
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************


    public function changeJobstatus(Request $request)
    {
      $request->validate([
        'job_id' => 'required',
        'worker_id' => 'required',
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
            $tmp_end_date=date('Y-m-d H:i:s',time());
            if(isset($end_date) && $end_date!=null)
            {
                $tmp_end_date=date('Y-m-d H:i:s',$end_date);
            }
            //check job is exist or not
            $job= Job::
             where('id',$job_id)
            ->where('is_deleted',0)
            ->where('is_active',1)
            ->where('selected_worker_id',$worker_id)
            ->where('user_id',$user_id)
            ->first();
            if(isset($job) && $job!=array())
            {

                $status='';
                $api_msg='success';
                if($action==1)//breaks contract
                {
                    //get worker name
                    $worker_data = User::selectRaw("id,concat(first_name,' ',second_name) as emp_name")
                    ->where(['is_deleted' => '0'])
                    ->first();
                    $worker_full_name='';
                    if(isset($worker_data) && $worker_data!=array())
                    {
                        $worker_full_name=(isset($worker_data->emp_name) && $worker_data->emp_name!='')?$worker_data->emp_name:'';
                    }

                    $status=5;
                    //$api_msg='success_break_contract_employee';
                    $api_msg= str_replace('{worker_name}',$worker_full_name,config('api_messages.success_break_contract_employee_change'));
                }
                else if($action==2) //complete
                {
                    $status=3;
                    $api_msg='success_complete_job_employee';
                    $job->end_date = $tmp_end_date;
                    if(isset($comment) && $comment!=null)
                    {
                        $job->comment=$comment;
                    }
                }
                else if($action==3) //terminate
                {
                    $status=4;
                    $api_msg='success_terminate_job_employee';
                }

                $job->status = $status;
                $job->u_date = date('Y-m-d H:i:s',time());
                $job->u_by = $user->id;
                $job->save();


                if($action==3 || $action==1)//terminate and break
                {
                    //check record is exist or not

                    $job_tracking=new Jobterminatehistory;
                    $job_tracking->worker_id = $worker_id;
                    $job_tracking->job_id = $job_id;
                    $job_tracking->job_status = $status;
                    $job_tracking->date = $tmp_end_date;
                    $job_tracking->i_date = date('Y-m-d H:i:s',time());
                    $job_tracking->i_by = $user->id;
                    $job_tracking->u_date = date('Y-m-d H:i:s',time());
                    $job_tracking->u_by = $user->id;
                    $job_tracking->save();
                    if($action==3 || $action==1)//terminate
                    {
                        if(isset($reason_id) && $reason_id!=null)
                        $job_tracking->terminate_id=$reason_id;

                        if(isset($comment) && $comment!=null)
                        $job_tracking->terminate_description=$comment;

                        $job_tracking->save();
                    }
                }




                if($action==3 || $action==2 || $action==1)//terminate or complete or break
                {
                    if(isset($rating_attitude) && $rating_attitude!=null || isset($rating_competency) && $rating_competency!=null ||
                       isset($rating_communication_skills) && $rating_communication_skills!=null || isset($rating_shows_initiative) && $rating_shows_initiative!=null ||
                       isset($rating_recommend) && $rating_recommend!=null  )
                    {
                        //add / update user rating to worker based on job
                        if(isset($rating_attitude) && $rating_attitude!=null)
                        {
                            $type='1';
                            $rating=$rating_attitude;
                            CommonFunction::setrating($user_id,$worker_id,$job_id,$type,$rating);
                        }

                        if(isset($rating_competency) && $rating_competency!=null)
                        {
                            $type='2';
                            $rating=$rating_competency;
                            CommonFunction::setrating($user_id,$worker_id,$job_id,$type,$rating);
                        }

                        if(isset($rating_communication_skills) && $rating_communication_skills!=null)
                        {
                            $type='3';
                            $rating=$rating_communication_skills;
                            CommonFunction::setrating($user_id,$worker_id,$job_id,$type,$rating);
                        }

                        if(isset($rating_shows_initiative) && $rating_shows_initiative!=null)
                        {
                            $type='4';
                            $rating=$rating_shows_initiative;
                            CommonFunction::setrating($user_id,$worker_id,$job_id,$type,$rating);
                        }

                        if(isset($rating_recommend) && $rating_recommend!=null)
                        {
                            $type='5';
                            $rating=$rating_recommend;
                            CommonFunction::setrating($user_id,$worker_id,$job_id,$type,$rating);
                        }

                    }
                    //start send push and email notification for complete & terminate to worker
                    $type=$type_id='';
                    if($action==2)//complete job
                        $type_id=3;//3:flag for complete job
                    elseif($action==3)//terminate job
                        $type_id=4;//3:flag for terminate job

                    //push noti
                    $type=2;//2:Push notification
                    $push_noti_flag=PushNotification::getUserNotificationsetting($worker_id,$type,$type_id);
                    if($push_noti_flag)
                    {
                        //send push notification to worker
                        PushNotification::sendJobactionPushNotificationToWorker($job,$action,$user,$worker_id); //action:2=complete,3=terminate
                    }
                    //end push noti

                    //email noti
                    $type=1;//1:email notification
                    $email_noti_flag=PushNotification::getUserNotificationsetting($worker_id,$type,$type_id);
                    if($email_noti_flag)
                    {
                        //send email notification to worker
                        EmailNotification::sendJobactionEmailNotificationToWorker($job,$action,$user); //action:2=complete,3=terminate
                    }
                    //end email noti

                    //end send push and email notification for complete & terminate to worker
                }


                return $this->SuccessResponse([],$api_msg);
            }
            else
                return $this->ErrorResponse('job_not_found',200);

      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }
    //********************************************************************************
    //Title : Post Job
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 19-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function postJob(Request $request)
    {
       $request->validate([
         'skill_id' => 'required',
         'contract_type' => 'required',
         'preferred_residency' => 'required',
         'experience' => 'required',
         'proposed_pay_from' => 'required',
         'proposed_pay_to' => 'required',
         'service_needed_type' => 'required',
         'address' => 'required',
         'latitude' => 'required',
         'longitude' => 'required',

       ]);

       if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        return $this->ErrorResponse('error_user_not_found',200);
        }

        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {

            if(isset($coupon_id) && $coupon_id!=null)
            {
                $coupon_data = Jobcoupon::
                selectRaw('id')
                ->where('is_deleted',0)
                ->where('id',$coupon_id)
                ->first();
                if(isset($coupon_data) && $coupon_data!=array())
                {

                }
                else
                {
                    return $this->ErrorResponse('coupon_not_found',200);
                }
            }
            $api_msg='success_post_job_employee';
            if(isset($job_id) && $job_id!=null)
            {
                $api_msg='success_update_job_employee';
                $job = job::
                selectRaw('*')
                ->where('is_deleted',0)
                ->where('id',$job_id)
                ->first();
                if(isset($job) && $job!=array())
                {

                }
                else
                {
                    return $this->ErrorResponse('job_not_found',200);
                }
            }
            else
            {
                $job = new Job;
                $job->i_date = date('Y-m-d H:i:s',time());
                $job->i_by = $user->id;
                $job->status=0;
            }

            $job->user_id = $user_id;
            $job->skill_id = $skill_id;
            $job->contract_type = $contract_type;
            $job->preferred_residency = $preferred_residency;
            $job->experience = $experience;
            $job->proposed_pay_from= $proposed_pay_from;
            $job->proposed_pay_to= $proposed_pay_to;
            $job->service_needed_type= $service_needed_type;
            $job->description= (isset($service_needed_description) && $service_needed_description!=null)?$service_needed_description:'';
            $job->address= (isset($address) && $address!=null)?$address:'';
            $job->latitude= (isset($latitude) && $latitude!=null)?$latitude:'';
            $job->longitude= (isset($longitude) && $longitude!=null)?$longitude:'';



            $job->u_by = $user->id;
            $job->u_date = date('Y-m-d H:i:s',time());

            if($job->save())
            {
                if(isset($job_id) && $job_id!=null)
                {}
                else
                {
                    $job->unique_id = $job->id;
                    $job->save();
                    if(isset($coupon_id) && $coupon_id!=null)
                    {
                        if(isset($coupon_data) && $coupon_data!=array())
                        {
                            $coupon_data->u_by = $user->id;
                            $coupon_data->u_date = date('Y-m-d H:i:s',time());
                            $coupon_data->status=4;
                            $coupon_data->save();
                        }
                    }
                }
                return $this->SuccessResponse([],$api_msg);
            }
            else
            {
              return $this->ErrorResponse('error_in_save',200);
            }
        }
        else
        {
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
    //Created Date : 20-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getJobdetail(Request $request)
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
            //->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
            //->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
             ->selectRaw("{$radius_query} AS distance,jobs.*,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id,skills.price as skill_price,skills.is_paid as skill_charge")
            //->selectRaw("{$radius_query} AS distance,jobs.*,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id,locations.name as town_name,l.name as suburb_name,users.profile_image,concat(users.first_name,' ',users.second_name) as emp_name")
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

                $result['job_id'] = $job->id;
                $result['skill_id'] = (isset($job->skill_id) && $job->skill_id!=null)?$job->skill_id:'';
                $result['skill_name'] = (isset($job->skill_name) && $job->skill_name!=null)?$job->skill_name:'';
                //$result['contract_type'] = (isset($job->contract_type) && $job->contract_type!=null)?config('params.contract_type')[$job->contract_type]:'';
                $result['contract_type'] = (isset($job->contract_type) && $job->contract_type!=null)?(int)$job->contract_type:0;
                $result['skill_price'] = (isset($job->skill_price) && $job->skill_price!=null)?(float)$job->skill_price:0;
                $result['is_chargeable'] = (isset($job->skill_charge) && $job->skill_charge!=null)?(int)$job->skill_charge:0;
                //$result['job_location']='';
                //if(isset($job->town_name) && $job->town_name!=null)
                //$result['job_location'] .= (isset($job->town_name) && $job->town_name!=null)?$job->town_name:'';
                //if(isset($job->suburb_name) && $job->suburb_name!=null)
                //{
                //    $result['job_location'] .=', ';
                //    $result['job_location'] .=(isset($job->suburb_name) && $job->suburb_name!=null)?$job->suburb_name:'';
                //}

                $result['posted_timestamp'] = (isset($job->i_date) && $job->i_date!=null)?strtotime($job->i_date):'';

                $result['is_expired']=0;
                if(time() >= strtotime($job->i_date)+(86400*7))
                $result['is_expired']=1;


                $result['experience'] = (isset($job->experience) && $job->experience!=null)?(int)$job->experience:0;

                $result['proposed_pay_from']='';
                if(isset($job->proposed_pay_from) && $job->proposed_pay_from!=null)
                    $result['proposed_pay_from'] = (isset($job->proposed_pay_from) && $job->proposed_pay_from!=null)?$job->proposed_pay_from:'';

                $result['proposed_pay_to'] ='';
                if(isset($job->proposed_pay_to) && $job->proposed_pay_to!=null)
                    $result['proposed_pay_to'] =(isset($job->proposed_pay_to) && $job->proposed_pay_to!=null)?$job->proposed_pay_to:'';

                $result['service_needed_type'] = (isset($job->service_needed_type) && $job->service_needed_type!=null)?(int)$job->service_needed_type:0;
                $result['preferred_residency'] = (isset($job->preferred_residency) && $job->preferred_residency!=null)?(int)$job->preferred_residency:0;
                $result['service_needed_description'] = (isset($job->description) && $job->description!=null)?$job->description:'';
                $result['address'] = (isset($job->address) && $job->address!=null)?$job->address:'';
                $result['latitude'] = (isset($job->latitude) && $job->latitude!=null)?(float)$job->latitude:0;
                $result['longitude'] = (isset($job->longitude) && $job->longitude!=null)?(float)$job->longitude:0;

                $result['is_interested_worker'] = 0;
                $job_tracking = Usershortlisted::where(['is_deleted'=>0,'job_id'=>$job_id,'type'=>1,'type_value'=>1])->first();
                if(isset($job_tracking) && $job_tracking!=array())
                {
                  $result['is_interested_worker'] = 1;
                }

                $result['is_finalshortlist_worker'] = 0;
                $job_tracking_list = Usershortlisted::where(['is_deleted'=>0,'user_id'=>$user_id,'job_id'=>$job_id,'type'=>2,'type_value'=>1])->first();
                if(isset($job_tracking_list) && $job_tracking_list!=array())
                {
                  $result['is_finalshortlist_worker'] = 1;
                }

                $result['job_status'] = (isset($job->status) && $job->status!=null)?(int)$job->status:'';

                $result['start_timestamp'] = (isset($job->worker_started_work_on) && $job->worker_started_work_on!=null)?strtotime($job->worker_started_work_on):0;
                $result['end_timestamp'] = (isset($job->end_date) && $job->end_date!=null)?strtotime($job->end_date):0;
                $result['terminated_reason']=$result['comment']='';

                //job is terminated
                if($job->status==4)
                {
                    $job_track= Jobterminatehistory::
                    leftjoin('terminate_reasons', 'terminate_reasons.id', '=', 'job_terminate_history.terminate_id')
                    ->selectRaw("job_terminate_history.*,terminate_reasons.name as terminate_reasons")
                    ->where(['job_terminate_history.is_deleted'=>0,'job_terminate_history.job_id'=>$job_id,'job_terminate_history.worker_id'=>$job->selected_worker_id,'job_terminate_history.job_status'=>4])
                    ->orderby('job_terminate_history.id','desc')
                    ->first();
                    //dd($job_track->terminate_description);
                    if(isset($job_track) && $job_track!=array())
                    {
                        $result['terminated_reason']=$job_track->terminate_reasons;
                        $result['comment']=(isset($job_track->terminate_description) && $job_track->terminate_description!=null)?$job_track->terminate_description:'';
                        $result['end_timestamp'] = (isset($job_track->i_date) && $job_track->i_date!=null)?strtotime($job_track->i_date):0;
                    }

                }
                elseif($job->status==3)
                {
                    $result['comment']=(isset($job->comment) && $job->comment!=null)?$job->comment:'';
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
    //Title : job History List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 20-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function jobHistorylist(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required',
          'type'=>'required',
          'list_type'=>'required',
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

                $andwhere1='';
                if(isset($list_type) && $list_type!=null )
                {
                    $tmp_status='3,4';
                    if($list_type==1)
                    {
                        $tmp_status='1,2';
                    }
                    $andwhere1='jobs.status IN ('.$tmp_status.')';
                }
                //end filter

                $result['is_last'] = 1;
                $result['job_history_list'] =array();
                $skill_data = Job::
                selectRaw("jobs.*,user_details.premium_worker_status,user_details.town_id,user_details.suburb_id,skills.name as skill_name,skills.is_paid as ispaid,user_details.town_id,user_details.suburb_id,locations.name as town_name,l.name as suburb_name,users.profile_image,concat(users.first_name,' ',users.second_name) as emp_name")
                ->leftjoin('user_details', 'user_details.user_id', '=', 'jobs.selected_worker_id')
                ->leftjoin('users', 'users.id', '=', 'jobs.selected_worker_id')
                ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
                ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
                ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
                //->select(['{$radius_query} AS distance','jobs.*','user_details.town_id','user_details.suburb_id','skills.name as skill_name','user_details.town_id','user_details.suburb_id','locations.name as town_name','l.name as suburb_name'])
                ->where('jobs.user_id',$user_id)
                ->whereBetween('jobs.status',[1,4])// By Khandhar
                // ->where('jobs.status', '<=', 4)//upto terminated
                ->where('jobs.is_deleted',0)
                ->where('jobs.is_active',1)
                ->where('skills.is_deleted',0)
                ->where('skills.is_active',1);

                if( $andwhere!='')
                $skill_data=$skill_data->whereRaw($andwhere);

                if( $andwhere1!='')
                $skill_data=$skill_data->whereRaw($andwhere1);

                $skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('jobs.id','desc')
                ->get();

                // dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $value)
                    {
                      // dd($value->premium_worker_status);
                      $result['job_history_list'][$i]['job_id'] = $value->id;
                      $result['job_history_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                      $result['job_history_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';
                      //$result['job_history_list'][$i]['contract_type'] = (isset($value->contract_type) && $value->contract_type!=null)?config('params.contract_type')[$value->contract_type]:'';

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
                      $result['job_history_list'][$i]['worker_id'] = (isset($value->selected_worker_id) && $value->selected_worker_id!=null)?(int)$value->selected_worker_id:0;
                      $result['job_history_list'][$i]['worker_profile_image'] = (isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                      $result['job_history_list'][$i]['worker_fullname'] = (isset($value->emp_name) && $value->emp_name!=null)?$value->emp_name:'';
                      $result['job_history_list'][$i]['job_status']=$value->status;
                      $result['job_history_list'][$i]['is_skill_chargeable']=$value->ispaid ?? 0;
                      $result['job_history_list'][$i]['is_worker_premium']=0;
                      if(isset($value->premium_worker_status) && $value->premium_worker_status!=null && $value->premium_worker_status==2)
                        $result['job_history_list'][$i]['is_worker_premium']=1;
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
    //Title : Posted Job List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 20-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function postedJoblist(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required',
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
                $result['posted_job_list'] =array();
                $skill_data = Job::
                selectRaw("jobs.*,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id")
                ->leftjoin('user_details', 'user_details.user_id', '=', 'jobs.selected_worker_id')
                ->leftjoin('users', 'users.id', '=', 'jobs.selected_worker_id')
                ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
                ->where('jobs.user_id',$user_id)
                ->where('jobs.is_deleted',0)
                ->where('jobs.status','<>',3)
                ->where('jobs.is_active',1)
                ->where('skills.is_deleted',0)
                ->where('skills.is_active',1);
                $skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('jobs.id','desc')
                ->get();

                //dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $value)
                    {
                        $result['posted_job_list'][$i]['job_id'] = $value->id;
                        $result['posted_job_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                        $result['posted_job_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';
                        $result['posted_job_list'][$i]['posted_timestamp'] = (isset($value->i_date) && $value->i_date!=null)?strtotime($value->i_date):'';
                        //$result['posted_job_list'][$i]['distance_in_km'] = (isset($value->distance) && $value->distance!=null)?$value->distance:'';

                        $result['posted_job_list'][$i]['job_status']=$value->status;
                        $result['posted_job_list'][$i]['experience'] = (isset($value->experience) && $value->experience!=null)?(int)$value->experience:0;

                        $result['posted_job_list'][$i]['proposed_pay_from']='';
                        if(isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)
                            $result['posted_job_list'][$i]['proposed_pay_from'] = (isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)?$value->proposed_pay_from:'';

                        $result['posted_job_list'][$i]['proposed_pay_to'] ='';
                        if(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)
                            $result['posted_job_list'][$i]['proposed_pay_to'] =(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)?$value->proposed_pay_to:'';

                        $result['posted_job_list'][$i]['is_expired']=0;
                        if(time() >= strtotime($value->i_date)+(86400*7))
                        $result['posted_job_list'][$i]['is_expired']=1;

                      $i++;
                    }

                    if($i == $limit){
                        unset($result['posted_job_list'][$i-1]);
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
    //Title : Posted job action(delete)
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 20-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function postedJobaction(Request $request)
    {
         $request->validate([
          'job_id'=>'required',
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

            $job = Job::
             selectRaw("jobs.*")
            ->where('jobs.is_deleted',0)
            ->where('jobs.is_active',1)
            ->where('jobs.id',$job_id)
            ->where('jobs.user_id',$user_id)
            ->first();
            if(isset($job) && $job!=array())
            {
                $api_msg='success';
                if($type==1)//delete
                {
                    $api_msg='success_job_deleted';
                    if($job->status==2)//running job
                    {
                        return $this->ErrorResponse('error_running_job_not_delete',200);
                    }
                    $job->is_deleted=1;
                }
                elseif($type==3)//extend reminder of one month
                {
                    $job->extend_date = date('Y-m-d H:i:s',strtotime("+1 month"));
                }
                elseif($type==4)//remove from fresh posted job list
                {
                    $job->in_fresh_job = 0;
                }
                $job->u_date = date('Y-m-d H:i:s',time());
                $job->u_by = $user->id;
                $job->save();
                return $this->SuccessResponse([],$api_msg);
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
    //Title : Contact History List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 20-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function contactHistorylist(Request $request)
    {
        // $request->validate([
        //  'start'=>'required',
        //  'limit'=>'required',
        //]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $user_id = $request->user['id'];
        $user = User::find($user_id);

        if(isset($user) && $user!=array())
        {
                //$start=(isset($start) && $start!='')?$start:0;
                //$limit=(isset($limit) && $limit!='')?$limit+1:11;


                //$result['is_last'] = 1;
                $result['contact_history_list'] =array();
                $skill_data = Employerpremiumhistory::
                 where('user_id',$user_id)
                ->where('is_deleted',0)
                ->where('is_active',1)
                //$skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('id','desc')
                ->get();

                //dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $value)
                    {
                        $result['contact_history_list'][$i]['id'] = $value->id;
                        $result['contact_history_list'][$i]['contact_count'] = (isset($value->contact) && $value->contact!=null)?(int)$value->contact:0;
                        $result['contact_history_list'][$i]['contact_subscribed_timestamp'] = (isset($value->start_date) && $value->start_date!=null)?strtotime($value->start_date):'';
                        $result['contact_history_list'][$i]['contact_expired_timestamp'] = (isset($value->end_date) && $value->end_date!=null)?strtotime($value->end_date):'';
                        $result['contact_history_list'][$i]['total_price'] = (isset($value->price) && $value->price!=null)?(float)round($value->price,2):0;

                      $i++;
                    }

                    //if($i == $limit){
                    //    unset($result['contact_history_list'][$i-1]);
                    //    $result['is_last'] =0;
                    //}
                }
                return $this->SuccessResponse($result);
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }
    //********************************************************************************
    //Title : Get job worker list
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 28-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getJobworkerlist(Request $request)
    {

        $request->validate([
          'start'=>'required',
          'limit'=>'required',
          'job_id'=>'required',
          'type'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();

      if(isset($user) && $user!=array())
      {
            $start=(isset($start) && $start!='')?$start:0;
            $limit=(isset($limit) && $limit!='')?$limit+1:11;



            //get location
            //$latitude = (isset($user->latitude) && $user->latitude!='')?$user->latitude:0;
            //$longitude = (isset($user->longitude) && $user->longitude!='')?$user->longitude:0;



            $result['is_last'] = 1;

            //start suburb data
            $result['worker_list'] =array();
            //$worker_data = Usershortlisted::
            //selectRaw("users.*,locations.name as town_name,l.name as suburb_name,user_shortlisted.type,user_shortlisted.job_id,j.proposed_pay_from,j.proposed_pay_to,j.service_needed_type,j.description")
            //->leftjoin('users', 'users.id', '=', 'user_shortlisted.worker_id')
            //->leftjoin('user_details', 'user_details.user_id', '=', 'users.id')
            //->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
            //->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
            //->leftjoin('jobs as j', 'j.id', '=', 'user_shortlisted.job_id')
            //->where('users.is_deleted',0)
            //->where('user_shortlisted.job_id',$job_id)
            //->where('user_shortlisted.is_deleted',0)
            //->where('user_shortlisted.is_active',1)
            //->where('user_shortlisted.type_value',1)
            //->where('users.actor',3)
            //->where('users.is_active',1);

            $worker_data = User::
            selectRaw("users.*,locations.name as town_name,l.name as suburb_name,user_shortlisted.id as s_id,user_shortlisted.type,user_shortlisted.job_id,j.proposed_pay_from,j.proposed_pay_to,j.service_needed_type,j.description,user_shortlisted.is_from_worker")
            ->leftjoin('user_shortlisted', 'user_shortlisted.worker_id', '=', 'users.id')
            ->leftjoin('user_details', 'user_details.user_id', '=', 'users.id')
            ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
            ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
            ->leftjoin('jobs as j', 'j.id', '=', 'user_shortlisted.job_id')
            ->where('users.is_deleted',0)
            ->where('user_shortlisted.job_id',$job_id)
            ->where('user_shortlisted.is_deleted',0)
            ->where('user_shortlisted.is_active',1)
            ->where('user_shortlisted.type_value',1)
            ->where('users.actor',3)
            ->where('users.is_active',1);

            if($type==1)//interested/shortlisted
            {
                $worker_data=$worker_data->where('user_shortlisted.type',1);
            }
            else if($type==2) //2= final shorlisted
            {
                $worker_data=$worker_data->where('user_shortlisted.type',2);
                $worker_data=$worker_data->where('user_shortlisted.user_id',$user_id);
            }
            $worker_data=$worker_data->whereRaw('(user_shortlisted.is_from_worker=0 OR (user_shortlisted.is_from_worker=1 and user_shortlisted.from_worker_status=1))');

            //$worker_data=$worker_data->whereRaw("{$radius_query} < ?", [$radius]);
            //->where('locations.is_deleted',0)
            //->where('locations.is_active',1)
            $worker_data=$worker_data->skip($start)->take($limit)
            ->orderby('user_shortlisted.id','desc')
            ->get();
            //dd($worker_data);
            if(isset($worker_data) && $worker_data!=array())
            {
                $i=0;
                foreach ($worker_data as $key => $value)
                {
                    $result['worker_list'][$i]['id'] = $value->s_id;
                    $result['worker_list'][$i]['worker_id'] = $value->id;
                    $result['worker_list'][$i]['worker_firstname']=(isset($value->first_name) && $value->first_name!=null)?$value->first_name:'';
                    $result['worker_list'][$i]['worker_secondname']=(isset($value->second_name) && $value->second_name!=null)?$value->second_name:'';
                    $result['worker_list'][$i]['profile_image']=(isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                    $result['worker_list'][$i]['skill_name']=(isset($value->detail->getskill->name) && $value->detail->getskill->name!=null)?$value->detail->getskill->name:'';

                    $result['worker_list'][$i]['proficiency_level_id']=(isset($value->detail->proficiency_level_id) && $value->detail->proficiency_level_id!=null)?(int)$value->detail->proficiency_level_id:0;
                    $result['worker_list'][$i]['is_worker_premium'] = (isset($value->is_employer_premium) && $value->is_employer_premium!=null)?(int)$value->is_employer_premium:0;
                    $result['worker_list'][$i]['preferred_residency']=(isset($value->detail->preferred_residency) && $value->detail->preferred_residency!=null)?(int)$value->detail->preferred_residency:0;

                    $result['worker_list'][$i]['town_suburb']='';
                    if(isset($value->town_name) && $value->town_name!=null)
                    $result['worker_list'][$i]['town_suburb'] .= (isset($value->town_name) && $value->town_name!=null)?$value->town_name:'';
                    if(isset($value->suburb_name) && $value->suburb_name!=null)
                    {
                        $result['worker_list'][$i]['town_suburb'] .=', ';
                        $result['worker_list'][$i]['town_suburb'] .=(isset($value->suburb_name) && $value->suburb_name!=null)?$value->suburb_name:'';
                    }

                    $result['worker_list'][$i]['skill_id']=(isset($value->detail->skill_id) && $value->detail->skill_id!=null)?(int)$value->detail->skill_id:0;
                    $result['worker_list'][$i]['experience']=(isset($value->detail->experience) && $value->detail->experience!=null)?(float)$value->detail->experience:0;
                    $result['worker_list'][$i]['contract_type']=(isset($value->detail->contract_type) && $value->detail->contract_type!=null)?(int)$value->detail->contract_type:0;
                    $result['worker_list'][$i]['part_time_desired_pay']=0;
                    $result['worker_list'][$i]['part_time_not_available']='';
                    $result['worker_list'][$i]['full_time_desired_pay']=0;
                    $result['worker_list'][$i]['full_time_not_available']='';
                    $usercontract= Usercontract::where(['is_deleted'=>0,'user_id'=>$value->id])->get();
                    if(isset($usercontract) && $usercontract!=array())
                    {
                        foreach($usercontract as $v)
                        {
                            if($v->contract_type==1)
                            {
                                $result['worker_list'][$i]['full_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['full_time_not_available']=$v->not_available_for_work_days;
                            }
                            else if($v->contract_type==2)
                            {
                                $result['worker_list'][$i]['part_time_desired_pay']=$v->desired_pay;
                                $result['worker_list'][$i]['part_time_not_available']=$v->not_available_for_work_days;
                            }
                        }
                    }

                    $result['worker_list'][$i]['distance_in_km']=(isset($value->distance) && $value->distance!=null)?$value->distance:'';

                    $result['worker_list'][$i]['language_id']=(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)?$value->detail->language_proficiency_id:'0';
                    $result['worker_list'][$i]['language_name']='';
                    if(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)
                    {
                        $langs=explode(',',$value->detail->language_proficiency_id);
                        if(isset($langs) && $langs!=array())
                        {
                            $l=0;
                            foreach($langs as $v)
                            {
                                $result['worker_list'][$i]['language_name'].=(isset(config('params.language_proficiency')[$v]) && config('params.language_proficiency')[$v]!=null)?config('params.language_proficiency')[$v]:'';
                                if(count($langs)-1>$l)
                                $result['worker_list'][$i]['language_name'].=',';
                                $l++;
                            }
                        }

                    }
                    // $result['worker_list'][$i]['language_name']=(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id!=null)?config('params.language_proficiency')[$value->detail->language_proficiency_id]:'';
                    if(isset($value->detail->language_proficiency_id) && $value->detail->language_proficiency_id==0)
                        $result['worker_list'][$i]['language_name']=(isset($value->detail->language_proficiency_other) && $value->detail->language_proficiency_other!=null)?$value->detail->language_proficiency_other:'';

                    $result['worker_list'][$i]['education_id']=(isset($value->detail->heighest_education_id) && $value->detail->heighest_education_id!=null)?(int)$value->detail->heighest_education_id:0;
                    $result['worker_list'][$i]['education_name']=(isset($value->detail->geteducation->name) && $value->detail->geteducation->name!=null)?$value->detail->geteducation->name:'';
                    if(isset($value->detail->heighest_education_id) && $value->detail->heighest_education_id==0)
                        $result['worker_list'][$i]['education_name']=(isset($value->detail->other_highest_education) && $value->detail->other_highest_education!=null)?$value->detail->other_highest_education:'';

                    $result['worker_list'][$i]['total_percentage']=0;
                    $getWokerjobrating=CommonFunction::getWokeralljobavgrating($value->id);
                    if(isset($getWokerjobrating) && $getWokerjobrating!=array())
                    {
                        $result['worker_list'][$i]['total_percentage']=(isset($getWokerjobrating['total']) && $getWokerjobrating['total']!=null)?$getWokerjobrating['total']:0;
                    }
                    $result['worker_list'][$i]['worker_status']=0;
                    if($value->type==1) //interets or shortlisted
                    {
                        $result['worker_list'][$i]['worker_status']=2;
                        if($value->is_from_worker==1)
                        $result['worker_list'][$i]['worker_status']=1;
                        if($value->job_id!=0)
                        {
                            //get job detail
                            //$result['worker_list'][$i]['worker_status']=1;
                            $result['worker_list'][$i]['job_id']=$value->job_id;
                            $result['worker_list'][$i]['proposed_pay_from']=(isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)?(int)$value->proposed_pay_from:0;
                            $result['worker_list'][$i]['proposed_pay_to']=(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)?(int)$value->proposed_pay_to:0;
                            $result['worker_list'][$i]['service_needed_type']=(isset($value->service_needed_type) && $value->service_needed_type!=null)?(int)$value->service_needed_type:0;
                            $result['worker_list'][$i]['service_needed_description']=(isset($value->description) && $value->description!=null)?$value->description:'';

                        }

                    }
                    $result['worker_list'][$i]['job_id']=(isset($value->job_id) && $value->job_id!=null)?(int)$value->job_id:0;
                    $result['worker_list'][$i]['dob']='';
                    $result['worker_list'][$i]['age']=0;
                    if(isset($value->detail->dob) && $value->detail->dob!=null)
                    {
                        $result['worker_list'][$i]['dob']= date(config('params.new_date_format'),strtotime($value->detail->dob));
                        $result['worker_list'][$i]['age']=commonFunction::calculateAgefromdob($value->detail->dob);
                    }

                  $i++;
                }
            }
            if($i == $limit){
                unset($result['worker_list'][$i-1]);
                $result['is_last'] =0;
            }
            //end suburb data


            return $this->SuccessResponse($result);
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }
    //********************************************************************************
    //Title : Coupon  List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function couponList(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required',
          //'type'=>'required',
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
                $result['coupon_list'] =array();
                $skill_data = Jobcoupon::
                selectRaw("job_coupons.*,skills.name as skill_name")
                ->leftjoin('skills', 'skills.id', '=', 'job_coupons.skill_id')
                ->where('job_coupons.user_id',$user_id)
                ->where('job_coupons.status','<=',3)
                ->where('job_coupons.is_deleted',0)
                ->where('job_coupons.is_active',1);
                $skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('job_coupons.id','desc')
                ->get();

                //dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $value)
                    {
                      $result['coupon_list'][$i]['id'] = $value->id;
                      $result['coupon_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                      $result['coupon_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';
                      $result['coupon_list'][$i]['skill_amount'] = (isset($value->price) && $value->price!=null)?(float)$value->price:0;
                      $result['coupon_list'][$i]['coupon_status'] = (isset($value->status) && $value->status!=null)?(int)$value->status:0;
                      $result['coupon_list'][$i]['coupon_expired_timestamp'] = (isset($value->coupon_validity) && $value->coupon_validity!=null)?strtotime($value->coupon_validity):0;
                      $result['coupon_list'][$i]['coupon_insertion_timestamp'] = (isset($value->i_date) && $value->i_date!=null)?strtotime($value->i_date):0;

                      $i++;
                    }

                    if($i == $limit){
                        unset($result['coupon_list'][$i-1]);
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
    //Title : Remove searched history
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 18-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function removeSearchHistory(Request $request)
    {

        $request->validate([
          'type'=>'required',
          'type_id'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();

      if(isset($user) && $user!=array())
      {
          $suburb_data = Searchhistory::
          selectRaw('search_histories.*')
          ->where('search_histories.is_deleted',0)
          ->where('search_histories.is_active',1)
          ->where('search_histories.user_id',$user_id)
          ->where('search_histories.type',$type)
          ->where('search_histories.id',$type_id)
          ->first();
          //dd($suburb_data);
          if(isset($suburb_data) && $suburb_data!=array())
          {
              $suburb_data->u_date = date('Y-m-d H:i:s',time());
              $suburb_data->u_by = $user->id;
              $suburb_data->is_deleted=1;
              $suburb_data->save();
              return $this->SuccessResponse([],'success');
          }
          else
          {
              return $this->ErrorResponse('error_data_not_found',200);
          }

      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }

    //********************************************************************************
    //Title : Claim Coupon
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 20-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function claimCoupon(Request $request)
    {

        $request->validate([
          'job_id'=>'required',
          'reason_id'=>'required',
        ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();

      if(isset($user) && $user!=array())
      {
            //get jobdetail
            $job= Job::
             where('id',$job_id)
            ->where('is_deleted',0)
            ->where('is_active',1)
            ->where('user_id',$user_id)
            ->first();
            if(isset($job) && $job!=array())
            {
            }
            else
            {
                return $this->ErrorResponse('job_not_found',200);
            }
            //get skill data
            $skill_data = Skill::where('is_deleted',0)
            ->where('id',$job->skill_id)
            ->first();
            $price=0;
            $is_take_charge=Setting::where('is_deleted',0)->where('key', '=', 'skill_charge')->first();
            if(isset($skill_data) && $skill_data!=array())
            {
                if($skill_data->is_paid==1)
                {
                    $price=$skill_data->price;
                }
                //if(isset($is_take_charge) && $is_take_charge!=array())
                //{
                //      if($is_take_charge->value==0)
                //      {
                //        $price=0;
                //      }
                //}
            }
            else
            {
                return $this->ErrorResponse('skill_not_found',200);
            }
            //start suburb data
            $coupon_data = Jobcoupon::
            selectRaw('id')
            ->where('is_deleted',0)
            ->where('is_active',1)
            ->where('user_id',$user_id)
            ->where('job_id',$job_id)
            ->first();
            //dd($coupon_data);
            if(isset($coupon_data) && $coupon_data!=array())
            {
                return $this->ErrorResponse('error_already_coupon_requested',200);
            }
            else
            {
                //add coupon request
                $job_coupon=new Jobcoupon();
                $job_coupon->user_id=$user->id;
                $job_coupon->worker_id=$job->selected_worker_id;
                $job_coupon->job_id=$job_id;
                $job_coupon->claim_coupon_reason_id=$reason_id;
                $job_coupon->skill_id=$job->skill_id;
                $job_coupon->price=$price;
                if(isset($comment) && $comment!=null)
                    $job_coupon->description=$comment;

                $job_coupon->status=1;
                $job_coupon->i_date = date('Y-m-d H:i:s',time());
                $job_coupon->i_by = $user->id;
                $job_coupon->u_date = date('Y-m-d H:i:s',time());
                $job_coupon->u_by = $user->id;
                $job_coupon->save();

                $job->coupon_applied=1;
                $job->save();
                return $this->SuccessResponse([],'success');

            }

      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }

    //********************************************************************************
    //Title : Purchase Contact
    //Developer:Khandhar.
    //Email: Khandhar@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Khandhar.
    //Created Date : 26-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function purchaseContact(Request $request)
    {
      $request->validate([
        'contact_id'=>'required',
      ]);
      if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        return $this->ErrorResponse('error_user_not_found',200);
      }

      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();
      if(isset($user) && $user!=array())
      {
        $contactpackage = Contactpackage::where(['is_deleted'=>0,'id'=>$contact_id])->first();
        if(isset($contactpackage) && $contactpackage != array())
        {
          $employerpremium = Employerpremium::where(['user_id'=>$user_id])->first();

          $end_date = Date('Y-m-d 00:00:00', strtotime("+5 days"));
          if($contactpackage->validity_factor == 1){ //days
            $end_date = Date('Y-m-d 00:00:00', strtotime("+".$contactpackage->validity_value." days"));
            $tmp_day=$contactpackage->validity_value;
          }
          if($contactpackage->validity_factor == 2){ //hours
            $h = $contactpackage->validity_value/24;
            // dd((int)$h);
            if((int)$h > 0){
              $end_date = Date('Y-m-d 00:00:00', strtotime("+".(int)$h." days"));
              $tmp_day=(int)round($h);
            }else{
              $end_date = Date('Y-m-d 00:00:00', strtotime("+1 days"));
              $tmp_day=(int)1;
            }
          }
          $contact_total = $contactpackage->contact;

          if($employerpremium != array())
          {
            $employerpremium = $employerpremium;
            $contact_total = $employerpremium->contact + $contactpackage->contact;
            $end_date = date('Y-m-d',strtotime($employerpremium->end_date) + ($tmp_day*24*60*60));
            // $end_date = date('Y-m-d', strtotime($employerpremium->end_date. '+'.$contactpackage->validity_value.' days'));
          }else {
            $employerpremium = New Employerpremium;
            $employerpremium->start_date = date('Y-m-d 00:00:00',time());
          }
          $employerpremium->user_id = $user_id;
          $employerpremium->contact_package_id = $contact_id;
          $employerpremium->contact = $contact_total;
          $employerpremium->end_date = $end_date;
          $employerpremium->i_by = $user_id;
          $employerpremium->i_date = date('Y-m-d H:i:s',time());
          $employerpremium->u_by = $user_id;
          $employerpremium->u_date = date('Y-m-d H:i:s',time());
          if($employerpremium->save())
          {
            $epremiumhistory = New Employerpremiumhistory;
            $epremiumhistory->user_id = $user_id;
            $epremiumhistory->contact_package_id = $contact_id;
            $epremiumhistory->transaction_id = $transaction_id ?? 0;
            $epremiumhistory->validity_factor = $contactpackage->validity_factor;
            $epremiumhistory->validity_value = $contactpackage->validity_value;
            $epremiumhistory->price = $contactpackage->price;
            $epremiumhistory->contact = $contactpackage->contact;
            //$epremiumhistory->start_date = date('Y-m-d 00:00:00',time());
            $epremiumhistory->start_date =$employerpremium->start_date;
            $epremiumhistory->end_date = $end_date;
            $epremiumhistory->i_by = $user_id;
            $epremiumhistory->i_date = date('Y-m-d H:i:s',time());
            $epremiumhistory->u_by = $user_id;
            $epremiumhistory->u_date = date('Y-m-d H:i:s',time());
            $epremiumhistory->save();

            //insert employer premium activity
            CommonFunction::insertEmployerpremiumactivity($epremiumhistory->id);
          }
          $result['contact_count'] =CommonFunction::getEmployercontactcount($user_id);
          return $this->SuccessResponse($result);
        }else {
          return $this->ErrorResponse('error_package_not_found',200);
        }

      }else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }

    //********************************************************************************
    //Title : Purchase Contact
    //Developer:Khandhar.
    //Email: Khandhar@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Khandhar.
    //Created Date : 26-03-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function changeWorkerSelectionStatus(Request $request)
    {
      $request->validate([
        //'worker_id'=>'required',
        'selection_type'=>'required',
      ]);

      if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        return $this->ErrorResponse('error_user_not_found',200);
      }
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();
      if(isset($user) && $user!=array())
      {
        if(isset($selection_type) && $selection_type != "")
        {
          if($selection_type == 3) //final shortlist
          {
            $ids = explode(",",$id);
            $getEmployercontactcount=CommonFunction::getEmployercontactcount($user_id);
            if($getEmployercontactcount<count($ids))
            {
                return $this->ErrorResponse('error_less_employer_contact',200);
            }

            foreach($ids as $key => $id)
            {
                $sc = Usershortlisted::where(['is_deleted'=>0,'id'=>$id,'type'=>1,'type_value'=>1])->first();
                if(isset($sc) && $sc!=array())
                {
                    $job = Job::where(['is_deleted'=>0,'id'=>$sc->job_id])->first();
                    //$sc->worker_id = $id;
                    //$sc->user_id = $user_id;;
                    //$sc->job_id = $job_id ?? 0;
                    $sc->type = 2;
                    $sc->type_value = 1;

                    $type_id=$action=5;//shortlisted
                    if($sc->is_from_worker==1)
                    {
                        $type_id=1;//accepted
                        $action=3;
                    }


                    $sc->u_by = $user_id;
                    $sc->u_date = date('Y-m-d H:i:s',time());
                    if($sc->save())
                    {
                      $push_noti_flag=PushNotification::getUserNotificationsetting($id,2,$type_id);
                      if($push_noti_flag)
                      {
                        PushNotification::sendUsershortlistedPushNotificationToWorker($job,$action,$user,$sc->worker_id); //action:1=apply,2=cancel
                      }
                      $push_noti_flag=PushNotification::getUserNotificationsetting($id,1,$type_id);
                      if($push_noti_flag)
                      {
                        EmailNotification::sendUsershortlistedEmailNotificationToWorker($job,$action,$user,$sc->worker_id); //action:1=apply,2=cancel
                      }
                    }
                    //decrease empoyer's conact
                    CommonFunction::decreaseEmployercontactcount($user_id);
                }
            }
          }
          if( $selection_type == 2) //uninterested
          {
            //$ids = explode(",",$worker_id);
            $ids = explode(",",$id);

            // dd($job);
            foreach($ids as $key => $id)
            {
                $sc = Usershortlisted::where(['is_deleted'=>0,'id'=>$id])->first();
                if(isset($sc) && $sc!=array())
                {
                      $job = Job::where(['is_deleted'=>0,'id'=>$sc->job_id])->first();
                      $sc->type_value = 0;
                      //$sc->job_id = $job_id ?? 0;
                      //$sc->is_from_worker = 0;
                      $sc->u_by = $user_id;
                      $sc->u_date = date('Y-m-d H:i:s',time());
                      if($sc->save())
                      {
                        if($sc->is_from_worker==1) // if worker has interested
                        {
                            $push_noti_flag=PushNotification::getUserNotificationsetting($sc->worker_id,2,2);
                            if($push_noti_flag)
                            {
                              PushNotification::sendUsershortlistedPushNotificationToWorker($job,2,$user,$sc->worker_id); //action:1=apply,2=cancel
                            }
                            $push_noti_flag=PushNotification::getUserNotificationsetting($sc->worker_id,1,2);
                            if($push_noti_flag)
                            {
                              EmailNotification::sendUsershortlistedEmailNotificationToWorker($job,2,$user,$sc->worker_id); //action:1=apply,2=cancel
                            }
                        }
                      }
                }
            }
          }
          elseif($selection_type == 1 ) //interested
          {
                //start check there is same skill interested or not
                $previous_shortlist_data= Usershortlisted::selectRaw('id,(select skill_id from user_details where is_deleted="N" and user_id=user_shortlisted.worker_id) as skill_id')
                ->where(['is_deleted'=>0,'user_id'=>$user_id,'type'=>1,'type_value'=>1])
                ->whereRaw('(is_from_worker=0 OR (is_from_worker=1 and from_worker_status=1))')
                ->first();

                if(isset($previous_shortlist_data) && $previous_shortlist_data!=array())
                {
                    if($previous_shortlist_data->skill_id!=$skill_id)
                    {
                        return $this->ErrorResponse('error_worker_skill_not_match',200);
                    }
                }
                //end check there is same skill interested or not

                //start check shortlisted count
                $previous_shortlist_count= Usershortlisted::select('id')
                ->where(['is_deleted'=>0,'user_id'=>$user_id,'type_value'=>1])
                ->whereRaw('(is_from_worker=0 OR (is_from_worker=1 and from_worker_status=1))')
                ->count();
                //dd($previous_shortlist_count);
                $is_shortlist_first_worker=1;
                if(isset($previous_shortlist_count) && $previous_shortlist_count!=null)
                {
                    //dd($previous_shortlist_count->count());
                    $is_shortlist_first_worker=0;
                    //$dataCount = $previous_shortlist_count->count();
                    $dataCount=$previous_shortlist_count;
                    if($dataCount>=10)
                    {
                        return $this->ErrorResponse('error_employer_shortlist_count',200);
                    }
                }


                //end check shortlisted count

                //check alreay there or not
              if($job_id!=0)
              {
                $already_data = Usershortlisted::where(['is_deleted'=>0,'job_id'=>$job_id,'worker_id'=>$worker_id,'type'=>1,'type_value'=>1])->first();
                if(isset($already_data) && $already_data!=array())
                {
                    return $this->ErrorResponse('worker_already_interested_employerside',200);
                }
              }
              $job_id = $job_id ?? 0;
              $job = Job::where(['is_deleted'=>0,'id'=>$job_id])->first();

              $sc = Usershortlisted::where(['is_deleted'=>0,'user_id'=>$user_id,'worker_id'=>$worker_id,'type'=>1,'is_from_worker'=>0]);
              //
              if($job_id!=0)
              {
                $sc->where(['job_id'=>$job_id]);
              }
              $sc=$sc->first();


              if(isset($sc) && $sc != array()){
              }
              else{

                $sc = new Usershortlisted;
              }

              $sc->worker_id = $worker_id;
              $sc->user_id = $user_id;;
              $sc->job_id = $job_id ?? 0;
              $sc->employer_contract_type = $employer_contract_type ?? 0;
              $sc->type = 1;
              $sc->type_value = 1;
              $sc->is_from_worker = 0;
              $sc->i_by = $user_id;
              $sc->i_date = date('Y-m-d H:i:s',time());

              if($sc->save())
              {
                //$push_noti_flag=PushNotification::getUserNotificationsetting($worker_id,2,1);
                //if($push_noti_flag)
                //{
                //  PushNotification::sendUseracceptedPushNotificationToWorker($job,3,$user,$worker_id); //action:1=apply,2=cancel
                //}
                //$push_noti_flag=PushNotification::getUserNotificationsetting($worker_id,1,1);
                //if($push_noti_flag)
                //{
                //  EmailNotification::sendUsershortlistedEmailNotificationToWorker($job,3,$user,$worker_id); //action:1=apply,2=cancel
                //}
                $result['is_shortlist_first_worker']=$is_shortlist_first_worker;
                return $this->SuccessResponse($result);
              }

          }elseif($selection_type == 4) //hire
          {


            if($job_id == 0){
              return $this->ErrorResponse('job_already_assigned',200);
            }

            $sc = $job = array();
            //$sc = Usershortlisted::where(['is_deleted'=>0,'id'=>$id,'type'=>2,'type_value'=>1])->first();
            $sc = Usershortlisted::where(['is_deleted'=>0,'id'=>$id,'type'=>1,'type_value'=>1])->first();
            if(isset($sc) && $sc != array())
            {}
            else
            {
              return $this->ErrorResponse('error_user_not_sortlisted',200);
            }

            $job = Job::where(['is_deleted'=>0,'id'=>$job_id])->where(['selected_worker_id'=>null])->first();
            // dd($job);
            if($job == array()){
              return $this->ErrorResponse('job_already_assigned',200);
            }

            $worker_id=$sc->worker_id;
            $jb = Job::where(['is_deleted'=>0,'id'=>$job_id])->first();
            $jb->selected_worker_id = $worker_id;
            $jb->worker_started_work_on = (isset($hire_date) && $hire_date != "")?date('Y-m-d H:i:s',$hire_date):date('Y-m-d 00:00:00',time());
            $hire_date = (isset($hire_date) && $hire_date != "")?date('Y-m-d H:i:s',$hire_date):date('Y-m-d 00:00:00',time());
            $startdate = date('Y-m-d 00:00:00',time());
            $enddate = date('Y-m-d 23:59:59',time());
            $jb->status = 1;
            if($startdate <= $hire_date && $hire_date <= $enddate)
              $jb->status = 2;
            if($jb->save())
            {
                // added by shahnavaz
                $job_tracking=new Jobterminatehistory;
                $job_tracking->worker_id = $worker_id;
                $job_tracking->job_id = $job_id;
                $job_tracking->job_status = 1;
                $job_tracking->date = date('Y-m-d',time());
                $job_tracking->i_date = date('Y-m-d H:i:s',time());
                $job_tracking->i_by = $user_id;
                $job_tracking->u_date = date('Y-m-d H:i:s',time());
                $job_tracking->u_by = $user_id;
                $job_tracking->save();
                // added by shahnavaz

              //$sc_remove = Usershortlisted::where(['id'=>$id])->first();
              //$sc_remove->is_deleted = 1;
              //$sc_remove->save();

              //start remove shortlist data of this user
              $previous_shortlist_count= Usershortlisted::select('id')
              ->where(['is_deleted'=>0,'user_id'=>$user_id,'type'=>1,'type_value'=>1])
              ->whereRaw('(is_from_worker=0 OR (is_from_worker=1 and from_worker_status=1))')
              ->get();
              if(isset($previous_shortlist_count) && $previous_shortlist_count!=array())
              {
                    foreach($previous_shortlist_count as $sc_remove)
                    {
                        $sc_remove->is_deleted = 1;
                        $sc_remove->save();
                    }
              }
              //end remove shortlist data of this user

              $hire_date=date(config('params.new_date_format'),strtotime($hire_date));

              //$push_noti_flag=PushNotification::getUserNotificationsetting($worker_id,2,5);
              //if($push_noti_flag)
              //
                PushNotification::sendUsershortlistedPushNotificationToWorker($job,4,$user,$worker_id,$hire_date);
              //}
              //$push_noti_flag=PushNotification::getUserNotificationsetting($worker_id,1,5);
              //if($push_noti_flag)
              //{
                EmailNotification::sendUsershortlistedEmailNotificationToWorker($job,4,$user,$worker_id,$hire_date);
              //}

              //start When employer has final shortlisted but another employer hired worker
              //$other_users= Usershortlisted::where(['is_deleted'=>0,'worker_id'=>$worker_id,'type'=>2,'type_value'=>1])->get()->toArray();
              $other_users= Usershortlisted::where(['is_deleted'=>0,'worker_id'=>$worker_id,'type'=>1,'type_value'=>1])->get()->toArray();
              if(isset($other_users) && $other_users!=array())
              {
                    foreach($other_users as $other)
                    {
                        PushNotification::sendotherUsershortlistedPushNotificationToemployeer($job,6,$user,$other['user_id'],$hire_date,$worker_id);
                    }
              }
              //end When employer has final shortlisted but another employer hired worker
            }

          }
          elseif($selection_type == 5) //accept worker
          {
                //start check there is same skill interested or not
                $previous_shortlist_data= Usershortlisted::selectRaw('id,(select skill_id from user_details where is_deleted="N" and user_id=user_shortlisted.worker_id) as skill_id')
                ->where(['is_deleted'=>0,'user_id'=>$user_id,'type'=>1,'type_value'=>1])
                ->whereRaw('(is_from_worker=0 OR (is_from_worker=1 and from_worker_status=1))')
                ->first();
                if(isset($previous_shortlist_data) && $previous_shortlist_data!=array())
                {
                    if($previous_shortlist_data->skill_id!=$skill_id)
                    {
                        return $this->ErrorResponse('error_worker_skill_not_match',200);
                    }
                }
                //end check there is same skill interested or not

                //start check shortlisted count
                $previous_shortlist_count= Usershortlisted::select('id')
                ->where(['is_deleted'=>0,'user_id'=>$user_id,'type'=>1,'type_value'=>1])
                ->whereRaw('(is_from_worker=0 OR (is_from_worker=1 and from_worker_status=1))')
                ->get();
                $is_shortlist_first_worker=1;
                if(isset($previous_shortlist_count) && $previous_shortlist_count!=array())
                {
                    $is_shortlist_first_worker=0;
                    $dataCount = $previous_shortlist_count->count();
                    if($dataCount>=10)
                    {
                        return $this->ErrorResponse('error_employer_shortlist_count',200);
                    }
                }

                //end check shortlisted count

                $sc = $job = array();
                //$sc = Usershortlisted::where(['is_deleted'=>0,'id'=>$id,'type'=>2,'type_value'=>1])->first();
                $sc = Usershortlisted::where(['is_deleted'=>0,'id'=>$id])->first();
                if(isset($sc) && $sc != array())
                {
                    $sc->from_worker_status=1;
                    $sc->u_by = $user_id;
                    $sc->u_date = date('Y-m-d H:i:s',time());
                    $sc->save();
                }
          }
          return $this->SuccessResponse([],'success');
        }
      }else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }


    //********************************************************************************
    //Title : month Running job list
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 15-05-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function monthRunningjoblist(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required',
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


                $tmp_date=date("Y-m-d H:i:s",time());
                $result['is_last'] = 1;
                $result['one_month_running_job_list'] =array();
                $skill_data = Job::
                selectRaw("jobs.*,TIMESTAMPDIFF(MONTH ,jobs.worker_started_work_on, NOW()) as one_month,user_details.premium_worker_status,user_details.town_id,user_details.suburb_id,skills.name as skill_name,skills.is_paid as ispaid,user_details.town_id,user_details.suburb_id,users.profile_image,concat(users.first_name,' ',users.second_name) as emp_name")
                ->leftjoin('user_details', 'user_details.user_id', '=', 'jobs.selected_worker_id')
                ->leftjoin('users', 'users.id', '=', 'jobs.selected_worker_id')
                ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
                //->select(['{$radius_query} AS distance','jobs.*','user_details.town_id','user_details.suburb_id','skills.name as skill_name','user_details.town_id','user_details.suburb_id','locations.name as town_name','l.name as suburb_name'])
                ->where('jobs.user_id',$user_id)
                ->where('jobs.status',2)//running
                ->where('jobs.contract_type',1)//full time
                ->where('jobs.is_deleted',0)
                ->where('jobs.is_active',1)
                ->havingRaw("one_month >= 1")
                ->whereRaw("case when jobs.extend_date IS NOT NULL then jobs.extend_date <= '".$tmp_date."' else 1 end")
                ->where('skills.is_deleted',0)
                ->where('skills.is_active',1);


                $skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('jobs.id','desc')
                ->get();

                // dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $value)
                    {
                      // dd($value->premium_worker_status);
                      $result['one_month_running_job_list'][$i]['job_id'] = $value->id;
                      $result['one_month_running_job_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                      $result['one_month_running_job_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';
                      $result['one_month_running_job_list'][$i]['contract_type'] = (isset($value->contract_type) && $value->contract_type!=null)?config('params.contract_type')[$value->contract_type]:'';
                      $result['one_month_running_job_list'][$i]['worker_id'] = (isset($value->selected_worker_id) && $value->selected_worker_id!=null)?(int)$value->selected_worker_id:0;
                      $result['one_month_running_job_list'][$i]['worker_profile_image'] = (isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                      $result['one_month_running_job_list'][$i]['worker_fullname'] = (isset($value->emp_name) && $value->emp_name!=null)?$value->emp_name:'';
                      $result['one_month_running_job_list'][$i]['job_status']=$value->status;
                      //$result['one_month_running_job_list'][$i]['is_skill_chargeable']=$value->ispaid ?? 0;
                      $result['one_month_running_job_list'][$i]['is_worker_premium']=0;
                      if(isset($value->premium_worker_status) && $value->premium_worker_status!=null && $value->premium_worker_status==2)
                      {
                        $result['one_month_running_job_list'][$i]['is_worker_premium']=1;
                      }

                      $result['one_month_running_job_list'][$i]['join_date']=strtotime($value->worker_started_work_on);

                      $i++;
                    }

                    if($i == $limit){
                        unset($result['one_month_running_job_list'][$i-1]);
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
    //Title : Posted Job List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 20-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function freshPostedjoblist(Request $request)
    {
         $request->validate([
          'start'=>'required',
          'limit'=>'required',
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

                //gte job of this user which has interested and shortlisted
                $shortlist_job= Usershortlisted::selectRaw('job_id')
                ->where(['is_deleted'=>0,'user_id'=>$user_id,'type_value'=>1])
                ->whereRaw('job_id !=""')
                ->groupby('job_id')
                ->get();

                $not_job_arr=[];
                if(isset($shortlist_job) && $shortlist_job!=array())
                {
                    foreach($shortlist_job as $v)
                    {
                        $not_job_arr[]=$v->job_id;
                    }
                }
                //dd($not_job_arr);

                $display_job_interval_worker=7;
                $result['is_last'] = 1;
                $result['posted_job_list'] =array();
                $skill_data = Job::
                selectRaw("jobs.*,DATE_ADD(jobs.i_date, INTERVAL {$display_job_interval_worker} DAY) AS nextweek,user_details.town_id,user_details.suburb_id,skills.name as skill_name,user_details.town_id,user_details.suburb_id")
                ->leftjoin('user_details', 'user_details.user_id', '=', 'jobs.selected_worker_id')
                ->leftjoin('users', 'users.id', '=', 'jobs.selected_worker_id')
                ->leftjoin('skills', 'skills.id', '=', 'jobs.skill_id')
                ->where('jobs.user_id',$user_id)
                ->where('jobs.in_fresh_job',1)
                ->where('jobs.is_deleted',0)
                ->where('jobs.status',0)
                ->havingRaw('nextweek <= now()');
                if(isset($not_job_arr) && $not_job_arr!=array())
                {
                    $skill_data=$skill_data->whereNotIn('jobs.id',$not_job_arr);
                }
                $skill_data=$skill_data->where('jobs.is_active',1)
                ->where('skills.is_deleted',0)
                ->where('skills.is_active',1);
                $skill_data=$skill_data->skip($start)->take($limit)
                ->orderby('jobs.id','desc')
                ->get();

                //dd($skill_data);
                if(isset($skill_data) && $skill_data!=array())
                {
                    $i=0;
                    foreach ($skill_data as $value)
                    {
                        $result['posted_job_list'][$i]['job_id'] = $value->id;
                        $result['posted_job_list'][$i]['skill_id'] = (isset($value->skill_id) && $value->skill_id!=null)?$value->skill_id:'';
                        $result['posted_job_list'][$i]['skill_name'] = (isset($value->skill_name) && $value->skill_name!=null)?$value->skill_name:'';
                        $result['posted_job_list'][$i]['posted_timestamp'] = (isset($value->i_date) && $value->i_date!=null)?strtotime($value->i_date):'';
                        //$result['posted_job_list'][$i]['distance_in_km'] = (isset($value->distance) && $value->distance!=null)?$value->distance:'';

                        $result['posted_job_list'][$i]['job_status']=$value->status;
                        $result['posted_job_list'][$i]['experience'] = (isset($value->experience) && $value->experience!=null)?(int)$value->experience:0;

                        $result['posted_job_list'][$i]['proposed_pay_from']='';
                        if(isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)
                            $result['posted_job_list'][$i]['proposed_pay_from'] = (isset($value->proposed_pay_from) && $value->proposed_pay_from!=null)?$value->proposed_pay_from:'';

                        $result['posted_job_list'][$i]['proposed_pay_to'] ='';
                        if(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)
                            $result['posted_job_list'][$i]['proposed_pay_to'] =(isset($value->proposed_pay_to) && $value->proposed_pay_to!=null)?$value->proposed_pay_to:'';

                        $result['posted_job_list'][$i]['is_expired']=0;
                        if(time() >= strtotime($value->i_date)+(86400*7))
                        $result['posted_job_list'][$i]['is_expired']=1;

                      $i++;
                    }

                    if($i == $limit){
                        unset($result['posted_job_list'][$i-1]);
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
    //Title : clear short List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 23-05-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function clearshortList(Request $request)
    {
        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
          return $this->ErrorResponse('error_user_not_found',200);
        }
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id])->first();

      if(isset($user) && $user!=array())
      {

            $type=$selection_type=1;
            //start search employeer
            $andwhere1='';
            if(isset($contract_type) && $contract_type!=null)
            {
                if($contract_type!=3)
                {
                    $andwhere1='(user_details.contract_type='.$contract_type.' or user_details.contract_type=3)';
                }

            }
            $andwhere2='';
            if(isset($skill_id) && $skill_id!=null)
            {
              $andwhere2='user_details.skill_id IN ('.$skill_id.')';
            }


            //get location
            $latitude = (isset($user->latitude) && $user->latitude!='')?$user->latitude:0;
            $longitude = (isset($user->longitude) && $user->longitude!='')?$user->longitude:0;

            $radius = 100;
            $radius_query = "(6371 * acos(cos(radians(" . $latitude . "))
                              * cos(radians(users.latitude))
                              * cos(radians(users.longitude)
                              - radians(" . $longitude . "))
                              + sin(radians(" . $latitude . "))
                              * sin(radians(users.latitude))))";
            //start suburb data
            $result['worker_list'] =array();
            $worker_data = Usershortlisted::
            selectRaw("{$radius_query} AS distance,user_shortlisted.id,user_shortlisted.is_deleted")
            //selectRaw("{$radius_query} AS distance,users.*,locations.name as town_name,l.name as suburb_name,user_shortlisted.type,user_shortlisted.job_id,j.proposed_pay_from,j.proposed_pay_to,j.service_needed_type,j.description")
            ->leftjoin('users', 'users.id', '=', 'user_shortlisted.worker_id')
            ->leftjoin('user_details', 'user_details.user_id', '=', 'users.id')
            ->leftjoin('locations', 'locations.id', '=', 'user_details.town_id')
            ->leftjoin('locations as l', 'l.id', '=', 'user_details.suburb_id')
            ->leftjoin('jobs as j', 'j.id', '=', 'user_shortlisted.job_id')

            ->where('users.is_deleted',0)
            ->where('user_shortlisted.is_deleted',0)
            ->where('user_shortlisted.is_active',1)
            ->where('users.actor',3)
            ->where('users.is_active',1)
            ->where('user_shortlisted.type_value',1)
            ->where('user_shortlisted.user_id',$user_id);
            //if($andwhere!='')
            //$worker_data=$worker_data->whereRaw($andwhere);


            if($type==1)//interested/shortlisted
                $worker_data=$worker_data->where('user_shortlisted.type',1);
            else if($type==2) //2= final shorlisted
                $worker_data=$worker_data->where('user_shortlisted.type',2);

            if($selection_type==1)//interested by employer
            {
                $worker_data=$worker_data->whereRaw('(user_shortlisted.is_from_worker=0 OR (user_shortlisted.is_from_worker=1 and user_shortlisted.from_worker_status=1))');
            }
            elseif($selection_type==2)//Apply by worker
            {
                $worker_data=$worker_data->where('user_shortlisted.is_from_worker',1);
                $worker_data=$worker_data->where('user_shortlisted.from_worker_status',0);
            }
            if($andwhere1!='')
            $worker_data=$worker_data->whereRaw($andwhere1);

            if($andwhere2!='')
            $worker_data=$worker_data->whereRaw($andwhere2);

            //$worker_data=$worker_data->whereRaw("{$radius_query} < ?", [$radius]);
            //->where('locations.is_deleted',0)
            //->where('locations.is_active',1)

            $worker_data=$worker_data->orderby('user_shortlisted.id','desc')
            ->get();

             //dd($worker_data);
            if(isset($worker_data) && $worker_data!=array())
            {

                foreach ($worker_data as $value)
                {
                    $value->is_deleted=1;
                    $value->save();
                    //dd($value);
                }
            }
            return $this->SuccessResponse([],'success');
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }

    //********************************************************************************
    //Title : Delete Account
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 28-06-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function deleteAccount(Request $request)
    {
        // $request->validate([
        //   'user_id'=>'required',
        // ]);

        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
            return $this->ErrorResponse('error_user_not_found',200);
        }
        extract($_POST);
        $uid =$user_id= $request->user['id'];
        $user = $data= User::where(['is_deleted'=>0,'id'=>$uid])->first();

        if(isset($data) && $data!=array())
        {

                //    dd($data);
                //check user has any running or hire jobs
                if($data->actor==3)//worker
                {
                    $job_data = Job::where(['is_deleted'=>0,'selected_worker_id'=>$user_id])
                    ->whereRaw('(status = 1 OR status =2)')
                    ->first();
                    // dd($job_data);
                    if(isset($job_data) && $job_data!=array())
                    {
                        return $this->ErrorResponse('error_worker_job_running',200);
                    }
                    else
                    {
                        $data->is_deleted=1;
                        $data->u_date = date('Y-m-d H:i:s');
                        $data->save();
                        $userids[]=$data->id;
                        CommonFunction::deleteAllToken($userids);
                    }
                }
                else //customer
                {

                    $job_data = Job::where(['is_deleted'=>0,'user_id'=>$user_id])
                    ->whereRaw('(status = 1 OR status =2)')
                    ->first();
                    //dd($job_data);
                    //if(isset($job_data) && $job_data!=array())
                    if(0)
                    {
                        return $this->ErrorResponse('error_worker_job_running',200);
                    }
                    else
                    {
                        $data->is_deleted=1;
                        $data->u_date = date('Y-m-d H:i:s');
                        $data->save();
                        $userids[]=$data->id;
                        CommonFunction::deleteAllToken($userids);
                        //delete all job of this users
                        Job::where('user_id',$user->id)->where('is_deleted',0)->update(['is_deleted' => 1]);
                    }
                }
                return $this->SuccessResponse([],'success');
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }

    }
}
