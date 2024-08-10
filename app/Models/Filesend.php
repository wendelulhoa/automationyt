<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filesend extends Model
{
    use HasFactory;

    protected $table = 'filesend';
    protected $fillable = ['path', 'type', 'forget_in'];
}
