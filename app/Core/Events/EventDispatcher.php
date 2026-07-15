<?php

declare(strict_types=1);

namespace App\Core\Events;

use App\Core\Container;
use App\Core\Logger;
use Throwable;

/**
 * Central event dispatcher. Services fire domain events after a business
 * action completes (e.g. after AuthService::register commits its
 * transaction); listeners registered here react to them — sending
 * notifications, awarding XP, updating search index, notifying the
 * Node.js realtime bridge, etc. This is what lets Gamification/
 * Notification/Search stay decoupled from Course/Auth/Payment services,
 * per MODULE_SYSTEM.md's "modules communicate through contracts" rule.
 *
 * Dispatch is synchronous in-process for now (no queue backend on the
 * cPanel target) — a listener that must not block the request (e.g.
 * email sending) should hand off to Jobs/ once that layer exists,
 * not block here.
 *
 * A single listener throwing must never break the triggering business
 * transaction that already committed, so exceptions are caught, logged,
 * and do not propagate.
 */
final class EventDispatcher
{
    private static ?EventDispatcher $instance = null;

    /** @var array<class-string, array<int, class-string<ListenerInterface>>> */
    private array $listeners = [];

    private function __construct(private readonly Container $container, private readonly Logger $logger)
    {
    }

    public static function getInstance(Container $container, Logger $logger): self
    {
        return self::$instance ??= new self($container, $logger);
    }

    /**
     * @param class-string $eventClass
     * @param class-string<ListenerInterface> $listenerClass
     */
    public function listen(string $eventClass, string $listenerClass): void
    {
        $this->listeners[$eventClass][] = $listenerClass;
    }

    public function dispatch(Event $event): void
    {
        $eventClass = $event::class;

        foreach ($this->listeners[$eventClass] ?? [] as $listenerClass) {
            try {
                /** @var ListenerInterface $listener */
                $listener = $this->container->make($listenerClass);
                $listener->handle($event);
            } catch (Throwable $e) {
                $this->logger->log('error', 'events', "Listener {$listenerClass} failed for {$eventClass}: " . $e->getMessage(), [
                    'exception' => $e::class,
                ]);
            }
        }
    }
}
