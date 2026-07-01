<?php

namespace App\Controllers\Admin;

use App\Models\OthersOptionsModel;
use CodeIgniter\Controller;

class OthersOptions extends Controller
{
    private const CONTEXTS = [
        'suffix' => 'SUFFIX',
        'prefix' => 'PREFIX',
        'degree' => 'DEGREE',
    ];

    public function index()
    {
        $oom = new OthersOptionsModel();

        return view('others_options/index', [
            'title'    => 'Other Options',
            'options'  => $oom->getAllForTable(),
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
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid field.']);
        }

        if ($value === '' || $value === '__OTHER__') {
            return $this->response->setJSON(['success' => false, 'message' => 'Value cannot be empty.']);
        }

        if (mb_strlen($value) > 255) {
            return $this->response->setJSON(['success' => false, 'message' => 'Value must be 255 characters or fewer.']);
        }

        $oom      = new OthersOptionsModel();
        $existing = $oom->where('context', $context)->where('value', $value)->first();
        if ($existing) {
            $msg = (int) $existing['is_active']
                ? 'This value already exists.'
                : 'This value is deactivated. Activate it from the table.';
            return $this->response->setJSON(['success' => false, 'message' => $msg]);
        }

        $oom->insert(['context' => $context, 'value' => $value, 'is_active' => 1, 'created_by' => session()->get('user_id')]);

        return $this->response->setJSON(['success' => true, 'message' => 'Option saved.', 'csrf_token' => csrf_hash()]);
    }

    public function edit(int $id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(site_url('admin/others-options'));
        }

        $context = trim((string) $this->request->getPost('context'));
        $value   = strtoupper(trim((string) $this->request->getPost('value')));
        $oom     = new OthersOptionsModel();
        $row     = $oom->find($id);

        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Option not found.']);
        }

        if (!array_key_exists($context, self::CONTEXTS)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid field.']);
        }

        if ($value === '' || $value === '__OTHER__') {
            return $this->response->setJSON(['success' => false, 'message' => 'Value cannot be empty.']);
        }

        if (mb_strlen($value) > 255) {
            return $this->response->setJSON(['success' => false, 'message' => 'Value must be 255 characters or fewer.']);
        }

        // Exclude self only when context hasn't changed (same slot); if context changes, no exclusion needed
        $excludeId = ($context === $row['context']) ? $id : null;
        if ($oom->isDuplicate($context, $value, $excludeId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'This value already exists for this field.']);
        }

        $oom->update($id, ['context' => $context, 'value' => $value]);
        return $this->response->setJSON(['success' => true, 'message' => 'Option updated.', 'csrf_token' => csrf_hash()]);
    }

    public function deactivate($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(site_url('admin/others-options'));
        }

        $oom = new OthersOptionsModel();
        $row = $oom->find((int) $id);

        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Option not found.']);
        }

        $oom->deactivate((int) $id);

        return $this->response->setJSON(['success' => true, 'message' => 'Option deactivated.', 'csrf_token' => csrf_hash()]);
    }

    public function activate($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(site_url('admin/others-options'));
        }

        $oom = new OthersOptionsModel();
        $row = $oom->find((int) $id);

        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Option not found.']);
        }

        $oom->activate((int) $id);

        return $this->response->setJSON(['success' => true, 'message' => 'Option activated.', 'csrf_token' => csrf_hash()]);
    }
}
