<?php

namespace App\Enums;

enum FrequencyTypeEnum: string
{
    case OneOff = "One-Off";
    case AdHoc = "Ad-Hoc";
    case Recurring = "Recurring";
}
