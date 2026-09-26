<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\BookAppointmentTool;
use App\Mcp\Tools\CancelBookingTool;
use App\Mcp\Tools\CheckAvailabilityTool;
use App\Mcp\Tools\CheckInventoryTool;
use App\Mcp\Tools\CheckTechnicianEtaTool;
use App\Mcp\Tools\CheckTechnicianGpsTool;
use App\Mcp\Tools\DispatchTechnicianTool;
use App\Mcp\Tools\GetAvailabilitySlotsTool;
use App\Mcp\Tools\GetFirstThreeAvailabilitiesTool;
use App\Mcp\Tools\KnowledgeSearchTool;
use App\Mcp\Tools\LanguageTransferTool;
use App\Mcp\Tools\LookupBookingTool;
use App\Mcp\Tools\RescheduleAppointmentTool;
use App\Mcp\Tools\VoicemailFallbackTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;

#[Name('BusinessCalls Server')]
#[Version('1.0.0')]
#[Instructions('This server provides AI telephony agent tools, appointment scheduling, technician dispatching, parts inventory checks, and tenant knowledge search for BusinessCalls.')]
class BusinessCallsServer extends Server
{
    /**
     * The tools registered with this MCP server.
     * Core tools are advertised directly, while situational tools are placed in the searchable tool catalog.
     *
     * @var array<int|string, Tool|class-string<Tool>|array<int, Tool|class-string<Tool>>>
     */
    protected array $tools = [
        // Primary tools advertised upfront
        CheckAvailabilityTool::class,
        BookAppointmentTool::class,
        LookupBookingTool::class,

        // Searchable tool catalog using ToolSearch
        ToolSearch::class => [
            CheckInventoryTool::class,
            RescheduleAppointmentTool::class,
            CancelBookingTool::class,
            CheckTechnicianEtaTool::class,
            CheckTechnicianGpsTool::class,
            DispatchTechnicianTool::class,
            KnowledgeSearchTool::class,
            GetAvailabilitySlotsTool::class,
            GetFirstThreeAvailabilitiesTool::class,
            VoicemailFallbackTool::class,
            LanguageTransferTool::class,
        ],
    ];
}
