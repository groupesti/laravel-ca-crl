<?php

declare(strict_types=1);

namespace CA\Crl\Events;

use CA\Crl\Models\Crl;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CrlGenerated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Crl $crl,
    ) {}
}
