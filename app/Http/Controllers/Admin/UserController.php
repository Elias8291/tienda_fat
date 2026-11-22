<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'username'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = validator([
            'name' => $request->input('name'),
            'username' => strtolower(trim((string) $request->input('username'))),
            'password' => $request->input('password'),
        ], [
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'string', 'regex:/^[a-z0-9._-]{3,40}$/', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ], [
            'username.regex' => 'Usa de 3 a 40 caracteres: letras, números, punto, guion o guion bajo.',
            'username.unique' => 'Ese usuario ya existe.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ], [
            'name' => 'nombre',
            'username' => 'usuario',
            'password' => 'contraseña',
        ])->validate();

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => $data['password'],
        ]);

        return back()->with('success', 'Usuario creado. Ya puede entrar al panel.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 403);

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }
}
