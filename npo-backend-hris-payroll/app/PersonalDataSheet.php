<?php

namespace App;

use Carbon\Carbon;

use Illuminate\Support\Arr;

use App\Helpers\CitizenshipType;
use App\Helpers\CivilStatus;
use App\Helpers\Gender;
use App\Helpers\EducationalBackgroundType;
use App\Helpers\IsGovernmentService;

class PersonalDataSheet
{
    protected $personalInformation;
    protected $employmentAndCompensation;
    protected $familyBackground;
    protected $civilServices;
    protected $workExperiences;
    protected $voluntaryWorks;
    protected $trainingPrograms;
    protected $otherInformations;
    protected $questionnaire;
    protected $references;

    protected $elementaryEducation;
    protected $secondaryEducation;
    protected $vocationalEducation;
    protected $collegeEducation;
    protected $graduateStudiesEducation;

    protected $governmentId;

    public function __construct(int $employeeId)
    {
        $this->personalInformation = PersonalInformation::firstWhere('employee_id', $employeeId);
        $this->employmentAndCompensation = EmploymentAndCompensation::firstWhere('employee_id', $employeeId);
        $this->familyBackground = FamilyBackground::firstWhere('employee_id', $employeeId);
        $this->civilServices = CivilService::where('employee_id', $employeeId)->get();
        $this->workExperiences = WorkExperience::where('employee_id', $employeeId)->get();
        $this->voluntaryWorks = VoluntaryWork::where('employee_id', $employeeId)->get();
        $this->trainingPrograms = TrainingProgram::where('employee_id', $employeeId)->get();
        $this->otherInformations = OtherInformation::where('employee_id', $employeeId)->get();
        $this->questionnaire = Questionnaire::firstWhere('employee_id', $employeeId);
        $this->references = Reference::where('employee_id', $employeeId);

        $this->elementaryEducation = EducationalBackground::where('employee_id', $employeeId)
            ->whereType(EducationalBackgroundType::ELEMENTARY)
            ->orderBy('end_year', 'DESC')
            ->orderBy('end_month', 'DESC')
            ->orderBy('end_day', 'DESC')
            ->first();

        $this->secondaryEducation = EducationalBackground::where('employee_id', $employeeId)
            ->whereType(EducationalBackgroundType::SECONDARY)
            ->orderBy('end_year', 'DESC')
            ->orderBy('end_month', 'DESC')
            ->orderBy('end_day', 'DESC')
            ->first();

        $this->vocationalEducation = EducationalBackground::where('employee_id', $employeeId)
            ->whereType(EducationalBackgroundType::VOCATIONAL)
            ->orderBy('end_year', 'DESC')
            ->orderBy('end_month', 'DESC')
            ->orderBy('end_day', 'DESC')
            ->first();

        $this->collegeEducation = EducationalBackground::where('employee_id', $employeeId)
            ->whereType(EducationalBackgroundType::COLLEGE)
            ->orderBy('end_year', 'DESC')
            ->orderBy('end_month', 'DESC')
            ->orderBy('end_day', 'DESC')
            ->first();

        $this->graduateStudiesEducation = EducationalBackground::where('employee_id', $employeeId)
            ->whereType(EducationalBackgroundType::GRADUATE_STUDIES)
            ->orderBy('end_year', 'DESC')
            ->orderBy('end_month', 'DESC')
            ->orderBy('end_day', 'DESC')
            ->first();

        $this->governmentId = GovernmentId::firstWhere('employee_id', $employeeId);
    }

