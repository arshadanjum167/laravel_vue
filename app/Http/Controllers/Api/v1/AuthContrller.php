<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Base\ApiController;
use App\Models\Token;
use App\Models\User;
use Hash;
use URL;
use ApiFunction;
use CommonFunction;


class AuthContrller extends ApiController
{
    //********************************************************************************
    //Title : Get Token
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 11-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function getToken(Request $request)
    {
      
      try {
        extract($_POST);
        // dd('aaa');
        if(isset($device_id) && $device_id!=null && isset($device_type) && $device_type!=null)
        {
            //check device id and device type already there
            $userlogindata = Token::where(['device_id' => $device_id, 'device_type' => $device_type])->orderby('last_login','DESC')->first();

            if(isset($userlogindata) && $userlogindata!=array())
            {
                //merge the data
            }
            else
            {
                $userlogindata = new Token();
            }
        }
        else
        {
            $userlogindata = new Token();
        }

        if (isset($device_id) && $device_id!=null)
          $userlogindata->device_id = $device_id;

        if (isset($device_type) && $device_type!=null)
          $userlogindata->device_type = $device_type;

        $userlogindata->user_id = null;
        $userlogindata->access_token =  $this->getRandomToken();
        // $userlogindata->last_login = date("Y-m-d H:i:s");

        $userlogindata->save();
        
        $result['token']['token']=$userlogindata->access_token;
        $result['token']['type']='Bearer';
        return $this->SuccessResponse($result);

      } catch (\Exception $e) {
        dd($e);
      }
    }

