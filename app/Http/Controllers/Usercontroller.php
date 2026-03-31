<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('fullname', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderByDesc('id')->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email'    => 'required|email|unique:user,email',
            'phone'    => 'nullable|string|max:50',
            'address'  => 'nullable|string|max:255',
            'brithday' => 'nullable|date',
            'role'     => 'nullable|integer|in:0,1',
            'status'   => 'nullable|integer|in:0,1',
            'password' => 'required|string|min:6',
            'image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imagePath = '';
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('users', 'public');
        }

        $user = User::create([
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'phone'    => $request->phone ?? '',
            'address'  => $request->address ?? '',
            'brithday' => $request->brithday ?? now(),
            'role'     => $request->role ?? 0,
            'status'   => $request->status ?? 1,
            'password' => Hash::make($request->password),
            'image'    => $imagePath,
            'otp'      => '',
            'otp_time' => now(),
        ]);

        return response()->json($this->formatUser($user), 201);
    }

    public function show(User $user)
    {
        return response()->json($this->formatUser($user));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email'    => 'required|email|unique:user,email,' . $user->id,
            'phone'    => 'nullable|string|max:50',
            'address'  => 'nullable|string|max:255',
            'brithday' => 'nullable|date',
            'role'     => 'nullable|integer|in:0,1',
            'status'   => 'nullable|integer|in:0,1',
            'password' => 'nullable|string|min:6',
            'image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'phone'    => $request->phone ?? $user->phone,
            'address'  => $request->address ?? $user->address,
            'brithday' => $request->brithday ?? $user->brithday,
            'role'     => $request->role ?? $user->role,
            'status'   => $request->status ?? $user->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            $data['image'] = $request->file('image')->store('users', 'public');
        }

        $user->update($data);

        return response()->json($this->formatUser($user));
    }

    public function destroy(User $user)
    {
        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }
        $user->delete();
        return response()->json(['message' => 'Xóa người dùng thành công!']);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['status' => $user->status === 1 ? 0 : 1]);
        return response()->json($this->formatUser($user));
    }

    private function formatUser(User $user): array
    {
        $base     = rtrim(env('APP_URL', 'http://localhost:8000'), '/');
        $imageUrl = null;

        if ($user->image) {
            $imageUrl = str_starts_with($user->image, 'http')
                ? $user->image
                : $base . '/storage/' . $user->image;
        }

        return [
            'id'       => $user->id,
            'fullname' => $user->fullname,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'address'  => $user->address,
            'brithday' => $user->brithday,
            'image'    => $imageUrl,
            'role'     => $user->role,
            'status'   => $user->status,
        ];
    }
}