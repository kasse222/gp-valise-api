<?php

namespace App\Status;

enum UserRole: string
{
    case VOYAGEUR = 'voyageur';
    case EXPEDITEUR = 'expediteur';
    case ADMIN = 'admin';
}
