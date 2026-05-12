<?php

namespace App\Controllers;

use App\Models\UserLogin;

class Authentication extends BaseController
{
    public function index()
    {
        return view('auth/login');
    }

    public function authenticate()
    {
        $model = new UserLogin();

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $model->where('username', $username)->first();

        if (!$user || ($user['is_active'] == 0)) { 
            return redirect()->to('/')->with('error', 'Invalid account or access denied.');
        }

        if (!password_verify($password, $user['password'])) {
            return redirect()->to('/')->with('error', 'Invalid username or password.');
        }

        session()->set([
            'user_id'    => $user['user_id'],
            'username'   => $user['username'],
            'full_name'  => $user['full_name'],
            'role'       => $user['role'], 
            'isLoggedIn' => true
        ]);

        if ($user['role'] === 'admin') {
            return redirect()->to('/admin/user_management')->with('success', 'Logged as Admin.');
        } else {
            return redirect()->to('/students')->with('success', 'Logged in successfully.');
        }
    }

    public function logout()
    {
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