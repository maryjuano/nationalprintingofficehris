<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;

class TaxController extends Controller
{

    /**
     * Business rules and DB design logic:
     * #1. entries with the same class(effectivity date)
     *     form a tax table
     * #2. class(effectivity date) should be a unique date
     * #3. tax steps(lowerLimit - upperLimit) should never
     *     overlap against other tax steps
     * #4. isActive indicates whether a current payrun
     *     is using a tax table(composed of tax entries)
     *     or has been used in a previous payrun. This
     *     means that it can't be directly modified,
     *     instead, changes made should be on a copy
     *     with a new date
     */

    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['create_tax_table']);
        if ($unauthorized) {
            return $unauthorized;
        }
        /**
         * post-data structure
         *{
         *  class: "2020-01-29 00:00:00",
         *  table_entries: [
         *      {
         *          constant: 2500,
         *          percentage: 13,
         *          upperLimit: 60000,
         *          lowerLimit: 45000
         *      }
         *  ]
         *}
         */
        $validation_requirements = [
            // #2
            'class' => 'required|unique:tax',
            'table_entries' => 'required|array',
            'table_entries.*.constant' => 'required|numeric',
            'table_entries.*.percentage' => 'required|numeric',
            'table_entries.*.upperLimit' => 'required|numeric',
            'table_entries.*.lowerLimit' => 'required|numeric',
        ];
        $validation = Validator::make($request->all(), $validation_requirements);
        if ($validation->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validation->errors()->all()], 400);
        }

        $class = Carbon::parse($request->input('class'));
        $table_entries = $request->input('table_entries');

        // check if tax table exists
        $tax_table_entries_query = \DB::table('tax')
            ->where('class', '=', $class)
            ->select('constant', 'percentage', 'upperLimit', 'lowerLimit', 'isActive')
            ->get();
        if (count($tax_table_entries_query) > 0) {
            return response()->json(['error' => 'effectivity date already exists', 'messages' => 'effectivity date already exists'], 404);
        }
        #3
        /**
         * sort tables by their lowerLimit, to ensure that the table is arranged
         * from lowest to highest
         */
        usort($table_entries, function ($a, $b) {
            return $a['lowerLimit'] - $b['lowerLimit'];
        });

        $previousValueUpperLimit = null;
        foreach ($table_entries as $table_entry) {
            // ensure that the current lowerLimit is less than its upperLimit
            if ($table_entry['lowerLimit'] > $table_entry['upperLimit']) {
                return response()->json([
                    'error' => 'Invalid request.',
                    'messages' => ['Please make sure that all your tax table
                    entry\'s minimum range is lower than it\'s maximum range.',],
                ], 442);
            }
            /**
             * check that the upperLimit of the current tax table entry
             * is not greater than or equal to the lowerLimit of the
             * next tax table entry
             */
            if ($table_entry['lowerLimit'] >= $previousValueUpperLimit) {
                $previousValueUpperLimit = $table_entry['upperLimit'];
            } else {
                return response()->json([
                    'error' => 'Invalid request.',
                    'messages' => ['Please make sure your tax table\'s ranges do not overlap.'],
                ], 442);
            }
        }

        \DB::beginTransaction();
        try {
            foreach ($table_entries as $table_entry) {
                $new_tax_table_entry = new \App\Tax();
                $new_tax_table_entry->class = $class;
                $new_tax_table_entry->constant = $table_entry['constant'];
                $new_tax_table_entry->percentage = $table_entry['percentage'];
                $new_tax_table_entry->lowerLimit = $table_entry['lowerLimit'];
                $new_tax_table_entry->upperLimit = $table_entry['upperLimit'];
                /**
                 * is_active is used to know whether the current tax table
                 * is being used for an ongoing payrun
                 *
                 * default should always be 0 since payroll should be the one
                 * activating this
                 */
                // #4
                $new_tax_table_entry->isActive = 0;

                $new_tax_table_entry->save();
            }
        } catch (\Exception $exception) {
            \DB::rollback();
            return response()->json(
                ['error' => 'Internal Server Error', 'messages' => ['Server Error', $exception->getMessage()]],
                500
            );
        }
        \DB::commit();

        return response()->json(["result" => "Tax Table added successfully!"]);
    }

    public function update(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['create_tax_table']);
        if ($unauthorized) {
            return $unauthorized;
        }

        /**
         * 1. tax table can be edited as long as it hasn't \
         *    been used for a payrun(active = 0)
         * 2. if it's been used, create a new copy,
         *    require a new effectivity date/class
         * 3. a tax table that's being used currently for a
         *    payrun(active=1) can be edited, by means of
         *    creating a new instance with a new date
         * 4. (additional rule in edit) old class should remain
         *    in record if the new class is not equal to old class
         *    means di nagchange ng effectivity date
         *    (maooverride lang yung data pag same date pero pag di same
         *      need to create new entry for that class )
         *    and if the new class is already existing throw an error
         *
         *
         * once a tax table has been labeled(active),
         * it can't be directly edited anymore,
         * you need to create a new instance
         */
        $validation_requirements = [
            // #2
            // effectivity date of table's to be deleted
            'old_class' => 'required',
            'new_class' => 'required',
            // new table values
            'table_entries' => 'required|array',
            'table_entries.*.constant' => 'required|numeric',
            'table_entries.*.percentage' => 'required|numeric',
            'table_entries.*.upperLimit' => 'required|numeric',
            'table_entries.*.lowerLimit' => 'required|numeric',
        ];
        $validation = Validator::make($request->all(), $validation_requirements);
        if ($validation->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validation->errors()->all()], 400);
        }

        // check if tax table exists
        $old_class = Carbon::parse($request->input('old_class'));
        $tax_table_entries_query = \DB::table('tax')
            ->where('class', '=', $old_class)
            ->select('constant', 'percentage', 'upperLimit', 'lowerLimit', 'isActive')
            ->get();
        if (count($tax_table_entries_query) === 0) {
            return response()->json(['error' => 'tax table does not exist', 'messages' => 'tax table does not exist'], 404);
        }
        // can't edit if it's already linked to a payroll operation/payrun(?)
        if ($tax_table_entries_query[0]->isActive === 1) {
            return response()->json([
                'error' => 'tax table has been/is being used in payroll',
                'messages' => 'tax table has been/is being used in payroll',
            ], 404);
        }

        #3
        /**
         * sort tables by their lowerLimit, to ensure that the table is arranged
         * from lowest to highest
         */
        $table_entries = $request->input('table_entries');
        usort($table_entries, function ($a, $b) {
            return $a['lowerLimit'] - $b['lowerLimit'];
        });

        $previousValueUpperLimit = -1;

        foreach ($table_entries as $table_entry) {
            // ensure that the current lowerLimit is less than its upperLimit
            if ($table_entry['lowerLimit'] >= $table_entry['upperLimit']) {
                return response()->json([
                    'error' => 'Invalid request.',
                    'messages' => ['Please make sure that all your tax table
                    entry\'s minimum range is lower than it\'s maximum range.',],
                ], 442);
            }
            /**
             * check that the upperLimit of the current tax table entry
             * is not greater than or equal to the lowerLimit of the
             * next tax table entry
             */
            if ($table_entry['lowerLimit'] >= $previousValueUpperLimit) {
                $previousValueUpperLimit = $table_entry['upperLimit'];
            } else {
                return response()->json([
                    'error' => 'Invalid request.',
                    'messages' => ['Please make sure your tax table\'s ranges do not overlap. current =' . $table_entry['lowerLimit'] . ' previous = ' . $previousValueUpperLimit],
                ], 442);
            }
        }

        $new_class = Carbon::parse($request->input('new_class'));

        /**
         * check if new class and old class is equal
         * if equal, we only need to create new entry for
         * old class by means of deleting the old data
         */

        \DB::beginTransaction();
        if ($new_class == $old_class) {
            // delete existing tax table entries
            \DB::table('tax')
                ->where('class', '=', $old_class)
                ->delete();
        } else {
            // check new class if it is existing
            $check_new_class = \App\Tax::whereDate('class', '=', $new_class)->first();
            if ($check_new_class != null) {
                return response()->json(['error' => 'effectivity date already exists', 'messages' => 'effectivity date already exists'], 404);
            }
        }

        try {
            foreach ($table_entries as $table_entry) {
                $new_tax_table_entry = new \App\Tax();
                $new_tax_table_entry->class = $new_class;
                $new_tax_table_entry->constant = $table_entry['constant'];
                $new_tax_table_entry->percentage = $table_entry['percentage'];
                $new_tax_table_entry->lowerLimit = $table_entry['lowerLimit'];
                $new_tax_table_entry->upperLimit = $table_entry['upperLimit'];
                /**
                 * is_active is used to know whether the current tax table
                 * is being used for an ongoing payrun
                 *
                 * default should always be 0 since payroll should be the one
                 * activating this
                 */
                // #4
                $new_tax_table_entry->isActive = 0;

                $new_tax_table_entry->save();
            }
        } catch (\Exception $exception) {
            \DB::rollback();
            return response()->json(
                ['error' => 'Internal Server Error', 'messages' => ['Server Error', $exception->getMessage()]],
                500
            );
        }
        \DB::commit();

        return response()->json(["result" => "Tax Table updated successfully!"]);
    }

    function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_tax_table']);
        if ($unauthorized) {
            return $unauthorized;
        }

        /**
         * retrieve each distinct class(effectivity dates)
         */
        $distinct_classes = \DB::table('tax')
            ->groupBy('class')
            ->select('class')
            ->get();

        /**
         * data structure
         *[
         *  {
         *    class: "2020-01-29 00:00:00",
         *    table_entries: [
         *      {
         *        constant: 2500,
         *        percentage: 13,
         *        upperLimit: 60000,
         *        lowerLimit: 45000
         *      }
         *    ]
         *  }
         *]
         */
        $tax_tables = [];
        foreach ($distinct_classes as $distinct_class) {
            $tax_table = [];
            $tax_table['class'] = $distinct_class->class;

            #1
            $tax_table_entries = \DB::table('tax')
                ->where('class', '=', $distinct_class->class)
                ->select('id', 'constant', 'percentage', 'upperLimit', 'lowerLimit', 'isActive')
                ->get();
            $tax_table['table_entries'] = $tax_table_entries;

            array_push($tax_tables, $tax_table);
        }

        return response()->json($tax_tables);
    }

    public function get(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_tax_table']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validation_requirements = [
            // #2
            'class' => 'required|unique:tax',
        ];
        $validation = Validator::make($request->all(), $validation_requirements);
        if ($validation->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validation->errors()->all()], 400);
        }

        $class = Carbon::parse($request->input('class'));
        $tax_table_entries = \DB::table('tax')
            ->where('class', '=', $class)
            ->select('id', 'constant', 'percentage', 'upperLimit', 'lowerLimit', 'isActive')
            ->get();

        if (count($tax_table_entries) === 0) {
            return response()->json(['error' => 'tax table does not exist', 'messages' => 'tax table does not exist'], 404);
        }
        /**
         * data structure
         *{
         *  class: "2020-01-29 00:00:00",
         *  table_entries: [
         *      {
         *          constant: 2500,
         *          percentage: 13,
         *          upperLimit: 60000,
         *          lowerLimit: 45000
         *      }
         *  ]
         *}
         */
        $tax_table = [];
        $tax_table['class'] = $request->input('class');
        $tax_table['table_entries'] = $tax_table_entries;

        return response()->json($tax_table);
    }

    public function get_effective_tax_table(Request $request) {
        // get latest class
        $class = \App\Tax::where('class', '<', Carbon::now()->format('Y-m-d'))->max('class');

        // get all items under this class
        $tax_table = \App\Tax::where('class', '=', $class)->get();
        return response()->json($tax_table);
    }
}
