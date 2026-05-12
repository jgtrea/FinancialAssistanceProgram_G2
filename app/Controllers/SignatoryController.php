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
            'signatories' => $signatoryModel->orderBy('signatory_id', 'DESC')->findAll()
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

        if ($id) {
            $signatoryModel->update($id, $data);
            $this->writeAuditLog('signatory_updated', 'Updated signatory ' . $this->formatSignatoryName($data) . ' (ID #' . $id . ').');
            return redirect()->to('/signatories')->with('success', 'Signatory updated successfully.');
        }

        $newSignatoryId = $signatoryModel->insert($data);
        $this->writeAuditLog('signatory_created', 'Added signatory ' . $this->formatSignatoryName($data) . ' (ID #' . $newSignatoryId . ').');

        return redirect()->to('/signatories')->with('success', 'Signatory added successfully.');
    }

    public function deactivate($id)
    {
        $signatoryModel = new SignatoryModel();
        $signatory = $signatoryModel->find($id);

        $signatoryModel->update($id, [
            'is_active' => 0
        ]);

        $this->writeAuditLog('signatory_deactivated', 'Deactivated signatory ' . ($signatory ? $this->formatSignatoryName($signatory) : 'ID #' . $id) . '.');

        return redirect()->to('/signatories')->with('success', 'Signatory deactivated successfully.');
    }

    private function formatSignatoryName(array $signatory): string
    {
        $name = trim(
            ($signatory['first_name'] ?? '') . ' ' .
            ($signatory['middle_name'] ?? '') . ' ' .
            ($signatory['last_name'] ?? '') . ' ' .
            ($signatory['suffix'] ?? '')
        );

        return $name !== '' ? $name : 'Unnamed signatory';
    }
}
