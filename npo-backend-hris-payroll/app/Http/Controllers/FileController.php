<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Carbon\Carbon;
use Cocur\Slugify\Slugify;
use Illuminate\Support\Facades\Storage;


class FileController extends Controller
{
    protected $slugify;
    public function __construct()
    {
        parent::__construct();
        $this->slugify = new Slugify();
    }

    public function upload_file(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $date = Carbon::now();
        $filename = $this->slugify->slugify(request()->file('file')->getClientOriginalName())
            . '_' . time();
        $location = request()->file('file')->storeAs(
            'uploads/' . $date->toDateString(),
            $filename .  '.' . request()->file('file')->getClientOriginalExtension()
        );
        return response()->json(array(
            'file_location' => $location,
            'file_name' => $filename,
            'file_type' => request()->file('file')->getClientOriginalExtension()
        ));
    }

    public function read_file(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => 'validation failed',
                'messages' => $validator->errors()->all()
            ], 404);
        }
        $file_urls = explode('/', $request->input('location'));
        $filename = $file_urls[count($file_urls) - 1];

        // $headers = ['Content-Disposition: attachment; filename={$filename}'];

        return response()->download(storage_path('app/' . request('location')));
    }
}
