<?php

namespace App\Controllers;

use App\Models\SignatoryModel;

class SignatoryController extends BaseController
{
    private const SIGNATURE_DIR = 'uploads' . DIRECTORY_SEPARATOR . 'signatures';
    private const MAX_SIGNATURE_BYTES = 2097152;
    private const ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    private const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'webp'];

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
        $existing = $id ? $signatoryModel->find($id) : null;

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

        $signatureFile = $this->request->getFile('signature_image');
        $removeSignature = $this->request->getPost('remove_signature') === '1';
        $newSignatureName = null;

        if ($signatureFile && $signatureFile->isValid() && !$signatureFile->hasMoved()) {
            $error = $this->validateSignatureFile($signatureFile);
            if ($error !== null) {
                $redirect = $id
                    ? redirect()->to('/signatories/form/' . $id)
                    : redirect()->to('/signatories/form');
                return $redirect->withInput()->with('error', $error);
            }

            $newSignatureName = $signatureFile->getRandomName();
            $signatureFile->move($this->signatureDir(), $newSignatureName);
            $data['signature_image'] = $newSignatureName;
        } elseif ($removeSignature) {
            $data['signature_image'] = null;
        }

        if ($id) {
            $signatoryModel->update($id, $data);

            if (($newSignatureName !== null || $removeSignature) && $existing && !empty($existing['signature_image'])) {
                $this->deleteSignatureFile($existing['signature_image']);
            }

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

    public function signature($id)
    {
        $signatoryModel = new SignatoryModel();
        $signatory = $signatoryModel->find($id);

        if (!$signatory || empty($signatory['signature_image'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $filename = basename($signatory['signature_image']);
        $path = $this->signatureDir() . $filename;

        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'private, max-age=300')
            ->setBody(file_get_contents($path));
    }

    private function signatureDir(): string
    {
        return WRITEPATH . self::SIGNATURE_DIR . DIRECTORY_SEPARATOR;
    }

    private function validateSignatureFile($file): ?string
    {
        if ($file->getSize() > self::MAX_SIGNATURE_BYTES) {
            return 'Signature image must be 2 MB or smaller.';
        }

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return 'Signature image must be a PNG, JPG, or WEBP file.';
        }

        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return 'Signature image has an unsupported file type.';
        }

        return null;
    }

    private function deleteSignatureFile(string $filename): void
    {
        $path = $this->signatureDir() . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
