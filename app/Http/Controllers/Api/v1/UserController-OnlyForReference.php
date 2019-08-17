<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Base\ApiController;
use App\Models\Token;
use App\Models\Users;
use App\Models\UserPreference;
use App\Models\UserFriend;
use App\Models\BlockedUsers;
use App\Models\Post;
use App\Models\Event;
use App\Mail\ContactAdmin;
use Illuminate\Support\Facades\Mail;

use Hash;
use ApiFunction;
use CommonFunction;
use GeneralApiData;
use UserData;

class UseroldController extends ApiController
{

  public function notificationSetting(Request $request)
  {
    if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    extract($_POST);
    $user_id = $request->user['id'];

    $preference = UserPreference::where(['user_id'=>$user_id])->where('is_deleted',false)->first();

    if(!$preference)
    {
      $preference = new UserPreference;
    }
    $preference->user_id = $user_id;

    if(isset($occation_event) && $occation_event!=null){
      $preference->occation_event = $occation_event;
    }

    if(isset($chat) && $chat!=null){
      $preference->chat = $chat;
    }

    if(isset($payment_transfer) && $payment_transfer!=null){
      $preference->payment_transfer = $payment_transfer;
    }

    if(isset($post_comment) && $post_comment!=null){
      $preference->post_comment = $post_comment;
    }

    if(isset($post_like) && $post_like!=null){
      $preference->post_like = $post_like;
    }

    if(isset($friend_request) && $friend_request!=null){
      $preference->friend_request = $friend_request;
    }

    $preference->i_by = $user_id;
    $preference->i_date = time();

    if($preference->save()){
      $result['notification_settings'] = ApiFunction::userNotificationPreference($user_id);
      return $this->SuccessResponse($result,'preference_updated');
    }
    else {
      return $this->ErrorResponse('error_in_save',200);
    }

  }

  public function getProfile(Request $request)
  {
    if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    extract($_POST);
    $user_id = $request->user['id'];

    if(isset($requested_user_id) && $requested_user_id!=null){
      $requested_user_id = $requested_user_id;
    }
    else {
      $requested_user_id = $user_id;
    }

    $user = Users::find($requested_user_id);

    if($user){
      $result = ApiFunction::userResponse($user);
      $result['user']['user_friend_status'] = UserData::checkFriend($user_id,$requested_user_id);
      $result['user']['is_blocked'] =$user->blockedUsers()->where('from_id',$user_id)->count();
      return $this->SuccessResponse($result);
    }
    else {
      return $this->ErrorResponse('error_user_not_found',200);
    }
  }

