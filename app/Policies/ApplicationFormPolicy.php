<?php

namespace App\Policies;

use App\Models\ApplicationForm;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApplicationFormPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view lists, but filtered by controller
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ApplicationForm $applicationForm): bool
    {
        return $applicationForm->canView($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only agents can create application forms
        return $user->type === 'agent';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ApplicationForm $applicationForm): bool
    {
        return $applicationForm->isEditableBy($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ApplicationForm $applicationForm): bool
    {
        // Only admin can delete application forms
        return $user->type === 'admin';
    }

    /**
     * Determine whether the user can confirm the application form.
     */
    public function confirm(User $user, ApplicationForm $applicationForm): bool
    {
        // Only the agent who created it can confirm
        return $user->type === 'agent' && $user->id === $applicationForm->agent_id;
    }

    /**
     * Determine whether the user can update status of the application form.
     */
    public function updateStatus(User $user, ApplicationForm $applicationForm): bool
    {
        // Only admin can update status
        return $user->type === 'admin';
    }

    /**
     * Determine whether the user can upload documents to the application form.
     */
    public function uploadDocument(User $user, ApplicationForm $applicationForm): bool
    {
        // Users who can view the form can upload documents
        return $applicationForm->canView($user);
    }

    /**
     * Determine whether the user can delete documents from the application form.
     */
    public function deleteDocument(User $user, ApplicationForm $applicationForm): bool
    {
        // Admin can delete any document, others can only delete their own uploads
        if ($user->type === 'admin') {
            return true;
        }

        // Check if user uploaded any documents for this form
        return $applicationForm->documents()->where('uploaded_by', $user->id)->exists();
    }
}
