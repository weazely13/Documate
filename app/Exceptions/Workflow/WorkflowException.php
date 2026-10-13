<?php

namespace App\Exceptions\Workflow;

use App\Enums\TransactionStatus;
use RuntimeException;

/**
 * Every workflow rule violation throws this. Messages are safe to show to the user, so a
 * Livewire component can do: catch (WorkflowException $e) { $this->addError('x', $e->getMessage()); }
 */
class WorkflowException extends RuntimeException
{   
    public static function externalEmailMissing(): self
    {
        return new self('The rater\'s email address is missing or invalid. Ask the student to correct it.');
    }
    public static function identityCheckRequired(): self
    {
        return new self('Record how you verified the rater (method and confirmation) before sending for signing.');
    }
    public static function reviewerFieldRequired(string $label): self
    {
        return new self("Please fill in: {$label}.");
    }
    public static function signingRequestInvalid(): self
    {
        return new self('This signing link has expired or was already used.');
    }
    public static function invalidTransition(TransactionStatus $from, TransactionStatus $to): self
    {
        return new self("A {$from->label()} transaction cannot move to {$to->label()}.");
    }

    public static function selfApproval(): self
    {
        return new self('You cannot sign a document you submitted yourself.');
    }

    public static function cannotIssueToSelf(): self
    {
        return new self('You cannot issue a document to yourself.');
    }
    public static function notIssuer(): self
    {
        return new self('You are not authorized to issue this document.');
    }
    public static function notWorkflowTransaction(): self
    {
        return new self('This transaction does not use the digital approval workflow.');
    }

    public static function templateNotWorkflowEnabled(): self
    {
        return new self('This document template is not set up for digital approval.');
    }

    public static function notOwner(): self
    {
        return new self('Only the student who owns this transaction can do that.');
    }

    public static function notReviewer(): self
    {
        return new self('You are not authorized to review transactions.');
    }

    public static function notSignatory(): self
    {
        return new self('You do not currently hold the signatory role required for this approval.');
    }

    public static function approvalNotActionable(): self
    {
        return new self('This approval is no longer waiting for action. It may have been handled by someone else.');
    }

    public static function reasonRequired(): self
    {
        return new self('Please provide a reason (at least 5 characters).');
    }

    public static function signingAlreadyStarted(): self
    {
        return new self('Signing has already been started for this transaction.');
    }

    public static function approvalsAlreadyActed(): self
    {
        return new self('Some signatories have already approved, so changes can no longer be requested.');
    }

    public static function approvalsIncomplete(): self
    {
        return new self('All required approvals must be completed first.');
    }

    public static function noHolderForRole(string $roleLabel): self
    {
        return new self("No active signatory is currently assigned to \"{$roleLabel}\". Assign one before sending this for signing.");
    }

    public static function signatureMissing(): self
    {
        return new self('Upload your signature in your profile before approving documents.');
    }

        public static function notOfficeDocument(): self
    {
        return new self('This document type is started by the student. It cannot be issued by the office.');
    }

    public static function studentNotEligible(): self
    {
        return new self('Choose an active student or officer account.');
    }

    public static function studentNameMissing(): self
    {
        return new self('That student has no name on record. Ask them to complete their profile.');
    }

    public static function officeIssuedNotEditable(): self
    {
        return new self('This document was issued by the VPSD office, so the student cannot edit it. Reject it and issue a new one instead.');
    }
    public static function scopeMissing(string $scope): self
    {
        $what = ['organization' => 'organization', 'program' => 'program', 'college' => 'college'][$scope] ?? $scope;

        return new self("The student's account has no {$what}, so the right signatory can't be found. Ask them to update their profile.");
    }
}
