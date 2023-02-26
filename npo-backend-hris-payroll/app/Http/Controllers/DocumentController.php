<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class DocumentController extends Controller
{
    public function add_documents(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $employee->documents = request('documents', null);
        $data = $request->only('attachments')['attachments']['files'];

        // checking if file is already used in a document (using file_location)
        // foreach (request('documents') as $file) {
        //     // TODO
        // }

        foreach ($data as $file) {
            $document = new \App\Document();
            $document->employee_id = $employee->id;
            $document->file_location = $file['file_location'];
            $document->file_type = $file['file_type'];
            $document->file_name = $file['file_name'];
            if (array_key_exists('file_date', $file) && $file['file_date'] != null) $document->file_date = Carbon::parse($file['file_date'])->toDateString();
            $document->file_remarks = $file['file_remarks'];
            $document->save();
        }

        return response()->json(array("data" => $document, "result" => "save"));
    }

    public function read_documents(Request $request, $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\Document::where('employee_id', $employee)
            ->where('is_archived', 1);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = ['file_date', 'file_name', 'created_at'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($result);
    }

    public function list_documents(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \DB::table('documents');
        $data = $query->select('documents.*');
        $query = $data->where('is_archived', '=', 1);

        $ALLOWED_FILTERS = ['file_type', 'created_by', 'file_name'];
        $SEARCH_FIELDS = ['file_type', 'file_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($result);
    }

    public function archive_documents(Request $request, $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \DB::table('documents')
            ->where('id', '=', $employee)
            ->update(array(
                'is_archived' => 0
            ));

        return response()->json(array("document_id" => $employee, "result" => "Archived"));
    }

    //  power upload and document upload
    public function add_documents_power_upload(Request $request, \App\Document $document, $is_new = false)
    {

        // $validator_arr = [

        // ];

        // $validator = Validator::make($request->all(), $validator_arr);

        // if ($validator->fails()) {
        //     return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        // }

        foreach ($request->all() as $data) {
            foreach ($data['files'] as $file) {
                $document = new \App\Document();
                $document->employee_id = $data['employee_id'];
                $document->file_location = $file['file_location'];
                $document->file_type = $file['file_type'];
                $document->file_name = $file['file_name'];
                if (array_key_exists('file_date', $file) && $file['file_date'] != null) $document->file_date = Carbon::parse($file['file_date'])->toDateString();
                $document->file_remarks = $file['file_remarks'];

                $document->save();
            }
        }

        return response()->json($request->all());
    }

    public function create_power_upload(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->add_documents_power_upload($request, new \App\Document(), true);
    }
}
