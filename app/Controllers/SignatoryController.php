<?php

namespace App\Controllers;

use App\Models\SignatoryModel;

class SignatoryController extends BaseController
{
    public function index()
    {
        $signatoryModel = new SignatoryModel();

        return view('signatories/index', [
            'title' => 'Signatories',
            'signatories' => $signatoryModel
                ->where('is_active', 1)
                ->orderBy('signatory_id', 'DESC')
                ->findAll()
        ]);
    }

    public function form($id = null)
    {
        $signatoryModel = new SignatoryModel();

        $signatory = null;

        if ($id !== null) {
            $signatory = $signatoryModel->find($id);
        }

        return view('signatories/form', [
            'title' => $signatory ? 'Edit Signatory' : 'Add Signatory',
            'signatory' => $signatory
        ]);
    }

    public function save()
    {
        $signatoryModel = new SignatoryModel();

        $id = $this->request->getPost('signatory_id');

        $data = [
            'first_name' => $this->request->getPost('first_name'),
            'middle_name' => $this->request->getPost('middle_name'),
            'last_name' => $this->request->getPost('last_name'),
            'suffix' => $this->request->getPost('suffix'),
            'position_title' => $this->request->getPost('position_title'),
            'is_active' => $this->request->getPost('is_active') ?? 1,
        ];

        $userId = session()->get('user_id');
        $name   = trim($this->request->getPost('first_name') . ' ' . $this->request->getPost('last_name'));

        if ($id) {
            $signatoryModel->update($id, $data);
            log_action($userId, 'UPDATE_SIGNATORY', "Updated signatory {$name}");
            return redirect()->to('/signatories')->with('success', 'Signatory updated successfully.');
        }

        $signatoryModel->insert($data);
        log_action($userId, 'CREATE_SIGNATORY', "Created signatory {$name}");
        return redirect()->to('/signatories')->with('success', 'Signatory added successfully.');
    }

    public function deactivate($id)
    {
        $signatoryModel = new SignatoryModel();
        $signatoryModel->update($id, ['is_active' => 0]);
        log_action(session()->get('user_id'), 'DEACTIVATE_SIGNATORY', "Deactivated signatory #{$id}");
        return redirect()->to('/signatories')->with('success', 'Signatory deactivated successfully.');
    }
}
