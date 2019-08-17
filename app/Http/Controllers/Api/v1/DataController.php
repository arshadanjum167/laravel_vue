<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Base\ApiController;
use App\Models\Token;
use App\Models\User;
use App\Models\Usercontract;
use App\Models\Userworklocationtown;
use App\Models\Userworklocationsuburb;
use App\Models\Nationality;
use App\Models\Location;
use App\Models\Skill;
use App\Models\Maritalstatus;
use App\Models\Contactpackage;
use App\Models\Religion;
use App\Models\Educationcertificate;
use App\Models\Govtinstitute;
use App\Models\Domestictraininginstitute;
use App\Models\Faq;
use App\Models\Terminatereason;
use App\Models\Claimcouponreason;
use App\Models\Query;
use App\Models\Setting;
use App\Models\Job;
use Hash;
use URL;
use ApiFunction;
use CommonFunction;
use DB;

class DataController extends ApiController
{

    //********************************************************************************
    //Title : nationality List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 14-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function nationalityList(Request $request)
    {

        //$request->validate([
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
            //$start=(isset($start) && $start!='')?$start:0;
            //$limit=(isset($limit) && $limit!='')?$limit:10;
            //$result['is_last'] = 1;
            $result['nationality_list'] =array();
            $nationality_data = Nationality::where('is_deleted',0)
            ->where('is_active',1)
            //->where(['user_id'=>$requested_user_id])
            //->skip($start)->take($limit)
            ->orderby('name')
            ->get();
            //dd($nationality_data);
            if(isset($nationality_data) && $nationality_data!=array())
            {
                $i=0;
                foreach ($nationality_data as $key => $value)
                {
                  $result['nationality_list'][$i]['id'] = $value->id;
                  $result['nationality_list'][$i]['nationality_name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $i++;
                }

                //if($i >= $limit){
                //    unset($data['posts'][$i]);
                //    $data['is_last'] =0;
                //}
            }
            return $this->SuccessResponse($result);
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }

    //********************************************************************************
    //Title : town suburb  List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 14-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function townsuburbList(Request $request)
    {

        //$request->validate([
        //  'user_type'=>'required',
        //]);

        //if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::find($user_id);

      //if(isset($user) && $user!=array())
      //{
            //$start=(isset($start) && $start!='')?$start:0;
            //$limit=(isset($limit) && $limit!='')?$limit:10;
            //$result['is_last'] = 1;
            $result['town_list'] =array();
            $town_data = Location::where('is_deleted',0)
            ->where('is_active',1)
            ->where('parent_id',0)
            //->where(['user_id'=>$requested_user_id])
            //->skip($start)->take($limit)
            //->orderby('name')
            ->orderByRaw("FIELD(name ,'Nairobi') DESC,name")
            ->get();
            //dd($town_data);
            $result['town_list'][0]['town_id'] = 0;
            $result['town_list'][0]['town_name'] = 'Any';
            $result['town_list'][0]['suburb_list'] =array();
            if(isset($town_data) && $town_data!=array())
            {
                $i=1;
                foreach ($town_data as $key => $value)
                {
                    $result['town_list'][$i]['town_id'] = $value->id;
                    $result['town_list'][$i]['town_name'] = (isset($value->name) && $value->name!=null)?$value->name:'';

                    //get suburb data
                    $result['town_list'][$i]['suburb_list'] =array();

                    $suburb_data = Location::where('is_deleted',0)
                    ->where('is_active',1)
                    ->where('parent_id',$value->id)
                    //->where(['user_id'=>$requested_user_id])
                    //->skip($start)->take($limit)
                    //->orderby('name')
                    ->orderByRaw("FIELD(name ,'Athi River') DESC,name")
                    ->get();
                    //dd($suburb_data);
                    if(isset($suburb_data) && $suburb_data!=array())
                    {
                        $j=0;
                        foreach ($suburb_data as $key => $value)
                        {
                            $result['town_list'][$i]['suburb_list'][$j]['id'] =$value->id;
                            $result['town_list'][$i]['suburb_list'][$j]['suburb_name'] =$value->name;

                            $j++;
                        }
                    }
                  //end suburb data

                  $i++;
                }

                //if($i >= $limit){
                //    unset($data['posts'][$i]);
                //    $data['is_last'] =0;
                //}
            }
            return $this->SuccessResponse($result);
      //}
      //else {
      //  return $this->ErrorResponse('error_user_not_found',200);
      //}
    }

    //********************************************************************************
    //Title : Skill List With Jobs Count
    //Developer:Rahil Momin.
    //Email: rahil@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Rahil Momin .
    //Created Date : 27-06-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function skillListWithJobsCount(Request $request)
    {

        $result['skill_list'] =array();
        $skill_data = Skill::where('is_deleted',0)
        ->where('is_active',1)
        ->orderby('name')
        ->get();
        if(isset($skill_data) && $skill_data!=array())
        {
            $i=0;
            foreach ($skill_data as $key => $value)
            {
                $result['skill_list'][$i]['id'] = $value->id;
                $result['skill_list'][$i]['skill_name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                $result['skill_list'][$i]['contract_type'] = (isset($value->contract_type) && $value->contract_type!=null)?(int)$value->contract_type:'';
                $result['skill_list'][$i]['preferred_residency'] = (isset($value->preferred_residency) && $value->preferred_residency!=null)?(int)$value->preferred_residency:'';
                $result['skill_list'][$i]['job_count'] = DB::table('jobs')->where('status',0)->where('skill_id',$value->id)->groupBy('skill_id')->count();
                $i++;
            }
        }
        return $this->SuccessResponse($result);
    }

    //********************************************************************************
    //Title : Jobs list
    //Developer:Rahil Momin.
    //Email: rahil@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Rahil Momin .
    //Created Date : 27-06-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function jobsListHome(Request $request)
    {
        $result['job_list'] = array();
        $jobsData = DB::table('jobs')->join('users','users.id','=','jobs.user_id')->join('skills','skills.id','=','jobs.skill_id')->select('jobs.id', 'jobs.description', 'jobs.address', 'skills.name', 'users.profile_image')->groupBy('jobs.id')->orderBy('jobs.id','desc')->take(6)->get();
        if(isset($jobsData) && $jobsData!=array())
            {
                $i=0;
                foreach ($jobsData as $key => $value)
                {
                  $result['job_list'][$i]['id'] = $value->id;
                  $result['job_list'][$i]['description'] = (isset($value->description) && $value->description!=null)?$value->description:'Not Set';
                  $result['job_list'][$i]['address'] = (isset($value->address) && $value->address!=null)?$value->address:'Not Set';
                  $result['job_list'][$i]['name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $result['job_list'][$i]['profile_image'] = (isset($value->profile_image) && $value->profile_image!=null)?$value->profile_image:'';
                  $i++;
                }
            }
        return $this->SuccessResponse($result);
    }

    //********************************************************************************
    //Title : Skill List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 14-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function skillList(Request $request)
    {

        //$request->validate([
        //  'user_type'=>'required',
        //]);

        //if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::find($user_id);

      //if(isset($user) && $user!=array())
      //{
            //$start=(isset($start) && $start!='')?$start:0;
            //$limit=(isset($limit) && $limit!='')?$limit:10;
            //$result['is_last'] = 1;
            $is_take_charge=Setting::where('is_deleted',0)->where('key', '=', 'skill_charge')->first();
            $result['skill_list'] =array();
            $skill_data = Skill::where('is_deleted',0)
            ->where('is_active',1)
            //->where(['user_id'=>$requested_user_id])
            //->skip($start)->take($limit)
            ->orderby('name')
            ->get();
            //dd($skill_data);
            if(isset($skill_data) && $skill_data!=array())
            {
                $i=0;
                foreach ($skill_data as $key => $value)
                {
                  $result['skill_list'][$i]['id'] = $value->id;
                  $result['skill_list'][$i]['skill_name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $result['skill_list'][$i]['contract_type'] = (isset($value->contract_type) && $value->contract_type!=null)?(int)$value->contract_type:'';
                  $result['skill_list'][$i]['preferred_residency'] = (isset($value->preferred_residency) && $value->preferred_residency!=null)?(int)$value->preferred_residency:'';

                  $result['skill_list'][$i]['is_chargeable'] = (isset($value->is_paid) && $value->is_paid!=null)?(int)$value->is_paid:0;
                  //$result['skill_list'][$i]['price']=0;
                  //if(isset($value->is_paid) && $value->is_paid!=null)
                  $result['skill_list'][$i]['price'] = (isset($value->price) && $value->price!=null)?$value->price:'';
                  $result['skill_list'][$i]['is_take_charge'] = 1;
                  if(isset($is_take_charge) && $is_take_charge!=array())
                  {
                        $result['skill_list'][$i]['is_take_charge']=(int)$is_take_charge->value;
                  }

                  $i++;
                }

                //if($i >= $limit){
                //    unset($data['posts'][$i]);
                //    $data['is_last'] =0;
                //}
            }
            return $this->SuccessResponse($result);
      //}
      //else {
      //  return $this->ErrorResponse('error_user_not_found',200);
      //}
    }

    //********************************************************************************
    //Title : All List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 14-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function allList(Request $request)
    {

        //$request->validate([
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
            // start marital status list
            $result['marital_status_list'] =array();
            $maritalstatus_data = Maritalstatus::where('is_deleted',0)
            ->where('is_active',1)
            ->orderby('name')
            ->get();
            if(isset($maritalstatus_data) && $maritalstatus_data!=array())
            {
                $i=0;
                foreach ($maritalstatus_data as $key => $value)
                {
                  $result['marital_status_list'][$i]['id'] = $value->id;
                  $result['marital_status_list'][$i]['name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $i++;
                }
            }
            // end marital status list

            // start religion list
            $result['religion_list'] =array();
            $religion_data = Religion::where('is_deleted',0)
            ->where('is_active',1)
            ->orderby('name')
            ->get();
            if(isset($religion_data) && $religion_data!=array())
            {
                $i=0;
                foreach ($religion_data as $key => $value)
                {
                  $result['religion_list'][$i]['id'] = $value->id;
                  $result['religion_list'][$i]['name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $i++;
                }
            }
            // end religion list

            // start education list
            $result['education_list'] =array();
            $education_data = Educationcertificate::where('is_deleted',0)
            ->where('is_active',1)
            ->orderby('id')
            ->get();
            if(isset($education_data) && $education_data!=array())
            {
                $i=0;
                foreach ($education_data as $key => $value)
                {
                  $result['education_list'][$i]['id'] = $value->id;
                  $result['education_list'][$i]['name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $i++;
                }
            }
            // end education list

            // start gvt institute list
            $result['govt_institute_list'] =array();
            $govt_institute_data = Govtinstitute::where('is_deleted',0)
            ->where('is_active',1)
            ->orderby('id')
            ->get();
            if(isset($govt_institute_data) && $govt_institute_data!=array())
            {
                $i=0;
                foreach ($govt_institute_data as $key => $value)
                {
                  $result['govt_institute_list'][$i]['id'] = $value->id;
                  $result['govt_institute_list'][$i]['name'] = (isset($value->short_form) && $value->short_form!=null)?$value->short_form:'';
                  $result['govt_institute_list'][$i]['full_name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $i++;
                }
            }
            // end gvt institute list

            // start special training list
            $result['specialized_training_list'] =array();
            $training_data = Domestictraininginstitute::where('is_deleted',0)
            ->where('is_active',1)
            ->where(function ($query) use($user_id){
                $query->where('approved_by_admin', 1)
                ->orWhere('i_by','=', $user_id);
              })
            ->orderby('name')
            ->get();
            if(isset($training_data) && $training_data!=array())
            {
                $i=0;
                foreach ($training_data as $key => $value)
                {
                  $result['specialized_training_list'][$i]['id'] = $value->id;
                  $result['specialized_training_list'][$i]['name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $i++;
                }
            }
            // end special training list

            // start total experince list
            $result['total_experience_list'] = array();
            $experiance_data = config('params.total_experiance_array');
            if(isset($experiance_data) && $experiance_data!=array())
            {
                $i=0;
                foreach ($experiance_data as $key => $value)
                {
                  $result['total_experience_list'][$i]['id'] = $key;
                  $result['total_experience_list'][$i]['name'] = (isset($value) && $value!=null)?$value:'';
                  $i++;
                }
            }
            // end total experince list

            // start langauage list
            $result['language_list'] = array();
            $experiance_data = config('params.language_proficiency');
            if(isset($experiance_data) && $experiance_data!=array())
            {
                $i=0;
                foreach ($experiance_data as $key => $value)
                {
                  $result['language_list'][$i]['id'] = $key;
                  $result['language_list'][$i]['name'] = (isset($value) && $value!=null)?$value:'';
                  $i++;
                }
            }
            // end langauage list

            // start terminate reason list
            $result['terminate_reason_list'] = array();
            $terminate_reason_data = Terminatereason::where('is_deleted',0)
            ->where('is_active',1)
            ->orderby('name')
            ->get();
            if(isset($terminate_reason_data) && $terminate_reason_data!=array())
            {
                $i=0;
                foreach ($terminate_reason_data as  $value)
                {
                  $result['terminate_reason_list'][$i]['id'] = $value->id;;
                  $result['terminate_reason_list'][$i]['name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $i++;
                }
            }
            // end terminate reason list

            // start terminate reason list
            $result['claim_coupon_terminate_reason_list'] = array();
            $claimcouponreason_data = Claimcouponreason::where('is_deleted',0)
            ->where('is_active',1)
            ->orderby('name')
            ->get();
            if(isset($claimcouponreason_data) && $claimcouponreason_data!=array())
            {
                $i=0;
                foreach ($claimcouponreason_data as  $value)
                {
                  $result['claim_coupon_terminate_reason_list'][$i]['id'] = $value->id;;
                  $result['claim_coupon_terminate_reason_list'][$i]['name'] = (isset($value->name) && $value->name!=null)?$value->name:'';
                  $i++;
                }
            }
            // end terminate reason list
            return $this->SuccessResponse($result);
        }
        else {
          return $this->ErrorResponse('error_user_not_found',200);
        }
    }
    //********************************************************************************
    //Title : CMS PAges
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 16-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function cmsPages(Request $request)
    {
        $request->validate([
          'user_type'=>'required',
        ]);
        //if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
        extract($_POST);
        //$link =  URL::to("useremailverification?args=".$user->email_verification_token."&type=N");
        $result['terms_and_conditions'] = URL::to("terms/".$user_type);
        $result['privacy_policy'] = URL::to("privacy/".$user_type);
        $result['about_us'] = URL::to("about/".$user_type);
        $result['terms_of_service_for_coupon'] = URL::to("terms_of_service_for_coupon/".$user_type);

        return $this->SuccessResponse($result);
    }
    //********************************************************************************
    //Title : Contact us
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 16-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function contactUs(Request $request)
    {
        $request->validate([
          //'subject' => 'required',
          'message'=>'required',
          'user_type'=>'required',
        ]);

        //if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
        $subject=(isset($subject) && $subject!='')?$subject:null;

        extract($_POST);
        $user_id=0;
        $query=new Query();
        $query->subject=$subject;
        $query->text=$message;
        $admin_conact_email=config('params.admin_contactus_email');
        if(isset($request->user['id']) && $request->user['id']!=null)
        {
            $user_id = $request->user['id'];
            $user = User::where(['is_deleted'=>0,'id'=>$user_id,'actor'=>$user_type])->first();
            if(isset($user) && $user!=array())
            {

              //Mail::to('yasin@peerbits.com')->send(new ContactAdmin($subject,$message,$user->full_name));
              CommonFunction::sendcontactusemail($admin_conact_email,$subject,$message,$user->first_name,$user->email);

            }
            else
            {
                return $this->ErrorResponse('error_user_not_found',200);
            }
        }
        else
        {
            $guest_name=(isset($name) && $name!=null)?$name:'';
            $guest_email=(isset($email) && $email!=null)?$email:'';
            //geust user
            CommonFunction::sendcontactusemail($admin_conact_email,$subject,$message,$guest_name,$guest_email);
            //$query->name=$guest_name;
            //$query->email=$guest_email;
        }
        //insert query


        //$query->user_type = $user_type;
        $query->i_by = $user_id;
        $query->i_date = date('Y-m-d H:i:s',time());
        $query->u_by = $user_id;
        $query->u_date = date('Y-m-d H:i:s',time());
        $query->save();
        return $this->SuccessResponse([],'success_contact_us');
    }

    //********************************************************************************
    //Title : Skill List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 14-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getFaq(Request $request)
    {

        $request->validate([
          'user_type'=>'required',
        ]);

        //if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
        extract($_POST);
        //$user_id = $request->user['id'];
        //$user = User::where(['is_deleted'=>0,'id'=>$user_id,'actor'=>$user_type])->first();
        //
        //if(isset($user) && $user!=array())
        //{
              //$start=(isset($start) && $start!='')?$start:0;
              //$limit=(isset($limit) && $limit!='')?$limit:10;
              //$result['is_last'] = 1;
              $result['faq_list'] =array();
              $faq_data = Faq::where('is_deleted',0)
              ->where('is_active',1)
              ->where('actor',$user_type)
              //->where(['user_id'=>$requested_user_id])
              //->skip($start)->take($limit)
              ->orderby('id')
              ->get();
              //dd($faq_data);
              if(isset($faq_data) && $faq_data!=array())
              {
                  $i=0;
                  foreach ($faq_data as $key => $value)
                  {
                    $result['faq_list'][$i]['id'] = $value->id;
                    $result['faq_list'][$i]['question'] = (isset($value->question) && $value->question!=null)?$value->question:'';
                    $result['faq_list'][$i]['description'] = (isset($value->answer) && $value->answer!=null)?$value->answer:'';


                    $i++;
                  }

                  //if($i >= $limit){
                  //    unset($data['posts'][$i]);
                  //    $data['is_last'] =0;
                  //}
              }
              return $this->SuccessResponse($result);
        //}
        //else {
        //  return $this->ErrorResponse('error_user_not_found',200);
        //}
    }
    //********************************************************************************
    //Title : Contact List
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 19-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function contactList(Request $request)
    {

        //$request->validate([
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
            //$start=(isset($start) && $start!='')?$start:0;
            //$limit=(isset($limit) && $limit!='')?$limit:10;
            //$result['is_last'] = 1;
            $result['contact_list'] =array();
            $contactpackage_data = Contactpackage::where('is_deleted',0)
            ->where('is_active',1)
            //->where(['user_id'=>$requested_user_id])
            //->skip($start)->take($limit)
            ->orderby('id')
            ->get();
            //dd($contactpackage_data);
            if(isset($contactpackage_data) && $contactpackage_data!=array())
            {
                $i=0;
                foreach ($contactpackage_data as $key => $value)
                {
                  $result['contact_list'][$i]['id'] = $value->id;
                  $result['contact_list'][$i]['contact_count'] = (isset($value->contact) && $value->contact!=null)?(int)$value->contact:0;
                  $result['contact_list'][$i]['total_price'] = (isset($value->price) && $value->price!=null)?(int)$value->price:0;
                  $result['contact_list'][$i]['validity_factor'] = (isset($value->validity_factor) && $value->validity_factor!=null)?(int)$value->validity_factor:0;
                  $result['contact_list'][$i]['validity_value'] = (isset($value->validity_value) && $value->validity_value!=null)?(int)$value->validity_value:0;
                  $i++;
                }

                //if($i >= $limit){
                //    unset($data['posts'][$i]);
                //    $data['is_last'] =0;
                //}
            }
            return $this->SuccessResponse($result);
      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }
}
