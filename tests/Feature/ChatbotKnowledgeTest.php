<?php

namespace Tests\Feature;

use App\Livewire\Chatbot\ChatWidget;
use App\Models\Role;
use App\Models\User;
use App\Livewire\Admin\Dashboard;
use App\Services\ChatbotService;
use App\Support\MarkdownLite;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_legacy_welcome_message_is_refreshed_in_the_chat_session(): void
    {
        $user = new User;
        $user->setAttribute('id', 11);
        $user->setAttribute('account_status', 'active');
        $this->actingAs($user);
        session()->put('chatbot.messages', [[
            'role' => 'assistant',
            'content' => 'Hello. How can I assist you with Leyte Normal University or the DocuMate system today?',
        ]]);

        Livewire::test(ChatWidget::class)
            ->assertSee(ChatWidget::GREETING)
            ->assertDontSee('Hello. How can I assist you with Leyte Normal University or the DocuMate system today?');

        $this->assertSame(
            ChatWidget::GREETING,
            session('chatbot.messages.0.content'),
        );
    }

    public function test_selecting_a_preset_question_loads_contextual_follow_ups_after_the_reply(): void
    {
        $user = new User;
        $user->setAttribute('id', 12);
        $user->setAttribute('account_status', 'active');
        $this->actingAs($user);

        $chatbot = Mockery::mock(ChatbotService::class);
        $chatbot->shouldReceive('ask')->once()->andReturn([
            'reply' => 'The Registrar\'s Office handles transcript and enrollment records.',
            'quick_replies' => [],
        ]);
        $this->app->instance(ChatbotService::class, $chatbot);

        Livewire::test(ChatWidget::class)
            ->call('selectQuickReply', 'Who handles transcript requests?')
            ->call('getReply')
            ->assertSee('The Registrar\'s Office handles transcript and enrollment records.')
            ->assertSee('Find the Registrar\'s Office')
            ->assertSet('quickReplies', [
                'Find the Registrar\'s Office',
                'Find the Cashier\'s Office',
                'Who handles scholarships?',
            ]);
    }

    public function test_structured_answers_do_not_get_a_generic_automatic_follow_up(): void
    {
        $user = new User;
        $user->setAttribute('id', 13);
        $user->setAttribute('account_status', 'active');
        $this->actingAs($user);

        $chatbot = Mockery::mock(ChatbotService::class);
        $chatbot->shouldReceive('ask')->once()->andReturn([
            'reply' => "### Student ID replacement\n\nID printing is handled by BIGO.\n\n1. Get authorization.\n2. Pay at the Cashier.",
            'quick_replies' => [],
        ]);
        $this->app->instance(ChatbotService::class, $chatbot);

        Livewire::test(ChatWidget::class)
            ->set('input', 'How do I replace my student ID?')
            ->call('sendMessage')
            ->call('getReply')
            ->assertSee('Student ID replacement')
            ->assertDontSee('Would you like any other details about this office?');
    }

    public function test_chat_markdown_links_allow_internal_routes_but_not_script_urls(): void
    {
        $html = MarkdownLite::toHtml('Open [Appointments](/student/appointments) or [unsafe](javascript:alert(1)).');

        $this->assertStringContainsString('<a href="/student/appointments" wire:navigate>Appointments</a>', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringContainsString('unsafe', $html);

        $formatted = MarkdownLite::toHtml("## Next steps\n\n1. Open **Appointments**.\n2. Choose a date.\n\n> Note: You must be signed in.\n\nType `<script>alert(1)</script>` only as an example.");
        $this->assertStringContainsString('<h3 class="chatbot-heading">Next steps</h3>', $formatted);
        $this->assertStringContainsString('<ol class="chatbot-list chatbot-list-numbered"><li>Open <strong>Appointments</strong>.</li><li>Choose a date.</li></ol>', $formatted);
        $this->assertStringContainsString('<blockquote class="chatbot-callout">Note: You must be signed in.</blockquote>', $formatted);
        $this->assertStringNotContainsString('<script>', $formatted);

        $inlineSteps = MarkdownLite::toHtml('To book an appointment: 1. Open Appointments. 2. Choose an available date. 3. Confirm the booking.');
        $this->assertStringContainsString('<ol class="chatbot-list chatbot-list-numbered"><li>Open Appointments.</li><li>Choose an available date.</li><li>Confirm the booking.</li></ol>', $inlineSteps);

        $stepLabels = MarkdownLite::toHtml('Step 1: Open Appointments. Step 2: Choose a date.');
        $this->assertStringContainsString('<ol class="chatbot-list chatbot-list-numbered"><li>Open Appointments.</li><li>Choose a date.</li></ol>', $stepLabels);

        $facts = MarkdownLite::toHtml("Office: BIGO\nService: ID reprinting");
        $this->assertStringContainsString('<ul class="chatbot-list"><li><strong>Office:</strong> BIGO</li><li><strong>Service:</strong> ID reprinting</li></ul>', $facts);

        $longAnswer = MarkdownLite::toHtml('Students can check a request in Documents after signing in. The request page shows the current status and timeline for the transaction. If VPSD asks for a correction, open the request, read the note, edit the form, and resubmit it. The office reviews the updated information before moving the request forward.');
        $this->assertStringContainsString('<p>Students can check a request in Documents after signing in.</p>', $longAnswer);
        $this->assertStringContainsString('<ul class="chatbot-list">', $longAnswer);
    }

    public function test_faq_function_returns_relevant_local_answer_to_the_model(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()
            ->push(['choices' => [['message' => [
                'tool_calls' => [[
                    'id' => 'faq-call',
                    'type' => 'function',
                    'function' => [
                        'name' => 'search_faqs',
                        'arguments' => json_encode(['query' => 'forgot password']),
                    ],
                ]],
            ]]]])
            ->push(['choices' => [['message' => ['content' => 'Use the password reset option on the sign-in page.']]]]);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'I forgot my password. What should I do?'],
        ]);

        $this->assertStringContainsString('Open the [password reset page](/forgot-password)', $result['reply']);
        $this->assertStringContainsString('contact a DocuMate administrator', $result['reply']);
        $this->assertStringContainsString("\n- Open the [password reset page]", $result['reply']);
        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'search_faqs');
    }

    public function test_student_registration_question_returns_the_account_requirements_faq(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()->push(['choices' => [['message' => [
            'tool_calls' => [[
                'id' => 'account-faq-call',
                'type' => 'function',
                'function' => [
                    'name' => 'search_faqs',
                    'arguments' => json_encode(['query' => 'How do I create a student account?']),
                ],
            ]],
        ]]]]);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'How do I create a student account?'],
        ]);

        $this->assertStringContainsString('Complete the Personal and Academic sections', $result['reply']);
        $this->assertStringContainsString('enrollment slip (up to 5 MB)', $result['reply']);
        $this->assertStringContainsString('at least 15 years old', $result['reply']);
        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'search_faqs');
    }

    public function test_student_id_renewal_question_returns_the_local_id_services_faq(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()->push(['choices' => [['message' => [
            'tool_calls' => [[
                'id' => 'id-faq-call',
                'type' => 'function',
                'function' => [
                    'name' => 'search_faqs',
                    'arguments' => json_encode(['query' => 'Who is in charge for ID renewal?']),
                ],
            ]],
        ]]]]);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'Who is in charge for ID renewal?'],
        ]);

        $this->assertStringContainsString('Business and Income Generation Office (BIGO)', $result['reply']);
        $this->assertStringContainsString('certification or authorization from OSS or VPSD', $result['reply']);
        $this->assertStringContainsString('lists PHP 200', $result['reply']);
        $this->assertStringContainsString('### Who handles student ID renewal, replacement, or reprinting?', $result['reply']);
        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'search_faqs');
    }

    public function test_admin_dashboard_question_uses_the_dashboard_summary_tool(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        $dashboard = Mockery::mock(Dashboard::class);
        $dashboard->shouldReceive('chatbotSummary')->once()->andReturn([
            'as_of' => '2026-10-30 12:00:00',
            'headline' => ['requests_overdue_7_school_days' => 2],
        ]);
        $this->app->instance(Dashboard::class, $dashboard);

        Http::fakeSequence()
            ->push(['choices' => [['message' => [
                'tool_calls' => [[
                    'id' => 'admin-dashboard-call',
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_admin_dashboard_summary',
                        'arguments' => '{}',
                    ],
                ]],
            ]]]])
            ->push(['choices' => [['message' => ['content' => 'There are 2 overdue requests.']]]]);

        $user = new User;
        $role = new Role;
        $role->setAttribute('role_name', 'Admin');
        $user->setRelation('role', $role);
        $user->setAttribute('id', 5);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'How many workflow requests are overdue on the dashboard?'],
        ], $user);

        $this->assertSame('There are 2 overdue requests.', $result['reply']);
        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'get_admin_dashboard_summary'
            && collect($request['tools'] ?? [])->contains(fn ($tool) => ($tool['function']['name'] ?? null) === 'get_admin_dashboard_summary'));
        Http::assertSent(fn (Request $request) => collect($request['messages'] ?? [])
            ->contains(fn ($message) => ($message['role'] ?? null) === 'tool'
                && str_contains($message['content'] ?? '', 'requests_overdue_7_school_days')));
    }

    public function test_student_chat_does_not_receive_the_admin_dashboard_tool(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()->push(['choices' => [['message' => ['content' => 'Please contact an administrator for dashboard figures.']]]]);

        $user = new User;
        $role = new Role;
        $role->setAttribute('role_name', 'Student');
        $user->setRelation('role', $role);
        $user->setAttribute('id', 12);

        app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'How many workflow requests are overdue on the dashboard?'],
        ], $user);

        Http::assertSent(fn (Request $request) => ! collect($request['tools'] ?? [])
            ->contains(fn ($tool) => ($tool['function']['name'] ?? null) === 'get_admin_dashboard_summary'));
    }

    public function test_tool_choice_mismatch_retries_with_automatic_tool_selection(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()
            ->push(['error' => ['message' => "Tool call validation failed: attempted to call tool 'search_faqs' which does not match request.tool_choice: 'get_admin_dashboard_summary'."]], 400)
            ->push(['choices' => [['message' => [
                'tool_calls' => [[
                    'id' => 'faq-after-retry',
                    'type' => 'function',
                    'function' => [
                        'name' => 'search_faqs',
                        'arguments' => json_encode(['query' => 'forgot password']),
                    ],
                ]],
            ]]]]);

        $user = new User;
        $role = new Role;
        $role->setAttribute('role_name', 'Admin');
        $user->setRelation('role', $role);
        $user->setAttribute('id', 6);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'What does the dashboard say about password reset?'],
        ], $user);

        $this->assertStringContainsString('password reset page', $result['reply']);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'get_admin_dashboard_summary');
        Http::assertSent(fn (Request $request) => ($request['tool_choice'] ?? null) === 'auto');
    }

    public function test_required_tool_not_called_retries_automatically_for_short_id_query(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()
            ->push(['error' => ['message' => 'Tool choice is required, but model did not call a tool']], 400)
            ->push(['choices' => [['message' => [
                'tool_calls' => [[
                    'id' => 'id-faq-retry',
                    'type' => 'function',
                    'function' => [
                        'name' => 'search_faqs',
                        'arguments' => json_encode(['query' => 'ID renewal']),
                    ],
                ]],
            ]]]]);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'ID RENEWAL'],
        ]);

        $this->assertStringContainsString('Business and Income Generation Office (BIGO)', $result['reply']);
        $this->assertStringContainsString('1. Obtain certification or authorization from OSS or VPSD.', $result['reply']);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'search_faqs');
        Http::assertSent(fn (Request $request) => ($request['tool_choice'] ?? null) === 'auto');
    }

    public function test_office_questions_use_the_lnu_office_directory(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fake();

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'What does the Registrar\'s Office do?'],
        ]);

        $this->assertStringContainsString('Registrar\'s Office', $result['reply']);
        $this->assertStringContainsString('transcript requests', $result['reply']);
        $this->assertStringContainsString('student records', $result['reply']);
        $this->assertStringContainsString('Responsibilities:', $result['reply']);
        $this->assertStringNotContainsString('No LNU office matched', $result['reply']);
        $officeHtml = MarkdownLite::toHtml($result['reply']);
        $this->assertStringContainsString('<h3 class="chatbot-heading">Registrar&#039;s Office</h3>', $officeHtml);
        $this->assertStringContainsString('<ul class="chatbot-list">', $officeHtml);
        Http::assertNothingSent();
    }

    public function test_broad_lnu_office_question_lists_directory_roles_without_calling_ai(): void
    {
        Http::fake();

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'What offices are in LNU and what do they do?'],
        ]);

        $this->assertStringContainsString('Registrar\'s Office', $result['reply']);
        $this->assertStringContainsString('Responsibilities:', $result['reply']);
        $this->assertStringContainsString('Showing the first 10 offices', $result['reply']);
        $this->assertStringContainsString('<h3 class="chatbot-heading">', MarkdownLite::toHtml($result['reply']));
        Http::assertNothingSent();
    }

    public function test_university_hymn_reply_uses_styled_blocks_and_a_source_link(): void
    {
        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'Show me the LNU university hymn lyrics.'],
        ]);

        $html = MarkdownLite::toHtml($result['reply']);

        $this->assertStringContainsString('<div class="chatbot-center">', $html);
        $this->assertStringContainsString('<div class="chatbot-credits">', $html);
        $this->assertStringContainsString('href="https://www.lnu.edu.ph/university-profile/university-hymn/"', $html);
    }

    public function test_combined_faq_answer_uses_only_the_matched_source_entries(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()
            ->push(['choices' => [['message' => [
                'tool_calls' => [[
                    'id' => 'faq-call',
                    'type' => 'function',
                    'function' => [
                        'name' => 'search_faqs',
                        'arguments' => json_encode(['query' => 'What is DocuMate and who can use it?']),
                    ],
                ]],
            ]]]])
            ->push(['choices' => [['message' => ['content' => 'Students can use it. Tap these invented quick actions.']]]]);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'What is DocuMate and who can use it?'],
        ]);

        $this->assertStringContainsString('digital system designed to streamline student transactions', $result['reply']);
        $this->assertStringContainsString('Students, officers, and administrators', $result['reply']);
        $this->assertStringNotContainsString('What can I do in DocuMate?', $result['reply']);
        $this->assertStringNotContainsString('invented quick actions', $result['reply']);
        $this->assertSame([], $result['quick_replies']);
    }

    public function test_procedural_appointment_question_uses_workflow_context_not_page_lookup(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()
            ->push(['choices' => [['message' => ['content' => "Open Appointments, choose a purpose, then select a date and session.\n\n---\n**Quick actions**\n- Book from the Appointments tab\n- View my upcoming appointments\n(These appear as tappable suggestions.)"]]]]);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'How do I book an appointment?'],
        ]);

        $this->assertStringContainsString('Open Appointments', $result['reply']);
        $this->assertStringNotContainsString('Quick actions', $result['reply']);
        $this->assertStringNotContainsString('tappable suggestions', $result['reply']);
        $this->assertSame([], $result['quick_replies']);

        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'] ?? '', 'HOW TO SET AN APPOINTMENT')
            && ! collect($request['tools'] ?? [])->contains(fn ($tool) => ($tool['function']['name'] ?? null) === 'get_navigation_help'));
    }

    public function test_navigation_function_returns_pages_available_to_the_users_role(): void
    {
        config([
            'services.groq.api_key' => 'test-key',
            'services.groq.model' => 'test-model',
        ]);

        Http::fakeSequence()
            ->push(['choices' => [['message' => [
                'tool_calls' => [[
                    'id' => 'navigation-call',
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_navigation_help',
                        'arguments' => json_encode(['task' => 'start a document request']),
                    ],
                ]],
            ]]]])
            ->push(['choices' => [['message' => ['content' => 'Open [New Transaction](/student/new-transaction) to start your request.']]]]);

        $user = new User;
        $role = new Role;
        $role->setAttribute('role_name', 'Student');
        $user->setRelation('role', $role);
        $user->setAttribute('id', 7);

        $result = app(ChatbotService::class)->ask([
            ['role' => 'user', 'content' => 'Where do I find New Transaction to start a document request?'],
        ], $user);

        $this->assertStringContainsString('/student/new-transaction', $result['reply']);
        $this->assertStringContainsString('Start a document request', $result['reply']);
        $this->assertStringContainsString('<a href="/student/new-transaction" wire:navigate>New Transaction</a>', MarkdownLite::toHtml($result['reply']));

        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'get_navigation_help');

        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'] ?? '', '/student/new-transaction')
            && ! str_contains($request['messages'][0]['content'] ?? '', '/admin/users'));
    }
}
