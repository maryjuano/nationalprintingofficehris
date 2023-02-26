<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<style>
    table {
        /* border-left: 0.01em solid #ccc;
        border-right: 0.01em solid #ccc;
        border-top: 0.01em solid #ccc;
        border-bottom: 0.01em solid #ccc; */
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
        /* padding: 8; */
        border-collapse: collapse;
    }

    table.bordered, th.bordered, td.bordered {
        border: 1px solid black;
    }

    td {
        vertical-align: top;
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

    .blank {
        border-bottom: 1px solid black;
    }
</style>

<body>
    <div class="page_break">
        <div>
            <table style="border: 0px">
                <tbody>
                    <tr >
                        <td style="width: 10%; "><img src="{{ public_path() . '/images/logo.png' }}" style="height: 60px; width: 60px; margin-left: 5px; margin-right: 0; padding: 0 0" /></td>
                        <td style="width: 60%;">
                            <span class="span_label" style=" font-size: 12px">Republic of the Philippines</span><br />
                            <b style=" font-size: 20px; ">National Printing Office</b><br />
                            <span class="span_label" style=" font-size: 12px">Presidential Communications Operations Office</span>
                        </td>

                    </tr>
                </tbody>
            </table>
            <br />
            <p style="text-align: center; font-size: 18px;"><b>APPLICATION FOR AVAILMENT OF<br/>COMPENSATORY TIME OFF (CTO)</b></p>

            <table>
                <tr>
                    <td><b>Name: </b></td>
                    <td class="blank" width="33%">{{$employee->name}}</td>
                    <td class="blank" width="33%"></td>
                    <td class="blank" width="33%"></td>
                </tr>
                <tr>
                    <td><b></b></td>
                    <td style="text-align: center"></td>
                    <td style="text-align: center"></td>
                    <td style="text-align: center"></td>
                </tr>
            </table>

            <table>
                <tr>
                    <td>Position</td>
                    <td class="blank" width="80%">{{$employee->employment_and_compensation->position->position_name}}</td>
                </tr>
                <tr>
                    <td>Office/Division</td>
                    <td class="blank" width="80%">{{$employee->employment_and_compensation->department->department_name}}</td>
                </tr>
            </table>

            <p style="text-align: center; font-size: 18px;"><b>DETAILS OF APPLICATION</b></p>
            <table>
                <tr>
                    <td width="18%">Number of Hours Applied for</td>
                    <td class="blank" width="30%" style="vertical-align: bottom">{{number_format($time_off_request->total_days, 2)}}</td>
                    <td width="4%"></td>
                    <td width="18%" style="vertical-align: bottom">Inclusive Dates</td>
                    <td class="blank" width="30%" style="vertical-align: bottom">
                        {{$time_off_request->start_detail->time_off_date}} -
                                    {{$time_off_request->end_detail->time_off_date}}
                    </td>
                </tr>
            </table>
            <br /><br /><br />
            <b>Recommending Approval:</b>
            <br /><br />
            <br /><br />
            <table>
                <tr>
                    <td class="blank" width="33%"></td>
                    <td width="33%"></td>
                    <td class="blank" width="33%"></td>
                </tr>
                <tr>
                    <td width="33%" style="text-align: center">(Division Chief)</td>
                    <td width="33%"></td>
                    <td width="33%" style="text-align: center">(Signature of Applicant)</td>
                </tr>
            </table>

            <p style="text-align: center; font-size: 18px;"><b>DETAILS OF ACTION ON APPLICATION</b></p>
            <table>
                <tr>
                    <td width="25%"></td>
                    <td width="25%"></td>
                    <td width="10%"></td>
                    <td width="40%"></td>
                </tr>
                <tr>
                    <td colspan=2><b>a. Certification of Compensatory Overtime</b></td>
                    <td colspan=2><b>b. Approval</b></td>
                </tr>
                <tr>
                    <td>Credit COC as of</td>
                    <td class="blank">{{$time_off_request->created_at->isoFormat('MMMM DD, YYYY')}}</td>
                    <td><input style="margin-top: 0px; font-size: 20px; float: right" type="checkbox"
                        {{$time_off_request->status == 1? 'checked': ''}}
                        /></td>
                    <td><b>Approval</b></td>
                </tr>
                <tr>
                    <td>Number of Hours Earned</td>
                    <td class="blank" style="vertical-align: bottom">{{number_format($time_off_request->balance_at_request_time, 2)}}</td>
                    <td><input style="margin-top: 0px; font-size: 20px; float: right" type="checkbox"
                        {{$time_off_request->status == -1? 'checked': ''}}/></td>
                    <td class="blank"><b>Disapproval Due to</b></td>
                </tr>
                <tr>
                    <td></td>
                    <td class=""></td>
                    <td></td>
                    <td class="blank">&nbsp;</td>
                </tr>
            </table>
            <br /><br /><br />
            <table>
                <tr>
                    <td class="blank" width="33%" style="text-align: center; font-size: 14px;"></td>
                    <td width="33%"></td>
                    <td class="blank" width="33%"></td>
                </tr>
                <tr>
                    <td class="" width="33%" style="text-align: center; ">Chief, HRM Section</td>
                    <td width="17%"></td>
                    <td class="" width="50%" style="text-align: center">Director IV / Authorized Representative</td>
                </tr>
            </table>
        </div>
    </div>
</body>
