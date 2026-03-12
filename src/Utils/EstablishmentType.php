<?php

namespace App\Utils;

enum EstablishmentType: string
{
    case HOSPITAL = 'Hopital';
    case CLINIC = 'Clinique';
    case PHARMACY = 'Pharmacie';
    case LABORATORY = 'Laboratoire';
    case RADIOLOGY = 'Radiologie';
    case PRIVATE_PRACTICE = 'Consultation privée';
    case OTHER = 'Autre';
}