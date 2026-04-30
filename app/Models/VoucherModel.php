<?php namespace App\Models;
use CodeIgniter\Model;

class VoucherModel extends Model {
    protected $table = 'voucher';
    protected $primaryKey = 'voucher_no';
    protected $useAutoIncrement = false;
    protected $allowedFields = ['voucher_no', 'voucher_date', 'rank', 'gwa', 'jhr', 'preferred_shr', 'remarks'];
}