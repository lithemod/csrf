# CSRF

The CSRF (Cross-Site Request Forgery) middleware in Lithe is a security layer that protects your application from attacks that attempt to perform actions on behalf of the user without their authorization. These attacks can occur when an authenticated user accesses a malicious site that tries to send requests to your application.

## Installation

To install the CSRF middleware in your Lithe application, use Composer. Run the following command in your terminal:

```bash
composer require lithemod/csrf
```

## Using the CSRF Middleware

The CSRF middleware should be configured in your Lithe application to protect routes that alter the state of the application (such as POST, PUT, DELETE). To configure it, add it to your application using the `use()` method on an instance of the Lithe application, and provide an array of configurations:

```php
use Lithe\Middleware\Security\csrf;

$app->use(csrf([ 
    'expire' => 600, // Token expiration time in seconds
]));
```

### Middleware Configurations

The following configurations are available for the CSRF middleware:

- **name** (string): The name of the CSRF token. The default is `_token`. You can change it to meet your needs, for example, if you are using a frontend framework that expects a different token name.

- **expire** (int): The expiration time of the token in seconds. The default is 600 seconds (10 minutes). This value should be balanced between security and usability; very low values may frustrate users.

- **checkBody** (bool): Indicates whether the token should be verified in the request body. The default is `false`. If enabled, this ensures that the token is validated even in methods like PUT or DELETE.

- **bodyMethods** (array): HTTP methods for which token validation should be applied if the `checkBody` configuration is enabled. The default is `['POST']`. This is useful if you have endpoints that modify data using other HTTP methods.

- **regenerate** (bool): Indicates whether the token should be regenerated on each request. The default is `false`. Enabling this can enhance security, but it may impact user experience in some cases.

Example configuration in a route:

```php
$app->use(csrf([ 
    'name' => '_csrf_token', 
    'expire' => 900, // 15 minutes 
    'checkBody' => true, 
    'bodyMethods' => ['POST', 'PUT', 'DELETE'], 
    'regenerate' => true,
]));
```

### Generating and Retrieving CSRF Tokens

The CSRF middleware generates a unique token for each session. You can generate and retrieve the token using the following methods within a route:

```php
$app->get('/generate-token', function ($req, $res) {
    $token = $req->csrf->generateToken();
    return $res->json(['token' => $token]);
});

$app->get('/get-token', function ($req, $res) {
    $token = $req->csrf->getToken();
    return $res->json(['token' => $token]);
});
```

The `generateToken` method has an optional parameter that, when set to `true`, forces the generation of a new token.

### Including the CSRF Token in Forms

To include the CSRF token in HTML forms, use the `getTokenField()` method to generate a hidden field with the token:

```php
$app->get('/form', function ($req, $res) {
    $tokenField = $req->csrf->getTokenField();
    return $res->send("
        <form method='POST' action='/submit'>
            $tokenField
            <input type='text' name='data' required>
            <button type='submit'>Submit</button>
        </form>
    ");
});
```

### Verifying CSRF Tokens

The middleware automatically verifies the token in POST requests and other methods specified in `bodyMethods` when the `checkBody` option is enabled. If the token is invalid or missing, an HTTP 419 exception will be thrown. If `checkBody` is disabled, you can use the following methods to verify the validity of the token:

```php
$app->post('/submit', function ($req, $res) {
    $token = $req->input('_csrf_token'); // Change to the token name if necessary
    if ($req->csrf->verifyToken($token)) {
        // Process the request
        return $res->json(['message' => 'Data submitted successfully!']);
    } else {
        // Handle the invalid token
        return $res->status(419)->json(['error' => 'Invalid CSRF token!']);
    }
});
```

### Token Manipulation Functions

Here are some useful functions for CSRF token manipulation:

- **invalidate()**: Destroys the CSRF token and its associated session variable, invalidating it.

```php
$app->post('/invalidate-token', function ($req, $res) {
    $req->csrf->invalidate();
    return $res->json(['message' => 'CSRF token invalidated!']);
});
```

- **exists()**: Checks if a CSRF token exists in the session.

```php
$app->get('/check-token', function ($req, $res) {
    $exists = $req->csrf->exists();
    return $res->json(['token_exists' => $exists]);
});
```

## Security Considerations

1. **Application Security**: Using the CSRF middleware is essential for application security. Always include the token in all forms that submit modifiable data and in AJAX requests. The absence or invalidity of the token should be handled appropriately, usually resulting in redirects or error messages.

2. **Token Expiration**: Configure the token expiration time to balance security and usability. Expired tokens should be regenerated, and the user should be notified if they attempt to use an invalid token.

3. **Body Verification**: Enabling body verification (`checkBody`) increases security, especially in APIs that use methods like PUT and DELETE. However, this may add overhead to request processing, so evaluate your application’s needs.

4. **Token Regeneration**: Enabling token regeneration on each request increases security, but it can also cause issues if users attempt to submit forms quickly. Use this with caution and test to ensure an adequate user experience.

5. **Error Handling**: Be prepared to handle HTTP 419 exceptions thrown when the CSRF token is invalid or missing. Appropriate handling may include redirecting to an error page, displaying a user-friendly message, or even logging attack attempts for later analysis.

6. **Monitoring and Analysis**: Consider implementing logging to monitor CSRF attack attempts. This can help identify suspicious patterns and further strengthen your application’s security.