<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use App\Models\Users;
use App\Models\Token;

trait ApiResponser
{
	protected function getRandomToken(){
		return uniqid(base64_encode(str_random(60)));
	}

	protected function SuccessResponse($data,$message='success',$code=200){
      $response['error'] = array();
      $response['data'] = $data;
			$response['data']['message'] = $message;

			if(config('api_messages.'.$message))
       $response['data']['message'] = config('api_messages.'.$message);

			$response['success'] = 1;
			return response()->json($response,$code);
	}

	protected function ErrorResponse($message='Error',$code=400){


		if(config('api_messages.'.$message)){
			$response['error'][]  = config('api_messages.'.$message);
		}
		else {
			$response['error'][] = $message;
		}

		 if(!empty($message))

        $response['data'] = [];
        $response['success'] = 0;
        return response()->json($response, $code);
	}

	protected function manageToken($user_id=null,$device_id=null,$device_type=null,$token=null)
	{
		$result['token']['token'] = '';
		$result['token']['type'] = 'Bearer';
		if(isset($token) && $token!=null)
		{
			$result["token"] = [];

			$userlogindata = Token::where('access_token',$token)->first();

			if(!is_object($userlogindata)){
				$userlogindata = new Token();
			}

			if (isset($user_id) && $user_id!=null )
			$userlogindata->user_id = $user_id;


			if (isset($device_id) ){
				$userlogindata->device_id = $device_id;
			}
			if (isset($device_type))
				$userlogindata->device_type = $device_type;

			//$userlogindata->last_login = date("Y-m-d H:i:s");
			$userlogindata->save();

			$result['token']['token'] = $userlogindata->access_token;
			$result['token']['type'] = 'Bearer';

		}
		return $result["token"];
	}

	protected function LoginResponse($data,$token,$message='Success',$code=200){

      $response['error'] = array();
      $data = ['user'=>$data,'token'=>$token];
      $response['data'] = $data;
      if(!empty($message))
       $response['data']['message'] = $message;
      $response['success'] = 1;
			return response()->json($response,$code);
	}
	protected function updateAccessTockentoNull($token){
		$deletedRows = Token::where('access_token', $token)->delete();
	}


}
?>
