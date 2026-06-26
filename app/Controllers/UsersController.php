<?php

namespace App\Controllers;

use App\Models\UserLogin;

class UsersController extends BaseController
{
    public function index()
    {
        $model        = new UserLogin();
        $keyword      = trim((string) $this->request->getGet('q'));
        $filterRole   = trim((string) $this->request->getGet('role'));
        $filterStatus = trim((string) $this->request->getGet('status'));

        if ($keyword !== '') {
            $model
                ->groupStart()
                ->like('username', $keyword)
                ->orLike('email', $keyword)
                ->orLike('role', $keyword)
                ->groupEnd();
        }

        if ($filterRole !== '' && in_array($filterRole, ['admin', 'user'], true)) {
            $model->where('role', $filterRole);
        }

        if ($filterStatus === 'active') {
            $model->where('is_active', 1);
        } elseif ($filterStatus === 'inactive') {
            $model->where('is_active', 0);
        }

        $model->where('user_id !=', (int) session()->get('user_id'));
        $data['users']        = $model->orderBy('is_active', 'DESC')->orderBy('user_id', 'DESC')->findAll();
        $data['keyword']      = $keyword;
        $data['filterRole']   = $filterRole;
        $data['filterStatus'] = $filterStatus;
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
        $uName = $user['username'] ?? '';
        log_action($adminId, $action, ($newStatus ? 'Activated' : 'Deactivated') . " user #{$id} ({$uName})");

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
        if ((int) $id === (int) session()->get('user_id')) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'You cannot edit your own account here.',
            ]);
        }

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

        if ($id && (int) $id === (int) session()->get('user_id')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'You cannot edit your own account here.',
            ]);
        }
        $password = $this->request->getPost('password');
        $role = strtolower(trim((string) $this->request->getPost('role')));

        $validation = \Config\Services::validation();
        $validation->setRules([
            'username'   => 'required|max_length[100]',
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'email'      => 'required|valid_email|max_length[150]',
            'password'   => 'permit_empty|min_length[8]|max_length[255]',
            'role'       => 'required|in_list[admin,user]',
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
            'username'    => trim((string) $this->request->getPost('username')),
            'first_name'  => strtoupper(trim((string) $this->request->getPost('first_name'))),
            'middle_name' => strtoupper(trim((string) $this->request->getPost('middle_name'))),
            'last_name'   => strtoupper(trim((string) $this->request->getPost('last_name'))),
            'suffix'      => strtoupper(trim((string) $this->request->getPost('suffix'))),
            'email'       => strtolower(trim((string) $this->request->getPost('email'))),
            'role'        => $role,
        ];

        if (strtolower($data['username']) === $data['email']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Username and email must be different.',
            ]);
        }

        if ($this->userFieldTaken('email', $data['email'], $id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Email is already in use.',
            ]);
        }

        if ($this->userFieldTaken('username', $data['username'], $id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Username is already in use.',
            ]);
        }

        if (!$id) {
            $data['is_active'] = 1;
        }

        if (!$id && empty($password)) {
            $password = 'password123';
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
            $uName = $user['username'] ?? '';
            log_action($adminId, $action, ($status ? 'Activated' : 'Deactivated') . " user #{$id} ({$uName})");
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
