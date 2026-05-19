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

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $model->where('username', $username)->first();

        if (!$user || $user['is_active'] == 0) {
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

        log_action($user['user_id'], 'LOGIN', "User {$username} logged in");

        if ($user['role'] === 'admin') {
            return redirect()->to('admin/dashboard')->with('success', 'Logged as Admin.');
        } else {
            return redirect()->to('user/dashboard')->with('success', 'Logged in successfully.');
        }
    }

    public function logout()
    {
        $userId   = session()->get('user_id');
        $username = session()->get('username');
        log_action($userId, 'LOGOUT', "User {$username} logged out");
        session()->destroy();
        return redirect()->to('/');
    }
}