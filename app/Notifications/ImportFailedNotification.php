<?php

namespace App\Notifications;

use App\Models\Upload;
use Illuminate\Notifications\Notification;

class ImportFailedNotification extends Notification
{
    public function __construct(public Upload $upload) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'import_failed',
            'upload_id' => $this->upload->id,
            'filename' => $this->upload->original_filename,
            'failed_rows' => $this->upload->failed_rows,
            'total_rows' => $this->upload->total_rows,
            'message' => "{$this->upload->failed_rows} of {$this->upload->total_rows} products failed to import in \"{$this->upload->original_filename}\".",
        ];
    }
}
