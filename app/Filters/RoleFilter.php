<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        if ($arguments) {
            $allowedRoles = $arguments;
            $userRole     = session()->get('role');

            if (!in_array($userRole, $allowedRoles)) {
                // Redirect to their own dashboard if they try to access a restricted area
                if ($userRole === 'admin') {
                    return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
                }
                return redirect()->to('/user/dashboard')->with('error', 'Access denied.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing needed after
    }
}