    //********************************************************************************
    //Title : Login
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 12-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function login(Request $request)
    {
          $request->validate([
            'email' => 'required',
            'password' => 'required',
            // 'device_id'=>'required',
            // 'device_type'=>'required',
          ]);

          extract($_POST);

          
      $email=$email;
      $user_type=1;
      $data = ApiFunction::getUserDetailOnEmailPassword($email,$password,$user_type);
      $device_type='';
      $device_id='';

       if(isset($data) && $data != array()){
         switch ($data) {
            //  case $data->is_active==0:
            //   $result_code = 803;
            //  break;
             default:
              $result_code = 200;
         }
         /*if from admin panel the user is not activated then  we will show this below message*/
         if($result_code==803 || $result_code==802 || $result_code==801)
         {
             return $this->ErrorResponse('account_deactivate',200);
         }

         // if($data->contact_verified == 0)
         // {
         //   return $this->ErrorResponse('contact_number_not_verified',200);
         // }
         //$data->login_type=1;
         $data->save();
         $token_result = $this->manageToken($data->id, $device_id, $device_type,$request->user['access_token']);
         $result = ApiFunction::apiLogin($data,$token_result,'');

         
         return $this->SuccessResponse($result,'login_success');
       }
       else
       {
          $err_msg='invalid_login_1';
          // if($logininput_type==2)
          //     $err_msg='invalid_login_2';

          return $this->ErrorResponse($err_msg,200);
       }
    }
    //********************************************************************************
    //Title : Register
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 12-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function register(Request $request)
    {
       $request->validate([
         'first_name' => 'required',
         'second_name' => 'required',
         //'contact_number' => 'required',
         //'country_code' => 'required',
         'email' => 'required | email',
         'password' => 'required',
         'user_type'=>'required',
         'device_id'=>'required',
         'device_type'=>'required',
       ]);

       extract($_POST);
       $data = ApiFunction::getUserDetailOnEmail($email,$user_type);

       if($data == null)
       {

          if(isset($contact_number) && $contact_number!=null)
          {
            $country_code=(isset($country_code) && $country_code!=null)?$country_code:'+254';
            $mobiledata = ApiFunction::getUserDetailOnMobile($country_code,$contact_number,$user_type);
            if(isset($mobiledata) && $mobiledata!=array())
            {
                return $this->ErrorResponse('mobile_exist_already',200);
            }
          }



          $user = new User;
          $user->first_name = $first_name;
          $user->second_name = $second_name;
          $user->email = $email;
          $user->password = Hash::make($password);
          $user->actor= $user_type;

          if(isset($contact_number) && $contact_number!=null)
          {
               $user->contact_number = $contact_number;
               if(isset($country_code) && $country_code!=null)
                   $user->country_code = $country_code;
          }

          //$user->otp_code=1234;

          $user->i_date = date('Y-m-d H:i:s',time());
          $user->u_date = date('Y-m-d H:i:s',time());

          $user->nationality_id = $nationality_id ?? 1;


          if($user->save())
          {

                $userdetail = new Userdetail;
                $userdetail->town_id = $hometown_id ?? 1;
                $userdetail->suburb_id = $hometown_suburb_id ?? 2;
                $userdetail->user_id=$user->id;
                $userdetail->i_date = date('Y-m-d H:i:s',time());
                $userdetail->u_date = date('Y-m-d H:i:s',time());
                $userdetail->i_by = $user->id;
                $userdetail->u_by = $user->id;
                $userdetail->save();


              $user->i_by = $user->id;
              $user->u_by = $user->id;
              $user->email_verification_token = ApiFunction::randomstring($user->id);
              $user->email_verification_token_timeout = date('Y-m-d H:i:s', strtotime('+1 hour'));
              $user->save();
              //insert worker notification
              if($user_type==3)
              {
                  CommonFunction::insertworkerpushnotificationsetting($user->id);
              }
              if(isset($user->email) && $user->email!=null)
              {
                  $link =  URL::to("useremailverification?args=".$user->email_verification_token."&type=N");
                  CommonFunction::sendemail($user->email,$user->first_name,$link,'emailverify');
              }
              //$token_result = $this->manageToken($user->id, $device_id, $device_type,$request->user['access_token']);
              //$result = ApiFunction::apiLogin($user,$token_result);
              return $this->SuccessResponse([],'verification_email_sent');
          }
          else {
            return $this->ErrorResponse('error_in_save',200);
          }
       }
       else {
         return $this->ErrorResponse('email_exist_already',200);
       }
    }
    //********************************************************************************
    //Title : Forgot Password
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 13-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function forgotPassword(Request $request)
    {
      $request->validate([
        'logininput_type' => 'required',
        'email_mobile' => 'required',
        'user_type' => 'required',
      ]);

      extract($_POST);

      if($logininput_type==1)//email
      {
        $email=$email_mobile;
        $user = ApiFunction::getUserDetailOnEmail($email,$user_type);

      }
      else
      {
        $contact_number=$email_mobile;
        $country_code=(isset($country_code) && $country_code!=null)?$country_code:'+254';
        $user = ApiFunction::getUserDetailOnMobile($country_code,$contact_number,$user_type);
      }

      if($user)
      {
        if($logininput_type==1)//email
        {
            $random_str = time().rand(10000,99999);
            $user->forgot_password_token =$token= md5($random_str);

            // set forgot password token timeout, set to 1 hour from now
            $user->forgot_password_token_timeout = time();
            $user->forgot_password_token_timeout = date('Y-m-d H:i:s', strtotime('+24 hour'));

            if($user->save())
            {
                $link =  URL::to('password/reset/'.$token);
                CommonFunction::sendemail($user->email,$user->first_name,$link,'forgot_password');
            }
            else
            {
                return $this->ErrorResponse('error_forgot_password',200);
            }
        }
        else //mobile number
        {
            $otp=CommonFunction::generateOtpCode();
            $user->otp=$otp;
            $otp_msg = str_replace('{number}',$otp,config('params.otp_text'));

            CommonFunction::sendsms($country_code.$contact_number,$otp_msg);
            /*updated by arshad*/
            if(config('params.environment') == 'dev'){

                if(isset($user->email) && $user->email!=null)
                {
                    $content = 'Welcome to '.config('params.appName').', One Time Password(OTP) to verify your phone number is :'.$otp;
                    CommonFunction::otpemail($user->email,$user->first_name,$content);
                }
            }

        }
        $user->u_by=$user->id;
        $user->u_date = date('Y-m-d H:i:s',time());
        if($user->save())
        {
          if($logininput_type==1)//email
            return $this->SuccessResponse([],'forgot_password_link_sent');
          else
            return $this->SuccessResponse([],'forgot_password_sms_sent');
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
    //Title : Resend Email
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 13-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function resendEmail(Request $request)
    {
      $request->validate([
        'logininput_type' => 'required',
        'email_mobile' => 'required',
        'user_type' => 'required',
        //'new_contact_number' => 'required',
      ]);

      extract($_POST);

      if($logininput_type==1)//email
      {
        $email=$email_mobile;
        $user = ApiFunction::getUserDetailOnEmail($email,$user_type);

      }
      else
      {
        $contact_number=$email_mobile;
        $country_code=(isset($country_code) && $country_code!=null)?$country_code:'+254';
        $user = ApiFunction::getUserDetailOnMobile($country_code,$contact_number,$user_type);
        if(!$user)
        {
           if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
           return $this->ErrorResponse('error_user_not_found',200);
           }
           $uid = $request->user['id'];
           $user = User::where(['is_deleted'=>0,'id'=>$uid])->first();
        }
        $country_code=(isset($country_code) && $country_code!=null)?$country_code:'+254';
        $contact_number=(isset($new_contact_number) && $new_contact_number!=null)?$new_contact_number:$contact_number;

      }
      //if($isfrom==2)//edit profile
      //{
      //      if(!$user)
      //      {
      //         if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
      //         return $this->ErrorResponse('error_user_not_found',200);
      //         }
      //         $uid = $request->user['id'];
      //         $user = User::where(['is_deleted'=>0,'id'=>$uid])->first();
      //      }
      //}


      if($user)
      {
        if($logininput_type==1)//email
        {
            $user->email_verification_token = ApiFunction::randomstring($user->id);
            $user->email_verification_token_timeout = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $user->save();
            if(isset($user->email) && $user->email!=null)
            {
                $link =  URL::to("useremailverification?args=".$user->email_verification_token."&type=N");
                CommonFunction::sendemail($user->email,$user->first_name,$link,'emailverify');
            }
        }
        else //mobile number
        {
            $otp=CommonFunction::generateOtpCode();
            $user->otp=$otp;
            $otp_msg = str_replace('{number}',$otp,config('params.otp_text'));

            CommonFunction::sendsms($country_code.$contact_number,$otp_msg);
            /*updated by arshad*/
            if(config('params.environment') == 'dev'){

                if(isset($user->email) && $user->email!=null)
                {
                    $content = 'Welcome to '.config('params.appName').', One Time Password(OTP) to verify your phone number is :'.$otp;
                    CommonFunction::otpemail($user->email,$user->first_name,$content);
                }
            }

        }
        $user->u_by=$user->id;
        $user->u_date = date('Y-m-d H:i:s',time());
        if($user->save())
        {
          if($logininput_type==1)//email
            return $this->SuccessResponse([],'email_link_sent');
          else
            return $this->SuccessResponse([],'verify_sms_sent');
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
    //Title : Change Password
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 13-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************


    public function changePassword(Request $request)
    {
      $request->validate([
        'user_type' => 'required',
        'old_password' => 'required',
        'new_password'=>'required',
      ]);

      if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        return $this->ErrorResponse('error_user_not_found',200);
      }

      extract($_POST);
      $user_id = $request->user['id'];
      $user = User::where(['is_deleted'=>0,'id'=>$user_id,'actor'=>$user_type])->first();

      if(isset($user) && $user!=array()){

        if(isset($old_password) && $old_password!=null){
          if(!Hash::check($old_password,$user->password)){
            return $this->ErrorResponse('invalid_old_password',200);
          }
        }

        $user->password = Hash::make($new_password);
        $user->u_by = $user_id;
        $user->u_date = date('Y-m-d H:i:s',time());

        if($user->save()){
          return $this->SuccessResponse([],'password_updated');
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
    //Title : Logout
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 12-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function logout(Request $request)
    {
      if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
        return $this->ErrorResponse('error_user_not_found',200);
      }

      $this->updateAccessTockentoNull($request->user['access_token']);
      return $this->SuccessResponse([],'logout_success');

    }
    //********************************************************************************
    //Title : Verify OTP
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 13-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function verifyOtp(Request $request)
    {
      $request->validate([
        'contact_number' => 'required',
        'isfrom' => 'required',
        'otp' => 'required',
        'user_type' => 'required',
      ]);

      extract($_POST);

      $country_code=(isset($country_code) && $country_code!=null)?$country_code:'+254';
      $user = ApiFunction::getUserDetailOnMobile($country_code,$contact_number,$user_type);
      if(!$user)
      {
        //get user id formm token
        if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
            return $this->ErrorResponse('error_user_not_found',200);
          }

          $user_id = $request->user['id'];
          $user = User::where(['is_deleted'=>0,'id'=>$user_id,'actor'=>$user_type])->first();
      }

      if($user){

        if($user->otp != $otp){
          return $this->ErrorResponse('invalid_otp',200);
        }

        if($isfrom==2)
        {
          $new_country_code=(isset($new_country_code) && $new_country_code!=null)?$new_country_code:'+254';
          $user->country_code = $new_country_code;
          $user->contact_number = $new_contact_number;

        }

        $user->is_mobile_verified = 1;
        if($user->save())
        {
          return $this->SuccessResponse([],'mobile_verified_successfully');
        }
        else {
          return $this->ErrorResponse('error_in_save',200);
        }
      }
      else {
        return $this->ErrorResponse('error_contact_not_found',200);
      }
    }


  public function socialLogin(Request $request)
  {
    $request->validate([
      'social_id' => 'required',
      'social_type' => 'required', // 2 Google, 3	facebook, 4	twitter, 5	instagram
      'is_provided_by_user' => 'required',
      'is_merge' => 'required',
      'first_name' => 'required',
      'second_name' => 'required',
      'email' => 'required|email',
      'device_id' => 'required',
      'device_type' => 'required',
      'user_type' => 'required',
    ]);

    extract($_POST);

    if(!in_array($social_type,[2,3,4,5])){
      return $this->ErrorResponse('bad_request',200);
    }

    if(!isset($device_id)){$device_id=null;}
    if(!isset($device_type)){$device_type=null;}

    $user = ApiFunction::getUserDetailFromSocialId($social_type,$social_id,$user_type);

    if($user != array()){ // social id exists

      if($user->is_active == 0) // check is account deactived
      {
          return $this->ErrorResponse('account_deactivate',200);
      }

      $user->login_type = $social_type;
      $user->save();

      $token_result = $this->manageToken($user->id, $device_id, $device_type,$request->user['access_token']);
      $result = ApiFunction::apiLogin($user,$token_result);

      // if($social_type == 2 && $user->google_verified ==0 ){
      //   return $this->SuccessResponse($result);
      // }
      // if ($social_type == 3 && $user->facebook_verified == 0) {
      //   return $this->SuccessResponse($result);
      // }
      // if ($social_type == 4 && $user->twitter_verified == 0) {
      //   return $this->SuccessResponse($result);
      // }
      // if ($social_type == 5 && $user->instagram_verified == 0) {
      //   return $this->SuccessResponse($result);
      // }
      return $this->SuccessResponse($result);
    }
    else { // social id is not exists

      // if social id is not exists than user need to provide country code and contact number
      if(isset($email) && $email != null){

         $user = ApiFunction::getUserDetailOnEmail($email,$user_type);

         if($user){ // contact number given by user is already exists

           if($user->is_active == 0) // check is account deactived
           {
               return $this->ErrorResponse('account_deactivate',200);
           }

           //  user provided contact number so we need to  check that user want to merge account or not
           if($is_merge == 2)
           {
               $result['is_merge']=1;
               return $this->SuccessResponse($result);
           }
           else
           {
             // user want to merge account with existing contact number
             //$otp=CommonFunction::generateOtpCode();
             switch ($social_type) {
               case 2: // google
                 $user->google_id = $social_id;
                 $user->google_token = md5($social_id);
                 $token=$user->google_token;

                 if($is_provided_by_user == 2 ){
                     $user->is_google_verified = 1;
                     $user->is_email_verified = 1;
                 }
                 break;
             }
             
             if(isset($first_name) && $first_name!=null)
              $user->first_name = $first_name;
             if(isset($second_name) && $second_name!=null)
              $user->second_name = $second_name;

            $user->login_type = $social_type;

            $login_imidiatly = 'n';
            /*user want to merge the account, check email provided by user or not*/
            if(isset($is_provided_by_user) && $is_provided_by_user == 2)
            {
                $login_imidiatly = 'y';
            }
            $user->u_by =$user->id;
            $user->u_date = date('Y-m-d H:i:s',time());
              if($user->save()) // save record
              {
                if($login_imidiatly=='n')
                {
                    if(isset($email) && $email!=null)
                    {
                        $first_name=$user->first_name;
                        //$link=Url::to("@web/site/useremailverification?args=".$token."&type=".strtoupper($social_type),true);
                        //$email= Yii::$app->apifunction->encryptdecryptvalue($user_obj_from_phone->email,'d');
                        //Yii::$app->common->sendemail($email,$first_name,$link,'emailverify');
                        $user->email_verification_token = ApiFunction::randomstring($user->id);
                        $user->email_verification_token_timeout = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        $user->save();
                        if(isset($user->email) && $user->email!=null)
                        {
                            $link =  URL::to("useremailverification?args=".$token."&type=G");
                            CommonFunction::sendemail($user->email,$user->first_name,$link,'emailverify');
                        }
                    }

                }
                $token_result = $this->manageToken($user->id, $device_id, $device_type,$request->user['access_token']);
                $result = ApiFunction::apiLogin($user,$token_result,'n');
                if($is_provided_by_user == 1){
                  return $this->SuccessResponse($result,'verification_sms_sent');
                }
                else {
                  return $this->SuccessResponse($result);
                }
              }
              else { // error in save
                return $this->ErrorResponse('error_in_save',200);
              }
           }
         }
         else { // contact number is not exists so we will create a new account
            
            if(isset($is_tandc_accepted) && $is_tandc_accepted==0)
            {
                $result['is_new_user']=1;
                return $this->SuccessResponse($result,'success');   
            }

           $user = new User;

            if(isset($first_name) && $first_name!=null)
             $user->first_name = $first_name;

            if(isset($second_name) && $second_name!=null)
                $user->second_name = $second_name;

           if(isset($email) && $email!=null)
            $user->email = $email;

           if(isset($profile_image) && $profile_image!=null)
            $user->profile_image = $profile_image;

           //if(isset($country_code) && $country_code!=null)
           // $user->country_code = $country_code;
           //
           //if(isset($contact_number) && $contact_number!=null)
           // $user->contact_number = $contact_number;

           //$otp=CommonFunction::generateOtpCode();
            $login_imidiatly='n';
           $user->actor = $user_type;
           $user->i_date = date('Y-m-d H:i:s',time());
           $user->u_date = date('Y-m-d H:i:s',time());

           $user->login_type = $social_type;

           switch ($social_type) {
             case 2: // google
               $user->google_id = $social_id;
               $user->google_token = md5($social_id);
               $token=$user->google_token;

               if($is_provided_by_user == 2 ){
                   $user->is_google_verified = 1;
                   $user->is_email_verified = 1;
               }
               break;
           }
           
          $user->nationality_id = $nationality_id ?? 1;
          
           if($user->save()) // save record
           {
                //insert worker notification
                if($user_type==3)
                {
                    CommonFunction::insertworkerpushnotificationsetting($user->id);
                }
                $userdetail = new Userdetail;
                $userdetail->user_id=$user->id;
                $userdetail->town_id = $hometown_id ?? 1;
                $userdetail->suburb_id = $hometown_suburb_id ?? 2;
                $userdetail->i_date = date('Y-m-d H:i:s',time());
                $userdetail->u_date = date('Y-m-d H:i:s',time());
                $userdetail->i_by = $user->id;
                $userdetail->u_by = $user->id;
                $userdetail->save();

                $user->i_by=$user->id;
                $user->u_by=$user->id;
                if(isset($is_provided_by_user) && $is_provided_by_user == 1)
                {
                    //$first_name='';
                    //if(isset($user_obj->first_name) && $user_obj->first_name!=null)
                    //    $first_name .=$user_obj->first_name;
                    //if(isset($user_obj->last_name) && $user_obj->last_name!=null)
                    //    $first_name .=$user_obj->last_name;
                    //
                    //$first_name='User'
                    $first_name=$user->first_name;
                    $user->email_verification_token = ApiFunction::randomstring($user->id);
                    $user->email_verification_token_timeout = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    if(isset($user->email) && $user->email!=null)
                    {
                        $link =  URL::to("useremailverification?args=".$token."&type=G");
                        CommonFunction::sendemail($user->email,$user->first_name,$link,'emailverify');
                    }
                }
                $user->save();
                $token_result = $this->manageToken($user->id, $device_id, $device_type,$request->user['access_token']);
                $result = ApiFunction::apiLogin($user,$token_result);
                if($is_provided_by_user == 1){
                  // contact number not fetch by social login so we will send otp to usr for social contact verification
                  return $this->SuccessResponse($result,'verification_email_sent');
                }
                else {
                  return $this->SuccessResponse($result);
                }
           }
           else { // error in save
             return $this->ErrorResponse('error_in_save',200);
           }
         }
      }
      else { // contact number not provided by user so we directly throw error to user that provide contact numer
             //  than app ask user to enter mobile number and call this api again
         $result['is_email_required']=1;
         return $this->SuccessResponse($result);
      }
    }
  }

    //********************************************************************************
    //Title : Edit Mobile
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 13-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function editMobile(Request $request)
    {
      $request->validate([
        'contact_number' => 'required',
        'user_type' => 'required',
        'new_contact_number'=>'required',
      ]);

      extract($_POST);

      $country_code=(isset($country_code) && $country_code!=null)?$country_code:'+254';
      $user = ApiFunction::getUserDetailOnMobile($country_code,$contact_number,$user_type);
        if(!$user)
        {
           if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
           return $this->ErrorResponse('error_user_not_found',200);
           }
           $uid = $request->user['id'];
           $user = User::where(['is_deleted'=>0,'id'=>$uid])->first();
        }
       //dd($user);
       if(isset($user) && $user!=array())
       {
            $new_country_code=(isset($new_country_code) && $new_country_code!=null)?$new_country_code:'+254';
            $new_user = ApiFunction::getUserDetailOnMobile($new_country_code,$new_contact_number,$user_type);
            if(isset($new_user) && $new_user!=array())
            {
              return $this->ErrorResponse('mobile_exist_already',200);
            }
            else
            {
              $otp=CommonFunction::generateOtpCode();
              $user->otp=$otp;
              $otp_msg = str_replace('{number}',$otp,config('params.otp_text'));

              CommonFunction::sendsms($new_country_code.$new_contact_number,$otp_msg);
              /*updated by arshad*/
              if(config('params.environment') == 'dev'){

                  if(isset($user->email) && $user->email!=null)
                  {
                      $content = 'Welcome to '.config('params.appName').', One Time Password(OTP) to verify your phone number is :'.$otp;
                      CommonFunction::otpemail($user->email,$user->first_name,$content);
                  }
              }
              if($user->save())
              {
                return $this->SuccessResponse([],'otp_sent');
              }
              else {
                return $this->ErrorResponse('error_in_save',200);
              }
            }
        }
        else{
            //not found
            return $this->ErrorResponse('error_user_not_found',200);
        }
    }

    //********************************************************************************
    //Title : Change Password
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 13-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public function resetPassword(Request $request)
    {
      $request->validate([
        'contact_number' => 'required',
        'new_password'=>'required',
        'user_type'=>'required',
      ]);

      //if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
      //  return $this->ErrorResponse('error_user_not_found',200);
      //}

      extract($_POST);
      $country_code=(isset($country_code) && $country_code!=null)?$country_code:'+254';
      $user = ApiFunction::getUserDetailOnMobile($country_code,$contact_number,$user_type);

      if($user)
      {


        if(Hash::check($new_password,$user->password))
        {
            return $this->ErrorResponse('error_same_old_password',200);
        }

        $user->password = Hash::make($new_password);
        $user->u_by = $user->id;
        $user->u_date = date('Y-m-d H:i:s',time());

        if($user->save())
        {
          return $this->SuccessResponse([],'password_updated');
        }
        else {
          return $this->ErrorResponse('error_in_save',200);
        }

      }
      else {
        return $this->ErrorResponse('error_user_not_found',200);
      }
    }


}
