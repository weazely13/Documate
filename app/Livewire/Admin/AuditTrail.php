<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit;
use App\Workflow\AuditLabels;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Who did what, and when. Reads the system trail (audit_logs) and the transaction workflow trail
 * (transaction_audit_logs) as one list.
 */
class AuditTrail extends Component
{
    use WithPagination;

    public const PER_PAGE = 25;
    private const EXPORT_LIMIT = 20000;

    public const PERIODS = [
        '' => 'All time',
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'custom' => 'Custom range',
    ];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $action = '';

    #[Url(except: '')]
    public string $module = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(except: '')]
    public string $period = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public ?string $openRow = null;

    public function updating($name): void
    {
        if (in_array($name, ['search', 'action', 'module', 'role', 'period', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
            $this->openRow = null;
        }
    }

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->dateFrom = '';
            $this->dateTo = '';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'action', 'module', 'role', 'period', 'dateFrom', 'dateTo', 'openRow']);
        $this->resetPage();
    }

    public function toggleRow(string $key): void
    {
        $this->openRow = $this->openRow === $key ? null : $key;
    }

    /** Both trails as one query with the same columns. */
    private function union(): Builder
    {
        $system = DB::table('audit_logs')->select([
            DB::raw("'system' as source"), 'id', 'created_at', 'user_id', 'user_role', 'action', 'module', 'description',
            'subject_type', 'subject_id', 'changes', 'ip_address', 'user_agent',
            DB::raw('NULL as raw_action'), DB::raw('NULL as from_status'), DB::raw('NULL as to_status'),
        ]);

        $workflow = DB::table('transaction_audit_logs')->select([
            DB::raw("'workflow' as source"), 'id', 'created_at', DB::raw('actor_id as user_id'), DB::raw('actor_role as user_role'),
            DB::raw("'workflow' as action"), DB::raw("'Transactions' as module"), DB::raw("'' as description"),
            DB::raw('NULL as subject_type'), DB::raw('workspace_id as subject_id'), DB::raw('meta as changes'), 'ip_address',
            DB::raw('NULL as user_agent'), DB::raw('action as raw_action'), 'from_status', 'to_status',
        ]);

        return DB::query()->fromSub($system->unionAll($workflow), 't');
    }

    private function filtered(): Builder
    {
        [$from, $to] = $this->range();

        return $this->union()
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->select('t.*', 'u.first_name', 'u.middle_name', 'u.last_name', 'u.suffix', 'u.email', 'u.profile_picture')
            ->when($this->action !== '', fn ($q) => $q->where('t.action', $this->action))
            ->when($this->module !== '', fn ($q) => $q->where('t.module', $this->module))
            ->when($this->role !== '', fn ($q) => $this->role === 'System'
                ? $q->whereNull('t.user_id')
                : $q->where('t.user_role', $this->role))
            ->when($from, fn ($q) => $q->where('t.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('t.created_at', '<=', $to))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(fn ($w) => $w->where('t.description', 'like', $term)
                    ->orWhere('t.module', 'like', $term)
                    ->orWhere('t.raw_action', 'like', $term)
                    ->orWhere('t.ip_address', 'like', $term)
                    ->orWhere('u.first_name', 'like', $term)
                    ->orWhere('u.last_name', 'like', $term)
                    ->orWhere('u.email', 'like', $term)
                    ->orWhere(DB::raw("CONCAT(u.first_name, ' ', u.last_name)"), 'like', $term));
            })
            ->orderByDesc('t.created_at')
            ->orderByDesc('t.id');
    }

    /** @return array{0: ?\Carbon\CarbonInterface, 1: ?\Carbon\CarbonInterface} */
    private function range(): array
    {
        return match ($this->period) {
            'today' => [today(), null],
            '7d' => [today()->subDays(6), null],
            '30d' => [today()->subDays(29), null],
            'custom' => [
                $this->dateFrom !== '' ? rescue(fn () => \Carbon\Carbon::parse($this->dateFrom)->startOfDay(), null, false) : null,
                $this->dateTo !== '' ? rescue(fn () => \Carbon\Carbon::parse($this->dateTo)->endOfDay(), null, false) : null,
            ],
            default => [null, null],
        };
    }

    /** One row as the page and the CSV show it. */
    private function present(object $row): array
    {
        $changes = is_string($row->changes) ? json_decode($row->changes, true) : $row->changes;
        $isWorkflow = $row->source === 'workflow';

        $description = $isWorkflow
            ? AuditLabels::label((string) $row->raw_action) . ' · Transaction #' . $row->subject_id
                . ($row->from_status && $row->to_status && $row->from_status !== $row->to_status
                    ? ' (' . Str::headline($row->from_status) . ' → ' . Str::headline($row->to_status) . ')' : '')
            : $row->description;

        return [
            'key' => $row->source . '-' . $row->id,
            'at' => \Carbon\Carbon::parse($row->created_at),
            'user' => $row->user_id
                ? (User::formatName($row->first_name, $row->middle_name, $row->last_name, $row->suffix) ?: ($row->email ?: 'User #' . $row->user_id))
                : 'System',
            'email' => $row->email,
            'avatar' => User::avatarUrlFor($row->profile_picture),
            'role' => $row->user_role ?: ($row->user_id ? null : 'Automatic'),
            'action' => $row->action,
            'action_label' => AuditLog::ACTIONS[$row->action] ?? Str::headline($row->action),
            'module' => $row->module,
            'description' => $description,
            'changes' => is_array($changes) ? $changes : [],
            'is_diff' => ! $isWorkflow && $row->action === 'updated',
            'ip' => $row->ip_address,
            'agent' => $row->user_agent,
            'link' => $isWorkflow && $row->subject_id ? route('admin.workflow.show', $row->subject_id) : null,
        ];
    }

    public function export(): StreamedResponse
    {
        $query = $this->filtered()->limit(self::EXPORT_LIMIT);
        $count = min($this->filtered()->count(), self::EXPORT_LIMIT);

        Audit::record('exported', 'Audit Trail', "Downloaded the audit trail as CSV ({$count} " . Str::plural('row', $count) . ')'
            . ($this->activeFilterSummary() ? ', filtered by ' . $this->activeFilterSummary() : ''));

        $filename = 'documate-audit-trail-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8
            fputcsv($out, ['Date', 'Time', 'User', 'Email', 'Role', 'Action', 'Module', 'Description', 'Changes', 'IP address']);

            foreach ($query->cursor() as $row) {
                $r = $this->present($row);
                $changes = collect($r['changes'])->map(fn ($v, $k) => $r['is_diff'] && is_array($v)
                    ? Str::headline($k) . ': ' . $this->csvValue($v[0] ?? null) . ' → ' . $this->csvValue($v[1] ?? null)
                    : Str::headline((string) $k) . ': ' . $this->csvValue($v))->implode('; ');

                fputcsv($out, [
                    $r['at']->format('Y-m-d'), $r['at']->format('H:i:s'), $r['user'], $r['email'], $r['role'],
                    $r['action_label'], $r['module'], $r['description'], $changes, $r['ip'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvValue(mixed $v): string
    {
        return match (true) {
            $v === null || $v === '' => '(empty)',
            is_array($v) => json_encode($v),
            is_bool($v) => $v ? 'Yes' : 'No',
            default => (string) $v,
        };
    }

    private function activeFilterSummary(): string
    {
        return collect([
            $this->search !== '' ? "search \"{$this->search}\"" : null,
            $this->action !== '' ? 'action ' . (AuditLog::ACTIONS[$this->action] ?? $this->action) : null,
            $this->module !== '' ? "module {$this->module}" : null,
            $this->role !== '' ? "role {$this->role}" : null,
            $this->period !== '' ? 'period ' . self::PERIODS[$this->period] . ($this->period === 'custom' ? " {$this->dateFrom} to {$this->dateTo}" : '') : null,
        ])->filter()->implode(', ');
    }

    public function render()
    {
        $page = $this->filtered()->paginate(self::PER_PAGE);
        $rows = collect($page->items())->map(fn ($row) => $this->present($row));

        $modules = collect(AuditLog::MODULES)
            ->merge(DB::table('audit_logs')->distinct()->pluck('module'))
            ->unique()->sort()->values();
        $roles = DB::table('audit_logs')->whereNotNull('user_role')->distinct()->pluck('user_role')
            ->merge(DB::table('transaction_audit_logs')->whereNotNull('actor_role')->distinct()->pluck('actor_role'))
            ->unique()->sort()->values()->push('System');

        return view('livewire.admin.audit-trail', [
            'rows' => $rows,
            'page' => $page,
            'modules' => $modules,
            'roles' => $roles,
            'actions' => AuditLog::ACTIONS,
            'periods' => self::PERIODS,
            'hasFilters' => $this->activeFilterSummary() !== '',
            'today' => [
                'events' => AuditLog::where('created_at', '>=', today())->count()
                    + DB::table('transaction_audit_logs')->where('created_at', '>=', today())->count(),
                'signins' => AuditLog::where('action', 'login')->where('created_at', '>=', today())->count(),
                'failed' => AuditLog::where('action', 'login_failed')->where('created_at', '>=', today())->count(),
                'changes' => AuditLog::whereIn('action', ['created', 'updated', 'deleted', 'restored'])->where('created_at', '>=', today())->count(),
            ],
        ])->layout('layouts.app', ['title' => 'Audit Trail']);
    }
}
