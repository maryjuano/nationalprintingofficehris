<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>

    table {
        border-left: 0.01em solid #ccc;
        border-right:  0.01em solid #ccc;
        border-top: 0.01em solid #ccc;
        border-bottom:  0.01em solid #ccc;
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
        padding: 8
    }

    .span-small {
        font-size: 10px
    }

    .span-medium {
        font-size: 11px
    }

    .span-normal {
        font-size: 11px
    }

    .indented {
        padding-left: 30px;
        padding-right: 20px;
        border: none;
    }

    .signature {
        padding-left: 320px;
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
        margin : 60px 80px 0px 80px;
        font-size: 14px;
        text-align: justify;
    }
    p {
        text-align: justify;
    }
    div.page_break + div.page_break{
        page-break-before: always;
    }
    </style>

    <body>
        @foreach ($nosas as $nosa)
        <div class="page_break">

            @php
                $suffix = $nosa->employee->personal_information->ext == 'NA' ? '' : ' ' . $nosa->employee->personal_information->ext;
                $name = ($nosa->employee->personal_information->gender === 1 ? 'MR. ' : 'MS. ')
                    . $nosa->employee->personal_information->first_name
                    . ' ' . $nosa->employee->personal_information->middle_name
                    . ' ' . $nosa->employee->personal_information->last_name
                    . $suffix;
                $salutation = $nosa->employee->personal_information->gender === 1 ? 'Sir:' : 'Madam:';

            @endphp


            <p><br /></p>
            <p style="text-align: right">
                {{$generated_date->isoFormat('MMMM DD, YYYY')}}
            </p>
            <p style="text-align: center; font-size: 20px; text-decoration: underline;">NOTICE OF SALARY ADJUSTMENT<br/></p>
            <p><br /></p>
            <p>
                <b>{{$name}}</b> <br />
                NATIONAL PRINTING OFFICE <br />
                EDSA cor. NPO Rd., Diliman, Quezon City
            </p>
            <p>
                {{$salutation}}
            </p>
            <p>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                Pursuant to <b>{{ $extra_data->circular }} </b>, dated {{ $extra_data->circular_date }}, implementing <b>{{ $extra_data->executive_order }}</b> your salary  is hereby adjusted effective <b>{{$nosa->effectivity_date->isoFormat('MMMM DD, YYYY')}}</b> as follows:
            </p>
            <table class="indented">
                <tr>
                    <td width="80%">1. Adjusted monthly basic salary effective {{$nosa->effectivity_date->isoFormat('MMMM DD, YYYY')}},<br />under the new Salary Schedule; <b>SG: {{$nosa->new_grade}}, Step: {{$nosa->new_step}}</b></td>
                    <td style="text-align: right;">{{'P ' . number_format($nosa->new_rate,2,'.', ',')}}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                </tr>
                <tr>
                    <td>2. Actual monthly basic salary as of<br /><b>{{$nosa->previous_effectivity_date->isoFormat('MMMM DD, YYYY')}}; SG: {{$nosa->old_grade}}, Step: {{$nosa->old_step}}</b></td>
                    <td style="text-align: right;">{{'P ' . number_format($nosa->old_rate,2,'.', ',')}}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                </tr>
                <tr>
                    <td>3. Monthly salary adjustment effective<br /><b>{{$nosa->effectivity_date->isoFormat('MMMM DD, YYYY')}} (differential rate)</b></td>
                    <td style="text-align: right;">{{'P ' . number_format($nosa->new_rate - $nosa->old_rate, 2,'.', ',' )}}</td>
                </tr>
            </table>

            <p>
                It is understood that this salary adjustment is subject to review and post-audit, and to appropriate re-adjustment and refund if found not in order.
            </p>

            <p class="signature">
                <br />
                Very truly yours,
                <br /><br /><br />
                <b>{{$signatory->signatories[0]['name'] ?? 'Name#1'}}</b>
                <br />{{$signatory->signatories[0]['title'] ?? 'Title#1'}}
                <br /><br />
            </p>

            <p>
                <b>
                    Position Title: {{$nosa->new_position->position_name}} <br />
                    Salary Grade: {{$nosa->new_grade}}<br />
                    Item No. / Unique Item No., FY {{$generated_date->isoFormat('YYYY')}} Personal Services Itemization <br />
                    and/or Plantilla of Personnel: {{$nosa->new_position->item_number}}
                </b>
            </p>

            <p style="font-size: 10px">
                Copy furnished: <br />
                GSIS <br />
                201 File
            </p>
            <div style="clear: both;"></div>
        </div>
        @endforeach
    </body>
</html>

