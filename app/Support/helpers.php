<?php

if (! function_exists('rupees')) {
    /** Formats a number as ₹ using the Indian digit-grouping convention (e.g. 1234567 -> ₹12,34,567). */
    function rupees(int|float $amount): string
    {
        $amount = round((float) $amount);
        $negative = $amount < 0;
        $digits = (string) abs($amount);

        if (strlen($digits) <= 3) {
            $formatted = $digits;
        } else {
            $lastThree = substr($digits, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($digits, 0, -3));
            $formatted = "{$rest},{$lastThree}";
        }

        return ($negative ? '−₹' : '₹').$formatted;
    }
}
