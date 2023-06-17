<?php

namespace App\Http\Controllers;

use App\Services\PatientService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    protected $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'birthdate' => 'required|date',
        ]);

        $patient = $this->patientService->create($validatedData);

        return response()->json($patient);
    }

    public function index()
    {
        $patients = $this->patientService->getCachedPatients();

        return response()->json($patients);
    }
}
