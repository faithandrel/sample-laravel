<?php

namespace App\Models;

use DB;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\NewMatch;
use App\Notifications\NewMessage;
use App\Services\Notifications\Notifiable;
use App\Services\Notifications\FCMNotifier;
use App\Services\Location\Geolocation;

class User extends Authenticatable
{
    use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = [
        'last_active',
        'last_notified',
    ];


    //Accessors
    public function getDescriptionExcerptAttribute() {
        return substr(strip_tags($this->description),0,72);
    }

    public function sendLatestNotification() {
        $notification = $this->latestUnreadNotification();
    
        if($notification && !empty($this->device_token)) {
            $result = FCMNotifier::send($notification, $this->device_token);
            return $result;
        }

        return false;
    }

    public function saveMessageNotification($fromUserId, $chatboxId, $messageId) {     
        $instance = new static; 
        $fromUser = $instance::find($fromUserId);

        $notification = $this->notify(new NewMessage($fromUser, $chatboxId, $messageId));
    }

    //Relationships
    public function items() {
        return $this->hasMany(Item::class);
    }

    public function locations() {
        return $this->morphMany(Location::class, 'locationable');
    }
}
