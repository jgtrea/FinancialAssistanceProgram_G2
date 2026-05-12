<?php

namespace App\Controllers;

use App\Models\UserLogin;

class UsersController extends BaseController
{
    public function index()
    {
        $model = new UserLogin();
        $data['users'] = $model->where('is_active', 1)->findAll();
        return view('admin/user_management/index', $data);
    }

    public function form($id = null)
    {
        $model = new UserLogin();
        $data['user'] = $id ? $model->find($id) : null;
        return view('admin/user_management/form', $data);
    }

    public function save()
    {
        $model = new UserLogin();
        $id = $this->request->getPost('user_id');
        $password = $this->request->getPost('password');

        $data = [
            'full_name' => $this->request->getPost('full_name'),
            'username'  => $this->request->getPost('username'),
            'role'      => $this->request->getPost('role'),
            'is_active' => $this->request->getPost('is_active'),
        ];

        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_ARGON2ID);
        }

        if ($id) {
            $model->update($id, $data);
            $this->writeAuditLog('user_updated', 'Updated user ' . $data['full_name'] . ' (ID #' . $id . ').');
            $message = 'User updated successfully.';
        } else {
            $newUserId = $model->insert($data);
            $this->writeAuditLog('user_created', 'Created user ' . $data['full_name'] . ' (ID #' . $newUserId . ').');
            $message = 'User created successfully.';
        }

        return $this->response->setJSON(['status' => 'success', 'message' => $message]);
    }

    public function archive($id)
    {
        $model = new UserLogin();
        $user = $model->find($id);

        $model->update($id, ['is_active' => 0]);
        $this->writeAuditLog('user_archived', 'Archived user ' . ($user['full_name'] ?? 'ID #' . $id) . '.');

        return $this->response->setJSON(['status' => 'success', 'message' => 'User archived successfully.']);
    }

    // Loads view
    public function archived()
    {
        $model = new UserLogin();
        $data['users'] = $model->where('is_active', 0)->findAll();
        return view('admin/user_management/archived', $data);
    }

    public function restore($id)
    {
        $model = new UserLogin();
        $user = $model->find($id);

        $model->update($id, ['is_active' => 1]);
        $this->writeAuditLog('user_restored', 'Restored user ' . ($user['full_name'] ?? 'ID #' . $id) . '.');

        return $this->response->setJSON(['status' => 'success', 'message' => 'User restored successfully.']);
    }
}
