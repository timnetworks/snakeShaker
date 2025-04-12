<?php
// Set headers FIRST
header('Content-Type: application/json');

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// --- Error Logging Setup ---
error_reporting(E_ALL); // Report all PHP errors
ini_set('display_errors', 0); // Do NOT display errors to the browser in production
ini_set('log_errors', 0); // Log errors to the server's error log file
// Optionally specify a custom error log file path:
// ini_set('error_log', __DIR__ . '/php_error.log');
// --- End Error Logging Setup ---

// --- Execution Time Limit ---
// Increase maximum execution time if external API calls are slow.
// Default is often 30 or 60 seconds. Set to 120 seconds (2 minutes) here.
// Be mindful of server resources if setting very high limits.
// Set to 10 seconds because shared hosting.
set_time_limit(10);
// --- End Execution Time Limit ---

// --- DEBUG CONFIGURATION ---
define('DEBUG_MODE', true); // Set to true to enable logging, false to disable
define('DEBUG_LOG_FILE', __DIR__ . './debug_log.txt'); // Log file in the same directory

// --- API KEY CONFIGURATION ---
// IMPORTANT: Get your free API key from https://libraries.io/api
// Store it securely (e.g., environment variable) instead of hardcoding if possible.
define('LIBRARIES_IO_API_KEY', 'YOUR_API_KEY_GOES_HERE'); // <<< REPLACE THIS or use getenv('LIBRARIES_IO_API_KEY')

// Helper function for logging
function debug_log($message) {
    if (DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] " . (is_string($message) ? $message : print_r($message, true)) . "\n";
        // Use FILE_APPEND to add to the log file without overwriting + LOCK_EX for concurrent requests
        @file_put_contents(DEBUG_LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

debug_log("--- New Request ---");
debug_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
// Log headers only if needed for deep debugging, can be verbose
// debug_log("Request Headers: " . print_r(getallheaders(), true));


// --- Standard Library Definitions ---
$python_builtins = [
    'abc', 'aifc', 'argparse', 'array', 'ast', 'asyncio', 'atexit', 'audioop',
    'base64', 'bdb', 'binascii', 'binhex', 'bisect', 'builtins', 'bz2',
    'calendar', 'cgi', 'cgitb', 'chunk', 'cmath', 'cmd', 'code', 'codecs',
    'codeop', 'collections', 'colorsys', 'compileall', 'concurrent', 'configparser',
    'contextlib', 'contextvars', 'copy', 'copyreg', 'cProfile', 'crypt', 'csv',
    'ctypes', 'curses', 'dataclasses', 'datetime', 'dbm', 'decimal', 'difflib',
    'dis', 'distutils', 'doctest', 'email', 'encodings', 'ensurepip', 'enum',
    'errno', 'faulthandler', 'fcntl', 'filecmp', 'fileinput', 'fnmatch',
    'formatter', 'fractions', 'ftplib', 'functools', 'gc', 'getopt', 'getpass',
    'gettext', 'glob', 'grp', 'gzip', 'hashlib', 'heapq', 'hmac', 'html',
    'http', 'idlelib', 'imaplib', 'imghdr', 'imp', 'importlib', 'inspect',
    'io', 'ipaddress', 'itertools', 'json', 'keyword', 'lib2to3', 'linecache',
    'locale', 'logging', 'lzma', 'mailbox', 'mailcap', 'marshal', 'math',
    'mimetypes', 'mmap', 'modulefinder', 'msilib', 'msvcrt', 'multiprocessing',
    'netrc', 'nis', 'nntplib', 'numbers', 'operator', 'optparse', 'os',
    'ossaudiodev', 'parser', 'pathlib', 'pdb', 'pickle', 'pickletools', 'pipes',
    'pkgutil', 'platform', 'plistlib', 'poplib', 'posix', 'pprint', 'profile',
    'pty', 'pwd', 'py_compile', 'pyclbr', 'pydoc', 'queue', 'quopri',
    'random', 're', 'readline', 'reprlib', 'resource', 'rlcompleter', 'runpy',
    'sched', 'secrets', 'select', 'selectors', 'shelve', 'shlex', 'shutil',
    'signal', 'site', 'smtpd', 'smtplib', 'sndhdr', 'socket', 'socketserver',
    'sqlite3', 'spwd', 'ssl', 'stat', 'statistics', 'string', 'stringprep',
    'struct', 'subprocess', 'sunau', 'symbol', 'symtable', 'sys', 'sysconfig',
    'syslog', 'tabnanny', 'tarfile', 'telnetlib', 'tempfile', 'termios',
    'test', 'textwrap', 'threading', 'time', 'timeit', 'tkinter', 'token',
    'tokenize', 'trace', 'traceback', 'tracemalloc', 'tty', 'turtle',
    'turtledemo', 'types', 'typing', 'unicodedata', 'unittest', 'urllib',
    'uu', 'uuid', 'venv', 'warnings', 'wave', 'weakref', 'webbrowser',
    'winreg', 'winsound', 'wsgiref', 'xdrlib', 'xml', 'xmlrpc', 'zipapp',
    'zipfile', 'zipimport', 'zlib'
];
$nodejs_builtins = [
    'assert', 'async_hooks', 'buffer', 'child_process', 'cluster', 'console',
    'constants', 'crypto', 'dgram', 'diagnostics_channel', 'dns', 'domain',
    'events', 'fs', 'http', 'http2', 'https', 'inspector', 'module', 'net',
    'os', 'path', 'perf_hooks', 'process', 'punycode', 'querystring', 'readline',
    'repl', 'stream', 'string_decoder', 'sys', // sys is deprecated but might appear
    'timers', 'tls', 'trace_events', 'tty', 'url', 'util', 'v8', 'vm',
    'worker_threads', 'zlib'
];


// Only allow POST requests (already checked OPTIONS)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $error_msg = 'Method Not Allowed. Only POST requests are accepted.';
    // Log the error before exiting
    debug_log("Request failed: {$error_msg}");
    // Send JSON error response
    echo json_encode(['error' => $error_msg]);
    exit;
}

// Get raw POST data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
debug_log("Received Raw Payload: " . ($json_data ?: '<empty>'));

// Check if JSON decoding was successful and 'code' exists
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['code'])) {
    http_response_code(400); // Bad Request
    $error_msg = 'Invalid JSON payload or missing "code" field. Error: ' . json_last_error_msg();
    debug_log("Request failed: {$error_msg}");
    echo json_encode(['error' => $error_msg]);
    exit;
}

