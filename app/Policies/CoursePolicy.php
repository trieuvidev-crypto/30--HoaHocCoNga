<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Policy;

/**
 * Answers "can this user manage this specific course" — teachers may
 * only manage courses where they are the primary teacher or a listed
 * co-teacher/assistant; admins may always manage any course.
 */
final class CoursePolicy extends Policy
{
    public function canManage(int $userId, array $course, array $assistantTeacherIds = []): bool
    {
        if ($this->isAdmin($userId)) {
            return true;
        }

        if ((int) $course['primary_teacher_id'] === $userId) {
            return true;
        }

        return in_array($userId, $assistantTeacherIds, true);
    }

    public function canPublish(int $userId, array $course): bool
    {
        // Publishing is more sensitive than general editing: co-teachers/
        // assistants may edit content but only the primary teacher or an
        // admin may flip a course to `published`.
        if ($this->isAdmin($userId)) {
            return true;
        }

        return (int) $course['primary_teacher_id'] === $userId;
    }
}
