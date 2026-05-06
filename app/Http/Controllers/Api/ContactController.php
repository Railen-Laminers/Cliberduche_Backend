<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormMail;
use App\Mail\ContactAcknowledgmentMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Handle contact form submission
     */
    public function send(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255',
            'message' => 'required|string|min:10|max:2000',
            'privacyAccepted' => 'required|accepted',
        ], [
            'privacyAccepted.accepted' => 'You must accept the Privacy Policy to submit this form.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed. Please check your input.'
            ], 422);
        }

        try {
            // Prepare clean data
            $data = $request->only(['subject', 'name', 'email', 'message', 'privacyAccepted']);

            // 1. Send email to admin
            Mail::send(new ContactFormMail($data));

            // 2. Send acknowledgment email to the user
            Mail::send(new ContactAcknowledgmentMail($data));

            // Log successful submission
            Log::info('Contact form submitted and acknowledgment sent', [
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'privacy_accepted' => $data['privacyAccepted'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your message has been sent successfully.'
            ], 200);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_data' => $request->only(['name', 'email']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sorry, we could not send your message at this time. Please try again later.'
            ], 500);
        }
    }
}