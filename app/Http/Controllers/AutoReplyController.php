<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AutoReply;
use Illuminate\Validation\Rule;

class AutoReplyController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        return Inertia::render('AutoReplies/Index', [
            'autoReplies' => AutoReply::where('user_id', $user->id)->latest()->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'keyword' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('auto_replies')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                })
            ],
            'reply_message' => 'required|string',
        ]);

        AutoReply::create([
            'user_id' => $user->id,
            'keyword' => $request->keyword,
            'reply_message' => $request->reply_message,
            'is_active' => true,
        ]);

        return back()->with('success', 'Auto-reply created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $autoReply = AutoReply::where('user_id', $user->id)->findOrFail($id);

        $request->validate([
            'keyword' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('auto_replies')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                })->ignore($autoReply->id)
            ],
            'reply_message' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $autoReply->update($request->only('keyword', 'reply_message', 'is_active'));

        return back()->with('success', 'Auto-reply updated successfully.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        AutoReply::where('user_id', $user->id)->findOrFail($id)->delete();

        return back()->with('success', 'Auto-reply deleted successfully.');
    }
}
