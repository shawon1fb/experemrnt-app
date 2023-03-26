<?php

namespace App\Http\Controllers;

use App\Events\SendMessage;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function chat()
    {
        return view('chat');
    }

    public function messages()
    {
        return Message::with('user')->get();
    }

    /**
     * Store a new message for the authenticated user.
     *
     * @param Request $request
     * @return string|\Illuminate\Http\Response
     */
    public function messageStore(Request $request)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response('Unauthorized', 401);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Validate the request data
        $validatedData = $request->validate([
            'message' => 'required|max:255',
        ]);

        // Sanitize the message data before storing
        $sanitizedMessage = filter_var($validatedData['message'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Use prepared statements to insert the message into the database
        $message = $user->messages()->create([
            'message' => $sanitizedMessage,
        ]);

        // Clear the input data from the request object
        $request->replace(['message' => null]);

        // broadcast message to all users
        broadcast(new SendMessage($user, $message))->toOthers();

        // Return success message
        return 'Message sent';
    }
}
