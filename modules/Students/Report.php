<?php

DrawHeader('Simple Report');

// Get student enrollment data
$SimpleReport = DBGet("SELECT DISTINCT
    s.STUDENT_ID,
    concat(s.FIRST_NAME,' ',s.LAST_NAME) AS FULL_NAME,
    se.START_DATE,
    se.SYEAR as ENROLLMENT_YEAR,
    GROUP_CONCAT(DISTINCT c.TITLE SEPARATOR ', ') as COURSES
FROM students s
INNER JOIN student_enrollment se ON (se.STUDENT_ID = s.STUDENT_ID)
LEFT JOIN schedule sch ON (sch.STUDENT_ID=s.STUDENT_ID)
LEFT JOIN courses c ON c.COURSE_ID=sch.COURSE_ID
ORDER BY s.LAST_NAME, s.FIRST_NAME");

// Define columns for the report
$columns = [
    'STUDENT_ID' => _('Student ID'),
    'FULL_NAME' => _('Student Full Name'),
    'ENROLLMENT_YEAR' => _('Enrollment Year'),
    'START_DATE' => _('Enrollment Date'),
    'COURSES' => _('Courses')
];

// Output the report
ListOutput(
    $SimpleReport,
    $columns,
    'Student',
    'Students'
);
