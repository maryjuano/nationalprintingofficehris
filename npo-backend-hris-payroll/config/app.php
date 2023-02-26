<?php

return [

  /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

  'name' => env('APP_NAME', 'Laravel'),

  /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

  'env' => env('APP_ENV', 'production'),

  /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

  'debug' => env('APP_DEBUG', false),

  /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

  'url' => env('APP_URL', 'http://localhost'),

  'asset_url' => env('ASSET_URL', null),

  'OAUTH_ACCESS_TOKEN_URL' => env('OAUTH_ACCESS_TOKEN_URL', ''),
  'CREATE_USER_URL' => env('CREATE_USER_URL', ''),
  'EDIT_ACTIVATE_USER_URL' => env('EDIT_ACTIVATE_USER_URL', ''),
  'UPDATE_USER_URL' => env('UPDATE_USER_URL', ''),
  'APPROVE_EDIT_REQUEST_URL' => env('APPROVE_EDIT_REQUEST_URL', ''),

  'OAUTH_CLIENT_ID' => env('OAUTH_CLIENT_ID', ''),
  'OAUTH_CLIENT_SECRET' => env('OAUTH_CLIENT_SECRET', ''),

  /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

  'timezone' => 'UTC', //'Asia/Manila',

  /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

  'locale' => 'en',

  /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

  'fallback_locale' => 'en',

  /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

  'faker_locale' => 'en_US',

  /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

  'key' => env('APP_KEY'),

  'cipher' => 'AES-256-CBC',

  /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        Barryvdh\DomPDF\ServiceProvider::class,
        Clockwork\Support\Laravel\ClockworkServiceProvider::class,
        Maatwebsite\Excel\ExcelServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

  'aliases' => [

    'App' => Illuminate\Support\Facades\App::class,
    'Arr' => Illuminate\Support\Arr::class,
    'Artisan' => Illuminate\Support\Facades\Artisan::class,
    'Auth' => Illuminate\Support\Facades\Auth::class,
    'Blade' => Illuminate\Support\Facades\Blade::class,
    'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
    'Bus' => Illuminate\Support\Facades\Bus::class,
    'Cache' => Illuminate\Support\Facades\Cache::class,
    'Config' => Illuminate\Support\Facades\Config::class,
    'Cookie' => Illuminate\Support\Facades\Cookie::class,
    'Crypt' => Illuminate\Support\Facades\Crypt::class,
    'DB' => Illuminate\Support\Facades\DB::class,
    'Eloquent' => Illuminate\Database\Eloquent\Model::class,
    'Event' => Illuminate\Support\Facades\Event::class,
    'File' => Illuminate\Support\Facades\File::class,
    'Gate' => Illuminate\Support\Facades\Gate::class,
    'Hash' => Illuminate\Support\Facades\Hash::class,
    'Lang' => Illuminate\Support\Facades\Lang::class,
    'Log' => Illuminate\Support\Facades\Log::class,
    'Mail' => Illuminate\Support\Facades\Mail::class,
    'Notification' => Illuminate\Support\Facades\Notification::class,
    'Password' => Illuminate\Support\Facades\Password::class,
    'Queue' => Illuminate\Support\Facades\Queue::class,
    'Redirect' => Illuminate\Support\Facades\Redirect::class,
    'Redis' => Illuminate\Support\Facades\Redis::class,
    'Request' => Illuminate\Support\Facades\Request::class,
    'Response' => Illuminate\Support\Facades\Response::class,
    'Route' => Illuminate\Support\Facades\Route::class,
    'Schema' => Illuminate\Support\Facades\Schema::class,
    'Session' => Illuminate\Support\Facades\Session::class,
    'Storage' => Illuminate\Support\Facades\Storage::class,
    'Str' => Illuminate\Support\Str::class,
    'URL' => Illuminate\Support\Facades\URL::class,
    'Validator' => Illuminate\Support\Facades\Validator::class,
    'View' => Illuminate\Support\Facades\View::class,
    'PDF' => Barryvdh\DomPDF\Facade::class,
    'Clockwork' => Clockwork\Support\Laravel\Facade::class,
    'Excel' => Maatwebsite\Excel\Facades\Excel::class,
  ],

  'payroll' => [
    'no_dtr_needed' => [4],
    'dtr_needed' => [1, 2, 5],
    'flexi' => [3]
  ],

  'payslip' => [
    'employee_name' => 'C8',
    'net_taxable_pay' => 'F9',
    'monthly_rate' => 'C7',
    'dtr_deductions' => [
      'undertime' => 'C10',
      'late' => 'C11',
      'absence' => 'C12'
    ],

    'mandatories' => [
      'tax' => 'F13',
      'gsis' => 'F10',
      'pagibig' => 'F12',
      'philhealth' => 'F11'
    ],
    'additional_pay' => [
      'holiday' => 'C15',
      'night' => 'C18',
      'overtime' => 'C17',
      'rest' => 'C16'
    ],


    'loans' => [
      '1' => 'F16'
    ]

  ],

  'alphalist' => [
    0 => [
      'filename' => './forms/1604CF_74.xls',
      'active_sheet' => 'Schedule74',
      'filetype' => 'Xls',
      'description' => 'With previous employers within the year',
      'tin' => 'A',
      'last_name' => 'C',
      'first_name' => 'D',
      'middle_name' => 'E',
      'address' => 'F',
      'zipcode' => 'G',
      'birthday' => 'H',
      'tel' => 'I',
      'deMinimis' => [
        'adjustments' => [4]
      ],
      'net_sss_philhealth_gsis_pagibig' => 'U',
      'net_withheld' => 'AH',
      'net_taxable_pay' => 'X',



    ],
    1 => [
      'filename' => './forms/1604CF_71.xls',
      'active_sheet' => 'Schedule71',
      'filetype' => 'Xls',
      'description' => 'Employees terminated before Dec 31',
      'tin' => 'A',
      'last_name' => 'C',
      'first_name' => 'D',
      'middle_name' => 'E',
      'address' => 'F',
      'zipcode' => 'G',
      'birthday' => 'H',
      'tel' => 'I',
      'deMinimis' => [
        'adjustments' => [4]
      ],
      'mandatory' => [
        'location' => 'U',
        'payroll_codes' =>
        [
          "philhealth.employee_share",
          "pagibig.employee_share,",
          "gsis.employee_share"
        ],
      ],
      'net_taxable_pay' => 'L',
      'net_sss_hdmf_phil' => 'O',
      'net_withheld' => 'Y',

    ],
    2 => [
      'filename' => './forms/1604CF_72.xls',
      'active_sheet' => 'Schedule72',
      'filetype' => 'Xls',
      'description' => 'Exempt from withholding tax but subject to Income tax',
      'tin' => 'A',
      'last_name' => 'C',
      'first_name' => 'D',
      'middle_name' => 'E',
      'address' => 'F',
      'zipcode' => 'G',
      'birthday' => 'H',
      'tel' => 'I',
      'deMinimis' => [
        'adjustments' => [4]
      ],
      'mandatory' => [
        'location' => 'U',
        'payroll_codes' =>
        [
          "philhealth.employee_share",
          "pagibig.employee_share,",
          "gsis.employee_share"
        ],
      ],
      'sala'
    ],
    3 => [
      'filename' => './forms/1604CF_73.xls',
      'active_sheet' => 'Schedule73',
      'filetype' => 'Xls',
      'description' => 'Regular alphalist',
      'tin' => 'A',
      'last_name' => 'C',
      'first_name' => 'D',
      'middle_name' => 'E',
      'address' => 'F',
      'zipcode' => 'G',
      'birthday' => 'H',
      'tel' => 'I',
      'deMinimis' => [
        'adjustments' => [4]
      ],
      'mandatory' => [
        'location' => 'U',
        'payroll_codes' =>
        [
          "philhealth.employee_share",
          "pagibig.employee_share,",
          "gsis.employee_share"
        ],
      ],
      'sala'
    ]
  ]
];

