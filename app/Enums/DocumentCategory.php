<?php

namespace App\Enums;

/** Groups forms on the student's New transaction page. Shared by paperless forms and paper templates. */
enum DocumentCategory: string
{
    case Permits = 'permits';
    case Consents = 'consents';
    case Clearances = 'clearances';
    case Requests = 'requests';
    case Awards = 'awards';
    case Reports = 'reports';
    case Other = 'other';   // paper templates nobody has categorized yet

    public function label(): string
    {
        return match ($this) {
            self::Permits => 'Permits',
            self::Consents => 'Consents',
            self::Clearances => 'Clearances',
            self::Requests => 'Requests and applications',
            self::Awards => 'Nominations and ratings',
            self::Reports => 'Reports and complaints',
            self::Other => 'Other forms',
        };
    }
}
