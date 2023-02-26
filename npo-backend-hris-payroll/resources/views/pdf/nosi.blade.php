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
        padding-right: 30px;
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
        margin : 80px 80px 0px 80px;
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
        @foreach ($nosis as $nosi)
        <div class="page_break">

            @php
                $suffix = $nosi->employee->personal_information->ext == 'NA' ? '' : ' ' . $nosi->employee->personal_information->ext;
                $name = ($nosi->employee->personal_information->gender === 1 ? 'MR. ' : 'MS. ')
                    . $nosi->employee->personal_information->first_name
                    . ' ' . $nosi->employee->personal_information->middle_name
                    . ' ' . $nosi->employee->personal_information->last_name
                    . $suffix;
                $salutation = $nosi->employee->personal_information->gender === 1 ? 'Sir:' : 'Madam:';
            @endphp


            <p style="text-align: right">
                {{$generated_date->isoFormat('MMMM DD, YYYY')}}
            </p>
            <p style="text-align: center; font-size: 20px; text-decoration: underline;">NOTICE OF STEP INCREMENT<br/></p>
            <p><br /></p>
            <p></p>
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
                Pursuant to Joint <b>Civil Service Commission </b> (CSC) and <b> Department of Budget and Management </b> (DBM) <b><i>Circular No. 1 s. 1990 Implementing Section 13(c) of R.A. No. 6758</i></b>, your salary as <b>{{$nosi->position->position_name}} (SG-{{$nosi->position->salary_grade}})</b> is hereby adjusted effective <b>{{$nosi->effectivity_date->isoFormat('MMMM DD, YYYY')}}</b> as shown below:
            </p>
            <table class="indented">
                <tr>
                    <td>ACTUAL BASIC MONTHLY SALARY (Old Rate)</td>
                    <td style="text-align: right;">{{'P ' . number_format($nosi->old_rate,2,'.', ',')}}</td>
                </tr>
                <tr>
                    <td>NEW BASIC SALARY ADJUSTMENT
                        <br />
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        As of <b>{{$nosi->effectivity_date->isoFormat('MMMM DD, YYYY')}}</b>
                    </td>
                    <td style="text-align: right;">{{'P ' . number_format($nosi->new_rate,2,'.', ',')}}</td>
                </tr>
                <tr>
                    <td>a) Differential Rate</td>
                    <td style="text-align: right;">{{'P ' . number_format(($nosi->new_rate - $nosi->old_rate),2,'.', ',')}}</td>
                </tr>
                <tr>
                    <td>b) Length of Service <b>(Step {{$nosi->new_step}})</b></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Total Basic Monthly Salary ..............</td>
                    <td style="text-align: right;"><b>{{'P ' . number_format($nosi->new_rate,2,'.', ',')}}</b></td>
                </tr>
            </table>

            <p>
                The step increment is subject to review and post-audit by the <b>Department of Budget and Management</b> and subject to readjustment and refund if found not in order.
            </p>

            <p class="signature">
                <br />
                Very truly yours,
                <br /><br /><br />
                <b>{{$signatory->signatories[0]['name'] ?? 'Name#1'}}</b>
                <br />{{$signatory->signatories[0]['title'] ?? 'Title#1'}}
            </p>
            <div style="clear: both;"></div>
        </div>
        @endforeach
    </body>
</html>

