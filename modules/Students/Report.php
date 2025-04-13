<?php
DrawHeader(ProgramTitle());

// Add your report logic here
$students_RET = DBGet("SELECT 
    s.STUDENT_ID,
    s.FIRST_NAME,
    s.LAST_NAME,
    se.GRADE_ID,
    se.START_DATE,
    se.ENROLLMENT_CODE, 
    sec.TITLE
FROM students s
INNER JOIN student_enrollment se ON (se.STUDENT_ID=s.STUDENT_ID AND se.SYEAR='" . UserSyear() . "')
INNER JOIN student_enrollment_codes sec ON sec.id=se.ENROLLMENT_CODE
WHERE se.SCHOOL_ID='" . UserSchool() . "'
AND (se.END_DATE IS NULL OR se.END_DATE >= CURRENT_DATE)
ORDER BY s.LAST_NAME, s.FIRST_NAME");

$columns = [
    'STUDENT_ID' => _('Student ID'),
    'FIRST_NAME' => _('First Name'),
    'LAST_NAME' => _('Last Name'),
    'GRADE_ID' => _('Grade Level'),
    'START_DATE' => _('Enrollment Date'),
    'TITLE' => _('Enrollment Status')
];

ListOutput($students_RET, $columns, 'Student', 'Students');
