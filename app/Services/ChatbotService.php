<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotService
{
    protected array $offices;
    protected array $handbook;
    protected array $handbookChunks = [];

    public function __construct()
    {
        $this->offices = json_decode(
            file_get_contents(resource_path('data/lnu_offices.json')), true
        ) ?? [];

        $this->handbook = json_decode(
            file_get_contents(resource_path('data/lnu-handbook.json')), true
        ) ?? [];

        $this->handbookChunks = $this->buildHandbookChunks($this->handbook);
    }

    protected function buildHandbookChunks(array $sections): array
    {
        $chunks = [];

        foreach ($sections as $block) {
            $text = $block['text'] ?? '';
            $section = $block['section'] ?? 'handbook';

            // Split on the anchor markers, keeping the TOC index
            $parts = preg_split('/⟦A:(\d+)⟧/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

            // $parts[0] = text before first marker, then [index, text, index, text, ...]
            $push = function (string $body, ?int $tocIndex) use (&$chunks, $section) {
                $body = trim($body);
                if ($body === '') {
                    return;
                }
                // Cap oversized chunks by splitting on blank lines
                foreach ($this->splitLong($body, 1200) as $piece) {
                    $chunks[] = [
                        'section' => $section,
                        'toc_index' => $tocIndex,
                        'text' => $piece,
                    ];
                }
            };

            $push($parts[0] ?? '', null);

            for ($i = 1; $i < count($parts); $i += 2) {
                $push($parts[$i + 1] ?? '', (int) $parts[$i]);
            }
        }

        return $chunks;
    }

    protected function splitLong(string $text, int $max): array
    {
        if (mb_strlen($text) <= $max) {
            return [$text];
        }

        $pieces = [];
        $current = '';

        foreach (preg_split('/\n\s*\n/', $text) as $para) {
            if ($current !== '' && mb_strlen($current) + mb_strlen($para) > $max) {
                $pieces[] = trim($current);
                $current = '';
            }
            $current .= ($current ? "\n\n" : '') . $para;
        }

        if (trim($current) !== '') {
            $pieces[] = trim($current);
        }

        return $pieces;
    }

    protected function findRelevantHandbookSections(string $query, int $limit = 3): array
    {
        $words = array_values(array_filter(
            preg_split('/\s+/', Str::lower($query)),
            fn ($w) => strlen($w) > 2
        ));

        if (empty($words)) {
            return [];
        }

        $scored = [];
        foreach ($this->handbookChunks as $chunk) {
            $haystack = Str::lower($chunk['text']);
            $score = 0;
            foreach ($words as $word) {
                if (str_contains($haystack, $word)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scored[] = ['chunk' => $chunk, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'chunk'), 0, $limit);
    }

    protected function findRelevantOffices(string $query, int $limit = 4): array
    {
        $query = Str::lower($query);
        $scored = [];

        foreach ($this->offices as $office) {
            $haystack = Str::lower(implode(' ', [
                $office['name'],
                implode(' ', $office['aliases'] ?? []),
                implode(' ', $office['services'] ?? []),
            ]));

            $score = 0;
            foreach (preg_split('/\s+/', $query) as $word) {
                if (strlen($word) > 2 && str_contains($haystack, $word)) {
                    $score++;
                }
            }

            if ($score > 0) {
                $scored[] = ['office' => $office, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'office'), 0, $limit);
    }


    protected function systemPrompt(string $latestUserMessage, ?\App\Models\User $user = null): string
    {   
        $processContext = $this->processGuide($latestUserMessage, $user);
        $relevantOffices = $this->findRelevantOffices($latestUserMessage);
        $relevantHandbook = $this->findRelevantHandbookSections($latestUserMessage);

        $officeContext = empty($relevantOffices)
            ? 'No specific office matched this query.'
            : collect($relevantOffices)->map(fn ($o) => sprintf(
                "- %s (%s, %s). Handles: %s. Hours: %s. Contact: %s",
                $o['name'], $o['building'], $o['floor'],
                implode(', ', $o['services'] ?? []), $o['hours'] ?? 'N/A', $o['contact'] ?? 'N/A'
            ))->implode("\n");

        $handbookContext = empty($relevantHandbook)
            ? 'No specific handbook section matched this query.'
            : collect($relevantHandbook)->map(fn ($c) =>
                "- (" . ucfirst($c['section']) . ") " . Str::limit($c['text'], 700)
            )->implode("\n");

        $identityBlock = $user
            ? sprintf(
                "The current logged-in user is %s (role: %s). You already know who they are — never ask for a student ID, email, or any identifying information. When they ask about their own requests, appointments, or clearance status, call the relevant tool immediately; it is automatically scoped to this user by the system.",
                trim("{$user->first_name} {$user->last_name}"),
                $user->role->role_name ?? 'Student'
            )
            : "No user is currently authenticated. If asked about personal records, tell them to log in first — do not attempt to call any personal-data tools.";

        return <<<PROMPT
You are the DocuMate Assistant, an in-app helper for LNU's document transaction and appointment system.
{$identityBlock}

Your job:
1. Help students/officers use Documate: New Transaction, appointments, document/clearance status.
2. Help users locate offices, using the campus office data below.
3. Answer questions about the LNU Student Handbook (2022 Edition), using the excerpts below.
4. Be brief and practical. If you don't know something, say so and suggest who to ask instead of guessing.

Reply formatting (this matters):
- Keep answers to 2-5 sentences, or a short list. Never write long paragraphs.
- Never indent any line. Every line starts at the left edge.
- For unordered items use "- ".
- For steps or ordered items use "1.", "2.", "3.".
- For sub-items under a numbered item use "a.", "b.", "c." on their own line, still with no indentation.
- For a third level use roman numerals "i.", "ii.", "iii.".
- Put each item on its own line.
- Use **bold** only for a key label or number, not whole sentences.
- If you're about to present several things the user could choose from next, call the suggest_quick_replies tool instead of listing them in prose.
- For "how do I..." or process questions, use a numbered list with one short step per line (up to 10 steps). Otherwise keep to 2-5 sentences.
- Call at most one data tool per question, then write your answer. If the user names a document, call get_template_requirements directly; do not call list_document_templates first.

When the user asks about their own requests, appointments, or clearance status, call the relevant tool instead of guessing.
- Never write tool names, JSON, or "suggest_quick_replies" in your reply text. Tools are called silently.
- If you call suggest_quick_replies, do not also list those same options in your reply text.
- suggest_quick_replies options must be plain strings, e.g. ["Check my appointment", "New transaction"].
- When the user asks what documents they can request, call list_document_templates and present the names as a numbered list. Tell them to open New Transaction from the sidebar to start one.
- - For get_template_requirements: print every line of "fields_to_fill_in" exactly as given, one per line, without changing the text. Never add input types like TEXT, DATE, or PARAGRAPH. Then one line "Filled in for you: ..." from "auto_filled_by_system", then the instructions.

{$processContext}
Only explain how DocuMate works using the guide above. Do not invent steps, buttons, or tabs.

Relevant campus offices for this query:
{$officeContext}
Only mention offices from the list above. Never invent office names, hours, or contacts.

Relevant Student Handbook excerpts for this query:
{$handbookContext}
Only answer handbook questions using the excerpts above. If they don't cover it, say you're not certain and suggest checking the Office of Student Development or the full handbook — don't guess at policy details.
PROMPT;
    }

    /**
     * @return array{reply: string, quick_replies: array}
     */
    public function ask(array $history, ?\App\Models\User $user = null): array
    {
        $latestUserMessage = collect($history)->last(fn ($m) => $m['role'] === 'user')['content'] ?? '';

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($latestUserMessage, $user)]],
            array_slice($history, -10)
        );

        $quickReplies = [];
        $maxRounds = 5;

        $parseQuickReplies = fn (array $args) => collect($args['options'] ?? [])
            ->map(fn ($o) => is_array($o) ? ($o['title'] ?? $o['label'] ?? null) : $o)
            ->filter(fn ($o) => is_string($o) && $o !== '')
            ->values()
            ->take(5)
            ->all();

        try {
            for ($round = 1; $round <= $maxRounds; $round++) {
                $isLast = $round === $maxRounds;
                $response = $this->callGroq($messages, withTools: ! $isLast);

                if ($response->failed()) {
                    Log::error('Groq chatbot error', [
                        'round' => $round,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return $this->reply($this->describeFailure($response));
                }

                $message = $response->json('choices.0.message');

                // No tool calls: this is the final answer
                if (empty($message['tool_calls'])) {
                    return $this->reply($this->cleanReply($message['content'] ?? ''), $quickReplies);
                }

                // Only suggest_quick_replies was called and the model already wrote its answer
                $onlyQuickReplies = collect($message['tool_calls'])
                    ->every(fn ($tc) => ($tc['function']['name'] ?? '') === 'suggest_quick_replies');

                if ($onlyQuickReplies && trim((string) ($message['content'] ?? '')) !== '') {
                    foreach ($message['tool_calls'] as $tc) {
                        $args = json_decode($tc['function']['arguments'] ?? '{}', true) ?? [];
                        $quickReplies = $parseQuickReplies($args);
                    }
                    return $this->reply($this->cleanReply($message['content']), $quickReplies);
                }

                $messages[] = $message;

                foreach ($message['tool_calls'] as $toolCall) {
                    $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];
                    $name = $toolCall['function']['name'];

                    if ($name === 'suggest_quick_replies') {
                        $quickReplies = $parseQuickReplies($args);
                        $result = ['ok' => true];
                    } else {
                        $result = $this->executeTool($name, $args, $user?->id);
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode($result),
                    ];
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Groq chatbot connection error', ['message' => $e->getMessage()]);
            return $this->reply("Couldn't reach the AI service. It timed out or the internet connection failed. Please try again.");
        } catch (\Throwable $e) {
            Log::error('Groq chatbot exception', ['message' => $e->getMessage()]);
            return $this->reply("Something went wrong on my end (" . class_basename($e) . "). Please try again.");
        }

        return $this->reply("Sorry, I couldn't finish that. Please try again.", $quickReplies);
    }
    protected function describeFailure(\Illuminate\Http\Client\Response $response): string
    {
        $status = $response->status();
        $apiMessage = trim((string) ($response->json('error.message') ?? ''));
        $lower = Str::lower($apiMessage);

        // Wait time: header first, then the "try again in 7m12s" text in the message
        $wait = '';
        if ($retry = $response->header('retry-after')) {
            $wait = $this->humanWait((int) ceil((float) $retry));
        } elseif (preg_match('/try again in ([0-9hms\.]+)/i', $apiMessage, $m)) {
            $wait = $m[1];
        }
        $retryText = $wait ? " Please try again in about {$wait}." : ' Please try again later.';

        if ($status === 429) {
            return match (true) {
                str_contains($lower, 'tokens per day') || str_contains($lower, 'tpd')
                    => "The AI's daily token limit has been reached (Error 429)." . $retryText,
                str_contains($lower, 'tokens per minute') || str_contains($lower, 'tpm')
                    => "The AI's per-minute token limit has been reached (Error 429)." . $retryText,
                str_contains($lower, 'requests per day') || str_contains($lower, 'rpd')
                    => "The AI's daily request limit has been reached (Error 429)." . $retryText,
                str_contains($lower, 'requests per minute') || str_contains($lower, 'rpm')
                    => "Too many requests per minute (Error 429)." . $retryText,
                default => "The AI service rate limit has been reached (Error 429)." . $retryText,
            };
        }

        return match (true) {
            $status === 413 => "This request is too large for the AI service (Error 413). Try a shorter message or clear the chat.",
            in_array($status, [401, 403], true) => "The AI service rejected the API key (Error {$status}). Please check the Groq API key in the .env file.",
            $status === 404 => "The AI model was not found (Error 404). Please check services.groq.model in the config.",
            $status === 400 => "The AI service couldn't process this request (Error 400)" . ($apiMessage ? ': ' . Str::limit($apiMessage, 140) : '.'),
            $status >= 500 => "The AI service is temporarily unavailable (Error {$status})." . $retryText,
            default => "The AI service returned an unexpected error (Error {$status})" . ($apiMessage ? ': ' . Str::limit($apiMessage, 140) : '.'),
        };
    }

    protected function humanWait(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        if ($m < 60) {
            return $m . 'm' . ($s ? " {$s}s" : '');
        }
        return intdiv($m, 60) . 'h ' . ($m % 60) . 'm';
    }
    
    protected function templateRequirements(string $name): array
    {
        $name = trim(preg_replace('/\b(form|document|the)\b/i', '', $name));
        $name = trim($name);
        if ($name === '') {
            return ['error' => 'No template name given. Ask the user which document they mean.'];
        }

        $templates = \App\Models\Template::query()
            ->where('status', 'active')
            ->whereNotNull('current_version_id')
            ->where('name', 'like', '%' . $name . '%')
            ->with([
                'currentVersion.fields' => fn ($q) => $q->orderBy('y_position')->orderBy('x_position'),
                'currentVersion.instructions' => fn ($q) => $q->orderBy('step_number'),
            ])
            ->limit(3)
            ->get();

        if ($templates->isEmpty()) {
            return ['note' => "No active document matches \"{$name}\". Call list_document_templates to show valid names."];
        }

        return $templates->map(function ($t) {
            $fields = $t->currentVersion?->fields ?? collect();

            $typeWords = ['text', 'date', 'paragraph', 'number', 'input', 'textarea', 'field'];

            // A usable name is real text that is not just a type word
            $usable = function ($value) use ($typeWords) {
                $value = trim((string) $value);
                if ($value === '' || in_array(Str::lower($value), $typeWords, true)) {
                    return null;
                }
                return $value;
            };

            $pretty = function (string $value) {
                // Only prettify snake_case / slug values; leave normal text alone
                return preg_match('/^[a-z0-9_\-]+$/', $value) ? Str::headline($value) : $value;
            };

            $displayName = function ($f) use ($usable, $pretty) {
                // Real field name first, group name last
                foreach ([$f->label ?? null, $f->name ?? null, $f->placeholder ?? null] as $candidate) {
                    if ($value = $usable($candidate)) {
                        return $pretty(Str::limit($value, 60));
                    }
                }
                // Fall back to the group only if nothing else exists
                if ($group = $usable($f->group_name ?? null)) {
                    return $pretty($group);
                }

                return 'Field ' . $f->field_id;
            };

            $isStudentInput = fn ($f) => ($f->source_type ?? 'input') === 'input'
                && ! (($f->field_type === 'paragraph' ? 'paragraph' : $f->data_type) === 'date'
                    && ($f->date_mode ?? 'current') === 'current');

            $toFill = $fields->filter($isStudentInput)->values();

            return [
                'document' => $t->name,
                'fields_to_fill_in' => $toFill->map(function ($f, $i) use ($displayName, $usable) {
                    $name  = $displayName($f);
                    $group = $usable($f->group_name ?? null);

                    // Show the group as context, e.g. "Student Name (First Copy)"
                    $suffix = ($group && Str::lower($group) !== Str::lower($name)) ? " ({$group})" : '';

                    return ($i + 1) . '. ' . $name . $suffix . ($f->required ? ' (required)' : ' (optional)');
                })->all(),
                'auto_filled_by_system' => $fields->reject($isStudentInput)->map(function ($f) use ($displayName, $usable) {
                    $key = $usable($f->system_key ?? null);
                    if ($key) {
                        return Str::headline($key);
                    }
                    return ($f->data_type ?? '') === 'date' ? 'Current Date' : $displayName($f);
                })->unique()->values()->all(),
                // Renumbered 1..n so duplicate step numbers in the database don't repeat
                'instructions' => ($t->currentVersion?->instructions ?? collect())
                    ->values()
                    ->map(fn ($i, $idx) => ($idx + 1) . '. ' . $i->description)
                    ->all(),
            ];
        })->values()->all();
    }
    protected function cleanReply(string $text): string
    {
        $text = preg_replace('/suggest_quick_replies\s*[\[\{(].*$/s', '', $text);
        $text = preg_replace('/<function=.*?(<\/function>|$)/s', '', $text);
        $text = trim($text);

        return $text !== '' ? $text : "Sorry, I didn't catch that. Could you rephrase?";
    }

    protected function callGroq(array $messages, bool $withTools = true)
    {
        $payload = [
            'model' => config('services.groq.model'),
            'messages' => $messages,
            'temperature' => 0.4,
            'max_tokens' => 800,
        ];

        if ($withTools) {
            $payload['tools'] = $this->tools();
            $payload['tool_choice'] = 'auto';
        }

        return Http::withToken(config('services.groq.api_key'))
            ->timeout(20)
            ->post('https://api.groq.com/openai/v1/chat/completions', $payload);
    }

    protected function reply(string $text, array $quickReplies = []): array
    {
        return ['reply' => $text, 'quick_replies' => $quickReplies];
    }


    protected function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_document_workspaces',
                    'description' => 'Get the logged-in student\'s document transaction requests: status, template used, and whether an appointment is attached.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_appointments',
                    'description' => 'Get the logged-in student\'s appointments: status, date, session, queue number, and reschedule/reapplication history if any.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_document_templates',
                    'description' => 'List document types available to request in New Transaction.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_clearance_status',
                    'description' => 'Get the logged-in student\'s clearance tagging status per organization/office for the current semester.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_appointment_availability',
                    'description' => 'Check open appointment slots (morning/afternoon) for a given date, or the next several days if no date given.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD. Omit to check the next 5 days.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_template_requirements',
                    'description' => 'Get the form fields a student must fill in to complete a document request (New Transaction), plus fields auto-filled by the system and the step-by-step instructions. Pass the document name (partial names work).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'template_name' => ['type' => 'string', 'description' => 'Document name, e.g. "Admission Slip".'],
                        ],
                        'required' => ['template_name'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'suggest_quick_replies',
                    'description' => 'Offer the user 2-5 short follow-up options they can tap instead of typing. Use this whenever you would otherwise list choices in prose.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'options' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Short button labels, e.g. ["Check my appointment", "New transaction"]',
                            ],
                        ],
                        'required' => ['options'],
                    ],
                ],
            ],
        ];
    }

    protected function executeTool(string $name, array $args, ?int $userId): array
    {
        if (! $userId) {
            return ['error' => 'No authenticated user. Do not attempt this tool again this turn.'];
        }

        return match ($name) {
            'get_my_document_workspaces' => \App\Models\StudentDocumentWorkspace::query()
                ->where('user_id', $userId)
                ->with('template:template_id,name')
                ->latest('workspace_id')
                ->limit(10)
                ->get()
                ->map(fn ($w) => [
                    'workspace_id' => $w->workspace_id,
                    'document' => $w->template?->name,
                    'status' => $w->status,
                    'processing' => (bool) $w->processing,
                    'processing_stage' => $w->processing_stage,
                    'analysis_status' => $w->analysis_status,
                    'has_attended_appointment' => $w->hasAttendedAppointment(),
                    'completed_at' => $w->completed_at?->diffForHumans(),
                ])
                ->toArray(),

            'get_my_appointments' => \App\Models\Appointment::query()
                ->where('user_id', $userId)
                ->with('workspace.template:template_id,name')
                ->latest('appointment_id')
                ->limit(10)
                ->get()
                ->map(fn (\App\Models\Appointment $a) => [
                    'appointment_id' => $a->appointment_id,
                    'for_document' => $a->workspace?->template?->name,
                    'purpose' => $a->purpose,
                    'date' => $a->appointment_date?->toDateString(),
                    'session' => $a->sessionLabel(),
                    'status' => $a->status,
                    'queue_number' => $a->queue_number,
                    'can_reapply' => $a->canReapply(),
                    'reschedule_count' => $a->reschedule_count,
                ])
                ->toArray(),

            'list_document_templates' => (function () {
                $rows = \App\Models\Template::query()
                    ->where('status', 'active')
                    ->whereNotNull('current_version_id')
                    ->with('currentVersion:version_id,template_id,document_size')
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($t) => [
                        'name' => $t->name,
                        'document_size' => $t->currentVersion?->document_size,
                    ])
                    ->values()
                    ->toArray();

                return $rows ?: ['note' => 'No templates are currently available in New Transaction.'];
            })(),

            'get_my_clearance_status' => \App\Models\ClearanceStatus::query()
                ->where('user_id', $userId)
                ->whereHas('semesterPeriod', fn ($q) => $q->where('is_current', true))
                ->get(['organization', 'status', 'remarks', 'tagged_at'])
                ->toArray(),

            'get_appointment_availability' => $this->availabilityFor($args['date'] ?? null),
            'get_template_requirements' => $this->templateRequirements($args['template_name'] ?? ''),

            default => ['error' => "Unknown tool: {$name}"],
        };
    }

    protected function processGuide(string $query, ?\App\Models\User $user): string
    {
        $q = Str::lower($query);
        $role = Str::lower($user->role->role_name ?? 'student');
        $isStaff = in_array($role, ['officer', 'student officer', 'admin'], true);

        $overview = <<<TXT
    DOCUMATE OVERVIEW (use only these facts for "how do I..." questions):
    Main tabs: New Transaction, Documents, Appointments, Notifications, Clearance Status, Handbook.
    TXT;

        $transaction = <<<TXT
    HOW TO COMPLETE A DOCUMENT TRANSACTION (in order):
    1. Open the New Transaction tab and select a document.
    2. Fill in the form inputs (text fields) for that document. Some fields (name, student number, program, current date) are filled in automatically.
    3. Save to workspace. The document appears in the Documents tab under In Progress (pending).
    4. Set an appointment, either inside the document page (Set Appointment button) or from the Appointments tab.
    5. When the appointment is approved, a notification with the appointment date appears in the Notifications tab.
    6. Print the document using the Save PDF button (New Transaction page). You can also View, Export or Download the PDF from the document page.
    7. Get the document signed by all required signatories BEFORE your appointment date.
    8. On the appointment date, bring the signed document to the VPSD office. The Vice President signs the accomplished documents that already have the signatories.
    9. The VPSD secretary marks the appointment as attended once it is done with no errors. The document then moves to the Completed tab.
    10. The secretary uploads a photo of the final document as the digital copy. An AI reads the text and checks that the photo matches the student's document. If the photo is not accepted, the secretary re-uploads it.
    Other document actions: Edit Document (date fields set to "today" update to the current date when re-saved) and Delete (cannot be undone).
    Timeline: each document page shows an Activity Timeline (created, PDF generated, appointment status, photo uploaded, AI review, completed).
    TXT;

        $appointment = <<<TXT
    HOW TO SET AN APPOINTMENT (4 steps):
    You can book from the Appointments tab (Book Appointment button) or from inside a document (Set Appointment).
    1. Purpose: pick a purpose from the list. If none fits, choose "Others" and type your purpose (at least 5 characters).
    2. Document: choose one of your pending documents, or continue without one. Only documents with no active appointment are listed. This step is SKIPPED when you book from inside a document.
    3. Date and session: use the calendar. Green-dot dates have open slots. Choose Morning (8:00 AM - 12:00 PM) or Afternoon (1:00 PM - 5:00 PM). Sundays are closed, past dates are unavailable, and each session has limited slots (25 by default unless the office changed it). Same-day booking closes at 11:30 AM for morning and 4:30 PM for afternoon.
    4. Confirm: review the details and press Confirm Appointment.
    AFTER BOOKING:
    - The status starts as Pending. An AI reviews the appointment and document, and the office sorts requests into "For Rejection" or "Ready for Approval". A staff member makes the final decision.
    - You get a notification when it is approved or rejected. Approved appointments receive a queue number.
    - The office can also reschedule an appointment. It returns to Pending, and you are notified.
    - On the day of the appointment, open the appointment from the Appointments tab to see the live queue: your number, the number now being served, and whether it is your turn. Queue tracking opens on the appointment date, within your session hours.
    - You can retract a pending or approved appointment from the Appointments list. This cancels your queue position.
    - If you missed it, it is marked Missed. Open it and choose Reapply Appointment. Your purpose and document stay the same, so step 2 is skipped. You only pick a new date and session, then it goes back to Pending review.
    - Appointment statuses: pending, approved, rejected, attended, missed, retracted. Past ones appear in the Past History tab.
    TXT;

        $clearance = $isStaff ? <<<TXT
    HOW TO TAG CLEARANCE (officers and admins):
    1. Open the clearance monitoring tab to see the list of students and their clearance status. Officers only see students of their own organization. Admins see all students.
    2. Click a student to open their profile, their clearance records and the tagging history per clearance.
    3. In the tagging card, choose the semester and school year, then mark the status as Pending, Cleared or Uncleared. Remarks are optional.
    TXT : <<<TXT
    CLEARANCE (students): use the Clearance Status tab to view your clearance status per organization or office. Tagging is done by officers and admins, so if a status looks wrong, contact the office or organization that tagged it.
    TXT;

        $parts = [$overview];

        $wants = fn (array $words) => collect($words)->contains(fn ($w) => str_contains($q, $w));

        $matchedAny = false;

        if ($wants(['transaction', 'document', 'workspace', 'pdf', 'print', 'sign', 'complete', 'process', 'step', 'how do i', 'how to', 'request', 'start', 'photo', 'upload', 'secretary', 'vpsd'])) {
            $parts[] = $transaction;
            $matchedAny = true;
        }
        if ($wants(['appointment', 'appoint', 'book', 'schedule', 'queue', 'reschedule', 'reapply', 'missed', 'slot', 'session', 'retract', 'transaction', 'process'])) {
            $parts[] = $appointment;
            $matchedAny = true;
        }
        if ($wants(['clearance', 'tag', 'cleared', 'uncleared'])) {
            $parts[] = $clearance;
            $matchedAny = true;
        }

        if (! $matchedAny) {
            $parts[] = "No process guide matched this message. For questions about how the system works, answer only from the facts above or use the tools.";
        }

        return implode("\n\n", $parts);
    }

    protected function availabilityFor(?string $date): array
    {
        $dates = $date
            ? [\Carbon\Carbon::parse($date)]
            : collect(range(0, 4))->map(fn ($i) => now()->addDays($i));

        return collect($dates)->map(function ($d) {
            $row = \App\Models\OfficeAvailability::where('date', $d->toDateString())->first();

            return [
                'date' => $d->toDateString(),
                'morning_open' => $row?->morning_open ?? false,
                'morning_slots' => $row?->morning_slots,
                'afternoon_open' => $row?->afternoon_open ?? false,
                'afternoon_slots' => $row?->afternoon_slots,
                'note' => $row?->note,
            ];
        })->toArray();
    }
}