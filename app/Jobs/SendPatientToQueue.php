<?php

namespace App\Jobs;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPatientToQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $patient;

    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
    }

    public function handle()
    {
        // Здесь вы можете добавить логику отправки пациента в очередь.
        // Привожу пример такого кода
        //                        |
        //                        V
        // Добавить use Illuminate\Support\Facades\Redis;
        /* $patientData = [
            'name' => $this->patient->first_name.' '.$this->patient->last_name,
            'birthdate' => $this->patient->birthdate->format('d.m.Y'),
            'age' => $this->patient->age.' '.$this->patient->age_type,
        ];

        Redis::rpush('patients', json_encode($patientData)); */
    }
}
