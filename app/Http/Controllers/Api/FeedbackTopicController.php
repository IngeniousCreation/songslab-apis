<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeedbackTopic;
use Illuminate\Http\Request;

class FeedbackTopicController extends Controller
{
    /**
     * Get all active feedback topics
     */
    public function index()
    {
        $topics = FeedbackTopic::active()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'topics' => $topics,
            ],
        ]);
    }
}

