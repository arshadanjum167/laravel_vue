<?php
namespace App\Helpers;
use App\Models\User;
use App\Models\Job;
use App\Models\Usercontract;
use App\Models\Userworklocationtown;
use App\Models\Userworklocationsuburb;
use App\Models\Govtinstitute;
//use App\Models\Usercontract;

use Hash;

class ApiFunction
{
    //********************************************************************************
    //Title : get User Detail On Mobile Password
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 12-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function getUserDetailOnMobilePassword($country_code,$contact_number,$password,$actor)
    {
      $result_obj =[];//blank array for the result object
          //query one for user
          try {
              $result_obj = User::where(['is_deleted'=>0,'country_code'=>$country_code,'contact_number'=>$contact_number,'actor'=>$actor])->first();
        if($result_obj)
        {
          if(!Hash::check($password,$result_obj->password)){
            return [];
          }
        }
          }
          catch(Exception $e) {
          }
      return $result_obj;
    }
    //********************************************************************************
    //Title : get User Detail On Email Password
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 12-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function getUserDetailOnEmailPassword($email,$password,$actor)
    {
      $result_obj =[];//blank array for the result object
          //query one for user
          try {
              $result_obj = User::where(['email'=>$email])->first();
            //   dd($result_obj);
        if($result_obj)
        {
            // dd($password.'=='.$result_obj->password);
          if(!Hash::check($password,$result_obj->password)){
            return [];
          }
        }
          }
          catch(Exception $e) {
          }
      return $result_obj;
    }


  public static function apiLogin($data,$token,$is_merge="")
  {
    try {
      $result['message'] = config('params.login_success');
      if($data->actor==3)//worker
      $result = ApiFunction::workerResponse($data);
      else //customer
      $result = ApiFunction::userResponse($data);
      if(isset($is_merge) && $is_merge!=null)
       {
			$result["user"]["is_merge"] = \strtolower($is_merge);
            if(\strtolower($is_merge)=='n')
            $result["user"]["is_merge"]=0;
            else
            $result["user"]["is_merge"]=1;
		}else{
			$result["user"]["is_merge"] = \strtolower("Y");
            $result["user"]["is_merge"]=1;
		}
    }
    catch(Exception $e) {
    }

    $result["token"] = $token;
    return $result;
  }
    //********************************************************************************
    //Title : user Response
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 12-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function userResponse($data)
    {
      $result['user']=array();
      if(isset($data) && $data!=null)
      {

            $result['user']['user_id']=$data->id;
            $result['user']['name']=(isset($data->name) && $data->name!=null)?$data->name:'';
            $result['user']['email']=(isset($data->email) && $data->email!=null)?$data->email:'';
            //$result['user']['user_type']=(int)$data->actor;
            
            

      }
      return $result;
    }
    


    public static function getUserDetailOnMobile($country_code,$contact_number,$actor)
    {
      $result_obj =[];//blank array for the result object
          //query one for user
          try {
              $result_obj = User::where(['is_deleted'=>0,'country_code'=>$country_code,'contact_number'=>$contact_number,'actor'=>$actor])->first();
          }
          catch(Exception $e) {
          }
      return $result_obj;
    }
    //********************************************************************************
    //Title : get User Detail from Email
    //Developer:Arshad Shaikh.
    //Email: arshad@peerbits.com.
    //Company: Peerbits Solutions.
    //Project: Diggz.
    //Created By:Arshad Shaikh .
    //Created Date : 11-02-2019.
    //Updated Date :
    //Updated By :
    //********************************************************************************
    public static function getUserDetailOnEmail($email,$actor)
    {
      $result_obj =[];//blank array for the result object
          //query one for user
          try {
              $result_obj = User::where(['is_deleted'=>0,'email'=>$email,'actor'=>$actor])->first();
          }
          catch(Exception $e) {
          }
      return $result_obj;
    }

  
    public static function randomstring($id)
    {
        $random_str = time().rand(10000,99999);
        $res = md5($random_str);
        //$check = Users::find()->where([$id=>$res])->one();
        //if($check)
        //{
        //    $code = $this->randomstring($id);
        //    return $code;
        //}
        return $res;
    }
}
