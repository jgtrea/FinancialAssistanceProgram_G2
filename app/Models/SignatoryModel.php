<?php

namespace App\Models;

use CodeIgniter\Model;

class SignatoryModel extends Model
{
    protected $table      = 'signatories';
    protected $primaryKey = 'signatory_id';

    protected $allowedFields = [
        'full_name',
        'position_title',
        'signature_image',
        'is_active',
    ];
}