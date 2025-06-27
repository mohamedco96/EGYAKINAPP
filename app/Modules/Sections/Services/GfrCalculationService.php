<?php

namespace App\Modules\Sections\Services;

class GfrCalculationService
{
    /**
     * Calculate GFR for CKD using CKD-EPI equation.
     *
     * @param string|null $gender
     * @param int|null $age
     * @param float|null $creatinine
     * @return float
     */
    public function calculateCkdGfr($gender, $age, $creatinine): float
    {
        if (is_null($gender) || $age === 0 || $creatinine === 0) {
            return 0;
        }

        if ($gender === 'Male') {
            $A = 0.9;
            $B = ($creatinine <= 0.9) ? -0.302 : -1.200;
            $gfr = 142 * pow($creatinine / $A, $B) * pow(0.9938, $age);
        } else {
            $A = 0.7;
            $B = ($creatinine <= 0.7) ? -0.241 : -1.200;
            $gfr = 142 * pow($creatinine / $A, $B) * pow(0.9938, $age) * 1.012;
        }

        return (float) number_format($gfr, 2, '.', '');
    }

    /**
     * Calculate Sobh Ccr (Creatinine Clearance).
     *
     * @param int|null $age
     * @param float|null $weight
     * @param float|null $height
     * @param float|null $serumCreatinine
     * @return float
     */
    public function calculateSobhCcr($age, $weight, $height, $serumCreatinine): float
    {
        // Check for null values or zero serum creatinine
        if (is_null($age) || is_null($weight) || is_null($height) || is_null($serumCreatinine) || $serumCreatinine == 0) {
            return 0;
        }

        $ccr = ((140 - $age) / $serumCreatinine) *
            pow($weight, 0.54) *
            pow($height, 0.40) *
            0.014;

        return (float) number_format($ccr, 2, '.', '');
    }

    /**
     * Calculate GFR using MDRD equation.
     *
     * @param float $serumCr Serum Creatinine level
     * @param int $age Age of the patient
     * @param string $race Race of the patient ('yes' for black, other for non-black)
     * @param string $gender Gender of the patient
     * @return float Calculated GFR
     */
    public function calculateMdrdGfr($serumCr, $age, $race, $gender): float
    {
        $genderFactor = ($gender === 'Female') ? 0.742 : 1.0;
        $raceFactor = ($race === 'yes') ? 1.212 : 1.0;
        
        $constant = 175.0;
        $ageFactor = pow($age, -0.203);
        $serumCrFactor = pow($serumCr, -1.154);

        $mdrd = $constant * $serumCrFactor * $ageFactor * $raceFactor * $genderFactor;

        return (float) number_format($mdrd, 2, '.', '');
    }

    /**
     * Calculate all GFR values for a patient.
     *
     * @param array $patientData
     * @return array
     */
    public function calculateAllGfrValues(array $patientData): array
    {
        $gfr = [
            'ckd' => [
                'current_GFR' => '0',
                'basal_creatinine_GFR' => '0',
                'creatinine_on_discharge_GFR' => '0',
            ],
            'sobh' => [
                'current_GFR' => '0',
                'basal_creatinine_GFR' => '0',
                'creatinine_on_discharge_GFR' => '0',
            ],
            'mdrd' => [
                'current_GFR' => '0',
                'basal_creatinine_GFR' => '0',
                'creatinine_on_discharge_GFR' => '0',
            ],
        ];

        $gender = $patientData['gender'] ?? null;
        $age = floatval($patientData['age'] ?? 0);
        $height = floatval($patientData['height'] ?? 0);
        $weight = floatval($patientData['weight'] ?? 0);
        $race = $patientData['race'] ?? null;

        $creatinineValues = [
            'current' => floatval($patientData['current_creatinine'] ?? 0),
            'basal' => floatval($patientData['basal_creatinine'] ?? 0),
            'discharge' => floatval($patientData['creatinine_on_discharge'] ?? 0),
        ];

        foreach ($creatinineValues as $type => $creatinine) {
            if ($this->isValidForCalculation($gender, $age, $height, $weight, $race, $creatinine)) {
                $gfrKey = match($type) {
                    'current' => 'current_GFR',
                    'basal' => 'basal_creatinine_GFR',
                    'discharge' => 'creatinine_on_discharge_GFR',
                };

                $gfr['ckd'][$gfrKey] = $this->calculateCkdGfr($gender, $age, $creatinine);
                $gfr['sobh'][$gfrKey] = $this->calculateSobhCcr($age, $weight, $height, $creatinine);
                $gfr['mdrd'][$gfrKey] = $this->calculateMdrdGfr($creatinine, $age, $race, $gender);
            }
        }

        return $gfr;
    }

    /**
     * Check if all required parameters are valid for GFR calculation.
     *
     * @param mixed $gender
     * @param mixed $age
     * @param mixed $height
     * @param mixed $weight
     * @param mixed $race
     * @param mixed $creatinine
     * @return bool
     */
    private function isValidForCalculation($gender, $age, $height, $weight, $race, $creatinine): bool
    {
        return !is_null($gender) && 
               !is_null($age) && $age != 0 &&
               !is_null($height) && $height != 0 && 
               !is_null($weight) && $weight != 0 &&
               !is_null($race) && 
               !is_null($creatinine) && $creatinine != 0;
    }
}
