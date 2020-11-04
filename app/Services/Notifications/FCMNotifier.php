<?php 

namespace App\Services\Notifications;

use FCM;
use Log;
use Carbon\Carbon;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use App\Models\User;
use App\Models\Item;

class FCMNotifier {

	public static function notifyUsers() {
		$instance = new static;
		$allUsers = User::all();

		foreach ($allUsers as $user) {
			if( $instance::checkLastActive($user) &&
				$instance::checkLastNotified($user) && 
				!is_null($user->device_token) ) {
					$instance::notifyUser($user);					
			}
		}
	}

	public static function notifyUser($user) {
		$instance = new static;

		$notification = $user->latestUnreadNotification();

		if(!is_null($notification)) {
			$resultNotification = $instance::send($notification, $user->device_token);
			$user->last_notified = Carbon::now();
			$user->save();
		}

		return $notification;
	}

	public static function send($notification, $token) {		
		$fcmNotification = forward_static_call([$notification->type, 'buildForFCM'], $notification);

		$result = FCM::sendTo($token, null, $fcmNotification['notification'], $fcmNotification['data']);

		return $result;
	}

	public static function checkLastActive($user) {
		$now 		= Carbon::now();
		$lastActive = $user->last_active;

		$minutesPassed = $now->diffInMinutes($lastActive);
		//TODO: add one month constraint, user should be active for at least one month
		return $minutesPassed > config('fcm.lastActiveMinutes') ? true : false;
	}

	public static function checkLastNotified($user) {
		$now 		  = Carbon::now();
		$lastNotified = $user->last_notified;

		if(is_null($lastNotified)) {
			return true;
		}

		$minutesPassed = $now->diffInMinutes($lastNotified);

		return $minutesPassed > config('fcm.lastNotifiedMinutes') ? true : false;
	}

}
