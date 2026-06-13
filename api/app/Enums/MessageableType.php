<?php

namespace App\Enums;

enum MessageableType: string
{
    case Conversation = 'conversation';
    case Channel = 'channel';
}
