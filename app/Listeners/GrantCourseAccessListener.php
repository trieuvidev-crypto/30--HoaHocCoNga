<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Core\Database;
use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\PaymentCompletedEvent;
use App\Repositories\CourseRepository;
use App\Repositories\OrderRepository;
use App\Services\Notification\NotificationService;

/**
 * The single place enrollment access is granted after a successful
 * payment — OrderService/PaymentService never insert into
 * course_enrollments directly, they only dispatch PaymentCompletedEvent
 * and let this listener (and any future ones — e.g. AwardXpListener,
 * GenerateInvoiceListener) react independently, per MODULE_SYSTEM.md.
 */
final class GrantCourseAccessListener implements ListenerInterface
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CourseRepository $courses,
        private readonly Database $db,
        private readonly NotificationService $notifications
    ) {
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof PaymentCompletedEvent) {
            return;
        }

        $items = $this->orders->findItemsByOrder($event->orderId);

        foreach ($items as $item) {
            if ($item['item_type'] !== 'course' || $item['course_id'] === null) {
                continue;
            }

            $courseId = (int) $item['course_id'];

            if ($this->orders->findActiveEnrollment($courseId, $event->userId) !== null) {
                continue; // already enrolled — avoid duplicate enrollment on re-processing
            }

            $this->db->query(
                'INSERT INTO course_enrollments (uuid, course_id, student_id, order_item_id, enrolled_at)
                 VALUES (:uuid, :course_id, :student_id, :order_item_id, NOW())',
                [
                    'uuid' => generate_uuid_v4(),
                    'course_id' => $courseId,
                    'student_id' => $event->userId,
                    'order_item_id' => $item['id'],
                ]
            );

            $this->courses->incrementEnrollmentCount($courseId);

            $course = $this->courses->findById($courseId);

            if ($course !== null) {
                $this->notifications->notify(
                    userId: $event->userId,
                    category: 'order',
                    keyName: 'payment.completed',
                    title: 'Thanh toán thành công!',
                    body: 'Bạn đã được cấp quyền truy cập khóa học "{{course_title}}". Chúc bạn học tốt!',
                    actionUrl: rtrim((string) config('app.url'), '/') . '/dashboard/course/' . $course['uuid'],
                    variables: ['course_title' => $course['title']],
                    priority: 'high'
                );
            }
        }
    }
}
