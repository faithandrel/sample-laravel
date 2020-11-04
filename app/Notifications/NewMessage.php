<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Message\PayloadDataBuilder;

use App\Models\User;
use App\Services\Notifications\FCMNotificationInterface;

class NewMessage extends Notification implements FCMNotificationInterface
{
    use Queueable;

    private $user, $chatbox_id, $message_id;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, $chatboxId, $messageId)
    {
        $this->user       = $user;
        $this->chatbox_id = $chatboxId;
        $this->message_id = $messageId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'chatbox_id' => $this->chatbox_id,
            'message_id' => $this->message_id,
            'user_id'    => $this->user->id,
            'username'   => $this->user->name,
            'text'       => $this->getActionText(),
        ];
    }

    /**
     * Returns text for the notification 
     * @return string
     */
    public function getActionText() 
    {   
        return 'You have new messages from '.$this->user->name;
    }

    /**
     * Prepares fcm notification
     * @param type $notification 
     * @return type
     */
    public static function buildForFCM($notification)
    {
        $notificationBuilder = new PayloadNotificationBuilder();

        $notificationBuilder->setTitle('New Message')
                  ->setBody($notification->data['text'])
                  ->setSound('default')
                  ->setTag($notification->id);

        $fcmNotification = $notificationBuilder->build();

        $dataBuilder = new PayloadDataBuilder();

        $notificationType = array_search($notification->type, config('notifications.types'));

        $dataBuilder->addData([
            'type' => 'text',
            'notification_type' => $notificationType,
            'chatbox' => $notification->data['chatbox_id'],
            'tag'  => $notification->id,
            'text' => $notification->data['text'],
         ]);

        $data   = $dataBuilder->build();

        return ['notification' => $fcmNotification, 'data' => $data];

    }
}

/**
 * As you can see this extends Laravel's Notification class 
 * (Illuminate\Notifications\Notification)
 * 
 * Also it implements an interface - FCMNotificationInterface
 * See - Services/Notifications/FCMNotificationInterface.php
 * 
 * This is to ensure that all notifications that will be pushed to mobile 
 * will implement the functions to prepare data for Firebase  
 * See also Services/Notifications/ChatRequest.php
 * 
 * Next, please see Services/Notifications/FCMNotifier.php
 */
