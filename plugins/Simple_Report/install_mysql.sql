-- Add profile exceptions (to make it possible for users to access the page)
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT staff_id, 'Students/Report.php', 'Y', 'Y'
FROM staff WHERE profile LIKE 'admin';
