<?php
// ---- DAWA API proxy endpoints (same file) ----
if (isset($_GET['api']) && $_GET['api'] === 'dawa_autocomplete') {
    header('Content-Type: application/json; charset=utf-8');
    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        echo json_encode([]);
        exit;
    }

    // Use official current host
    $url = 'https://api.dataforsyningen.dk/adresser/autocomplete?q=' . urlencode($q);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "Accept: application/json\r\nUser-Agent: ShareToNeighbour/1.0\r\n"
        ]
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch DAWA autocomplete']);
        exit;
    }

    echo $json;
    exit;
}

if (isset($_GET['api']) && $_GET['api'] === 'dawa_address') {
    header('Content-Type: application/json; charset=utf-8');
    $href = trim($_GET['href'] ?? '');

    if ($href === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid href']);
        exit;
    }

    $parts = parse_url($href);
    $host = strtolower($parts['host'] ?? '');
    $scheme = strtolower($parts['scheme'] ?? '');
    $path = $parts['path'] ?? '';

    // allow both DAWA hosts
    $allowedHosts = ['dawa.aws.dk', 'api.dataforsyningen.dk'];

    // keep strict path check
    if ($scheme !== 'https' || !in_array($host, $allowedHosts, true) || strpos($path, '/adresser/') !== 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid href host/path']);
        exit;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "Accept: application/json\r\nUser-Agent: ShareToNeighbour/1.0\r\n"
        ]
    ]);

    $json = @file_get_contents($href, false, $ctx);
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch DAWA address']);
        exit;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid DAWA response']);
        exit;
    }

    // Handle different DAWA response shapes
    $street = $data['vejnavn']
        ?? ($data['adgangsadresse']['vejstykke']['navn'] ?? '');

    $houseNumber = $data['husnr']
        ?? ($data['adgangsadresse']['husnr'] ?? '');

    $floor = $data['etage'] ?? '';
    $door  = $data['dør'] ?? '';

    $postalCode = (string)($data['postnr']
        ?? ($data['adgangsadresse']['postnummer']['nr'] ?? ''));

    $municipality = $data['kommune']['navn']
        ?? ($data['adgangsadresse']['kommune']['navn'] ?? '');

    $id = $data['id'] ?? '';

    // coordinates prefer [lng,lat]
    $lng = null;
    $lat = null;
    if (isset($data['adgangsadresse']['adgangspunkt']['koordinater']) && is_array($data['adgangsadresse']['adgangspunkt']['koordinater'])) {
        $coords = $data['adgangsadresse']['adgangspunkt']['koordinater'];
        $lng = $coords[0] ?? null;
        $lat = $coords[1] ?? null;
    } else {
        $lng = $data['x'] ?? null;
        $lat = $data['y'] ?? null;
    }

    echo json_encode([
        'id' => $id,
        'street' => $street,
        'house_number' => $houseNumber,
        'apartment' => trim($floor . ' ' . $door),
        'postal_code' => $postalCode,
        'municipality' => $municipality,
        'lat' => $lat,
        'lng' => $lng
    ], JSON_UNESCAPED_UNICODE);
    exit;
}