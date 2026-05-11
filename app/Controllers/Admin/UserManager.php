<?php

namespace App\Controllers\Admin;

use App\Models\UserModel;
use CodeIgniter\Controller;

class UserManager extends Controller
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // ── List ──────────────────────────────────────────────────────────────────
    public function index()
    {
        $users = $this->userModel->orderBy('created_at', 'DESC')->findAll();

        return view('admin/users/index', [
            'title' => 'Users',
            'users' => $users,
        ]);
    }

    // ── Create form ───────────────────────────────────────────────────────────
    public function create()
    {
        return view('admin/users/form', [
            'title'      => 'Add User',
            'user'       => null,
            'validation' => null,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store()
    {
        $rules = [
            'username'         => 'required|min_length[3]|max_length[100]|is_unique[users.username]',
            'full_name'        => 'required|max_length[150]',
            'password'         => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]',
            'role'             => 'required|in_list[admin,user]',
        ];

        if (!$this->validate($rules)) {
            return view('admin/users/form', [
                'title'      => 'Add User',
                'user'       => null,
                'validation' => $this->validator,
            ]);
        }

        $this->userModel->insert([
            'username'  => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'password'  => $this->request->getPost('password'), // hashed via beforeInsert hook
            'role'      => $this->request->getPost('role'),
            'is_active' => 1,
        ]);

        $this->logAction(session()->get('user_id'), 'CREATE_USER',
            "Created user: " . $this->request->getPost('username'));

        return redirect()->to(site_url('admin/users'))
                         ->with('message', 'User created successfully.');
    }

    // ── Edit form ─────────────────────────────────────────────────────────────
    public function edit(int $id)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to(site_url('admin/users'))
                             ->with('error', 'User not found.');
        }

        return view('admin/users/form', [
            'title'      => 'Edit User',
            'user'       => $user,
            'validation' => null,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(int $id)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to(site_url('admin/users'))
                             ->with('error', 'User not found.');
        }

        $rules = [
            'username'  => "required|min_length[3]|max_length[100]|is_unique[users.username,user_id,{$id}]",
            'full_name' => 'required|max_length[150]',
            'role'      => 'required|in_list[admin,user]',
        ];

        // Only validate password if provided
        $newPassword = $this->request->getPost('password');
        if (!empty($newPassword)) {
            $rules['password']         = 'min_length[6]';
            $rules['confirm_password'] = 'matches[password]';
        }

        if (!$this->validate($rules)) {
            return view('admin/users/form', [
                'title'      => 'Edit User',
                'user'       => $user,
                'validation' => $this->validator,
            ]);
        }

        $data = [
            'username'  => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'role'      => $this->request->getPost('role'),
            'is_active' => $this->request->getPost('is_active') ?? $user['is_active'],
        ];

        if (!empty($newPassword)) {
            $data['password'] = $newPassword; // hashed via beforeUpdate hook
        }

        $this->userModel->update($id, $data);

        $this->logAction(session()->get('user_id'), 'UPDATE_USER',
            "Updated user ID: {$id} ({$user['username']})");

        return redirect()->to(site_url('admin/users'))
                         ->with('message', 'User updated successfully.');
    }

    // ── Delete (Ajax) ─────────────────────────────────────────────────────────
    public function delete(int $id)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found.']);
        }

        // Prevent deleting yourself
        if ($id === (int) session()->get('user_id')) {
            return $this->response->setJSON(['success' => false, 'message' => 'You cannot delete your own account.']);
        }

        $this->userModel->delete($id);

        $this->logAction(session()->get('user_id'), 'DELETE_USER',
            "Deleted user ID: {$id} ({$user['username']})");

        return $this->response->setJSON(['success' => true, 'message' => 'User deleted successfully.']);
    }

    // ── Toggle active status (Ajax) ───────────────────────────────────────────
    public function toggleStatus(int $id)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found.']);
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $this->userModel->update($id, ['is_active' => $newStatus]);

        $action = $newStatus ? 'ACTIVATE_USER' : 'DEACTIVATE_USER';
        $this->logAction(session()->get('user_id'), $action, "User ID: {$id} ({$user['username']})");

        return $this->response->setJSON([
            'success'   => true,
            'is_active' => $newStatus,
            'message'   => $newStatus ? 'User activated.' : 'User deactivated.',
        ]);
    }

    private function logAction(?int $userId, string $action, string $description = ''): void
    {
        try {
            $db = \Config\Database::connect();
            $db->table('audit_log')->insert([
                'user_id'     => $userId,
                'voucher_id'  => null,
                'action'      => $action,
                'description' => $description,
                'ip_address'  => $this->request->getIPAddress(),
                'user_agent'  => $this->request->getUserAgent()->getAgentString(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'logAction failed: ' . $e->getMessage());
        }
    }
}