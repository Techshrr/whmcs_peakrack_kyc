<?php

namespace PeakRack\Kyc\Providers;

class BankCardProvider extends ReservedProvider
{
    public function getName(): string
    {
        return 'bank_card_multi_factor';
    }
}
