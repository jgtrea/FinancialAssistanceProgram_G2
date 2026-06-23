<?php

namespace App\Controllers;

use App\Libraries\SessionValidator;
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

        // Clear stale session locks before doing anything else: any token whose
        // session_last_active is older than the expiration window (or never set)
        // belongs to a session that has idle-expired. A passively-expired session
        // can't clean itself (its data is gone, so no later request knows which
        // user it was), so we sweep them here on each login. Keeps the users
        // table tidy and frees abandoned single-session locks.
        $lockTtl = (int) (new \Config\Session())->expiration;
        $model->where('session_token IS NOT NULL', null, false)
            ->groupStart()
                ->where('session_last_active IS NULL', null, false)
                ->orWhere('session_last_active <', date('Y-m-d H:i:s', time() - $lockTtl))
            ->groupEnd()
            ->set(['session_token' => null, 'session_last_active' => null])
            ->update();

        $input    = strtolower(trim((string) $this->request->getPost('username')));
        $password = $this->request->getPost('password');

        // Try email first, then username.
        $user = $model->where('email', $input)->first()
             ?? $model->where('username', $input)->first();

        if (!$user || $user['is_active'] == 0) {
            log_action(null, 'LOGIN_FAILED', "Failed login attempt for \"{$input}\"");
            return redirect()->to('/')->with('error', 'Invalid account or access denied.');
        }

        if (!password_verify($password, $user['password'])) {
            log_action($user['user_id'], 'LOGIN_FAILED', "Bad password for \"{$input}\"");
            return redirect()->to('/')->with('error', 'Invalid credentials.');
        }

        // First-login-holds: if this account already has a live session on
        // another device, REJECT this login instead of taking the session over.
        // The holder keeps it until they log out or their session goes idle past
        // the expiration window (SessionValidator heartbeats session_last_active
        // on every request, so an actively-used session never frees).
        $lockTtl     = (int) (new \Config\Session())->expiration; // seconds
        $activeToken = (string) ($user['session_token'] ?? '');
        $lastActive  = $user['session_last_active'] ?? null;
        $lockAlive   = $activeToken !== ''
            && $lastActive !== null
            && (time() - strtotime((string) $lastActive)) < $lockTtl;

        // The real owner (correct password, above) may have just closed the
        // browser without logging out and now can't get back in. Offer a force
        // override: re-rendering the login view with a "log out the other device
        // and continue" button. Submitting that (force_login=1) falls through
        // and takes over the session, kicking the stale/other device.
        $forceLogin = $this->request->getPost('force_login') === '1';
        if ($lockAlive && !$forceLogin) {
            log_action($user['user_id'], 'LOGIN_BLOCKED', "Login blocked for \"{$input}\"; account active on another device.");
            return view('auth/login', [
                'lockedNotice' => 'This account is already logged in on another device.',
                'prefillUser'  => $input,
                'prefillPass'  => (string) $password,
            ]);
        }

        session()->regenerate(true);
        $validator = new SessionValidator();
        $sessionToken = bin2hex(random_bytes(32));
        $now          = date('Y-m-d H:i:s');

        session()->set([
            'user_id'        => $user['user_id'],
            'full_name'      => $validator->fullName($user),
            'role'           => $user['role'],
            'isLoggedIn'     => true,
            'auth_signature' => $validator->authSignature($user),
            'session_token'  => $sessionToken,
        ]);

        $model->update($user['user_id'], [
            'last_login'          => $now,
            'session_token'       => $sessionToken,
            'session_last_active' => $now,
        ]);

        $loginName = $validator->fullName($user);
        log_action($user['user_id'], 'LOGIN', "User {$loginName} logged in");

        if ($user['role'] === 'admin') {
            return redirect()->to('admin/dashboard')->with('success', 'Logged as Admin.');
        } else {
            return redirect()->to('user/dashboard')->with('success', 'Logged in successfully.');
        }
    }

    public function logout()
    {
        $userId   = session()->get('user_id');
        $fullName = session()->get('full_name') ?? 'unknown';
        $sessionToken = session()->get('session_token');

        if ($userId && $sessionToken) {
            $model = new UserLogin();
            $user = $model->find($userId);
            if ($user && hash_equals((string) ($user['session_token'] ?? ''), (string) $sessionToken)) {
                $model->update($userId, [
                    'session_token'       => null,
                    'session_last_active' => null,
                ]);
            }
        }

        log_action($userId, 'LOGOUT', "User {$fullName} logged out");
        session()->destroy();
        return redirect()->to('/');
    }

    public function debugUsers()
    {
        $model = new UserLogin();
        $allUsers = $model->findAll();
        $jsonUsers = json_encode($allUsers);

        echo "<script>
            console.log('--- Debug: All Users in Table ---');
            console.table($jsonUsers);
        </script>";
        echo "Check your browser console (F12 -> Console) to see the user list.";
    }

    public function hashPasswords()
    {
        $model = new UserLogin();
        $users = $model->findAll();

        $count = 0;
        foreach ($users as $user) {
            if (str_starts_with($user['password'], '$argon2') || str_starts_with($user['password'], '$2y$')) {
                continue;
            }

            $model->update($user['user_id'], [
                'password' => password_hash($user['password'], PASSWORD_ARGON2ID)
            ]);
            $count++;
        }

        echo "Done. {$count} password(s) hashed. Skipped already-hashed accounts.";
    }
}
