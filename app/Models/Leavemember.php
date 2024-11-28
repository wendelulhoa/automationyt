<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leavemember extends Model
{
    use HasFactory;
    protected $table = 'leavemembers';
    protected $fillable = ['jid', 'leavets'];
}
