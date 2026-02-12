<?php

namespace Rr\Bundle\Workers\Temporal\Enums;

enum TemporalEntity : string
{
    case ACTIVITY = 'activity';
    case WORKFLOW = 'workflow';
}
