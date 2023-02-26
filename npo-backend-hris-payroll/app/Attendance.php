<?php

namespace App;

use App\Helpers\DayFractions;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class Attendance extends Model
{

    public const BIOMETRIC_TIME_IN = 0;
    public const BIOMETRIC_TIME_OUT = 1;
    public const BIOMETRIC_BREAK_START = 3;
    public const BIOMETRIC_BREAK_END = 2;

    public function additionalCounter(
        $dtr,
        $schedule,
        $holidaySchedule,
        $plsSchedule,
        $otSchedule,
        $nightDiffSettings,
        $dailyRate,
        $content
    ) {
        // // \Log::info(" ");

        $holidayPay = 0;
        $holidayDuration = 0;
        $nightDiffDuration = 0;
        $restDayDuration = 0;
        $restDayPay = 0;

        $perSecondRate = $dailyRate / 8 / 60 / 60;
        $dayOfWeek = date("l", strtotime($dtr["date"]));
        $dtrDayOfWeek = self::getDayIntOfWeek($dayOfWeek);

        $numOfIns = count($dtr["ins"]);
        $numOfOuts = count($dtr["outs"]);
        $numOfBreakStarts = count($dtr["breakStarts"]);
        $numOfBreakEnds = count($dtr["breakEnds"]);

        $nightDiffPay = 0;
        $nextNightDiffPay = 0;
        $nightDiffMultiplier = $nightDiffSettings["multiplier"];
        $holidayCount = count($holidaySchedule);
        // // \Log::info("Holiday Count: " . $holidayCount);

        $currentMultiplier = 1;
        $nextDayMultiplier = 1;
        $nextDayMultiplierRestDay = $content["restDaySettings"]["multiplier"];
        $restDayMultiplier = $content["restDaySettings"]["multiplier"];

        $otSettings = $content["overtimeSettings"];
        $otMultiplier = $content["overtimeSettings"]["multiplier"] - 1;
        $otMultiplierRest = $content["overtimeSettings"]["multiplier"];


        if ($holidayCount > 0) {
            $nextDayDate = date('Y-m-d', strtotime($dtr["date"] . ' +1 day'));

            foreach ($holidaySchedule as $holiday) {
                if ($dtr["date"] == $holiday["holidayDate"]) {
                    $currentMultiplier = $holiday["multiplier"];
                } else if ($nextDayDate == $holiday["holidayDate"]) {
                    $nextDayMultiplier = $holiday["multiplier"];
                    $nextDayMultiplierRestDay = $holiday["multiplier"];
                }
            }
        }

        //CASE 200_2_4d: REST DAY PAY
        if ($schedule[$dtrDayOfWeek]["type"] == 0) {
            $nightDiffMultiplier = $restDayMultiplier * $nightDiffSettings["multiplier"];
            if ($numOfIns == 0 && $numOfOuts == 0 && $numOfBreakStarts == 0 && $numOfBreakEnds == 0) {
                $result["holidayDuration"] = 0;
                $result["holidayPay"] = 0;
                $result["nightDiffDuration"] = 0;
                $result["nightDiffPay"] = 0;
                $result["otDuration"] = 0;
                $result["otPay"] = 0;
                $result["restDayDuration"] = "00:00:00";
                $result["restDayPay"] = 0;
                return $result;
            }
            if ($numOfIns == 1 && $numOfBreakStarts == 1) {
                if ($holidayCount > 0) {
                    foreach ($holidaySchedule as $holiday) {
                        //200_4_2_2
                        // // \Log::info("CASE 200_2_4_2");

                        if ($holiday["holidayDate"] == $dtr["date"]) {
                            $holidayStart = date('Y-m-d H:i:s', strtotime($holiday["holidayStart"]));
                            $holidayEnd = date('Y-m-d H:i:s', strtotime($holiday["holidayEnd"]));


                            $in = date('Y-m-d H:i:s', strtotime($dtr["ins"][0]["schedule"]));
                            $breakStart = date('Y-m-d H:i:s', strtotime($dtr["breakStarts"][0]["schedule"]));

                            // // \Log::info("CASE 200_4_2_2 Holiday Start: " . $holidayStart);
                            // // \Log::info("CASE 200_4_2_2 In: " . $in);
                            // // \Log::info("CASE 200_4_2_2 Break Start: " . $breakStart);
                            // // \Log::info("CASE 200_4_2_2 Holiday End: " . $holidayEnd);


                            if ($holidayStart <= $in && $breakStart <= $holidayEnd) {
                                $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                                $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                                $diffInSeconds = $time2->diffInSeconds($time1);
                                $holidayDuration = $holidayDuration + $diffInSeconds;
                                $holidayPay = $holidayPay + round($diffInSeconds * $perSecondRate * $holiday["multiplierRest"], 2);
                                $nightDiffMultiplier = $holiday["multiplierRest"] * $nightDiffSettings["multiplier"];

                                $restDayMultiplier = 0;
                                break;
                            }
                        }
                    }
                }

                //CASE 200_2_4d_2
                $in = date('Y-m-d H:i:s', strtotime($dtr["ins"][0]["schedule"]));
                $breakStart = date('Y-m-d H:i:s', strtotime($dtr["breakStarts"][0]["schedule"]));

                if ($in < $breakStart) {
                    // // \Log::info("CASE 200_2_4d_2");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $restDayDuration = $restDayDuration + $diffInSeconds;
                    $restDayPay = $restDayPay + $diffInSeconds * $perSecondRate * $restDayMultiplier;
                }


                $nightDiffAmStart = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . "00:00:00"));
                $nightDiffAmEnd = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . $nightDiffSettings["end"]));
                $nightDiffPmStart = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . $nightDiffSettings["start"]));
                $nightDiffPmEnd = date('Y-m-d H:i:s', strtotime($dtr["date"] . ' +1 day'));
                $nightDiffNextDayEnd = date('Y-m-d H:i:s', strtotime($nightDiffAmEnd . ' +1 day'));



                //CASE 200_2_4a_2: AM DTR within AM Night Differential - Ordinary Day
                if ($nightDiffAmStart <= $in && $in <= $nightDiffAmEnd && $breakStart <= $nightDiffAmEnd) {
                    // \Log::info("CASE 200_2_4a_2");
                    // \Log::info("CASE 200_2_4_4_1b");
                    // \Log::info("CASE 200_2_4d_2b");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4a_2a
                else if (
                    $nightDiffAmStart <= $in && $in <= $nightDiffAmEnd &&
                    $nightDiffAmEnd < $breakStart
                ) {
                    // \Log::info("CASE 200_2_4a_2a");
                    // \Log::info("CASE 200_2_4_4_1c");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffAmEnd);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4a_2b
                else if ($nightDiffPmStart <= $in && $nightDiffPmStart <= $nightDiffPmEnd && $breakStart <= $nightDiffPmEnd) {
                    // \Log::info("CASE 200_2_4a_2b");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4a_2c
                else if ($in < $nightDiffPmStart &&  $nightDiffPmStart <= $breakStart && $breakStart <= $nightDiffPmEnd) {
                    // \Log::info("CASE 200_2_4a_2c");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffPmStart);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                } else if (
                    $nightDiffPmStart <= $in && $nightDiffPmEnd < $breakStart &&
                    $breakStart <= $nightDiffNextDayEnd
                ) {

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffPmEnd);
                    $time0 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time1->diffInSeconds($time0);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);

                    $nextDay = date("l", strtotime($breakStart));
                    $dtrNextDay = self::getDayIntOfWeek($nextDay);

                    // \Log::info("CASE 200_2_4a_2d - Next day type: " . $schedule[$dtrNextDay]["type"]);

                    if ($schedule[$dtrNextDay]["type"] == 1) {
                        //CASE 200_2_4a_2d
                        // \Log::info("CASE 200_2_4a_2d");

                        $diffInSeconds = $time2->diffInSeconds($time1);
                        $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                        $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier * $nextDayMultiplier, 2);
                    } else {
                        //CASE 200_2_4a_2d_1
                        // \Log::info("CASE 200_2_4a_2d_1");

                        $diffInSeconds = $time2->diffInSeconds($time1);
                        $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                        $nightDiffPay = $nightDiffPay +
                            round($diffInSeconds * $perSecondRate * $nightDiffSettings["multiplier"] * $nextDayMultiplierRestDay, 2);
                    }
                }
            }
            if ($numOfBreakEnds == 1 && $numOfOuts == 1) {
                $out = date('Y-m-d H:i:s', strtotime($dtr["outs"][0]["schedule"]));
                $breakEnd = date('Y-m-d H:i:s', strtotime($dtr["breakEnds"][0]["schedule"]));

                if ($holidayCount > 0) {
                    foreach ($holidaySchedule as $holiday) {
                        //200_4_2_2
                        // \Log::info("CASE 200_2_4_3");

                        if ($holiday["holidayDate"] == $dtr["date"]) {

                            if ($otSchedule != null) {
                                $otStart = date('Y-m-d H:i:s', strtotime($otSchedule["start"]));
                                $otEnd = date('Y-m-d H:i:s', strtotime($otSchedule["end"]));
                                if ($holidayStart <= $otStart && $otEnd <= $holidayEnd && $out == $otEnd) {
                                    // \Log::info("CASE 200_2_5_4");

                                    $out = $otStart;
                                }
                            } else {
                                // \Log::info("CASE 200_2_5_1: No Overtime");

                            }

                            $holidayStart = date('Y-m-d H:i:s', strtotime($holiday["holidayStart"]));
                            $holidayEnd = date('Y-m-d H:i:s', strtotime($holiday["holidayEnd"]));



                            // \Log::info("CASE 200_4_2_2 Holiday Start: " . $holidayStart);
                            // \Log::info("CASE 200_4_2_2 Break End: " . $breakEnd);

                            // \Log::info("CASE 200_4_2_2 Out: " . $out);
                            // \Log::info("CASE 200_4_2_2 Holiday End: " . $holidayEnd);


                            if ($holidayStart <= $breakEnd && $out <= $holidayEnd) {
                                $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                                $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                                $diffInSeconds = $time2->diffInSeconds($time1);
                                $holidayDuration = $holidayDuration + $diffInSeconds;
                                $holidayPay = $holidayPay + round($diffInSeconds * $perSecondRate * $holiday["multiplierRest"], 2);

                                $nightDiffMultiplier = $holiday["multiplierRest"] * $nightDiffSettings["multiplier"];

                                $restDayMultiplier = 0;

                                break;
                            }
                        }
                    }
                }

                $breakEnd = date('Y-m-d H:i:s', strtotime($dtr["breakEnds"][0]["schedule"]));
                $out = date('Y-m-d H:i:s', strtotime($dtr["outs"][0]["schedule"]));

                if ($otSchedule != null) {
                    $otStart = date('Y-m-d H:i:s', strtotime($otSchedule["start"]));

                    if ($otStart <= $out) {
                        $out = $otStart;
                    }
                }

                if ($breakEnd < $out) {

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $restDayDuration = $restDayDuration + $diffInSeconds;
                    $restDayPay = $restDayPay + $diffInSeconds * $perSecondRate * $restDayMultiplier;
                }

                $nightDiffAmStart = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . "00:00:00"));
                $nightDiffAmEnd = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . $nightDiffSettings["end"]));
                $nightDiffPmStart = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . $nightDiffSettings["start"]));
                $nightDiffPmEnd = date('Y-m-d H:i:s', strtotime($dtr["date"] . ' +1 day'));
                $nightDiffNextDayEnd = date('Y-m-d H:i:s', strtotime($nightDiffAmEnd . ' +1 day'));



                if ($nightDiffAmStart <= $breakEnd && $breakEnd <= $nightDiffAmEnd && $out <= $nightDiffAmEnd) {

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                } else if ($nightDiffAmStart <= $breakEnd && $breakEnd <= $nightDiffAmEnd && $nightDiffAmEnd < $out && $out < $nightDiffPmStart) {
                    // \Log::info("CASE 200_2_4b_2a");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffAmEnd);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                } else if ($nightDiffPmStart <= $breakEnd && $out <= $nightDiffPmEnd) {
                    // \Log::info("CASE 200_2_4b_2b");
                    // \Log::info("CASE 200_2_4_4_2a");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                } else if ($breakEnd < $nightDiffPmStart &&  $nightDiffPmStart <= $out && $out <= $nightDiffPmEnd) {
                    // \Log::info("CASE 200_2_4_4_2");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffPmStart);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                } else if (
                    $nightDiffPmStart <= $breakEnd && $nightDiffPmEnd < $out &&
                    $out <= $nightDiffNextDayEnd
                ) {

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffPmEnd);
                    $time0 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time1->diffInSeconds($time0);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);

                    $nextDay = date("l", strtotime($out));
                    $dtrNextDay = self::getDayIntOfWeek($nextDay);

                    // \Log::info("CASE 200_2_4b_2d - Next day type: " . $schedule[$dtrNextDay]["type"]);

                    if ($schedule[$dtrNextDay]["type"] == 1) {
                        // \Log::info("CASE 200_2_4b_2d");

                        $diffInSeconds = $time2->diffInSeconds($time1);
                        $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                        $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffSettings["multiplier"] * $nextDayMultiplier, 2);
                    } else {
                        // \Log::info("CASE 200_2_4b_2d_1");

                        $diffInSeconds = $time2->diffInSeconds($time1);
                        $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                        $nightDiffPay = $nightDiffPay +
                            round($diffInSeconds * $perSecondRate * $nightDiffSettings["multiplier"] * $nextDayMultiplierRestDay, 2);
                    }
                }
            }


            $result["holidayDuration"] = gmdate('H:i:s', $holidayDuration);
            $result["holidayPay"] = round($holidayPay, 2);
            $result["restDayDuration"] = gmdate('H:i:s', $restDayDuration);
            $result["restDayPay"] = round($restDayPay, 2);

            $result["otDuration"] = 0;
            $result["otPay"] = 0;
        } else if ($schedule[$dtrDayOfWeek]["type"] == 1) {

            //CASE 200_2_4_1
            if ($numOfIns == 0 && $numOfOuts == 0 && $numOfBreakStarts == 0 && $numOfBreakEnds == 0) {
                $result["holidayDuration"] = 0;
                $result["holidayPay"] = 0;
                $result["nightDiffDuration"] = 0;
                $result["nightDiffPay"] = 0;
                $result["otDuration"] = 0;
                $result["otPay"] = 0;
                $result["restDayDuration"] = 0;

                $result["restDayPay"] = 0;
                return $result;
            }
            if ($numOfIns == 1 && $numOfBreakStarts == 1) {
                if ($holidayCount > 0) {
                    foreach ($holidaySchedule as $holiday) {
                        //200_4_2_2
                        // \Log::info("CASE 200_2_4_2");

                        if ($holiday["holidayDate"] == $dtr["date"]) {
                            $holidayStart = date('Y-m-d H:i:s', strtotime($holiday["holidayStart"]));
                            $holidayEnd = date('Y-m-d H:i:s', strtotime($holiday["holidayEnd"]));
                            $nightDiffMultiplier = $holiday["multiplier"] * $nightDiffSettings["multiplier"];

                            $in = date('Y-m-d H:i:s', strtotime($dtr["ins"][0]["schedule"]));
                            $breakStart = date('Y-m-d H:i:s', strtotime($dtr["breakStarts"][0]["schedule"]));

                            // \Log::info("CASE 200_4_2_2 Holiday Start: " . $holidayStart);
                            // \Log::info("CASE 200_4_2_2 In: " . $in);
                            // \Log::info("CASE 200_4_2_2 Break Start: " . $breakStart);
                            // \Log::info("CASE 200_4_2_2 Holiday End: " . $holidayEnd);


                            if ($holidayStart <= $in && $breakStart <= $holidayEnd) {
                                $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                                $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                                $diffInSeconds = $time2->diffInSeconds($time1);
                                $holidayDuration = $holidayDuration + $diffInSeconds;
                                $holidayPay = $holidayPay + round($diffInSeconds * $perSecondRate * $holiday["multiplier"], 2);

                                break;
                            }
                            //CASE 200_2_4_2e
                            else if ($holidayStart <= $in && $breakStart > $holidayEnd) {
                                $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $holidayEnd);
                                $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                                $diffInSeconds = $time2->diffInSeconds($time1);
                                $holidayDuration = $holidayDuration + $diffInSeconds;
                                $holidayPay = $holidayPay + round($diffInSeconds * $perSecondRate * $holiday["multiplier"], 2);

                                break;
                            }
                        }
                    }
                }

                $in = date('Y-m-d H:i:s', strtotime($dtr["ins"][0]["schedule"]));
                $breakStart = date('Y-m-d H:i:s', strtotime($dtr["breakStarts"][0]["schedule"]));

                $nightDiffAmStart = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . "00:00:00"));
                $nightDiffAmEnd = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . $nightDiffSettings["end"]));
                $nightDiffPmStart = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . $nightDiffSettings["start"]));
                $nightDiffPmEnd = date('Y-m-d H:i:s', strtotime($dtr["date"] . ' +1 day'));
                $nightDiffNextDayEnd = date('Y-m-d H:i:s', strtotime($nightDiffAmEnd . ' +1 day'));


                // \Log::info("CASE 200_2_4a: NIGHT DIFF AM START " . $nightDiffAmStart);
                // \Log::info("CASE 200_2_4a: NIGHT DIFF AM END " . $nightDiffAmEnd);

                // \Log::info("CASE 200_2_4a: IN " . $in);
                // \Log::info("CASE 200_2_4a: BREAKSTART " . $breakStart);
                // \Log::info("CASE 200_2_4a: NIGHT DIFF PM START " . $nightDiffPmStart);
                // \Log::info("CASE 200_2_4a: NIGHT DIFF PM END " . $nightDiffPmEnd);
                // \Log::info("CASE 200_2_4a: NIGHT DIFF NEXT DAY AM END " . $nightDiffNextDayEnd);

                //CASE 200_2_4a_2: AM DTR within AM Night Differential - Ordinary Day
                if ($nightDiffAmStart <= $in && $in <= $nightDiffAmEnd && $breakStart <= $nightDiffAmEnd) {
                    // \Log::info("CASE 200_2_4a_2");
                    // \Log::info("CASE 200_2_4_4_1");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4a_2a
                else if (
                    $nightDiffAmStart <= $in && $in <= $nightDiffAmEnd &&
                    $nightDiffAmEnd < $breakStart
                ) {
                    // \Log::info("CASE 200_2_4a_2a");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffAmEnd);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4a_2b
                else if ($nightDiffPmStart <= $in && $nightDiffPmStart <= $nightDiffPmEnd && $breakStart <= $nightDiffPmEnd) {
                    // \Log::info("CASE 200_2_4a_2b");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4a_2c
                else if ($in < $nightDiffPmStart &&  $nightDiffPmStart <= $breakStart && $breakStart <= $nightDiffPmEnd) {
                    // \Log::info("CASE 200_2_4a_2c");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffPmStart);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                } else if (
                    $nightDiffPmStart <= $in && $nightDiffPmEnd < $breakStart &&
                    $breakStart <= $nightDiffNextDayEnd
                ) {

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffPmEnd);
                    $time0 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

                    $diffInSeconds = $time1->diffInSeconds($time0);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);

                    $nextDay = date("l", strtotime($breakStart));
                    $dtrNextDay = self::getDayIntOfWeek($nextDay);

                    // \Log::info("CASE 200_2_4a_2d - Next day type: " . $schedule[$dtrNextDay]["type"]);

                    if ($schedule[$dtrNextDay]["type"] == 1) {
                        if ($holidayCount > 0) {
                            $breakStartDate = date('Y-m-d', strtotime($breakStart));

                            foreach ($holidaySchedule as $holiday) {
                                //200_4_2_2
                                // \Log::info("CASE 200_2_4_2");

                                if ($holiday["holidayDate"] == $breakStartDate) {
                                    // \Log::info("CASE 200_2_4_2f");

                                    $holidayStart = date('Y-m-d H:i:s', strtotime($holiday["holidayStart"]));
                                    $holidayEnd = date('Y-m-d H:i:s', strtotime($holiday["holidayEnd"]));
                                    $nightDiffMultiplier = $holiday["multiplier"] * $nightDiffSettings["multiplier"];


                                    if ($holidayStart <= $breakStart && $breakStart <= $holidayEnd) {
                                        $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $holidayStart);
                                        $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);

                                        $diffInSeconds = $time2->diffInSeconds($time1);
                                        $holidayDuration = $holidayDuration + $diffInSeconds;
                                        $holidayPay = $holidayPay + round($diffInSeconds * $perSecondRate * $holiday["multiplier"], 2);

                                        break;
                                    }
                                }
                            }
                        }

                        //CASE 200_2_4a_2d
                        // \Log::info("CASE 200_2_4a_2d");

                        $diffInSeconds = $time2->diffInSeconds($time1);
                        $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                        $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffSettings["multiplier"] * $nextDayMultiplier, 2);
                    } else {
                        //CASE 200_2_4a_2d_1
                        // \Log::info("CASE 200_2_4a_2d_1");

                        $diffInSeconds = $time2->diffInSeconds($time1);
                        $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                        $nightDiffPay = $nightDiffPay +
                            round($diffInSeconds * $perSecondRate * $nightDiffSettings["multiplier"] * $nextDayMultiplierRestDay, 2);
                    }
                }
            }
            if ($numOfBreakEnds == 1 && $numOfOuts == 1) {
                $breakEnd = date('Y-m-d H:i:s', strtotime($dtr["breakEnds"][0]["schedule"]));
                $out = date('Y-m-d H:i:s', strtotime($dtr["outs"][0]["schedule"]));



                if ($holidayCount > 0) {
                    foreach ($holidaySchedule as $holiday) {
                        //200_4_2_2
                        // \Log::info("CASE 200_2_4_3");
                        $holidayStart = date('Y-m-d', strtotime($holiday["holidayStart"]));
                        $holidayEnd = date('Y-m-d', strtotime($holiday["holidayEnd"]));
                        if ($holiday["holidayDate"] == $dtr["date"]) {
                            if ($otSchedule != null) {
                                $otStart = date('Y-m-d H:i:s', strtotime($otSchedule["start"]));
                                $otEnd = date('Y-m-d H:i:s', strtotime($otSchedule["end"]));
                                if ($holidayStart <= $otStart && $otEnd <= $holidayEnd && $out == $otEnd) {
                                    // \Log::info("CASE 200_2_5_4");

                                    $out = $otStart;
                                }
                            } else {
                                // \Log::info("CASE 200_2_5_1: No Overtime");

                            }

                            $holidayStart = date('Y-m-d H:i:s', strtotime($holiday["holidayStart"]));
                            $holidayEnd = date('Y-m-d H:i:s', strtotime($holiday["holidayEnd"]));
                            $nightDiffMultiplier = $holiday["multiplier"] * $nightDiffSettings["multiplier"];



                            // \Log::info("CASE 200_4_2_2 Holiday Start: " . $holidayStart);
                            // \Log::info("CASE 200_4_2_2 Break End: " . $breakEnd);

                            // \Log::info("CASE 200_4_2_2 Out: " . $out);
                            // \Log::info("CASE 200_4_2_2 Holiday End: " . $holidayEnd);


                            if ($holidayStart <= $breakEnd && $out <= $holidayEnd) {
                                $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                                $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                                $diffInSeconds = $time2->diffInSeconds($time1);
                                $holidayDuration = $holidayDuration + $diffInSeconds;
                                $holidayPay = $holidayPay + round($diffInSeconds * $perSecondRate * $holiday["multiplier"], 2);
                                break;
                            }
                        }
                    }
                }





                $nightDiffAmStart = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . "00:00:00"));
                $nightDiffAmEnd = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . $nightDiffSettings["end"]));
                $nightDiffPmStart = date('Y-m-d H:i:s', strtotime($dtr["date"] . " " . $nightDiffSettings["start"]));
                $nightDiffPmEnd = date('Y-m-d H:i:s', strtotime($dtr["date"] . ' +1 day'));
                $nightDiffNextDayEnd = date('Y-m-d H:i:s', strtotime($nightDiffAmEnd . ' +1 day'));



                //CASE 200_2_4b_2: PM DTR within AM Night Differential - Ordinary Day
                if ($nightDiffAmStart <= $breakEnd && $breakEnd <= $nightDiffAmEnd && $out <= $nightDiffAmEnd) {
                    // \Log::info("CASE 200_2_4b_2");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4b_2a:
                else if ($nightDiffAmStart <= $breakEnd && $breakEnd <= $nightDiffAmEnd && $nightDiffAmEnd < $out && $out < $nightDiffPmStart) {
                    // \Log::info("CASE 200_2_4b_2a");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffAmEnd);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4b_2b
                else if ($nightDiffPmStart <= $breakEnd && $out <= $nightDiffPmEnd) {
                    // \Log::info("CASE 200_2_4b_2b");
                    // \Log::info("CASE 200_2_4_4_2a");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4b_2c
                else if ($breakEnd < $nightDiffPmStart &&  $nightDiffPmStart <= $out && $out <= $nightDiffPmEnd) {
                    // \Log::info("CASE 200_2_4b_2c");

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffPmStart);

                    $diffInSeconds = $time2->diffInSeconds($time1);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);
                }
                //CASE 200_2_4b_2d
                else if (
                    $nightDiffPmStart <= $breakEnd && $nightDiffPmEnd < $out &&
                    $out <= $nightDiffNextDayEnd
                ) {

                    $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
                    $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nightDiffPmEnd);
                    $time0 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

                    $diffInSeconds = $time1->diffInSeconds($time0);
                    $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                    $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffMultiplier, 2);

                    $nextDay = date("l", strtotime($out));
                    $dtrNextDay = self::getDayIntOfWeek($nextDay);

                    // \Log::info("CASE 200_2_4b_2d - Next day type: " . $schedule[$dtrNextDay]["type"]);

                    if ($schedule[$dtrNextDay]["type"] == 1) {
                        // \Log::info("CASE 200_2_4b_2d");

                        $diffInSeconds = $time2->diffInSeconds($time1);
                        $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                        $nightDiffPay = $nightDiffPay + round($diffInSeconds * $perSecondRate * $nightDiffSettings["multiplier"] * $nextDayMultiplier, 2);
                    } else {
                        // \Log::info("CASE 200_2_4b_2d_1");

                        $diffInSeconds = $time2->diffInSeconds($time1);
                        $nightDiffDuration = $nightDiffDuration + $diffInSeconds;
                        $nightDiffPay = $nightDiffPay +
                            round($diffInSeconds * $perSecondRate * $nightDiffSettings["multiplier"] * $nextDayMultiplierRestDay, 2);
                    }
                }
            }
            $result["restDayDuration"] = gmdate('H:i:s', $restDayDuration);
            $result["restDayPay"] = round($restDayPay, 2);

            $result["holidayDuration"] = gmdate('H:i:s', $holidayDuration);
            $result["holidayPay"] = round($holidayPay, 2);

            $result["otDuration"] = 0;
            $result["otPay"] = 0;
        }


        if ($otSchedule != null) {
            $otStart = date('Y-m-d H:i:s', strtotime($otSchedule["start"]));
            $otEnd = date('Y-m-d H:i:s', strtotime($otSchedule["end"]));

            if ($holidayCount > 0) {
                foreach ($holidaySchedule as $holiday) {

                    if ($holiday["holidayDate"] == $dtr["date"]) {
                        $holidayStart = date('Y-m-d', strtotime($holiday["holidayStart"]));
                        $holidayEnd = date('Y-m-d', strtotime($holiday["holidayEnd"]));

                        if ($holidayStart <= $otStart && $otEnd <= $holidayEnd) {
                            if ($holiday["type"] == 0 && $schedule[$dtrDayOfWeek]["type"] == 1)
                                $otMultiplier = $otSettings["specialHoliday"];
                            else if ($holiday["type"] == 0 && $schedule[$dtrDayOfWeek]["type"] == 0)
                                $otMultiplier = $otSettings["specialHolidayRest"];
                            else if ($holiday["type"] == 1 && $schedule[$dtrDayOfWeek]["type"] == 1)
                                $otMultiplier = $otSettings["regularHoliday"];
                            else if ($holiday["type"] == 1 && $schedule[$dtrDayOfWeek]["type"] == 0)
                                $otMultiplier = $otSettings["regularHolidayRest"];
                            else if ($holiday["type"] == 2 && $schedule[$dtrDayOfWeek]["type"] == 1)
                                $otMultiplier = $otSettings["doubleHoliday"];
                            else if ($holiday["type"] == 2 && $schedule[$dtrDayOfWeek]["type"] == 0)
                                $otMultiplier = $otSettings["doubleHolidayRest"];
                        }
                    }
                }
            } else {

                if ($schedule[$dtrDayOfWeek]["type"] == 0) {
                    $otMultiplier = $otSettings["multiplierRest"];
                }
            }


            $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $otEnd);
            $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $otStart);

            $diffInSeconds = $time2->diffInSeconds($time1);
            $result["otDuration"] =  gmdate('H:i:s', $diffInSeconds);

            $result["otPay"] = abs($diffInSeconds * $perSecondRate * $otMultiplier);
        } else {
            $result["otDuration"] =  0;
            $result["otPay"] = 0;
        }

        $result["nightDiffDuration"] = gmdate('H:i:s', $nightDiffDuration);
        $result["nightDiffPay"] = round($nightDiffPay, 2); //round($nightDiffDuration * $nightDiffSettings["multiplier"] * $perSecondRate, 2);

        return $result;
    }

    public static function getTimeOffs($employee_ids, $startDate, $endDate)
    {
        return \DB::table('time_off_requests')
            ->join('time_off_request_details', 'time_off_requests.id', '=', 'time_off_request_details.time_off_request_id')
            ->join('time_off_balance', 'time_off_requests.time_off_balance_id', '=', 'time_off_balance.id')
            ->join('time_offs', 'time_off_balance.time_off_id', '=', 'time_offs.id')
            ->leftJoin('time_off_color', 'time_off_color.id', 'time_offs.time_off_color_id')
            ->whereIn('time_off_requests.employee_id', $employee_ids)
            ->whereBetween('time_off_request_details.time_off_date', [$startDate, $endDate])
            ->where('time_off_balance.time_off_id', '!=', 6)
            ->select(
                'time_off_requests.employee_id',
                'time_off_request_details.time_off_date',
                'time_off_requests.id',
                'time_offs.time_off_type',
                'time_offs.time_off_code',
                'time_off_requests.status',
                'time_off_request_details.time_off_duration',
                'time_off_request_details.time_off_period',
                'time_off_request_details.time_will_be_gone',
                'time_off_color.color_hex',
                'time_off_requests.is_without_pay'
            )
            ->orderBy('time_off_request_details.time_off_date', 'DESC')
            ->get();
    }

    public static function getEmployeeTimeOff($timeOffsList, $employee_id, $current_dtr_date_str)
    {
        $time_off = $timeOffsList->filter(function ($item) use ($employee_id, $current_dtr_date_str) {
            return $item->employee_id == $employee_id && $item->time_off_date == $current_dtr_date_str;
        })->first();

        if ($time_off) {
            $leave_item = array();
            $leave_item["type"] = $time_off->time_off_type;
            $leave_item["code"] = $time_off->time_off_code;
            $leave_item["status"] = $time_off->status;
            $leave_item["id"] = $time_off->id;
            $leave_item["color"] = $time_off->color_hex;
            $leave_item["is_without_pay"] = $time_off->is_without_pay;

            if ($time_off->time_off_duration === "whole") {
                $leave_item["duration"] = "whole";
            } else if ($time_off->time_off_duration === "half") {
                $leave_item["duration"] = "half";
            } else if ($time_off->time_off_duration === "none" && !$time_off->time_will_be_gone) {
                $leave_item = null;
            } else {
                $leave_item["duration"] = $time_off->time_will_be_gone;
            }

            return $leave_item;
        } else {
            return null;
        }
    }

    public static function getEmployeeOvertimeRequest($overtime_requests, $current_dtr_date_str)
    {
        $overtime_request = $overtime_requests->filter(function ($item) use ($current_dtr_date_str) {
            $overtime_request_date = Carbon::createFromFormat('Y-m-d H:i:s', $item->start_time)->toDateString();
            return $overtime_request_date === $current_dtr_date_str;
        })->first();

        if ($overtime_request) {
            $overtime_item = array();
            $overtime_item["start"] = $overtime_request->start_time;
            $overtime_item["end"] = $overtime_request->end_time;
            $overtime_item["id"] = $overtime_request->id;
            $overtime_item["status"] = $overtime_request->status;

            $overtime_start = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $overtime_item['start']);
            $overtime_end = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $overtime_item['end']);
            $overtime_item['duration'] = self::convertToMinutes($overtime_start->diffInSeconds($overtime_end));

            return $overtime_item;
        } else {
            return null;
        }
    }

    public static function getFormattedDtrDays($startDate, $endDate, $holidays)
    {
        $days = array();

        for ($start = date("Y-m-d", strtotime($startDate)); $start <= $endDate; $start = date("Y-m-d", strtotime("$start +1 day"))) {
            $holiday = $holidays->filter(function ($item) use ($start) {
                $currentDate = Carbon::parse($start);
                $holidayDate = Carbon::parse($item->holiday_date);
                $holidayDate->year($currentDate->year);
                return $holidayDate->equalTo($currentDate);
            })->first();
            $dayOfWeek = date("l", strtotime($start));
            $dtrDayOfWeek = self::getDayIntOfWeek($dayOfWeek);
            $day_item["day"] = self::getDayOfWeek($dtrDayOfWeek);
            $day_item["date"] = $start;
            $day_item["isHoliday"] = !!$holiday;
            $day_item["isWeekend"] = $dtrDayOfWeek == 0 || $dtrDayOfWeek == 6;
            $day_item["holiday_name"] =  !!$holiday ? $holiday->name : "";
            $day_item["holiday_type"] =  !!$holiday ? $holiday->time_data_name : "";

            array_push($days, $day_item);
        }

        return $days;
    }

    public static function getHolidays($startDate, $endDate)
    {
        return \App\Holiday::orderBy('date', 'asc')
            ->leftJoin('time_data', 'time_data.id', 'holidays.time_data_id')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('is_recurring', 0);
                $query->whereDate('date', '>=', $startDate);
                $query->whereDate('date', '<=', $endDate);
            })
            ->orWhere(function ($query) use ($startDate, $endDate) {
                $query->where('is_recurring', 1);
                $query->whereRaw('DAYOFYEAR(date) <= DAYOFYEAR(?) AND DAYOFYEAR(date) >=  dayofyear(?)', [$endDate, $startDate]);
            })
            ->select(
                'holidays.id',
                'holidays.holiday_name as name',
                'holidays.date as holiday_date',
                \DB::raw('CAST(date as DATETIME) as holiday_start'),
                \DB::raw('CAST(ADDDATE(date, INTERVAL 1 DAY) as DATETIME) as holiday_end'),
                'multiplier',
                'time_data_name',
                'is_recurring'
            )->get();
    }

    public static function getSchedule($employee, $breaks)
    {
        switch ($employee->time_option) {
            case 1:
                return self::getFixedDailyHours($employee->daily_hours);
            case 2:
                return self::getFixedDailySchedule($employee->start_times, $employee->end_times, $employee->end_times_is_next_day, $employee->grace_periods, $breaks);
            case 3:
                return null;
            case 4:
                return null;
            default:
                return null;
        }
    }

    public static function getFixedDailyHours($dailyHours)
    {
        $dailyHours = json_decode($dailyHours, true);

        return [
            '0' => self::getDayHours('sunday', $dailyHours['sunday']),
            '1' => self::getDayHours('monday', $dailyHours['sunday']),
            '2' => self::getDayHours('tuesday', $dailyHours['tuesday']),
            '3' => self::getDayHours('wednesday', $dailyHours['wednesday']),
            '4' => self::getDayHours('thursday', $dailyHours['thursday']),
            '5' => self::getDayHours('friday', $dailyHours['friday']),
            '6' => self::getDayHours('saturday', $dailyHours['saturday']),
        ];
    }

    public static function getDayHours($day, $dailyHour)
    {
        return [
            'type' => $dailyHour === 0 ? 0 : 1,
            'requiredHours' => $dailyHour,
            'day_name' => $day,
        ];
    }

    public static function getFixedDailySchedule($startTimes, $endTimes, $endTimesIsNextDay, $gracePeriods, $break)
    {
        $startTimes = json_decode($startTimes, true);
        $endTimes = json_decode($endTimes, true);
        $gracePeriods = json_decode($gracePeriods, true);
        $endTimesIsNextDay = json_decode($endTimesIsNextDay, true);

        return [
            '0' => self::getDaySchedule('sunday', $startTimes['sunday'], $endTimes['sunday'], $endTimesIsNextDay['sunday'], $gracePeriods['sunday'], $break),
            '1' => self::getDaySchedule('monday', $startTimes['monday'], $endTimes['monday'], $endTimesIsNextDay['monday'], $gracePeriods['monday'], $break),
            '2' => self::getDaySchedule('tuesday', $startTimes['tuesday'], $endTimes['tuesday'], $endTimesIsNextDay['tuesday'], $gracePeriods['tuesday'], $break),
            '3' => self::getDaySchedule('wednesday', $startTimes['wednesday'], $endTimes['wednesday'], $endTimesIsNextDay['wednesday'], $gracePeriods['wednesday'], $break),
            '4' => self::getDaySchedule('thursday', $startTimes['thursday'], $endTimes['thursday'], $endTimesIsNextDay['thursday'], $gracePeriods['thursday'], $break),
            '5' => self::getDaySchedule('friday', $startTimes['friday'], $endTimes['friday'], $endTimesIsNextDay['friday'], $gracePeriods['friday'], $break),
            '6' => self::getDaySchedule('saturday', $startTimes['saturday'], $endTimes['saturday'], $endTimesIsNextDay['saturday'], $gracePeriods['saturday'], $break)
        ];
    }

    public static function getDaySchedule($day, $startTime, $endTime, $endTimeIsNextDay, $gracePeriod, $break)
    {
        return [
            'type' => $startTime != null ? 1 : 0,
            'maxIn' => $startTime,
            'breakStarts' => $break != null ? $break->start_time : null,
            'breakStartsIsNextDay' => $break->start_time_next_day,
            'breakEnds' => $break != null ? $break->end_time : null,
            'breakEndsIsNextDay' => $break->end_time_next_day,
            'maxOut' => $endTime,
            'maxOutIsNextDay' => $endTimeIsNextDay,
            'day_name' => $day,
            'grace_periods' => $gracePeriod
        ];
    }

    public static function getUndertimesInMinutes($dtr, $schedule, $time_option)
    {
        if ($time_option !== 2 && $time_option !== 1) {
            return 0;
        }

        $dayOfWeek = date("l", strtotime($dtr["date"]));
        $dtrDayOfWeek = self::getDayIntOfWeek($dayOfWeek);
        if ($dtr['is_restday']) { // REST DAY
            return 0;
        }

        if ($dtr['holiday'] !== null) {
            return 0;
        }

        if ($dtr['time_off_request'] != null  && $dtr['time_off_request']['status'] !== -1 && $dtr['time_off_request']['duration'] === 'whole') {
            return 0;
        }

        $maxIn = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["maxIn"]);
        $maxOut = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["maxOut"]);
        if ($schedule[$dtrDayOfWeek]["maxOutIsNextDay"]) {
            $maxOut->addDay('1');
        }
        $breakStart = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["breakStarts"]);
        if ($schedule[$dtrDayOfWeek]["breakStartsIsNextDay"]) {
            $breakStart->addDay('1');
        }
        $breakEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["breakEnds"]);
        if ($schedule[$dtrDayOfWeek]["breakEndsIsNextDay"]) {
            $breakEnd->addDay('1');
        }
        $minimumOfficeSeconds = $maxIn->diffInSeconds($maxOut) - $breakStart->diffInSeconds($breakEnd);

        $renderedOfficeSeconds = self::getRenderedSeconds($dtr, $schedule);
        
        if ($dtr['time_off_request'] != null  && $dtr['time_off_request']['status'] !== -1 && $dtr['time_off_request']['duration'] === 'half') {
            $minimumOfficeSeconds = round($minimumOfficeSeconds / 2);
            if ($renderedOfficeSeconds === 0) {
                return $minimumOfficeSeconds;
            }
        }

        if ($renderedOfficeSeconds === 0) {
            return 0;
        }

        $undertime = $minimumOfficeSeconds - $renderedOfficeSeconds;
        return $undertime > 0 ? self::convertToMinutes($undertime) : 0;
    }

    public static function getLatesInMinutes($dtr, $schedule, $time_option)
    {
        if ($time_option !== 2) {
            return 0;
        }

        $dayOfWeek = date("l", strtotime($dtr["date"]));
        $dtrDayOfWeek = self::getDayIntOfWeek($dayOfWeek);
        if ($schedule[$dtrDayOfWeek]["type"] === 0) { // REST DAY
            return 0;
        }

        if ($dtr['holiday'] !== null) {
            return 0;
        }

        if (!isset($dtr['in']['schedule']) && !isset($dtr['break_end']['schedule'])) {
            return 0;
        }

        if ($dtr['time_off_request'] != null && $dtr['time_off_request']['status'] !== -1) {
            return 0;
        }

        $duration = 0;

        if (isset($dtr['in']['schedule'])) {
            $actualIn = Carbon::parse($dtr['in']["schedule"]);
            $maxIn = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["maxIn"]);
            $maxInWithGrace = $maxIn->copy()->addMinutes($schedule[$dtrDayOfWeek]['grace_periods']);

            if ($actualIn->greaterThan($maxInWithGrace)) {
                $totalLateMinutes = $maxIn->diffInSeconds($actualIn);
                if ($totalLateMinutes <= 7200) {
                    $duration += $maxInWithGrace->diffInSeconds($actualIn);
                } else {
                    $duration += $maxIn->diffInSeconds($actualIn);
                }
            }
        }

        if (isset($dtr['break_end']['schedule'])) {
            $actualBreakEnd = Carbon::parse($dtr['break_end']["schedule"]);
            $breakEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["breakEnds"]);
            if ($schedule[$dtrDayOfWeek]["breakEndsIsNextDay"]) {
                $breakEnd->addDay('1');
            }

            if ($actualBreakEnd->greaterThan($breakEnd)) {
                $duration += $breakEnd->diffInSeconds($actualBreakEnd);
            }
        }

        return self::convertToMinutes($duration);
    }

    public static function getAbsenceCount($dtr, $time_option)
    {
        if ($time_option !== 2 && $time_option !== 1) {
            return 0;
        }

        if ($dtr['is_restday']) {
            return 0;
        }

        if ($dtr['holiday'] !== null) {
            return 0;
        }

        if ($dtr['time_off_request'] != null && $dtr['time_off_request']['status'] !== -1) {
            return 0;
        }

        if (!isset($dtr['in']['schedule']) && !isset($dtr['break_end']['schedule'])) {
            return 1;
        }

        return 0;
    }

    public static function getOvertimes($overtimeMinutes, $lateMinutes, $dtr, $schedule, $time_option)
    {
        if (($time_option !== 2 && $time_option !== 1) || $overtimeMinutes === 0) {
            return array();
        }

        $in = isset($dtr['in']['schedule']) ? Carbon::createFromFormat('Y-m-d H:i:s', $dtr['in']['schedule']) : null;
        $breakEnd = isset($dtr['break_end']['schedule']) ? Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_end']['schedule']) : null;

        if (!$in && !$breakEnd) {
            return array();
        }

        $breakStart = isset($dtr['break_start']['schedule']) ? Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_start']['schedule']) : null;
        $out = isset($dtr['out']['schedule']) ? Carbon::createFromFormat('Y-m-d H:i:s', $dtr['out']['schedule']) : null;

        if ($dtr['holiday'] != null || $dtr['is_restday']) {
            if ($in != null && $out != null) {
                $duration_in_minutes = $out->diffInMinutes($in);
                if ($breakStart != null && $breakEnd != null) { // check if there is a break configured
                    $duration_in_minutes = $duration_in_minutes - $breakEnd->diffInMinutes($breakStart);
                }
                return array(array(
                    'start' => $in->toDateTimeString(),
                    'end' => $out->toDateTimeString(),
                    'duration_in_minutes' => $duration_in_minutes
                ));
            } else if ($in != null && $breakStart != null) {
                return array(array(
                    'start' => $in->toDateTimeString(),
                    'end' => $breakStart->toDateTimeString(),
                    'duration_in_minutes' => $breakStart->diffInMinutes($in)
                ));
            } else if ($breakEnd != null && $out != null) {
                return array(array(
                    'start' => $breakEnd->toDateTimeString(),
                    'end' => $out->toDateTimeString(),
                    'duration_in_minutes' => $out->diffInMinutes($breakEnd)
                ));
            }
        }

        $overtimes = array();
        $dayOfWeek = date("l", strtotime($dtr["date"]));
        $dtrDayOfWeek = self::getDayIntOfWeek($dayOfWeek);

        $scheduled_in = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["maxIn"]);

        if ($scheduled_in != null && $in != null && $in->diffInMinutes($scheduled_in) > 0) {
            array_push($overtimes, array(
                'start' => $in->toDateTimeString(),
                'end' => $scheduled_in->toDateTimeString(),
                'duration_in_minutes' => $in->diffInMinutes($scheduled_in)
            ));
        }

        $scheduled_out = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["maxOut"])->addMinutes($lateMinutes);
        if ($schedule[$dtrDayOfWeek]["maxOutIsNextDay"]) {
            $scheduled_out->addDay('1');
        }

        if ($scheduled_out != null && $out != null && $out->diffInMinutes($scheduled_out) > 0) {
            array_push($overtimes, array(
                'start' => $scheduled_out->toDateTimeString(),
                'end' => $out->toDateTimeString(),
                'duration_in_minutes' => $scheduled_out->diffInMinutes($out)
            ));
        }

        return $overtimes;
    }

    public static function getOvertimeInMinutes($dtr, $schedule, $time_option)
    {
        if ($time_option !== 2 && $time_option !== 1) {
            return 0;
        }

        $renderedOfficeSeconds = self::getRenderedSeconds($dtr, $schedule);
        if ($renderedOfficeSeconds === 0) {
            return 0;
        }

        $dayOfWeek = date("l", strtotime($dtr["date"]));
        $dtrDayOfWeek = self::getDayIntOfWeek($dayOfWeek);

        if ($dtr['holiday'] !== null) {
            return self::convertToMinutes($renderedOfficeSeconds);
        }

        if ($dtr['time_off_request'] != null && $dtr['time_off_request']['status'] !== -1 && $dtr['time_off_request']['duration'] === 'whole') {
            return self::convertToMinutes($renderedOfficeSeconds);
        }

        if ($schedule[$dtrDayOfWeek]["type"] === 0) { // REST DAY
            $minimumOfficeSeconds = 0;
        } else {
            $maxIn = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["maxIn"]);
            $maxOut = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["maxOut"]);
            if ($schedule[$dtrDayOfWeek]["maxOutIsNextDay"]) {
                $maxOut->addDay('1');
            }
            $breakStart = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["breakStarts"]);
            if ($schedule[$dtrDayOfWeek]["breakStartsIsNextDay"]) {
                $breakStart->addDay('1');
            }
            $breakEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["date"] . " "  . $schedule[$dtrDayOfWeek]["breakEnds"]);
            if ($schedule[$dtrDayOfWeek]["breakEndsIsNextDay"]) {
                $breakEnd->addDay('1');
            }
            $minimumOfficeSeconds = $maxIn->diffInSeconds($maxOut) - $breakStart->diffInSeconds($breakEnd);
        }

        if ($dtr['time_off_request'] != null && $dtr['time_off_request']['status'] !== -1 && $dtr['time_off_request']['duration'] === 'half') {
            $minimumOfficeSeconds = round($minimumOfficeSeconds / 2);
        }

        $overtime = $renderedOfficeSeconds - $minimumOfficeSeconds;
        return $overtime > 0 ? self::convertToMinutes($overtime) : 0;
    }

    public static function getHoliday($holidays = [], $dtr)
    {
        foreach ($holidays as $holiday) {
            $holiday_date = Carbon::parse($holiday->holiday_date)->startOfDay();
            $dtr_date = Carbon::parse($dtr['date'])->startOfDay();
            if ($holiday->is_recurring) {
                $holiday_date->year($dtr_date->format('Y'));
            }
            if ($dtr_date->equalTo($holiday_date)) {
                return $holiday;
            }
        }
        return null;
    }

    public static function getIsRestday($current_date, $schedule)
    {
        $dayOfWeek = date("l", strtotime($current_date));
        $dtrDayOfWeek = self::getDayIntOfWeek($dayOfWeek);
        return $schedule[$dtrDayOfWeek]['type'] === 0;
    }

    public static function getRenderedSeconds($dtr, $schedule)
    {
        $dayOfWeek = date("l", strtotime($dtr["dtr_date"]));
        $dtrDayOfWeek = self::getDayIntOfWeek($dayOfWeek);
        if (isset($dtr['in']['schedule']) && isset($dtr['out']['schedule']) && isset($dtr['break_start']['schedule']) && isset($dtr['break_end']['schedule'])) {
            $in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['in']["schedule"]);
            $breakStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_start']["schedule"]);
            $breakEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_end']["schedule"]);
            $out = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['out']["schedule"]);

            if ($schedule[$dtrDayOfWeek]["type"] !== 0) { // NOT REST DAY
                $scheduledBreakEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['dtr_date'] . ' ' . $schedule[$dtrDayOfWeek]['breakEnds']);
                if ($schedule[$dtrDayOfWeek]['breakEndsIsNextDay']) {
                    $scheduledBreakEnd->addDays(1);
                }
                $scheduledBreakStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['dtr_date'] . ' ' . $schedule[$dtrDayOfWeek]['breakStarts']);
                if ($schedule[$dtrDayOfWeek]['breakStartsIsNextDay']) {
                    $scheduledBreakStart->addDays(1);
                }

                if ($scheduledBreakStart->lessThan($breakStart)) {
                    $breakStart = $scheduledBreakStart->copy();
                }

                if ($scheduledBreakEnd->greaterThan($breakEnd)) {
                    $breakEnd = $scheduledBreakEnd->copy();
                }
            }

            $maxIn = Carbon::createFromFormat('Y-m-d H:i:s', $dtr["dtr_date"] . " "  . $schedule[$dtrDayOfWeek]["maxIn"]);
            if ($maxIn->diffInSeconds($in) > 7200) {
                $out = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['dtr_date'] . ' ' . $schedule[$dtrDayOfWeek]['maxOut']);
                if ($schedule[$dtrDayOfWeek]['maxOutIsNextDay']) {
                    $out->addDays(1);
                }
            }

            // $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
            // $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

            // $time4 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
            // $time3 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);
            $duration = $breakStart->diffInSeconds($in) + $out->diffInSeconds($breakEnd);
            return self::roundToNearestMinute($duration);
        } else if (isset($dtr['in']['schedule']) && isset($dtr['out']['schedule']) && !isset($dtr['break_start']['schedule']) && !isset($dtr['break_end']['schedule'])) {
            $in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['in']["schedule"]);
            $out = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['out']["schedule"]);

            if ($schedule[$dtrDayOfWeek]["type"] !== 0) { // NOT REST DAY
                $scheduledBreakEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['dtr_date'] . ' ' . $schedule[$dtrDayOfWeek]['breakEnds']);
                if ($schedule[$dtrDayOfWeek]['breakEndsIsNextDay']) {
                    $scheduledBreakEnd->addDays(1);
                }
                $scheduledBreakStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['dtr_date'] . ' ' . $schedule[$dtrDayOfWeek]['breakStarts']);
                if ($schedule[$dtrDayOfWeek]['breakStartsIsNextDay']) {
                    $scheduledBreakStart->addDays(1);
                }

                if ($in->greaterThan($scheduledBreakStart) && $in->lessThan($scheduledBreakEnd)) {
                    $in = $scheduledBreakEnd->copy();
                } else if ($out->greaterThan($scheduledBreakStart) && $out->lessThan($scheduledBreakEnd)) {
                    $out = $scheduledBreakStart->copy();
                }
            }
            // $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
            // $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

            $duration = $out->diffInSeconds($in);
            return self::roundToNearestMinute($duration);
        } else if (isset($dtr['in']['schedule']) && !isset($dtr['out']['schedule']) && isset($dtr['break_start']['schedule']) && !isset($dtr['break_end']['schedule'])) {
            $in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['in']["schedule"]);
            $breakStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_start']["schedule"]);

            // $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakStart);
            // $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $in);

            $duration = $breakStart->diffInSeconds($in);
            return self::roundToNearestMinute($duration);
        } else if (!isset($dtr['in']['schedule']) && isset($dtr['out']['schedule']) && !isset($dtr['break_start']['schedule']) && isset($dtr['break_end']['schedule'])) {
            $out = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['out']["schedule"]);
            $breakEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_end']["schedule"]);

            // $time2 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $out);
            // $time1 = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $breakEnd);

            $duration = $out->diffInSeconds($breakEnd);
            return self::roundToNearestMinute($duration);
        } else {
            return 0;
        }
    }

    public static function getNightDifferentialInMinutes($dtr)
    {
        $night_differential_minutes = 0;
        $morning_night_diff_start = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['date'] . ' 22:00:00');
        $morning_night_diff_start = $morning_night_diff_start->subDays(1);
        $morning_night_diff_end = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['date'] . ' 06:00:00');
        $night_night_diff_start = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['date'] . ' 22:00:00');
        $night_night_diff_end = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['date'] . ' 06:00:00');
        $night_night_diff_end = $night_night_diff_end->addDays(1);
        if (isset($dtr['in']['schedule']) && isset($dtr['out']['schedule']) && isset($dtr['break_start']['schedule']) && isset($dtr['break_end']['schedule'])) {
            $in = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['in']["schedule"]);
            $breakStart = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_start']["schedule"]);
            $breakEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_end']["schedule"]);
            $out = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['out']["schedule"]);
            if (
                $morning_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($morning_night_diff_end) &&
                $breakStart->lessThanOrEqualTo($morning_night_diff_end)
            ) {
                $night_differential_minutes += $in->diffInSeconds($breakStart);
            } else if (
                $morning_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($morning_night_diff_end) &&
                $morning_night_diff_end->lessThan($breakStart)
            ) {
                $night_differential_minutes += $in->diffInSeconds($morning_night_diff_end);
            } else if (
                $night_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($night_night_diff_end) &&
                $breakStart->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $in->diffInSeconds($breakStart);
            } else if (
                $in->lessThan($night_night_diff_start) &&
                $breakStart->greaterThan($night_night_diff_start) &&
                $breakStart->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $night_night_diff_start->diffInSeconds($breakStart);
            }

            if (
                $morning_night_diff_start->lessThanOrEqualTo($breakEnd) &&
                $breakEnd->lessThan($morning_night_diff_end) &&
                $out->lessThanOrEqualTo($morning_night_diff_end)
            ) {
                $night_differential_minutes += $breakEnd->diffInSeconds($out);
            } else if (
                $morning_night_diff_start->lessThanOrEqualTo($breakEnd) &&
                $breakEnd->lessThan($morning_night_diff_end) &&
                $morning_night_diff_end->lessThan($out)
            ) {
                $night_differential_minutes += $breakEnd->diffInSeconds($morning_night_diff_end);
            } else if (
                $night_night_diff_start->lessThanOrEqualTo($breakEnd) &&
                $breakEnd->lessThan($night_night_diff_end) &&
                $out->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $breakEnd->diffInSeconds($out);
            } else if (
                $breakEnd->lessThan($night_night_diff_start) &&
                $out->greaterThan($night_night_diff_start) &&
                $out->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $night_night_diff_start->diffInSeconds($out);
            }
            
            return self::convertToMinutes($night_differential_minutes);
        } else if (isset($dtr['in']['schedule']) && isset($dtr['out']['schedule']) && !isset($dtr['break_start']['schedule']) && !isset($dtr['break_end']['schedule'])) {
            $in = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['in']["schedule"]);
            $out = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['out']["schedule"]);

            if (
                $morning_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($morning_night_diff_end) &&
                $out->lessThanOrEqualTo($morning_night_diff_end)
            ) {
                $night_differential_minutes += $in->diffInSeconds($out);
            } else if (
                $morning_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($morning_night_diff_end) &&
                $morning_night_diff_end->lessThan($out) &&
                $out->lessThan($night_night_diff_start)
            ) {
                $night_differential_minutes += $in->diffInSeconds($morning_night_diff_end);
            } else if (
                $morning_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($morning_night_diff_end) &&
                $out->greaterThan($night_night_diff_start)
            ) {
                $night_differential_minutes += $in->diffInSeconds($morning_night_diff_end);
                $night_differential_minutes += $night_night_diff_start->diffInSeconds($out);
            } else if (
                $night_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($night_night_diff_end) &&
                $out->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $in->diffInSeconds($out);
            } else if (
                $in->lessThan($night_night_diff_start) &&
                $out->greaterThan($night_night_diff_start) &&
                $out->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $night_night_diff_start->diffInSeconds($out);
            }

            return self::convertToMinutes($night_differential_minutes);
        } else if (isset($dtr['in']['schedule']) && !isset($dtr['out']['schedule']) && isset($dtr['break_start']['schedule']) && !isset($dtr['break_end']['schedule'])) {
            $in = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['in']["schedule"]);
            $breakStart = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_start']["schedule"]);

            if (
                $morning_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($morning_night_diff_end) &&
                $breakStart->lessThanOrEqualTo($morning_night_diff_end)
            ) {
                $night_differential_minutes += $in->diffInSeconds($breakStart);
            } else if (
                $morning_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($morning_night_diff_end) &&
                $morning_night_diff_end->lessThan($breakStart)
            ) {
                $night_differential_minutes += $in->diffInSeconds($morning_night_diff_end);
            } else if (
                $night_night_diff_start->lessThanOrEqualTo($in) &&
                $in->lessThan($night_night_diff_end) &&
                $breakStart->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $in->diffInSeconds($breakStart);
            } else if (
                $in->lessThan($night_night_diff_start) &&
                $breakStart->greaterThan($night_night_diff_start) &&
                $breakStart->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $night_night_diff_start->diffInSeconds($breakStart);
            }

            return self::convertToMinutes($night_differential_minutes);
        } else if (!isset($dtr['in']['schedule']) && isset($dtr['out']['schedule']) && !isset($dtr['break_start']['schedule']) && isset($dtr['break_end']['schedule'])) {
            $breakEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['break_end']["schedule"]);
            $out = Carbon::createFromFormat('Y-m-d H:i:s', $dtr['out']["schedule"]);

            if (
                $morning_night_diff_start->lessThanOrEqualTo($breakEnd) &&
                $breakEnd->lessThan($morning_night_diff_end) &&
                $out->lessThanOrEqualTo($morning_night_diff_end)
            ) {
                $night_differential_minutes += $breakEnd->diffInSeconds($out);
            } else if (
                $morning_night_diff_start->lessThanOrEqualTo($breakEnd) &&
                $breakEnd->lessThan($morning_night_diff_end) &&
                $morning_night_diff_end->lessThan($out)
            ) {
                $night_differential_minutes += $breakEnd->diffInSeconds($morning_night_diff_end);
            } else if (
                $night_night_diff_start->lessThanOrEqualTo($breakEnd) &&
                $breakEnd->lessThan($night_night_diff_end) &&
                $out->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $breakEnd->diffInSeconds($out);
            } else if (
                $breakEnd->lessThan($night_night_diff_start) &&
                $out->greaterThan($night_night_diff_start) &&
                $out->lessThanOrEqualTo($night_night_diff_end)
            ) {
                $night_differential_minutes += $night_night_diff_start->diffInSeconds($out);
            }

            return self::convertToMinutes($night_differential_minutes);
        } else {
            return 0;
        }
    }

    public static function getDtrError($dtr)
    {
        if ($dtr['is_restday'] || $dtr['holiday'] != null) {
            return null;
        }

        $response = array();

        if ($dtr['in'] == null) {
            $response["date"] = $dtr["date"];
            $response["resultCode"] = "400_1_2_4";
            $response["result"] = "No IN record";
            return $response;
        } else if ($dtr["out"] == null) {
            $response["date"] = $dtr["date"];
            $response["resultCode"] = "400_1_2_1";
            $response["result"] = "No OUT record";
            return $response;
        }

        $actualIn = Carbon::parse($dtr["in"]["schedule"]);
        $actualOut = Carbon::parse($dtr["out"]["schedule"]);

        if ($actualIn->greaterThanOrEqualTo($actualOut)) {
            $response["date"] = $dtr["date"];
            $response["resultCode"] = "400_1_1_3";
            $response["result"] = "In > Out";
            return $response;
        }

        return null;
    }

    public static function getDayOfWeek($dayInInt)
    {
        switch ($dayInInt) {
            case 0:
                return "SUN";
            case 1:
                return "MON";
            case 2:
                return "TUE";
            case 3:
                return "WED";
            case 4:
                return "THU";
            case 5:
                return "FRI";
            case 6:
                return "SAT";
            default:
                return "";
        }
    }

    public static function getDayIntOfWeek($dayOfWeek)
    {
        switch ($dayOfWeek) {
            case "Sunday":
                $day = "0";
                break;
            case "Monday":
                $day = "1";
                break;
            case "Tuesday":
                $day = "2";
                break;
            case "Wednesday":
                $day = "3";
                break;
            case "Thursday":
                $day = "4";
                break;
            case "Friday":
                $day = "5";
                break;
            case "Saturday":
                $day = "6";
                break;
            default:
                $day = "0";
                break;
        }
        return $day;
    }

    public static function getBiometricsIn($biometricsList, $dtrDate, $endTimesIsNextDay) 
    {
        $ins = $biometricsList->where('type', self::BIOMETRIC_TIME_IN);
        $startRange = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 00:00:00');
        $endRange = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 06:00:00')->addDays(1);
        if ($endTimesIsNextDay != null) {
            $currentDay = $startRange->format('l');
            $endTimesIsNextDayArr = json_decode($endTimesIsNextDay, true);
            if (array_key_exists($currentDay, $endTimesIsNextDayArr) && !$endTimesIsNextDayArr[$currentDay]) {
                $startRange = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 20:00:00')->subDays(1);
                $endRange = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 23:59:59');
            }
        } 
        
        $dtr_entry = $ins->filter(function ($entry) use ($startRange, $endRange) {
            $biometricsEntry = Carbon::createFromFormat('Y-m-d H:i:s', $entry->attendance);
            return $biometricsEntry->gte($startRange) && $biometricsEntry->lte($endRange);
        })->sortBy('attendance')->first();

        if ($dtr_entry) {
            return array(
                'schedule' => $dtr_entry->attendance,
                'from_source' => true,
                'biometrics_id' => $dtr_entry->id
            );
        }

        return null;
    }

    public static function getBiometricsBreak($biometricsList, $dtrDate, $type)
    {
        $sort_ascending = self::BIOMETRIC_BREAK_START === $type;
        $currentDay = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 00:00:00');
        $nextDay = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 10:00:00')->addDays(1);
        $filtered_list = $biometricsList->where('type', $type)->filter(function ($entry) use ($currentDay, $nextDay, $type) {
            $biometricsEntry = Carbon::createFromFormat('Y-m-d H:i:s', $entry->attendance);
            return $biometricsEntry->gte($currentDay) && $biometricsEntry->lte($nextDay);
        });
        if ($sort_ascending) {
            $filtered_list = $filtered_list->sortBy('attendance');
        } else {
            $filtered_list = $filtered_list->sortByDesc('attendance');
        }
        $dtr_entry = $filtered_list->first();
        if ($dtr_entry) {
            return array(
                'schedule' => $dtr_entry->attendance,
                'from_source' => true,
                'biometrics_id' => $dtr_entry->id
            );
        }

        return null;
    }

    public static function getBiometricsOut($biometricsList, $dtrDate, $endTimesIsNextDay) {
        $outs = $biometricsList->where('type', self::BIOMETRIC_TIME_OUT);
        $startRange = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 18:00:00');
        $endRange = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 10:00:00')->addDays(1);
        if ($endTimesIsNextDay != null) {
            $currentDay = $startRange->format('l');
            $endTimesIsNextDayArr = json_decode($endTimesIsNextDay, true);
            if (array_key_exists($currentDay, $endTimesIsNextDayArr) && !$endTimesIsNextDayArr[$currentDay]) {
                $startRange = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 00:00:00');
                $endRange = Carbon::createFromFormat('Y-m-d H:i:s', $dtrDate . ' 23:59:59');
            }
        } 
        
        $dtr_entry = $outs->filter(function ($entry) use ($startRange, $endRange) {
            $biometricsEntry = Carbon::createFromFormat('Y-m-d H:i:s', $entry->attendance);
            return $biometricsEntry->gte($startRange) && $biometricsEntry->lte($endRange);
        })->sortByDesc('attendance')->first();

        if ($dtr_entry) {
            return array(
                'schedule' => $dtr_entry->attendance,
                'from_source' => true,
                'biometrics_id' => $dtr_entry->id
            );
        }

        return null;
    }

    // public static function getBiometricsEntries($employee_id_number, $current_dtr_date) {
    //     $current_dtr_date_str = $current_dtr_date->toDateString();
    //     $window_from_current = $current_dtr_date->copy()->addDay('1');
    //     $biometrics = \App\Biometrics::where('employeeId', '=', $employee_id_number)
    //                 ->where('attendance', '>=', $current_dtr_date_str . ' 04:00')
    //                 ->where('attendance', '<', $window_from_current->toDateString(). ' 10:00') // plus 30 hours
    //                 ->orderBy('attendance', 'asc')->get();
    //     // \Log::info("BIOMETRICS" . $biometrics);
    //     $time_ins = $biometrics->where('type', self::BIOMETRIC_TIME_IN)->collect();
    //     $time_outs = $biometrics->where('type', self::BIOMETRIC_TIME_OUT)->collect();
    //     $break_starts = $biometrics->where('type', self::BIOMETRIC_BREAK_START)->collect();
    //     $break_ends = $biometrics->where('type', self::BIOMETRIC_BREAK_END)->collect();

    //     $time_in1 = self::timeFilter($time_ins, '<', $window_from_current->toDateString(). ' 04:00:00')->first();
    //     $time_in2 = self::timeFilter($time_ins, '>=', $window_from_current->toDateString(). ' 04:00:00')->first();

    //     if($time_in2) {
    //         $time_outs = self::timeFilter($time_outs, '<', $time_in2->attendance);
    //     }
    //     $time_out = $time_outs->sortByDesc('attendance')->first();

    //     if($time_in1) {
    //         $break_starts = self::timeFilter($break_starts, '>', $time_in1->attendance);
    //         $break_ends = self::timeFilter($break_ends, '>', $time_in1->attendance);
    //     }
    //     if($time_out) {
    //         $break_starts = self::timeFilter($break_starts, '<', $time_out->attendance);
    //         $break_ends = self::timeFilter($break_ends, '<', $time_out->attendance);
    //     }

    //     $break_start = $break_starts->first();
    //     $break_end = $break_ends->sortByDesc('attendance')->first();
    //     $out_time_in = $out_time_out = $out_break_start = $out_break_end = null;
    //     if($time_in1) {
    //         $out_time_in = array(
    //             'schedule' => $time_in1->attendance,
    //             'from_source' => true,
    //             'biometrics_id' => $time_in1->id
    //         );
    //     }
    //     if($time_out) {
    //         $out_time_out = array(
    //             'schedule' => $time_out->attendance,
    //             'from_source' => true,
    //             'biometrics_id' => $time_out->id
    //         );
    //     }
    //     if($break_start) {
    //         $out_break_start = array(
    //             'schedule' => $break_start->attendance,
    //             'from_source' => true,
    //             'biometrics_id' => $break_start->id
    //         );
    //     }
    //     if($break_end) {
    //         $out_break_end = array(
    //             'schedule' => $break_end->attendance,
    //             'from_source' => true,
    //             'biometrics_id' => $break_end->id
    //         );
    //     }
    //     return array(
    //         'in' => $out_time_in,
    //         'break_start' => $out_break_start,
    //         'break_end' => $out_break_end,
    //         'out' => $out_time_out
    //     );
    // }

    // private static function timeFilter($coll, $comparator, $datetime_str) {
    //     return $coll->filter(function($item) use ($comparator, $datetime_str) {
    //         if ($comparator == '<') {
    //             return $item->attendance < Carbon::parse($datetime_str);
    //         }
    //         else if ($comparator == '<=') {
    //             return $item->attendance <= Carbon::parse($datetime_str);
    //         }
    //         else if ($comparator == '>') {
    //             return $item->attendance > Carbon::parse($datetime_str);
    //         }
    //         else if ($comparator == '>=') {
    //             return $item->attendance > Carbon::parse($datetime_str);
    //         }
    //     });

    // }

    // http://www.csc.gov.ph/phocadownload//MC1999/mc14s1999.pdf
    public static function convertLeavePointsToTime($leavePoints)
    {
        $minArr = DayFractions::MIN;
        $hourArr = DayFractions::HOUR;
        $hours = 0;
        $minutes = 0;

        $hourIndex = count($hourArr) - 1;
        while ($hourIndex) {
            // if ($leavePoints >= $hourArr[$hourIndex])
            if (bccomp($leavePoints, $hourArr[$hourIndex], DayFractions::SCALE) >= 0) {
                $leavePoints = bcsub($leavePoints, $hourArr[$hourIndex], DayFractions::SCALE);
                $hours += $hourIndex;
            } else {
                $hourIndex -= 1;
            }
        }
        $minuteIndex = count($minArr) - 1;
        while ($minuteIndex) {
            // if ($leavePoints >= $minArr[$minuteIndex])
            if (bccomp($leavePoints, $minArr[$minuteIndex], DayFractions::SCALE) >= 0) {
                $leavePoints = bcsub($leavePoints, $minArr[$minuteIndex], DayFractions::SCALE);
                $minutes += $minuteIndex;
            } else {
                $minuteIndex -= 1;
            }
        }
        return CarbonInterval::hours($hours)->minutes($minutes);
    }

    public static function convertTimeToLeavePoints($hour, $min)
    {
        return bcadd(DayFractions::HOUR[$hour], DayFractions::MIN[$min], DayFractions::SCALE);
    }

    public static function convertToMinutes($seconds)
    {
        return self::roundToNearestMinute($seconds) / 60;
    }

    public static function roundToNearestMinute($seconds)
    {
        return intval($seconds / 60) * 60;
    }
}
