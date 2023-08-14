<?php

namespace ShipSaasInboxProcess\Core;

enum LifecycleEventEnum: string
{
    case CLOSING = 'closing';
    case CLOSED = 'closed';
}
