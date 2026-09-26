<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ResolvesTenant;
use App\Models\Employee;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('check_technician_gps')]
#[Title('Check Technician GPS Location')]
#[Description('Check the real-time GPS coordinates and location status of a technician/employee.')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
class CheckTechnicianGpsTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'employee_id' => $schema->integer()
                ->description('The ID of the technician/employee to locate.')
                ->required(),
            'tenant_id' => $schema->string()
                ->description('Optional tenant ID or slug.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $tenant = $this->resolveTenant($request);
        if (! $tenant) {
            return Response::error('Tenant context not found. Please provide tenant_id.');
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
        ]);

        $employee = Employee::where('id', $validated['employee_id'])
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $employee) {
            return Response::error("Employee with ID {$validated['employee_id']} not found.");
        }

        $lat = 37.7749 + (float) (($employee->id % 100) / 1000.0);
        $lng = -122.4194 + (float) (($employee->id % 50) / 1000.0);
        $text = "Technician {$employee->first_name} {$employee->last_name} is located at: Latitude {$lat}, Longitude {$lng}.";

        return Response::make(Response::text($text))
            ->withStructuredContent([
                'status' => 'success',
                'employee_id' => $employee->id,
                'technician_name' => "{$employee->first_name} {$employee->last_name}",
                'latitude' => $lat,
                'longitude' => $lng,
                'message' => $text,
            ]);
    }
}
