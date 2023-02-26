<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function create_or_update(Request $request, \App\DocumentType $document_type, $is_new = false)
    {
        $validator_arr = [
            'document_type_name' => 'required|unique:document_types',
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails() and $is_new) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $document_type->fill(
            $request->only(['document_type_name', 'sub_types'])
        );

        \DB::beginTransaction();
        try {
            if ($is_new) {
                $document_type->is_active = true;
                $document_type->save();
            } else {
                $document_type->save();
            }

            $this->log_user_action(
                Carbon::parse($document_type->created_at)->toDateString(),
                Carbon::parse($document_type->created_at)->toTimeString(),
                $this->me->id,
                $this->me->name,
                "Created " . $document_type->document_type_name . " as Document Type",
                "HR & Payroll"
            );

            \DB::commit();
            return response()->json($document_type);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function enrich($_tmodel)
    {
        // TODO
        return $_tmodel;
    }

    public function create(Request $request)
    {
        // $unauthorized = $this->is_not_authorized(['create_document_type']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        return $this->create_or_update($request, new \App\DocumentType(), true);
    }

    public function update(Request $request, \App\DocumentType $document_type)
    {
        // $unauthorized = $this->is_not_authorized(['edit_document_type']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        return $this->create_or_update($request, $document_type);
    }

    public function list(Request $request)
    {
        // $unauthorized = $this->is_not_authorized();
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        $query = \App\DocumentType::select('*');

        // filtering
        $ALLOWED_FILTERS = ['document_type_name', 'is_active'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = ['is_active'];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function set_is_active(Request $request, $document_type_id)
    {
        // $unauthorized = $this->is_not_authorized(['toggle_document_type']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }

        $validator_arr = [
            'is_active' => 'required'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $document_type = \App\DocumentType::withCount('members')->find($document_type_id);

        \DB::beginTransaction();
        try {
            $document_type->is_active = request('is_active');
            $document_type->save();
            $this->log_user_action(
                Carbon::now(),
                Carbon::now(),
                $this->me->id,
                $this->me->name,
                $document_type->is_active == true ? "Activated document type " . $document_type->document_type_name : "Deactivated document type " . $document_type->document_type_name,
                "HR & Payroll"
            );
            \DB::commit();
            return response()->json([
                'document_type_name' => $document_type->document_type_name,
                'is_active' => $document_type->is_active,
                'id' => $document_type->id
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
