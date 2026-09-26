<?php

namespace App\Helpers;

use App\Models\Employee;

class TradeClassifier
{
    /**
     * Normalize a service type, skill name, or user description into a canonical trade category.
     */
    public static function normalizeTradeCategory(string $input): string
    {
        $input = strtolower(trim($input));
        if ($input === '') {
            return '';
        }

        // 1. Roofing & Gutters (check first to prevent "roof leak" hitting plumbing)
        if (preg_match('/(roof|gutter|siding|shingle|fascia|downspout|skylight|flashing|handyman)/i', $input)) {
            return 'roofing';
        }

        // 2. Locksmith & Security
        if (preg_match('/(locksmith|locks?|rekey|deadbolt|keypad|lockout|cylinder|padlock|smart[- ]?lock)/i', $input)) {
            return 'locksmith';
        }

        // 3. Appliance Repair
        if (preg_match('/(appliance|refrigerator|fridge|freezer|wash|dryer|dishwasher|stove|oven|range|cooktop|microwave|ice[- ]?maker)/i', $input)) {
            return 'appliance';
        }

        // 4. HVAC / Heating & Air Conditioning (must precede electrical)
        if (in_array($input, ['ac', 'a/c', 'hvac', 'a.c.']) || preg_match('/(hvac|\b(a\/?c|ac)\b|air[- ]?condition|heating|cooling|furnace|duct|thermostat|ventilation|mini[- ]?split|ductless|heat[- ]?pump|chiller|boiler|compressor|freon|air[- ]?handler|climate)/i', $input)) {
            return 'hvac';
        }

        // 5. Electrical
        if (preg_match('/(electr|breaker|outlet|wir|light|panel|generator|voltage|surge|ev[- ]?charg|circuit|switch|rewir|meter|ceiling[- ]?fan)/i', $input)) {
            return 'electrical';
        }

        // 6. Plumbing
        if (preg_match('/(plumb|plumm|drain|sewer|pipe|piping|water[- ]?heater|toilet|faucet|sink|leak|clog|rooter|sump|fittings|backflow|garbage[- ]?disposal|water[- ]?line|water[- ]?softener|gas[- ]?line)/i', $input)) {
            return 'plumbing';
        }

        return $input;
    }

    /**
     * Determine if an employee matches a requested service type or skill description.
     */
    public static function employeeMatches(Employee $employee, string $serviceTypeInput): bool
    {
        $input = trim($serviceTypeInput);
        if ($input === '') {
            return true;
        }

        if (! is_array($employee->skills) || empty($employee->skills)) {
            return false;
        }

        $targetCategory = static::normalizeTradeCategory($input);
        $knownCategories = ['roofing', 'locksmith', 'appliance', 'hvac', 'electrical', 'plumbing'];
        $isTargetKnownCategory = in_array($targetCategory, $knownCategories, true);

        foreach ($employee->skills as $skill) {
            $skillStr = strtolower(trim((string) $skill));
            $skillCategory = static::normalizeTradeCategory($skillStr);

            // 1. Canonical category match (e.g. both 'hvac' or both 'plumbing')
            if ($isTargetKnownCategory && $skillCategory === $targetCategory) {
                return true;
            }

            // 2. Direct exact match
            if ($skillStr === strtolower($input)) {
                return true;
            }

            // 3. Fallback for non-canonical categories: substring match
            if (! $isTargetKnownCategory) {
                if (str_contains($skillStr, strtolower($input)) || str_contains(strtolower($input), $skillStr)) {
                    return true;
                }
            }
        }

        return false;
    }
}
