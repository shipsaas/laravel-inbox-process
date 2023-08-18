<?php

return [
    /**
     * Put it "false" if you don't want to use "inbox/{topic}" route
     */
    'uses_default_inbox_route' => true,

    /**
     * The DB connection that Inbox Process should use
     *
     * Default: null - use Laravel default connection
     *
     * Recommendation: use a dedicated DB for inbox, for high availability purposes.
     */
    'db_connection' => null,
];
