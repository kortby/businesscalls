<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageReceived;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationsController extends Controller
{
    /**
     * Display a list of the conversations.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user && $user->tenant_id) {
            TenantScope::setTenantId($user->tenant_id);
        }

        $conversations = Conversation::with(['messages', 'user'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Conversations/Index', [
            'tenant' => $user ? Tenant::find($user->tenant_id) : null,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Store a newly created chat message in storage.
     */
    public function storeMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        if ($user && $user->tenant_id) {
            TenantScope::setTenantId($user->tenant_id);
        }

        // Authorize conversation belongs to user's tenant
        if ($conversation->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'agent',
            'body' => $validated['body'],
        ]);

        // Touch conversation updated_at
        $conversation->touch();

        // Broadcast to Reverb real-time channel
        event(new ChatMessageReceived($user->tenant_id, $message));

        return redirect()->back();
    }
}
