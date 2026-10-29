<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class College extends Model
{
    use Auditable;

    protected string $auditModule = 'Organizations';

    protected $fillable = ['code', 'name'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}