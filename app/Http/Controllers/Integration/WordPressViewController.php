<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\UserOpenai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WordPressViewController extends Controller
{
    /**
     * Show the WordPress connections management page.
     */
    public function connections(): View
    {
        return view('panel.user.wordpress.connections');
    }

    /**
     * Show the WordPress publish page for a specific content.
     */
    public function publish(Request $request, string $contentId): View
    {
        // Validate that the content exists and belongs to the user
        $content = UserOpenai::where('id', $contentId)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$content) {
            abort(404, 'Content not found');
        }
        
        return view('panel.user.wordpress.publish', [
            'contentId' => $contentId,
        ]);
    }

    /**
     * Show the WordPress publish history page.
     */
    public function history(): View
    {
        return view('panel.user.wordpress.history');
    }
}