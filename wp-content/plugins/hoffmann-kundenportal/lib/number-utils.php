<?php
if (!function_exists('hoffmann_to_float')) {
    function hoffmann_to_float($value) {
        if (is_string($value)) {
            $value = str_replace([' ', "\xC2\xA0"], '', $value); // remove spaces & NBSP
            if (strpos($value, ',') !== false) {
                $value = str_replace('.', '', $value); // remove thousand separators
                $value = str_replace(',', '.', $value);
            }
        }
        return floatval($value);
    }
}

if (!function_exists('hoffmann_to_int')) {
    function hoffmann_to_int($value) {
        return (int)hoffmann_to_float($value);
    }
}
?>
