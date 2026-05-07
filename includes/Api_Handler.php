<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * API HANDLER - DAWA (Danish Address Web API) Proxy Endpoints
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * This file serves as a proxy for DAWA API requests. It handles address 
 * autocomplete and detailed address lookups by forwarding requests to the 
 * official DAWA API endpoints.
 * 
 * Supported endpoints:
 *   - dawa_autocomplete: Autocomplete address suggestions as user types
 *   - dawa_address: Fetch full address details from a DAWA href
 */

// ═══════════════════════════════════════════════════════════════════════════
// ENDPOINT 1: DAWA AUTOCOMPLETE
// ═══════════════════════════════════════════════════════════════════════════
// Function: Provides address autocomplete suggestions based on user input query.
// Purpose: Allows users to search and get suggestions as they type an address.
// Returns: JSON array of matching addresses from DAWA API
// ───────────────────────────────────────────────────────────────────────────

if (isset($_GET['api']) && $_GET['api'] === 'dawa_autocomplete') {
    // Set response content type to JSON with UTF-8 encoding for proper character handling
    header('Content-Type: application/json; charset=utf-8');
    
    // Retrieve and sanitize the search query from GET parameter, trim whitespace
    $q = trim($_GET['q'] ?? '');
    
    // Validate: If query is empty, return empty JSON array and exit
    if ($q === '') {
        echo json_encode([]);
        exit;
    }

    // Build the DAWA autocomplete API URL with the sanitized query parameter (URL-encoded)
    $url = 'https://api.dataforsyningen.dk/adresser/autocomplete?q=' . urlencode($q);

    // Create HTTP stream context with configuration for the request
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',                          // Use GET request method
            'timeout' => 10,                            // Set 10-second timeout to prevent hanging
            // Set headers: Accept JSON format, identify as ShareToNeighbour application
            'header' => "Accept: application/json\r\nUser-Agent: ShareToNeighbour/1.0\r\n"
        ]
    ]);

    // Fetch JSON response from DAWA API using file_get_contents (@ suppresses warnings)
    $json = @file_get_contents($url, false, $ctx);
    
    // Error handling: If request failed (returned false), send 500 error with error message
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch DAWA autocomplete']);
        exit;
    }

    // Output the JSON response directly from DAWA API to the client
    echo $json;
    exit;
}


// ═══════════════════════════════════════════════════════════════════════════
// ENDPOINT 2: DAWA ADDRESS DETAILS
// ═══════════════════════════════════════════════════════════════════════════
// Function: Fetches complete address information from a DAWA href URL.
// Purpose: Retrieves detailed address data (street, postal code, coordinates, etc.)
//          when user selects an autocomplete suggestion.
// Returns: JSON object with normalized address fields (street, house_number, 
//          apartment, postal_code, municipality, coordinates)
// ───────────────────────────────────────────────────────────────────────────

