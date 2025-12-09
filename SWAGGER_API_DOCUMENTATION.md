# Swagger/OpenAPI API Documentation

## Overview

The CARMF IJ Calculator API is fully documented using **OpenAPI 3.0** (formerly Swagger) with automatic generation from PHP 8 attributes. The documentation is **always up-to-date** with the code because it's generated dynamically from controller annotations.

## Accessing the Documentation

### Interactive Swagger UI

**URL**: http://localhost:8000/api-docs

The Swagger UI provides:
- ✅ **Interactive testing** - Try out endpoints directly in the browser
- ✅ **Request/response examples** - See exactly what data to send and expect
- ✅ **Schema validation** - Understand data types and required fields
- ✅ **cURL command generation** - Copy commands to use in terminal
- ✅ **Full API exploration** - Browse all endpoints, parameters, and responses

### OpenAPI Specification Files

```bash
# JSON format (for tools, import into Postman, etc.)
GET /api/docs

# YAML format (human-readable, git-friendly)
GET /api/docs/yaml
```

## How It Works

### Architecture

```
Controller Method
    ↓
PHP 8 Attributes (#[OA\Post(...)])
    ↓
swagger-php Scanner (uses ReflectionClass)
    ↓
OpenAPI JSON/YAML Specification
    ↓
Swagger UI (Interactive Documentation)
```

### Technology Stack

- **zircote/swagger-php 5.x** - PHP library for OpenAPI generation
- **PHP 8 Attributes** - Native PHP attributes for clean annotations
- **ReflectionClass** - Used internally by swagger-php to scan controllers
- **Swagger UI 5.x** - CDN-hosted interactive documentation interface

### Dynamic Generation

The `SwaggerController` uses `OpenApi\Generator::scan()` to:
1. Scan all controller files in `src/Controllers/`
2. Read PHP 8 attributes using ReflectionClass
3. Generate OpenAPI specification on-the-fly
4. Serve JSON/YAML to Swagger UI

**No manual JSON/YAML editing required!**

## Adding Documentation to New Endpoints

### Step 1: Add Route

In `config/routes.php`:

```php
$group->post('/calculations/my-endpoint', [CalculationController::class, 'myMethod']);
```

### Step 2: Add Controller Method with Attributes

In `src/Controllers/CalculationController.php`:

```php
use OpenApi\Attributes as OA;

#[OA\Post(
    path: "/api/calculations/my-endpoint",
    summary: "Brief one-line description",
    description: "Detailed description of what this endpoint does and when to use it",
    tags: ["calculations"],  // Group in documentation
    requestBody: new OA\RequestBody(
        required: true,
        description: "Input data description",
        content: new OA\JsonContent(
            required: ["field1", "field2"],  // Required fields
            properties: [
                new OA\Property(
                    property: "field1",
                    type: "string",
                    description: "Description of field1",
                    example: "example-value"
                ),
                new OA\Property(
                    property: "field2",
                    type: "integer",
                    description: "Description of field2",
                    example: 42
                ),
                new OA\Property(
                    property: "optionalField",
                    type: "number",
                    format: "float",
                    description: "Optional field",
                    example: 123.45,
                    nullable: true
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Successful response",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(
                        property: "data",
                        properties: [
                            new OA\Property(property: "result", type: "string", example: "value")
                        ],
                        type: "object"
                    )
                ]
            )
        ),
        new OA\Response(response: 400, description: "Invalid request"),
        new OA\Response(response: 500, description: "Server error")
    ]
)]
public function myMethod(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    // Implementation
}
```

### Step 3: Test

1. Refresh Swagger UI: http://localhost:8000/api-docs
2. Your endpoint appears automatically!
3. Try it out directly in the browser

## Common Attribute Patterns

### GET Endpoint with Path Parameters

```php
#[OA\Get(
    path: "/api/resource/{id}",
    summary: "Get resource by ID",
    tags: ["resource"],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "Resource ID",
            schema: new OA\Schema(type: "integer", example: 123)
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Resource found"),
        new OA\Response(response: 404, description: "Resource not found")
    ]
)]
```

### POST Endpoint with Array Input

```php
#[OA\Post(
    path: "/api/bulk-process",
    summary: "Process multiple items",
    tags: ["processing"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: "items",
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "name", type: "string"),
                            new OA\Property(property: "value", type: "number")
                        ],
                        type: "object"
                    )
                )
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Processing complete")
    ]
)]
```

### Enum Values

```php
new OA\Property(
    property: "status",
    type: "string",
    enum: ["pending", "processing", "completed", "failed"],
    example: "pending"
)
```

### Date Fields

```php
new OA\Property(
    property: "birthDate",
    type: "string",
    format: "date",
    example: "1989-09-26",
    description: "Birth date in YYYY-MM-DD format"
)
```

### Optional/Nullable Fields

