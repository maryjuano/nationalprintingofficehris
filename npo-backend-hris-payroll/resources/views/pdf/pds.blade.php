<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Personal Data Sheet</title>
	<link rel="stylesheet" href="{{ asset('css/app.css')  }}">
</head>
<style>
	table {
		width: 100%;
		max-width: 9in;
		margin: 0 auto;
		border: 2px solid #000;
	}
	table td:not(.separator) {
		font-size: 10px;
		border-color: #000;
		height: 30px;
	}
	table tbody {
		border: 1px solid #000;
	}
	table tbody:not(.table-header) td {
		border: 1px solid #000;
	}
	table .separator {
		font-size: 12px;
		font-style: italic;
		font-weight: 600;
		background-color: #757575;
		border-top-width: 2px !important;
		border-bottom-width: 2px !important;
	}
	table td.s-label {
		background-color: #dddddd;
		width: 20%;
	}
	table td.q-label {
		background-color: #dddddd;
	}
	table td .count {
		display: inline-block;
		width: 1.32em;
		text-align: center;
	}
	.table-body.question-block td {
		font-size: 10px !important;
	}
	.table-body.question-block tr td:first-child {
		border-bottom-width: 0px !important;
		border-top-width: 0px !important;
	}
	.table-body.question-block tr td:not(:first-child) {
		border-width: 0px !important;
		height: 10px;
	}
	.table-body.question-block tr td:nth-child(2) {
		padding-left: 15px;
	}
	.image-style {
		height : 4.5cm;
		width : 3.5cm;
		object-fit: fill;
	}
	.section-title {
		height : 40px;
	}
	.tr-height-45 tr{
		height : 45px;
	}
	.tr-height-40 tr{
		height : 40px;
	}

	@media print {
		@page {
			margin: 0.1in;
			size : legal portrait;
		}
		body {
			-webkit-print-color-adjust: exact;
			background-color : #fff;
			float : none;
			overflow: visible;
		}
		table {
			max-width: none;
		}
		.page-break {
			page-break-after : always !important;
		}
	}
</style>
<script>
	window.onload = function () {
		setTimeout(function () {
			window.print();
		}, 500);
	}
	window.onafterprint = (event) => {
		window.close();
	};
</script>

