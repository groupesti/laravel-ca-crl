<?php

declare(strict_types=1);

namespace CA\Crl\Http\Controllers;

use CA\Crl\Contracts\CrlDistributionInterface;
use CA\Crl\Contracts\CrlManagerInterface;
use CA\Crl\Http\Resources\CrlResource;
use CA\Crl\Models\Crl;
use CA\Models\CertificateAuthority;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class CrlController extends Controller
{
    public function __construct(
        private readonly CrlManagerInterface $crlManager,
        private readonly CrlDistributionInterface $distributor,
    ) {}

    /**
     * List CRLs for a CA.
     */
    public function index(string $caId): JsonResponse
    {
        $ca = CertificateAuthority::findOrFail($caId);
        $crls = $this->crlManager->getAll($ca);

        return CrlResource::collection($crls)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Generate a new CRL for a CA.
     */
    public function generate(Request $request, string $caId): JsonResponse
    {
        $ca = CertificateAuthority::findOrFail($caId);
        $isDelta = $request->boolean('delta', false);

        if ($isDelta) {
            $baseCrl = $this->crlManager->getCurrent($ca);

            if ($baseCrl === null) {
                return response()->json([
                    'error' => 'No current full CRL exists to base a delta CRL on.',
                ], 422);
            }

            $crl = $this->crlManager->generateDelta($ca, $baseCrl);
        } else {
            $crl = $this->crlManager->generate($ca);
        }

        return (new CrlResource($crl))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Download the current CRL in DER format.
     */
    public function current(string $caId): Response
    {
        $ca = CertificateAuthority::findOrFail($caId);
        $derBytes = $this->distributor->serve($ca);

        return new Response($derBytes, 200, [
            'Content-Type' => 'application/pkix-crl',
            'Content-Disposition' => "attachment; filename=\"{$caId}.crl\"",
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Download the current CRL in PEM format.
     */
    public function currentPem(string $caId): Response
    {
        $ca = CertificateAuthority::findOrFail($caId);
        $crl = $this->crlManager->getCurrent($ca);

        if ($crl === null) {
            abort(404, 'No current CRL available.');
        }

        return new Response($crl->crl_pem, 200, [
            'Content-Type' => 'application/x-pem-file',
            'Content-Disposition' => "attachment; filename=\"{$caId}.pem\"",
            'Cache-Control' => 'no-cache',
        ]);
    }
}
