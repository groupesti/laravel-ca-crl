<?php

declare(strict_types=1);

namespace CA\Crl\Facades;

use CA\Crl\Contracts\CrlManagerInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \CA\Crl\Models\Crl generate(\CA\Models\CertificateAuthority $ca)
 * @method static \CA\Crl\Models\Crl generateDelta(\CA\Models\CertificateAuthority $ca, \CA\Crl\Models\Crl $baseCrl)
 * @method static \CA\Crl\Models\Crl|null getCurrent(\CA\Models\CertificateAuthority $ca)
 * @method static bool isRevoked(\CA\Models\CertificateAuthority $ca, string $serial)
 * @method static void publish(\CA\Crl\Models\Crl $crl)
 * @method static \Illuminate\Support\Collection getAll(\CA\Models\CertificateAuthority $ca)
 * @method static \CA\Crl\Models\Crl|null findByUuid(string $uuid)
 *
 * @see \CA\Crl\Services\CrlManager
 */
class CaCrl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CrlManagerInterface::class;
    }
}
