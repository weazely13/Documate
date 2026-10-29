<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationOfficer extends Model
{
    use Auditable;

    protected string $auditModule = 'Organizations';

    use HasFactory;

    protected $fillable = ['user_id', 'organization_id', 'officer_position_id', 'term_year'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function position()
    {
        return $this->belongsTo(OfficerPosition::class, 'officer_position_id');
    }
}