  public function friendUnfriendUser(Request $request)
  {
    $request->validate([
      'requested_user_id' => 'required',
      'status' => 'required',  // 1 = friend, 2= unfriend
    ]);

    if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    extract($_POST);

    $user_id = $request->user['id'];

    if(!in_array($status,[1,2])){
      return $this->ErrorResponse('bad_request',200);
    }

    if($user_id == $requested_user_id){
      return $this->ErrorResponse('invalid_user_id',200);
    }

    $requested_user = Users::find($requested_user_id);
    if(!$requested_user){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    $friend = UserFriend::where('is_deleted',0)
              ->where(function($query) use($user_id,$requested_user_id){
                $query->where(['from_id'=>$user_id,'to_id'=>$requested_user_id])
                      ->orWhere(['from_id'=>$requested_user_id,'to_id'=>$user_id]);
              })->first();

    if($status == 1 && !$friend){   // friend
      $friend = new UserFriend;
      $friend->from_id = $user_id;
      $friend->to_id = $requested_user_id;
      $friend->status = UserFriend::REQUESTED;
      $friend->i_by = $user_id;
      $friend->i_date = time();
      $friend->save();
    }
    else if($status == 2 && $friend){ //   unfriend
        $friend->delete();
    }

    $result['user_friend_status'] = UserData::checkFriend($user_id,$requested_user_id);
    return $this->SuccessResponse($result);
  }

  public function blockUnblockUser(Request $request)
  {
    $request->validate([
      'requested_user_id' => 'required',
      'status' => 'required',  // 1 = block, 2= unblock
    ]);

    if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    extract($_POST);

    $user_id = $request->user['id'];

    if(!in_array($status,[1,2])){
      return $this->ErrorResponse('bad_request',200);
    }

    if($user_id == $requested_user_id){
      return $this->ErrorResponse('invalid_user_id',200);
    }

    $requested_user = Users::find($requested_user_id);
    if(!$requested_user){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    $user_block = BlockedUsers::where('is_deleted',0)
                            ->where(['from_id'=>$user_id,'to_id'=>$requested_user_id])
                            ->first();

    if($status == 1 && !$user_block){   // block
      $user_block = new BlockedUsers;
      $user_block->from_id = $user_id;
      $user_block->to_id = $requested_user_id;
      $user_block->is_blocked = 1;
      $user_block->i_by = $user_id;
      $user_block->i_date = time();
      $user_block->save();
    }
    else if($status == 2 && $user_block){ //   unblock
        $user_block->delete();
    }

    return $this->SuccessResponse([]);
  }

  public function acceptRejectFriendRequest(Request $request)
  {
    $request->validate([
      'requested_user_id' => 'required',
      'status' => 'required',  // 1 = accept, 2= reject
    ]);

    if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    extract($_POST);

    $user_id = $request->user['id'];

    if(!in_array($status,[1,2])){
      return $this->ErrorResponse('bad_request',200);
    }

    if($user_id == $requested_user_id){
      return $this->ErrorResponse('invalid_user_id',200);
    }

    $requested_user = Users::find($requested_user_id);
    if(!$requested_user){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    $friend = UserFriend::where('is_deleted',0)
              ->where(['from_id'=>$requested_user_id,'to_id'=>$user_id])
              ->first();

    if(!$friend){
      return $this->ErrorResponse('friend_request_not_found',200);
    }

    if($status == 1 ){
      $friend->status = UserFriend::FRIEND;
      $friend->save();
    }
    else {
      $friend->delete();
    }

    return $this->SuccessResponse([]);
  }

  public function userList(Request $request)
  {
    $request->validate([
      'start' => 'required',
      'limit' => 'required',
      'type' => 'required',
      //  1 - Blocked User List, 2 - Friends list, 3-  Post Liked user List, 4-  Get Users to Invite in Event
      // 5-  Event Attending User List, 6-  Event Invited User List, 7-  App Users from contact numbers"
    ]);

    if(!($request->user['id'] !=0 && $request->user['is_deleted'] !=1)){
      return $this->ErrorResponse('error_user_not_found',200);
    }

    extract($_POST);
    $user_id = $request->user['id'];

    if(!in_array($type,[1,2,3,4,5,6,7])){
      return $this->ErrorResponse('bad_request',200);
    }

    $start=(isset($start) && $start!='')?$start:0;
    $limit=(isset($limit) && $limit!='')?$limit:10;

    switch ($type) {
      case 1:
        $result = UserData::getBlockedUserList($user_id,$start,$limit);
        break;
      case 2:
        $result = UserData::getUsersFriendList($user_id,$start,$limit);
        break;
      case 3:
        $request->validate([
          'post_id' => 'required',
        ]);
        $post = Post::find($post_id);
        if(!$post){
          return $this->ErrorResponse('post_not_found',200);
        }
        $result = UserData::getPostLikedUsersList($post,$user_id,$start,$limit);
        break;
      case 5:
        $request->validate([
          'event_id' => 'required',
        ]);
        $event = Event::find($event_id);
        if(!$event){
          return $this->ErrorResponse('event_not_found',200);
        }
        $result = UserData::getEventAttendingUserList($event,$user_id,$start,$limit);
      break;
      case 6:
        $request->validate([
          'event_id' => 'required',
        ]);
        $event = Event::find($event_id);
        if(!$event){
          return $this->ErrorResponse('event_not_found',200);
        }
        $result = UserData::getEventInvitedUserList($event,$user_id,$start,$limit);
      break;
      case 7:
        $request->validate([
          'contact_numbers' => 'required',
        ]);
        $result = UserData::getUsersfromContactList($contact_numbers,$user_id,$start,$limit);
      break;
      default:
        $result = UserData::getList([]);
        break;
    }
    return $this->SuccessResponse($result);
  }
}