    public function getSurname()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->last_name;
    }

    public function getFirstName()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->first_name;
    }

    public function getMiddleName()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->middle_name;
    }

    public function getNameExtension()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->name_extension;
    }

    public function getDateOfBirth()
    {
        if (!$this->personalInformation || !$this->personalInformation->date_of_birth) {
            return '';
        }
        return Carbon::parse($this->personalInformation->date_of_birth)->format('m/d/Y');
    }

    public function getPlaceOfBirth()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->place_of_birth;
    }

    public function getSexMale()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->gender == Gender::MALE ? '3' : '';
    }

    public function getSexFemale()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->gender == Gender::FEMALE ? '3' : '';
    }

    public function getCivilStatusSingle()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->civil_status == CivilStatus::SINGLE ? '3' : '';
    }

    public function getCivilStatusMarried()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->civil_status == CivilStatus::MARRIED ? '3' : '';
    }

    public function getCivilStatusWidowed()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->civil_status == CivilStatus::WIDOWED ? '3' : '';
    }

    public function getCivilStatusSeparated()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->civil_status == CivilStatus::SEPARATED ? '3' : '';
    }

    public function getCivilStatusOthers()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->civil_status == CivilStatus::DIVORCED ? '3' : '';
    }

    public function getCitizenshipFilipino()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->citizenship == 'Philippines' ? '3' : '';
    }

    public function getCitizenshipDualCitizenship()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->dual_citizenship ? '3' : '';
    }

    public function getCitizenshipByBirth()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->by == CitizenshipType::BIRTH ? '3' : '';
    }

    public function getCitizenshipByNaturalization()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->by == CitizenshipType::NATURALIZATION ? '3' : '';
    }

    public function getCitizenshipCountry()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->country;
    }

    public function getHeight()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->height;
    }

    public function getWeight()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->weight;
    }

    public function getBloodType()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->blood_type_str;
    }

    public function getResidentialAddressLine1()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return "{$this->personalInformation->house_number} {$this->personalInformation->street}";
    }

    public function getResidentialAddressLine2()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return "{$this->personalInformation->subdivision} {$this->personalInformation->barangay}";
    }

    public function getResidentialAddressLine3()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return "{$this->personalInformation->city} {$this->personalInformation->province}";
    }

    public function getResidentialAddressZipCode()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->zip_code;
    }

    public function getPermanentAddressLine1()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return "{$this->personalInformation->p_house_number} {$this->personalInformation->p_street}";
    }

    public function getPermanentAddressLine2()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return "{$this->personalInformation->p_subdivision} {$this->personalInformation->p_barangay}";
    }

    public function getPermanentAddressLine3()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return "{$this->personalInformation->p_city} {$this->personalInformation->p_province}";
    }

    public function getPermanentAddressZipCode()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->p_zip_code;
    }

    public function getTelephoneNo()
    {
        if (!$this->personalInformation) {
            return '';
        }
        if ($this->personalInformation->telephone_number) {
            return "{$this->personalInformation->area_code} {$this->personalInformation->telephone_number}";
        }
        return null;
    }

    public function getMobileNo()
    {
        if (!$this->personalInformation) {
            return '';
        }
        if (!$this->personalInformation->mobile_number) {
            return null;
        }
        return implode(',', $this->personalInformation->mobile_number);
    }

    public function getEmailAddress()
    {
        if (!$this->personalInformation) {
            return '';
        }
        return $this->personalInformation->email_address;
    }

    public function getGSISIDNo()
    {
        if (!$this->employmentAndCompensation) {
            return '';
        }
        return $this->employmentAndCompensation->gsis_number;
    }

    public function getPagIbigIDNo()
    {
        if (!$this->employmentAndCompensation) {
            return '';
        }
        return $this->employmentAndCompensation->pagibig_number;
    }

    public function getPhilHealthNo()
    {
        if (!$this->employmentAndCompensation) {
            return '';
        }
        return $this->employmentAndCompensation->philhealth_number;
    }

    public function getSSSNo()
    {
        if (!$this->employmentAndCompensation) {
            return '';
        }
        return $this->employmentAndCompensation->sss_number;
    }

    public function getTIN()
    {
        if (!$this->employmentAndCompensation) {
            return '';
        }
        return $this->employmentAndCompensation->tin;
    }

    public function getAgencyEmployeeNo()
    {
        if (!$this->employmentAndCompensation) {
            return '';
        }
        return $this->employmentAndCompensation->id_number;
    }

    public function getSpouseSurname()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->spouse_last_name;
    }

    public function getSpouseFirstName()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->spouse_first_name;
    }

    public function getSpouseNameExtension()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->name_extension;
    }

    public function getSpouseMiddleName()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->spouse_middle_name;
    }

    public function getSpouseOccupation()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->occupation;
    }

    public function getSpouseEmployer()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->employer_name;
    }

    public function getSpouseBusinessAddress()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->business_address;
    }

    public function getSpouseTelephoneNo()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->telephone_number;
    }

    public function getFatherSurname()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->father_last_name;
    }

    public function getFatherFirstName()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->father_first_name;
    }

    public function getFatherMiddleName()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->father_middle_name;
    }

    public function getFatherNameExtension()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->father_extension;
    }

    public function getMotherMaidenName()
    {
        return '';
    }

    public function getMotherSurname()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->mother_last_name;
    }

    public function getMotherFirstName()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->mother_first_name;
    }

    public function getMotherMiddleName()
    {
        if (!$this->familyBackground) {
            return '';
        }
        return $this->familyBackground->mother_middle_name;
    }

    public function getChildrenNames()
    {
        if (!$this->familyBackground) {
            return collect([]);
        }
        return collect($this->familyBackground->children)->pluck('name');
    }

    public function getChildrenBirthdays()
    {
        if (!$this->familyBackground) {
            return collect([]);
        }
        return collect($this->familyBackground->children)->map(function ($child) {
            return Carbon::parse($child['birthday'])->format('m/d/Y');
        });
    }

    public function getCivilServiceIds()
    {
        return $this->civilServices->pluck('government_id');
    }

    public function getCivilServiceRatings()
    {
        return $this->civilServices->pluck('place_rating');
    }

    public function getCivilServiceDates()
    {
        return $this->civilServices->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('m/d/Y');
        });
    }

    public function getCivilServicePlaces()
    {
        return $this->civilServices->pluck('place');
    }

    public function getCivilServiceLicenseNumbers()
    {
        return $this->civilServices->pluck('license_no');
    }

    public function getCivilServiceLicenseValidityDates()
    {
        return $this->civilServices->pluck('validity_date')->map(function ($date) {
            return Carbon::parse($date)->format('m/d/Y');
        });
    }

    public function getWorkExperienceDateFroms()
    {
        return $this->workExperiences->pluck('start_inclusive_date')->map(function ($date) {
            return Carbon::parse($date)->format('m/d/Y');
        });
    }

    public function getWorkExperienceDateTos()
    {
        return $this->workExperiences->pluck('end_inclusive_date')->map(function ($date) {
            return Carbon::parse($date)->format('m/d/Y');
        });
    }

    public function getWorkExperiencePositionTitles()
    {
        return $this->workExperiences->pluck('position_title');
    }

    public function getWorkExperienceDepartments()
    {
        return $this->workExperiences->pluck('company');
    }

    public function getWorkExperienceMonthlySalaries()
    {
        return $this->workExperiences->pluck('monthly_salary')->map(function ($val) {
            return number_format($val);
        });
    }

    public function getWorkExperiencePayGrades()
    {
        return $this->workExperiences->pluck('pay_grade');
    }

    public function getWorkExperienceAppointmentStatuses()
    {
        return $this->workExperiences->pluck('status_of_appointment');
    }

    public function getWorkExperienceGovernmentServices()
    {
        return $this->workExperiences->pluck('government_service')->map(function ($val) {
            if ($val === null) {
                return null;
            }
            return IsGovernmentService::DISPLAY[$val];
        });
    }

    public function getVoluntaryWorkNamesAndAddresses()
    {
        return $this->voluntaryWorks->map(function ($val) {
            return "{$val->name_of_organization} {$val->address}";
        });
    }

    public function getVoluntaryWorkInclusiveDateFroms()
    {
        return $this->voluntaryWorks->pluck('start_inclusive_date')->map(function ($date) {
            return Carbon::parse($date)->format('m/d/Y');
        });
    }

    public function getVoluntaryWorkInclusiveDateTos()
    {
        return $this->voluntaryWorks->pluck('end_inclusive_date')->map(function ($date) {
            return Carbon::parse($date)->format('m/d/Y');
        });
    }

    public function getVoluntaryWorkNumberOfHours()
    {
        return $this->voluntaryWorks->pluck('number_of_hours');
    }

    public function getVoluntaryWorkPositions()
    {
        return $this->voluntaryWorks->pluck('position');
    }

    public function getLearningAndDevelopmentPrograms()
    {
        return $this->trainingPrograms->pluck('title');
    }

    public function getLearningAndDevelopmentInclusiveDateFroms()
    {
        return $this->trainingPrograms->pluck('start_inclusive_date')->map(function ($date) {
            return Carbon::parse($date)->format('m/d/Y');
        });
    }

    public function getLearningAndDevelopmentInclusiveDateTos()
    {
        return $this->trainingPrograms->pluck('end_inclusive_date')->map(function ($date) {
            return Carbon::parse($date)->format('m/d/Y');
        });
    }

    public function getLearningAndDevelopmentNumberOfHours()
    {
        return $this->trainingPrograms->pluck('number_of_hours');
    }

    public function getLearningAndDevelopmentTypes()
    {
        return $this->trainingPrograms->pluck('type');
    }

    public function getLearningAndDevelopmentSponsors()
    {
        return $this->trainingPrograms->pluck('sponsor');
    }

    public function getOtherInformationSpecialSkills()
    {
        return $this->otherInformations->pluck('special_skills');
    }

    public function getOtherInformationNonAcademicDistinctions()
    {
        return $this->otherInformations->pluck('recognition');
    }

    public function getOtherInformationMemberships()
    {
        return $this->otherInformations->pluck('organization');
    }

    public function getThirdDegreeRelativeYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->third_degree_relative === 1 ? '3' : '';
    }

    public function getThirdDegreeRelativeNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->third_degree_relative === 0 ? '3' : '';
    }

    public function getFourthDegreeRelativeYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->fourth_degree_relative === 1 ? '3' : '';
    }

    public function getFourthDegreeRelativeNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->fourth_degree_relative === 0 ? '3' : '';
    }

    public function getThirdAndFourthRelativeDetails()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return collect([
            $this->questionnaire->third_degree_relative_details,
            $this->questionnaire->fourth_degree_relative_details
        ])->filter()->implode(',');
    }

    public function getGuiltyOfAdministrativeOffenseYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->administrative_offender === 1 ? '3' : '';
    }

    public function getGuiltyOfAdministrativeOffenseNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->administrative_offender === 0 ? '3' : '';
    }

    public function getGuiltyOfAdministrativeOffenseDetails()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->administrative_offender_details;
    }

    public function getCriminallyChargedYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->criminally_charged === 1 ? '3' : '';
    }

    public function getCriminallyChargedNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->criminally_charged === 0 ? '3' : '';
    }

    public function getCriminallyChargedDates()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return trim(implode(', ', array_map(function ($dateTimeString) {
            return substr($dateTimeString, 0, 10);
        }, Arr::pluck($this->questionnaire->criminally_charged_data ?? [], 'date_filed'))));
    }

    public function getCriminallyChargedStatuses()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return trim(implode(', ', Arr::pluck($this->questionnaire->criminally_charged_data ?? [], 'status')));
    }

    public function getCrimeConvictionYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->convicted_of_crime === 1 ? '3' : '';
    }

    public function getCrimeConvictionNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->convicted_of_crime === 0 ? '3' : '';
    }

    public function getCrimeConvictionDetails()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->convicted_of_crime_details;
    }

    public function getSeparatedFromServiceYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->separated_from_service === 1 ? '3' : '';
    }

    public function getSeparatedFromServiceNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->separated_from_service === 0 ? '3' : '';
    }

    public function getSeparatedFromServiceDetails()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->separated_from_service_details;
    }

    public function getElectionCandidateYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->election_candidate === 1 ? '3' : '';
    }

    public function getElectionCandidateNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->election_candidate === 0 ? '3' : '';
    }

    public function getElectionCandidateDetails()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->election_candidate_details;
    }

    public function getResignedFromGovernmentYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->resigned_from_gov === 1 ? '3' : '';
    }

    public function getResignedFromGovernmentNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->resigned_from_gov === 0 ? '3' : '';
    }

    public function getResignedFromGovernmentDetails()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->resigned_from_gov_details;
    }

    public function getMultipleResidencyYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->multiple_residency === 1 ? '3' : '';
    }

    public function getMultipleResidencyNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->multiple_residency === 0 ? '3' : '';
    }

    public function getMultipleResidencyCountry()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->multiple_residency_country;
    }

    public function getIndigenousYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->indigenous === 1 ? '3' : '';
    }

    public function getIndigenousNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->indigenous === 0 ? '3' : '';
    }

    public function getIndigenousGroup()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->indigenous_group;
    }

    public function getDisabledYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->pwd === 1 ? '3' : '';
    }

    public function getDisabledNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->pwd === 0 ? '3' : '';
    }

    public function getPWDId()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->pwd_id;
    }

    public function getSoloParentYes()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->solo_parent === 1 ? '3' : '';
    }

    public function getSoloParentNo()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->solo_parent === 0 ? '3' : '';
    }

    public function getSoloParentId()
    {
        if (!$this->questionnaire) {
            return '';
        }
        return $this->questionnaire->solo_parent_id;
    }

    public function getReferenceNames()
    {
        return $this->references->pluck('ref_name');
    }

    public function getReferenceAddresses()
    {
        return $this->references->pluck('ref_address');
    }

    public function getReferenceTelNos()
    {
        return $this->references->pluck('ref_tel_no');
    }

    public function getElementaryEducationSchool()
    {
        return $this->elementaryEducation->school_name ?? '';
    }

    public function getSecondaryEducationSchool()
    {
        return $this->secondaryEducation->school_name ?? '';
    }

    public function getVocationalEducationSchool()
    {
        return $this->vocationalEducation->school_name ?? '';
    }

    public function getCollegeEducationSchool()
    {
        return $this->collegeEducation->school_name ?? '';
    }

    public function getGraduateStudiesEducationSchool()
    {
        return $this->graduateStudiesEducation->school_name ?? '';
    }

    public function getElementaryEducationCourse()
    {
        return $this->elementaryEducation->course ?? '';
    }

    public function getSecondaryEducationCourse()
    {
        return $this->secondaryEducation->course ?? '';
    }

    public function getVocationalEducationCourse()
    {
        return $this->vocationalEducation->course ?? '';
    }

    public function getCollegeEducationCourse()
    {
        return $this->collegeEducation->course ?? '';
    }

    public function getGraduateStudiesEducationCourse()
    {
        return $this->graduateStudiesEducation->course ?? '';
    }

    public function getElementaryEducationYearGraduated()
    {
        return $this->elementaryEducation->year_graduated ?? '';
    }

    public function getSecondaryEducationYearGraduated()
    {
        return $this->secondaryEducation->year_graduated ?? '';
    }

    public function getVocationalEducationYearGraduated()
    {
        return $this->vocationalEducation->year_graduated ?? '';
    }

    public function getCollegeEducationYearGraduated()
    {
        return $this->collegeEducation->year_graduated ?? '';
    }

    public function getGraduateStudiesEducationYearGraduated()
    {
        return $this->graduateStudiesEducation->year_graduated ?? '';
    }


    public function getElementaryEducationUnitsEarned()
    {
        return $this->elementaryEducation->year_graduated ?? false ? 'GRADUATE' : $this->elementaryEducation->units_earned ?? 0;
    }

    public function getSecondaryEducationUnitsEarned()
    {
        return $this->secondaryEducation->year_graduated ?? false ? 'GRADUATE' : $this->secondaryEducation->units_earned ?? 0;
    }

    public function getVocationalEducationUnitsEarned()
    {
        return $this->vocationalEducation->year_graduated ?? false ? 'GRADUATE' : $this->vocationalEducation->units_earned ?? 0;
    }

    public function getCollegeEducationUnitsEarned()
    {
        return $this->collegeEducation->year_graduated ?? false ? 'GRADUATE' : $this->collegeEducation->units_earned ?? 0;
    }

    public function getGraduateStudiesEducationUnitsEarned()
    {
        return $this->graduateStudiesEducation->year_graduated ?? false ? 'GRADUATE' : $this->graduateStudiesEducation->units_earned ?? 0;
    }

    public function getElementaryEducationAttendanceFrom()
    {
        if (!$this->elementaryEducation || !$this->elementaryEducation->start_year) {
            return '';
        }
        return Carbon::create(
            $this->elementaryEducation->start_year,
            $this->elementaryEducation->start_month ?? 1,
            $this->elementaryEducation->start_day ?? 1
        )->format('Y');
    }

    public function getSecondaryEducationAttendanceFrom()
    {
        if (!$this->secondaryEducation || !$this->secondaryEducation->start_year) {
            return '';
        }
        return Carbon::create(
            $this->secondaryEducation->start_year,
            $this->secondaryEducation->start_month ?? 1,
            $this->secondaryEducation->start_day ?? 1
        )->format('Y');
    }

    public function getVocationalEducationAttendanceFrom()
    {
        if (!$this->vocationalEducation || !$this->vocationalEducation->start_year) {
            return '';
        }
        return Carbon::create(
            $this->vocationalEducation->start_year,
            $this->vocationalEducation->start_month ?? 1,
            $this->vocationalEducation->start_day ?? 1
        )->format('Y');
    }

    public function getCollegeEducationAttendanceFrom()
    {
        if (!$this->collegeEducation || !$this->collegeEducation->start_year) {
            return '';
        }
        return Carbon::create(
            $this->collegeEducation->start_year,
            $this->collegeEducation->start_month ?? 1,
            $this->collegeEducation->start_day ?? 1
        )->format('Y');
    }

    public function getGraduateStudiesEducationAttendanceFrom()
    {
        if (!$this->graduateStudiesEducation || !$this->graduateStudiesEducation->start_year) {
            return '';
        }
        return Carbon::create(
            $this->graduateStudiesEducation->start_year,
            $this->graduateStudiesEducation->start_month ?? 1,
            $this->graduateStudiesEducation->start_day ?? 1
        )->format('Y');
    }

    //

    public function getElementaryEducationAttendanceTo()
    {
        if (!$this->elementaryEducation || !$this->elementaryEducation->end_year) {
            return '';
        }
        return Carbon::create(
            $this->elementaryEducation->end_year,
            $this->elementaryEducation->end_month ?? 1,
            $this->elementaryEducation->end_day ?? 1
        )->format('Y');
    }

    public function getSecondaryEducationAttendanceTo()
    {
        if (!$this->secondaryEducation || !$this->secondaryEducation->end_year) {
            return '';
        }
        return Carbon::create(
            $this->secondaryEducation->end_year,
            $this->secondaryEducation->end_month ?? 1,
            $this->secondaryEducation->end_day ?? 1
        )->format('Y');
    }

    public function getVocationalEducationAttendanceTo()
    {
        if (!$this->vocationalEducation || !$this->vocationalEducation->end_year) {
            return '';
        }
        return Carbon::create(
            $this->vocationalEducation->end_year,
            $this->vocationalEducation->end_month ?? 1,
            $this->vocationalEducation->end_day ?? 1
        )->format('Y');
    }

    public function getCollegeEducationAttendanceTo()
    {
        if (!$this->collegeEducation || !$this->collegeEducation->end_year) {
            return '';
        }
        return Carbon::create(
            $this->collegeEducation->end_year,
            $this->collegeEducation->end_month ?? 1,
            $this->collegeEducation->end_day ?? 1
        )->format('Y');
    }

    public function getGraduateStudiesEducationAttendanceTo()
    {
        if (!$this->graduateStudiesEducation || !$this->graduateStudiesEducation->end_year) {
            return '';
        }
        return Carbon::create(
            $this->graduateStudiesEducation->end_year,
            $this->graduateStudiesEducation->end_month ?? 1,
            $this->graduateStudiesEducation->end_day ?? 1
        )->format('Y');
    }

    public function getElementaryEducationHonors()
    {
        return $this->elementaryEducation->honors ?? '';
    }

    public function getSecondaryEducationHonors()
    {
        return $this->elementaryEducation->honors ?? '';
    }

    public function getVocationalEducationHonors()
    {
        return $this->vocationalEducation->honors ?? '';
    }

    public function getCollegeEducationHonors()
    {
        return $this->collegeEducation->honors ?? '';
    }

    public function getGraduateStudiesEducationHonors()
    {
        return $this->graduateStudiesEducation->honors ?? '';
    }

    public function getGovernmentIssuedID()
    {
        if (!$this->governmentId) {
            return '';
        }
        return $this->governmentId->id_type;
    }

    public function getGovernmentIDNo()
    {
        if (!$this->governmentId) {
            return '';
        }
        return $this->governmentId->id_no;
    }

    public function getGovernmentIDDateAndPlaceOfIssue()
    {
        if (!$this->governmentId) {
            return '';
        }
        $date = Carbon::parse($this->governmentId->date_of_issue)->format('d/m/Y');
        return "{$date} {$this->governmentId->place_of_issue}";
    }
}
