<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<style>
    table {
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
        border-collapse: collapse;
    }

    table.bordered {
        border: 1px solid black;
    }

    td.no-left-border {
        border: 1px solid black;
        border-left: none;
    }

    td.no-right-border {
        border: 1px solid black;
        border-right: none;
    }
    
    th.bordered, td.bordered {
        border: 1px solid black;
    }

    td.bottom-bordered {
        border-bottom: 1px solid black;
    }

    td.top-bordered {
        border-top: 1px solid black;
    }

    table.form {
        table-layout: fixed;
    }

    .header-row {
        padding: 5px;
    }

    p.header-item {
        display: table;
        width: 300px;
        margin: 0px;
    }

    td {
        vertical-align: top;
    }

    p.form-item-left {
        display: table;
        width: 100%;
        margin: 0px;
    }

    p.form-item-right {
        display: table;
        width: 100%;
        margin: 0px;
    }

    @font-face {
        font-family: 'Helvetica';
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url("https://fonts.googleapis.com/css?family=Baloo+Chettan+2&display=swap");
    }

    body {
        font-family: Helvetica, sans-serif;
        line-height: 24px;
    }

    .span_label {
        font-size: 12px;
        line-height: 12px;
    }
    div.page_break + div.page_break{
        page-break-before: always;
    }
</style>

@php
    $vl_days = $time_off_request->time_off_type->time_off_code == 'VL' ? $time_off_request->total_days: 0;
    $sl_days = $time_off_request->time_off_type->time_off_code == 'SL' ? $time_off_request->total_days: 0;
    $total_days = $vl_days + $sl_days;

@endphp

