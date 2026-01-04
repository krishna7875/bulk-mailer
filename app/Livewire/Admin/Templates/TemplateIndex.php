<?php

namespace App\Livewire\Admin\Templates;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateAttachmentService;
use App\Support\EmailTemplateVariables;
use App\Support\EmailTemplatePreviewData;
use Log;


class TemplateIndex extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['deleteTemplate'];

    // Form fields
    public $templateId;
    public $name;
    public $subject;
    public $body;
    public $status = 'active';
    public $attachment;

    public $showModal = false;

    public $previewSubject;
    public $previewBody;
    public $showPreview = false;

    public $existingAttachment = null;
    public $removeAttachment = false;


    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'status' => 'required|in:active,inactive',
            'attachment' => 'nullable|file|max:10240', // 10MB
        ];
    }

    public function updatedShowModal($value)
    {
        if (!$value) {
            $this->reset(['showPreview', 'previewSubject', 'previewBody']);
            $this->resetValidation();
        }
    }

    public function removeExistingAttachment()
    {
        $this->removeAttachment = true;
        $this->existingAttachment = null;
    }

    public function render()
    {
        $availableVars = EmailTemplateVariables::allowed();
        // $filteredVars = array_values(array_diff($availableVars, ['target_status','shooter_daily_quota', '', 'now', 'app_name']));
        $filteredVars = array_slice($availableVars, 0, 3);


        Log::info($availableVars);

        return view('livewire.admin.templates.template-index', [
            'templates' => EmailTemplate::latest()->paginate(10),
            'allowedVariables' => $filteredVars,
        ]);

    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $template = EmailTemplate::findOrFail($id);

        $this->templateId = $template->id;
        $this->name = $template->name;
        $this->subject = $template->subject;
        $this->body = $template->body;
        $this->status = $template->status;

        if ($template->attachment_path) {
            $this->existingAttachment = [
                'path' => $template->attachment_path,
                'name' => $template->attachment_name,
                'size' => $template->attachment_size,
            ];
        }

        $this->removeAttachment = false;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $usedVariables = array_merge(
            EmailTemplateVariables::extract($this->subject),
            EmailTemplateVariables::extract($this->body)
        );

        $invalidVariables = EmailTemplateVariables::invalid($usedVariables);

        if (!empty($invalidVariables)) {
            $this->dispatch(
                'notify',
                type: 'warning',
                message: 'Unknown variables found: ' . implode(', ', $invalidVariables)
            );
        }

        $template = EmailTemplate::updateOrCreate(
            ['id' => $this->templateId],
            [
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'status' => $this->status,
            ]
        );

        /* Remove attachment if requested */
        if ($this->removeAttachment) {
            app(EmailTemplateAttachmentService::class)
                ->deleteIfExists($template->attachment_path);

            $template->update([
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_mime' => null,
                'attachment_size' => null,
            ]);
        }

        /* Replace attachment if new uploaded */
        if ($this->attachment) {
            $service = app(EmailTemplateAttachmentService::class);

            $service->deleteIfExists($template->attachment_path);

            $template->update(
                $service->handleUpload($this->attachment)
            );
        }

        $this->dispatch('notify', type: 'success', message: 'Template saved successfully.');
        $this->showModal = false;
        $this->resetForm();
    }

    public function deleteTemplate($id)
    {
        $template = EmailTemplate::findOrFail($id);

        app(EmailTemplateAttachmentService::class)
            ->deleteIfExists($template->attachment_path);

        $template->delete();

        $this->dispatch('notify', type: 'success', message: 'Template deleted.');
        $this->resetPage();
    }

    protected function resetForm()
    {
        $this->reset([
            'templateId',
            'name',
            'subject',
            'body',
            'status',
            'attachment',
            'existingAttachment',
            'removeAttachment',
        ]);
        $this->resetValidation();
    }

    public function preview()
    {
        $data = EmailTemplatePreviewData::data();

        $this->previewSubject = $this->resolveVariables($this->subject, $data);
        $this->previewBody    = $this->resolveVariables($this->body, $data);

        $this->showPreview = true;
    }

    /**
     * Resolve {{ variable }} placeholders safely
     */
    private function resolveVariables(string $content, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function ($matches) use ($data) {
                $key = '{{' . $matches[1] . '}}';
                return $data[$key] ?? '';
            },
            $content
        );
    }
}

