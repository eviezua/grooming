<?php

namespace App\Enum;

enum Status: string
{
    case Awaiting = 'Awaiting';

    case Approved = 'Approved';

    case Rejected = 'Rejected';

    case Inactive = 'Inactive';
}
