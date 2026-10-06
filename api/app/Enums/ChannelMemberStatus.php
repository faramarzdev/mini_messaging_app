<?php

namespace App\Enums;

enum ChannelMemberStatus: string
{
    case Approved = 'approved';         // became a member

    case Pending = 'pending';           // user sent a join request (private channels) --> can either be approved or blocked

    case Blocked = 'blocked';           // blocked member , channel managers can block user to not see and send

    case Invited = 'invited';           // channel managers added the user, should be changed to approved or left over a specific period

    case Left = 'left';                 // member left the channel
}

/*
 Approved, can interact   , see and send
 Pending, cannot interact , no see and no send
 Blocked, cannot interact , no see and no send
 Invited, can interact    , see and send
 Left, can interact on public but not on private, see and send on public no see or send on private
 */
