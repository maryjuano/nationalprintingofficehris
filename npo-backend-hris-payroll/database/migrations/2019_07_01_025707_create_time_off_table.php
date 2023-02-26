<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateTimeOffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_offs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->string('time_off_type');
            $table->string('time_off_code');
            $table->float('balance_credit')->nullable();
            $table->integer('balance_credit_type'); //day: 0, hour: 1
            $table->string('color');
            $table->boolean('is_active')->default(true);
            $table->boolean('use_csl_matrix')->default(false);
            $table->boolean('cash_convertible')->default(false);
            $table->boolean('carry_over')->default(false);
            $table->boolean('carry_over_type')->nullable(); //monthly: 0, yearly: 1
            $table->integer('month')->nullable();
            $table->integer('day');
            $table->string('created_by');
            $table->string('updated_by');
        });

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Vacation Leave',
            'time_off_code' => 'VL',
            'balance_credit' => '1.25',
            'balance_credit_type' => '0',
            'color' => 'Orange Fun',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'use_csl_matrix' => true,
            'cash_convertible' => true,
            'carry_over' => true,
            'carry_over_type' => 0,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Sick Leave',
            'time_off_code' => 'SL',
            'balance_credit' => '1.25',
            'balance_credit_type' => '0',
            'color' => 'Koko Caramel',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'use_csl_matrix' => true,
            'cash_convertible' => true,
            'carry_over' => true,
            'carry_over_type' => 0,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'CNA Leave',
            'time_off_code' => 'CNA',
            'balance_credit' => '3',
            'balance_credit_type' => '0',
            'color' => 'Sunny',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'month' => 1,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Special Priviledge Leave',
            'time_off_code' => 'SPL',
            'balance_credit' => '5',
            'balance_credit_type' => '0',
            'color' => 'Pale Wood',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'month' => 1,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Off-Set',
            'time_off_code' => 'OFST',
            'balance_credit' => '0',
            'balance_credit_type' => '1',
            'color' => 'Blue Skies',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'carry_over' => true,
            'carry_over_type' => 1,
            'month' => 1,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Personnel Locator Slip',
            'time_off_code' => 'PLS',
            'balance_credit' => '2',
            'balance_credit_type' => '1',
            'color' => 'Piggy Pink',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'month' => 1,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Maternity Leave',
            'time_off_code' => 'MTL',
            'balance_credit' => '105',
            'balance_credit_type' => '0',
            'color' => 'Citrus Peel',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'month' => 1,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Paternity Leave',
            'time_off_code' => 'PTL',
            'balance_credit' => '7',
            'balance_credit_type' => '0',
            'color' => 'Sea Blizz',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'month' => 1,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Bereavement Leave',
            'time_off_code' => 'BRVL',
            'balance_credit' => '3',
            'balance_credit_type' => '0',
            'color' => 'Forest',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'month' => 1,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Solo Parent Leave',
            'time_off_code' => 'SLPL',
            'balance_credit' => '7',
            'balance_credit_type' => '0',
            'color' => 'Dark Ocean',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'month' => 1,
            'day' => 1
        ]);

        $user_id = DB::table('time_offs')->insertGetId([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'time_off_type' => 'Violence Against Women Leave',
            'time_off_code' => 'VAWL',
            'balance_credit' => 11,
            'balance_credit_type' => '0',
            'color' => 'Cool Blues',
            'created_by' => 'Administrator',
            'updated_by' => 'Administrator',
            'month' => 1,
            'day' => 1
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_offs');
    }
}
