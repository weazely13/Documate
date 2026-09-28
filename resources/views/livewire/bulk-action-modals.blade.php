<div>
    {{-- BULK APPROVE MODAL --}}
    @if($showApproveModal)
        <div class="fixed inset-0 h-screen w-screen z-[9999] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-md transition-opacity">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-100 transform transition-all">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class='bx bx-check-circle text-green-600 text-2xl'></i>
                        Bulk Approve Requests
                    </h3>
                    <button wire:click="$set('showApproveModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-4 space-y-4">
                    <p class="text-sm text-gray-600">
                        You have selected <span class="font-semibold text-gray-900">{{ count($selectedIds) }}</span> items for approval.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Approval Reason / Remarks</label>
                        <select wire:model.live="approvalReason" class="w-full p-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                            <option value="">-- Select Predetermined Reason --</option>
                            <option value="All documents submitted are verified and complete.">All documents submitted are verified and complete.</option>
                            <option value="Approved per department clearance guidelines.">Approved per department clearance guidelines.</option>
                            <option value="Special approval granted by officer/administrator.">Special approval granted by officer/administrator.</option>
                            <option value="Custom">Other / Custom Reason</option>
                        </select>
                    </div>

                    @if($approvalReason === 'Custom')
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Specify Custom Reason</label>
                            <textarea wire:model="customApprovalReason" rows="3" class="w-full p-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Enter reason for approval..."></textarea>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button wire:click="$set('showApproveModal', false)" class="px-4 py-2 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button wire:click="confirmBulkApproval" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-semibold shadow-md transition">
                        Confirm Approval
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- BULK RESCHEDULE MODAL --}}
    @if($showRescheduleModal)
        <div class="fixed inset-0 h-screen w-screen z-[9999] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-md transition-opacity">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-100 transform transition-all">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class='bx bx-calendar-edit text-blue-600 text-2xl'></i>
                        Bulk Reschedule Appointments
                    </h3>
                    <button wire:click="$set('showRescheduleModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-4 space-y-4">
                    <p class="text-sm text-gray-600">
                        Rescheduling <span class="font-semibold text-gray-900">{{ count($selectedIds) }}</span> selected appointments.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">New Date & Time</label>
                        <input type="datetime-local" wire:model="newScheduledDate" class="w-full p-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Reason for Rescheduling</label>
                        <select wire:model.live="rescheduleReason" class="w-full p-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                            <option value="">-- Select Predetermined Reason --</option>
                            <option value="Official event conflict / Holiday closure.">Official event conflict / Holiday closure.</option>
                            <option value="Officer unavailable during original schedule.">Officer unavailable during original schedule.</option>
                            <option value="System maintenance / Office queue adjustment.">System maintenance / Office queue adjustment.</option>
                            <option value="Custom">Other / Custom Reason</option>
                        </select>
                    </div>

                    @if($rescheduleReason === 'Custom')
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Specify Custom Reason</label>
                            <textarea wire:model="customRescheduleReason" rows="3" class="w-full p-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Enter reason for rescheduling..."></textarea>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button wire:click="$set('showRescheduleModal', false)" class="px-4 py-2 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button wire:click="confirmBulkReschedule" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-md transition">
                        Confirm Reschedule
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>