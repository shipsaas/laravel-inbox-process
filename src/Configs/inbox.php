<?php

return [
    /**
     * Put it "false" if you don't want to use "inbox/{topic}" route
     */
    'uses_default_inbox_route' => true,

    /**
     * Customize route path if you don't want to use the default "inbox/{topic}"
     *
     * E.g.:
     * - inbox => inbox/{topic}
     * - this-is/my-inbox/pro-max => this-is/my-inbox/pro-max/{topic}
     *
     * You can use hardcoded code here or ENV(...)
     */
    'route_path' => 'inbox',

    /**
     * The DB connection that Inbox Process should use
     *
     * Default: null - use Laravel default connection
     *
     * Recommendation: use a dedicated DB for inbox, for high availability purposes.
     */
    'db_connection' => null,
];
