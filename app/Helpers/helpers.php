<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('guard')) {
    function guard()
    {
        if (Auth::guard('web')->check()) {
            return 'web';
        } elseif (Auth::guard('employee')->check()) {
            return 'employee';
        }
        return null;
    }
}

if (!function_exists('shortEncrypt')) {
    function shortEncrypt($string)
    {
        $key = 'fA7xB93kL0pTzWmQ';
        $cipher = 'AES-128-ECB';
        return rtrim(strtr(base64_encode(openssl_encrypt($string, $cipher, $key, 0)), '+/', '-_'), '=');
    }
}

if (!function_exists('shortDecrypt')) {
    function shortDecrypt($encrypted)
    {
        $key = 'fA7xB93kL0pTzWmQ';
        $cipher = 'AES-128-ECB';
        $encrypted = strtr($encrypted, '-_', '+/');
        return openssl_decrypt(base64_decode($encrypted), $cipher, $key, 0);
    }
}

if (!function_exists('formatLeaveDateRange')) {
    function formatLeaveDateRange($dateRange, $days = 0) {
        if (empty($dateRange)) {
            return ['formatted' => '', 'calculatedDays' => $days ?? 0];
        }
        if (strpos($dateRange, ' to ') !== false) {
            [$sDate, $eDate] = explode(' to ', $dateRange);
            try {
                $startCarbon = \Carbon\Carbon::parse(trim($sDate));
                $endCarbon = \Carbon\Carbon::parse(trim($eDate));
                $formatted = $startCarbon->format('M d, Y') . ' - ' . $endCarbon->format('M d, Y');
                
                $calculatedDays = 0;
                $tempDate = $startCarbon->copy();
                while ($tempDate->lte($endCarbon)) {
                    if (!$tempDate->isWeekend()) {
                        $calculatedDays++;
                    }
                    $tempDate->addDay();
                }
                if ($calculatedDays == 0 && !empty($days)) {
                    $calculatedDays = $days;
                }
                return ['formatted' => $formatted, 'calculatedDays' => $calculatedDays];
            } catch (\Exception $e) {
                return ['formatted' => $dateRange, 'calculatedDays' => $days ?? 0];
            }
        } else {
            try {
                $startCarbon = \Carbon\Carbon::parse(trim($dateRange));
                $formatted = $startCarbon->format('M d, Y');
                $calculatedDays = $startCarbon->isWeekend() ? ($days ?? 0) : 1;
                if ($calculatedDays == 0 && !empty($days)) {
                    $calculatedDays = $days;
                }
                return ['formatted' => $formatted, 'calculatedDays' => $calculatedDays];
            } catch (\Exception $e) {
                return ['formatted' => $dateRange, 'calculatedDays' => $days ?? 0];
            }
        }
    }
}

if (!function_exists('calculateServiceDuration')) {
    function calculateServiceDuration($hireDate) {
        if (empty($hireDate)) {
            return '';
        }
        try {
            $startDate = new \DateTime($hireDate);
            $endDate = new \DateTime();
            $interval = $startDate->diff($endDate);
            return $interval->y . ' years ' . $interval->m . ' months';
        } catch (\Exception $e) {
            return '';
        }
    }
}


