<?php

namespace PeakRack\Kyc\Providers;

class AlipayFaceProvider extends ReservedProvider
{
    public function getName(): string
    {
        return 'alipay_face';
    }
}
