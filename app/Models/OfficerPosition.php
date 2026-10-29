<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficerPosition extends Model
{
    use Auditable;

    protected string $auditModule = 'Organizations';

    use HasFactory;

    protected $fillable = ['title', 'sort_order'];

    public function officers()
    {
        return $this->hasMany(OrganizationOfficer::class);
    }
}