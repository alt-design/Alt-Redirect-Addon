---
name: CRUD Redirects and Query Strings
description: The ability to CRUD redirects and query strings within the Alt Redirect addon.
---

### Domain Description
The Alt Redirect addon provides a flexible way to manage redirects and query strings in Statamic. It supports both file-based and database-based storage via a repository pattern.

### Key Components
- `AltDesign\AltRedirect\Contracts\RepositoryInterface`: The primary interface for data operations.
- `AltDesign\AltRedirect\Repositories\RepositoryManager`: Handles driver resolution.

### Basic CRUD Operations
You can perform operations on two types: `redirects` and `query-strings`.

#### Injecting the Repository
The `RepositoryInterface` is automatically bound to the active driver. You can inject it into your classes:

```php
use AltDesign\AltRedirect\Contracts\RepositoryInterface;

public function __construct(protected RepositoryInterface $repository)
{
}
```

#### Fetching All Data
To get all redirects or query strings:

```php
$redirects = $this->repository->all('redirects');
$queryStrings = $this->repository->all('query-strings');
```

#### Finding a Specific Item
To find a redirect by its `from` path:

```php
$redirect = $this->repository->find('redirects', 'from', '/old-url');
```

To find a query string by its key:

```php
$queryString = $this->repository->find('query-strings', 'query_string', 'utm_source');
```

#### Creating or Updating
To save data, pass the type and an array containing the item data.
**IMPORTANT:** Always provide an `id` (e.g., using `uniqid()`) when creating new items to ensure compatibility across all storage drivers and to allow for future updates.

**Redirect Example:**
```php
$this->repository->save('redirects', [
    'id' => uniqid(),
    'from' => '/example-from',
    'to' => '/example-to',
    'redirect_type' => '301',
    'sites' => ['default']
]);
```

**Query String Example:**
```php
$this->repository->save('query-strings', [
    'id' => uniqid(),
    'query_string' => 'new_param',
    'strip' => true,
    'sites' => ['default']
]);
```

#### Deleting
To delete an item, pass the type and an array containing at least the `id`, `from`, or `query_string` key.

```php
// Delete redirect by ID
$this->repository->delete('redirects', ['id' => '69d767de74f05']);

// Delete redirect by path
$this->repository->delete('redirects', ['from' => '/example-from']);

// Delete query string by name
$this->repository->delete('query-strings', ['query_string' => 'new_param']);
```

### Advanced Operations
#### Regex Redirects
To get only the redirects that are intended for regex matching (often those containing regex patterns in the `from` field):

```php
$regexRedirects = $this->repository->getRegex('redirects');
```

#### Bulk Saving
To save multiple items at once:

```php
$this->repository->saveAll('redirects', [
    ['from' => '/a', 'to' => '/b', 'redirect_type' => '301', 'sites' => ['default']],
    ['from' => '/c', 'to' => '/d', 'redirect_type' => '302', 'sites' => ['default']],
]);
```

### Verifying Operations
After creating or modifying a redirect, you can verify it in several ways:

#### 1. Direct Storage Check
- **File Driver**: Check for a YAML file in `content/alt-redirect/`. The filename is typically a hash of the `from` URL or its base64 encoding.
- **Database Driver**: Use a database tool to query the `alt_redirects` or `alt_query_strings` tables.

#### 2. Using CURL
You can test if the redirect is working by making a request to the `from` path:
```bash
curl -I http://your-site.test/old-url
```
A successful redirect should return a `301 Moved Permanently` or `302 Found` status with a `Location` header pointing to the new URL.

#### 3. Repository Check
You can also use the repository's `find` or `all` methods in a test or via Tinker to confirm the data was saved correctly.

### Site Configuration
Redirects and query strings are site-specific. You must provide a list of site handles in the `sites` array.

#### Obtaining Site List
To get the list of available site handles in the current Statamic installation, you can use Tinker:
```php
php artisan tinker --execute="print_r(Statamic\Facades\Site::all()->map->handle()->toArray())"
```
Or within your code:
```php
use Statamic\Facades\Site;
$siteHandles = Site::all()->map->handle()->all();
```

### Implementation Details
- **File Driver**: Stores data as YAML files in `content/alt-redirect/`.
- **Database Driver**: Uses `alt_redirects` and `alt_query_strings` tables.
- **Multisite**: Both types include a `sites` array which should contain the handles of the Statamic sites where the redirect or query string rule applies.
