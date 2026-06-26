<?php

namespace App\Controllers;

use App\Libraries\SessionValidator;
use App\Models\UserLogin;

class ProfileController extends BaseController
{
    public function edit()
    {
        $role = session('role');
        return redirect()->to($role === 'admin' ? 'admin/dashboard' : 'user/students');
    }

    public function apiGet()
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }

        return $this->response->setJSON([
            'username'    => $user['username'] ?? '',
            'email'       => $user['email'] ?? '',
            'role'        => ucfirst((string) ($user['role'] ?? '')),
            'first_name'  => $user['first_name'] ?? '',
            'middle_name' => $user['middle_name'] ?? '',
            'last_name'   => $user['last_name'] ?? '',
            'suffix'      => $user['suffix'] ?? '',
        ]);
    }

    public function update()
    {
        $isAjax = $this->request->isAJAX();

        $user = $this->currentUser();
        if (!$user) {
            if ($isAjax) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Session expired.']);
            }
            return redirect()->to('logout');
        }

        $userId      = (int) $user['user_id'];
        $newPassword = trim((string) $this->request->getPost('new_password'));

        $rules = [
            'username'    => ['label' => 'Username',    'rules' => 'required|max_length[100]'],
            'first_name'  => ['label' => 'First Name',  'rules' => 'required|max_length[100]'],
            'middle_name' => ['label' => 'Middle Name',  'rules' => 'permit_empty|max_length[100]'],
            'last_name'   => ['label' => 'Last Name',   'rules' => 'required|max_length[100]'],
            'email'       => ['label' => 'Email',        'rules' => 'required|valid_email|max_length[150]'],
        ];

        if ($newPassword !== '') {
            $rules['current_password'] = ['label' => 'Current Password', 'rules' => 'required'];
            $rules['new_password']     = ['label' => 'New Password',     'rules' => 'min_length[8]|max_length[255]'];
            $rules['confirm_password'] = ['label' => 'Confirm Password', 'rules' => 'required|matches[new_password]'];
        }

        if (!$this->validate($rules)) {
            if ($isAjax) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success'    => false,
                    'message'    => 'Please check the account details.',
                    'errors'     => $this->validator->getErrors(),
                    'csrf_value' => csrf_hash(),
                ]);
            }
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('error', 'Please check the account details.');
        }

        $model = new UserLogin();

        $username = trim((string) $this->request->getPost('username'));
        $email    = strtolower(trim((string) $this->request->getPost('email')));

        if ($this->fieldTaken('username', $username, $userId)) {
            if ($isAjax) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success'    => false,
                    'message'    => 'Username is already in use.',
                    'csrf_value' => csrf_hash(),
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'Username is already in use.');
        }

        if ($this->fieldTaken('email', $email, $userId)) {
            if ($isAjax) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success'    => false,
                    'message'    => 'Email is already in use.',
                    'csrf_value' => csrf_hash(),
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'Email is already in use.');
        }

        $data = [
            'username'    => $username,
            'first_name'  => strtoupper(trim((string) $this->request->getPost('first_name'))),
            'middle_name' => strtoupper(trim((string) $this->request->getPost('middle_name'))),
            'last_name'   => strtoupper(trim((string) $this->request->getPost('last_name'))),
            'suffix'      => strtoupper(trim((string) $this->request->getPost('suffix'))),
            'email'       => $email,
        ];

        $auditDescription = $this->profileAuditDescription($user, $data, $newPassword !== '');

        if ($newPassword !== '') {
            $currentPassword = (string) $this->request->getPost('current_password');
            if (!password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
                if ($isAjax) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success'    => false,
                        'message'    => 'Current password is incorrect.',
                        'csrf_value' => csrf_hash(),
                    ]);
                }
                return redirect()->back()->withInput()->with('error', 'Current password is incorrect.');
            }
            $data['password'] = password_hash($newPassword, PASSWORD_ARGON2ID);
        }

        $model->update($userId, $data);

        $updated = $model->find($userId);
        $validator = new SessionValidator();
        $fullName = $validator->fullName($updated ?: $data);
        session()->set([
            'full_name'      => $fullName,
            'auth_signature' => $updated ? $validator->authSignature($updated) : session()->get('auth_signature'),
        ]);

        log_action($userId, 'UPDATE_PROFILE', $auditDescription);

        if ($isAjax) {
            return $this->response->setJSON([
                'success'    => true,
                'message'    => 'Account updated successfully.',
                'full_name'  => $fullName,
                'csrf_value' => csrf_hash(),
            ]);
        }

        return redirect()->to('profile')->with('message', 'Account updated successfully.');
    }

    private function currentUser(): ?array
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return null;
        }

        return (new UserLogin())->find($userId) ?: null;
    }

    private function fieldTaken(string $field, string $value, int $ignoreId): bool
    {
        return (new UserLogin())
            ->where($field, $value)
            ->where('user_id !=', $ignoreId)
            ->first() !== null;
    }

    private function profileAuditDescription(array $before, array $after, bool $passwordChanged): string
    {
        $labels = [
            'username'    => 'username',
            'email'       => 'email',
            'first_name'  => 'first name',
            'middle_name' => 'middle name',
            'last_name'   => 'last name',
        ];

        $changes = [];
        foreach ($labels as $field => $label) {
            $old = trim((string) ($before[$field] ?? ''));
            $new = trim((string) ($after[$field] ?? ''));
            if ($old === $new) {
                continue;
            }

            $changes[] = $label . ' from "' . ($old !== '' ? $old : 'blank') . '" to "' . ($new !== '' ? $new : 'blank') . '"';
        }

        if ($passwordChanged) {
            $changes[] = 'password';
        }

        if (empty($changes)) {
            return 'Opened account management and submitted no profile changes.';
        }

        return 'Updated own account: changed ' . implode(', ', $changes) . '.';
    }
}
