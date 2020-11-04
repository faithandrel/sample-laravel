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

class ChatRequest extends Notification implements FCMNotificationInterface
{
    use Queueable;

    private $user, $chatbox_id, $match_id, $use_match_text;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, $chatboxId, $matchId = 0, $useMatchText = false)
    {
        $this->user       = $user;
        $this->chatbox_id = $chatboxId;
        $this->match_id   = $matchId;
        $this->use_match_text = $useMatchText;
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
            'user_id'    => $this->user->id,
            'username'   => $this->user->name,
            'text'       => $this->getActionText(),
            'match_id'   => $this->match_id,
            'use_match_text' => $this->use_match_text
        ];
    }

    /**
     * Returns text for the notification 
     * @return string
     */
    public function getActionText() 
    {   
        if($this->match_id || $this->use_match_text) {
            if(empty($this->user->description)) {
                return 'You have a new match! Click this for more.';
            }

            return  'You have a new match! "'.$this->user->description_excerpt.'..."';
        }

        return $this->user->name.' is requesting to chat with you.';
    }

    /**
     * Prepares fcm notification
     * @param type $notification 
     * @return type
     */
    public static function buildForFCM($notification)
    {
        $notificationBuilder = new PayloadNotificationBuilder();

        $title = 'New Chat Request';

        $notificationBuilder->setTitle($title)
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