```php
new OA\Property(
    property: "optionalValue",
    type: "string",
    nullable: true,
    description: "Optional field"
)
```

## API Metadata (Info, Servers, Tags)

These are defined once in `CalculationController.php` at the class level:

```php
#[OA\Info(
    version: "1.0.0",
    title: "CARMF IJ Calculator API",
    description: "French medical professional sick leave benefits calculator",
    contact: new OA\Contact(name: "API Support", email: "support@example.com")
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local development server"
)]
#[OA\Server(
    url: "https://api.production.com",
    description: "Production server"
)]
#[OA\Tag(
    name: "calculations",
    description: "IJ calculation operations"
)]
#[OA\Tag(
    name: "mocks",
    description: "Mock data for testing"
)]
class CalculationController
{
    // ...
}
```

## Testing the Documentation

### Automated Test

Run the included test script:

```bash
php test_swagger_api.php
```

This verifies:
- ✅ OpenAPI JSON endpoint works
- ✅ All paths are documented
- ✅ Tags are defined
- ✅ Servers are configured
- ✅ Endpoint details are complete
- ✅ Swagger UI loads correctly
- ✅ YAML endpoint works

### Manual Testing

1. **Start server**: `cd public && php -S localhost:8000`
2. **Open Swagger UI**: http://localhost:8000/api-docs
3. **Test an endpoint**:
   - Click on an endpoint (e.g., POST /api/calculations)
   - Click "Try it out"
   - Fill in example data
   - Click "Execute"
   - View response

## Exporting Documentation

### For Postman

1. Download: http://localhost:8000/api/docs
2. Import JSON into Postman
3. All endpoints with examples are ready to use

### For Other Tools

Most API tools support OpenAPI/Swagger import:
- **Insomnia** - Import OpenAPI spec
- **Paw** - Import OpenAPI spec
- **VSCode REST Client** - Generate requests from spec

## Best Practices

### DO ✅

- **Add descriptions** - Make it clear what each endpoint does
- **Provide examples** - Use realistic example values
- **Document all fields** - Include type, format, and description
- **Specify required fields** - Use `required` parameter
- **Document error responses** - Not just 200 OK
- **Use appropriate tags** - Group related endpoints
- **Test your documentation** - Try it out in Swagger UI

### DON'T ❌

- **Skip documentation** - If you add an endpoint, document it
- **Use generic descriptions** - "Does a thing" is not helpful
- **Omit examples** - They help developers understand quickly
- **Forget about errors** - Document what can go wrong
- **Duplicate info** - Use references if needed (not shown here, but available)

## Advanced Features

### Reusable Schemas

For complex, reused objects, you can define schemas:

```php
#[OA\Schema(
    schema: "Arret",
    properties: [
        new OA\Property(property: "arret-from-line", type: "string", format: "date"),
        new OA\Property(property: "arret-to-line", type: "string", format: "date"),
        new OA\Property(property: "rechute-line", type: "integer"),
        new OA\Property(property: "dt-line", type: "integer"),
        new OA\Property(property: "gpm-member-line", type: "integer")
    ]
)]
```

Then reference it:

```php
new OA\Property(
    property: "arrets",
    type: "array",
    items: new OA\Items(ref: "#/components/schemas/Arret")
)
```

### Authentication (if added later)

```php
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
```

Apply to endpoints:

```php
#[OA\Post(
    path: "/api/secure-endpoint",
    security: [["bearerAuth" => []]],
    // ...
)]
```

## Troubleshooting

### Documentation not updating

1. Check PHP 8 syntax is correct
2. Ensure `use OpenApi\Attributes as OA;` is imported
3. Clear browser cache and refresh
4. Check server logs for errors

### Endpoint missing from docs

1. Verify PHP 8 attributes are added
2. Ensure controller is in `src/Controllers/` directory
3. Check that scanner can access the file
4. Verify route is registered in `config/routes.php`

### Swagger UI not loading

1. Check CDN is accessible (requires internet)
2. Verify `/api-docs` route is registered
3. Check browser console for errors
4. Ensure server is running on correct port

## References

- **swagger-php Documentation**: https://github.com/zircote/swagger-php
- **OpenAPI 3.0 Specification**: https://swagger.io/specification/
- **Swagger UI**: https://swagger.io/tools/swagger-ui/
- **PHP 8 Attributes**: https://www.php.net/manual/en/language.attributes.overview.php

## Summary

✅ **Dynamic generation** - Always up-to-date with code
✅ **PHP 8 attributes** - Clean, native syntax
✅ **ReflectionClass powered** - Automatic scanning
✅ **Interactive UI** - Test endpoints in browser
✅ **Zero maintenance** - No manual JSON/YAML editing
✅ **Industry standard** - OpenAPI 3.0 compatible

**Access your API documentation at**: http://localhost:8000/api-docs
