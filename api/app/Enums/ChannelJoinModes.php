<?php

namespace App\Enums;

enum ChannelJoinModes: string
{
    case Open = 'open';                         // anyone can join with approved status by default

    case Closed = 'closed';                     // no one can join

    case ApprovalNeeded = 'approval_needed';    // joining sends a request waiting for a manager to approve
}