<body>
    <div class="page_break">
        <div>
            <table style="border: 0px">
                <tr >
                    <td style="width: 10%; "><img src="{{ public_path() . '/images/logo.png' }}" style="height: 60px; width: 60px; margin-left: 5px; margin-right: 0; padding: 0 0" /></td>
                    <td style="width: 60%;">
                        <span class="span_label" style=" font-size: 12px">Republic of the Philippines</span><br />
                        <b style=" font-size: 20px; ">National Printing Office</b><br />
                        <span class="span_label" style=" font-size: 12px">Presidential Communications Operations Office</span>
                    </td>
                </tr>
            </table>
            <p style="text-align: center; font-size: 18px;"><b>APPLICATION FOR LEAVE</b></p>
            <table class="bordered form">
                <tr>
                    <td class="header-row bottom-bordered">
                        <p class="header-item">
                            <span style="display: table-cell; width: 150px;"><b>Date of Filing :</b></span>
                            <span style="display: table-cell; border-bottom: 1px solid black;">{{$time_off_request->created_at->isoFormat('MMMM DD, YYYY')}}</span>
                        </p>
                    </td>
                    <td class="header-row bottom-bordered">
                        <p class="header-item header-item-right">
                            <span style="text-align: right; display: table-cell; width: 150px;"><b>Application No. :</b></span>
                            <span style="display: table-cell; border-bottom: 1px solid black;"></span>
                        </p>
                        <p class="header-item">
                            <span style="text-align: right; display: table-cell; width: 150px;"><b>Employee No. :</b></span>
                            <span style="display: table-cell; border-bottom: 1px solid black;"></span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 2px; padding-bottom: 10px; border-right: 1px solid black">
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 140px;"><b>Employee Name </b></span>
                            <span style="display: table-cell; border-bottom: 1px solid black;">{{$employee->name}}</span>
                        </p>
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 140px;">Position</span>
                            <span style="display: table-cell; border-bottom: 1px solid black;">{{$employee->employment_and_compensation->position->position_name}}</span>
                        </p>
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 140px;">Division/Section</span>
                            <span style="display: table-cell; border-bottom: 1px solid black;">{{$employee->employment_and_compensation->department->department_name}}</span>
                        </p>
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 140px;">Monthly Salary</span>
                            <span style="display: table-cell; border-bottom: 1px solid black;">{{number_format($employee->employment_and_compensation->salary->step[$employee->employment_and_compensation->step_increment], 2)}}</span>
                        </p>
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 140px;">
                                <span>No. of Working</span>
                                <br>
                                <span>days applied for</span>
                            </span>
                            <span style="display: table-cell; border-bottom: 1px solid black;">
                            <br><span>{{$time_off_request->getTotalDaysStr()}}</span>
                            </span>
                        </p>
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 65%; font-weight: bold">Inclusive Date:</span>
                            <span style="display: table-cell;">
                                &nbsp;
                            </span>
                        </p>
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 65%; text-align: center;">{{$time_off_request->getInclusiveDates()}}</span>
                            <span style="display: table-cell; border-bottom: 1px solid black;"></span>
                        </p>
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 65%;">&nbsp;</span>
                            <span style="display: table-cell; text-align: center">Signature</span>
                        </p>
                        <p class="form-item-left">
                            <span style="display: table-cell; width: 140px;"><b>Commutation : </b></span>
                            <span style="display: table-cell;"></span>
                        </p>
                        <p class="form-item-left" style="padding: 0px 10px;">
                            <span style="display: table-cell; width: 50%"><input style="margin-top: 6px; font-size: 20px;" type="checkbox" /> Requested</span>
                            <span style="display: table-cell; width: 50%"><input style="margin-top: 6px; font-size: 20px;" type="checkbox" /> Not Requested</span>
                        </p>
                    </td>
                    <td style="padding: 0px">
                        <table>
                            <tr>
                                <td style="padding: 6px; color: white; background-color: black; text-align: center">
                                    Type of Leave
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px">
                                    <input style="margin-top: 10px; font-size: 24px;" type="checkbox"
                                        {{$time_off_request->time_off_type->time_off_code == 'VL' ? 'checked': ''}}
                                    /> Vacation
                                    <br/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<input style="margin-top: 6px; font-size: 20px;" type="checkbox"
                                        {{
                                            ($time_off_request->time_off_type->time_off_code == 'VL' && $time_off_request->is_within_Philippines == 1) ?
                                            'checked': ''
                                        }}
                                    /> Within the Philippines<br/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<input style="margin-top: 6px; font-size: 20px;" type="checkbox"
                                    {{
                                        ($time_off_request->time_off_type->time_off_code == 'VL' && $time_off_request->is_within_Philippines == 0) ?
                                        'checked': ''
                                    }}
                                    /> Abroad (specify) <u>{{
                                        ($time_off_request->time_off_type->time_off_code == 'VL' && $time_off_request->is_within_Philippines == 0) ?
                                        ' ' . $time_off_request->location . ' ': '______________'
                                    }}</u> <br />
                                    <input style="margin-top: 10px; font-size: 24px;" type="checkbox"
                                    {{
                                        ($time_off_request->time_off_type->time_off_code == 'SL') ?
                                        'checked': ''
                                    }} /> Sick
                                    <br/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<input style="margin-top: 6px; font-size: 20px;" type="checkbox" /> Outpatient (specify) _____________<br/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<input style="margin-top: 6px; font-size: 20px;" type="checkbox" /> In-Hospital ____________________<br />
                                    <input style="margin-top: 10px; font-size: 24px;" type="checkbox"
                                    {{
                                        ($time_off_request->time_off_type->time_off_code == 'SL' || $time_off_request->time_off_type->time_off_code == 'VL') ?
                                        '': 'checked'
                                    }}
                                    /> Others (specify) <u>{{
                                        ($time_off_request->time_off_type->time_off_code == 'SL' || $time_off_request->time_off_type->time_off_code == 'VL') ?
                                        '__________________': ' ' . $time_off_request->time_off_type->time_off_type . ' '
                                    }}</u>

                                    <br/>

                                </td>

                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding 0px; padding-bottom: 20px; border-right: 1px solid black">
                        <div style="padding: 6px; color: white; background-color: black; text-align: center">
                            For HRM Use Only
                        </div>
                        <table style="margin-top: 5px">
                            <tr>
                                <td width="120" class="no-left-border" style="text-align: center; font-size: 0.9em;">Leave Credits as of</td>
                                <td class="bordered" style="text-align: center; font-size: 0.9em;">Vacation Leave</td>
                                <td class="bordered" style="text-align: center; font-size: 0.9em;">Sick Leave</td>
                                <td class="no-right-border" style="text-align: center; font-size: 0.9em;">Total</td>
                            </tr>
                            <tr>
                                <td width="120" class="no-left-border" style="text-align: center; font-size: 0.9em;">{{$time_off_request->created_at->isoFormat('MMMM DD, YYYY')}}</td>
                                <td class="bordered" style="text-align: center; font-size: 0.9em;">&nbsp;{{number_format($time_off_balance['VL']->balance, 3, '.', '')}}</td>
                                <td class="bordered" style="text-align: center; font-size: 0.9em;">&nbsp;{{number_format($time_off_balance['SL']->balance, 3, '.', '')}}</td>
                                <td class="no-right-border" style="text-align: center; font-size: 0.9em;">&nbsp;{{number_format($time_off_balance['VL']->balance + $time_off_balance['SL']->balance, 3, '.', '')}}</td>
                            </tr>
                            <tr>
                                <td width="120" class="no-left-border" style="text-align: left; font-size: 0.9em;">Less: This Leave</td>
                                <td class="bordered" style="text-align: center; font-size: 0.9em;">&nbsp;{{$vl_days}}</td>
                                <td class="bordered" style="text-align: center; font-size: 0.9em;">&nbsp;{{$sl_days}}</td>
                                <td class="no-right-border" style="text-align: center; font-size: 0.9em;">&nbsp;{{$total_days}}</td>
                            </tr>
                            <tr>
                                <td width="120" class="no-left-border" style="text-align: left; font-size: 0.9em;">Leave Balance</td>
                                <td class="bordered" style="text-align: center; font-size: 0.9em;">&nbsp;{{number_format($time_off_balance['VL']->balance - $vl_days, 3, '.', '') }}</td>
                                <td class="bordered" style="text-align: center; font-size: 0.9em;">&nbsp;{{number_format($time_off_balance['SL']->balance - $sl_days, 3, '.', '') }}</td>
                                <td class="no-right-border" style="text-align: center; font-size: 0.9em;">&nbsp;{{number_format($time_off_balance['VL']->balance + $time_off_balance['SL']->balance - $total_days, 3, '.', '')}}</td>
                            </tr>
                        </table>
                        <p class="form-item-left" style="padding: 5px">
                            <span style="display: table-cell;">
                                <input 
                                    style="margin-top: 6px; font-size: 20px;" 
                                    type="checkbox"
                                    {{$time_off_request->status == 1? 'checked': ''}}
                                /> 
                                Approval for:
                            </span>
                            <span style="display: table-cell; ">
                                <input 
                                    style="margin-top: 6px; font-size: 20px;" 
                                    type="checkbox"
                                    {{$time_off_request->status == -1? 'checked': ''}}
                                /> 
                                Disapproved due to:
                            </span>
                        </p>
                        <p class="form-item-left" style="padding: 0px 5px">
                            <span style="display: table-cell; width: 50%">
                                _____Day/s with Pay
                            </span>
                            <span style="display: table-cell; "></span>
                        </p>
                        <p class="form-item-left" style="padding: 0px 5px">
                            <span style="display: table-cell; width: 50%">
                                _____Day/s w/o Pay
                            </span>
                            <span style="display: table-cell; "></span>
                        </p>
                    </td>
                    <td style="padding: 0px">
                        <table>
                            <tr>
                                <td style="padding: 6px; color: white; background-color: black; text-align: center">
                                    Action on Application
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span style="margin-left: 5px"><b>&nbsp;Recommending:</b></span>
                                    <table style="margin: 5px; ">
                                        <tr>
                                            <td style="height: 40px; border: 1px solid black;"></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <input style="margin-top: 10px; font-size: 24px;" type="checkbox"/> Approval
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <input style="margin-top: 10px; font-size: 24px;" type="checkbox"/> Disapproval due to
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border-bottom: 1px solid black; height: 20px">

                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="top-bordered" style="padding: 0px 5px">Certified By: </td>
                    <td class="top-bordered"></td>
                </tr>
                <tr>
                    <td style="text-align: center;"> _________________________</td>
                    <td style="text-align: center"> _________________________</td>
                </tr>
                <tr>
                    <td></td>
                    <td style="text-align: center;"><span style="font-size: 10px; line-height: 12px;">Supervising Administrative Office</span></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="page_break">
        <br /><br /><br /><br />


        <p style="text-align: center; font-size: 18px;"><b>INSTRUCTIONS</b></p>
        <table style="padding: 20px 50px">
            <tr>
                <td>1.</td>
                <td>Application for vacation or sick leave for one full day or more shall be made on this Form and to be accomplished at least in duplicate.<br /><br /></td>
            </tr>
            <tr>
                <td>2.</td>
                <td>Application for vacation leave shall be filed in advance or whenever possible five (5) days before going on such leave.<br /><br /></td>
            </tr>
            <tr>
                <td>3.</td>
                <td>Application for sick leave filed in advance or exceeding five (5) days shall be accompanied by a medical certificate.
                    In case medical consulataion was not availed of, an affidavit should be executed by the applicant.<br /><br />
                </td>
            </tr>
            <tr>
                <td>4.</td>
                <td>An employee which is absent without approved leave shall not be entitled to receive his salary corresponding to the period of his unauthorized leave of absence.<br /><br /></td>
            </tr>
            <tr>
                <td>5.</td>
                <td>An application for leave of absence for thirty (30) calendar days or more shall be accomplished by a clearance from money and property accountabilities.<br /><br /></td>
            </tr>
        </table>
    </div>
</body>