@php
	$dual_citizeship = $employees[0]->personal_information->dual_citizenship ? 'checked' : '';
	$by = isset($employees[0]->personal_information->by) ? ($employees[0]->personal_information->by === 1 ? 'Birth' : 'Naturalization') : '';

	function checkedValueYes($value){
		return $value === 1 ? 'checked' : '';
	}
	function checkedValueNo($value){
		return $value === 0 ? 'checked' : '';
	}

	$profile_pic = $employees[0]->profile_picture ?
		url('/api/file?location='.$employees[0]->profile_picture->file_location)
			:
		"https://www.grandfurniture.com/hotbuys/images/ina.jpg?t=1565571459" ;

	$months = [
		'Jan', 'Feb', 'Mar', 'Ap', 'May', 'Jun',
		'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];

	//formatting the date since hiwalay na yung year/month/day
	$concat_educational_period = function ($item, $period = "start") use ($months) {
		if($period === 'start'){
			$start_month =  isset($item->start_month) && $item->start_month !=0 ? $months[(int)$item->start_month - 1].'. ' : '' ;
			$start_day =  isset($item->start_day) && $item->start_day !=0 ? $item->start_day.', '  : '' ;
			$start_year =  isset($item->start_year) && $item->start_year !=0 ? $item->start_year  : '' ;
			return $start_month.$start_day.$start_year;
		}
		$end_month =  isset($item->end_month) && $item->end_month !=0 ? $months[(int)$item->end_month - 1].'. ' : '' ;
		$end_day =  isset($item->end_day) && $item->end_day !=0? $item->end_day.', '  : '' ;
		$end_year =  isset($item->end_year) && $item->end_year !=0 ? $item->end_year  : '' ;
		return $end_month.$end_day.$end_year;
	};

	function educational_year_graduate ($data) {
		if($data->year_graduated != null || $data->year_graduated != 0){
			return date_format(date_create($data->year_graduated),'Y');
		}
		return '';
	};

	/**
	*	Educ 1 - 2002 to 2003
	* 	Educ 2 - 2004 to 2005
	*	System displays in PDS yung Educ 2 instead of Educ 1
	*/
	$filterAndGetLatestEndPeriod = function ( $type ) use ($employees) {
		$education_list = $employees[0]->educational_background;
		$filtered = $education_list->filter(function ($value, $key) use($type) {
    		return $value->type == $type;
		});
		$filtered = $filtered->reduce(function ($acc, $curr) {
			if(!isset($acc->end_year)) return $curr;
			return $acc->end_year > $curr->end_year ? $acc : $curr;
		}, null);
		return $filtered;
	};

@endphp
<body id="pds-data" >
	<div>
		<form action="">
			<table class="page-break">
				<tbody class="table-header">
					<tr>
						<td colspan="12" class="h5"><i><b>CS Form No. 212</b></i></td>
					</tr>
					<tr>
						<td colspan="12" class="align-top" style="max-height: 12px;">
							<i><b>Revised 2017</b></i>
						</td>
					</tr>
					<tr>
						<td colspan="12" class="text-center"><h1><b>PERSONAL DATA SHEET</b></h1></td>
					</tr>
					<tr>
						<td colspan="12"><i><b>WARNING: Any misrepresentation main in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administative/criminal case/s against the person concerned.</b></i></td>
					</tr>
					<tr>
						<td colspan="12"><i><b>READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM</b></i></td>
					</tr>
					<tr>
						<td colspan="9">Print legibly. Tick appropriate boxes ( <input disabled type="checkbox" checked> ) ad use seperate sheet if necessary. Indicate N/A if not applicable. <b>DO NOT ABBREVIATE.</b></td>
						<td colspan="1" style="border:1px solid#000b;background:#757575;width:8%;"><small>1. CS ID No.</small></td>
						<td colspan="2" class="text-right" style="border:1px solid #000;width:20%;"><small>(Do not fill up. For CSC use only)</small></td>
					</tr>
				</tbody>

				<tbody class="table-body">
					<tr class="section-title" >
						<td colspan="12" class="text-white separator" >I. PERSONAL INFORMATION </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-bottom-0">
							<span class="count">2.</span> SURNAME
						</td>
						<td colspan="11">{{ $employees[0]->personal_information->last_name ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0"><span class="count"></span> FIRST NAME</td>
						<td colspan="9">{{ $employees[0]->personal_information->first_name ?? '' }}</td>
						<td colspan="2" class="align-top s-label border-bottom-0"><small>NAME EXTENSION (JR.,SR)</small></td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0"><span class="count"></span> MIDDLE NAME</td>
						<td colspan="9">{{ $employees[0]->personal_information->middle_name ?? '' }}</td>
						<td colspan="2">{{ $employees[0]->personal_information->name_extension  ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-bottom-0">
							<span class="count">3.</span> DATE OF BIRTH<br>
							<span class="count"></span> (mm/dd/yyyy)
						</td>
						<td colspan="5" class="border-0" >{{  date_format(date_create($employees[0]->personal_information->date_of_birth),'M. d, Y') ?? ''  }}</td>
						<td colspan="3" class="s-label align-top border-bottom-0">
							<span class="count">16.</span> CITIZENSHIP
						</td>
						<td colspan="3">
							{{ $employees[0]->personal_information->citizenship ?? '' }} &nbsp;&nbsp;&nbsp;
							<input type="checkbox" {{$dual_citizeship}} disabled/> Dual Citizenship
						</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0"></td>
						<td colspan="5"></td>
						<td colspan="3" class="s-label align-top border-0 text-center small">
							If holder of dual citizenship,
						</td>
						<td colspan="3"> {{ $by }}
						</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">4.</span> PLACE OR BIRTH</td>
						<td colspan="5">{{$employees[0]->personal_information->place_of_birth}}</td>
						<td colspan="3" class="s-label align-top border-0 text-center small"> please indicate the details.</td>
						<td colspan="3"> {{$employees[0]->personal_information->country ?? ''}}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">5.</span> SEX</td>
						<td colspan="5">{{ $employees[0]->personal_information->gender_str ?? ''  }}</td>
						<td colspan="3" class="s-label align-top border-0"></td>
						<td colspan="3"></td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-bottom-0"><span class="count">6.</span> CIVIL STATUS</td>
						<td colspan="5"> {{ $employees[0]->personal_information->civil_status_str ?? '' }} </td>
						<td colspan="2" class="s-label align-top border-bottom-0 small">
							<span class="count">17.</span> RESIDENTIAL ADDRESS
						</td>
						<td colspan="2"> {{ $employees[0]->personal_information->house_number ?? '' }} </td>
						<td colspan="2"> {{ $employees[0]->personal_information->street ?? '' }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-top-0"><span class="count"></span></td>
						<td colspan="5"></td>
						<td colspan="2" class="s-label align-top border-0"></td>
						<td colspan="2"> {{ $employees[0]->personal_information->subdivision ?? '' }} </td>
						<td colspan="2"> {{ $employees[0]->personal_information->barangay ?? '' }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">7.</span> HEIGHT (m)</td>
						<td colspan="5"> {{ $employees[0]->personal_information->height ?? '' }} </td>
						<td colspan="2" class="s-label align-top border-0"></td>
						<td colspan="2">{{ $employees[0]->personal_information->city ?? '' }}</td>
						<td colspan="2"> {{ $employees[0]->personal_information->province ?? '' }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">8.</span> WEIGHT (kg)</td>
						<td colspan="5"> {{ $employees[0]->personal_information->weight ?? '' }} </td>
						<td colspan="2" class="s-label border-0 text-center">
							ZIP CODE
						</td>
						<td colspan="4">{{ $employees[0]->personal_information->zip_code ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">9.</span> BLOOD TYPE</td>
						<td colspan="5"> {{ $employees[0]->personal_information->blood_type_str ?? ''  }} </td>
						<td colspan="2" class="s-label border-bottom-0"><span class="count">18.</span> PERMANENT ADDRESS</td>
						<td colspan="2"> {{ $employees[0]->personal_information->p_house_number ?? '' }} </td>
						<td colspan="2"> {{ $employees[0]->personal_information->p_street ?? '' }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">10.</span> GSIS ID NO.</td>
						<td colspan="5"> {{ $employees[0]->employment_and_compensation->gsis_number ?? ''  }} </td>
						<td colspan="2" class="s-label border-0"></td>
						<td colspan="2"> {{ $employees[0]->personal_information->p_subdivision ?? '' }} </td>
						<td colspan="2"> {{ $employees[0]->personal_information->p_barangay ?? '' }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">11.</span> PAG-IBIG NO.</td>
						<td colspan="5"> {{ $employees[0]->employment_and_compensation->pagibig_number ?? ''  }}  </td>
						<td colspan="2" class="s-label border-0"></td>
						<td colspan="2">{{ $employees[0]->personal_information->p_city ?? '' }}</td>
						<td colspan="2"> {{ $employees[0]->personal_information->p_province ?? '' }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">12.</span> PHILHEALTH NO.</td>
						<td colspan="5"> {{ $employees[0]->employment_and_compensation->philhealth_number ?? ''  }}  </td>
						<td colspan="2" class="s-label text-center border-0">ZIP CODE</td>
						<td colspan="4">{{ $employees[0]->personal_information->p_zip_code ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">13.</span> SSS NO.</td>
						<td colspan="5"> {{ $employees[0]->employment_and_compensation->sss_number ?? '' }} </td>
						<td colspan="2" class="s-label"><span class="count">19.</span> TELEPHONE NO.</td>
						<td colspan="4">{{ $employees[0]->personal_information->telephone_number ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">14.</span> TIN NO.</td>
						<td colspan="5"> {{ $employees[0]->employment_and_compensation->tin ?? '' }} </td>
						<td colspan="2" class="s-label"><span class="count">20.</span> MOBILE NO.</td>
						<td colspan="4">
							@foreach (isset($employees[0]->personal_information->mobile_number) ? $employees[0]->personal_information->mobile_number : [] as $item  )
								+63{{ $item }}/
							@endforeach
						</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label"><span class="count">15.</span> AGENCY EMPLOYEE NO.</td>
						<td colspan="5"></td>
						<td colspan="2" class="s-label"><span class="count">21.</span> EMAIL ADDRESS (if any)</td>
						<td colspan="4"> {{ $employees[0]->personal_information->email_address ?? '' }} </td>
					</tr>
				</tbody>

				<tbody class="table-body">
					<tr class="section-title" >
						<td colspan="12" class="text-white separator">II. FAMILY BACKGROUND</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-bottom-0">
							<span class="count">22.</span> SPOUSE SURNAME
						</td>
						<td colspan="5"> {{ $employees[0]->family_background->spouse_last_name ?? '' }} </td>
						<td colspan="3" class="s-label">
							<span class="count">23.</span> NAME of CHILDREN (Write full name and list all)
						</td>
						<td colspan="3" class="s-label text-center" style="width: 18%;">DATE OF BIRTH (mm/dd/yyyy)</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0">
							<span class="count"></span> FIRST NAME
						</td>
						<td colspan="4"> {{ $employees[0]->family_background->spouse_first_name ?? '' }} </td>
						<td colspan="1" class="align-top s-label">
							<small>
								NAME EXTENSION (JR.,SR) {{ $employees[0]->family_background->name_extension ?? '' }}
							</small>
						</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[0]['name']) ? $employees[0]->family_background->children[0]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[0]['birthday']) ? date_format(date_create($employees[0]->family_background->children[0]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0">
							<span class="count"></span> MIDDLE NAME
						</td>
						<td colspan="5">{{ $employees[0]->family_background->spouse_middle_name ?? '' }} </td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[1]['name']) ? $employees[0]->family_background->children[1]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[1]['birthday']) ? date_format(date_create($employees[0]->family_background->children[1]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> OCCUPATION
						</td>
						<td colspan="5">{{ $employees[0]->family_background->occupation ?? '' }} </td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[2]['name']) ? $employees[0]->family_background->children[2]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[2]['birthday']) ? date_format(date_create($employees[0]->family_background->children[2]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> EMPLOYER/BUSINESS NAME
						</td>
						<td colspan="5">{{ $employees[0]->family_background->employer_name ?? '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[3]['name']) ? $employees[0]->family_background->children[3]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[3]['birthday']) ? date_format(date_create($employees[0]->family_background->children[3]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> BUSINESS ADDRESS
						</td>
						<td colspan="5">{{ $employees[0]->family_background->business_address ?? '' }} </td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[4]['name']) ? $employees[0]->family_background->children[4]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[4]['birthday']) ? date_format(date_create($employees[0]->family_background->children[4]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> TELEPHONE NO.
						</td>
						<td colspan="5">{{ $employees[0]->family_background->telephone_number ?? '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[5]['name']) ? $employees[0]->family_background->children[5]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[5]['birthday']) ? date_format(date_create($employees[0]->family_background->children[5]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-bottom-0">
							<span class="count">24.</span> FATHER'S SURNAME
						</td>
						<td colspan="5">{{ $employees[0]->family_background->father_last_name ?? '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[6]['name']) ? $employees[0]->family_background->children[6]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[6]['birthday']) ? date_format(date_create($employees[0]->family_background->children[6]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0">
							<span class="count"></span> FIRST NAME
						</td>
						<td colspan="4">{{ $employees[0]->family_background->father_first_name ?? '' }}</td>
						<td colspan="1" class="align-top s-label">
							<small>
								NAME EXTENSION (JR.,SR) {{ $employees[0]->family_background->father_extension ?? '' }}
							</small>
						</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[7]['name']) ? $employees[0]->family_background->children[7]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[7]['birthday']) ? date_format(date_create($employees[0]->family_background->children[7]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0">
							<span class="count"></span> MIDDLE NAME
						</td>
						<td colspan="5">{{ $employees[0]->family_background->father_middle_name ?? '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[8]['name']) ? $employees[0]->family_background->children[8]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[8]['birthday']) ? date_format(date_create($employees[0]->family_background->children[8]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-bottom-0">
							<span class="count">25.</span> MOTHERS MAIDEN NAME
						</td>
						<td colspan="5"></td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[9]['name']) ? $employees[0]->family_background->children[9]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[9]['birthday']) ? date_format(date_create($employees[0]->family_background->children[9]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0">
							<span class="count"></span> SURNAME
						</td>
						<td colspan="5">{{ $employees[0]->family_background->mother_last_name ?? '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[10]['name']) ? $employees[0]->family_background->children[10]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[10]['birthday']) ? date_format(date_create($employees[0]->family_background->children[10]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0">
							<span class="count"></span> FIRST NAME
						</td>
						<td colspan="5">{{ $employees[0]->family_background->mother_first_name ?? '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[11]['name']) ? $employees[0]->family_background->children[11]['name'] : '' }}</td>
						<td colspan="3">{{ isset($employees[0]->family_background->children[11]['birthday']) ? date_format(date_create($employees[0]->family_background->children[11]['birthday']),'M. d, Y') : ''  }} </td>
					</tr>
					<tr>
						<td colspan="1" class="s-label border-0">
							<span class="count"></span> MIDDLE NAME
						</td>
						<td colspan="5">{{ $employees[0]->family_background->mother_middle_name ?? '' }}</td>
						<td colspan="6" class="s-label text-danger text-center"><i><b>(Continue on seperate sheet if necessary)</b></i></td>
					</tr>
				</tbody>

				<tbody class="table-body">
					<tr class="section-title" >
						<td colspan="12" class="text-white separator">III. EDUCATIONAL BACKGROUND</td>
					</tr>
					<tr class="text-center">
						<td colspan="1" class="s-label border-bottom-0">
							<span class="count">26.</span>
							<span class="d-block text-center">LEVEL</span>
						</td>
						<td colspan="5" class="s-label border-bottom-0">
							NAME OF SCHOOL<br>(Write in full)
						</td>
						<td colspan="1" class="s-label border-bottom-0">
							BASIC EDUCATION/DEGREE/COURSE<br>
							(Write in full)
						</td>
						<td colspan="2" class="s-label border-bottom-0">
							PERIOD OF ATTENDANCE
						</td>
						<td colspan="1" class="s-label border-bottom-0">HIGHEST LEVEL/UNITS EARNED<br>(If not graduated)</td>
						<td colspan="1" class="s-label border-bottom-0">YEAR GRADUATED</td>
						<td colspan="1" class="s-label border-bottom-0">SCHOLARSHIP/<br>ACADEMIC<br>HONORS<br>RECEIVED</td>
					</tr>
					<tr class="text-center" style="margin-top: -20px;">
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="5" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label">From</td>
						<td colspan="1" class="s-label">To</td>
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label border-top-0"></td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> ELEMENTARY
						</td>
						<td colspan="5">{{ $filterAndGetLatestEndPeriod(1)->school_name ?? '' }}</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(1)->course ?? '' }}</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(1), 'start')
							}}
						</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(1), 'end')
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(1)->units_earned ?? '' }}</td>
						<td colspan="1">
							{{
								$filterAndGetLatestEndPeriod(1) ? educational_year_graduate($filterAndGetLatestEndPeriod(1)) : ''
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(1)->honors ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> SECONDARY
						</td>
						<td colspan="5">{{ $filterAndGetLatestEndPeriod(2)->school_name ?? '' }}</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(2)->course ?? '' }}</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(2), 'start')
							}}
						</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(2), 'end')
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(2)->units_earned ?? '' }}</td>
						<td colspan="1">
							{{
								$filterAndGetLatestEndPeriod(2) ? educational_year_graduate($filterAndGetLatestEndPeriod(2)) : ''
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(2)->honors ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> VOCATIONAL/<br>
							<span class="count"></span> TRADE COURSE
						</td>
						<td colspan="5">{{ $filterAndGetLatestEndPeriod(3)->school_name ?? '' }}</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(3)->course ?? '' }}</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(3), 'start')
							}}
						</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(3), 'end')
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(3)->units_earned ?? '' }}</td>
						<td colspan="1">
							{{
								$filterAndGetLatestEndPeriod(3) ? educational_year_graduate($filterAndGetLatestEndPeriod(3)) : ''
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(3)->honors ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> COLLEGE
						</td>
						<td colspan="5">{{ $filterAndGetLatestEndPeriod(4)->school_name ?? '' }}</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(4)->course ?? '' }}</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(4), 'start')
							}}
						</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(4), 'end')
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(4)->units_earned ?? '' }}</td>
						<td colspan="1">
							{{
								$filterAndGetLatestEndPeriod(4) ? educational_year_graduate($filterAndGetLatestEndPeriod(4)) : ''
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(4)->honors ?? '' }}</td>
					</tr>
					<tr>
						<td colspan="1" class="s-label">
							<span class="count"></span> GRADUATE STUDIES
						</td>
						<td colspan="5">{{ $filterAndGetLatestEndPeriod(5)->school_name ?? '' }}</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(5)->course ?? '' }}</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(5), 'start')
							}}
						</td>
						<td colspan="1">
							{{
								$concat_educational_period($filterAndGetLatestEndPeriod(5), 'end')
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(5)->units_earned ?? '' }}</td>
						<td colspan="1">
							{{
								$filterAndGetLatestEndPeriod(5) ? educational_year_graduate($filterAndGetLatestEndPeriod(5)) : ''
							}}
						</td>
						<td colspan="1">{{ $filterAndGetLatestEndPeriod(5)->honors ?? '' }}</td>
					</tr>
				</tbody>

				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator bg-transparent text-danger text-center">
							<i>(Continue on seperate sheet if necessary)</i>
						</td>
					</tr>
					<tr>
						<td colspan="1" class="text-center"><i><b>SIGNATURE</b></i></td>
						<td colspan="6"></td>
						<td colspan="2" class="text-center"><i><b>DATE</b></i></td>
						<td colspan="3"></td>
					</tr>
				</tbody>
			</table>
			<!-- End of Page 1 -->
			<table class=" page-break tr-height-45">
				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator">IV.  CIVIL SERVICE ELIGIBILITY</td>
					</tr>
					<tr class="text-center">
						<td colspan="5" class="s-label border-bottom-0" style="width:30%">
							<span class="count float-left">27.</span>
							CAREER SERVICE/ RA 1080 (BOARD/ BAR) UNDER SPECIAL LAWS/ CES/ CSEE
							BARANGAY ELIGIBILITY / DRIVER'S LICENSE
						</td>
						<td colspan="1" class="s-label border-bottom-0">RATING<br>(If Applicable)</td>
						<td colspan="2" class="s-label border-bottom-0">DATE OF EXAMINATION / CONFERMENT</td>
						<td colspan="2" class="s-label border-bottom-0">PLACE OF EXAMINATION / CONFERMENT</td>
						<td colspan="2" class="s-label border-bottom-0">LICENSE<br>(if applicable)</td>
					</tr>
					<tr class="text-center">
						<td colspan="5" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="2" class="s-label border-top-0"></td>
						<td colspan="2" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label">NUMBER</td>
						<td colspan="1" class="s-label">Date of Validity</td>
					</tr>
					@php
						$blank_space_count_civil = 0;
						$civil_arr = [];
						if(COUNT($employees[0]->civilservice_eligibility) < 7) {
							$civil_arr = $employees[0]->civilservice_eligibility;
							$blank_space_count_civil = 7 - COUNT($employees[0]->civilservice_eligibility);
						}else {
							//incase the array is more than 7,
							//just get the first 7 in array to preserve the format
							$civil_arr = array_slice($employees[0]->civilservice_eligibility, 0, 7, true);
						}
					@endphp
					@foreach ( $civil_arr as $item)
						<tr>
							<td colspan="5">{{$item->government_id ?? ''}}</td>
							<td colspan="1">{{$item->place_rating ?? ''}}</td>
							<td colspan="2">{{date_format(date_create($item->date),'M. d, Y') ?? ''  }}</td>
							<td colspan="2">{{$item->place ?? ''}}</td>
							<td colspan="1">{{$item->license_no ?? ''}}</td>
							<td colspan="1">{{date_format(date_create($item->validity_date),'M. d, Y') ?? '' }}</td>
						</tr>
					@endforeach
					{{-- filling the blank space --}}
					@for ($i = 0; $i < $blank_space_count_civil ; $i++)
						<tr>
							<td colspan="5"></td>
							<td colspan="1"></td>
							<td colspan="2"></td>
							<td colspan="2"></td>
							<td colspan="1"></td>
							<td colspan="1"></td>
						</tr>
					@endfor
				</tbody>

				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator bg-transparent text-danger text-center">
							<i>(Continue on seperate sheet if necessary)</i>
						</td>
					</tr>
				</tbody>

				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator">
							V.  WORK EXPERIENCE<br>
							<small><i>(Include private employment.  Start from your recent work) Description of duties should be indicated in the attached Work Experience sheet.</i></small>
						</td>
					</tr>
					<tr class="text-center">
						<td colspan="1" class="s-label border-bottom-0" style="width: 20%;">
							<span class="count float-left">28.</span>
							INCLUSIVE DATES<br>(mm/dd/yyyy)

						</td>
						<td colspan="5" class="s-label border-bottom-0">
							POSITION TITLE<br>
							Write in full/Do not abbreviate)
						</td>
						<td colspan="1" class="s-label border-bottom-0">
							DEPARTMENT/AGENCY/OFFICE/COMPANY<br>
							(Write in full/Do not abbreviate)
						</td>
						<td colspan="2" class="s-label border-bottom-0">MONTHLY<br>SALARY</td>
						<td colspan="1" class="s-label border-bottom-0"><small>SALARY/ JOB/ PAY<br>GRADE (if applicable)& STEP  (Format "00-0")/ INCREMENT</small></td>
						<td colspan="1" class="s-label border-bottom-0">STATUS OF<br>APPOINTMENT</td>
						<td colspan="1" class="s-label border-bottom-0">GOV'T SERVICE<br>
							<small>(Y/ N)</small></td>
					</tr>
					<tr>
						<td colspan="1" class="p-0">
						<table class="w-100 border-0">
							<tbody class="border-0">
								<tr class="text-center">
									<td class="s-label border-0 border-bottom-0" style="width: 50%;">From</td>
									<td class="s-label border-top-0 border-right-0 border-bottom-0" style="width: 50%;">To</td>
								</tr>
							</tbody>
						</table>
						</td>
						<td colspan="5" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="2" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label border-top-0"></td>
					</tr>
					@php
						$blank_space_count_work = 0;
						$work_arr = [];
						if(COUNT($employees[0]->work_experience) < 19){
							$blank_space_count_work = 19 - COUNT($employees[0]->work_experience);
							$work_arr= $employees[0]->work_experience;
						}else{
							//incase the array is more than 19,
							//just get the first 19 in array to preserve the format
							$work_arr= array_slice($employees[0]->work_experience, 0, 19, true);
						}
					@endphp
					@foreach ( $work_arr as $item)
						<tr>
							<td colspan="1" class="p-0">
							<table class="w-100 border-0">
								<tbody class="border-0">
									<tr>
										<td class="border-0 border-bottom-0" style="width: 50%;">
											{{date_format(date_create($item->start_inclusive_date),'M. d, Y') ?? ''  }}
										</td>
										<td class="border-top-0 border-right-0 border-bottom-0" style="width: 50%;">
											{{date_format(date_create($item->end_inclusive_date),'M. d, Y') ?? ''  }}
										</td>
									</tr>
								</tbody>
							</table>
							</td>
							<td colspan="5">{{$item->position_title ?? ''}}</td>
							<td colspan="1">{{$item->company ?? ''}}</td>
							<td colspan="2">{{number_format($item->monthly_salary, 2) ?? ''}}</td>
							<td colspan="1">{{$item->pay_grade ?? ''}}</td>
							<td colspan="1">{{$item->status_of_appointment ?? ''}}</td>
							<td colspan="1">{{$item->government_service ? 'Y' : 'N'}}</td>
						</tr>
					@endforeach
					{{-- filling the blank space --}}
					@for ($i = 0; $i < $blank_space_count_work ; $i++)
						<tr>
							<td colspan="1" class="p-0">
							<table class="w-100 border-0">
								<tbody class="border-0">
									<tr>
										<td class="border-0 border-bottom-0" style="width: 50%;"></td>
										<td class="border-top-0 border-right-0 border-bottom-0" style="width: 50%;"></td>
									</tr>
								</tbody>
							</table>
							</td>
							<td colspan="5"></td>
							<td colspan="1"></td>
							<td colspan="2"></td>
							<td colspan="1"></td>
							<td colspan="1"></td>
							<td colspan="1"></td>
						</tr>
					@endfor
				</tbody>

				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator bg-transparent text-danger text-center">
							<i>(Continue on seperate sheet if necessary)</i>
						</td>
					</tr>
					<tr>
						<td colspan="1" class="text-center"><i><b>SIGNATURE</b></i></td>
						<td colspan="6"></td>
						<td colspan="2" class="text-center"><i><b>DATE</b></i></td>
						<td colspan="3"></td>
					</tr>
				</tbody>
			</table>
			<!-- End of Page 2 -->
			<table class=" page-break tr-height-40">
				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator">VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S</td>
					</tr>
					<tr class="text-center">
						<td colspan="6" class="s-label border-bottom-0">
							<span class="count float-left">29.</span> NAME & ADDRESS OF ORGANIZATION<br>
							(Write in full)
						</td>
						<td colspan="3" class="s-label border-bottom-0">INCLUSIVE DATES</td>
						<td colspan="1" class="s-label border-bottom-0">NUMBER OF HOURS</td>
						<td colspan="2" class="s-label border-bottom-0">POSITION / NATURE OF WORK</td>
					</tr>
					<tr class="text-center">
						<td colspan="6" class="s-label border-top-0"></td>
						<td colspan="1" class="s-label">From</td>
						<td colspan="2" class="s-label">To</td>
						<td colspan="1" class="s-label border-top-0"></td>
						<td colspan="2" class="s-label border-top-0"></td>
					</tr>
					@php
						$blank_space_count_voluntrary = 0;
						$voluntary_arr = [];
						if(COUNT($employees[0]->voluntary_work) < 7) {
							$voluntary_arr = $employees[0]->voluntary_work;
							$blank_space_count_voluntrary = 7 - COUNT($employees[0]->voluntary_work);
						}else {
							//incase the array is more than 7,
							//just get the first 7 in array to preserve the format
							$voluntary_arr = array_slice($employees[0]->voluntary_work, 0, 7, true);
						}
					@endphp
					@foreach ( $voluntary_arr as $item)
						<tr>
							<td colspan="6">{{$item->name_of_organization ?? ''}}</td>
							<td colspan="1">{{date_format(date_create($item->start_inclusive_date),'M. d, Y') ?? ''  }}</td>
							<td colspan="2">{{date_format(date_create($item->end_inclusive_date),'M. d, Y') ?? ''  }}</td>
							<td colspan="1">{{$item->number_of_hours ?? ''}}</td>
							<td colspan="2">{{$item->position ?? ''}}</td>
						</tr>
					@endforeach
					@for ( $i= 0;  $i < $blank_space_count_voluntrary ; $i++)
						<tr>
							<td colspan="6"></td>
							<td colspan="1"></td>
							<td colspan="2"></td>
							<td colspan="1"></td>
							<td colspan="2"></td>
						</tr>
					@endfor
				</tbody>

				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator bg-transparent text-danger text-center">
							<i>(Continue on seperate sheet if necessary)</i>
						</td>
					</tr>
				</tbody>

				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator">VII. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED<br>
							<small><i>(Start from the most recent L&D/training program and include only the relevant L&D/training taken for the last five (5) years for Division Chief/Executive/Managerial positions)</i></small>
						</td>
					</tr>
					<tr class="text-center">
						<td colspan="7" class="s-label border-bottom-0">
							<span class="count float-left">30.</span> TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS<br>
							(Write in full)
						</td>
						<td colspan="2" class="s-label border-bottom-0">INCLUSIVE DATES</td>
						<td colspan="1" class="s-label border-bottom-0">NUMBER OF HOURS</td>
						<td colspan="1" class="s-label border-bottom-0">Type of LD ( Managerial/ Supervisory/Technical/etc)</td>
						<td colspan="1" class="s-label border-bottom-0">CONDUCTED/ SPONSORED BY<br>(Write in full)</td>
					</tr>
					<tr class="text-center">
						<td style="width : 40%;" colspan="7" class="s-label border-top-0"></td>
						<td style="width : 10%;" colspan="1" class="s-label">From</td>
						<td style="width : 10%;" colspan="1" class="s-label">To</td>
						<td style="width : 10%;" colspan="1" class="s-label border-top-0"></td>
						<td style="width : 15%;" colspan="1" class="s-label border-top-0"></td>
						<td style="width : 15%;" colspan="1" class="s-label border-top-0"></td>
					</tr>
					@php
						$blank_space_count_training = 0;
						$traning_arr = [];
						if(COUNT($employees[0]->training_program) < 13) {
							$traning_arr = $employees[0]->training_program;
							$blank_space_count_training = 13 - COUNT($employees[0]->training_program);
						}else {
							//incase the array is more than 13,
							//just get the first 13 in array to preserve the format
							$traning_arr = array_slice($employees[0]->training_program, 0, 13, true);
						}
					@endphp
					@foreach ( $traning_arr as $item)
						<tr>
							<td style="width : 40%;" colspan="7">{{$item->title ?? ''}}</td>
							<td style="width : 10%;" colspan="1">{{date_format(date_create($item->start_inclusive_date),'M. d, Y') ?? ''  }}</td>
							<td style="width : 10%;" colspan="1">{{date_format(date_create($item->end_inclusive_date),'M. d, Y') ?? ''  }}</td>
							<td style="width : 10%;" colspan="1">{{$item->number_of_hours ?? ''}}</td>
							<td style="width : 15%;" colspan="1">{{$item->type ?? ''}}</td>
							<td style="width : 15%;" colspan="1">{{$item->sponsor ?? ''}}</td>
						</tr>
					@endforeach
					@for ( $i= 0;  $i < $blank_space_count_training ; $i++)
						<tr>
							<td colspan="7"></td>
							<td colspan="1"></td>
							<td colspan="1"></td>
							<td colspan="1"></td>
							<td colspan="1"></td>
							<td colspan="1"></td>
						</tr>
					@endfor
				</tbody>

				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator bg-transparent text-danger text-center">
							<i>(Continue on seperate sheet if necessary)</i>
						</td>
					</tr>
				</tbody>

				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator">VIII.  OTHER INFORMATION</td>
					</tr>
					<tr class="text-center">
						<td style="width : 50%;" colspan="4" class="s-label">
							<span class="count float-left">31.</span> SPECIAL SKILLS and HOBBIES
						</td>
						<td colspan="4" class="s-label">
							<span class="count float-left">32.</span> NON-ACADEMIC DISTINCTIONS / RECOGNITION<br>(Write in full)
						</td>
						<td colspan="4" class="s-label">
							<span class="count float-left">33.</span> MEMBERSHIP IN ASSOCIATION/ORGANIZATION<br>(Write in full)
						</td>
					</tr>
					@php
						$blank_space_count_other = 0;
						$other_arr = [];
						if(COUNT($employees[0]->other_information) < 7) {
							$other_arr = $employees[0]->other_information;
							$blank_space_count_other = 7 - COUNT($employees[0]->other_information);
						}else {
							//incase the array is more than 7,
							//just get the first 7 in array to preserve the format
							$other_arr = array_slice($employees[0]->other_information, 0, 7, true);
						}
					@endphp
					@foreach ( $other_arr as $item)
						<tr>
							<td colspan="4">{{$item->special_skills ?? ''}}</td>
							<td colspan="4">{{$item->recognition ?? ''}}</td>
							<td colspan="4">{{$item->organization ?? ''}}</td>
						</tr>
					@endforeach
					@for ( $i= 0;  $i < $blank_space_count_other ; $i++)
						<tr>
							<td colspan="4"></td>
							<td colspan="4"></td>
							<td colspan="4"></td>
						</tr>
					@endfor
				</tbody>
				<tbody class="table-body">
					<tr>
						<td colspan="12" class="text-white separator bg-transparent text-danger text-center">
							<i>(Continue on seperate sheet if necessary)</i>
						</td>
					</tr>
					<tr>
						<td colspan="4" class="text-center"><i><b>SIGNATURE</b></i></td>
						<td colspan="3"></td>
						<td colspan="1" class="text-center"><i><b>DATE</b></i></td>
						<td colspan="4"></td>
					</tr>
				</tbody>
			</table>
			<!-- End of Page 3 -->
			<table>
				<!-- Q1 -->
				<tbody class="table-body question-block">
					<tr>
						<td colspan="10" class="q-label border-bottom-0" style="width: 45%" >
							<span class="count">34.</span> Are you related by consanguinity or affinity to the appointing or recommending authority, or to the<br>
							<span class="count"></span>chief of bureau or office or to the person who has immediate supervision over you in the Office,<br>
							<span class="count"></span>Bureau or Department where you will beapppointed,<br>
						</td>
						<td style="width: 15%" colspan="1"><input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['third_degree_relative'])}} /> Yes</td>
						<td style="width: 40%" colspan="1"><input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['third_degree_relative'])}} /> No</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span>a. within the third degree?<br>
						</td>
						<td colspan="1"><input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['fourth_degree_relative'])}} /> Yes</td>
						<td colspan="1"><input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['fourth_degree_relative'])}} /> No</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span>b. within the fourth degree (for Local Government Unit - Career Employees)?
						</td>
						<td colspan="1">If YES, give details:</td>
						<td colspan="1">{{$employees[0]->questionnaire->third_degree_relative_details ?? ''}}</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
						</td>
						<td colspan="1">If YES, give details:</td>
						<td colspan="1">{{$employees[0]->questionnaire->fourth_degree_relative_details ?? ''}}</td>
					</tr>
				</tbody>
				<!-- Q2 -->
				<tbody class="table-body question-block">
					<tr>
						<td colspan="10" class="q-label border-bottom-0">
							<span class="count">35.</span> a. Have you ever been found guilty of any administrative offense?
						</td>
						<td colspan="1"><input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['administrative_offender'])}} /> Yes</td>
						<td colspan="1"><input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['administrative_offender'])}} /> No</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label"></td>
						<td colspan="1">If YES, give details:</td>
						<td colspan="1">{{$employees[0]->questionnaire->administrative_offender_details ?? ''}}</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span> b. Have you been criminally charged before any court?
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['criminally_charged'])}} /> Yes
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['criminally_charged'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label"></td>
						<td colspan="2">If YES, give details: {{ '' }} </td>
					</tr>
					<tr>
						<td colspan="10" class="q-label"></td>
						<td colspan="1">Date Filed:</td>
						<td colspan="1">
							@foreach ($employees[0]->questionnaire->criminally_charged_data ?? [] as $item)
								{{ isset($item['date_filed']) ? date_format(date_create($item['date_filed']),'M. d, Y') : ''  }} /
							@endforeach
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label"></td>
						<td colspan="1">Status of Case/s:</td>
						<td colspan="1">
							@foreach ($employees[0]->questionnaire->criminally_charged_data ?? [] as $item)
								{{ isset( $item['status']) ? $item['status'] : ''  }} /
							@endforeach
						</td>
					</tr>
				</tbody>
				<!-- Q3 -->
				<tbody class="table-body question-block">
					<tr>
						<td colspan="10" class="q-label border-bottom-0">
							<span class="count">36.</span> Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['convicted_of_crime'])}} /> Yes
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['convicted_of_crime'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label"></td>
						<td colspan="1">If YES, give details:</td>
						<td colspan="1">{{$employees[0]->questionnaire->convicted_of_crime_details ?? ''}}</td>
					</tr>

				</tbody>
				<!-- Q4 -->
				<tbody class="table-body question-block">
					<tr>
						<td colspan="10" class="q-label border-bottom-0">
							<span class="count">37.</span> Have you ever been separated from the service in any of the following modes: resignation,<br>
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['separated_from_service'])}} /> Yes
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['separated_from_service'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							retirement, dropped from the rolls, dismissal, termination, end of term, finished contract
							or phased out (abolition) in the public or private sector?<br>
						</td>
						<td colspan="2">If YES, give details: {{$employees[0]->questionnaire->separated_from_service_details ?? ''}}</td>
					</tr>
				</tbody>
				<!-- Q5 -->
				<tbody class="table-body question-block">
					<tr>
						<td colspan="10" class="q-label border-bottom-0">
							<span class="count">38.</span> a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['election_candidate'])}} /> Yes
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input  disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['election_candidate'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span><br>
						</td>
						<td colspan="1">If YES, give details:</td>
						<td colspan="1">{{$employees[0]->questionnaire->election_candidate_details ?? ''}}</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span> b. Have you resigned from the government service during the three (3)-month period before the last
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['resigned_from_gov'])}} /> Yes
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['resigned_from_gov'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span> election to promote/actively campaign for a national or local candidate?
						</td>
						<td colspan="1">If YES, give details:</td>
						<td colspan="1">{{$employees[0]->questionnaire->resigned_from_gov_details ?? ''}}</td>
					</tr>
				</tbody>
				<!-- Q6 -->
				<tbody class="table-body question-block">
					<tr>
						<td colspan="10" class="q-label border-bottom-0">
							<span class="count">39.</span> Have you acquired the status of an immigrant or permanent resident of another country?
						</td>
							<td colspan="1">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['multiple_residency'])}} /> Yes
						</td>
						<td colspan="1">
							<input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['multiple_residency'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
						</td>
						<td colspan="1">if YES, give details (country):</td>
						<td colspan="1">{{$employees[0]->questionnaire->multiple_residency_country ?? ''}}</td>
					</tr>
				</tbody>
				<!-- Q7 -->
				<tbody class="table-body question-block">
					<tr>
						<td colspan="10" class="q-label border-bottom-0">
							<span class="count">40.</span> Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA<br>
							<span class="count"></span> 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:
						</td>
						<td colspan="1">

						</td>
						<td colspan="1"></td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span>a. Are you a member of any indigenous group?<br>
						</td>
						<td colspan="1">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['indigenous'])}} /> Yes
						</td>
						<td colspan="1">
							<input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['indigenous'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span><br>
						</td>
						<td colspan="1">If YES, please specify:</td>
						<td colspan="1">{{$employees[0]->questionnaire->indigenous_group ?? ''}}</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span>b. Are you a person with disability?
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['pwd'])}} /> Yes
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['pwd'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
						</td>
						<td colspan="1">If YES, please specify:</td>
						<td colspan="1">{{$employees[0]->questionnaire->pwd_id ?? ''}}</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label">
							<span class="count"></span>c. Are you a solo parent?
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueYes($employees[0]->questionnaire['solo_parent'])}} /> Yes
						</td>
						<td colspan="1" style="border-top-width: 1px !important;">
							<input disabled type="checkbox" {{checkedValueNo($employees[0]->questionnaire['solo_parent'])}} /> No
						</td>
					</tr>
					<tr>
						<td colspan="10" class="q-label"></td>
						<td colspan="1">If YES, please specify:</td>
						<td colspan="1">{{$employees[0]->questionnaire->solo_parent_id ?? ''}}</td>
					</tr>
				</tbody>
			</table>
			<table>
				<tbody class="table-body">
					<tr>
						<td colspan="8" class="s-label">
							<span class="count">41.</span> REFERENCES <span class="text-danger">(Person not related by consanguinity or affinity to applicant /appointee)</span>
						</td>
						<td colspan="4" rowspan="6" class="p-5">
							<table class="w-75 mx-auto ">
								<tbody>
									<tr>
										<td class="text-center">
											@if($profile_pic)
												<img src="{{$profile_pic}}" class="image-style"/>
											@else
												ID picture taken within the last 6 months4.5 cm. X 3.5 cm(passport size)With full and handwrittenname
												tag and signature overprinted nameComputer generated or photocopied picture is not acceptable
											@endif
										</td>
									</tr>
									<tr>
										<td class="text-muted lead text-center">PHOTO</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr class="text-center" >
						<td colspan="4" class="s-label">NAME</td>
						<td colspan="2" class="s-label">ADDRESS</td>
						<td colspan="2" class="s-label">TEL. NO.</td>
					</tr>
					<tr >
						<td colspan="4">{{ isset($employees[0]->references[0]) ? $employees[0]->references[0]['ref_name'] : '' }}</td>
						<td colspan="2">{{ isset($employees[0]->references[0]) ? $employees[0]->references[0]['ref_address'] : '' }}</td>
						<td colspan="2">{{ isset($employees[0]->references[0]) ? $employees[0]->references[0]['ref_tel_no'] : '' }}</td>
					</tr>
					<tr>
						<td colspan="4">{{ isset($employees[0]->references[1]) ? $employees[0]->references[1]['ref_name'] : '' }}</td>
						<td colspan="2">{{ isset($employees[0]->references[1]) ? $employees[0]->references[1]['ref_address'] : '' }}</td>
						<td colspan="2">{{ isset($employees[0]->references[1]) ? $employees[0]->references[1]['ref_tel_no'] : '' }}</td>
					</tr>
					<tr >
						<td colspan="4">{{ isset($employees[0]->references[2]) ? $employees[0]->references[2]['ref_name'] : '' }}</td>
						<td colspan="2">{{ isset($employees[0]->references[2]) ? $employees[0]->references[2]['ref_address'] : '' }}</td>
						<td colspan="2">{{ isset($employees[0]->references[2]) ? $employees[0]->references[2]['ref_tel_no'] : '' }}</td>
					</tr>
					<tr>
						<td colspan="8">
							<span class="count">42.</span> I declare under oath that I have personally accomplished this Personal Data Sheet which is a true correct and<br><span class="count"></span> complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the<br><span class="count"></span> Philippines. I authorize the agency head / authorized representative t verify validate the contents stated herein.<br><span class="count"></span> I agree that any misrepresentation made in this document and its attachments shall cause the filing of<br><span class="count"></span> administrative/criminal case/s against me.
						</td>
					</tr>
					<tr>
						<td colspan="12" class="border-0 p-0">
							<table class="border-0 w-100">
								<tbody class="border-0">
									<tr>
										<td colspan="4" class="border-0 p-3" style="width: 38.5%;">
											<table class="border-0 w-100">
												<tbody>
													<tr>
														<td class="s-label py-2">
															Government Issued ID(i.e.Passport, GSIS, SSS, PRC, Driver's License, etc.)
															<br>
															PLEASE INDICATE ID Number and Date of Issuance
														</td>
													</tr>
													<tr>
														<td style="width: 30%;">
															Government Issued ID: &nbsp;&nbsp; {{ $employees[0]->govt_id->id_type ?? '' }}
														</td>
													</tr>
													<tr>
														<td style="width: 30%;">
															ID/License/Passport No.: &nbsp;&nbsp; {{ $employees[0]->govt_id->id_no ?? '' }}
														</td>
													</tr>
													<tr>
														<td style="width: 30%;">
															Date/Place of Issuance: &nbsp;&nbsp;
															{{
																isset($employees[0]->govt_id->date_of_issue) ?
																	date_format(date_create($employees[0]->govt_id->date_of_issue),'F d, Y')
																:
																	''
															}}
														</td>
													</tr>
												</tbody>
											</table>
										</td>
										<td colspan="4" class="border-0 p-3" style="width: 38.5%;">
											<table class="border-0 w-100">
												<tbody class="border-0 text-center">
													<tr>
														<td class="py-4"></td>
													</tr>
													<tr>
														<td class="s-label"><small>Signature (Sign inside the box)</small></td>
													</tr>
													<tr>
														<td></td>
													</tr>
													<tr>
														<td class="s-label"><small>Date Accomplished</small></td>
													</tr>
												</tbody>
											</table>
										</td>
										<td colspan="4" class="border-0 p-3">
											<table class="border-0 w-100">
												<tbody class="border-0">
													<tr>
														<td class="py-5"></td>
													</tr>
													<tr>
														<td class="s-label text-center">Right Thumbmark</td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
								<tbody >
									<tr>
										<td colspan="12" class="text-center py-2">
											SUBSCRIBED AND SWORN to before me this <input type="text" class="border-top-0 border-left-0 border-right-0" style="width: 25%;"> , affiant exhibiting his/her validly issued government ID as indicated above.
										</td>
									</tr>
									<tr>
										<td colspan="12" class="py-5">
											<table class="w-25 mx-auto">
												<tbody>
													<tr>
														<td class="py-5"></td>
													</tr>
													<tr>
														<td class="s-label text-center">Person Administering Oath</td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
			<!-- End of Page 4 -->
		</form>
	</div>
</body>
</html>
