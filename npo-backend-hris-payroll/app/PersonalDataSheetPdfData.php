<?php

namespace App;

use Illuminate\Support\Arr;

class PersonalDataSheetPdfData
{
    protected $pageData;

    // pdf page index => count (if has duplicate pages for separate sheets)
    protected $pages = [
        0 => 1,
        1 => 1,
        2 => 1,
        3 => 1,
    ];

    protected $pageMap = [0, 1, 2, 3];

    const CHILDREN_PER_PAGE = 12;
    const CIVIL_SERVICE_ELIGIBILITIES_PER_PAGE = 7;
    const WORK_EXPERIENCES_PER_PAGE = 28;
    const VOLUNTARY_WORKS_PER_PAGE = 7;
    const LEARNINGS_PER_PAGE = 17;
    const OTHER_INFOS_PER_PAGE = 7;

    public function __construct(PersonalDataSheet $personalDataSheet)
    {
        $this->pages[0] = max(
            ceil($personalDataSheet->getChildrenNames()->count() / self::CHILDREN_PER_PAGE),
            1
        );
        $this->pages[1] = max(
            ceil($personalDataSheet->getCivilServiceIds()->count() / self::CIVIL_SERVICE_ELIGIBILITIES_PER_PAGE),
            ceil($personalDataSheet->getWorkExperiencePositionTitles()->count() / self::WORK_EXPERIENCES_PER_PAGE),
            1
        );
        $this->pages[2] = max(
            ceil($personalDataSheet->getVoluntaryWorkPositions()->count() / self::VOLUNTARY_WORKS_PER_PAGE),
            ceil($personalDataSheet->getLearningAndDevelopmentPrograms()->count() / self::LEARNINGS_PER_PAGE),
            ceil($personalDataSheet->getOtherInformationSpecialSkills()->count() / self::OTHER_INFOS_PER_PAGE),
            1
        );

        $this->pageMap = Arr::collapse(array_map(function ($val, $key) {
            return array_fill(0, $val, $key);
        }, $this->pages, array_keys($this->pages)));

        $this->initPageData($personalDataSheet);
    }

    public function getPageMap()
    {
        return $this->pageMap;
    }

    public function getTotalPages()
    {
        return array_sum($this->pages);
    }

    public function getPageData()
    {
        return $this->pageData;
    }

