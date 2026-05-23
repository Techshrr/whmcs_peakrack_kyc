<?php

namespace PeakRack\Kyc\Providers;

class OverseasKycProvider extends ReservedProvider
{
    public function getName(): string
    {
        return 'overseas_kyc';
    }
}
