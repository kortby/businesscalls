<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

class GenerateRouteDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates comprehensive markdown documentation for all registered application routes';

    /**
     * Predefined documentation details for vendor or framework-defined routes.
     */
    protected array $vendorRouteDocs = [
        'sanctum.csrf-cookie' => [
            'description' => 'Retrieve the CSRF protection cookie for Sanctum-authenticated SPA clients.',
            'how_it_works' => 'Initiates a stateful session and sets the HTTP-only cookie (`XSRF-TOKEN`) required for subsequent state-mutating requests (POST, PUT, DELETE) to protect against Cross-Site Request Forgery.',
            'how_to_use' => "Make a GET request to `/sanctum/csrf-cookie` before sending any authentication requests (such as login or register).\n\n```bash\ncurl -X GET http://localhost/sanctum/csrf-cookie -i\n```",
        ],
        'login' => [
            'description' => 'Renders the login UI page or processes authentication credentials.',
            'how_it_works' => 'GET: Renders the Inertia Welcome page or login form. POST: Validates the request credentials (email, password) and logs the user in using Fortify\'s session authentication.',
            'how_to_use' => "POST request payload:\n\n```json\n{\n  \"email\": \"user@example.com\",\n  \"password\": \"secret_password\"\n}\n```",
        ],
        'logout' => [
            'description' => 'Destroys the authenticated session and logs the user out.',
            'how_it_works' => 'POST: Clears the authenticated session cookie, invalidates the session, and redirects the user to the home page.',
            'how_to_use' => 'Send a POST request to `/logout` with a valid CSRF token header/cookie.',
        ],
        'register' => [
            'description' => 'Renders the registration form or registers a new tenant user.',
            'how_it_works' => 'GET: Renders the Inertia user registration page. POST: Validates inputs (name, email, password, password_confirmation), creates a new User model, registers a corresponding default tenant organization, and logs the user in.',
            'how_to_use' => "POST request payload:\n\n```json\n{\n  \"name\": \"John Doe\",\n  \"email\": \"john@example.com\",\n  \"password\": \"password123\",\n  \"password_confirmation\": \"password123\"\n}\n```",
        ],
        'password.request' => [
            'description' => 'Renders the password reset request prompt page.',
            'how_it_works' => 'GET: Renders the password recovery view where users input their email address to receive recovery links.',
            'how_to_use' => 'Navigate to `/forgot-password` in the web browser.',
        ],
        'password.email' => [
            'description' => 'Sends a password reset link to the specified user email address.',
            'how_it_works' => 'POST: Validates the email address, generates a unique secure token, and dispatches a password recovery email notifications.',
            'how_to_use' => "POST request payload:\n\n```json\n{\n  \"email\": \"user@example.com\"\n}\n```",
        ],
        'password.reset' => [
            'description' => 'Renders the password update/reset form.',
            'how_it_works' => 'GET: Validates the password reset token in the URI and renders the Inertia view to input a new password.',
            'how_to_use' => 'Navigate to `/reset-password/{token}` containing the reset token received in the email.',
        ],
        'password.update' => [
            'description' => 'Performs the password reset operation.',
            'how_it_works' => 'POST: Validates the token, email, and new passwords, updates the user\'s record in the database, and redirects to the login page.',
            'how_to_use' => "POST request payload:\n\n```json\n{\n  \"token\": \"secret-reset-token\",\n  \"email\": \"user@example.com\",\n  \"password\": \"new_password\",\n  \"password_confirmation\": \"new_password\"\n}\n```",
        ],
        'verification.notice' => [
            'description' => 'Renders the email verification prompt view.',
            'how_it_works' => 'GET: Renders the verification notice requesting the user to confirm their email address before accessing features.',
            'how_to_use' => 'Navigate to `/email/verify` in the web browser.',
        ],
        'verification.verify' => [
            'description' => 'Performs email verification validation.',
            'how_it_works' => 'GET: Validates the signed URL signature containing the user id and email hash, marks the email as verified in the DB, and redirects to dashboard.',
            'how_to_use' => 'Accessed by clicking the verification link sent via email.',
        ],
        'verification.send' => [
            'description' => 'Resends the email verification notification.',
            'how_it_works' => 'POST: Throttles request rates and triggers a new email verification notification flow.',
            'how_to_use' => 'Send a POST request to `/email/verification-notification`.',
        ],
        'two-factor.login' => [
            'description' => 'Renders the two-factor authentication OTP login form.',
            'how_it_works' => 'GET: Displays the interface to input a two-factor authentication code or recovery code.',
            'how_to_use' => 'Redirected automatically if 2FA is active for the logging-in account.',
        ],
        'two-factor.login.store' => [
            'description' => 'Validates two-factor OTP credentials.',
            'how_it_works' => 'POST: Validates either the 2FA one-time code or a backup recovery code against the user\'s encrypted database record.',
            'how_to_use' => "POST request payload:\n\n```json\n{\n  \"code\": \"123456\"\n}\n```\nOr with recovery code:\n```json\n{\n  \"recovery_code\": \"abcd-efgh-ijkl\"\n}\n```",
        ],
        'two-factor.enable' => [
            'description' => 'Enables two-factor authentication for the authenticated user.',
            'how_it_works' => 'POST: Generates a 2FA secret key, recovery codes, and updates the user model state to active.',
            'how_to_use' => 'Requires password confirmation before enabling.',
        ],
        'two-factor.disable' => [
            'description' => 'Disables two-factor authentication for the authenticated user.',
            'how_it_works' => 'DELETE: Clears the user\'s two-factor secret key, recovery codes, and deactivates 2FA.',
            'how_to_use' => 'Requires password confirmation before disabling.',
        ],
        'two-factor.qr-code' => [
            'description' => 'Retrieves the two-factor QR code SVG.',
            'how_it_works' => 'GET: Returns the JSON response containing the SVG string of the QR code to sync with Google Authenticator.',
            'how_to_use' => 'Retrieve SVG representation to display in frontend configuration.',
        ],
        'two-factor.secret-key' => [
            'description' => 'Retrieves the raw text two-factor secret key.',
            'how_it_works' => 'GET: Decrypts and returns the raw secret key for manual OTP enrollment.',
            'how_to_use' => 'Retrieve raw key string value for display.',
        ],
        'two-factor.recovery-codes' => [
            'description' => 'Retrieves the active two-factor recovery codes.',
            'how_it_works' => 'GET: Decrypts and returns the collection of recovery codes.',
            'how_to_use' => 'Retrieve codes collection array.',
        ],
        'two-factor.regenerate-recovery-codes' => [
            'description' => 'Regenerates a new set of two-factor recovery codes.',
            'how_it_works' => 'POST: Generates, encrypts, and saves 8 new recovery codes to replace the old ones.',
            'how_to_use' => 'Send a POST request to `/user/two-factor-recovery-codes`.',
        ],
        'password.confirm' => [
            'description' => 'Renders password confirmation view.',
            'how_it_works' => 'GET: Displays the prompt requiring password verification before performing administrative actions.',
            'how_to_use' => 'Navigate to `/user/confirm-password` in the browser.',
        ],
        'password.confirm.store' => [
            'description' => 'Validates the password confirmation request.',
            'how_it_works' => 'POST: Validates the password, stores confirmation timestamp in session, and redirects to target route.',
            'how_to_use' => "POST request payload:\n\n```json\n{\n  \"password\": \"secret_password\"\n}\n```",
        ],
        'password.confirmation' => [
            'description' => 'Checks the password confirmation timeout status.',
            'how_it_works' => 'GET: Returns a JSON response indicating whether the user\'s password has been confirmed within the timeout limit.',
            'how_to_use' => 'Perform GET request to check status.',
        ],
        'passkey.confirm' => [
            'description' => 'Validates user credentials via passkey signature confirmation.',
            'how_it_works' => 'POST: Verifies a passkey assertion signature for high-security actions.',
            'how_to_use' => 'Sends the WebAuthn signature response payload.',
        ],
        'passkey.confirm-options' => [
            'description' => 'Retrieves credentials verification challenge options for passkeys.',
            'how_it_works' => 'GET: Generates and stores verification challenge details for WebAuthn API call.',
            'how_to_use' => 'Retrieve challenge configuration settings.',
        ],
        'passkey.login' => [
            'description' => 'Authenticates user login sessions via passkey verification.',
            'how_it_works' => 'POST: Validates the WebAuthn passkey assertion signature and logs the user in.',
            'how_to_use' => 'Sends assertion signature payload.',
        ],
        'passkey.login-options' => [
            'description' => 'Retrieves login challenge options for passkeys.',
            'how_it_works' => 'GET: Generates login challenge config details.',
            'how_to_use' => 'Fetch login challenge values.',
        ],
        'passkey.store' => [
            'description' => 'Registers a new passkey credential linked to the user.',
            'how_it_works' => 'POST: Validates WebAuthn registration response details and stores public key credentials.',
            'how_to_use' => 'Sends WebAuthn registration payload.',
        ],
        'passkey.registration-options' => [
            'description' => 'Retrieves registration options for passkeys.',
            'how_it_works' => 'GET: Generates registration challenge config details.',
            'how_to_use' => 'Fetch registration options values.',
        ],
        'passkey.destroy' => [
            'description' => 'Removes a registered passkey credential.',
            'how_it_works' => 'DELETE: Locates and deletes the specified passkey record from the database.',
            'how_to_use' => 'Send DELETE request to `/user/passkeys/{passkey_id}`.',
        ],
        'cashier.payment' => [
            'description' => 'Displays the Stripe payment confirmation page.',
            'how_it_works' => 'GET: Renders a payment confirmation template for resolving 3D secure payments.',
            'how_to_use' => 'Redirect target from checkout flows.',
        ],
        'cashier.webhook' => [
            'description' => 'Handles incoming Stripe billing events.',
            'how_it_works' => 'POST: Validates the Stripe webhook signature, routes the event type, and triggers corresponding handlers (such as updating subscriber states).',
            'how_to_use' => 'Configured in the Stripe dashboard to forward webhook events.',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting route documentation generation...');

        $routes = Route::getRoutes();
        $docsDir = base_path('docs/routes');
        if (! is_dir($docsDir)) {
            mkdir($docsDir, 0755, true);
        }

        $generatedCount = 0;
        $groupedRoutes = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = implode('|', array_filter($route->methods(), fn ($m) => $m !== 'HEAD'));
            if (empty($methods)) {
                $methods = 'GET';
            }

            $action = $route->getActionName();
            $name = $route->getName();
            $middleware = $route->middleware();

            // Determine Group/Category
            $category = $this->determineCategory($uri, $name);
            $fileName = $this->generateFileName($methods, $uri);
            $filePath = $docsDir.'/'.$fileName;

            // Generate Content details
            $details = $this->parseRouteDetails($route, $methods, $uri, $action, $name, $middleware);

            // Write File
            $mdContent = $this->buildMarkdownContent($details);
            file_put_contents($filePath, $mdContent);

            $generatedCount++;
            $groupedRoutes[$category][] = [
                'uri' => $uri,
                'methods' => $methods,
                'name' => $name,
                'file' => 'routes/'.$fileName,
                'description' => $details['description'],
            ];
        }

        // Generate README.md Table of Contents
        $this->generateReadme($groupedRoutes);

        $this->info("Successfully generated {$generatedCount} route documentation files in docs/routes/");
        $this->info('Central index generated at docs/README.md');

        return self::SUCCESS;
    }

    /**
     * Group routes into categories logically based on their prefix.
     */
    protected function determineCategory(string $uri, ?string $name): string
    {
        if (str_starts_with($uri, 'admin/')) {
            return 'Admin Panel';
        }
        if (str_starts_with($uri, 'api/webhooks/') || str_starts_with($uri, 'api/telephony/') || str_starts_with($uri, 'api/telemetry/')) {
            return 'Core Webhooks & Fallbacks';
        }
        if (str_starts_with($uri, 'api/')) {
            return 'Core API Endpoints';
        }
        if (str_starts_with($uri, 'settings/') || str_contains($uri, '/settings/')) {
            return 'Settings & Configuration';
        }
        if (str_starts_with($uri, 'stripe/')) {
            return 'Billing & Subscriptions';
        }
        if (preg_match('/^(login|logout|register|forgot-password|reset-password|two-factor|passkeys|user\/two-factor|user\/passkeys|user\/confirm-password)/', $uri) || ($name && (str_starts_with($name, 'password.') || str_starts_with($name, 'two-factor.') || str_starts_with($name, 'passkey.')))) {
            return 'Authentication & Security';
        }
        if (preg_match('/^(availabilities|bookings|employees|jobs|customers|conversations)/', $uri)) {
            return 'Resource & Operations Management';
        }

        return 'General / Public Pages';
    }

    /**
     * Generate a safe, unique filename based on the method and URI.
     */
    protected function generateFileName(string $method, string $uri): string
    {
        $sanitizedMethod = strtolower(str_replace('|', '_', $method));
        $sanitizedUri = str_replace(['{', '}'], '', $uri);
        $sanitizedUri = preg_replace('/[^a-zA-Z0-9\-]/', '_', $sanitizedUri);
        $sanitizedUri = trim($sanitizedUri, '_');

        if (empty($sanitizedUri)) {
            $sanitizedUri = 'root';
        }

        return "{$sanitizedMethod}_{$sanitizedUri}.md";
    }

    /**
     * Retrieve details about a route, inspecting code where possible.
     */
    protected function parseRouteDetails($route, string $methods, string $uri, string $action, ?string $name, array $middleware): array
    {
        $description = 'No description available.';
        $howItWorks = 'Standard routing endpoint.';
        $howToUse = 'Access via the specified HTTP method.';
        $parameters = [];
        $rendersComponent = null;

        // Try predefined documentation for vendor routes
        if ($name && isset($this->vendorRouteDocs[$name])) {
            $description = $this->vendorRouteDocs[$name]['description'];
            $howItWorks = $this->vendorRouteDocs[$name]['how_it_works'];
            $howToUse = $this->vendorRouteDocs[$name]['how_to_use'];
        } elseif ($action !== 'Closure' && str_contains($action, '@')) {
            [$controllerClass, $methodName] = explode('@', $action);

            if (class_exists($controllerClass) && method_exists($controllerClass, $methodName)) {
                $refClass = new ReflectionClass($controllerClass);
                $refMethod = new ReflectionMethod($controllerClass, $methodName);

                // 1. Get doc comment
                $docComment = $refMethod->getDocComment();
                if ($docComment) {
                    $description = $this->cleanDocComment($docComment);
                }

                // 2. Read source code
                $fileName = $refMethod->getFileName();
                $startLine = $refMethod->getStartLine();
                $endLine = $refMethod->getEndLine();

                if ($fileName && $startLine && $endLine) {
                    $lines = file($fileName);
                    $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

                    // Inspect validation rules
                    if (preg_match('/\$request->validate\(\[\s*(.*?)\s*\]\)/s', $methodCode, $matches)) {
                        $validationContent = $matches[1];
                        preg_match_all("/['\"]([^'\"]+)['\"]\s*=>\s*\[\s*([^\]]+)\]/", $validationContent, $ruleMatches, PREG_SET_ORDER);

                        foreach ($ruleMatches as $ruleMatch) {
                            $paramName = $ruleMatch[1];
                            $rulesRaw = $ruleMatch[2];
                            // clean up quotes and whitespace in rules
                            $rulesClean = preg_replace('/[\'\"\s]/', '', $rulesRaw);
                            $parameters[] = [
                                'name' => $paramName,
                                'type' => str_contains($rulesClean, 'integer') ? 'integer' : (str_contains($rulesClean, 'numeric') ? 'numeric' : (str_contains($rulesClean, 'boolean') ? 'boolean' : (str_contains($rulesClean, 'array') ? 'array' : 'string'))),
                                'required' => str_contains($rulesClean, 'required') ? 'Yes' : 'No',
                                'rules' => str_replace(',', ', ', $rulesClean),
                            ];
                        }
                    }

                    // Inspect Inertia render component
                    if (preg_match("/Inertia::render\(\s*['\"]([^'\"]+)['\"]/i", $methodCode, $matches)) {
                        $rendersComponent = $matches[1];
                    }

                    // Formulate 'how it works' based on code content
                    $logicSummary = [];
                    if ($rendersComponent) {
                        $logicSummary[] = "Renders the Inertia SPA view: `{$rendersComponent}`.";
                    }
                    if (str_contains($methodCode, '::create(') || str_contains($methodCode, '::save(')) {
                        $logicSummary[] = 'Stores or persists model state to the database.';
                    }
                    if (str_contains($methodCode, '::delete(') || str_contains($methodCode, '->delete(')) {
                        $logicSummary[] = 'Deletes records or models from the database.';
                    }
                    if (str_contains($methodCode, 'TenantScope::') || str_contains($methodCode, 'tenant_id')) {
                        $logicSummary[] = 'Applies tenant isolation scoping rules to isolate company data.';
                    }
                    if (str_contains($methodCode, 'event(new') || str_contains($methodCode, '::dispatch(')) {
                        $logicSummary[] = 'Dispatches real-time broadcast events or queued jobs.';
                    }

                    if (! empty($logicSummary)) {
                        $howItWorks = implode(' ', $logicSummary);
                    } else {
                        $howItWorks = 'Processes request through the controller action.';
                    }

                    // Formulate 'how to use' based on type of route
                    if ($methods === 'GET') {
                        if ($rendersComponent) {
                            $howToUse = 'Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.';
                        } else {
                            $howToUse = 'Perform an HTTP GET request to retrieve the requested resource data.';
                        }
                    } else {
                        $howToUse = "Perform an HTTP {$methods} request with the required payload parameters.";
                    }
                }
            }
        } elseif ($action === 'Closure' && str_contains($uri, 'storage')) {
            $description = 'Serves local storage files.';
            $howItWorks = 'Maps storage URL requests to local filesystem files.';
            $howToUse = 'Request files using their public URL.';
        } elseif ($action === 'Closure' && $uri === 'up') {
            $description = 'Application health status endpoint.';
            $howItWorks = 'Returns a basic HTTP response if the application is booted, signifying the server is active.';
            $howToUse = 'Send a GET request to `/up`. Used for server uptime check monitoring.';
        }

        return [
            'methods' => $methods,
            'uri' => $uri,
            'action' => $action,
            'name' => $name ?? 'None',
            'middleware' => $middleware,
            'description' => $description,
            'how_it_works' => $howItWorks,
            'how_to_use' => $howToUse,
            'parameters' => $parameters,
            'renders_component' => $rendersComponent,
        ];
    }

    /**
     * Clean up PHP DocBlock comments to standard text.
     */
    protected function cleanDocComment(string $docComment): string
    {
        $lines = explode("\n", $docComment);
        $cleanLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '/**' || $trimmed === '*/') {
                continue;
            }
            $cleanLine = ltrim($trimmed, '* ');
            if (empty($cleanLine) || str_starts_with($cleanLine, '@')) {
                continue;
            }
            $cleanLines[] = $cleanLine;
        }

        return implode(' ', $cleanLines);
    }

    /**
     * Build the markdown file content for a route.
     */
    protected function buildMarkdownContent(array $details): string
    {
        $middlewareStr = empty($details['middleware']) ? '*None*' : implode(', ', array_map(fn ($m) => "`{$m}`", $details['middleware']));

        $content = "# Route: {$details['methods']} /{$details['uri']}\n\n";
        $content .= "{$details['description']}\n\n";

        $content .= "## Technical Details\n\n";
        $content .= "| Property | Value |\n";
        $content .= "| --- | --- |\n";
        $content .= "| **URI** | `/{$details['uri']}` |\n";
        $content .= "| **HTTP Methods** | `{$details['methods']}` |\n";
        $content .= '| **Route Name** | '.($details['name'] !== 'None' ? "`{$details['name']}`" : '*None*')." |\n";
        $content .= "| **Controller Action** | `{$details['action']}` |\n";
        $content .= "| **Middleware** | {$middlewareStr} |\n";
        if ($details['renders_component']) {
            $content .= "| **Inertia Page Component** | `{$details['renders_component']}` |\n";
        }
        $content .= "\n";

        $content .= "## How it Works\n\n";
        $content .= "{$details['how_it_works']}\n\n";

        if (! empty($details['parameters'])) {
            $content .= "## Request Parameters\n\n";
            $content .= "| Parameter | Type | Required | Rules / Constraints |\n";
            $content .= "| --- | --- | --- | --- |\n";
            foreach ($details['parameters'] as $param) {
                $content .= "| `{$param['name']}` | `{$param['type']}` | {$param['required']} | `{$param['rules']}` |\n";
            }
            $content .= "\n";
        }

        $content .= "## How to Use\n\n";
        $content .= "{$details['how_to_use']}\n";

        if (! empty($details['parameters']) && in_array($details['methods'], ['POST', 'PUT', 'PATCH'])) {
            $payload = [];
            foreach ($details['parameters'] as $param) {
                if ($param['type'] === 'integer') {
                    $payload[$param['name']] = 1;
                } elseif ($param['type'] === 'numeric') {
                    $payload[$param['name']] = 99.99;
                } elseif ($param['type'] === 'boolean') {
                    $payload[$param['name']] = true;
                } elseif ($param['type'] === 'array') {
                    $payload[$param['name']] = [];
                } else {
                    $payload[$param['name']] = 'value';
                }
            }
            $jsonPayload = json_encode($payload, JSON_PRETTY_PRINT);
            $content .= "\n### Example Request Body\n\n```json\n{$jsonPayload}\n```\n";
        }

        return $content;
    }

    /**
     * Generate the central docs/README.md file.
     */
    protected function generateReadme(array $groupedRoutes): void
    {
        $readmePath = base_path('docs/README.md');
        $parentDir = dirname($readmePath);
        if (! is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        $content = "# Application Routing Directory & Reference\n\n";
        $content .= "This directory contains comprehensive, auto-generated documentation files for all routes registered in the application. Select a route from the categories below to view its purpose, backend implementation details, parameter validation rules, and usage examples.\n\n";

        $content .= "## Categories\n\n";
        foreach (array_keys($groupedRoutes) as $category) {
            $anchor = strtolower(str_replace(' & ', '-', $category));
            $anchor = str_replace(' ', '-', $anchor);
            $content .= "- [{$category}](#{$anchor})\n";
        }
        $content .= "\n---\n\n";

        foreach ($groupedRoutes as $category => $routes) {
            $content .= "## {$category}\n\n";
            $content .= "| Method | URI | Route Name | Description |\n";
            $content .= "| --- | --- | --- | --- |\n";

            // Sort routes by URI for cleaner presentation
            usort($routes, fn ($a, $b) => strcmp($a['uri'], $b['uri']));

            foreach ($routes as $route) {
                $routeNameStr = $route['name'] && $route['name'] !== 'None' ? "`{$route['name']}`" : '*None*';
                $content .= "| `{$route['methods']}` | [`/{$route['uri']}`]({$route['file']}) | {$routeNameStr} | {$route['description']} |\n";
            }
            $content .= "\n";
        }

        file_put_contents($readmePath, $content);
    }
}
