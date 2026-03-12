<?php

namespace App\Utils;

enum MedicalHistoryCategory: string
{
    case CONSULTATION = 'CONSULTATION';
    case DIAGNOSIS = 'DIAGNOSIS';
    case PROCEDURE = 'PROCEDURE';
    case SURGERY = 'SURGERY';
    case LAB_TEST = 'LAB_TEST';
    case PRESCRIPTION = 'PRESCRIPTION';
    case VACCINATION = 'VACCINATION';
    case ALLERGY = 'ALLERGY';
    case IMAGING = 'IMAGING';
    case OTHER = 'OTHER';
}
