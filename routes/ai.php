<?php

use App\Mcp\Servers\BusinessCallsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/businesscalls', BusinessCallsServer::class);
Mcp::local('businesscalls', BusinessCallsServer::class);
