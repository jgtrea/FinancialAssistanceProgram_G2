<?php namespace App\Models;
use CodeIgniter\Model;

class UserVoucherModel extends Model {
    protected $table = 'user_voucher';
    protected $allowedFields = ['user_id', 'voucher_no'];
}