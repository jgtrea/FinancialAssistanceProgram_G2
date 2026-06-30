<?php

namespace App\Controllers\Admin;

use App\Models\OthersOptionsModel;
use CodeIgniter\Controller;

class OthersOptions extends Controller
{
    private const CONTEXTS = [
        'suffix' => 'Suffix',
        'prefix' => 'Prefix',
        'degree' => 'Degree',
    ];

    public function index()
    {
        $oom = new OthersOptionsModel();

        return view('others_options/index', [
            'title'    => 'Other Options',
            'grouped'  => $oom->getAllGrouped(),
            'contexts' => self::CONTEXTS,
        ]);
    }

    public function save()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(site_url('admin/others-options'));
        }

        $context = trim((string) $this->request->getPost('context'));
        $value   = strtoupper(trim((string) $this->request->getPost('value')));

        if (!array_key_exists($context, self::CONTEXTS)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid context.']);
        }

        if ($value === '' || $value === '__OTHER__') {
            return $this->response->setJSON(['success' => false, 'message' => 'Value cannot be empty.']);
        }

        if (mb_strlen($value) > 255) {
            return $this->response->setJSON(['success' => false, 'message' => 'Value must be 255 characters or fewer.']);
        }

        $oom = new OthersOptionsModel();
        $oom->saveOption($context, $value, session()->get('user_id'));

        return $this->response->setJSON(['success' => true, 'message' => 'Option saved.', 'csrf_token' => csrf_hash()]);
    }

    public function delete($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(site_url('admin/others-options'));
        }

        $oom  = new OthersOptionsModel();
        $row  = $oom->find((int) $id);

        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Option not found.']);
        }

        $oom->delete((int) $id);

        return $this->response->setJSON(['success' => true, 'message' => 'Option deleted.', 'csrf_token' => csrf_hash()]);
    }
}
