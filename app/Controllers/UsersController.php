<?php

namespace App\Controllers;

use App\Models\UserLogin;

class UsersController extends BaseController
{
    public function index()
    {
        $model = new UserLogin();
        $data['users'] = $model->where('is_active', 1)->findAll();
        return view('admin/index', $data);
    }

    public function form($id = null)
    {
        $model = new UserLogin();
        $data['user'] = $id ? $model->find($id) : null;
        return view('admin/form', $data);
    }

    public function save()
    {
        $model = new UserLogin();
        $id = $this->request->getPost('user_id');
        $password = $this->request->getPost('password');

        $data = [
            'username'  => $this->request->getPost('full_name'),
            'email'     => strtolower(trim((string) $this->request->getPost('username'))),
            'role'      => $this->request->getPost('role'),
        ];

        if (!$id) {
            $data['is_active'] = 1;
        }

        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_ARGON2ID);
        }

        $adminId = session()->get('user_id');

        if ($id) {
            $model->update($id, $data);
            log_action($adminId, 'UPDATE_USER', "Updated user #{$id} ({$data['username']})");
            $message = 'User updated successfully.';
        } else {
            $model->insert($data);
            log_action($adminId, 'CREATE_USER', "Created user {$data['username']}");
            $message = 'User created successfully.';
        }

        return $this->response->setJSON(['status' => 'success', 'message' => $message]);
    }

    public function archive($id)
    {
        $model = new UserLogin();
        $model->update($id, ['is_active' => 0]);
        log_action(session()->get('user_id'), 'ARCHIVE_USER', "Deactivated user #{$id}");
        return $this->response->setJSON(['status' => 'success', 'message' => 'User archived successfully.']);
    }

    public function archiveMultiple()
    {
        $ids = $this->request->getPost('ids');
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No users selected.']);
        }

        $model  = new UserLogin();
        $userId = session()->get('user_id');

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && $id !== (int) $userId) {
                $model->update($id, ['is_active' => 0]);
                log_action($userId, 'ARCHIVE_USER', "Deactivated user #{$id}");
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => count($ids) . ' user(s) archived.']);
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
        $model->update($id, ['is_active' => 1]);
        log_action(session()->get('user_id'), 'RESTORE_USER', "Restored user #{$id}");
        return $this->response->setJSON(['status' => 'success', 'message' => 'User restored successfully.']);
    }
}
