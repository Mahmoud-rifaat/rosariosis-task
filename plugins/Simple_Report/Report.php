<?php

DrawHeader(ProgramTitle());

// Get student enrollment data
$students_RET = DBGet("SELECT DISTINCT
    s.STUDENT_ID,
    s.FIRST_NAME,
    s.LAST_NAME,
    s.USERNAME,
    se.GRADE_ID,
    se.START_DATE,
    se.END_DATE,
    se.SYEAR as ENROLLMENT_YEAR,
    se.ENROLLMENT_CODE,
    sec.TITLE as ENROLLMENT_STATUS,
    sgl.SHORT_NAME as GRADE_LEVEL,
    c.TITLE as COURSE_TITLE,
    cp.TITLE as COURSE_PERIOD,
    cp.ROOM,
    sub.TITLE as SUBJECT
FROM students s
INNER JOIN student_enrollment se ON (se.STUDENT_ID=s.STUDENT_ID AND se.SYEAR='" . UserSyear() . "')
INNER JOIN student_enrollment_codes sec ON sec.id=se.ENROLLMENT_CODE
INNER JOIN school_gradelevels sgl ON sgl.ID=se.GRADE_ID
LEFT JOIN schedule sch ON (sch.STUDENT_ID=s.STUDENT_ID AND sch.SYEAR=se.SYEAR)
LEFT JOIN course_periods cp ON cp.COURSE_PERIOD_ID=sch.COURSE_PERIOD_ID
LEFT JOIN courses c ON c.COURSE_ID=cp.COURSE_ID
LEFT JOIN course_subjects sub ON sub.SUBJECT_ID=c.SUBJECT_ID
WHERE se.SCHOOL_ID='" . UserSchool() . "'
AND (se.END_DATE IS NULL OR se.END_DATE >= CURRENT_DATE)
ORDER BY s.LAST_NAME, s.FIRST_NAME, c.TITLE");

// Define columns for the report
$columns = [
    'STUDENT_ID' => _('Student ID'),
    'FULL_NAME' => _('Student Name'),
    'USERNAME' => _('Username'),
    'GRADE_LEVEL' => _('Grade Level'),
    'ENROLLMENT_YEAR' => _('Enrollment Year'),
    'START_DATE' => _('Enrollment Date'),
    'END_DATE' => _('End Date'),
    'ENROLLMENT_STATUS' => _('Enrollment Status'),
    'SUBJECT' => _('Subject'),
    'COURSE_TITLE' => _('Course'),
    'COURSE_PERIOD' => _('Period'),
    'ROOM' => _('Room')
];

// Add link to student info
$link['FULL_NAME']['link'] = 'Modules.php?modname=Students/Student.php';
$link['FULL_NAME']['variables'] = ['student_id' => 'STUDENT_ID'];

// Output the report
ListOutput($students_RET, $columns, 'Student', 'Students', $link);
