<?php

namespace App\Livewire\Student;

use Livewire\Component;

class StudentHandbook extends Component
{
    public $search = '';
    public $activeSection = 'overview';

    public function render()
    {
        return view('livewire.student.student-handbook', [
            'handbookJson' => file_get_contents(resource_path('data/lnu-handbook.json')),
        ])->layout('layouts.app', ['title' => 'Student Handbook']);
    }
}