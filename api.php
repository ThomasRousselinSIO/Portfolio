<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

const ADMIN_PASSWORD = 'ThomasRousselin1307*';
const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'data';
const DATA_FILE = DATA_DIR . DIRECTORY_SEPARATOR . 'projects.json';
const UPLOAD_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
const UPLOAD_URL = 'uploads/';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ensureStorage(): void
{
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!file_exists(DATA_FILE)) file_put_contents(DATA_FILE, "[]", LOCK_EX);
}

function projects(): array
{
    ensureStorage();
    $content = file_get_contents(DATA_FILE);
    $data = json_decode($content ?: '[]', true);
    return is_array($data) ? $data : [];
}

function saveProjects(array $projects): void
{
    if (file_put_contents(DATA_FILE, json_encode(array_values($projects), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
        respond(['error' => 'Impossible d\'enregistrer les projets sur le serveur.'], 500);
    }
}

function requireAdmin(?string $password): void
{
    if (!hash_equals(ADMIN_PASSWORD, (string) $password)) {
        respond(['error' => 'Mot de passe administrateur incorrect.'], 403);
    }
}

function cleanName(string $name): string
{
    $name = basename($name);
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'fichier';
    return substr($name, 0, 140);
}

function cleanRelativePath(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $parts = [];

    foreach (explode('/', $path) as $part) {
        $part = trim($part);
        if ($part === '' || $part === '.' || $part === '..') continue;
        $parts[] = cleanName($part);
    }

    return implode('/', $parts) ?: 'fichier';
}

function uploadedRelativePath(array $file, int|string $index, array $paths = []): string
{
    $name = $paths[$index] ?? ($file['name'][$index] ?? 'fichier');
    return cleanRelativePath((string) $name);
}

function removeProjectFiles(array $project): void
{
    foreach (($project['files'] ?? []) as $file) {
        $path = basename((string) ($file['storedName'] ?? ''));
        if ($path !== '') @unlink(UPLOAD_DIR . DIRECTORY_SEPARATOR . $path);
    }
}

ensureStorage();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    respond(projects());
}

$json = json_decode(file_get_contents('php://input') ?: '', true);
$action = $_POST['action'] ?? ($json['action'] ?? '');
$password = $_POST['password'] ?? ($json['password'] ?? null);
requireAdmin(is_string($password) ? $password : null);

$all = projects();

if ($action === 'delete') {
    $id = (string) ($json['id'] ?? $_POST['id'] ?? '');
    foreach ($all as $index => $project) {
        if (($project['id'] ?? '') === $id) {
            removeProjectFiles($project);
            array_splice($all, $index, 1);
            saveProjects($all);
            respond(['ok' => true]);
        }
    }
    respond(['error' => 'Projet introuvable.'], 404);
}

if ($action !== 'save') respond(['error' => 'Action inconnue.'], 400);

$id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_POST['id'] ?? '')) ?: bin2hex(random_bytes(8));
$tags = json_decode((string) ($_POST['tags'] ?? '[]'), true);
$project = [
    'id' => $id,
    'title' => trim((string) ($_POST['title'] ?? '')),
    'description' => trim((string) ($_POST['description'] ?? '')),
    'tags' => is_array($tags) ? array_values(array_filter($tags, 'is_string')) : [],
    'date' => trim((string) ($_POST['date'] ?? '')),
    'link' => trim((string) ($_POST['link'] ?? '')),
    'createdAt' => (int) ($_POST['createdAt'] ?? time() * 1000),
    'files' => [],
];

$existingIndex = null;
foreach ($all as $index => $existing) {
    if (($existing['id'] ?? '') === $id) {
        $existingIndex = $index;
        $project['files'] = $existing['files'] ?? [];
        break;
    }
}

if (isset($_FILES['files']['tmp_name']) && is_array($_FILES['files']['tmp_name'])) {
    $paths = isset($_POST['paths']) && is_array($_POST['paths']) ? $_POST['paths'] : [];
    foreach ($_FILES['files']['tmp_name'] as $index => $temporaryPath) {
        if (!is_uploaded_file($temporaryPath)) continue;
        // Les navigateurs fournissent le chemin relatif lorsqu’un dossier
        // est sélectionné (webkitdirectory/directory).
        $relativePath = uploadedRelativePath($_FILES['files'], $index, $paths);
        $originalName = basename($relativePath);
        $storedName = bin2hex(random_bytes(8)) . '-' . $originalName;
        if (move_uploaded_file($temporaryPath, UPLOAD_DIR . DIRECTORY_SEPARATOR . $storedName)) {
            $project['files'][] = [
                'name' => $originalName,
                'path' => $relativePath,
                'type' => (string) ($_FILES['files']['type'][$index] ?? 'application/octet-stream'),
                'size' => (int) ($_FILES['files']['size'][$index] ?? 0),
                'storedName' => $storedName,
                'url' => UPLOAD_URL . rawurlencode($storedName),
            ];
        }
    }
}

if ($project['title'] === '' || $project['description'] === '') {
    respond(['error' => 'Le titre et la description sont obligatoires.'], 422);
}

if ($existingIndex === null) $all[] = $project;
else $all[$existingIndex] = $project;
saveProjects($all);
respond(['ok' => true, 'project' => $project]);