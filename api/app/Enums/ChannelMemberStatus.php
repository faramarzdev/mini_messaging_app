<?php

namespace App\Enums;

enum ChannelMemberStatus: string
{
    case Pending = 'pending';

    case Approved = 'approved';
    case Rejected = 'rejected';

    case Blocked = 'blocked';

    // use to track and prevent force adding members
    case Invited = 'invited';

    case Left = 'left';
}
