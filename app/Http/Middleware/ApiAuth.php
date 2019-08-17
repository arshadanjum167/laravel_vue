<?php

namespace App\Http\Middleware;

define('AUTHTYPE','Bearer');
use Closure;
use App\Models\Token;
use App\Models\User;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
     public function handle($request, Closure $next){

       $flag = 0;
       if($request->header('Authorization')){
         if(isset($request->header('Authorization')[0]) && $request->header('Authorization')[0]!=null &&
            isset($request->header('Authorization')[1]) && $request->header('Authorization')[1]!=null
         )
         {
           if(explode(' ',$request->header('Authorization'))[0] == AUTHTYPE)
           {
              $token = last(explode(' ',$request->header('Authorization')));

              $token_data = Token::where(['access_token'=>$token])->first();

                if($token_data)
                {
                  $flag = 1;
                  if($token_data->user_id)
                  {
                      $user_data = User::find($token_data->user_id);
                      if($user_data)
                      {
                        $request->user = ['id'=>$user_data->id,'type'=>$user_data->actor,'access_token'=>$token,'is_deleted'=>$user_data->is_deleted];
                      }
                      else {
                        $request->user = ['id'=>0,'type'=>0,'access_token'=>$token,'is_deleted'=>1];
                      }
                  }
                  else
                  {
                    $request->user = ['id'=>0,'type'=>0,'access_token'=>$token,'is_deleted'=>1];
                  }
                }
            }
         }
       }
       if($flag==1)
       {
         return $next($request);
       }
       else
       {
         $response['error'][] = 'Unauthorized';
         $response['data'] = [];
         $response['success'] = 0;
         return response()->json($response, $code=401);
       }
     }
}
