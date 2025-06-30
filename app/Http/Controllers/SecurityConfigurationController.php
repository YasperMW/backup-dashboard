<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SecurityConfigurationController extends Controller
{
    /**
     * Show the security configuration settings page.
     */
    public function show(): View
    {
        return view('profile.security-configuration-settings');
    }
} 