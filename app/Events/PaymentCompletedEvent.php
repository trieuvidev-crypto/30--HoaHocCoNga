<?php

declare(strict_types=1);

namespace App\Events;

use App\Core\Events\Event;

final class PaymentCompletedEvent extends Event
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderUuid,
        public readonly int $userId
    ) {
        parent::__construct();
    }
}
