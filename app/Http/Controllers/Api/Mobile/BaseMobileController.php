<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

/**
 * Base controller for mobile API endpoints without permission middleware
 */
abstract class BaseMobileController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    // No __construct() method = no permission assignment
}