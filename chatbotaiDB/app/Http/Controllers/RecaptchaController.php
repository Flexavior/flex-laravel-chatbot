<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RecaptchaController extends Controller
{
	public function validateRecaptcha(Request $request)
    {
        $recaptchaToken = $request->input('token');
        $action = $request->input('action');

        if (!$recaptchaToken || !$action) {
            return response()->json(['success' => false, 'message' => 'Token and action are required'], 400);
        }

        $secretKey = env('RECAPTCHA_SECRET_KEY');

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $recaptchaToken,
        ]);

        $result = $response->json();

        if ($result['success'] && $result['action'] === $action && $result['score'] > 0.5) {
            return response()->json(['success' => true, 'score' => $result['score']]);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed reCAPTCHA validation']);
        }
    }    
}
