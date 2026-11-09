<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    // 15. DELETE /api/v1/users/{id}
    public function destroy($id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'Naudotojas nerastas'], 404);
        }

        $currentUser = request()->user();

        if (! $currentUser->isAdmin() && $currentUser->id !== $user->id) {
            return response()->json(['message' => 'Neturite teisės ištrinti šio naudotojo'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Naudotojas sėkmingai pašalintas'], 200);
    }
}
