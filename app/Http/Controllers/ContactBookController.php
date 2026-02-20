<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactBook;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactBookController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $contactBooks = ContactBook::where('user_id', $user->id)
            ->withCount('contacts')
            ->latest()
            ->get();

        return Inertia::render('ContactBooks/Index', [
            'contactBooks' => $contactBooks,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        ContactBook::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Contact book created successfully.');
    }

    public function show($id)
    {
        $user = auth()->user();
        $contactBook = ContactBook::where('user_id', $user->id)
            ->withCount('contacts')
            ->findOrFail($id);

        $contacts = $contactBook->contacts()->latest()->paginate(15);

        $allContacts = Contact::where('user_id', $user->id)
            ->whereNotIn('id', $contactBook->contacts()->pluck('contacts.id'))
            ->get();

        return Inertia::render('ContactBooks/Show', [
            'contactBook' => $contactBook,
            'contacts' => $contacts,
            'allContacts' => $allContacts,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $contactBook = ContactBook::where('user_id', $user->id)->findOrFail($id);

        $contactBook->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Contact book updated successfully.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $contactBook = ContactBook::where('user_id', $user->id)->findOrFail($id);
        $contactBook->delete();

        return redirect()->route('contact-books.index')->with('success', 'Contact book deleted successfully.');
    }

    public function addContacts(Request $request, $id)
    {
        $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $user = auth()->user();
        $contactBook = ContactBook::where('user_id', $user->id)->findOrFail($id);

        // Only attach contacts that belong to this user
        $validContactIds = Contact::where('user_id', $user->id)
            ->whereIn('id', $request->contact_ids)
            ->pluck('id');

        $contactBook->contacts()->syncWithoutDetaching($validContactIds);

        return back()->with('success', count($validContactIds) . ' contacts added to book.');
    }

    public function removeContacts(Request $request, $id)
    {
        $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $user = auth()->user();
        $contactBook = ContactBook::where('user_id', $user->id)->findOrFail($id);

        $contactBook->contacts()->detach($request->contact_ids);

        return back()->with('success', count($request->contact_ids) . ' contacts removed from book.');
    }

    public function destroyAllContacts($id)
    {
        $user = auth()->user();
        $contactBook = ContactBook::where('user_id', $user->id)->findOrFail($id);

        // Get contact IDs in this book that belong to this user
        $contactIds = $contactBook->contacts()
            ->where('contacts.user_id', $user->id)
            ->pluck('contacts.id');

        // Detach all from the pivot
        $contactBook->contacts()->detach();

        // Delete the actual contacts
        Contact::whereIn('id', $contactIds)->delete();

        return back()->with('success', count($contactIds) . ' contacts deleted from book.');
    }
}
