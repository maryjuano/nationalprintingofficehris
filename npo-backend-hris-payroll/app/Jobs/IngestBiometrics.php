<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Log;

class IngestBiometrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $biometrics_data = \App\Biometrics::select(['employeeId', 'attendance', 'type'])->get()->toArray();
        // Sort inner arrays by keys
        $biometrics_data = array_map(
            function ($arr) {
                ksort($arr);
                return ($arr);
            },
            $biometrics_data
        );

        $filenames = scandir('zkteco_logs');
        $filenames = array_filter(
            $filenames,
            function ($s) {
                if (strpos($s, "attendance") !== false) {
                    return true;
                }
                return false;
            }
        );
        $filenames = array_values($filenames);

        $new_data = array();
        // Merge attendance logs
        foreach ($filenames as $filename) {

            $file = file_get_contents("zkteco_logs/{$filename}");
            $data = json_decode($file, true);

            $data = array_map(function ($arr) {
                return array(
                    'employeeId' => $arr['id'],
                    'type' => $arr['type'],
                    'attendance' => $arr['timestamp']
                );
            }, $data);

            $new_data = array_merge($new_data, $data);
        }
        // Sort inner arrays by keys
        $new_data = array_map(function ($arr) {
            ksort($arr);
            return ($arr);
        }, $new_data);

        // json encode inner arrays for diffing
        $new_data = array_map(function ($arr) {
            return json_encode($arr);
        }, $new_data);

        $biometrics_data = array_map(function ($arr) {
            return json_encode($arr);
        }, $biometrics_data);

        $new_entries = array_diff($new_data, $biometrics_data);
        $new_entries = array_map(function ($arr) {
            return json_decode($arr, true);
        }, $new_entries);

        \App\Biometrics::insert($new_entries);
    }
}
