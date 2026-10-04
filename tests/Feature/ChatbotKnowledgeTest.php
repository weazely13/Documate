<?php

namespace Tests\Feature;

use App\Livewire\Chatbot\ChatWidget;
use App\Models\Role;
use App\Models\User;
use App\Services\ChatbotService;
use App\Support\MarkdownLite;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ChatbotKnowledgeTest extends TestCase
{
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

    public function test_chat_markdown_links_allow_internal_routes_but_not_script_urls(): void
    {
        $html = MarkdownLite::toHtml('Open [Appointments](/student/appointments) or [unsafe](javascript:alert(1)).');

        $this->assertStringContainsString('<a href="/student/appointments" wire:navigate>Appointments</a>', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringContainsString('unsafe', $html);
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

        $this->assertStringContainsString('Use the password reset option on the sign-in page.', $result['reply']);
        $this->assertStringContainsString('contact the VPSD office or the system administrators', $result['reply']);
        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'search_faqs');
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
        $this->assertStringContainsString('transcript of records', $result['reply']);
        $this->assertStringContainsString('student records', $result['reply']);
        $this->assertStringContainsString('Locations (multiple directory entries):', $result['reply']);
        $this->assertStringNotContainsString('No LNU office matched', $result['reply']);
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
        Http::assertNothingSent();
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

        Http::assertSent(fn (Request $request) => ($request['tool_choice']['function']['name'] ?? null) === 'get_navigation_help');

        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'] ?? '', '/student/new-transaction')
            && ! str_contains($request['messages'][0]['content'] ?? '', '/admin/users'));
    }
}
