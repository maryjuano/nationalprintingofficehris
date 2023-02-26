<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{

    public const NOTIFICATION_STATUS_UNREAD = 0;
    public const NOTIFICATION_STATUS_READ = 1;
    public const NOTIFICATION_SECTION_MAIN = 'Main';
    public const NOTIFICATION_SECTION_SELF_SERVICE = 'Self Service';

    public const NOTIFICATION_SOURCE_LEAVE = 'Leave';
    public const NOTIFICATION_SOURCE_DTR = 'Daily Time Record';
    public const NOTIFICATION_SOURCE_OVERTIME = 'Overtime';
    public const NOTIFICATION_SOURCE_RAO = 'Authority to Overtime';
    public const NOTIFICATION_SOURCE_DOCUMENT = 'Document';
    public const NOTIFICATION_SOURCE_INFO = 'Information';
    public const NOTIFICATION_SOURCE_LOAN = 'Loan';
    public const NOTIFICATION_SOURCE_CONTRIBUTION = 'Contribution';
    public const NOTIFICATION_SOURCE_CONFIGURATION = 'Configuration';
    public const NOTIFICATION_SOURCE_NOSI = 'NOSI';
    public const NOTIFICATION_SOURCE_NOSA = 'NOSA';
    public const NOTIFICATION_SOURCE_TRANCHE = 'TRANCHE';
    # TODO: add more notification sources here.

    protected $table = 'notifications';
    protected $fillable = ['id',
    'user_id',
    'message',
    'section',
    'source',
    'source_id',
    'payload',
    'status',
    'created_at',
    'updated_at'];

    protected $casts = [
        'payload' => 'array'
    ];

    public static function create_hr_notification($target, $message, $source, $source_id, $payload) {
        // find the corresponding employees
        $users = \App\User::all();
        foreach ($users as $user) {
            if (isset($user->employee_details)) {
                // Log::debug($user);
                if (sizeof(array_intersect($target, $user->employee_details->system_information->privileges[0])) > 0) {
                    Notification::_create_notification(self::NOTIFICATION_SECTION_MAIN,$user->id, $message, $source, $source_id, $payload);
                }
            }
        }
    }

    public static function create_user_notification($user_id, $message, $source, $source_id, $payload) {
        Notification::_create_notification(self::NOTIFICATION_SECTION_SELF_SERVICE, $user_id, $message, $source, $source_id, $payload);
    }

    public static function _create_notification($section, $user_id, $message, $source, $source_id, $payload) {
        $notification = new Notification();
        $notification->section = $section;
        $notification->user_id = $user_id;
        $notification->message = $message;
        $notification->source = $source;
        $notification->source_id = $source_id;
        $notification->payload = $payload;
        $notification->save();
    }
}
