<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactBook;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $limit = $user->current_plan->limits['contacts'] ?? 0;
        
        $contacts = Contact::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $contactBooks = ContactBook::where('user_id', $user->id)
            ->withCount('contacts')
            ->get();

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'limit' => $limit,
            'count' => Contact::where('user_id', $user->id)->count(),
            'contactBooks' => $contactBooks,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $limit = $user->current_plan->limits['contacts'] ?? 0;
        $currentCount = Contact::where('user_id', $user->id)->count();

        if ($currentCount >= $limit) {
            return back()->with('error', 'Contact limit reached. Please upgrade your plan.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'contact_book_id' => 'nullable|exists:contact_books,id',
        ]);

        // Clean phone
        $phone = preg_replace('/[^0-9]/', '', $request->phone_number);

        $contact = Contact::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'phone_number' => $phone,
            'country_code' => '60', // Defaulting for now
        ]);

        if ($request->contact_book_id) {
            $book = ContactBook::where('user_id', $user->id)->find($request->contact_book_id);
            if ($book) {
                $book->contacts()->syncWithoutDetaching([$contact->id]);
            }
        }

        return back()->with('success', 'Contact added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        $user = auth()->user();
        $contact = Contact::where('user_id', $user->id)->findOrFail($id);

        // Clean phone
        $phone = preg_replace('/[^0-9]/', '', $request->phone_number);

        $contact->update([
            'name' => $request->name,
            'phone_number' => $phone,
        ]);

        return back()->with('success', 'Contact updated successfully.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $contact = Contact::where('user_id', $user->id)->findOrFail($id);
        $contact->delete();

        return back()->with('success', 'Contact deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
        ]);

        $user = auth()->user();
        Contact::where('user_id', $user->id)->whereIn('id', $request->ids)->delete();

        return back()->with('success', count($request->ids) . ' contacts deleted successfully.');
    }

    public function destroyAll()
    {
        $user = auth()->user();
        $deleted = Contact::where('user_id', $user->id)->delete();

        return back()->with('success', $deleted . ' contacts deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
            'contact_book_id' => 'nullable|exists:contact_books,id',
        ]);

        $user = auth()->user();
        $limit = $user->current_plan->limits['contacts'] ?? 0;
        $currentCount = Contact::where('user_id', $user->id)->count();

        if ($currentCount >= $limit) {
            return back()->with('error', 'Contact limit reached. Please upgrade your plan.');
        }

        $file = $request->file('file');

        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $header = array_shift($data);

        $count = 0;
        $importedContactIds = [];

        foreach ($data as $row) {
            if (count($row) < 2) continue;
            if ($currentCount + $count >= $limit) break;

            $name = $row[0] ?? 'Unknown';
            $phone = $row[1] ?? '';

            $phone = preg_replace('/[^0-9]/', '', $phone);

            if (empty($phone)) continue;

            $contact = Contact::firstOrCreate(
                ['user_id' => $user->id, 'phone_number' => $phone],
                ['name' => $name, 'country_code' => '60']
            );
            $importedContactIds[] = $contact->id;
            $count++;
        }

        // Attach to contact book if specified
        if ($request->contact_book_id && !empty($importedContactIds)) {
            $book = ContactBook::where('user_id', $user->id)->find($request->contact_book_id);
            if ($book) {
                $book->contacts()->syncWithoutDetaching($importedContactIds);
            }
        }

        return back()->with('success', "$count contacts imported successfully.");
    }
}