$code = $data['code'];
// Log only a snippet to avoid huge logs
debug_log("Received Code Snippet (first 500 chars):\n" . substr($code, 0, 500));

if (empty(trim($code))) {
    debug_log("Code snippet is empty, returning empty results.");
    echo json_encode([]); // Return empty array if code is empty
    exit;
}

// --- Language Detection & Parsing ---
/**
 * Detects potential packages based on language-specific patterns.
 * (Function remains the same as previous revision)
 * @param string $code The code snippet.
 * @return array An array ['lang' => string, 'packages' => array]
 */
 function detect_and_extract_packages(string $code): array {
    debug_log("Attempting to detect language and extract packages...");
    $packages = [];
    $language = 'unknown';

    // Python Check (Prioritize)
    if (preg_match('/^\s*(?:import|from)\s+([a-zA-Z0-9_.]+)/m', $code)) {
        $language = 'python';
        debug_log("Detected potential Python code.");
        preg_match_all('/^\s*(?:import|from)\s+([a-zA-Z0-9_.]+)(?:.*)?/m', $code, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $pkg) {
                 $parts = explode('.', $pkg);
                 $top_level_pkg = $parts[0];
                 if (!empty($top_level_pkg) && ctype_alnum(str_replace('_', '', $top_level_pkg))) {
                    $packages[$top_level_pkg] = $top_level_pkg;
                 }
            }
        }
        debug_log("Extracted Python candidates: " . implode(', ', $packages));
    }

    // JavaScript Check
    if (preg_match('/(require\s*\(\s*[\'"]([a-zA-Z0-9@\/_.-]+)[\'"]\s*\)|import(?:["\s{}]|\s+.*\s+from)\s*[\'"]([a-zA-Z0-9@\/_.-]+)[\'"])/', $code)) {
        if ($language === 'unknown') $language = 'js';
        debug_log("Detected potential JavaScript code.");
        preg_match_all('/require\s*\(\s*[\'"]([a-zA-Z0-9@\/_.-]+)[\'"]\s*\)/', $code, $matches_require);
        preg_match_all('/import(?:.*from)?\s*[\'"]([a-zA-Z0-9@\/_.-]+)[\'"]/', $code, $matches_import);

        $js_packages_raw = array_merge($matches_require[1] ?? [], $matches_import[1] ?? []);
        $js_packages = [];
        foreach($js_packages_raw as $pkg) {
             if (strpos($pkg, './') !== 0 && strpos($pkg, '../') !== 0 && strlen($pkg) > 0) {
                 if (strpos($pkg, '@') === 0 && strpos($pkg, '/') !== false) {
                    $js_packages[$pkg] = $pkg;
                 } else {
                     $parts = explode('/', $pkg);
                     $js_packages[$parts[0]] = $parts[0];
                 }
             }
        }
        $new_js_packages = array_diff_key($js_packages, $packages);
        if (!empty($new_js_packages)) {
             debug_log("Extracted JS candidates: " . implode(', ', $new_js_packages));
             $packages = array_merge($packages, $new_js_packages);
        }
    }

    // PHP Check
    if (preg_match('/^\s*use\s+([a-zA-Z0-9_]+\\\\[a-zA-Z0-9_\\\\]+)/m', $code) || strpos($code, '<?php') !== false) {
         if (preg_match('/^\s*use\s+([a-zA-Z0-9_]+\\\\[a-zA-Z0-9_\\\\]+)/m', $code)) {
            if ($language === 'unknown') $language = 'php';
            debug_log("Detected potential PHP code (via 'use' statement).");
            preg_match_all('/^\s*use\s+([a-zA-Z0-9_]+(?:\\\\[a-zA-Z0-9_]+)+)/m', $code, $matches_use);
            $php_packages = [];
            if (!empty($matches_use[1])) {
                foreach ($matches_use[1] as $namespace) {
                    $parts = explode('\\', $namespace);
                    if (count($parts) >= 2) {
                        $composer_name = strtolower($parts[0] . '/' . $parts[1]);
                        $php_packages[$composer_name] = $composer_name;
                    } else {
                         debug_log("Skipping PHP 'use' statement - not Vendor\\Package format: " . $namespace);
                    }
                }
            }
            $new_php_packages = array_diff_key($php_packages, $packages);
             if (!empty($new_php_packages)) {
                 debug_log("Extracted PHP Composer candidates: " . implode(', ', $new_php_packages));
                 $packages = array_merge($packages, $new_php_packages);
             }
         } elseif ($language === 'unknown' && strpos($code, '<?php') !== false) {
            $language = 'php';
            debug_log("Detected potential PHP code (via '<?php' tag) but no 'use Vendor\Package' statements found.");
         }
    }

    $final_packages = array_values(array_filter($packages, 'strlen'));

    if ($language === 'unknown') {
        if (strpos($code, '$') !== false || strpos($code, '->') !== false) $language = 'php';
        else if (strpos($code, '{') !== false && strpos($code, '}') !== false) $language = 'js';
        debug_log("Language detection fallback resulted in: " . $language);
    }

    debug_log("Final language detection: " . $language);
    debug_log("Final unique package candidates: " . implode(', ', $final_packages));

    return ['lang' => $language, 'packages' => $final_packages];
}


