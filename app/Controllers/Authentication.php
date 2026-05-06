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

        if (!$user) {
            return redirect()->to('/')->with('error', 'Invalid username or password.');
        }

        if ($password !== $user['password']) {
            return redirect()->to('/')->with('error', 'Invalid username or password.');
        }

        session()->set([
            'user_id'    => $user['user_id'],
            'username'   => $user['username'],
            'full_name'  => $user['full_name'],
            'role'       => $user['role'],
            'isLoggedIn' => true
        ]);

        // both roles go to students for now
        return redirect()->to('/students');
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

        foreach ($users as $user) {
            $model->update($user['user_id'], [
                'password' => password_hash($user['password'], PASSWORD_ARGON2ID)
            ]);
        }
        echo "All passwords hashed successfully.";
    }
}