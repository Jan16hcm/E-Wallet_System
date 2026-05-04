<?php
    function isValidDate(string $date){
        // Try to create a DateTime object from the given format
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt == false) {
            return false; // Could not parse at all
        }
        // Check for warnings/errors from parsing
        $errors = DateTime::getLastErrors();
        if ($errors['warning_count'] > 0 || $errors['error_count'] > 0) {
            return false; // Invalid date (e.g., 2024-02-30)
        }
        // Ensure the formatted date matches exactly (prevents partial matches)
        return $dt->format('Y-m-d') == $date;
    }


?>