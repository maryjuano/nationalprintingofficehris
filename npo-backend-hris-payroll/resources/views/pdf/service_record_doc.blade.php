<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <style>

    * {
        box-sizing: border-box;
        margin:  0;
        padding: 0;
    }

    body {
        font-family: Helvetica, sans-serif;
        padding: 15px 15px 15px 15px;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        box-shadow: 1px 1px 10px solid #ccc;
        table-layout: fixed;
    }

    table td {
        padding : 4px 0px;
    }

    td {
        word-wrap:break-word;
    }

    .border-bottom {
        border-bottom: 1px solid black;
    }

    .span-small {
        font-size: 11px
    }

    .span-medium {
        font-size: 13px
    }

    .span-normal {
        font-size: 14px
    }

    .page-break {
		page-break-after : always;
	}

    .centered {
        text-align: center;
    }

    @font-face {
        font-family: 'Helvetica';
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url("https://fonts.googleapis.com/css?family=Baloo+Chettan+2&display=swap");
    }

    </style>

    <body>
        @php
            $index = 0;
            $chunk = array_chunk($vals['employment_history']->toArray(), 23);
            if(count($chunk) == 0){
                $chunk = [array()];
            };
            $signatory = $vals['signatory']->signatories;
        @endphp
        @foreach ($chunk as $item )
            @php
                $index++;
            @endphp

            <div class="{{ $index == count($chunk) ? '' : 'page-break'}} ">
                <div style="display: flex; margin : 20px 30px 0px 30px">
                    <p class="span-small"> GSIS D 202-02 (Revised, 1989)</p>
                    <p class="span-small" style="text-align: right">(Follow Instructions at the Back) </p>
                </div>
                <p style="text-align: center; font-size: 20px">S E R V I C E &nbsp; R E C O R D <br/><span class="span-small">(To be Accomplished by Employer) </span></p>

                <div style="margin : 10px 0px 0px 0px">
                    <table style="border: none; width: 100%"  >
                        <tr>
                            <td style="width: 8%"></td>
                            <td style="width: 8%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 5%"></td>
                            <td style="width: 5%"></td>
                            <td style="width: 8%"></td>
                            <td style="width: 8%"></td>
                            <td style="width: 8%"></td>
                        </tr>
                        <tr>
                            <td style=" border-bottom: 1px solid #fff;"><span class="span-medium">Name:</span></td>
                            <td colspan="2" class="border-bottom centered"><span class="span-medium">{{$vals['employee_details']['last_name']}}</span></td>
                            <td colspan="2" class="border-bottom centered"><span class="span-medium">{{$vals['employee_details']['first_name']}}</span></td>
                            <td colspan="2" class="border-bottom centered"><span class="span-medium">{{$vals['employee_details']['middle_name']}}</span></td>

                            <td colspan="4" rowspan="2" style="text-align : left">
                                <p style="font-size: 9px; margin-left: 20px; ">(If married woman, give also full name & other surname used)  </p>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="span-medium"></span></td>
                            <td colspan="2" class="centered"><span style="font-size: 9px;"><i>(Surname)</i></span></td>
                            <td colspan="2" class="centered"><span style="font-size: 9px;"><i>(Given)</i></span></td>
                            <td colspan="2" class="centered"><span style="font-size: 9px;"><i>(Middle Name)</i></span></td>
                        </tr>
                    </table>

                    <table style="border: none; width: 100%"  >
                        <tr>
                            <td style="width: 8%"></td>
                            <td style="width: 8%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 5%"></td>
                            <td style="width: 5%"></td>
                            <td style="width: 8%"></td>
                            <td style="width: 8%"></td>
                            <td style="width: 8%"></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #fff;"><span class="span-medium">Birth:</span></td>
                            <td colspan="3" class="border-bottom centered"><span class="span-medium">{{$vals['employee_details']['date_of_birth_str']}}</span></td>
                            <td colspan="3" class="border-bottom centered"><span class="span-medium">{{$vals['employee_details']['place_of_birth']}}</span></td>
                            <td colspan="4" rowspan="2" style="text-align : left" rowspan="2">
                                <p style="font-size: 9px; margin-left: 20px; ">(Date herein should be checked from birth or baptismal certificate or some other official documents)  </p>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="span-medium"></span></td>
                            <td colspan="3" class="centered"><span style="font-size: 9px;"><i>(Date)</i></span></td>
                            <td colspan="3" class="centered"><span style="font-size: 9px;"><i>(Place)</i></span></td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td colspan="8">
                                <p class="span-medium" style="text-align: justify; margin-left: 80px; margin-right: 80px">
                                    This is to certify that the employee named hereinabove actually rendered services in this Office or
                                    Offices indicated below each line of which is supported by appointment and other papers actually
                                    issued and approved by the authorities concerned:
                                </p>
                            </td>
                            <td colspan="1"></td>

                        </tr>
                    </table>
                </div>

                <table style="padding: 20px 10px 20px 10px; text-align: center; font-size: 12px; width : 100%" border="1">
                    <tr>
                        <td colspan="2">SERVICE <br/> (Inclusive Dates)</td>
                        <td colspan="4">RECORD OF APPOINMENT</td>
                        <td colspan="1" rowspan="2">OFFICE Station/ Place BRANCH </td>
                        <td colspan="1" rowspan="2">BRANCH <br/> (3) </td>
                        <td colspan="1" rowspan="2">LV / ABS WO / PAY (4)</td>
                        <td colspan="3"> SEPARATION (5)</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px">FROM </td>
                        <td style="font-size: 10px">TO </td>
                        <td colspan="2"style="font-size: 10px"> Designation </td>
                        <td style="font-size: 10px" >Status <br/> (1)</td>
                        <td style="font-size: 10px">Salary <br/> (2)</td>
                        <td style="font-size: 10px">Date </td>
                        <td style="font-size: 10px">Cause </td>
                        <td style="font-size: 10px">Amount <br/> Received </td>
                    </tr>

                    @php
                        $current_employment = $vals['current_employment'];
                        $blank_space = 0;
                        if(COUNT($item) < 24) {
                            $blank_space = 24 - COUNT($item);
                        }elseif (COUNT($item) >= 24) {
                            # code...
                        }
                    @endphp

                    @foreach($item as $key=>$value)
                        <tr>
                            <td style="font-size: 10px; border-top: none;border-bottom: none;"> &nbsp; {{$value['start_date']}} </td>
                            <td style="font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['end_date']}} </td>
                            <td colspan="2" style="font-size: 10px;  border-top: none;border-bottom: none;">
                               {{$value['position_name']}}
                            </td>
                            <td style=" font-size: 10px;  border-top: none;border-bottom: none;" > {{$value['status']}}</td>
                            <td style=" font-size: 10px;  border-top: none;border-bottom: none;"> {{ number_format(str_replace(',', '', $value['salary']), 2)}} </td>
                            <td style=" font-size: 10px;  border-top: none;border-bottom: none; overflow-wrap: break-word !important;">
                                {{$value['department_name']}}
                            </td>
                            <td style="font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['branch']??''}}</td>
                            <td style="font-size: 10px;  border-top: none;border-bottom: none;">
                                {{$vals['lwopEmpty']===0?$vals['noneArray'][$key]:$value['lwop']??'NONE'}}
                            </td>
                            <td style="font-size: 10px;  border-top: none;border-bottom: none;">{{ (isset($value['separation_date']) && $value['separation_date'] != null) ? date_format(date_create($value['separation_date']),'m-d-Y') : ''}} </td>
                            <td style="font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['separation_cause']??''}} </td>
                            <td style="font-size: 10px;  border-top: none;border-bottom: none;"> {{$value['separation_amount_received']??""}} </td>
                        </tr>
                    @endforeach

                    @if ($index == count($chunk))
                        @if (!!$current_employment->position)
                            <tr>
                                <td style="font-size: 10px; border-top: none;border-bottom: none;"> &nbsp;
                                    {{ date_format(date_create($current_employment->job_info_effectivity_date),'m-d-Y') }}
                                </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;">
                                    present
                                    </td>
                                <td colspan="2" style="font-size: 10px;  border-top: none;border-bottom: none;">
                                    {{ $current_employment->position->position_name }}
                                </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;" >
                                    {{ $current_employment->employee_type->employee_type_name}}
                                </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;">
                                    {{ number_format($current_employment->salary->step[$current_employment->step_increment] * 12, 2)}}
                                </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;">
                                    NPO
                                </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;"> Nat'l </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;">{{ $vals['currentLwop']->lwop ?? end($vals['noneArray'])}} </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;">   </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;">  </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;">  </td>
                            </tr>
                        @endif

                        <tr>
                            <td colspan="12" style="font-size: 10px;  border-top: none;border-bottom: none;">
                                <b> xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Nothing Follows xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx </b>
                            </td>
                        </tr>

                        @for ($i = 0; $i < $blank_space; $i++)
                            <tr>
                                <td style="font-size: 10px; border-top: none;border-bottom: none;"> &nbsp;
                                </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;">
                                    </td>
                                <td colspan="2" style=" font-size: 10px;  border-top: none;border-bottom: none;">
                                    
                                </td>
                                <td style=" font-size: 10px;  border-top: none;border-bottom: none;" >
                                </td>
                                <td style=" font-size: 10px;  border-top: none;border-bottom: none;">
                                </td>
                                <td style=" font-size: 10px;  border-top: none;border-bottom: none;">
                                </td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;"></td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;"></td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;"></td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;"></td>
                                <td style="font-size: 10px;  border-top: none;border-bottom: none;"></td>
                            </tr>
                        @endfor
                    @else
                        <tr>
                            <td colspan="2" style="font-size: 10px; border-top: none;border-bottom: none;">
                                xxx
                            </td>
                            <td colspan="2"style=" font-size: 10px;  border-top: none; border-bottom: none;">
                                xxx
                            </td>
                            <td style=" font-size: 10px;  border-top: none;border-bottom: none;" >

                            </td>
                            <td style=" font-size: 10px;  border-top: none;border-bottom: none;">
                                xxx
                            </td>
                            <td style=" font-size: 10px;  border-top: none;border-bottom: none;">

                            </td>
                            <td style=" font-size: 10px;  border-top: none;border-bottom: none;">
                                xxx
                            </td>
                            <td style=" font-size: 10px;  border-top: none;border-bottom: none;">

                            </td>
                            <td style="font-size: 10px;  border-top: none;border-bottom: none;">
                                xxx
                            </td>
                            <td style="font-size: 10px;  border-top: none; border-bottom: none;">

                            </td>
                            <td style="font-size: 10px;  border-top: none;">
                                xxx
                            </td>
                        </tr>
                    @endif
                </table>
                <div style="position : relative;">
                    <p class="span-medium" style="text-align: center; margin-left: 100px; margin-right: 100px">Issued in compliance with Executive Order No. 54 dated August 10, 1954, and in accordance with Circular No. 58, dated August 19, 1954 of the System.</p>
                    <br/>
                    <br/>
                    <table style="width: 100%;">
                        <tr>
                            <td width="20%"></td>
                            <td width="60%"></td>
                            <td width="25%" class="span-small centered"> CERTIFIED CORRECT</td>
                        </tr>
                        <tr>
                            <td class="border-bottom centered span-small">{{$vals['generation_date']}}</td>
                            <td></td>
                            <td class="border-bottom span-small"></td>
                        </tr>
                        <tr>
                            <td class="span-small centered">Date</td>
                            <td></td>
                            <td class="span-small centered">Signature</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="border-bottom centered span-small"><b>{{ $signatory[0]['name'] ?? '' }}</b></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="span-small centered">Printed Name</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="border-bottom centered span-small"><b>{{ $signatory[0]['title'] ?? '' }}</b></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="span-small centered">Designation</td>
                        </tr>
                    </table>
                </div>
            </div>
        @endforeach
    </body>
</html>

