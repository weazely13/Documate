<?php
namespace App\Livewire;

use Livewire\Component;

class BulkActionModals extends Component
{
    public array $selectedIds = [];
    
    public bool $showApproveModal = false;
    public string $approvalReason = '';
    public string $customApprovalReason = '';

    public bool $showRescheduleModal = false;
    public string $rescheduleReason = '';
    public string $customRescheduleReason = '';
    public string $newScheduledDate = '';

    public function confirmBulkApproval()
    {
        $finalReason = $this->approvalReason === 'Custom' 
            ? $this->customApprovalReason 
            : $this->approvalReason;

        // Perform bulk update logic using $this->selectedIds and $finalReason
        
        $this->reset(['showApproveModal', 'approvalReason', 'customApprovalReason', 'selectedIds']);
    }

    public function confirmBulkReschedule()
    {
        $finalReason = $this->rescheduleReason === 'Custom' 
            ? $this->customRescheduleReason 
            : $this->rescheduleReason;

        // Perform bulk reschedule logic using $this->selectedIds, $this->newScheduledDate, and $finalReason

        $this->reset(['showRescheduleModal', 'rescheduleReason', 'customRescheduleReason', 'newScheduledDate', 'selectedIds']);
    }

    public function render()
    {
        return view('livewire.bulk-action-modals');
    }
}