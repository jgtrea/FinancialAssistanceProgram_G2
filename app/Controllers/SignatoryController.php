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
            'signatory' => null,
            'signatories' => $signatoryModel
                ->orderBy('is_active', 'DESC')
                ->orderBy('full_name', 'ASC')
                ->findAll(),
        ]);
    }

    public function edit($id)
    {
        $signatoryModel = new SignatoryModel();
        $signatory = $signatoryModel->find($id);

        if (!$signatory) {
            return redirect()->to('/signatories')->with('error', 'Signatory not found.');
        }

        return view('signatories/index', [
            'title' => 'Edit Signatory',
            'signatory' => $signatory,
            'signatories' => $signatoryModel
                ->orderBy('is_active', 'DESC')
                ->orderBy('full_name', 'ASC')
                ->findAll(),
        ]);
    }

    public function save()
    {
        $signatoryModel = new SignatoryModel();
        $signatoryId = $this->request->getPost('signatory_id');

        $data = [
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'position_title' => trim((string) $this->request->getPost('position_title')),
            'signature_image' => trim((string) $this->request->getPost('signature_image')),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ];

        if ($data['full_name'] === '' || $data['position_title'] === '') {
            return redirect()->back()->withInput()->with('error', 'Full name and position title are required.');
        }

        if ($signatoryId) {
            $signatoryModel->update($signatoryId, $data);
            $this->writeAuditLog(
                'signatory_updated',
                'Updated signatory: ' . $data['full_name']
            );
            return redirect()->to('/signatories')->with('success', 'Signatory updated successfully.');
        }

        $signatoryModel->insert($data);
        $this->writeAuditLog(
            'signatory_added',
            'Added signatory: ' . $data['full_name']
        );

        return redirect()->to('/signatories')->with('success', 'Signatory added successfully.');
    }

    public function setStatus($id, $status)
    {
        $signatoryModel = new SignatoryModel();
        $signatory = $signatoryModel->find($id);

        if (!$signatory) {
            return redirect()->to('/signatories')->with('error', 'Signatory not found.');
        }

        $signatoryModel->update($id, [
            'is_active' => $status === 'activate' ? 1 : 0,
        ]);

        $this->writeAuditLog(
            $status === 'activate' ? 'signatory_activated' : 'signatory_deactivated',
            ucfirst($status === 'activate' ? 'activated' : 'deactivated') . ' signatory: ' . $signatory['full_name']
        );

        return redirect()->to('/signatories')->with('success', 'Signatory status updated.');
    }
}
