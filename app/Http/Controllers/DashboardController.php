<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        return view('dashboard', [
            'totalWebsites' => Website::count(),
            'activeWebsites' => Website::where('is_active', true)->count(),
            'downWebsites' => Website::where(
                'current_status',
                'down'
            )->count(),
            'unknownWebsites' => Website::where(
                'current_status',
                'unknown'
            )->count(),
            'recentWebsites' => Website::query()
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
