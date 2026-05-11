<?php

/**
 * User Helper
 * Provides: current_user(), current_user_id(), is_admin(), user_role_badge()
 */

if (!function_exists('current_user_id')) {
    function current_user_id(): ?int
    {
        return session()->get('user_id') ? (int) session()->get('user_id') : null;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        if (!session()->get('user_id')) return null;

        return [
            'user_id'   => session()->get('user_id'),
            'username'  => session()->get('username'),
            'full_name' => session()->get('full_name'),
            'role'      => session()->get('role'),
        ];
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return session()->get('role') === 'admin';
    }
}

if (!function_exists('user_role_badge')) {
    /**
     * Return a role badge HTML span.
     */
    function user_role_badge(string $role): string
    {
        return "<span class=\"vs-role-badge vs-role-{$role}\">" . ucfirst($role) . "</span>";
    }
}

if (!function_exists('user_initials')) {
    /**
     * Get initials from a full name.
     * Example: "Juan dela Cruz" → "JC"
     */
    function user_initials(string $name): string
    {
        $words    = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word) && ctype_upper($word[0])) {
                $initials .= strtoupper($word[0]);
            }
        }
        return $initials ?: strtoupper($name[0] ?? '?');
    }
}