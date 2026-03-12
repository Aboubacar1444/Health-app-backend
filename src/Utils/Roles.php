<?php
namespace App\Utils;
enum Roles: string {
    case ROLE_USER = 'ROLE_USER';
    // APP WEB //

    case ROLE_BACKOFFICE = 'ROLE_BACKOFFICE';
    case ROLE_GESTION = 'ROLE_GESTION';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    
    // APP MOBILE //

    case ROLE_MEDECIN = 'ROLE_MEDECIN';
    case ROLE_ETUDIANT = 'ROLE_ETUDIANT';
    case ROLE_PHARMACIEN = 'ROLE_PHARMACIEN';
}