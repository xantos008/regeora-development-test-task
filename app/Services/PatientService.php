<?php

namespace App\Services;

use App\Jobs\SendPatientToQueue;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PatientService
{
    public function create(array $data): Patient
    {
        $birthdate = Carbon::parse($data['birthdate']);
        $age = Carbon::now()->diff($birthdate);
        $ageType = $this->getAgeType($age);

        $patient = Patient::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birthdate' => $birthdate,
            'age' => $age->{$ageType},
            'age_type' => $ageType,
        ]);

        $patientData = [
            'name' => $patient->first_name.' '.$patient->last_name,
            'birthdate' => $patient->birthdate->format('d.m.Y'),
            'age' => $patient->age.' '.$patient->age_type,
        ];

        Cache::put('patient_'.$patient->id, $patientData, 300);
        SendPatientToQueue::dispatch($patient);

        return $patient;
    }

    private function getAgeType($age): string
    {
        if ($age->y > 0) {
            return 'year';
        }

        if ($age->m > 0) {
            return 'month';
        }

        return 'day';
    }

    public function getCachedPatients()
    {
        return Cache::remember('patients', 5 * 60, function () {
            return $this->getAllPatients();
        });
    }

    public function getAllPatients(): array
    {
        $patients = Patient::all()->map(function ($patient) {
            return [
                'name' => $patient->first_name . ' ' . $patient->last_name,
                'birthdate' => $patient->birthdate->format('d.m.Y'),
                'age' => $patient->age . ' ' . $patient->age_type
            ];
        });

        return $patients->toArray();
    }
}
