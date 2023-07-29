<?php

namespace ShipSaasInboxProcess\Repositories;

use Illuminate\Database\Connection as DbConnection;
use Illuminate\Support\Facades\DB;

abstract class AbstractRepository
{
    public function makeDbClient(): DbConnection
    {
        return DB::connection(config('inbox.db_connection'));
    }
}
