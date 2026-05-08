<?php

namespace App\Controllers;

use App\Models\UserLogin;

class UsersController extends BaseController
{
    public function index()
    {
        $model = new UserLogin();
        $data['users'] = $model->findAll();
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
            $message = 'User updated successfully.';
        } else {
            $model->insert($data);
            $message = 'User created successfully.';
        }

        return $this->response->setJSON(['status' => 'success', 'message' => $message]);
    }

    public function delete($id)
    {
        $model = new UserLogin();
        $model->delete($id);
        return $this->response->setJSON(['status' => 'success', 'message' => 'User deleted successfully.']);
    }
}