if (isset($_GET['api']) && $_GET['api'] === 'dawa_address') {
    // Set response content type to JSON with UTF-8 encoding for proper character handling
    header('Content-Type: application/json; charset=utf-8');
    
    // Retrieve and sanitize the DAWA href URL from GET parameter, trim whitespace
    $href = trim($_GET['href'] ?? '');

    // Validation: If href is empty, return 400 Bad Request error with error message
    if ($href === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid href']);
        exit;
    }

    // Parse the URL to extract its components (scheme, host, path, etc.)
    $parts = parse_url($href);
    
    // Extract and normalize URL components (convert to lowercase for case-insensitive comparison)
    $host = strtolower($parts['host'] ?? '');
    $scheme = strtolower($parts['scheme'] ?? '');
    $path = $parts['path'] ?? '';

    // Define list of allowed DAWA API hosts to prevent SSRF attacks
    $allowedHosts = ['dawa.aws.dk', 'api.dataforsyningen.dk'];

    // SECURITY CHECK: Validate URL scheme (must be HTTPS), host (must be in allowlist), 
    // and path (must start with /adresser/ to ensure valid DAWA endpoint)
    if ($scheme !== 'https' || !in_array($host, $allowedHosts, true) || strpos($path, '/adresser/') !== 0) {
        // Reject invalid URLs with 400 Bad Request
        http_response_code(400);
        echo json_encode(['error' => 'Invalid href host/path']);
        exit;
    }

    // Create HTTP stream context with configuration for the request
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',                          // Use GET request method
            'timeout' => 10,                            // Set 10-second timeout to prevent hanging
            // Set headers: Accept JSON format, identify as ShareToNeighbour application
            'header' => "Accept: application/json\r\nUser-Agent: ShareToNeighbour/1.0\r\n"
        ]
    ]);

    // Fetch JSON response from the validated DAWA href URL (@ suppresses warnings)
    $json = @file_get_contents($href, false, $ctx);
    
    // Error handling: If request failed (returned false), send 500 error
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch DAWA address']);
        exit;
    }

    // Decode JSON response into associative array for data extraction
    $data = json_decode($json, true);
    
    // Validate: If decode failed or result is not an array, send 500 error
    if (!is_array($data)) {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid DAWA response']);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATA NORMALIZATION: Extract and normalize address fields
    // DAWA API can return data in different structures, so use null coalescing
    // operator (??) to handle multiple possible field locations
    // ─────────────────────────────────────────────────────────────────────────

    // Extract street name from either top-level 'vejnavn' or nested 'vejstykke.navn'
    $street = $data['vejnavn']
        ?? ($data['adgangsadresse']['vejstykke']['navn'] ?? '');

    // Extract house number from either top-level 'husnr' or nested 'adgangsadresse.husnr'
    $houseNumber = $data['husnr']
        ?? ($data['adgangsadresse']['husnr'] ?? '');

    // Extract floor number (etage) from response, default to empty string if not present
    $floor = $data['etage'] ?? '';
    
    // Extract door number (dør) from response, default to empty string if not present
    $door  = $data['dør'] ?? '';

    // Extract postal code, try top-level 'postnr' first, then nested path, cast to string
    $postalCode = (string)($data['postnr']
        ?? ($data['adgangsadresse']['postnummer']['nr'] ?? ''));

    // Extract municipality name from either top-level or nested 'kommune.navn' path
    $municipality = $data['kommune']['navn']
        ?? ($data['adgangsadresse']['kommune']['navn'] ?? '');

    // Extract unique address ID from DAWA response
    $id = $data['id'] ?? '';

    // ─────────────────────────────────────────────────────────────────────────
    // COORDINATES EXTRACTION: Handle different DAWA response coordinate formats
    // DAWA returns coordinates in [longitude, latitude] GeoJSON format
    // Prefer nested 'adgangspunkt.koordinater', fall back to top-level 'x' and 'y'
    // ─────────────────────────────────────────────────────────────────────────

    // Initialize longitude and latitude as null
    $lng = null;
    $lat = null;
    
    // Check if nested coordinates array exists and is an array (primary format)
    if (isset($data['adgangsadresse']['adgangspunkt']['koordinater']) && is_array($data['adgangsadresse']['adgangspunkt']['koordinater'])) {
        // Get the coordinates array
        $coords = $data['adgangsadresse']['adgangspunkt']['koordinater'];
        // Extract longitude from first position (index 0)
        $lng = $coords[0] ?? null;
        // Extract latitude from second position (index 1)
        $lat = $coords[1] ?? null;
    } else {
        // Fall back to top-level 'x' (longitude) and 'y' (latitude) if nested format not present
        $lng = $data['x'] ?? null;
        $lat = $data['y'] ?? null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OUTPUT: Send normalized address data as JSON response to frontend
    // JSON_UNESCAPED_UNICODE preserves Danish special characters (æ, ø, å)
    // ─────────────────────────────────────────────────────────────────────────

    echo json_encode([
        'id' => $id,                                                    // Unique DAWA address ID
        'street' => $street,                                            // Street name
        'house_number' => $houseNumber,                                // House/building number
        'apartment' => trim($floor . ' ' . $door),                    // Floor and door combined, trimmed
        'postal_code' => $postalCode,                                 // Postal code (ZIP)
        'municipality' => $municipality,                              // City/municipality name
        'lat' => $lat,                                                 // Latitude coordinate
        'lng' => $lng                                                  // Longitude coordinate
    ], JSON_UNESCAPED_UNICODE);                                        // Preserve Unicode characters (Danish letters)
    
    exit;
}