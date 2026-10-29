<?php

namespace App\Models\Concerns;

use App\Support\Audit;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Records creates, updates, deletes and restores of the model in the audit trail.
 * Mass updates through the query builder (Model::where(...)->update()) fire no model events, so they are not recorded.
 *
 * Optional on the model:
 *   protected array $auditIgnore = ['field', ...];   // changes to these alone are not recorded
 *   protected string $auditModule = 'Users';         // module shown on the Audit Trail page
 *   public function auditName(): string               // how the record is named in descriptions
 */
trait Auditable
{
    /** Never worth an audit row on their own. */
    private static array $auditAlwaysIgnored = ['updated_at', 'created_at', 'remember_token', 'deleted_at'];

    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->writeAudit('created'));

        static::updated(function ($model) {
            $ignored = array_merge(self::$auditAlwaysIgnored, property_exists($model, 'auditIgnore') ? $model->auditIgnore : []);
            $changes = [];
            foreach (array_diff_key($model->getChanges(), array_flip($ignored)) as $field => $new) {
                $changes[$field] = [Audit::value($field, $model->getOriginal($field)), Audit::value($field, $new)];
            }
            if ($changes) {
                $model->writeAudit('updated', $changes);
            }
        });

        static::deleted(fn ($model) => $model->writeAudit('deleted'));

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn ($model) => $model->writeAudit('restored'));
        }
    }

    public function auditModule(): string
    {
        return property_exists($this, 'auditModule') ? $this->auditModule : Str::headline(Str::plural(class_basename($this)));
    }

    public function auditName(): string
    {
        $name = $this->name ?? $this->label ?? $this->title ?? null;

        if (! $name && isset($this->first_name)) {
            $name = \App\Models\User::formatName($this->first_name, $this->middle_name ?? null, $this->last_name, $this->suffix ?? null);
        }

        // Records that belong to a person (appointments, clearance, verification) are named after them
        if (! $name && $this->user_id && method_exists($this, 'user')) {
            $owner = $this->user()->withoutGlobalScopes()->first();
            if ($owner) {
                return '#' . $this->getKey() . ' for ' . $owner->full_name;
            }
        }

        return $name ?: Str::headline(class_basename($this)) . ' #' . $this->getKey();
    }

    protected function writeAudit(string $action, ?array $changes = null): void
    {
        $verb = match ($action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
        };

        $description = "{$verb} " . Str::lower(Str::headline(class_basename($this))) . ' "' . $this->auditName() . '"';
        if ($changes) {
            $fields = collect(array_keys($changes))->map(fn ($f) => Str::lower(Str::headline($f)));
            $description .= ': ' . $fields->take(4)->implode(', ') . ($fields->count() > 4 ? ' and ' . ($fields->count() - 4) . ' more' : '');
        }

        Audit::record($action, $this->auditModule(), $description, $this, $changes);
    }
}
