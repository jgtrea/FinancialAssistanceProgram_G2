<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends Controller
{
    public function index()
    {
        return redirect()->to(site_url('login'));
    }

    public function login()
    {
        if (session()->get('logged_in')) {
            return $this->redirectByRole();
        }

        if ($this->request->getMethod() === 'post') {

            $rules = [
                'username' => 'required|min_length[3]|max_length[100]',
                'password' => 'required|min_length[6]',
            ];

            if (!$this->validate($rules)) {
                return view('auth/login', [
                    'validation' => $this->validator,
                    'error'      => null,
                ]);
            }

            $username  = trim($this->request->getPost('username'));
            $password  = $this->request->getPost('password');

            $userModel = new UserModel();
            $user      = $userModel->where('username', $username)->first();

            if (!$user || !password_verify($password, $user['password'])) {
                return view('auth/login', [
                    'validation' => null,
                    'error'      => 'Invalid username or password.',
                ]);
            }

            session()->set([
                'logged_in' => true,
                'user_id'   => $user['user_id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'] ?? '',
                'role'      => $user['role'],
            ]);

            // Log after session is set so user context is available
            log_action($user['user_id'], 'LOGIN_SUCCESS', "User {$username} logged in successfully");

            return $this->redirectByRole();
        }

        // GET — show the form
        return view('auth/login', [
            'validation' => null,
            'error'      => null,
        ]);
    }

    public function logout()
    {
        $userId   = session()->get('user_id');
        $username = session()->get('username');

        log_action($userId, 'LOGOUT', "User {$username} logged out");

        session()->destroy();
        return redirect()->to(site_url('login'))->with('message', 'You have been logged out.');
    }

    private function redirectByRole(): \CodeIgniter\HTTP\RedirectResponse
    {
        $role = session()->get('role');

        if ($role === 'admin') {
            return redirect()->to(site_url('admin/dashboard'));
        }

        return redirect()->to(site_url('user/dashboard'));
    }
}