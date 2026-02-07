<?php

namespace Coollabsio\LaravelSaas\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
