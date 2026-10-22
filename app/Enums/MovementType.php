<?php

namespace App\Enums;

enum MovementType: string
{
    case Purchase    = 'purchase';
    case Sale        = 'sale';
    case Adjustment  = 'adjustment';
    case TransferIn  = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Cancel      = 'cancel';
}
