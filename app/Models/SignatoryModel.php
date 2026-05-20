<?php

namespace App\Models;

use CodeIgniter\Model;

class SignatoryModel extends Model
{
    protected $table      = 'signatories';
    protected $primaryKey = 'signatory_id';

    protected $allowedFields = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'position_title',
        'signature_image',
        'is_active',
    ];
}