<?php

namespace App\Libraries;

use App\Models\UserLogin;
use CodeIgniter\HTTP\RedirectResponse;

class SessionValidator
{
    public function validate(?array $allowedRoles = null): ?RedirectResponse
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/')->with('error', 'Please log in to continue.');
        }

        $userId = (int) ($session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return $this->endSession('Please log in to continue.');
        }

        $model = new UserLogin();
        $user = $model->find($userId);
        if (!$user) {
            return $this->endSession('Your account session is no longer valid. Please log in again.');
        }

        if ((int) ($user['is_active'] ?? 0) !== 1) {
            log_action($userId, 'SESSION_INVALIDATED', 'Session ended because account is inactive.');
            return $this->endSession('Your account has been deactivated. Please contact an administrator.');
        }

        $sessionToken = (string) ($session->get('session_token') ?? '');
        $activeToken = (string) ($user['session_token'] ?? '');
        if ($sessionToken === '' || $activeToken === '' || !hash_equals($activeToken, $sessionToken)) {
            log_action($userId, 'SESSION_INVALIDATED', 'Session ended because the account logged in on another device.');
            return $this->endSession('Your account was logged in on another device. Please log in again to continue.');
        }

        $currentSignature = $this->authSignature($user);
        $sessionSignature = (string) ($session->get('auth_signature') ?? '');
        if ($sessionSignature !== '' && !hash_equals($sessionSignature, $currentSignature)) {
            log_action($userId, 'SESSION_INVALIDATED', 'Session ended because account credentials or role changed.');
            return $this->endSession('Your account permissions changed. Please log in again.');
        }

        if ($sessionSignature === '') {
            $session->set('auth_signature', $currentSignature);
        }

        // Heartbeat: refresh the session lock so it stays held while the user is
        // active. Throttled to once a minute to avoid a DB write on every
        // request. Once activity stops for longer than the session expiration,
        // session_last_active goes stale and a fresh login elsewhere is allowed
        // (see Authentication::authenticate()).
        $lastActive = $user['session_last_active'] ?? null;
        if ($lastActive === null || (time() - strtotime((string) $lastActive)) >= 60) {
            $model->update($userId, ['session_last_active' => date('Y-m-d H:i:s')]);
        }

        $role = (string) ($user['role'] ?? '');
        if ($allowedRoles && !in_array($role, $allowedRoles, true)) {
            $fallback = $role === 'admin' ? 'admin/dashboard' : 'user/dashboard';
            return redirect()->to(site_url($fallback))->with('error', 'Access denied.');
        }

        $session->set([
            'user_id'   => $user['user_id'],
            'full_name' => $this->fullName($user),
            'role'      => $role,
        ]);

        return null;
    }

    public function authSignature(array $user): string
    {
        return hash('sha256', implode('|', [
            (string) ($user['user_id'] ?? ''),
            (string) ($user['password'] ?? ''),
            (string) ($user['role'] ?? ''),
            (string) ($user['is_active'] ?? ''),
        ]));
    }

    public function fullName(array $user): string
    {
        $fullName = trim(implode(' ', array_filter([
            $user['first_name'] ?? '',
            $user['middle_name'] ?? '',
            $user['last_name'] ?? '',
            $user['suffix'] ?? '',
        ])));

        return $fullName ?: (string) ($user['username'] ?? $user['email'] ?? 'User');
    }

    private function endSession(string $message): RedirectResponse
    {
        session()->destroy();
        return redirect()->to('/')->with('error', $message);
    }
}
