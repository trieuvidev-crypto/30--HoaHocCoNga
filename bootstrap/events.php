<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Events\EventDispatcher;
use App\Events\LessonCreatedEvent;
use App\Events\PaymentCompletedEvent;
use App\Events\UserRegisteredEvent;
use App\Listeners\GrantCourseAccessListener;
use App\Listeners\LogUserRegisteredListener;
use App\Listeners\NotifyEnrolledStudentsListener;

/** @var Container $container */

/** @var EventDispatcher $dispatcher */
$dispatcher = $container->make(EventDispatcher::class);

$dispatcher->listen(UserRegisteredEvent::class, LogUserRegisteredListener::class);
$dispatcher->listen(LessonCreatedEvent::class, NotifyEnrolledStudentsListener::class);
$dispatcher->listen(PaymentCompletedEvent::class, GrantCourseAccessListener::class);

// Future registrations land here as each module ships, e.g.:
// $dispatcher->listen(QuizCompletedEvent::class, AwardXpListener::class);
