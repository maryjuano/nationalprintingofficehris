<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>

     .table {
        border-left: 0.01em solid #ccc;
        border-right:  0.01em solid #ccc;
        border-top: 0.01em solid #ccc;
        border-bottom:  0.01em solid #ccc;
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
    }

    .table td {
        font-size: 9px;
    }

    .div-vertical {
        height: 150px;
        overflow: visible;
        position: absolute;
        text-align: left;
        transform: rotate(270deg);
        transform-origin: left bottom 0;
        top: 600px;
        left: 110px;
        width: 750px;
        font-size: 15px
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
      }

    </style>
        <body >
          <div style=" display: flex">
            <div style="width: 20%;">
                <div class="div-vertical">
                    <p><span>GSIS Policy NO.</span>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <span>MEMBER'S SERVICE RECORD </span>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <span style="font-size: 10px">MSM-01-02 </span>
                    <br/>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; <span style="font-size: 11px">(ORIGINAL)</span>
                    <br/>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  <span style="font-size: 9px">(To be submitted to GSIS)</span>
                    </p>
                    <p style="font-size: 14px">Name: <span style="text-decoration: underline;"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; {{$vals['employee_details']['last_name']}} &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; {{$vals['employee_details']['first_name']}} &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  {{$vals['employee_details']['middle_name']}} &nbsp; &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp; </span> &nbsp; <span>Sex:</span> <span style="text-decoration: underline;">   &nbsp; &nbsp;  {{$vals['employee_details']['gender']}} &nbsp;  </span> &nbsp;<span>Civil Status:</span><span style="text-decoration: underline;">  &nbsp; &nbsp; {{$vals['employee_details']['civil_status']}} &nbsp; &nbsp; </span>
                    <br/>
                    <span style="font-size: 9px"><i> (PRINT or TYPE)  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (Surname) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (Given)&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;(Middle Name)</i></span>
                    <br/>
                    <p style="font-size: 14px">Address:<span style="text-decoration: underline;"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp; &nbsp; &nbsp;  &nbsp;  &nbsp;  &nbsp; &nbsp;  &nbsp; &nbsp;  &nbsp; &nbsp;  &nbsp; &nbsp;  &nbsp;  </span> &nbsp;  Birth:<span style="text-decoration: underline;"> &nbsp; &nbsp;  &nbsp;  &nbsp;  &nbsp; &nbsp;  {{$vals['employee_details']['date_of_birth']}}  &nbsp; &nbsp;  &nbsp; {{$vals['employee_details']['place_of_birth']}} &nbsp; &nbsp;   &nbsp; &nbsp;  &nbsp; &nbsp; </span>
                    <br/>
                    <span style="font-size: 9px"><i>  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp;  &nbsp;  &nbsp;(Station or Place of Assignment)&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  (Date)&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;(Place)</i></span>
                    </p>
                </div>
            </div>

            <div style="width: 100%; ; margin-left: 150px;">
                <table class="table" style="border-collapse: collapse; text-align: center; font-size: 12px" border=1>
                    <tr>
                        <td style="width: 20%" colspan="2">SERVICE <br/> (Inclusive Dates)</td>
                        <td style="width: 25%" colspan="3">RECORD OF APPOINMENT</td>
                        <td style="width: 10%" rowspan="2" >OFFICE ENTITY <br/>OF <br/>DIVISION</td>
                        <td style="width: 5%" rowspan="2"  >3 <br/> BRANCH</td>
                        <td style="width: 8%" rowspan="2" >LEAVE OF <br/>ABSENCES <br/>W/O PAY</td>
                        <td style="width: 10%" colspan="2"> 4 <br/>SEPARATION</td>
                        <td style="width: 12%" rowspan="2" >REMARKS</td>
                    </tr>
                    <tr>
                        <td style="width: 10%">FROM </td>
                        <td style="width: 10%">TO </td>
                        <td style="width: 10%">DESIGNATION</td>
                        <td style="width: 10%">1 <br/>Status</td>
                        <td style="width: 5%">2 <br/>Salary</td>
                        <td style="width: 5%"> Date</td>
                        <td style="width: 5%"> Cause</td>
                    </tr>

                    @foreach($vals['employee_position_history'] as $history)
                    <tr>
                        <td style="width: 10%">&nbsp; {{$history['start_date']}}</td>
                        <td style="width: 10%"> {{$history['end_date']}}</td>
                        <td style="width: 10%"> {{$history['position']}}</td>
                        <td style="width: 10%">{{$history['status']}}</td>
                        <td style="width: 5%">{{$history['salary']}}</td>
                        <td style="width: 10%">{{$history['section']}}</td>
                        <td style="width: 5%">{{$history['branch']}}</td>
                        <td style="width: 8%">{{$history['start_LWOP'] . '-' . $history['end_LWOP']}}</td>
                        <td style="width: 5%"> {{$history['separation_date']}}</td>
                        <td style="width: 5%">{{$history['separation_cause']}} </td>
                        <td style="width: 12%">{{$history['remarks']}}</td>
                    </tr>
                    @endforeach
                </table>

                <table class="table" style="border-collapse: collapse; text-align: center;">
                    <tr>
                        <td style="width: 100%; fons-size: 16px;">GOVERNMENT SERVICE INSURANCE SYSTEM</td>
                    </tr>
                </table>
            </div>

            <div style=" page-break-after: after;">
            <div style=" display: flex">
            <div style="width: 20%;">
                <div class="div-vertical">
                    <p>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  <span>EXPLANATION </span>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    <br/>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    <span style="font-size: 12px">
                    1. Probational, permanent, temporary, emergency or substitute.
                    <br/>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    2. Either daily, monthly or annual, whatever the appoinment shows as the basic salary excluding bonuses <br/>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; or allowances or supplementary salaries abroad.
                    <br/>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    3. Municipal city, provincial or national depending on which entity pays salary.
                    <br/>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    4. Registration, retirement (state what law), dropped, laid off, fismissal, suspension, expiration of appoinment State effective date.
                    </span>
                    </p>
                </div>
            </div>

            <div style="width: 100%; ; margin-left: 150px;">
            <p style="font-size: 10px; text-align: right">(Do not fill)</p>

                <table class="table" style="border-collapse: collapse; text-align: center; font-size: 12px" border=1>
                    <tr>
                        <td style="width: 20%" colspan="2">SERVICE <br/> (Inclusive Dates)</td>
                        <td style="width: 25%" colspan="3">RECORD OF APPOINMENT</td>
                        <td style="width: 10%" rowspan="2">OFFICE ENTITY<br/> OF <br/>DIVISION</td>
                        <td style="width: 5%" rowspan="2">3 <br/>BRANCH</td>
                        <td style="width: 8%" rowspan="2">LEAVE OF <br/> ABSENCE <br/>W/O PAY</td>
                        <td style="width: 10%" colspan="2"> 4 <br/>SEPARATION</td>
                        <td style="width: 12%" rowspan="2">REMARKS</td>
                    </tr>
                    <tr>
                        <td style="width: 10%">FROM </td>
                        <td style="width: 10%">TO </td>
                        <td style="width: 10%">DESIGNATION</td>
                        <td style="width: 10%">1 <br/>Status</td>
                        <td style="width: 5%">2 <br/>Salary</td>

                        <td style="width: 5%"> Date</td>
                        <td style="width: 5%"> Cause</td>
                    </tr>

                    @for($i=0; $i < 40; $i++)
                    <tr>
                        <td style="width: 10%">&nbsp; </td>
                        <td style="width: 10%"> </td>
                        <td style="width: 10%"></td>
                        <td style="width: 10%"></td>
                        <td style="width: 5%"></td>
                        <td style="width: 10%"></td>
                        <td style="width: 5%"></td>
                        <td style="width: 8%"></td>
                        <td style="width: 5%"> </td>
                        <td style="width: 5%"> </td>
                        <td style="width: 12%"></td>
                    </tr>
                    @endfor

                </table>
            </div>
            </div>
           </div>
        </body>
    </html>