    protected function initPageData(PersonalDataSheet $personalDataSheet)
    {
        // The page index where page 0 content first appears
        $this->pageData[array_search(0, $this->pageMap)] = [
            new PDFData(45, 42, 163, 7, $personalDataSheet->getSurname()),
            new PDFData(45, 49, 118, 7, $personalDataSheet->getFirstName()),
            new PDFData(163, 49, 45, 7, $personalDataSheet->getNameExtension()),
            new PDFData(45, 56, 163, 7, $personalDataSheet->getMiddleName()),
            new PDFData(45, 62, 40, 11, $personalDataSheet->getDateOfBirth()),
            new PDFData(45, 73, 40, 8, $personalDataSheet->getPlaceOfBirth()),

            new PDFData(47, 82, 5, 5, $personalDataSheet->getSexMale(), 10, 'ZapfDingbats'),
            new PDFData(65, 82, 5, 5, $personalDataSheet->getSexFemale(), 10, 'ZapfDingbats'),

            new PDFData(47, 89, 5, 5, $personalDataSheet->getCivilStatusSingle(), 10, 'ZapfDingbats'),
            new PDFData(65, 89, 5, 5, $personalDataSheet->getCivilStatusMarried(), 10, 'ZapfDingbats'),
            new PDFData(47, 93, 5, 5, $personalDataSheet->getCivilStatusWidowed(), 10, 'ZapfDingbats'),
            new PDFData(65, 93, 5, 5, $personalDataSheet->getCivilStatusSeparated(), 10, 'ZapfDingbats'),
            new PDFData(47, 97, 5, 5, $personalDataSheet->getCivilStatusOthers(), 10, 'ZapfDingbats'),

            new PDFData(45, 103, 40, 7, $personalDataSheet->getHeight()),
            new PDFData(45, 110, 40, 6, $personalDataSheet->getWeight()),
            new PDFData(45, 116, 40, 8, $personalDataSheet->getBloodType()),
            new PDFData(45, 124, 40, 7, $personalDataSheet->getGSISIDNo()),
            new PDFData(45, 131, 40, 8, $personalDataSheet->getPagIbigIDNo()),
            new PDFData(45, 139, 40, 6, $personalDataSheet->getPhilHealthNo()),
            new PDFData(45, 145, 40, 6, $personalDataSheet->getSSSNo()),
            new PDFData(45, 151, 40, 6, $personalDataSheet->getTIN()),
            new PDFData(45, 157, 40, 6, $personalDataSheet->getAgencyEmployeeNo()),

            new PDFData(133, 65, 5, 5, $personalDataSheet->getCitizenshipFilipino(), 10, 'ZapfDingbats'),
            new PDFData(159, 65, 5, 5, $personalDataSheet->getCitizenshipDualCitizenship(), 10, 'ZapfDingbats'),
            new PDFData(164, 70, 5, 5, $personalDataSheet->getCitizenshipByBirth(), 10, 'ZapfDingbats'),
            new PDFData(178, 70, 5, 5, $personalDataSheet->getCitizenshipByNaturalization(), 10, 'ZapfDingbats'),
            new PDFData(131, 81, 78, 7, $personalDataSheet->getCitizenshipCountry()),

            new PDFData(117, 88, 90, 5, $personalDataSheet->getResidentialAddressLine1()),
            new PDFData(117, 96, 90, 5, $personalDataSheet->getResidentialAddressLine2()),
            new PDFData(117, 103, 90, 5, $personalDataSheet->getResidentialAddressLine3()),
            new PDFData(117, 111, 90, 6, $personalDataSheet->getResidentialAddressZipCode()),

            new PDFData(117, 116, 90, 5, $personalDataSheet->getPermanentAddressLine1()),
            new PDFData(117, 124, 90, 5, $personalDataSheet->getPermanentAddressLine2()),
            new PDFData(117, 132, 90, 5, $personalDataSheet->getPermanentAddressLine3()),
            new PDFData(117, 139, 90, 6, $personalDataSheet->getPermanentAddressZipCode()),

            new PDFData(117, 145, 90, 6, $personalDataSheet->getTelephoneNo()),
            new PDFData(117, 151, 90, 6, $personalDataSheet->getMobileNo()),
            new PDFData(117, 157, 90, 6, $personalDataSheet->getEmailAddress()),

            new PDFData(45, 168, 72, 6, $personalDataSheet->getSpouseSurname()),
            new PDFData(45, 175, 39, 6, $personalDataSheet->getSpouseFirstName()),
            new PDFData(85, 175, 32, 6, $personalDataSheet->getSpouseNameExtension()),
            new PDFData(45, 181, 72, 6, $personalDataSheet->getSpouseMiddleName()),

            new PDFData(45, 187, 72, 6, $personalDataSheet->getSpouseOccupation()),
            new PDFData(45, 193, 72, 6, $personalDataSheet->getSpouseEmployer()),
            new PDFData(45, 200, 72, 6, $personalDataSheet->getSpouseBusinessAddress()),
            new PDFData(45, 206, 72, 6, $personalDataSheet->getSpouseTelephoneNo()),

            new PDFData(45, 212, 72, 6, $personalDataSheet->getFatherSurname()),
            new PDFData(45, 219, 39, 6, $personalDataSheet->getFatherFirstName()),
            new PDFData(85, 219, 32, 6, $personalDataSheet->getFatherNameExtension()),
            new PDFData(45, 226, 72, 6, $personalDataSheet->getFatherMiddleName()),
            new PDFData(45, 232, 72, 6, $personalDataSheet->getMotherMaidenName()),
            new PDFData(45, 238, 72, 6, $personalDataSheet->getMotherSurname()),
            new PDFData(45, 244, 72, 6, $personalDataSheet->getMotherFirstName()),
            new PDFData(45, 250, 72, 6, $personalDataSheet->getMotherMiddleName()),

            new PDFData(45, 275, 40, 6, $personalDataSheet->getElementaryEducationSchool()),
            new PDFData(45, 282, 40, 6, $personalDataSheet->getSecondaryEducationSchool()),
            new PDFData(45, 288, 40, 6, $personalDataSheet->getVocationalEducationSchool()),
            new PDFData(45, 295, 40, 6, $personalDataSheet->getCollegeEducationSchool()),
            new PDFData(45, 302, 40, 6, $personalDataSheet->getGraduateStudiesEducationSchool()),

            new PDFData(85, 275, 45, 6, $personalDataSheet->getElementaryEducationCourse()),
            new PDFData(85, 282, 45, 6, $personalDataSheet->getSecondaryEducationCourse()),
            new PDFData(85, 288, 45, 6, $personalDataSheet->getVocationalEducationCourse()),
            new PDFData(85, 295, 45, 6, $personalDataSheet->getCollegeEducationCourse()),
            new PDFData(85, 302, 45, 6, $personalDataSheet->getGraduateStudiesEducationCourse()),

            new PDFData(131, 275, 18, 6, $personalDataSheet->getElementaryEducationAttendanceFrom()),
            new PDFData(131, 282, 18, 6, $personalDataSheet->getSecondaryEducationAttendanceFrom()),
            new PDFData(131, 288, 18, 6, $personalDataSheet->getVocationalEducationAttendanceFrom()),
            new PDFData(131, 295, 18, 6, $personalDataSheet->getCollegeEducationAttendanceFrom()),
            new PDFData(131, 302, 18, 6, $personalDataSheet->getGraduateStudiesEducationAttendanceFrom()),

            new PDFData(150, 275, 13, 6, $personalDataSheet->getElementaryEducationAttendanceTo()),
            new PDFData(150, 282, 13, 6, $personalDataSheet->getSecondaryEducationAttendanceTo()),
            new PDFData(150, 288, 13, 6, $personalDataSheet->getVocationalEducationAttendanceTo()),
            new PDFData(150, 295, 13, 6, $personalDataSheet->getCollegeEducationAttendanceTo()),
            new PDFData(150, 302, 13, 6, $personalDataSheet->getGraduateStudiesEducationAttendanceTo()),

            new PDFData(163, 275, 14, 6, $personalDataSheet->getElementaryEducationUnitsEarned()),
            new PDFData(163, 282, 14, 6, $personalDataSheet->getSecondaryEducationUnitsEarned()),
            new PDFData(163, 288, 14, 6, $personalDataSheet->getVocationalEducationUnitsEarned()),
            new PDFData(163, 295, 14, 6, $personalDataSheet->getCollegeEducationUnitsEarned()),
            new PDFData(163, 302, 14, 6, $personalDataSheet->getGraduateStudiesEducationUnitsEarned()),

            new PDFData(178, 275, 15, 6, $personalDataSheet->getElementaryEducationYearGraduated()),
            new PDFData(178, 282, 15, 6, $personalDataSheet->getSecondaryEducationYearGraduated()),
            new PDFData(178, 288, 15, 6, $personalDataSheet->getVocationalEducationYearGraduated()),
            new PDFData(178, 295, 15, 6, $personalDataSheet->getCollegeEducationYearGraduated()),
            new PDFData(178, 302, 15, 6, $personalDataSheet->getGraduateStudiesEducationYearGraduated()),

            new PDFData(194, 275, 15, 6, $personalDataSheet->getElementaryEducationHonors()),
            new PDFData(194, 282, 15, 6, $personalDataSheet->getSecondaryEducationHonors()),
            new PDFData(194, 288, 15, 6, $personalDataSheet->getVocationalEducationHonors()),
            new PDFData(194, 295, 15, 6, $personalDataSheet->getCollegeEducationHonors()),
            new PDFData(194, 302, 15, 6, $personalDataSheet->getGraduateStudiesEducationHonors())
        ];

        for ($i = 0; $i < $personalDataSheet->getChildrenNames()->count(); $i++) {
            $indexInPage = $i % self::CHILDREN_PER_PAGE;
            $additionalPage = intdiv($i, self::CHILDREN_PER_PAGE);
            $this->pageData[array_search(0, $this->pageMap) + $additionalPage][] = new PDFData(118, 175 + $indexInPage * 6.5, 60, 6.5, $personalDataSheet->getChildrenNames()[$i]);
            $this->pageData[array_search(0, $this->pageMap) + $additionalPage][] = new PDFData(178, 175 + $indexInPage * 6.5, 31, 6.5, $personalDataSheet->getChildrenBirthdays()[$i]);
        }

        for ($i = 0; $i < $personalDataSheet->getCivilServiceIds()->count(); $i++) {
            $indexInPage = $i % self::CIVIL_SERVICE_ELIGIBILITIES_PER_PAGE;
            $additionalPage = intdiv($i, self::CIVIL_SERVICE_ELIGIBILITIES_PER_PAGE);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(7, 29 + $indexInPage * 7, 64, 7, $personalDataSheet->getCivilServiceIds()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(71, 29 + $indexInPage * 7, 23, 7, $personalDataSheet->getCivilServiceRatings()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(94, 29 + $indexInPage * 7, 25, 7, $personalDataSheet->getCivilServiceDates()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(119, 29 + $indexInPage * 7, 57, 7, $personalDataSheet->getCivilServicePlaces()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(175, 29 + $indexInPage * 7, 19, 7, $personalDataSheet->getCivilServiceLicenseNumbers()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(194, 29 + $indexInPage * 7, 15, 7, $personalDataSheet->getCivilServiceLicenseValidityDates()[$i], 6);
        }

        for ($i = 0; $i < $personalDataSheet->getWorkExperiencePositionTitles()->count(); $i++) {
            $indexInPage = $i % self::WORK_EXPERIENCES_PER_PAGE;
            $additionalPage = intdiv($i, self::WORK_EXPERIENCES_PER_PAGE);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(7, 109 + $indexInPage * 7, 16, 7, $personalDataSheet->getWorkExperienceDateFroms()[$i], 6);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(23, 109 + $indexInPage * 7, 16, 7, $personalDataSheet->getWorkExperienceDateTos()[$i], 6);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(39, 109 + $indexInPage * 7, 55, 7, $personalDataSheet->getWorkExperiencePositionTitles()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(94, 109 + $indexInPage * 7, 55, 7, $personalDataSheet->getWorkExperienceDepartments()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(149, 109 + $indexInPage * 7, 12, 7, $personalDataSheet->getWorkExperienceMonthlySalaries()[$i], 6);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(161, 109 + $indexInPage * 7, 15, 7, $personalDataSheet->getWorkExperiencePayGrades()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(176, 109 + $indexInPage * 7, 18, 7, $personalDataSheet->getWorkExperienceAppointmentStatuses()[$i]);
            $this->pageData[array_search(1, $this->pageMap) + $additionalPage][] = new PDFData(194, 109 + $indexInPage * 7, 15, 7, $personalDataSheet->getWorkExperienceGovernmentServices()[$i]);
        }

        for ($i = 0; $i < $personalDataSheet->getVoluntaryWorkNamesAndAddresses()->count(); $i++) {
            $indexInPage = $i % self::VOLUNTARY_WORKS_PER_PAGE;
            $additionalPage = intdiv($i, self::VOLUNTARY_WORKS_PER_PAGE);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(11, 30 + $indexInPage * 7, 63, 7, $personalDataSheet->getVoluntaryWorkNamesAndAddresses()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(74, 30 + $indexInPage * 7, 20, 7, $personalDataSheet->getVoluntaryWorkInclusiveDateFroms()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(94, 30 + $indexInPage * 7, 20, 7, $personalDataSheet->getVoluntaryWorkInclusiveDateTos()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(114, 30 + $indexInPage * 7, 17, 7, $personalDataSheet->getVoluntaryWorkNumberOfHours()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(131, 30 + $indexInPage * 7, 74, 7, $personalDataSheet->getVoluntaryWorkPositions()[$i]);
        }

        for ($i = 0; $i < $personalDataSheet->getLearningAndDevelopmentPrograms()->count(); $i++) {
            $indexInPage = $i % self::LEARNINGS_PER_PAGE;
            $additionalPage = intdiv($i, self::LEARNINGS_PER_PAGE);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(11, 107 + $indexInPage * 7, 63, 7, $personalDataSheet->getLearningAndDevelopmentPrograms()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(74, 107 + $indexInPage * 7, 20, 7, $personalDataSheet->getLearningAndDevelopmentInclusiveDateFroms()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(94, 107 + $indexInPage * 7, 20, 7, $personalDataSheet->getLearningAndDevelopmentInclusiveDateTos()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(114, 107 + $indexInPage * 7, 17, 7, $personalDataSheet->getLearningAndDevelopmentNumberOfHours()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(131, 107 + $indexInPage * 7, 21, 7, $personalDataSheet->getLearningAndDevelopmentTypes()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(152, 107 + $indexInPage * 7, 53, 7, $personalDataSheet->getLearningAndDevelopmentSponsors()[$i]);
        }

        for ($i = 0; $i < $personalDataSheet->getOtherInformationSpecialSkills()->count(); $i++) {
            $indexInPage = $i % self::OTHER_INFOS_PER_PAGE;
            $additionalPage = intdiv($i, self::OTHER_INFOS_PER_PAGE);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(11, 250 + $indexInPage * 7, 63, 7, $personalDataSheet->getOtherInformationSpecialSkills()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(74, 250 + $indexInPage * 7, 78, 7, $personalDataSheet->getOtherInformationNonAcademicDistinctions()[$i]);
            $this->pageData[array_search(2, $this->pageMap) + $additionalPage][] = new PDFData(152, 250 + $indexInPage * 7, 53, 7, $personalDataSheet->getOtherInformationMemberships()[$i]);
        }

        $this->pageData[array_search(3, $this->pageMap)] = [
            new PDFData(132, 23, 5, 5, $personalDataSheet->getThirdDegreeRelativeYes(), 10, 'ZapfDingbats'),
            new PDFData(154, 23, 5, 5, $personalDataSheet->getThirdDegreeRelativeNo(), 10, 'ZapfDingbats'),
            new PDFData(132, 28, 5, 5, $personalDataSheet->getFourthDegreeRelativeYes(), 10, 'ZapfDingbats'),
            new PDFData(154, 28, 5, 5, $personalDataSheet->getFourthDegreeRelativeNo(), 10, 'ZapfDingbats'),
            new PDFData(134, 36, 70, 6, $personalDataSheet->getThirdAndFourthRelativeDetails()),

            new PDFData(132, 45, 5, 5, $personalDataSheet->getGuiltyOfAdministrativeOffenseYes(), 10, 'ZapfDingbats'),
            new PDFData(154, 45, 5, 5, $personalDataSheet->getGuiltyOfAdministrativeOffenseNo(), 10, 'ZapfDingbats'),
            new PDFData(134, 53, 70, 6, $personalDataSheet->getGuiltyOfAdministrativeOffenseDetails()),

            new PDFData(132, 62, 5, 5, $personalDataSheet->getCriminallyChargedYes(), 10, 'ZapfDingbats'),
            new PDFData(155, 62, 5, 5, $personalDataSheet->getCriminallyChargedNo(), 10, 'ZapfDingbats'),
            new PDFData(156, 70, 50, 6, $personalDataSheet->getCriminallyChargedDates()),
            new PDFData(156, 75, 50, 6, $personalDataSheet->getCriminallyChargedStatuses()),

            new PDFData(132, 84, 5, 5, $personalDataSheet->getCrimeConvictionYes(), 10, 'ZapfDingbats'),
            new PDFData(156, 84, 5, 5, $personalDataSheet->getCrimeConvictionNo(), 10, 'ZapfDingbats'),
            new PDFData(134, 92, 70, 6, $personalDataSheet->getCrimeConvictionDetails()),

            new PDFData(132, 101, 5, 5, $personalDataSheet->getSeparatedFromServiceYes(), 10, 'ZapfDingbats'),
            new PDFData(157, 101, 5, 5, $personalDataSheet->getSeparatedFromServiceNo(), 10, 'ZapfDingbats'),
            new PDFData(134, 107, 70, 6, $personalDataSheet->getSeparatedFromServiceDetails()),

            new PDFData(132, 115, 5, 5, $personalDataSheet->getElectionCandidateYes(), 10, 'ZapfDingbats'),
            new PDFData(158, 115, 5, 5, $personalDataSheet->getElectionCandidateNo(), 10, 'ZapfDingbats'),
            new PDFData(157, 118, 50, 6, $personalDataSheet->getElectionCandidateDetails()),

            new PDFData(132, 126, 5, 5, $personalDataSheet->getResignedFromGovernmentYes(), 10, 'ZapfDingbats'),
            new PDFData(158, 126, 5, 5, $personalDataSheet->getResignedFromGovernmentNo(), 10, 'ZapfDingbats'),
            new PDFData(157, 130, 50, 6, $personalDataSheet->getResignedFromGovernmentDetails()),

            new PDFData(132, 138, 5, 5, $personalDataSheet->getMultipleResidencyYes(), 10, 'ZapfDingbats'),
            new PDFData(158, 138, 5, 5, $personalDataSheet->getMultipleResidencyNo(), 10, 'ZapfDingbats'),
            new PDFData(135, 146, 70, 6, $personalDataSheet->getMultipleResidencyCountry()),

            new PDFData(132, 163, 5, 5, $personalDataSheet->getIndigenousYes(), 10, 'ZapfDingbats'),
            new PDFData(159, 163, 5, 5, $personalDataSheet->getIndigenousNo(), 10, 'ZapfDingbats'),
            new PDFData(171, 166, 38, 6, $personalDataSheet->getIndigenousGroup()),

            new PDFData(132, 172, 5, 5, $personalDataSheet->getDisabledYes(), 10, 'ZapfDingbats'),
            new PDFData(159, 172, 5, 5, $personalDataSheet->getDisabledNo(), 10, 'ZapfDingbats'),
            new PDFData(171, 176, 38, 6, $personalDataSheet->getPWDId()),

            new PDFData(132, 181, 5, 5, $personalDataSheet->getSoloParentYes(), 10, 'ZapfDingbats'),
            new PDFData(159, 181, 5, 5, $personalDataSheet->getSoloParentNo(), 10, 'ZapfDingbats'),
            new PDFData(171, 184, 38, 6, $personalDataSheet->getSoloParentId()),

            new PDFData(34, 263, 47, 6, $personalDataSheet->getGovernmentIssuedID()),
            new PDFData(34, 270, 47, 6,  $personalDataSheet->getGovernmentIDNo()),
            new PDFData(34, 276, 47, 7, $personalDataSheet->getGovernmentIDDateAndPlaceOfIssue()),

            // new PDFData(140, 300, 60, 7, "CS FORM 212 (Revised 2017), Page 4 of 4", 7, 'Helvetica', 'I'),
        ];

        for ($i = 0; $i < $personalDataSheet->getReferenceNames()->count(); $i++) {
            $this->pageData[array_search(3, $this->pageMap)][] = new PDFData(7, 205 + $i * 8, 80, 7, $personalDataSheet->getReferenceNames()[$i]);
            $this->pageData[array_search(3, $this->pageMap)][] = new PDFData(87, 205 + $i * 8, 41, 7, $personalDataSheet->getReferenceAddresses()[$i]);
            $this->pageData[array_search(3, $this->pageMap)][] = new PDFData(128, 205 + $i * 8, 26, 7, $personalDataSheet->getReferenceTelNos()[$i]);
        }
    }
}
