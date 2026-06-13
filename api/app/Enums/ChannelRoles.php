<?php

namespace App\Enums;

enum ChannelRoles: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
}
