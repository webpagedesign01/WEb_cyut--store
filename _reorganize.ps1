# CYUTFest - Reorganize for WampServer
# =============================================

Write-Host "`n=== Step 1: Creating directories ===" -ForegroundColor Cyan
@('config', 'includes', 'assets\css', 'assets\js', 'auth', 'organizer', 'seller', 'customer') | ForEach-Object {
    New-Item -ItemType Directory -Force -Path $_ | Out-Null
}
Write-Host "  Directories created"

Write-Host "`n=== Step 2: Moving files ===" -ForegroundColor Cyan
$moves = [ordered]@{
    'database.php'            = 'config\database.php'
    'header.php'              = 'includes\header.php'
    'navbar.php'              = 'includes\navbar.php'
    'footer.php'              = 'includes\footer.php'
    'style.css'               = 'assets\css\style.css'
    'main.js'                 = 'assets\js\main.js'
    'login.php'               = 'auth\login.php'
    'register.php'            = 'auth\register.php'
    'logout.php'              = 'auth\logout.php'
    'dashboard_org.php'       = 'organizer\dashboard.php'
    'create-event.php'        = 'organizer\create-event.php'
    'manage-events.php'       = 'organizer\manage-events.php'
    'seller-applications.php' = 'organizer\seller-applications.php'
    'event-orders.php'        = 'organizer\event-orders.php'
    'dashboard_sel.php'       = 'seller\dashboard.php'
    'apply-event.php'         = 'seller\apply-event.php'
    'manage-store.php'        = 'seller\manage-store.php'
    'manage-products.php'     = 'seller\manage-products.php'
    'manage-orders.php'       = 'seller\manage-orders.php'
    'chat.php'                = 'seller\chat.php'
    'dashboard_cus.php'       = 'customer\dashboard.php'
    'events.php'              = 'customer\events.php'
    'stores.php'              = 'customer\stores.php'
}
foreach ($src in $moves.Keys) {
    $dst = $moves[$src]
    if (Test-Path $src) {
        Move-Item -Force $src $dst
        Write-Host "  $src -> $dst"
    } else {
        Write-Host "  SKIP $src (already moved or not found)" -ForegroundColor Yellow
    }
}

Write-Host "`n=== Step 3: Adding BASE_URL to database.php ===" -ForegroundColor Cyan
$utf8 = New-Object System.Text.UTF8Encoding $false
$dbPath = Join-Path $PWD 'config\database.php'
$db = [System.IO.File]::ReadAllText($dbPath)
if ($db -notmatch 'BASE_URL') {
    $db = $db.Replace(
        "define('DB_CHARSET', 'utf8mb4');",
        "define('DB_CHARSET', 'utf8mb4');`ndefine('BASE_URL', '/cyutfest');"
    )
    [System.IO.File]::WriteAllText($dbPath, $db, $utf8)
    Write-Host "  Added BASE_URL = '/cyutfest'"
} else {
    Write-Host "  BASE_URL already defined" -ForegroundColor Yellow
}

Write-Host "`n=== Step 4: Fixing absolute paths in all PHP files ===" -ForegroundColor Cyan
Get-ChildItem -Path . -Recurse -Filter *.php | ForEach-Object {
    $content = [System.IO.File]::ReadAllText($_.FullName)
    $original = $content

    # Fix PHP header() redirects: 'Location: /x -> 'Location: ' . BASE_URL . '/x
    $content = $content -replace "'Location: /", "'Location: ' . BASE_URL . '/"

    # Fix HTML href="/..." -> href="<?= BASE_URL ?>/..."
    $content = $content -replace 'href="/', 'href="<?= BASE_URL ?>/'

    # Fix HTML src="/..." -> src="<?= BASE_URL ?>/..."
    $content = $content -replace 'src="/', 'src="<?= BASE_URL ?>/'

    if ($content -ne $original) {
        [System.IO.File]::WriteAllText($_.FullName, $content, $utf8)
        Write-Host "  Fixed: $($_.Name)"
    }
}

Write-Host "`n=== Step 5: Fixing navbar dynamic links ===" -ForegroundColor Cyan
$navPath = Join-Path $PWD 'includes\navbar.php'
$nav = [System.IO.File]::ReadAllText($navPath)
$search = "<?= `$item['href'] ?>"
$replace = "<?= BASE_URL . `$item['href'] ?>"
if ($nav.Contains($search)) {
    # Only replace the href="<?= $item['href'] ?>" output, not the basename() usage
    $nav = $nav.Replace($search, $replace)
    [System.IO.File]::WriteAllText($navPath, $nav, $utf8)
    Write-Host "  Fixed navbar href output to use BASE_URL"
}

Write-Host "`n==============================" -ForegroundColor Green
Write-Host "COMPLETE! Project restructured." -ForegroundColor Green
Write-Host "Copy to: C:\wamp64\www\cyutfest\" -ForegroundColor Green
Write-Host "==============================`n" -ForegroundColor Green
