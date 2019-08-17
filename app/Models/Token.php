<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
	
	
    protected $table = 'user_devices';
	public $timestamps = false;
    protected $fillable = [ 'mode', 'user_id', 'access_token' ];
	protected $updated_at,$created_at;
	public function user() {
		return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
}
