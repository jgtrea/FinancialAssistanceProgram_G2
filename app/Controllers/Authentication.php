<?php

namespace App\Controllers;

use App\Models\UserLogin;

class Authentication extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            $role = session()->get('role');
            return redirect()->to($role === 'admin' ? site_url('admin/dashboard') : site_url('user/dashboard'));
        }

        return view('auth/login');
    }

    public function authenticate()
    {
        $model = new UserLogin();

        $input    = strtolower(trim((string) $this->request->getPost('username')));
        $password = $this->request->getPost('password');

        // Try email first, then full-name match (case-insensitive).
        $user = $model->where('email', $input)->first();

        if (!$user) {
            $nameUpper = mb_strtoupper($input, 'UTF-8');
            $user = $model->db->table('users')
                ->where("UPPER(CONCAT_WS(' ', first_name, last_name))", $nameUpper)
                ->orWhere("UPPER(CONCAT_WS(' ', first_name, middle_name, last_name))", $nameUpper)
                ->orWhere("UPPER(CONCAT_WS(', ', last_name, first_name))", $nameUpper)
                ->limit(1)
                ->get()
                ->getRowArray();
        }

        if (!$user || $user['is_active'] == 0) {
            log_action(null, 'LOGIN_FAILED', "Failed login attempt for \"{$input}\"");
            return redirect()->to('/')->with('error', 'Invalid account or access denied.');
        }

        if (!password_verify($password, $user['password'])) {
            log_action($user['user_id'], 'LOGIN_FAILED', "Bad password for \"{$input}\"");
            return redirect()->to('/')->with('error', 'Invalid credentials.');
        }

        $fullName = trim(implode(' ', array_filter([
            $user['first_name'] ?? '',
            $user['middle_name'] ?? '',
            $user['last_name'] ?? '',
        ])));

        session()->set([
            'user_id'    => $user['user_id'],
            'full_name'  => $fullName ?: $user['email'],
            'role'       => $user['role'],
            'isLoggedIn' => true,
        ]);

        $model->update($user['user_id'], [
            'last_login' => date('Y-m-d H:i:s'),
        ]);

        log_action($user['user_id'], 'LOGIN', "User {$input} logged in");

        if ($user['role'] === 'admin') {
            return redirect()->to('admin/dashboard')->with('success', 'Logged as Admin.');
        } else {
            return redirect()->to('user/dashboard')->with('success', 'Logged in successfully.');
        }
    }

    public function logout()
    {
        $userId   = session()->get('user_id');
        $fullName = session()->get('full_name') ?? 'unknown';
        log_action($userId, 'LOGOUT', "User {$fullName} logged out");
        session()->destroy();
        return redirect()->to('/');
    }

    public function debugUsers()
    {
        $model = new UserLogin();
        $allUsers = $model->findAll();
        $jsonUsers = json_encode($allUsers);

        echo "<script>
            console.log('--- Debug: All Users in Table ---');
            console.table($jsonUsers);
        </script>";
        echo "Check your browser console (F12 -> Console) to see the user list.";
    }

    public function hashPasswords()
    {
        $model = new UserLogin();
        $users = $model->findAll();

        $count = 0;
        foreach ($users as $user) {
            if (str_starts_with($user['password'], '$argon2') || str_starts_with($user['password'], '$2y$')) {
                continue;
            }

            $model->update($user['user_id'], [
                'password' => password_hash($user['password'], PASSWORD_ARGON2ID)
            ]);
            $count++;
        }

        echo "Done. {$count} password(s) hashed. Skipped already-hashed accounts.";
    }
}
