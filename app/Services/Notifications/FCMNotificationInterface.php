<?php 

namespace App\Services\Notifications;

use App\Models\Item;
use App\Models\User;

interface FCMNotificationInterface { 

	public static function buildForFCM($notification);

	public function getActionText();

}