<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<style>
    table {
        border-collapse: collapse;
    }

    .table {
        font-family: monospace;
        border-left: 0.01em solid #ccc;
        border-right: 0.01em solid #ccc;
        border-top: 0.01em solid #ccc;
        border-bottom: 0.01em solid #ccc;
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

    .border-bottom {
        border-bottom: 1px solid black;
        text-align: center;
    }

</style>

<body>
    @foreach ($vals['employment_history']->chunk(24) as $documentIndex => $perDocumentChunk)
        <div style=" display: flex">

            <div style="width: 20%;">
                <div class="div-vertical">

                    <table style="width: 100%">
                        <tr>
                            <td width="30%">GSIS Policy NO.</td>
                            <td width="50%" style="text-align: center">MEMBER'S SERVICE RECORD</td>
                            <td width="20%" style="text-align: right">
                                <span style="font-size: 10px">MSM-01-02</span>
                                <br />
                                <span style="font-size: 10px; text-align: center">(ORIGINAL)</span>
                                <br />
                                <span style="font-size: 10px; text-align: center">To be submitted to GSIS</span>
                            </td>
                        </tr>
                    </table>

                    <table width="100%" style="font-size: 14px">
                        <tr>
                            <td width="30">Name: </td>
                            <td colspan="2" class="border-bottom">{{ $vals['employee_details']['last_name'] }}
                            </td>
                            <td class="border-bottom">{{ $vals['employee_details']['first_name'] }}</td>
                            <td class="border-bottom">{{ $vals['employee_details']['middle_name'] }}</td>
                            <td width="25">Sex: </td>
                            <td class="border-bottom">{{ $vals['employee_details']['gender_str'] }}</td>
                            <td width="63">Civil Status: </td>
                            <td class="border-bottom">{{ $vals['employee_details']['civil_status_str'] }}</td>
                        </tr>
                        <tr>
                            <td colspan="2"><span style="font-size: 9px"><i> (PRINT or TYPE) </span></td>
                            <td style="font-size: 9px; text-align: center;"><i>(Surname)</i></td>
                            <td style="font-size: 9px; text-align: center;"><i>(Given)</i></td>
                            <td style="font-size: 9px; text-align: center;"><i>(Middle Name)</i></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>

                    <table width="100%" style="font-size: 14px">
                        <tr>
                            <td width="40">Address: </td>
                            <td colspan="3" class="border-bottom">
                                {{ $vals['current_employment']['department']['department_name'] }}</td>
                            <td width="30">Birth: </td>
                            <td colspan="1" class="border-bottom">
                                {{ $vals['employee_details']['date_of_birth_str'] }}
                            </td>
                            <td colspan="2" class="border-bottom">
                                {{ $vals['employee_details']['place_of_birth'] }}
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td colspan="3" style="font-size: 9px; text-align: center;"><i>(Station or Place of
                                    Assignment)</i></td>
                            <td></td>
                            <td colspan="1" style="font-size: 9px; text-align: center;"><i>(Date)</i></td>
                            <td colspan="2" style="font-size: 9px; text-align: center;"><i>(Place)</i></td>
                        </tr>
                    </table>

                </div>
            </div>

            <div style="width: 100%; margin-left: 150px;">
                <table class="table" style="border-collapse: collapse; text-align: center; font-size: 12px" border=1>
                    <tr>
                        <td style="width: 20%" colspan="2">SERVICE <br /> (Inclusive Dates)</td>
                        <td style="width: 25%" colspan="3">RECORD OF APPOINMENT</td>
                        <td style="width: 10%" rowspan="2">OFFICE ENTITY <br />OF <br />DIVISION</td>
                        <td style="width: 5%" rowspan="2">3 <br /> BRANCH</td>
                        <td style="width: 8%" rowspan="2">LEAVE OF <br />ABSENCES <br />W/O PAY</td>
                        <td style="width: 10%" colspan="2"> 4 <br />SEPARATION</td>
                        <td style="width: 12%" rowspan="2">REMARKS</td>
                    </tr>
                    <tr>
                        <td style="width: 10%">FROM</td>
                        <td style="width: 10%">TO</td>
                        <td style="width: 10%">DESIGNATION</td>
                        <td style="width: 10%">1 <br />Status</td>
                        <td style="width: 5%">2 <br />Salary</td>
                        <td style="width: 5%">Date</td>
                        <td style="width: 5%">Cause</td>
                    </tr>

                    @for ($i = 0; $i < min(12, $perDocumentChunk->count()); $i++)
                        @if ($perDocumentChunk[$i]->date_hired != null) {{-- $loop->last && $i == $perDocumentChunk->count() - 1) --}}
                            @if ($perDocumentChunk[$i]->position != null)
                                <tr>
                                    <td style="height: 48px;; width: 10%">
                                        {{ date_format(date_create($perDocumentChunk[$i]->job_info_effectivity_date), 'm-d-Y') }}
                                    </td>
                                    <td style="width: 10%">present</td>
                                    <td style="width: 10%"> {{ $perDocumentChunk[$i]->position->position_name }} </td>
                                    <td style="width: 10%">
                                        {{ $perDocumentChunk[$i]->employee_type->employee_type_name }}
                                    </td>
                                    <td style="width: 5%">
                                        {{ number_format($perDocumentChunk[$i]->salary->step[$perDocumentChunk[$i]->step_increment] * 12, 2) }}
                                    </td>
                                    <td style="width: 10%">NPO</td>
                                    <td style="width: 5%"> Nat'l </td>
                                    <td style="width: 8%"> {{$vals['currentLwop']->lwop ?? ''}}</td>
                                    <td style="width: 5%"> </td>
                                    <td style="width: 5%"> </td>
                                    <td style="width: 12%"> </td>
                                </tr>
                            @endif
                        @else
                            <tr>
                                <td style="height: 48px;; width: 10%">{{ $perDocumentChunk[$i]['start_date'] }}</td>
                                <td style="width: 10%">{{ $perDocumentChunk[$i]['end_date'] }}</td>
                                <td style="width: 10%">{{ $perDocumentChunk[$i]['position_name'] }}</td>
                                <td style="width: 10%">{{ $perDocumentChunk[$i]['status'] }}</td>
                                <td style="width: 5%">{{ number_format(str_replace(',', '', $perDocumentChunk[$i]['salary']), 2) }}</td>
                                <td style="width: 10%">{{ $perDocumentChunk[$i]['department_name'] }}</td>
                                <td style="width: 5%">{{ $perDocumentChunk[$i]['branch'] }}</td>
                                <td style="width: 8%">{{ $perDocumentChunk[$i]['lwop'] }}</td>
                                <td style="width: 5%">{{ (isset($perDocumentChunk[$i]['separation_date']) && $perDocumentChunk[$i]['separation_date'] != null) ? date_format(date_create($perDocumentChunk[$i]['separation_date']),'m-d-Y') : '' }}</td>
                                <td style="width: 5%">{{ $perDocumentChunk[$i]['separation_cause'] }}</td>
                                <td style="width: 12%">{{ $perDocumentChunk[$i]['remarks'] }}</td>
                            </tr>
                        @endif
                    @endfor
                    @for ($i = 0; $i < 12 - $perDocumentChunk->count(); $i++)
                        <tr style="">
                            <td style="height: 48px;; width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 5%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 5%"></td>
                            <td style="width: 8%"></td>
                            <td style="width: 5%"></td>
                            <td style="width: 5%"></td>
                            <td style="width: 12%"></td>
                        </tr>
                    @endfor
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
                                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                &nbsp;
                                &nbsp;
                                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                &nbsp;
                                &nbsp; <span>EXPLANATION </span>
                                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                &nbsp;
                                &nbsp;
                                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                <br />
                                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                <span style="font-size: 12px">
                                    1. Probational, permanent, temporary, emergency or substitute.
                                    <br />
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                    2. Either daily, monthly or annual, whatever the appoinment shows as the basic
                                    salary
                                    excluding bonuses <br />
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; or allowances or
                                    supplementary salaries abroad.
                                    <br />
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                    3. Municipal city, provincial or national depending on which entity pays salary.
                                    <br />
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                    4. Registration, retirement (state what law), dropped, laid off, fismissal,
                                    suspension,
                                    expiration of appoinment State effective date.
                                </span>
                            </p>
                        </div>
                    </div>

                    <div style="width: 100%; margin-left: 150px;">
                        <p style="font-size: 10px; text-align: right">(Do not fill)</p>

                        <table class="table" style="border-collapse: collapse; text-align: center; font-size: 12px"
                            border=1>
                            <tr>
                                <td style="width: 20%" colspan="2">SERVICE <br /> (Inclusive Dates)</td>
                                <td style="width: 25%" colspan="3">RECORD OF APPOINMENT</td>
                                <td style="width: 10%" rowspan="2">OFFICE ENTITY<br /> OF <br />DIVISION</td>
                                <td style="width: 5%" rowspan="2">3 <br />BRANCH</td>
                                <td style="width: 8%" rowspan="2">LEAVE OF <br /> ABSENCE <br />W/O PAY</td>
                                <td style="width: 10%" colspan="2"> 4 <br />SEPARATION</td>
                                <td style="width: 12%" rowspan="2">REMARKS</td>
                            </tr>
                            <tr>
                                <td style="width: 10%">FROM </td>
                                <td style="width: 10%">TO </td>
                                <td style="width: 10%">DESIGNATION</td>
                                <td style="width: 10%">1 <br />Status</td>
                                <td style="width: 5%">2 <br />Salary</td>

                                <td style="width: 5%"> Date</td>
                                <td style="width: 5%"> Cause</td>
                            </tr>

                            @for ($i = 12; $i < $perDocumentChunk->count(); $i++)
                                @if ($loop->last && $i == $perDocumentChunk->count() - 1)
                                    <tr>
                                        <td style="height: 48px; width: 10%">
                                            {{ date_format(date_create($perDocumentChunk[$i]->job_info_effectivity_date), 'm-d-Y') }}
                                        </td>
                                        <td style="width: 10%">present</td>
                                        <td style="width: 10%"> {{ $perDocumentChunk[$i]->position->position_name }}
                                        </td>
                                        <td style="width: 10%">
                                            {{ $perDocumentChunk[$i]->employee_type->employee_type_name }}
                                        </td>
                                        <td style="width: 5%">
                                            {{ number_format($perDocumentChunk[$i]->salary->step[$perDocumentChunk[$i]->step_increment] * 12, 2) }}
                                        </td>
                                        <td style="width: 10%">
                                            NPO    
                                        </td>
                                        <td style="width: 5%"> Nat'l </td>
                                        <td style="width: 8%"> {{$vals['currentLwop']->lwop ?? ''}} </td>
                                        <td style="width: 5%"> </td>
                                        <td style="width: 5%"> </td>
                                        <td style="width: 12%"> </td>
                                    </tr>
                                @else
                                    <tr>
                                        <td style="height: 48px; width: 10%">
                                            {{ $perDocumentChunk[$i]['start_date'] }}
                                        </td>
                                        <td style="width: 10%">{{ $perDocumentChunk[$i]['end_date'] }}</td>
                                        <td style="width: 10%">{{ $perDocumentChunk[$i]['position_name'] }}</td>
                                        <td style="width: 10%">{{ $perDocumentChunk[$i]['status'] }}</td>
                                        <td style="width: 5%">{{ number_format(str_replace(',', '', $perDocumentChunk[$i]['salary']), 2) }}
                                        </td>
                                        <td style="width: 10%">{{ $perDocumentChunk[$i]['department_name'] }}</td>
                                        <td style="width: 5%">{{ $perDocumentChunk[$i]['branch'] }}</td>
                                        <td style="width: 8%">{{ $perDocumentChunk[$i]['lwop'] }}</td>
                                        <td style="width: 5%">{{ (isset($perDocumentChunk[$i]['separation_date']) && $perDocumentChunk[$i]['separation_date'] != null) ? date_format(date_create($perDocumentChunk[$i]['separation_date']),'m-d-Y') : '' }}</td>
                                        <td style="width: 5%">{{ $perDocumentChunk[$i]['separation_cause'] }}</td>
                                        <td style="width: 12%">{{ $perDocumentChunk[$i]['remarks'] }}</td>
                                    </tr>
                                @endif

                            @endfor
                            @for ($i = 0; $i < 24 - max(12, $perDocumentChunk->count()); $i++)
                                <tr style="">
                                    <td style="height: 48px;; width: 10%"></td>
                                    <td style="width: 10%"></td>
                                    <td style="width: 10%"></td>
                                    <td style="width: 10%"></td>
                                    <td style="width: 5%"> </td>
                                    <td style="width: 10%"></td>
                                    <td style="width: 5%"></td>
                                    <td style="width: 8%"></td>
                                    <td style="width: 5%"></td>
                                    <td style="width: 5%"></td>
                                    <td style="width: 12%"></td>
                                </tr>
                            @endfor

                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
