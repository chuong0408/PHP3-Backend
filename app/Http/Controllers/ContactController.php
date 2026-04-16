<?php

namespace App\Http\Controllers;

use App\Mail\ContactReplyMail;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

    // ADMIN: Danh sách liên hệ (có lọc + search)
    public function index(Request $request)
    {
        $contacts = Contact::latest()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where(fn($q2) =>
                    $q2->where('fullname', 'like', "%$s%")
                       ->orWhere('email', 'like', "%$s%")
                       ->orWhere('phone', 'like', "%$s%")
                       ->orWhere('subject', 'like', "%$s%")
                );
            })
            ->paginate($request->get('per_page', 15));

        return response()->json($contacts);
    }

    // ADMIN: Chi tiết
    public function show($id)
    {
        return response()->json(Contact::findOrFail($id));
    }

    // ADMIN: Trả lời liên hệ → gửi email cho user
    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply_message' => 'required|string|min:5',
        ], [
            'reply_message.required' => 'Vui lòng nhập nội dung trả lời.',
            'reply_message.min'      => 'Nội dung phải có ít nhất 5 ký tự.',
        ]);

        $contact = Contact::findOrFail($id);

        // Gửi mail cho user
        Mail::to($contact->email)->send(new ContactReplyMail(
            fullname:       $contact->fullname,
            contactSubject: $contact->subject,
            userMessage:    $contact->message,
            replyMessage:   $request->reply_message,
        ));

        // Cập nhật DB
        $contact->update([
            'status'        => 'replied',
            'reply_message' => $request->reply_message,
            'replied_at'    => now(),
        ]);

        return response()->json([
            'message' => "Đã gửi phản hồi tới {$contact->email}!",
            'data'    => $contact,
        ]);
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

    // ADMIN: Thống kê nhanh
    public function stats()
    {
        return response()->json([
            'total'   => Contact::count(),
            'pending' => Contact::where('status', 'pending')->count(),
            'replied' => Contact::where('status', 'replied')->count(),
            'closed'  => Contact::where('status', 'closed')->count(),
        ]);
    }
}