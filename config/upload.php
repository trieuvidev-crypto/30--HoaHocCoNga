<?php

declare(strict_types=1);

return [
    'max_size_mb' => (int) env('UPLOAD_MAX_SIZE_MB', '200'),

    // extension => allowed MIME types. An upload is rejected unless both
    // the extension AND the detected (not client-declared) MIME type
    // match an entry here — this is the core anti-disguised-executable
    // control described in SECURITY_CHECKLIST.md / FILE_STORAGE.md.
    'allowed_types' => [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'svg' => ['image/svg+xml'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'mp3' => ['audio/mpeg'],
        'wav' => ['audio/wav', 'audio/x-wav'],
    ],

    // Never store uploads under the web-servable public/ directory —
    // they are served through a signed/streamed controller route instead
    // (built alongside the Document module).
    'storage_root' => base_path('uploads'),

    'directories' => [
        'avatars' => 'avatars',
        'covers' => 'covers',
        'course_covers' => 'courses',
        'lesson_videos' => 'videos',
        'lesson_attachments' => 'documents',
        'assignment_submissions' => 'assignments',
        'forum_attachments' => 'forum',
        'chat_attachments' => 'chat',
    ],
];
