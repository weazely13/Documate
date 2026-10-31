<?php

namespace App\Support;

use App\Enums\DocumentCategory;
use App\Models\StudentDocumentWorkspace;
use App\Models\Template;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Str;

/**
 * Why a student visits the VPSD office, in plain words rather than form titles. Each purpose names the
 * kinds of documents it is about (paperless forms by registry key, paper templates by category), so the
 * booking page can offer the right documents and the AI check can tell whether the two fit.
 */
class AppointmentPurposes
{
    public const INQUIRY = 'records';
    public const OTHER = 'other';

    /** key => [label, hint, document types, paper-template categories]. */
    private const PURPOSES = [
        'activity' => [
            'Activity or event permit',
            'In-campus or off-campus activities, events and trips',
            ['permit_to_conduct_activity', 'permit_to_conduct_off_campus'],
            ['permits'],
        ],
        'campus_entry' => [
            'Permission to enter the campus',
            'Entry outside your usual schedule or without your ID',
            ['permit_to_enter_campus'],
            [],
        ],
        'absence' => [
            'Absence or admission slip',
            'Excused absences and slips to attend class again',
            ['admission_slip', 'request_for_excused_absence'],
            [],
        ],
        'clearance' => [
            'Clearance',
            'Student, organization officer or SSC clearance',
            ['general_students_clearance', 'clearance_organization_officers', 'clearance_ssc_officers'],
            ['clearances'],
        ],
        'organization' => [
            'Student organization matters',
            'Officers, accreditation, activities and reports of your organization',
            ['clearance_organization_officers', 'clearance_ssc_officers', 'permit_to_conduct_activity', 'permit_to_conduct_off_campus'],
            [],
        ],
        'id' => [
            'Student ID',
            'Renewal, replacement or reprinting',
            ['student_id_renewal'],
            [],
        ],
        'assistantship' => [
            'Student assistantship',
            'Applying, recommendations, evaluations and parent consent',
            ['student_assistantship_application', 'student_assistantship_recommendation', 'student_assistant_evaluation', 'student_assistantship_endorsement', 'parent_consent_assistantship'],
            [],
        ],
        'awards' => [
            'Awards and recognition',
            'Model Student, YMCA award, nominations and ratings',
            ['college_model_nomination', 'college_model_interview_rating', 'model_student_rating', 'ymca_award_nomination', 'ymca_award_rating'],
            ['awards'],
        ],
        'concern' => [
            'Report a concern or lost item',
            'Complaints, grievances, incidents, lost and found',
            ['student_complaint_grievance', 'lost_found_item'],
            ['reports'],
        ],
        'discipline' => [
            'Discipline or referral',
            'A slip, referral or case the office sent you',
            ['disciplinary_action_slip', 'disciplinary_referral_slip', 'disciplinary_case_report'],
            [],
        ],
        'guidance' => [
            'Advice or guidance',
            'Advisement, personal concerns or a talk with the office',
            ['student_advisement_form', 'student_appointment_slip'],
            [],
        ],
        'parent_consent' => [
            'Parent or guardian consent',
            'Permits and consents signed by a parent or guardian',
            ['parent_consent_assistantship'],
            ['consents'],
        ],
        'good_moral' => [
            'Good moral certificate',
            'Requesting a certificate of good moral character',
            [],
            [],
        ],
        'records' => [
            'Records or requirements',
            'Questions about your records, a requirement, or which form you need',
            [],
            ['other', 'requests'],
        ],
    ];

    /** The purposes as options: [key, label, hint, document_types, categories]. */
    public static function options(): array
    {
        return collect(self::PURPOSES)->map(fn ($p, $key) => [
            'key' => $key,
            'label' => $p[0],
            'description' => $p[1],
            'document_types' => array_values(array_filter($p[2], fn ($t) => DocumentRegistry::has($t))),
            'categories' => $p[3],
        ])->values()->all();
    }

    public static function find(?string $key): ?array
    {
        return collect(self::options())->firstWhere('key', $key);
    }

    /** Labels for the AI check: what the office handles. */
    public static function labels(): array
    {
        $forms = collect(DocumentRegistry::all())->reject(fn ($d) => $d->retired())->map(fn ($d) => $d->name())->values();
        $templates = Template::where('status', 'active')->pluck('name')->map(fn ($n) => self::templateName($n));

        return collect(self::options())->pluck('label')->concat($forms)->concat($templates)->unique()->values()->all();
    }

    /** True when the purpose is about one kind of transaction (so only those documents fit). */
    public static function isSpecific(?array $purpose): bool
    {
        return $purpose && ($purpose['document_types'] || $purpose['categories']) && $purpose['key'] !== self::INQUIRY;
    }

    /** True when the document is one this purpose is about. */
    public static function matches(?array $purpose, StudentDocumentWorkspace $ws): bool
    {
        if (! $purpose) {
            return false;
        }
        if ($ws->document_type) {
            return in_array($ws->document_type, $purpose['document_types'], true);
        }

        $category = $ws->template?->category instanceof DocumentCategory ? $ws->template->category->value : ($ws->template?->category ?? 'other');

        return in_array($category, $purpose['categories'], true);
    }

    /** The purpose that fits a document, used when the student starts from the document itself. */
    public static function keyForWorkspace(StudentDocumentWorkspace $ws): ?string
    {
        foreach (self::options() as $p) {
            if ($p['key'] !== self::INQUIRY && self::matches($p, $ws)) {
                return $p['key'];
            }
        }

        return self::INQUIRY;
    }

    /** "PARENTS'/GUARDIAN'S PERMIT" reads as "Parents'/Guardian's Permit". */
    public static function templateName(string $name): string
    {
        return $name === Str::upper($name) ? Str::title(Str::lower($name)) : $name;
    }
}
