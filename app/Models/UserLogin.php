<?php namespace App\Models;
use CodeIgniter\Model;

class UserLogin extends Model {
    protected $table      = 'users';
    protected $primaryKey = 'user_id';
    protected $allowedFields = [
        'username',
        'password',
        'role',
        'full_name',
        'is_active',
        'last_login'
    ];
}