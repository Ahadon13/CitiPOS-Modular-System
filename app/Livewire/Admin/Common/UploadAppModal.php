<?php

namespace App\Livewire\Admin\Common;

use App\Livewire\Concerns\HasToast;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadAppModal extends Component
{
    use HasToast, WithFileUploads;

    public $app_installer;

    public function rules(): array
    {
        return [
            // Max 250MB (256000 KB). Adjust this if your app is larger!
            // Note: You may also need to increase `upload_max_filesize` and `post_max_size` in your server's php.ini
            'app_installer' => 'required|file|max:256000',
        ];
    }

    #[Computed]
    public function currentAppInfo()
    {
        $path = 'app/citipos-app.exe';
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return [
                'exists' => true,
                'size' => round($disk->size($path) / 1048576, 2) . ' MB',
                'last_modified' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($path))->format('M d, Y h:i A'),
            ];
        }

        return ['exists' => false];
    }

    public function save(): void
    {
        $this->validate();

        // Extra security check to ensure they only upload an .exe
        $extension = $this->app_installer->getClientOriginalExtension();
        if (strtolower($extension) !== 'exe') {
            $this->addError('app_installer', 'The file must be a Windows executable (.exe).');
            return;
        }

        try {
            // Ensure the directory exists
            if (!Storage::disk('public')->exists('app')) {
                Storage::disk('public')->makeDirectory('app');
            }

            // storeAs automatically overwrites the existing file if it shares the same name
            $this->app_installer->storeAs('app', 'citipos-app.exe', 'public');

            $this->toastSuccess("Desktop App uploaded and replaced successfully!");
            $this->resetForm();
            $this->dispatch('close-modal', id: 'upload-app');

        } catch (\Exception $e) {
            $this->toastError('Failed to upload app: ' . $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->reset('app_installer');
        $this->resetValidation();
    }
}
