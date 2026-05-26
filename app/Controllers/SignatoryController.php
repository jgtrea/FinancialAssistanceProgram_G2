<?php

namespace App\Controllers;

use App\Models\SignatoryModel;

class SignatoryController extends BaseController
{
    private const SIGNATURE_DIR = 'uploads' . DIRECTORY_SEPARATOR . 'signatures';
    private const MAX_SIGNATURE_BYTES = 2097152;
    private const ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    private const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'webp'];
    private const PREFIX_OPTIONS = ['', 'DR.', 'ENGR.', 'HON.', 'MR.', 'MRS.', 'MS.', 'PROF.'];
    private const SUFFIX_OPTIONS = ['', 'JR.', 'SR.', 'II', 'III', 'IV', 'V', 'CPA', 'LPT', 'MD', 'PHD'];

    public function index()
    {
        $signatoryModel = new SignatoryModel();

        return view('signatories/index', [
            'title' => 'Signatories',
            'signatories' => $signatoryModel
                ->where('is_active', 1)
                ->orderBy('signatory_id', 'DESC')
                ->findAll(),
            'prefixOptions' => self::PREFIX_OPTIONS,
            'suffixOptions' => self::SUFFIX_OPTIONS,
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
            'signatory' => $signatory,
            'prefixOptions' => self::PREFIX_OPTIONS,
            'suffixOptions' => self::SUFFIX_OPTIONS,
        ]);
    }

    public function save()
    {
        $signatoryModel = new SignatoryModel();
        $isAjax = $this->request->isAJAX();

        $id = $this->request->getPost('signatory_id');
        $existing = $id ? $signatoryModel->find($id) : null;
        $prefix = strtoupper(trim((string) $this->request->getPost('prefix')));
        $suffix = strtoupper(trim((string) $this->request->getPost('suffix')));

        if ($id && !$existing) {
            return $this->signatorySaveError('Signatory not found.', $id, $isAjax);
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'prefix'         => 'permit_empty|in_list[DR.,ENGR.,HON.,MR.,MRS.,MS.,PROF.]',
            'first_name'     => 'required|max_length[100]',
            'middle_name'    => 'permit_empty|max_length[100]',
            'last_name'      => 'required|max_length[100]',
            'suffix'         => 'permit_empty|in_list[JR.,SR.,II,III,IV,V,CPA,LPT,MD,PHD]',
            'position_title' => 'required|max_length[200]',
            'is_active'      => 'permit_empty|in_list[0,1]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            return $this->signatorySaveError(
                $this->validationErrorMessage($errors, 'Please check the signatory details.'),
                $id,
                $isAjax,
                $errors
            );
        }

        if (!in_array($prefix, self::PREFIX_OPTIONS, true)) {
            return $this->signatorySaveError('Please select a valid prefix.', $id, $isAjax);
        }

        if (!in_array($suffix, self::SUFFIX_OPTIONS, true)) {
            return $this->signatorySaveError('Please select a valid suffix.', $id, $isAjax);
        }

        $data = [
            'prefix'         => $prefix !== '' ? $prefix : null,
            'first_name'     => trim((string) $this->request->getPost('first_name')),
            'middle_name'    => trim((string) $this->request->getPost('middle_name')),
            'last_name'      => trim((string) $this->request->getPost('last_name')),
            'suffix'         => $suffix,
            'position_title' => trim((string) $this->request->getPost('position_title')),
            'is_active'      => $this->request->getPost('is_active') ?? 1,
        ];

        $userId = session()->get('user_id');
        $name   = trim($this->request->getPost('first_name') . ' ' . $this->request->getPost('last_name'));

        $signatureFile = $this->request->getFile('signature_image');
        $removeSignature = $this->request->getPost('remove_signature') === '1';
        $newSignatureName = null;

        if ($signatureFile && $signatureFile->isValid() && !$signatureFile->hasMoved()) {
            $error = $this->validateSignatureFile($signatureFile);
            if ($error !== null) {
                return $this->signatorySaveError($error, $id, $isAjax);
            }

            $autoRemoveBg = $this->request->getPost('auto_remove_bg') === '1';

            if ($autoRemoveBg) {
                $newSignatureName = bin2hex(random_bytes(8)) . '.png';
                $destPath = $this->signatureDir() . $newSignatureName;
                if (!$this->removeBackground($signatureFile->getTempName(), $destPath)) {
                    return $this->signatorySaveError(
                        'Could not process the signature image. Try a different file.',
                        $id,
                        $isAjax
                    );
                }
            } else {
                $newSignatureName = $signatureFile->getRandomName();
                $signatureFile->move($this->signatureDir(), $newSignatureName);
            }

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

            if ($isAjax) {
                return $this->response->setJSON(['success' => true, 'message' => 'Signatory updated successfully.']);
            }
            return redirect()->to('/signatories')->with('success', 'Signatory updated successfully.');
        }

        $signatoryModel->insert($data);
        log_action($userId, 'CREATE_SIGNATORY', "Created signatory {$name}");

        if ($isAjax) {
            return $this->response->setJSON(['success' => true, 'message' => 'Signatory added successfully.']);
        }
        return redirect()->to('/signatories')->with('success', 'Signatory added successfully.');
    }

    private function signatorySaveError(string $message, $id, bool $isAjax, array $errors = [])
    {
        if ($isAjax) {
            return $this->response->setJSON(['success' => false, 'message' => $message, 'errors' => $errors]);
        }
        $redirect = $id
            ? redirect()->to('/signatories/form/' . $id)
            : redirect()->to('/signatories/form');
        return $redirect->withInput()->with('error', $message);
    }

    private function validationErrorMessage(array $errors, string $fallback): string
    {
        if (empty($errors)) {
            return $fallback;
        }

        return 'Validation failed. Please review the field details below.';
    }

    public function getJson($id)
    {
        $signatory = (new SignatoryModel())->find($id);

        if (!$signatory) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Signatory not found.',
            ]);
        }

        $signatory['signature_url'] = !empty($signatory['signature_image'])
            ? base_url('signatories/signature/' . (int) $id)
            : null;

        return $this->response->setJSON([
            'success'   => true,
            'signatory' => $signatory,
        ]);
    }

    public function deactivate($id)
    {
        $signatoryModel = new SignatoryModel();
        $signatoryModel->update($id, ['is_active' => 0, 'is_selected' => 0]);
        log_action(session()->get('user_id'), 'DEACTIVATE_SIGNATORY', "Deactivated signatory #{$id}");
        return redirect()->to('/signatories')->with('success', 'Signatory archived.');
    }

    public function setStatus($id, $action)
    {
        $signatoryModel = new SignatoryModel();
        $signatory = $signatoryModel->find($id);

        if (!$signatory) {
            return $this->response->setJSON(['success' => false, 'message' => 'Signatory not found.']);
        }

        if ($action === 'select') {
            $selectedCount = $signatoryModel->where('is_selected', 1)->countAllResults();
            if ($selectedCount >= 3) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Maximum of 3 signatories can be selected. Deselect one first.',
                ]);
            }
            $signatoryModel->update($id, ['is_selected' => 1]);
            log_action(session()->get('user_id'), 'SELECT_SIGNATORY', "Selected signatory #{$id}");
            return $this->response->setJSON(['success' => true, 'selected' => true, 'message' => 'Signatory selected.']);
        }

        $signatoryModel->update($id, ['is_selected' => 0]);
        log_action(session()->get('user_id'), 'DESELECT_SIGNATORY', "Deselected signatory #{$id}");
        return $this->response->setJSON(['success' => true, 'selected' => false, 'message' => 'Signatory deselected.']);
    }

    public function archiveMultiple()
    {
        $ids = $this->request->getPost('ids');
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No signatories selected.']);
        }

        $signatoryModel = new SignatoryModel();
        $userId = session()->get('user_id');

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $signatoryModel->update($id, ['is_active' => 0, 'is_selected' => 0]);
                log_action($userId, 'DEACTIVATE_SIGNATORY', "Archived signatory #{$id}");
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => count($ids) . ' signatory(ies) archived.']);
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

    private function removeBackground(string $sourcePath, string $destPath): bool
    {
        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return false;
        }

        $src = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default      => null,
        };
        if (!$src) {
            return false;
        }

        // Downscale very large inputs to keep processing fast.
        $maxWidth = 1200;
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW > $maxWidth) {
            $newW = $maxWidth;
            $newH = (int) round($srcH * ($maxWidth / $srcW));
            $scaled = imagecreatetruecolor($newW, $newH);
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            imagecopyresampled($scaled, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
            imagedestroy($src);
            $src = $scaled;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefilledrectangle($out, 0, 0, $w, $h, $transparent);

        // Sample background color from the four corners and edge midpoints.
        $sampleCoords = [
            [0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1],
            [(int) ($w / 2), 0], [(int) ($w / 2), $h - 1],
            [0, (int) ($h / 2)], [$w - 1, (int) ($h / 2)],
        ];
        $bgR = $bgG = $bgB = 0;
        foreach ($sampleCoords as [$sx, $sy]) {
            $rgb  = imagecolorat($src, $sx, $sy);
            $bgR += ($rgb >> 16) & 0xFF;
            $bgG += ($rgb >> 8) & 0xFF;
            $bgB += $rgb & 0xFF;
        }
        $count = count($sampleCoords);
        $bgR = intdiv($bgR, $count);
        $bgG = intdiv($bgG, $count);
        $bgB = intdiv($bgB, $count);

        // Distance thresholds: fully transparent below $hard, fully opaque above $hard + $soft,
        // smoothly faded in between (anti-aliased edge).
        $hard = 55.0;
        $soft = 35.0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $dr = $r - $bgR;
                $dg = $g - $bgG;
                $db = $b - $bgB;
                $dist = sqrt($dr * $dr + $dg * $dg + $db * $db);

                if ($dist <= $hard) {
                    continue;
                }

                if ($dist >= $hard + $soft) {
                    $alpha = 0;
                } else {
                    $alpha = (int) round(127 * (1 - ($dist - $hard) / $soft));
                }

                $color = imagecolorallocatealpha($out, $r, $g, $b, $alpha);
                imagesetpixel($out, $x, $y, $color);
                imagecolordeallocate($out, $color);
            }
        }

        imagedestroy($src);

        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ok = imagepng($out, $destPath);
        imagedestroy($out);
        return $ok;
    }
}
