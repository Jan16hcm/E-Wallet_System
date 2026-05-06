<?php
    function isValidDate(string $date){
        //return error string, empty string if success
        // Try to create a DateTime object from the given format
        $dt = DateTime::createFromFormat('d/m/Y', $date);
        if ($dt == false) {
            return 'Could not parse date';
        }
        // Check for warnings/errors from parsing
        $errors = DateTime::getLastErrors();
        if ($errors['warning_count'] > 0 || $errors['error_count'] > 0) {
            return 'Invalid date';
        }
        // Ensure the formatted date matches exactly (prevents partial matches)
        return $dt->format('d/m/Y') == $date ? '' : 'Wrong format, please use date/month/year';
    }
?>