<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->has('is_admin')) {
            $query->where('is_admin', $request->boolean('is_admin'));
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->jsonSuccess([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        // Only admins can create users
        if (!$request->user()->is_admin) {
            return response()->jsonError('Unauthorized. Admin access required.', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
            'account_type' => 'nullable|string|in:staff,student',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->jsonError('Validation failed', 422, $validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin', false),
            'account_type' => $request->boolean('is_admin', false) ? 'staff' : $request->input('account_type', 'staff'),
            'is_active' => true,
            'phone' => $request->phone,
            'avatar' => $request->avatar,
        ]);

        return response()->jsonSuccess([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);

        return response()->jsonSuccess($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        // Only admins can update users
        if (!$request->user()->is_admin) {
            return response()->jsonError('Unauthorized. Admin access required.', 403);
        }

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'is_admin' => 'sometimes|boolean',
            'account_type' => 'sometimes|string|in:staff,student',
            'is_active' => 'sometimes|boolean',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->jsonError('Validation failed', 422, $validator->errors());
        }

        // Update user
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->has('is_admin')) {
            $user->is_admin = $request->boolean('is_admin');
            if ($user->is_admin) {
                $user->account_type = 'staff';
            }
        }

        if ($request->has('account_type') && !$user->is_admin) {
            $user->account_type = $request->input('account_type');
        }

        if ($request->has('is_active')) {
            $user->is_active = $request->boolean('is_active');
        }

        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }

        if ($request->has('avatar')) {
            $user->avatar = $request->avatar;
        }

        $user->save();

        return response()->jsonSuccess([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Deactivate (soft delete) the specified user.
     */
    public function destroy(Request $request, $id)
    {
        // Only admins can delete users
        if (!$request->user()->is_admin) {
            return response()->jsonError('Unauthorized. Admin access required.', 403);
        }

        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === $request->user()->id) {
            return response()->jsonError('You cannot deactivate your own account', 422);
        }

        // Deactivate instead of delete
        $user->is_active = false;
        $user->save();

        return response()->jsonSuccess([
            'message' => 'User deactivated successfully',
        ]);
    }

    public function userMe(Request $request)
    {
        return response()->jsonSuccess($request->user());
    }

    public function updateUserMe(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->jsonError('Validation failed', 422, $validator->errors());
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }

        if ($request->has('avatar')) {
            $user->avatar = $request->avatar;
        }

        $user->save();

        return response()->jsonSuccess([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }
}
