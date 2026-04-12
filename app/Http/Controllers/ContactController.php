<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // PUBLIC: User gửi liên hệ
    public function store(Request $request)
    {
        $data = $request->validate([
            'fullname' => 'required|string|max:100',
            'email'    => 'required|email',
            'phone'    => 'nullable|string|max:20',
            'subject'  => 'required|string|max:200',
            'message'  => 'required|string',
        ]);

        $contact = Contact::create($data);

        return response()->json([
            'message' => 'Gửi liên hệ thành công!',
            'data'    => $contact,
        ], 201);
    }

    // ADMIN: Danh sách liên hệ
    public function index(Request $request)
    {
        $contacts = Contact::latest()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(15);

        return response()->json($contacts);
    }

    // ADMIN: Chi tiết
    public function show($id)
    {
        return response()->json(Contact::findOrFail($id));
    }

    // ADMIN: Cập nhật trạng thái
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,replied,closed',
        ]);

        $contact = Contact::findOrFail($id);
        $contact->update(['status' => $request->status]);

        return response()->json(['message' => 'Cập nhật thành công', 'data' => $contact]);
    }

    // ADMIN: Xoá
    public function destroy($id)
    {
        Contact::findOrFail($id)->delete();
        return response()->json(['message' => 'Đã xoá liên hệ']);
    }
}