// --- Registry Query Functions ---

/**
 * Fetches data from a URL using file_get_contents with context options.
 * (Function remains the same as previous revision)
 * @param string $url
 * @param array $extra_headers Optional associative array of headers.
 * @return array ['error' => bool, 'status_code' => int, 'body' => string|null, 'message' => string|null]
 */
function fetch_url(string $url, array $extra_headers = []): array {
    debug_log("Fetching URL: " . $url);
    $default_headers = [
        "Accept: application/json",
        // Update User-Agent if needed
        "User-Agent: snakeShaker/1.1 (+https://yourdomain.com/)"
    ];
    $headers_string = implode("\r\n", array_merge($default_headers, $extra_headers)) . "\r\n";

    $options = [
        'http' => [
            'method' => "GET",
            'header' => $headers_string,
            'timeout' => 20, // Slightly longer timeout for API calls
            'ignore_errors' => true,
            'follow_location' => 1,
            'max_redirects' => 1,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ];
    $context = stream_context_create($options);
    // Error suppression: Use error handling below instead
    $response_body = file_get_contents($url, false, $context);

    $status_code = 0;
    $message = null;
    // $http_response_header is magically populated by PHP
    if (isset($http_response_header) && is_array($http_response_header) && count($http_response_header) > 0) {
        for ($i = count($http_response_header) - 1; $i >= 0; $i--) {
             if (preg_match('{HTTP/\d\.\d\s+(\d+)}', $http_response_header[$i], $matches)) {
                $status_code = (int)$matches[1];
                debug_log("Received HTTP Status: " . $status_code);
                break;
             }
        }
    }

    // Explicit check for false response and capture error
    if ($response_body === false) {
         $error_info = error_get_last();
         $message = 'Network error or timeout connecting to API.' . ($error_info ? ' Error: ' . $error_info['message'] : '');
         $status_code = $status_code ?: 0; // Use 0 if no status code was captured
         debug_log("file_get_contents failed for {$url}. " . $message);
         // Return detailed error information
         return ['error' => true, 'message' => $message, 'status_code' => $status_code, 'body' => null];
    }

    // Check for HTTP client or server errors (4xx, 5xx)
    if ($status_code >= 400) {
         $message = "API returned HTTP status {$status_code}.";
         debug_log("Received HTTP Error {$status_code} for {$url}. Body snippet: " . substr($response_body, 0, 200));
         // Return error true, let the calling function decide how to interpret specific codes like 404
         return ['error' => true, 'message' => $message, 'status_code' => $status_code, 'body' => $response_body];
    }

    // Success (2xx or 3xx redirection handled)
    debug_log("Successfully fetched URL {$url}. Response length: " . strlen($response_body));
    return ['error' => false, 'status_code' => $status_code, 'body' => $response_body, 'message' => null];
}

/**
 * Queries the PyPI JSON API.
 * (Function remains the same as previous revision)
 * @param string $package_name
 * @return array Package info or error status.
 */
function query_pypi(string $package_name): array {
    debug_log("[PyPI] Querying for: " . $package_name);
    $url = "https://pypi.org/pypi/" . urlencode($package_name) . "/json";
    $fetch_result = fetch_url($url);

    if ($fetch_result['status_code'] == 404) {
        debug_log("[PyPI] 404 Not Found for: " . $package_name);
        return ['status' => 'Not Found'];
    }
    if ($fetch_result['error']) { // Checks for network errors OR non-404 HTTP errors
        debug_log("[PyPI] Fetch error for {$package_name}: " . $fetch_result['message']);
        return ['status' => 'Error', 'error' => $fetch_result['message'] . " (Code: " . $fetch_result['status_code'] . ")"];
    }

    $response_body = $fetch_result['body'];
    $data = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['info'])) {
        debug_log("[PyPI] JSON decode error or missing 'info' for {$package_name}: " . json_last_error_msg());
        return ['status' => 'Error', 'error' => 'Invalid JSON response from PyPI.'];
    }

    $info = $data['info'];
    $created = null;
    if (isset($data['releases']) && is_array($data['releases']) && !empty($data['releases'])) {
        $earliest_time_obj = null;
        foreach ($data['releases'] as $version_files) {
            if(is_array($version_files)) {
                 foreach ($version_files as $file_info) {
                    if (isset($file_info['upload_time_iso_8601'])) {
                        try {
                           $current_time_obj = new DateTime($file_info['upload_time_iso_8601']);
                           if ($earliest_time_obj === null || $current_time_obj < $earliest_time_obj) {
                               $earliest_time_obj = $current_time_obj;
                           }
                        } catch (Exception $e) {
                             debug_log("[PyPI] Warning: Could not parse date '{$file_info['upload_time_iso_8601']}' for {$package_name}. Error: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        if ($earliest_time_obj !== null) {
             $created = $earliest_time_obj->format(DateTime::ATOM);
        }
    }

    $author = $info['author'] ?? ($info['maintainer'] ?? 'N/A');
    if (strlen($author) > 150) $author = substr($author, 0, 147) . '...';
    $link_url = $info['package_url'] ?? ("https://pypi.org/project/" . urlencode($package_name) . "/");

    debug_log("[PyPI] Found: {$package_name}. Created: {$created}. Author: {$author}");
    return [
        'status' => 'Found',
        'registry' => 'PyPI',
        'created' => $created,
        'author' => $author,
        'link_url' => $link_url
    ];
}

/**
 * Queries the npm Registry API.
 * (Function remains the same as previous revision)
 * @param string $package_name
 * @return array Package info or error status.
 */
function query_npm(string $package_name): array {
    debug_log("[npm] Querying for: " . $package_name);
    $encoded_name = str_replace('%2F', '/', rawurlencode($package_name));
    $url = "https://registry.npmjs.org/" . $encoded_name;
    $fetch_result = fetch_url($url);

    if ($fetch_result['status_code'] == 404) {
         debug_log("[npm] 404 Not Found for: " . $package_name);
         return ['status' => 'Not Found'];
    }
    if ($fetch_result['error']) {
        debug_log("[npm] Fetch error for {$package_name}: " . $fetch_result['message']);
         return ['status' => 'Error', 'error' => $fetch_result['message'] . " (Code: " . $fetch_result['status_code'] . ")"];
    }

    $response_body = $fetch_result['body'];
    $data = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        debug_log("[npm] JSON decode error for {$package_name}: " . json_last_error_msg());
        return ['status' => 'Error', 'error' => 'Invalid JSON response from npm Registry.'];
    }
    if (isset($data['error']) && $data['error'] === 'Not found') {
        debug_log("[npm] API returned 'Not found' error object for {$package_name}.");
        return ['status' => 'Not Found'];
    }

    if (!isset($data['time']['created']) || !isset($data['_id'])) {
         debug_log("[npm] Missing essential info (time.created or _id) in npm response for {$package_name}.");
         if(isset($data['_id'])) {
              $link_url_fallback = "https://www.npmjs.com/package/" . $encoded_name;
               return [
                'status' => 'Found',
                'registry' => 'npm',
                'created' => null,
                'author' => $data['author']['name'] ?? 'N/A',
                'link_url' => $link_url_fallback,
                'error' => 'Incomplete metadata (missing creation time).'
             ];
         }
        return ['status' => 'Error', 'error' => 'Missing required info in npm response.'];
    }

    $author_info = 'N/A';
    if (isset($data['maintainers']) && is_array($data['maintainers']) && !empty($data['maintainers'])) {
        $author_info = implode(', ', array_map(function($m) { return $m['name'] ?? '?'; }, $data['maintainers']));
    } elseif (isset($data['author']['name'])) {
        $author_info = $data['author']['name'];
    }
    if (strlen($author_info) > 150) $author_info = substr($author_info, 0, 147) . '...';

    $created = $data['time']['created'] ?? null;
    $link_url = "https://www.npmjs.com/package/" . $encoded_name;
    debug_log("[npm] Found: {$package_name}. Created: {$created}. Author: " . $author_info);
    return [
        'status' => 'Found',
        'registry' => 'npm',
        'created' => $created,
        'author' => $author_info,
        'link_url' => $link_url
    ];
}

/**
 * Queries the Packagist API.
 * (Function remains the same as previous revision)
 * @param string $package_name Expected format "vendor/package"
 * @return array Package info or error status.
 */
function query_packagist(string $package_name): array {
    debug_log("[Packagist] Querying for: " . $package_name);
    if (strpos($package_name, '/') === false || substr_count($package_name, '/') !== 1) {
         debug_log("[Packagist] Invalid name format (skipped query): " . $package_name);
         return ['status' => 'Unknown', 'error' => 'Invalid name format (expected vendor/package).'];
    }

    $url = "https://packagist.org/packages/" . urlencode($package_name) . ".json";
    $fetch_result = fetch_url($url);

    if ($fetch_result['status_code'] == 404) {
        debug_log("[Packagist] 404 Not Found for: " . $package_name);
        return ['status' => 'Not Found'];
    }
    if ($fetch_result['error']) {
        debug_log("[Packagist] Fetch error for {$package_name}: " . $fetch_result['message']);
         return ['status' => 'Error', 'error' => $fetch_result['message'] . " (Code: " . $fetch_result['status_code'] . ")"];
    }

    $response_body = $fetch_result['body'];
    $data = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        debug_log("[Packagist] JSON decode error for {$package_name}: " . json_last_error_msg());
        return ['status' => 'Error', 'error' => 'Invalid JSON response from Packagist.'];
    }
    if (isset($data['status']) && $data['status'] === 'error') {
        $error_message = $data['message'] ?? 'Unknown Packagist error';
        if (stripos($error_message, 'Package not found') !== false) {
            debug_log("[Packagist] API returned 'Package not found' for {$package_name}.");
            return ['status' => 'Not Found'];
        }
        debug_log("[Packagist] API returned error for {$package_name}: " . $error_message);
        return ['status' => 'Error', 'error' => 'Packagist API error: ' . $error_message];
    }

    if (!isset($data['package']['time']) || !isset($data['package']['name'])) {
         debug_log("[Packagist] Missing essential info (package.time or package.name) in response for {$package_name}.");
          if(isset($data['package']['name'])) {
              $link_url_fallback = "https://packagist.org/packages/" . urlencode($package_name);
               return [
                'status' => 'Found',
                'registry' => 'Packagist',
                'created' => null,
                'author' => 'N/A',
                'link_url' => $link_url_fallback,
                'error' => 'Incomplete metadata (missing creation time).'
             ];
          }
         return ['status' => 'Error', 'error' => 'Invalid or incomplete JSON structure from Packagist.'];
    }

    $package_info = $data['package'];
    $author_info = 'N/A';
    if (isset($package_info['maintainers']) && is_array($package_info['maintainers']) && !empty($package_info['maintainers'])) {
       $author_info = implode(', ', array_map(function($m) { return $m['name'] ?? '?'; }, $package_info['maintainers']));
    }
    elseif(isset($package_info['versions']) && is_array($package_info['versions']) && !empty($package_info['versions'])) {
        $latest_version_data = reset($package_info['versions']);
        if($latest_version_data && isset($latest_version_data['authors']) && is_array($latest_version_data['authors']) && !empty($latest_version_data['authors'])) {
            $author_info = implode(', ', array_map(function($a) { return $a['name'] ?? '?'; }, $latest_version_data['authors']));
        }
    }
    if (strlen($author_info) > 150) $author_info = substr($author_info, 0, 147) . '...';

    $created = $package_info['time'] ?? null;
    $link_url = "https://packagist.org/packages/" . urlencode($package_name);
    debug_log("[Packagist] Found: {$package_name}. Created: {$created}. Author: " . $author_info);
    return [
        'status' => 'Found',
        'registry' => 'Packagist',
        'created' => $created,
        'author' => $author_info,
        'link_url' => $link_url
    ];
}

/**
 * Queries the Libraries.io API for supplementary package info.
 * (Function remains the same as previous revision)
 * @param string $platform ('Pypi', 'NPM', 'Packagist') Case-sensitive!
 * @param string $package_name
 * @return array Supplementary info or error status.
 */
function query_libraries_io(string $platform, string $package_name): array {
    debug_log("[Libraries.io] Querying {$platform} for: " . $package_name);
    $api_key = defined('LIBRARIES_IO_API_KEY') ? LIBRARIES_IO_API_KEY : '';

    if (empty($api_key) || $api_key === 'YOUR_API_KEY_GOES_HERE') {
        debug_log("[Libraries.io] API Key not configured. Skipping query.");
        return ['status' => 'Skipped', 'error' => 'API key not configured.'];
    }

    $encoded_name = urlencode($package_name);
    $url = "https://libraries.io/api/{$platform}/{$encoded_name}?api_key={$api_key}";
    $fetch_result = fetch_url($url); // Uses the updated fetch_url

    if ($fetch_result['status_code'] == 404) {
        debug_log("[Libraries.io] 404 Not Found for {$platform}/{$package_name}.");
        return ['status' => 'Not Found'];
    }
    if ($fetch_result['status_code'] == 401) {
        debug_log("[Libraries.io] 401 Unauthorized for {$platform}/{$package_name}. Check API Key.");
        return ['status' => 'Error', 'error' => 'Libraries.io API key is invalid or missing permission.'];
    }
    if ($fetch_result['status_code'] == 429) {
        debug_log("[Libraries.io] 429 Rate Limit Exceeded for {$platform}/{$package_name}.");
        return ['status' => 'Error', 'error' => 'Libraries.io API rate limit exceeded. Try again later.'];
    }
    if ($fetch_result['error']) { // Catch other fetch errors
        debug_log("[Libraries.io] Fetch error for {$platform}/{$package_name}: " . $fetch_result['message']);
        return ['status' => 'Error', 'error' => 'Error connecting to Libraries.io API: ' . $fetch_result['message'] . " (Code: " . $fetch_result['status_code'] . ")"];
    }

    $response_body = $fetch_result['body'];
    $data = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        debug_log("[Libraries.io] JSON decode error for {$platform}/{$package_name}: " . json_last_error_msg());
        return ['status' => 'Error', 'error' => 'Invalid JSON response from Libraries.io.'];
    }

    $supplementary_info = [
        'status' => 'Found',
        'libraries_io_rank' => $data['rank'] ?? null,
        'libraries_io_stars' => $data['stars'] ?? null,
        'libraries_io_dependents_count' => $data['dependents_count'] ?? null,
        'libraries_io_latest_release_date' => $data['latest_release_published_at'] ?? null,
        'libraries_io_url' => $data['repository_url'] ?? ($data['homepage'] ?? null)
    ];

    if (empty($supplementary_info['libraries_io_url'])) {
         $supplementary_info['libraries_io_url'] = "https://libraries.io/{$platform}/{$encoded_name}";
    }

    debug_log("[Libraries.io] Found supplementary info for {$platform}/{$package_name}."); // Less verbose log
    return $supplementary_info;
}


// --- Safety Assessment Logic ---
/**
 * Provides a basic safety likelihood assessment based on age and status.
 * (Function remains the same as previous revision)
 * @param array $package_info Result from query_* or built-in handling.
 * @return array An array with 'safety' string description.
 */
function assess_safety(array $package_info): array {
    $name = $package_info['name'] ?? 'Unknown Package';
    debug_log("Assessing safety for: " . $name);

    if (isset($package_info['is_builtin']) && $package_info['is_builtin'] === true) {
         debug_log("[Safety:{$name}] Built-in.");
         return ['safety' => 'Likely Safe (Built-in)'];
    }

    switch ($package_info['status']) {
        case 'Not Found':
            debug_log("[Safety:{$name}] Status 'Not Found' -> High Potential Risk.");
            return ['safety' => 'High Potential Risk (Not Found)'];
        case 'Error':
             debug_log("[Safety:{$name}] Status 'Error' -> Unknown.");
             if (isset($package_info['error']) && stripos($package_info['error'], 'Incomplete metadata') !== false) {
                 return ['safety' => 'Use Caution (Incomplete Info)'];
             }
             // Include part of the error message for context if possible
             $error_context = isset($package_info['error']) ? substr($package_info['error'], 0, 30) : '';
             return ['safety' => 'Unknown (API Error' . ($error_context ? ': ' . $error_context . '...' : '') . ')'];
        case 'Unknown':
            debug_log("[Safety:{$name}] Status 'Unknown' -> Unknown.");
            return ['safety' => 'Unknown (Cannot Verify)'];
        case 'Built-in':
             debug_log("[Safety:{$name}] Status 'Built-in'.");
             return ['safety' => 'Likely Safe (Built-in)'];
        default:
            break;
    }

    if (empty($package_info['created'])) {
        debug_log("[Safety:{$name}] Missing Creation Date -> Use Caution.");
         if (isset($package_info['error']) && stripos($package_info['error'], 'Incomplete metadata') !== false) {
              return ['safety' => 'Use Caution (Incomplete Info)'];
         }
        return ['safety' => 'Use Caution (Missing Date)'];
    }

    try {
        $created_date = new DateTime($package_info['created']);
        $now = new DateTime();
        $threshold_30_days = (clone $now)->modify('-30 days');
        $threshold_6_months = (clone $now)->modify('-6 months');
        $threshold_2_years = (clone $now)->modify('-2 years');

        $safety = 'Unknown (Date Range Error)'; // Default if logic fails

        if ($created_date > $threshold_30_days) {
            $safety = 'Potential Risk (< 30 days old)';
        } elseif ($created_date > $threshold_6_months) {
             $safety = 'Use Caution (< 6 months old)';
        } elseif ($created_date > $threshold_2_years) {
             $safety = 'Likely Safe (Moderately Established)';
        } else { // 2 years or older
             $safety = 'Likely Safe (Established)';
        }

        debug_log("[Safety:{$name}] Created: {$package_info['created']}. Assessed as: {$safety}");
        return ['safety' => $safety];

    } catch (Exception $e) {
         debug_log("[Safety:{$name}] Error parsing date '{$package_info['created']}': " . $e->getMessage());
        return ['safety' => 'Unknown (Invalid Date)'];
    }
}


// --- Main Processing Logic ---

$detected = detect_and_extract_packages($code);
$package_names = $detected['packages'];
$language = $detected['lang'];
$results = [];

if (empty($package_names)) {
    debug_log("No packages detected to process.");
    echo json_encode([]);
    exit;
}

debug_log("Processing detected packages for language: " . $language);

foreach ($package_names as $name) {
    debug_log("--- Processing package: {$name} ---");
    $package_info = ['name' => $name];
    $is_builtin = false;
    $primary_registry = 'N/A';

    // 1. Check built-in
    if ($language === 'python' && in_array($name, $python_builtins, true)) {
        debug_log("Identified '{$name}' as Python built-in.");
        $package_info = array_merge($package_info, [
            'status' => 'Built-in', 'registry' => 'Python Standard Library', 'created' => null,
            'author' => 'Python Core Developers', 'is_builtin' => true, 'link_url' => null
        ]);
        $is_builtin = true;
    } elseif ($language === 'js' && in_array($name, $nodejs_builtins, true)) {
        debug_log("Identified '{$name}' as Node.js built-in.");
         $package_info = array_merge($package_info, [
            'status' => 'Built-in', 'registry' => 'Node.js Core Module', 'created' => null,
            'author' => 'Node.js Core Developers', 'is_builtin' => true, 'link_url' => null
        ]);
        $is_builtin = true;
    }

    // 2. Query primary registry if not built-in
    if (!$is_builtin) {
        $query_result = null;
        $query_error_msg = null; // Store specific error message if any
        try {
            switch ($language) {
                case 'python':
                    $primary_registry = 'PyPI';
                    $query_result = query_pypi($name);
                    break;
                case 'js':
                    $primary_registry = 'npm';
                    $query_result = query_npm($name);
                    break;
                case 'php':
                    $primary_registry = 'Packagist';
                    $query_result = query_packagist($name);
                    break;
                default:
                    $query_error_msg = 'Cannot query registry (Unknown Language).';
                    $query_result = ['status' => 'Unknown', 'error' => $query_error_msg];
                    $primary_registry = 'N/A';
                    debug_log("Language '{$language}' is unknown or unsupported for registry lookups for '{$name}'.");
            }
             // Capture error message from query result if status is Error
             if (isset($query_result['status']) && $query_result['status'] === 'Error' && isset($query_result['error'])) {
                 $query_error_msg = $query_result['error'];
             }

        } catch (Exception $e) {
            // Catch unexpected exceptions during the query *function call* itself
            $query_error_msg = "Internal PHP error during primary query: " . $e->getMessage();
            debug_log("!!! Exception during primary query function call for {$name}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $query_result = ['status' => 'Error', 'error' => $query_error_msg];
        }

        // Merge primary query results. Ensure 'error' key exists if status is Error.
        $package_info = array_merge($package_info, $query_result);
        if ($package_info['status'] === 'Error' && !isset($package_info['error'])) {
            $package_info['error'] = $query_error_msg ?? 'Unknown query error.';
        }
        $package_info['registry'] = $package_info['registry'] ?? $primary_registry;
    }

    // 3. Query Libraries.io (unless built-in or primary query failed badly)
    $libraries_io_info = null;
    if (!$is_builtin && $package_info['status'] !== 'Unknown' && ($package_info['status'] !== 'Error' || stripos($package_info['error'] ?? '', 'network error') === false)) { // Don't query Lib.io if primary had network error
         $libraries_io_platform = match($language) {
             'python' => 'Pypi', 'js' => 'NPM', 'php' => 'Packagist', default => null,
         };

         if ($libraries_io_platform) {
              try {
                  $libraries_io_info = query_libraries_io($libraries_io_platform, $name);
                  if (is_array($libraries_io_info) && $libraries_io_info['status'] !== 'Error' && $libraries_io_info['status'] !== 'Skipped') {
                     // Merge supplementary data, avoid overwriting key fields
                     unset($libraries_io_info['status']);
                     unset($libraries_io_info['error']);
                     $package_info = array_merge($package_info, $libraries_io_info);
                     // Decide on preferred link (JS will handle preferring libraries_io_url if present)
                  } elseif (is_array($libraries_io_info) && isset($libraries_io_info['error'])) {
                      debug_log("Libraries.io query for {$name} resulted in status '{$libraries_io_info['status']}' with error: " . $libraries_io_info['error']);
                      // Optionally add a note about secondary lookup failure?
                      // $package_info['notes'] = ($package_info['notes'] ?? '') . ' Libraries.io lookup failed.';
                  }
              } catch (Exception $e) {
                   debug_log("!!! Exception during Libraries.io query function call for {$name}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                   // $package_info['notes'] = ($package_info['notes'] ?? '') . ' Libraries.io lookup exception.';
              }
         }
    }


    // 4. Final cleanup and safety assessment
    // Ensure essential fields exist
    $package_info['status'] = $package_info['status'] ?? 'Error';
    if ($package_info['status'] === 'Error' && empty($package_info['error'])) {
         $package_info['error'] = 'Unknown processing error after queries.';
    }
    $package_info['link_url'] = $package_info['link_url'] ?? null; // Default registry link
    $package_info['libraries_io_url'] = $package_info['libraries_io_url'] ?? null; // Lib.io link (may be null)
    $package_info['created'] = $package_info['created'] ?? null;
    $package_info['author'] = $package_info['author'] ?? 'N/A';

    // Perform safety assessment
    $safety_assessment = assess_safety($package_info);
    $package_info['safety'] = $safety_assessment['safety'];

    // Clean up internal flags/verbose data before sending?
    unset($package_info['is_builtin']);
    // unset($package_info['libraries_io_rank']); // Example: If you don't need these on the client

    // Add final processed info to results list
    $results[] = $package_info;
    debug_log("--- Finished processing {$name}. Final data for client: " . print_r($package_info, true));
}


// 5. Return results as JSON
debug_log("--- Request Complete. Sending results (" . count($results) . " packages) ---");
// Make sure no accidental output before this line (check includes/requires if any)
// Use flags for better JSON handling if needed
echo json_encode($results, JSON_UNESCAPED_SLASHES);

// Exit cleanly
exit;

?>