/*

    {"employee":"3","monthlyRate":34130,"dailyRate":1551.36,
        "dtrs":[
            {
                "date":"2019-11-01",
                "basicPay":
                    {
                        "late":"00:00:00",
                        "lateDeduction":0,
                        "undertime":"00:00:00",
                        "undertimeDeduction":0,
                        "absence":0,
                        "absenceDeduction":0,
                        "basicPay":1551.36
                    },
                "additionalPay":
                    {
                        "holidayDuration":"10:45:10",
                        "holidayPay":4170.36,
                        "nightDiffDuration":"00:35:11",
                        "nightDiffPay":22.74,
                        "otDuration":0,
                        "otPay":0,
                        "restDayDuration":"00:00:00",
                        "restDayPay":0
                    }
                },
                {"date":"2019-11-02","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":0,"absenceDeduction":0,"basicPay":0},"additionalPay":{"holidayDuration":"00:00:00","holidayPay":0,"nightDiffDuration":"00:00:00","nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":"06:35:10","restDayPay":1660.33}},{"date":"2019-11-03","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":0,"absenceDeduction":0,"basicPay":0},"additionalPay":{"holidayDuration":"00:00:00","holidayPay":0,"nightDiffDuration":"00:00:00","nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":"03:55:00","restDayPay":987.38}},{"date":"2019-11-04","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":1,"absenceDeduction":1551.36,"basicPay":0},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":0,"restDayPay":0}},{"date":"2019-11-05","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":0,"absenceDeduction":0,"basicPay":1551.36},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":0,"restDayPay":0}},{"date":"2019-11-06","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":0.5,"absenceDeduction":775.68,"basicPay":775.68},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":0,"restDayPay":0}},{"date":"2019-11-07","basicPay":{"late":"01:09:03","lateDeduction":223.17,"undertime":"00:00:00","undertimeDeduction":0,"absence":0,"absenceDeduction":0,"basicPay":1328.19},"additionalPay":{"holidayDuration":"00:00:00","holidayPay":0,"nightDiffDuration":"04:35:11","nightDiffPay":88.94,"otDuration":"03:12:50","otPay":186.97,"restDayDuration":"00:00:00","restDayPay":0}},{"date":"2019-11-08","basicPay":{"late":"00:09:04","lateDeduction":29.3,"undertime":"01:25:40","undertimeDeduction":276.87,"absence":0,"absenceDeduction":0,"basicPay":1245.19},"additionalPay":{"holidayDuration":"00:00:00","holidayPay":0,"nightDiffDuration":"00:00:00","nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":"00:00:00","restDayPay":0}},{"date":"2019-11-09","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":0,"absenceDeduction":0,"basicPay":0},"additionalPay":{"holidayDuration":"00:00:00","holidayPay":0,"nightDiffDuration":"00:00:00","nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":"02:40:10","restDayPay":672.96}},{"date":"2019-11-10","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":0,"absenceDeduction":0,"basicPay":0},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":"00:00:00","restDayPay":0}},{"date":"2019-11-11","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":1,"absenceDeduction":1551.36,"basicPay":0},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":0,"restDayPay":0}},{"date":"2019-11-12","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":1,"absenceDeduction":1551.36,"basicPay":0},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":0,"restDayPay":0}},{"date":"2019-11-13","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":1,"absenceDeduction":1551.36,"basicPay":0},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":0,"restDayPay":0}},{"date":"2019-11-14","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":1,"absenceDeduction":1551.36,"basicPay":0},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":0,"restDayPay":0}},{"date":"2019-11-15","basicPay":{"late":"00:00:00","lateDeduction":0,"undertime":"00:00:00","undertimeDeduction":0,"absence":1,"absenceDeduction":1551.36,"basicPay":0},"additionalPay":{"holidayDuration":0,"holidayPay":0,"nightDiffDuration":0,"nightDiffPay":0,"otDuration":0,"otPay":0,"restDayDuration":0,"restDayPay":0}}],
                "additionalPay":{"holiday":4170.36,"overtime":186.97,"rest":3320.67,"night":111.68},
                "dtrDeduction":
                    {"late":252.47,"undertime":276.87,"absence":10084},
                "mandatoryDeduction":{"tax":2699.15}}

*/
