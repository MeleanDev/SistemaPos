<?php

namespace App\Http\Controllers;

use App\Traits\HasEmpresaActiva;

abstract class Controller
{
    use HasEmpresaActiva;
}
