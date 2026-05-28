<?php

namespace App\Controllers;

use App\Models\UserLogin;

class UsersController extends BaseController
{
    public function index()
    {
        $model = new UserLogin();
        $keyword = trim((string) $this->request->getGet('q'));

        if ($keyword !== '') {
            $model
                ->groupStart()
                ->like('username', $keyword)
                ->orLike('email', $keyword)
                ->orLike('role', $keyword)
                ->groupEnd();
        }

        $data['users'] = $model
            ->orderBy('user_id', 'DESC')
            ->findAll();
        $data['keyword'] = $keyword;
        return view('admin/index', $data);
    }

    public function toggleStatus(int $id)
    {
        $model   = new UserLogin();
        $user    = $model->find($id);
        $adminId = session()->get('user_id');

        if (!$user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User not found.']);
        }

        if ($id === (int) $adminId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'You cannot deactivate your own account.']);
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $model->update($id, ['is_active' => $newStatus]);

        $action = $newStatus ? 'ACTIVATE_USER' : 'DEACTIVATE_USER';
        log_action($adminId, $action, ($newStatus ? 'Activated' : 'Deactivated') . " user #{$id} ({$user['username']})");

        return $this->response->setJSON([
            'status'    => 'success',
            'is_active' => $newStatus,
            'message'   => $newStatus ? 'User activated.' : 'User deactivated.',
        ]);
    }

    public function form($id = null)
    {
        $model = new UserLogin();
        $data['user'] = $id ? $model->find($id) : null;
        return view('admin/form', $data);
    }

    public function getJson($id)
    {
        $user = (new UserLogin())->find($id);

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'User not found.',
            ]);
        }

        unset($user['password']);

        return $this->response->setJSON([
            'status' => 'success',
            'user'   => $user,
        ]);
    }

    public function save()
    {
        $model = new UserLogin();
        $id = $this->request->getPost('user_id');
        $password = $this->request->getPost('password');
        $role = strtolower(trim((string) $this->request->getPost('role')));

        $validation = \Config\Services::validation();
        $validation->setRules([
            'full_name' => 'required|max_length[100]',
            'username'  => 'required|valid_email|max_length[150]',
            'password'  => ($id ? 'permit_empty' : 'required') . '|min_length[8]|max_length[255]',
            'role'      => 'required|in_list[admin,user]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $this->validationErrorMessage($errors, 'Please check the user details.'),
                'errors' => $errors,
            ]);
        }

        if (!in_array($role, ['admin', 'user'], true)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Role must be Admin or User only.',
            ]);
        }

        if ($id && !$model->find($id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'User not found.',
            ]);
        }

        $data = [
            'username'  => trim((string) $this->request->getPost('full_name')),
            'email'     => strtolower(trim((string) $this->request->getPost('username'))),
            'role'      => $role,
        ];

        if ($this->userFieldTaken('username', $data['username'], $id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Username is already in use.',
            ]);
        }

        if ($this->userFieldTaken('email', $data['email'], $id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Email is already in use.',
            ]);
        }

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

    public function activateMultiple()
    {
        return $this->bulkSetStatus(1);
    }

    public function deactivateMultiple()
    {
        return $this->bulkSetStatus(0);
    }

    private function bulkSetStatus(int $status)
    {
        $ids = $this->request->getPost('ids');
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No users selected.']);
        }

        $model   = new UserLogin();
        $adminId = (int) session()->get('user_id');
        $changed = 0;

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0 || $id === $adminId) continue;
            $user = $model->find($id);
            if (!$user) continue;
            $model->update($id, ['is_active' => $status]);
            $action = $status ? 'ACTIVATE_USER' : 'DEACTIVATE_USER';
            log_action($adminId, $action, ($status ? 'Activated' : 'Deactivated') . " user #{$id} ({$user['username']})");
            $changed++;
        }

        if ($changed === 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No users were updated.']);
        }

        $label = $status ? 'activated' : 'deactivated';
        return $this->response->setJSON(['status' => 'success', 'message' => "{$changed} user(s) {$label}."]);
    }

    private function userFieldTaken(string $field, string $value, $ignoreId = null): bool
    {
        $query = (new UserLogin())->where($field, $value);

        if ($ignoreId) {
            $query->where('user_id !=', (int) $ignoreId);
        }

        return $query->first() !== null;
    }

    private function validationErrorMessage(array $errors, string $fallback): string
    {
        if (empty($errors)) {
            return $fallback;
        }

        return 'Validation failed. Please review the field details below.';
    }

}
