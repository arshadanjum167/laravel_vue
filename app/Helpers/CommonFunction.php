<?php
namespace App\Helpers;

use Cookie;
use Illuminate\Support\Facades\Storage;

use App\Models\User;

use Illuminate\Support\Facades\Mail;

use App\Models\Token;



class CommonFunction
{
  public static function rememberUser($request)
  {
    $v1 = $request->input('email');
    $v2 = $request->input('password');

    $no = rand(1,9);

    for($i=1;$i<=$no;$i++){
        $v1 = base64_encode($v1);
        $v2 = base64_encode($v2);
    }

    Cookie::queue(Cookie::make('email', $v1));
    Cookie::queue(Cookie::make('password', $v2));
    Cookie::queue(Cookie::make('turns', $no));
  }

  public static function removeRememberCookie()
  {
    Cookie::queue(Cookie::forget('email'));
    Cookie::queue(Cookie::forget('password'));
    Cookie::queue(Cookie::forget('turns'));
  }
  public static function getRememberUserData()
  {
    $email='';
    $password='';
    $no = 0;
    // get the cookie value
    if( Cookie::has('email') && Cookie::has('password') && Cookie::has('turns') )
    {
      $email = Cookie::get('email');
      $password = Cookie::get('password');
      $no = Cookie::get('turns');
    }

    for($i=1;$i<=$no;$i++){
    	$email = base64_decode($email);
    	$password = base64_decode($password);

    }
    return ['email'=>$email ,'password' => $password ];
  }

  //for uploding image on s3
  public static function uploadImageInS3bucket($file,$imageName)
  {
        $s3 = \Storage::disk('s3');
        $s3->put($imageName, file_get_contents($file ), 'public');
        $image_name = \Storage::disk('s3')->url($imageName);
        return $image_name;
  }
  //for uploding image on s3
  
  public static function generatepassword()
  {
    return '123456';
  }

  



   
}
