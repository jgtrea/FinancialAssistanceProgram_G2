<?php

namespace App\Controllers\Admin;

use App\Models\SchoolModel;
use CodeIgniter\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class School extends Controller
{
    protected SchoolModel $schoolModel;

    public function __construct()
    {
        $this->schoolModel = new SchoolModel();
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function index()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $filters = [
            'level'  => trim((string) ($this->request->getGet('level') ?? '')),
            'status' => (string) ($this->request->getGet('status') ?? ''),
        ];

        $schools = $this->schoolModel->getSchoolsForListing($keyword, $filters);

        return view('schools/index', [
            'title'   => 'Schools',
            'schools' => $schools,
            'keyword' => $keyword,
            'filters' => $filters,
        ]);
    }

    // ── JSON (edit modal populate) ────────────────────────────────────────────

    public function getJson(int $id)
    {
        $school = $this->schoolModel->find($id);
        if (!$school) {
            return $this->response->setStatusCode(404)
                ->setJSON(['success' => false, 'message' => 'School not found.']);
        }
        return $this->response->setJSON(['success' => true, 'school' => $school]);
    }

    // ── Save (add / edit) ─────────────────────────────────────────────────────

    public function save()
    {
        $id      = (int) $this->request->getPost('school_id');
        $name    = function_exists('mb_strtoupper')
            ? mb_strtoupper(trim((string) $this->request->getPost('school_name')), 'UTF-8')
            : strtoupper(trim((string) $this->request->getPost('school_name')));
        $level   = strtoupper(trim((string) $this->request->getPost('school_level')));
        $acronym = strtoupper(trim((string) $this->request->getPost('acronym')));
        if ($acronym === '') {
            $acronym = $this->generateAcronym($name);
        }

        if ($name === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'School name is required.']);
        }

        if (!in_array($level, ['JHS', 'SHS'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'School level must be JHS or SHS.']);
        }

        $excludeId = $id > 0 ? $id : null;
        if ($this->schoolModel->nameExistsForLevel($level, $name, $excludeId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "A {$level} school named \"{$name}\" already exists.",
            ]);
        }

        $userId = session()->get('user_id');

        if ($id > 0) {
            $this->schoolModel->update($id, ['school_name' => $name, 'school_level' => $level, 'acronym' => $acronym]);
            log_action($userId, 'UPDATE_SCHOOL', "Updated school #{$id}: {$name} ({$level})");

            return $this->response->setJSON(['success' => true, 'message' => 'School updated successfully.']);
        }

        $this->schoolModel->insert(['school_name' => $name, 'school_level' => $level, 'is_active' => 1, 'acronym' => $acronym]);
        log_action($userId, 'CREATE_SCHOOL', "Created school: {$name} ({$level}, {$acronym})");
        return $this->response->setJSON(['success' => true, 'message' => 'School added successfully.']);
    }

    // ── Archive / Restore ─────────────────────────────────────────────────────

    public function archiveMultiple()
    {
        $ids = $this->request->getPost('ids');
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No schools selected.']);
        }

        $userId = session()->get('user_id');
        $count  = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $this->schoolModel->update($id, ['is_active' => 0]);
                log_action($userId, 'ARCHIVE_SCHOOL', "Archived school #{$id}");
                $count++;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$count} school(s) archived successfully.",
        ]);
    }

    public function restoreMultiple()
    {
        $ids = $this->request->getPost('ids');
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No schools selected.']);
        }

        $userId = session()->get('user_id');
        $count  = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $this->schoolModel->update($id, ['is_active' => 1]);
                log_action($userId, 'RESTORE_SCHOOL', "Restored school #{$id}");
                $count++;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$count} school(s) restored successfully.",
        ]);
    }

    // ── Export ────────────────────────────────────────────────────────────────

    // GET admin/schools/export?format=excel&ids[]=1&ids[]=2
    // If no ids provided, exports all schools (respecting level/status query params).
    public function export()
    {
        $format  = $this->request->getGet('format') ?: 'excel';
        $ids     = $this->request->getGet('ids');     // array|null
        $schools = $this->getExportData($ids);

        if ($format === 'csv') {
            return $this->buildCsvResponse($schools);
        }
        return $this->buildExcelResponse($schools);
    }

    // ── Import ────────────────────────────────────────────────────────────────

    public function importSchools()
    {
        $file = $this->request->getFile('school_file');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Please upload a valid file.']);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only .xlsx, .xls, or .csv files are allowed.']);
        }

        try {
            $sheetData = $ext === 'csv'
                ? $this->parseCsv($file->getTempName())
                : IOFactory::load($file->getTempName())->getActiveSheet()->toArray();
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to read file: ' . $e->getMessage()]);
        }

        if (empty($sheetData)) {
            return $this->response->setJSON(['success' => false, 'message' => 'The file is empty.']);
        }

        // Validate header row (must contain "school name" and "level")
        $header    = array_map(static fn ($v) => strtolower(trim((string) $v)), $sheetData[0]);
        $nameIdx   = array_search('school name', $header, true);
        $levelIdx  = array_search('level', $header, true);
        $acronymIdx = array_search('acronym', $header, true); // optional

        if ($nameIdx === false || $levelIdx === false) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Expected column headers: "School Name" and "Level" (JHS or SHS). "Acronym" is optional.',
            ]);
        }

        $userId   = session()->get('user_id');
        $inserted = 0;
        $skipped  = 0;

        for ($i = 1, $total = count($sheetData); $i < $total; $i++) {
            $row   = $sheetData[$i];
            $name  = function_exists('mb_strtoupper')
                ? mb_strtoupper(trim((string) ($row[$nameIdx] ?? '')), 'UTF-8')
                : strtoupper(trim((string) ($row[$nameIdx] ?? '')));
            $level = strtoupper(trim((string) ($row[$levelIdx] ?? '')));

            if ($name === '' || !in_array($level, ['JHS', 'SHS'], true)) {
                $skipped++;
                continue;
            }

            if ($this->schoolModel->nameExistsForLevel($level, $name)) {
                $skipped++;
                continue;
            }

            $acronym = $acronymIdx !== false
                ? strtoupper(trim((string) ($row[$acronymIdx] ?? '')))
                : $this->generateAcronym($name);

            $this->schoolModel->insert(['school_name' => $name, 'school_level' => $level, 'is_active' => 1, 'acronym' => $acronym]);
            log_action($userId, 'IMPORT_SCHOOL', "Imported school: {$name} ({$level}, {$acronym})");
            $inserted++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Import complete: {$inserted} added, {$skipped} skipped.",
        ]);
    }

    // ── Active school options (JSON for dynamic dropdowns) ────────────────────

    public function optionsJson()
    {
        $model = new \App\Models\SchoolOptionModel();

        $options = static function (array $rows): array {
            return array_values(array_filter(array_map(static function ($r): array {
                return [
                    'school_id'   => (int) ($r['school_id'] ?? 0),
                    'school_name' => trim((string) ($r['school_name'] ?? '')),
                    'acronym'     => trim((string) ($r['acronym'] ?? '')),
                ];
            }, $rows), static fn ($r) => $r['school_id'] > 0 && $r['school_name'] !== ''));
        };

        return $this->response->setJSON([
            'jhs' => $options($model->getJuniorHighSchools()),
            'shs' => $options($model->getSeniorHighSchools()),
        ]);
    }

    // ── Import template download ───────────────────────────────────────────────

    public function importTemplate()
    {
        $csv = "School Name,Level\n"
             . "Sample Junior High School,JHS\n"
             . "Sample Senior High School,SHS\n";

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="schools_import_template.csv"')
            ->setHeader('Cache-Control', 'no-cache')
            ->setBody($csv);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getExportData(?array $ids): array
    {
        if (!empty($ids)) {
            $ids = array_map('intval', array_filter($ids, static fn ($v) => (int) $v > 0));
            if (!empty($ids)) {
                return $this->schoolModel->db->table('school')
                    ->select('school_id, school_name, acronym, school_level, is_active')
                    ->whereIn('school_id', $ids)
                    ->orderBy('school_level', 'ASC')
                    ->orderBy('school_name', 'ASC')
                    ->get()->getResultArray();
            }
        }
        // No IDs — export all
        return $this->schoolModel->getSchoolsForListing();
    }

    private function buildExcelResponse(array $schools)
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Schools');

        // Header row
        $sheet->fromArray([['School Name', 'Acronym', 'Level', 'Status']], null, 'A1');

        // Style header
        $headerStyle = $sheet->getStyle('A1:D1');
        $headerStyle->getFont()->setBold(true);

        // Data rows
        $row = 2;
        foreach ($schools as $s) {
            $sheet->fromArray([[
                $s['school_name'],
                $s['acronym'] ?? '',
                $s['school_level'],
                $s['is_active'] ? 'Active' : 'Inactive',
            ]], null, 'A' . $row);
            $row++;
        }

        // Auto-size columns
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer  = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'schools_export_');
        $writer->save($tmpFile);
        $body = file_get_contents($tmpFile);
        @unlink($tmpFile);

        $filename = 'schools_' . date('Ymd_His') . '.xlsx';
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($body);
    }

    private function buildCsvResponse(array $schools)
    {
        $out = fopen('php://memory', 'w');
        fputcsv($out, ['School Name', 'Acronym', 'Level', 'Status']);
        foreach ($schools as $s) {
            fputcsv($out, [
                $s['school_name'],
                $s['acronym'] ?? '',
                $s['school_level'],
                $s['is_active'] ? 'Active' : 'Inactive',
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $filename = 'schools_' . date('Ymd_His') . '.csv';
        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($csv);
    }

    private function generateAcronym(string $name): string
    {
        $skip  = ['AND', 'THE', 'OF', 'A', 'AN', 'OR', 'FOR'];
        $parts = preg_split('/[\s\-]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';
        foreach ($parts as $word) {
            $word = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $word));
            if ($word === '' || in_array($word, $skip, true)) continue;
            $initials .= $word[0];
        }
        return $initials;
    }

    private function parseCsv(string $path): array
    {
        $rows   = [];
        $handle = fopen($path, 'r');
        if (!$handle) return [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }
}
