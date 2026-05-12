<?php

namespace App\Controllers;

use App\Models\StudentArchiveModel;

class ArchiveController extends BaseController
{
    public function index()
    {
        $archiveModel = new StudentArchiveModel();

        return view('archive/index', [
            'title' => 'Student Archive',
            'archives' => $archiveModel->orderBy('archive_id', 'DESC')->findAll()
        ]);
    }
}