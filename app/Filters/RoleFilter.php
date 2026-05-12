<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/')->with('error', 'Please log in to continue.');
        }

        if ($arguments) {
            $userRole = session()->get('role');

            if (!in_array($userRole, $arguments)) {
                if ($userRole === 'admin') {
                    return redirect()->to(site_url('admin/dashboard'))->with('error', 'Access denied.');
                }
                return redirect()->to(site_url('user/vouchers'))->with('error', 'Access denied.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing needed after
    }
}