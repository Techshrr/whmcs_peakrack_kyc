<?php

namespace PeakRack\Kyc\Providers;

class CompanyVerificationProvider extends ReservedProvider
{
    public function getName(): string
    {
        return 'company_verification';
    }
}
