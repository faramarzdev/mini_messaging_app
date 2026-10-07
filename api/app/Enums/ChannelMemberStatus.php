<?php

namespace App\Enums;

/**
 * Who may see and send on Channel entities (groups, channels):
 * Public groups: only Blocked is denied.
 * Private: only Approved and Invited.
 */
enum ChannelMemberStatus: string
{
    /** Already became a member: can see and send  */
    case Approved = 'approved';

    /**  member left the channel: cannot see and send on private but can see and send on public */
    case Left = 'left';

    /** member or request is blocked: cannot see and cannot send */
    case Blocked = 'blocked';

    /**
     * sent a request to join: cannot see and send on private but can see and send on public
     *   request can either be approved or blocked (by channel managers)
     */
    case Pending = 'pending';

    /**
     * user invited by channel managers: can see and send
     *     will be changed to approved or left after some time
     */
    case Invited = 'invited';
}
