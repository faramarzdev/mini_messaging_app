<?php

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case System = 'system'; // system messages like: pined, started conversation ,...
    case Gif = 'gif';
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';

}
